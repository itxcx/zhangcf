<?php
	/*XML节点获取和操作函数
	X函数用于获得XML中的各个节点对象.
	语法结构如下
	X('user');     返回user节点,非数组,除user节点之外,直接写任何节点名都会返回数组
	
	X('net_place');以数组的形式返回所有net_place节点
	
	X('net_place@');返回第一个net_place节点
	
	X('net_place@管理');返回name属性等于'管理'的net_place节点
	
	X('@管理');            返回name属性等于'管理'的任意节点
	
	X('sale_*');           已数组形式返回所有sale_*开头的节点
	
	X('sale_reg,sale_up'); 以数组的形式返回所有sale_reg和sale_up节点
	
	X('prize_*',$tleobj);  以数组形式返回对象之下的所有奖金节点
	
	X('parent',$obj);      根据obj节点对象,返回其父节点
	
	X('pos',$obj);         返回节点在父对象中的位置
	
	X('>fun_bank');            根据args参数获得特定fun_bank对象,如果获得对象不是fun_bank则会报错
	
	X('>');               根据args参数获得任意对象
	
	X('>fun_bank',$args); 根据args参数的节点位置获得fun_bank对象
	
	X('>',$args);         根据args参数获得任意对象
	
	X函数的运行机制
	X函数负责加载config.xml创建DOMDocument对象，对xml文件进行解读
	X函数根据第一个参数，转换成DOMXPath的查询语句，查询出对应的DOMNode对象
	执行xmlinstance(DOMNode,&$cache),得到具体节点对象
	
	xmlinstance根据传入的DOMNode的nodeName属性，加载类文件
	并创建一个对象，把DOMNode对象抛入要创建类库的构造函数
	构造函数由stru类实现
	
	stru的构造函数要实现的功能
	遍历对象自身的属性以及DOMNode中的属性
	对创建的对象的默认属性进行覆盖
	根据CONFIG中的特定配置信息，对象属性进行覆盖。
	设置对象的byname属性
	返回对象，给xmlinstance，xmlinstance返回给X，X返回查询的对象
	*/
	function xmlinstance($Node,&$cache)
	{
		//查询缓存是否已经创建其实例
		if(isset($cache[$Node->getNodePath()]))
		{
		     return $cache[$Node->getNodePath()];
		}
		import('DmsAdmin.DMS.stru');
		//如果未在缓存中命中,则开始引用文件创建对象
		$pos=strpos($Node->nodeName,'_');
		if($pos !== false && $pos != 0)
		{
			$pclass = substr($Node->nodeName,0,$pos);
			if(!import('DmsAdmin.DMS._'.$pclass)){
				import('DmsAdmin.DMS.'.$pclass);
			}
			//if(!import('DmsAdmin.DMS.'.$pclass))
			//	throw_exception('DmsAdmin.DMS.'.$pclass.'类导入失败，请检查模块是否存在或命名是否规范');
		}
		if(!import('DmsAdmin.DMS._'.$Node->nodeName)){
			if(!import('DmsAdmin.DMS.'.$Node->nodeName))
				throw_exception('DmsAdmin.DMS.'.$Node->nodeName.'类导入失败，请检查模块是否存在或命名是否规范');
		}
		$cache[$Node->getNodePath()] = new $Node->nodeName($Node);
		return $cache[$Node->getNodePath()];
	}
	function X($name='',$parent = null)
	{
		
		//定义XML文档静态变量
		static $dom = null;
		//定义
		static $XPath = null;
		static $cache=array();
		//定义构建新对象的函数
		//*****************************开始实际执行部分开始*************************
		//
		if($dom===null)
		{
			$dom =new DOMDocument();//建一个DOMDocument对象
			$dom->load(ROOT_PATH."/DmsAdmin/config.xml",LIBXML_NOBLANKS);// 加载Xml文件    
			$xmlerr=libxml_get_last_error();//从 libxml 错误缓冲中获取最后一个错误
			if($xmlerr)
			{
				throw_exception("您的xml文件格式有误,错误行".$xmlerr->line."行,错误内容".htmlentities($xmlerr->message));
			}
			$XPath = new DOMXPath($dom);
		}
		//识别对象约束
		//
		$whereexp=array();
		if(strpos($name,'[')!==false)
		{
			preg_match_all('/\[([a-zA-Z0-9]+)\s*([\!>=<]+)\s*(.*)\]/',$name,$matchs);
			for($i=0;$i<count($matchs[0]);$i++){
				$whereexp[] = array($matchs[1][$i],$matchs[2][$i],$matchs[3][$i]);
			}
		}
		//如果等于''则取出包括USER在内的所有对象
		if($name=='')
		{
			$NodeList = $XPath->query('/con/user|/con/user//*');
			$ret=array();
			foreach($NodeList as $Node)
			{
				if(substr($Node->nodeName,0,1) !== '_')
					$ret[]=xmlinstance($Node,$cache);
			}
			return $ret;
		}
		//通过args参数得到对象
		if($name != '' && substr($name,0,1) == '>' && $parent!=null)
		{			
			//合成完整参数
			$path='/con/user/'.str_replace('-','/',$parent);
			//处理查询
			$NodeList = $XPath->query($path);
			if($NodeList->length!=1)
			{
				throw_exception("获得节点失败,未找到特定节点");
			}
			if($NodeList->item(0)->nodeName != substr($name,1) && substr($name,1)!='')
			{
				throw_exception("args获得的节点类型不正确,要求获得".substr($name,1).'节点,实际获得了'.$NodeList->item(0)->nodeName);
			}
			return xmlinstance($NodeList->item(0),$cache);
		}
		//判定是否为user
		if($name == 'user')
		{
			$path="/con/user";
			$NodeList = $XPath->query($path);
			return xmlinstance($NodeList->item(0),$cache);
		}
		if($name == 'parent')
		{
			if($parent==null)
			{
				throw_exception("获得父节点失败.未传入参数节点");
			}
			return xmlinstance($parent->xml->parentNode,$cache);
		}
		//取得节点基于父节点的位置
		if($name == 'pos')
		{
			$ret=1;
			foreach($XPath->query("./*",$parent->xml->parentNode) as $node)
			{
				if($parent->xml->getNodePath()== $node->getNodePath())
				{
					return $ret;
				}
				else
				{
					$ret++;
				}
			}
			return false;
		}
		//设置name查询值
		
		//其他的以数组形式返回的节点
		$ret=array();
		foreach(explode(',',$name) as $name)
		{
			$findname=null;
			if(strpos($name,'@') !== false)
			{
				$findname=substr($name,strpos($name,'@')+1);
				$name=substr($name,0,strpos($name,'@'));
			}
			if(strpos($name,'*') !== false || $name=='')
			{
				$path = ($parent) ? ".//*" : "/con/user//*";
			}
			else
			{
				$path = ($parent) ? ".//".$name : "/con/user//".$name;
			}
			
			if($findname !== null && $findname!='')
			{
				$path.="[@name='".$findname."']";
			}
			if($parent)
			{
				$NodeList = $XPath->query($path,$parent->xml);
			}
			else
			{
				$NodeList = $XPath->query($path);
			}
			
			//寻找到
			$leftstr=substr($name,0,strpos($name,'*'));
			
			foreach($NodeList as $Node)
			{
				if($leftstr == '' || strpos($Node->nodeName,$leftstr)===0 )
				{
					if(substr($Node->nodeName,0,1) != '_')
						$ret[] = xmlinstance($Node,$cache);
				}
			}
		}
		if($findname === null)
			return xfilter($ret,$whereexp);
		else
		{
			if(count($ret)==0)
			{
				return null;//throw_exception("未找到名字为".$findname.'的节点');
			}
			else
			{
				return $ret[0];
			}
		}
	}
	//
	function xfilter($ret,$whereexp)
	{
		//如果没有额外判定条件直接返回
		if(!$whereexp)
			return $ret;
		
		if(is_array($ret))
		{
			foreach($ret as $key=>$r)
			{
				if(!xfilter($r,$whereexp))
				unset($ret[$key]);
			}
			return $ret;
		}
		else
		{
			foreach($whereexp as $exp)
			{
				//如果没有找到要判定的属性
				if(!property_exists($ret,$exp[0]))
				{
					//unset($ret[$key]);
					return null;
				}
				if($exp[1]=='=')
				{
					throw_exception('X函数属性判断不支持=如果要做等于判断,请使用==');
				}
				eval('$exp=($ret->'.$exp[0].$exp[1].$exp[2].');');
				if(!$exp)
				{
					return null;
				}
			}
			return $ret;
			//判断当前对象如果不符合，则直接返回null否则返回该对象
		}
	}
	
	
	
	
	/*增加或者减少会员的货币
		参数说明:bankset(货币名称,会员编号,金额,货币类型,备注);
		
		实例
		bankset('电子货币','cn0001',100,'系统测试','这个是一个系统测试');
		
		如果货币涉及到双方,则有一个可选参数
		
		bankset('电子货币','cn0001',100,'订单审核','为cn0002审核订单','cn0002');
	*/
	function bankset($bankname,$name,$num,$mode,$memo,$toname = NULL)
	{	
		$bank=X('fun_bank@'.$bankname);

		if($toname === NULL)
			$toname=$name;
		if(!$bank)
		{
			throw_exception('bankset操作并未找到对应的货币模块。名称为['.$bankname.']');
		}
		$bank->set($name,$toname,$num,$mode,$memo);
	}
	/*字符串条件动态判定
	系统中经常使用的函数
	参数说明:
	transform(条件表达式,基本数组,二维前缀数组,是否得到结果);
	基本案例
	transform('1=1');返回值为true
	transform('1+1');返回值为2
	
	基本数组案例
	transform('[报单金额]=100',array('报单金额'=>'100'));返回值为true
	执行原理,中括号中的内容会根据$data数组寻找值,把表达式'[报单金额]=100'替换为,'100=100'然后再计算表达式
	
	二维前缀数组案例
	transform('S[报单金额]=100 and M[会员级别]=1',array(),
		array(
		'S'=>array('报单金额'=>100),
		'M'=>array('会员级别'=>2)
		)
	);返回值为false  表达式'S[报单金额]=100 and M[会员级别]=1' 被替换成'100=100 && 2=1',在进行计算
	
	如果$execute为false 的情况下,则返回被处理后的表达式'100=100 && 2=1'
	*/
	function transform($form,$data=array(),$bydata=array(),$execute=true)
	{
		//定义预编译条件静态数组
	   	static $compile=array();
	   	if($form=="")
			return true;
		//定义预编译数组名称
		$comname = $form.(string)$execute;
		if(!isset($compile[$comname]))
		{
			$form=str_replace(">>","<",$form);
			$preg_str=array();
			/*
			正则字符串缓存，如果参数中含有原生的正则判断代码，
			那么正则表达式当中可能存在中括号内容，会被进行替换识别
			所以需要预先识别并替换成临时字符标签
			*/			
			if(strpos($form,'preg_match') !== false)
			{
				preg_match_all('/preg_match\(.+\) = 1/Uis',$form,$pregform,PREG_SET_ORDER);
				foreach($pregform as $key=>$preg)
				{
					$preg_str[$key]=$preg[0];
					$form = str_replace($preg[0],'{$preg'.$key.'}',$form);
				}
			}
			preg_match_all('/(?<!\$_REQUEST)(?<!\$_POST)(?<!\$_GET)([A-Z]?)\[(.*)\]/Uis',$form,$trform,PREG_SET_ORDER);
			foreach($trform as $val)
			{
				if($val[1]=='')
				{
					$arrname='$data';
					$replaceData=&$data;
				}
				else
				{
					if(!isset($bydata[$val[1]]))
					{
						throw_exception('transform运行失败，处理判定'.$val[0].'时,不存在'.$val[1].'设定项');
					}
					$replaceData=&$bydata[$val[1]];
					$arrname='$bydata[\''.$val[1].'\']';
				}
				if(!isset($replaceData[$val[2]]))
				{
					throw_exception('transform运行失败，需要的替换项目未找到['.$val[2].']');
				}
				//$data_value = is_string($replaceData[$val[2]])?"'{$replaceData[$val[2]]}'":$replaceData[$val[2]];
				$data_value=$arrname.'[\''.$val[2].'\']';
				$form=str_replace($val[0],$data_value,$form);
				
			}
			//正则字符串缓存反替换
			if(count($preg_str)>0)
			{
				foreach($preg_str as $key=>$preg)
				{
					$form = str_replace('{$preg'.$key.'}',$preg,$form);
				}
			}
			if($execute)
			{
				$patterns			= array( '/ and /'	, '/ or /'	, '/=/'	, '/>==/'	, '/<==/'	,'/!==/');
				$replacements		= array( ' && '		, ' || '	, '=='	, '>='		, '<='		,'!=');
				$form				= preg_replace($patterns, $replacements, $form);
			}
			$compile[$comname] = $form;
		}
		else
		{
			$form=$compile[$comname];
		}
		if($execute)
		{
			if(eval('$result=('.$form.');')===false)
			{
				throw_exception('transform运行错误$result=('.$form.')');
			}
		}
		else
		{
			$result=$form;
		}
		return $result;
	}
	/*
		文件锁定
	*/
	function lockfile($filename="",$op=""){
		static $fp = array();
		$lockfile=ROOT_PATH.$filename.'.lock';
		//文件不存在创建文件并锁定
    	if(!is_lockfile($filename)){
		   	$fp[$filename] = fopen($lockfile,"w+");
		   	flock($fp[$filename],LOCK_EX);
		   	fwrite($fp[$filename],$op);
		   	return true;
		}
		//判定锁定状态
		$fp1 = fopen($lockfile,"r");
		if(!flock($fp1,LOCK_EX|LOCK_NB)){
			return false;
		}
		return true;
	}
	/*
		判断文件锁程序
	*/
	function is_lockfile($filename=""){
		$lockfile=ROOT_PATH.$filename.'.lock';
		//判断文件是否存在
		if(!file_exists($lockfile)){
			return false;
		}
		//判定锁定状态
		$fpp = fopen($lockfile,"r");
		if(!flock($fpp,LOCK_EX|LOCK_NB)){
			return true;
		}
		return false;
	}
	//密码加密
	function md100($str)
	{
		for($md_i=0;$md_i <= 100;$md_i++)
		{
			$str=md5($str);
		}
		return $str;
	}
