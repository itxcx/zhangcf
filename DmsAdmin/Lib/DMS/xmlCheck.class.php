<?php
class xmlCheck
{
	static function check()
	{
		//php版本检查========================================================================================
		if(version_compare(PHP_VERSION,'5.3.0','<')){
			throw_exception("【PHP版本不能低于5.3.0】---因系统采用较多高版本函数");
		}
		
		//检查php时区和mysql时区差异，同样的时间戳，用mysql函数转化会出现不同结果============================
		$mysqlQ=M()->query("select now() as time");
		$mysqlTime=strtotime($mysqlQ[0]['time']);
		$phpTime=time();
		if(abs($mysqlTime-$phpTime)>20){//超过10秒
			throw_exception("PHP时间和MySQL时间不一致，请联系服务器管理员，检查PHP时区和MySQL时区是否一致");
		}
		
		//检查数据库是否支持innoDB===========================================================================
		$engines = M()->query("show engines");
		$find_innodb=false;
		foreach($engines as $engine)
		{
			if($engine['Engine'] == 'InnoDB' && ($engine['Support'] == 'YES' || $engine['Support'] == 'DEFAULT'))
			{
				$find_innodb=true;
			}
		}
		if(!$find_innodb)
		{
			throw_exception('当前数据库环境不支持innodb环境,请使用支持InnoDB引擎的数据库,修正后请重新还原数据库');
		}
		
		//判断表的类型是否是InnoDB引擎(目前sqlxml库已经对引擎规范化，和当前数据表对比)========================
		$syncM=D("DmsAdmin://SyncDmsAdminAdmin");
		//返回数据库表字符串
		$xml=$syncM->getAllxml();
		if($xml === false)
		{
			echo '对应表的XML文件有语法错误';
			die;
		}
		$dbname = C('DB_NAME');//获取数据库名
		//所有innodb数据库
		$innodbary=array();
		$all_innodb=M()->query("show table status from {$dbname} where Engine<>'InnoDB'");
		foreach($all_innodb as $innodb){
			$innodbary[]=$innodb['Name'];
		}
		foreach($xml->xpath('./table') as $v_t)
		{
			if(strtolower($v_t['engine'])=='innodb' && in_array("dms_".$v_t['name'],$innodbary)){
				$sql="alter table dms_".$v_t["name"]." engine=InnoDB";
				M()->query($sql);
				//throw_exception('【dms_'.$v_t["name"].'】表当前的引擎不是InnoDB,请修改成InnoDB类型');
			}
		}
		//检查是否禁用了关键函数==============================================================================
		if(ini_get("disable_functions")!='')
		{
			$disable_functions=explode(',',ini_get("disable_functions"));
			if(in_array('eval',$disable_functions))
			{
				throw_exception('php配置文件禁止了eval函数的执行,请检查配置文件中的disable_functions设定.');
			}
		}
		
		//回填设置
		if((adminshow('admin_backfill') || adminshow('admin_blank')) && (adminshow('user_bank_backfill') || adminshow('admin_bank_backfill')) && X('user')->backbank==''){
			throw_exception('扣币回填请在XML中，设置user节点的backbank(货币名称)属性.');
		}
		//config配置信息有重名的检测,一般xml有重复的会出现此问题，xml重复也已经修正过===name字段已设为唯一属性
		$repeat_con=M()->query("select name from config group by concat(name) having count(*)>1");
		if($repeat_con){
			throw_exception("CONFIG配置表中存在相同name名【".$repeat_con[0]['name']."】的数据，请检查同名的【".$repeat_con[0]['name']."】存在于哪个位置并进行修复");
		}
		//xml检查=============================================================================================
		$con='';
		$allxname=array();
		$xmldom=new DOMDocument();
		$xmldom->load(ROOT_PATH."/DmsAdmin/config.xml",LIBXML_NOBLANKS);
		$xmldomlist=$xmldom->getElementsByTagName("con");
		$xml=$xmldomlist->item(0);
		$xpath = new DOMXPath($xml->ownerDocument);
		//初次安装后模板设置==================================================================================
		$nowTheme = CONFIG("DEFAULT_THEME");
		if(empty($nowTheme)){
			$DEFAULT_THEME=trim($xml->getAttribute('theme'));
			if(!empty($DEFAULT_THEME)){
				//验证填写的模板是否存在
				$themePath='./DmsAdmin/Tpl/User/';
				if(is_dir($themePath)){
					$handle1		= opendir($themePath);
					$haveTheme=false;
					while(false!==($filename = readdir($handle1))){
						if(is_dir($themePath.$filename) && $filename!='.' && $filename!='..' && $filename!='login' && $filename != '.svn' && $filename != 'core'){
							if($filename==$DEFAULT_THEME) {$haveTheme=true;break;}
						}
					}
					if(!$haveTheme){
						throw_exception("XML中con节点设置的theme=模板名【".$DEFAULT_THEME."】并不存在，请重新设置或删除theme属性");
					}
					CONFIG('DEFAULT_THEME',$DEFAULT_THEME);
				}else{
					throw_exception("系统缺少前台模板");
				}
			}else{
				CONFIG('DEFAULT_THEME','blanc_default');
			}
		}
		//对配置项进行校验
		$tleset = $xpath->query('tleset//*',$xml);
		$connames = array();
		foreach($tleset as $v)
		{
			if(in_array($v->nodeName,array('input','select')))
			{
				$name=$v->getAttribute('name');
				if(is_numeric($name))
				{
					throw_exception("奖金配置节点的name值存在纯数字");
				}
				if(isset($connames[$name]))
				{
					throw_exception("存在重复的奖金配置名称:".$name);
				}
				//判断xml文件有没有增加配置信息
				$tmpconfig = CONFIG($name);
				if(!isset($tmpconfig)){
					CONFIG($name , $v->getAttribute('value'));
				}
				$connames[$name]=$v->getAttribute('xml');
			}
		}
		$usenames = array();
		$tmpnodeName = '';
		$tmpcon = array();
		$nodeNamearr = array();
		$userset = $xpath->query('user//*',$xml);
		//配置项可能不存在的判断
		foreach($userset as $v)
		{
			$nodeName=$v->nodeName;
			if(strpos($nodeName,'prize')!==false){
				$tmpnodeName = $nodeName;
			}
			foreach($v->attributes as $attkey=>$attval)
			{
				$attval = $v->getAttribute($attkey);//节点的属性值
				preg_match_all('/#(.*)#/U',$attval,$truevals,PREG_SET_ORDER);//匹配当前节点，属性类似#多个字符串#，生成数组
				/******************判断rely属性值是否有存在操作节点*******************************/
				if($attkey=='name')	$nodeNameArr[]=$attval;
				
				if($attkey=='rely' && $attval!=''){
					$relyarr = explode(',',$attval);
					foreach($relyarr as $val){
						if(!in_array($val,$nodeNameArr)){
							throw_exception('发现【'.$attkey.'】的节点并没有其所依赖的操作节点【'.$val.'】');
						}
					}
				}
				/*************************************************/
				/***********判断极差中百分号的有无是否一致**************************/
				if($nodeName!=$tmpnodeName && strpos($tmpnodeName,'diff')>0 && $nodeName=="_con")
				{
					if($attkey=='val') $tmpcon[$tmpnodeName][]=strpos($attval,'%')?1:0;
				}
				/***********************************************************************/
				
				/*********对所有addval的to判断节点是否存在.并有event_valadd事件方法*******/
				if($nodeName == '_addval'){
					if($attkey == 'to'){
						$newto = $attval;
						if(strpos($attval,'_'))$newto = substr($attval,strpos($attval,'_')+1);
						$obj=X('@'.$newto);
						if(empty($obj->name)){
							throw_exception('发现【'.$newto.'】的节点并没有创建');
						}
						if(!method_exists($obj,'event_valadd'))
						{
							throw_exception('发现addval操作目标【'.$newto.'】所在类'.get_class($obj).'中并没有event_valadd进行处理');
						}
					}
				}
				/***********************************************************************/

			  	if(count($truevals)>0){
					foreach($truevals as $trueval)
					{	
						$msg='';
						$setName=$trueval[1];//tleset中的标签
						//判断_top-2-3这种 #@#
						if(strstr($setName,"@")){
							$setPrefix=substr($setName,0,strlen($setName)-1);
							//判断节点是否符合规则
							if(substr($nodeName,0,1)!='_'){
								$msg.="奖金引用的配置项:".$setName.'，内部的@只能用在"_标签"内！！';
							}
							$numary=explode("-",$nodeName);
							if(count($numary)<3){
								$msg.="奖金引用的配置项:".$setName.'，对应的节点'.$nodeName.'的必须设置为至少含有"_标签-数字-数字"的形式！！';
							}
							if($numary[1]<1 || $numary[2]<1){
								$msg.="奖金引用的配置项:".$setName.'，对应的节点'.$nodeName.'的前2个数字必须为正整数！！';
							}
							if($numary[1]>=$numary[2]){
								$msg.="奖金引用的配置项:".$setName.'，对应的节点'.$nodeName.'的第2个数字必须大于第1个数字！！';
							}
							if($msg!=''){
								throw_exception($msg);
							}
							for($num=$numary[1];$num<=$numary[2];$num++){
								$setName=$setPrefix.$num;
								if(!isset($connames[$setName]))
								{
									throw_exception("奖金引用的配置项:".$setName.'在tleset中并没有定义');
								}
								if(!in_array($setName,$usenames))
								{
									$usenames[]=$setName;
								}
							}
						}else{
							if(!isset($connames[$setName]))
							{
								throw_exception("奖金引用的配置项:".$setName.'在tleset中并没有定义');
							}
							if(!in_array($setName,$usenames))
							{
								$usenames[]=$setName;
							}
						}
					}
				}
			}
		}
		if(!empty($tmpcon)){
			foreach($tmpcon as $k=>$item){
				if(count(array_unique($item))>1){
					throw_exception($k."奖金配置项中百分号的使用不一致");
				}
			}
		}

		//从定义的配置节点中去掉被使用过的的节点。然后看是否有被忽略的
		foreach($usenames as $usename)
		{
			unset($connames[$usename]);
		}
		//找到所有没有被使用的配置项
		foreach($connames as $conname=>$usexml)
		{
			if($usexml !== 'false')
			{
				throw_exception("奖金配置项:".$conname.",在声明之后没有被使用(是否前后漏写'#'),如此参数要在其他位置使用,请在xml节点中设置 xml='false' 属性");
			}
		}
		//对自动进网但是没有进行addval set的进行警告
		foreach($xpath->query("user/net_place",$xml) as $Node)
		{
			//需要对安置网不进网的判定.因为没有设置set='1'
			//$Node->getAttribute('autoset');
			//{
			// 
			//}
		}
		//判断系统中是否有没有进行升级节点配置
		//获取user中的levels节点
		$userobj = X('user');
		//级别信息
		$areaAry=array("country","province","city","county","town");
        foreach(X('levels') as $levels)
        {
        	$j=0;$i=0;
        	$area=array();$lvary=array();
			foreach($levels->getcon("con",array("name"=>"","lv"=>"","area"=>"")) as $lvconf)
			{
				$j++;
				//存在重复的lv值
				if(in_array($lvconf['lv'],$lvary)){
					throw_exception('levels节点关于lvname='.$levels->name.'; lv=【'.$lvconf['lv'].'】属性值不能重复');
				}
				$lvary[]=$lvconf['lv'];
				
				//判断区域代理重复
				if($levels->area && $lvconf['area']!=''){
					$i++;
					if(!in_array($lvconf['area'],$areaAry)){
						throw_exception('levels节点关于lvname='.$levels->name.'; area=【'.$lvconf['area'].'】属性值填写有误，只能填写'.implode(",",$areaAry));
					}
					if(in_array($lvconf['area'],$area)){
						throw_exception('levels节点关于lvname='.$levels->name.'; area=【'.$lvconf['area'].'】属性值不能重复');
					}
					$area[]=$lvconf['area'];
				}
 			}
 			//判断con的area
 			if($levels->area && $i==0){
 				throw_exception('levels节点关于lvname='.$levels->name.'; 如果此级别不是选择区域升级，请取消area属性');
 			}
 			if($j>1){
 				$f=0;
 			   //判断是否设置了这个级别
 			   foreach(X('sale_up') as $sale_up){
 			       if($sale_up->lvName==$levels->name && $sale_up->user=='admin'){
 			          $f=1;
 			          break;
 			       }
 			   }
 			   if($f==0 && !$userobj->noup){
 			      throw_exception('sale_up节点关于lvname='.$levels->name."; user=admin的节点并没有创建，如果确定客户没有升级需求,请在user节点设置noup='true'");
 			   }
 			}
        }
        //节点重名
		$childs = X();
		$childsname = array();
		foreach($childs as $child){
			if(in_array($child->name,$childsname)){
				throw_exception("xml配置的user节点下的name属性($child->name)不能重复,请用byname代替");
			}
			
			if($child->name !=''){
				$childsname[] = $child->name;
			}
		}
		//如果有以推荐人数作为条件。要强制指定空点是否算推荐人数
		foreach(X('net_rec') as $net)
		{
			
			foreach($xpath->query('//*',$xml) as $v)
			{
				foreach($v->attributes as $attkey => $attval)
				{
					if($net->nullRecer == '' && strpos($v->getAttribute($attkey),$net->name.'_推荐人数') !== false)
					{
						//dump($v->getAttribute($attkey));
						throw_exception("系统存在对[".$net->name.'_推荐人数]的判断.需要在net_rec中指定nullRecer属性,设置空点是否算推荐人数true为是,false为否,如不清楚请与客服人员进行确定.');
					}
				}
			}
		}
		
	}
}
?>