/*密码校验
	chkpass(密码明文,要对比的密文)
*/
function chkpass($Expressly,$Ciphertext)
{
	if(md100($Expressly) == $Ciphertext)
	{
		return true;
	}
	//如同时要兼容其他加密算法请在此行下边写
	return false;
}

/*获得当前系统偏移后的时间
	在当前PHP进程时,任何时候调用此函数返回的时间都和第一次调用一致
	systemTime(特定时间);让之后调用systemTime()的时候返回的时间都与设置的时间一致
*/
function systemTime($setTime = NULL)
{
	static $thistime = NULL;//执行时间
	static $is_set = false;//系统设置运行时间,如果存在人工设置,则关闭警告
	static $shifting = 0;
	//如果时间偏移设置,在上一次运行期间发生过变动,在批量注册的情况下出现
	if($shifting != CONFIG('TIMEMOVE_DAY')*24*60*60+CONFIG('TIMEMOVE_HOUR')*3600)
	{
		$thistime = NULL;
		$shifting = CONFIG('TIMEMOVE_DAY')*24*60*60+CONFIG('TIMEMOVE_HOUR')*3600;
	}

	//当前系统时间 包括偏移的时间
	$nowtime=time()+$shifting;
	//自定义运行时间
	if($setTime != NULL && $setTime!=0)
	{
		$thistime=$setTime;
		$is_set = true;
	}
	//清除运行时间
	if($setTime === 0)
	{
		$thistime = NULL;
	}
	if($thistime == NULL)
	{
		$thistime=time()+$shifting;
	}
	if($nowtime-$thistime>300 && !$is_set){
		throw_exception('系统运行时间偏长，系统时间发生错误记录');
	}
 	return $thistime;
}
function systemDate($setTime = NULL)
{
	return strtotime(date('Y-m-d',systemTime($setTime = NULL)));
}
/*处理各类设置信息的存储和读取
	CONFIG('变量名','值');   参数案例
	
	CONFIG('设置名');        读取配置信息
	
	CONFIG('设置名','值');   设置配置信息
	
	CONFIG('','设置名');     取得名称是否有设置 
	
	CONFIG();                         导出各个项目的所有配置信息(二维数组)
*/
function CONFIG($name=NULL,$val=NULL) {
	static $_cache = null;
	static $_isedit = false;
	$filename = THINK_PATH.'config/config.php';
	if($name=== NULL && $val===NULL)
	{
		return $_isedit;
	}
	//加载缓存文件
	if($_cache == NULL)
	{
		if(is_file($filename))
		{
			//读取config文件配置时使用共享锁锁定
			$fp=fopen($filename,'r');
			flock($fp,LOCK_SH);
			$_cache=(include $filename);
			flock($fp,LOCK_UN);
			fclose($fp);
		} else {
			if($val == NULL)
			return null;
		}
	}
	//判定是否属于查找变量是否存在
	if($name === '')
	{
		if(! $_cache === NULL || !isset($_cache[$val]))
		{
			return false;
		}
		return true;
	}
	if($val!==NULL && $name != '')
	{
		if( $_cache === NULL || !is_array($_cache))
		{
			$_cache=array();
		}
		$_cache[$name]=$val;
		//处理数据库更新
		$data = M()->query("select id from `config` where `name`='{$name}'  FOR UPDATE");
		if($data){
			M()->execute("update `config` set `data`='".addslashes(serialize($val))."' where id=".$data[0]['id']);
		} else {
			M()->execute("insert `config`(`name`,`data`) values ('{$name}','".addslashes(serialize($val))."')");
		}
		$_isedit=true;
	}
	if ($_cache !== NULL)
	{
        if(isset($_cache[$name]))
        {
        	return $_cache[$name];
        } else {
        	return null;
        }
	}
	return null;
}

	/*
		对字符串去中括号以及小于号转义
		此位置一般用于XML设置的值要套用在sql语言中,进行预处理,比较典型的就是rowfrom属性
		因为XML规定字段名可以用中括号标注.因为要兼容transform,所以要在SQL语句套用之前.去掉设置过的中括号属性
	*/
	function delsign($where){
		$where=str_replace('>>','<',$where);
		if(strpos($where,'[')!==false){
			$where=str_replace('[','',$where);
		}
		if(strpos($where,']')!==false){
			$where=str_replace(']','',$where);
		}
		return $where;
	}
	
	//输出结算信息
	function calmsg($msg,$icon='')
	{
		if(!defined('runcal') && !defined('autoset'))
			return;
		callog($msg);
        $caltime=date('H:i:s',time());
        if(IS_CLI){
				file_put_contents(LOG_PATH.'clical.log', "结算||".$msg."||".$caltime."||".$icon.PHP_EOL, FILE_APPEND);
		}else{
	        print "<script  type='text/javascript'>parent.addexemsg('$msg','$caltime','$icon');</script>";
	        ob_flush(); //强制将缓存区的内容输出
	        flush(); //强制将缓冲区的内容发送给客户端
	    }
	}
	  //取得数字
	  function getnum($s1,$s2,$s3=NULL,$Proportion=100)
	  {
	  	  	//去减号计算
	  	    if(substr($s2,0,1)=='-'){
				$negative = true;
				$s2=trim($s2,'-');
			}else{
				$negative = false;
			}
			//如果含有百分号
			if(strstr($s2,'%')){
				//替换百分号为*0.01
				$jisuan=str_replace("%","*0.01",$s2);
				$num = $s1 * eval("return $jisuan;");
			}else if(substr($s2,0,1)=='*'){
				//字符第一位有乘号
				$jisuan=substr($s2,1,99999);
				$num = $s1 * eval("return $jisuan;");
				//transform(substr($s2,1,99999));
			}else{
				$num = transform($s2);
			}
			if($Proportion!=100)
			{
				$num=($num/100)*$Proportion;
			}
			if($s3){
				$num = round($num,$s3);
			}
			if($negative)
			{
				$num=-$num;
			}
			return $num;
		}
		//发送短信验证码
		 function sendSmsVerify(){
			import('COM.SMS.DdkSms');
			//DdkSms::send('手机号','内容');
			$verify = rand(100000,999999);
			$content = str_replace('[验证码]',$verify,$_POST['content']);
			preg_match_all('/\[(.*)\]/U',$content,$matchs);
			for($i=0;$i<count($matchs[0]);$i++){
				$str1=$matchs[0][$i];
				$str2=$matchs[1][$i];
				$content=str_replace($str1,$this->userinfo[$str2],$content);
			}
			$result = DdkSms::send($this->userinfo['移动电话'],$content,$_POST['type'],$this->userinfo['编号']);
			//S($this->userinfo['编号'].'_'.$_POST['type'],$verify,300);
			if($result['status'] == true){
				S($this->userinfo['编号'].'_'.$_POST['type'],$verify,300);
				$this->ajaxReturn(S($this->userinfo['编号'].'_'.$_POST['type']),'发送成功!',1);
			}else{
				$this->ajaxReturn('','发送失败!',0);
			}
		}
		//发送短信  
		/*
			$type: reg 注册；accok  订单审核；changePwd  修改密码；
		
		
		*/
		function sendSms($type="",$userid,$memo,$pdata,$mobile=""){
			if(CONFIG($type.'smsSwitch')!=1){
				return true;
			}
			//判断短信信息来源 如reg accok change
			if($type==""){
				return "未得到发送方式";
			}
			//会员信息
			$umobile=M("会员")->where(array("编号"=>$userid))->getField('移动电话');
			//会员接收短信的手机号
			if($mobile==""){
				$mobile=$umobile;
			}
			if($mobile==""){
				return "未得到接收号码";
			}
			//短信发送内容
			$content=CONFIG($type."smsContent");
			preg_match_all('/\[(.*)\]/U',$content,$matchs);
			for($i=0;$i<count($matchs[0]);$i++){
				$str1=$matchs[0][$i];
				$str2=$matchs[1][$i];
				$content=str_replace($str1,$pdata[$str2],$content);
			}
			//加载短信发送的类
			import('COM.SMS.DdkSms');
			$telNumberary=array();
			//监听手机号
			if(CONFIG($type.'smsMobile')!=""){
				$telNumberary=explode(",",CONFIG($type.'smsMobile'));
			}
			$telNumberary[]=$mobile;
			foreach(array_reverse($telNumberary) as $telNumber){
				if($telNumber==$mobile){
					$username=$udata['编号'];
				}else{
					$username="";
				}
				DdkSms::send($telNumber,$content,$memo,$username);
			}
			return true;
		}
	//数据增加处理函数
	//to='管理_两层人数'
	function runadd(&$userdata,$val,$to,$option=array())
	{
		$to=trim($to);
		$obj=X('@'.$to);
		if(!method_exists($obj,'event_valadd'))
		{
			throw_exception('在执行一次addval操作时,发现目标并没有event_valadd进行处理;');
		}
		
		if(isset($option['_netname']))
		{
			//有设置过网体
			$minlayer= isset($option['_minlayer']) ? intval($option['_minlayer']):0;
			$maxlayer= isset($option['_maxlayer']) ? intval($option['_maxlayer']):0;
			$net=X('@'.$option['_netname']);
			foreach($net->getups($userdata,$minlayer,$maxlayer) as $upuser) 
			{
				$obj->event_valadd($upuser,$val,$option);
			}
		}
		else
		{
			//针对本人
			$obj->event_valadd($userdata,$val,$option);
		}
	}
	//当在系统运行当中,实现了跨日时的事物处理.
	function diffTime()
	{
		//定义表前缀 由于由Admin发起的diffTime()操作时  表前缀是Admin中的config设置，而非DmsAdmin中的，所以要定义成DmsAdmin的前缀，最后再改回去
		$oldp=C('DB_PREFIX');
		if($oldp!="dms_")
			C('DB_PREFIX',"dms_");
		//首先日期不是当天
		if(date('Ymd',CONFIG('DIFFTIME')) != date('Ymd',systemTime()))
		{
			M()->startTrans();
			$diffTimedata = M('config',null)->lock(true)->where(array('name'=>'DIFFTIME'))->find();
			$diffTime = unserialize($diffTimedata['data']);
			//如果等于.说明有一个同步进程已经进行过处理.则不需要在进行了
			if(date('Ymd',$diffTime)==date('Ymd',systemTime()))
			{
				//回复修改的前缀到默认值
				if($oldp!="dms_")
					C('DB_PREFIX',$oldp);
				M()->rollback();
				return;
			}
			//设置保存
			CONFIG('DIFFTIME',systemTime());
			$startTime = strtotime(date('Y-m-d',$diffTime+86400));
			$endTime   = strtotime(date('Y-m-d',systemTime()));
			if($diffTime){
				for($time = $startTime;$time<=$endTime;$time+=86400)
				{
					X('user')->callevent('diffTime',array('time'=>$time));
				}
				
			}
			M()->commit();
		}
		if($oldp!="dms_")
			C('DB_PREFIX',$oldp);
		return ;
	}
//后台显示开关
function adminshow($shows){
	$adminshowss=explode(',',CONFIG('ADMIN_SHOW'));
	if(in_array($shows,$adminshowss)){
		return  true; 
	}
	return false;
}

function callog($str)
{
	if($str===false)
	{
		//判断文件是否存在
		if(file_exists(LOG_PATH.'cal.log'))
			unlink(LOG_PATH.'cal.log');
	}
	else
	{
		file_put_contents(LOG_PATH.'cal.log',$str."\n",FILE_APPEND);
	}
}
	//重定向输出
    function resetStd($stdoutFile)
    {
        $dir = dirname($stdoutFile);
            // 目录不存在则创建
            if (!is_dir($dir))
                mkdir($dir);
            
        global $STDOUT, $STDERR;
        $handle = fopen($stdoutFile,"a");
        if($handle) 
        {
            unset($handle);
            @fclose(STDOUT);
            @fclose(STDERR);
            $STDOUT = fopen($stdoutFile,"a");
            $STDERR = fopen($stdoutFile,"a");
        }
        else
        {
            throw new Exception('can not open stdoutFile ' . $stdoutFile);
        }
    }
    function getDaterangeBool($time,$opendateRange){
        if($opendateRange=='')
        	return true;
        //周期
        $week = date('w',$time);
        switch($week){
            case 0: $week = "周日";break;
            case 1: $week = "周一";break;
            case 2: $week = "周二";break;
            case 3: $week = "周三";break;
            case 4: $week = "周四";break;
            case 5: $week = "周五";break;
            case 6: $week = "周六";break;
        }
		$date = explode('|',$opendateRange);
        $result = false;
        foreach($date as $val){
            $dateArr = explode(';',$val);
           	//周期判断
            if($dateArr[2] !=""){
                if(in_array($week,explode(',',$dateArr[2]))){
                    $result = true;
                }else{
                    $result = false;
                    continue;
                }
            }
            //判断时分秒
            if($result == true){
	            //时分秒判断
	            $day=date("Y-m-d",$time);//当前年月日
	            //组成当天的开始结束的时间戳
	            if($time<strtotime($day." ".$dateArr[0]) || $time>strtotime($day." ".$dateArr[1])){
	            	return $dateArr[0]."至".$dateArr[1];
	            }else{
	            	$result = false;
	            	continue;
	            }
            }
        }
        return true;
	}
	
	//发送邮件           用户数据 主题  邮件内容 触发监控事件条件
	function sendMail($udata,$subject,$content){
		preg_match_all('/\[(.*)\]/U',$content,$matchs);
		for($i=0;$i<count($matchs[0]);$i++){
			$str1=$matchs[0][$i];
			$str2=$matchs[1][$i];
			$content=str_replace($str1,$udata[$str2],$content);
		}
		 import("COM.Mail.PHPMailer");
		 import("COM.Mail.SMTP");
		 import("COM.Mail.POP3");
	
		 //$mail=new Email();
		 $Mail = new PHPMailer;
         $Mail->SMTPDebug = 0; //Full debug output
         $Mail->Priority = 3;
         $Mail->Encoding = '8bit';
         $Mail->CharSet = 'utf-8';
         //发件人
		 $Mail->From     = CONFIG('MAIL_ADDRESS');
		 //发件名
		 $Mail->FromName = CONFIG('MAIL_FROMNAME');
		 //服务器地址
		 $Mail->Host     = CONFIG('MAIL_SMTP');
		 //
		 $Mail->Port     =25;
         $Mail->SMTPAuth = true;
         $Mail->Username = CONFIG('MAIL_LOGINNAME');
         $Mail->Password = CONFIG('MAIL_PASSWORD');
		 $Mail->Mailer = 'smtp';
	 	 $Mail->Subject = $subject;
		 $Mail->Body = $content;
		 $Mail->addAddress($udata['email'], '');
		 $re = $Mail->send();
		
		$model = M('站外邮件');
		$data['内容'] = $content;
		$data['发送时间'] = time();
		$data['发件人'] = '管理员';
		$data['标题'] = '密码找回';
		$data['收件人'] = $udata['编号'];
		if($re){
			$data['返回信息'] = 'OK';
		}else{
			$data['返回信息'] = $Mail->ErrorInfo;
		}
		$result = $model->add($data);
		//dump($Mail->ErrorInfo);
		return $re;
	}
	//判断网站根目录下是否存在压缩包
	function IfZipExists(){
		$filestr="";
		$dir = dirname(__FILE__);
		foreach(scandir($dir) as $file){
			if(is_file($dir.'/'.$file)){
				$re = checkTitle($dir.'/'.$file);
				if($re){
					$filestr.=$file;
					return $filestr;
					break;

				}
			}
		}
		return $filestr;
	}
	/**
	* 读取文件前几个字节 判断文件类型
	*/
	function checkTitle($filename) {
		$file     = fopen($filename, "rb");
		$bin      = fread($file, 2); //只读2字节
		fclose($file);
		if(trim($bin)=='')return false;
		$strInfo  = @unpack("c2chars", $bin);
		$typeCode = intval($strInfo['chars1'].$strInfo['chars2']);
		/*
		.7z||55122
		.rar||8297
		.zip||8075
		.tar.gz||31
		.tar||102105
		.tar.bz2||6690
		.rp文件头和.zip 一样都是8075 需要排除掉
		*/
		if($typeCode == 55122 || $typeCode == 8297 || $typeCode == 8075 && substr($filename,-3)!='.rp' || $typeCode == 31 || $typeCode == 102105 || $typeCode == 6690){
			return true;
		}else{
			return false;
		}
	}
	//判断网站根目录下是否存在压缩包
	function delZips(){
		$result=true;
		$dir = dirname(__FILE__);
		foreach(scandir($dir) as $file){
			if(is_file($dir.'/'.$file)){
				$re = checkTitle($dir.'/'.$file);
				if($re){
					$result = @unlink($dir.'/'.$file);
					if(!$result) return false;
				}
			}
		}
		return $result;
	}
	/*yubicloud密码校验
		chkyubicloud(密码明文,登陆账号)
	*/
	function chkyubicloud($otp_to_check,$account)
	{
		//验证传入密码是否符合当前登陆管理员在系统已绑定硬件前缀
		$yubi_prefixs = M("yubicloud",null)->query("select yubi_prefix from admin a left join yubicloud b on a.id=b.account_id where b.state=1 and a.account='".$account."'");

		if(!$yubi_prefixs){
			return false;
		}else{
			$flag = false;
			foreach($yubi_prefixs as $prefix){
				if(strpos($otp_to_check,$prefix['yubi_prefix'])===0){
					$flag = true;
					break;
				}
			}
			if(!$flag){
				return false;
			}
		}
		//密码传到yubicloud服务器验证
		require(ROOT_PATH."/Public/yubicloud.class.php");
		$yubicloudobj = new Yubicloud();
		$res = $yubicloudobj->checkOnYubiCloud($otp_to_check);
		if($res=='OK')
		{
			return true;
		}else{
			return false;
		}
	}	
?>