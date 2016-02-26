<?php
//导入数据接口
class D_ImportAction extends CommonAction{
	

	function index(){
		set_time_limit(0);
		ini_set('memory_limit','8000M');	
		$time=time();$time1=$time;
		$clearsys   = false;//清空数据库
		$instuser   = false;//插入会员
		$instsale   = false;//插入会员
		$instbank   = false; //货币余额
		
		$netdata    = false;
		$userupdata = false;//更新密码。省市区，服务中心null的处理
		$banklog    = false;   //货币明细
		$tlelist    = false;   //奖金明细
		$tlelistall = false;//奖金总账
		
		$sumpv      = false;
		$netyeji    = false;
		$calyeji    = false;
		$gljtly     = false;
		//所需数据库和表初始化
		$base1="cx_songjian";
		//数组初始化
		//-----------------------------------------------------------开始导入------------------------------------------------------------------------
		//1、清空数据库-----
		if($clearsys)
		{
			dump("清空数据库");
			R('Admin://Backup/cleanfun');
		}
		//2、导入会员数据-----
		if($instuser)
		{
			//M()->startTrans();
			dump("导入会员数据");
			$newzd=array(
				"编号"=>"username",
				"pass1"=>"pwd",
				"pass2"=>"pwd1",
				"pass3"=>"",
				"密保问题"=>"",
				"密保答案"=>"",
				"姓名"=>"realname",
				"收货人"=>"receiver",
				"别名"=>"",
				"注册日期"=>"regtime",
				"注册类型"=>"regtype",
				"空点"=>"isblank",
				"审核日期"=>"confirmtime",
				"状态"=>"state",
				"登陆锁定"=>"0",
				"开户银行"=>"bank",
				"银行卡号"=>"zhanghao",
				"开户名"=>"huzhu",
				"开户地址"=>"bankaddress",
				"移动电话"=>"mobile",
				"固定电话"=>"phone",
				"证件号码"=>"idcard",
				"性别"=>"sex",
				"生日"=>"",
				"email"=>"email",
				"QQ"=>"qq",
				"国家"=>"",
				"省份"=>"province",
				"城市"=>"city",
				"地区"=>"area",
				"代理国家"=>"",
				"代理省份"=>"",
				"代理城市"=>"",
				"代理地区"=>"",
				"邮编"=>"",
				"地址"=>"address",
				"月收入"=>"0",
				"累计收入"=>"price_s",
				"最后登入IP"=>"",
				"服务中心"=>"isdp",
				"服务中心编号"=>"zmdname",
				"申请会员级别"=>"rank0",
				"会员级别"=>"rank",
				"会员级别金额"=>"bdmoney",			
				"推荐_上级编号"=>"tjrname",
				"推荐_网体数据"=>"",
				"管理_上级编号"=>"prename",
				"管理_网体数据"=>"",
				"管理_位置"=>"pos",
				"注册人编号"=>"regusername",
				'备注'=>'memos',
			);
			$this->insertin($newzd,$base1,'dms_会员','dg_users');
		}
		//插报单记录
		if($instsale)
		{
			M()->execute('truncate table `dms_报单`');
			$newzd=array(
				"编号"    =>"username",
				"报单PV"  =>"bdnum",
				"购买日期"=>"regtime",
				"到款日期"=>"confirmtime"
			);
			$this->insertin($newzd,$base1,'dms_报单','dg_users');
			M()->startTrans();
				M('报单')->where('1=1')->save(array(
					'报单类别'=>'前台注册',
					'byname'=>'经销商注册',
					'报单状态'=>'已生效'
				));
			M()->commit();
		}
		//增加货币余额表记录
		if($instbank)
		{
			dump("增加货币余额表");
			$newzd=array(
			'userid'=>'id',
			'编号'=>'编号'
			);
			$this->insertin($newzd,$base1,'dms_货币','dms_会员');
			$bankary=array("price_shop"  =>"可购物金额",
			               "price"       =>"电子货币",
			               "price_repeat"=>"重消货币",
			);
			$zd='username';
			foreach($bankary as $key=>$bank)
			{
				$zd.=','.$key;
			}
			
			$bh2bid = M('货币')->getField('编号,id');
			$olds  = M('dg_users',null)->field($zd)->select();
			foreach($olds as $old)
			{
				$data=array('id'=>$bh2bid[$old['username']]);
				foreach($bankary as $key=>$bank)
				{
					$data[$bank]=$old[$key];
				}
				M('货币')->bsave($data);
			}
			M('货币')->bUpdate();
		}
		//处理网体数据
		if($netdata)
		{
			dump("处理网体数据");
			M()->execute("update dms_会员 set 管理_位置='A' where 管理_位置='1'");
			M()->execute("update dms_会员 set 管理_位置='B' where 管理_位置='2'");
			X('@推荐')->repair();
			X('@管理')->repair();
		}
		//
		if($userupdata)
		{
			dump("更新相关字段");
			M()->execute("update dms_会员 set 推荐_上级编号='' where 推荐_上级编号 is null");
			M()->execute("update dms_会员 set 服务中心编号='' where 服务中心编号 is null");
			M()->execute("update dms_会员 set 状态='有效' where 状态='1'");
			M()->execute("update dms_会员 set 状态='无效',审核日期=0 where 状态='0'");
			//mymd5();
			
			
			
			//更新国家
			M()->execute("update dms_会员 set 国家='中国' where 省份!=''");
			//更新密码，省市区
			//$rss = M('area',null)->getField('地区编码,地区名称');
			//foreach($rss as $rs){
			//	$are[$rs['areaID']]=$rs['area'];
			//}
			$users=M('会员')->field('id,pass1,pass2,省份,城市,地区')->select();
			foreach($users as $user)
			{
				$pid ='';$cid ='';$aid ='';
				if($user['省份'])
				$pid = 'CN'.substr($user['省份'],0,2);
				if($user['城市'])
				$cid = 'CN'.substr($user['城市'],0,4);
				if($user['地区'])
				$aid = 'CN'.substr($user['地区'],0,6);
				$data=array(
					'id'=>$user['id'],
					'pass1'=>md100($this->mymd5($user['pass1'],'DE')),
					'pass2'=>md100($this->mymd5($user['pass2'],'DE')),
					//'省份'=>isset($rss[$pid])?$rss[$pid]:'',
					//'城市'=>isset($rss[$cid])?$rss[$cid]:'',
					//'地区'=>isset($rss[$aid])?$rss[$aid]:'',
				);
				M('会员')->bsave($data);
			}
			M('会员')->bUpdate();
			unset($users);
			//unset($rss);
		}
		//生成奖金记录
		if($banklog)
		{
			dump("生成奖金明细");
			$logs=M('dg_e',null)->order('username ,addtime asc,id asc')->select();
			$uname='';
			$price  =0;
			$price_s=0;
			$price_r=0;
			//定义货币明细表字段关联
			$brows=array('price'=>'电子货币','price_s'=>'可购物金额','price_r'=>'重消货币');
			foreach($logs as $log)
			{
				if($uname!=$log['username'])
				{
					$price =0;
					$price_s=0;
					$price_r=0;
					$uname=$log['username'];
				}
				foreach($brows as $key=>$bname)
				{
					if($$key!=$log[$key])
					{
						$num=$log[$key]-$$key;
						//表示当前这种类型的货币有变化
						if($num<>0)
						{
							$data=array(
							'编号'=>$log['username'],
							'时间'=>$log['addtime'],
							'来源'=>'',
							'类型'=>getconsume($log['memo']),
							'金额'=>$num,
							'余额'=>$log[$key],
							'备注'=>isset($log['memo1'])?$log['memo1']:'',
							'删除'=>0,
							);
							M($bname.'明细')->badd($data);
						}
						$$key=$log[$key];
					}
				}
			}
			foreach($brows as $key=>$bname)
			{
				M($bname.'明细')->bupdate();
			}
			unset($logs);
		}
		if($tlelist)
		{
			dump("增加奖金明细");
			$p2time=M('dg_periods',null)->getField('periods,begintime');
			//统计所有有收入的记录
			$otles = M()->query("select periods,username,realname,rank,rank1,xstc,cengprice,gljj,cengprice,cfxf,dianbu,tjjprice1,tjjprice2,jifen,fax,xfnet_price,incomeall from dg_jsrec where 1 and (xstc+cengprice+gljj+dianbu+tjjprice1+tjjprice2+xfnet_price)>0 order by periods asc");
			foreach($otles as $otle)
			{
				if($otle['rank1']==0)
				{
					$otle['rank1']=1;
				}
				else
				{
					$otle['rank1']+=2;
				}
				$data=array(
				'计算日期'=>$p2time[$otle['periods']],
				'编号'    =>$otle['username'],
				'会员级别'=>$otle['rank'],
				'管理级别'=>$otle['rank1'],
				'点碰奖'  =>$otle['xstc'],
				'层碰奖'  =>$otle['cengprice'],
				'管理奖'  =>$otle['gljj'],
				'重复消费'=>-$otle['cfxf'],
				'管理津贴'=>$otle['dianbu'],
				'管理费'  =>-$otle['fax'],
				'奖金'    =>$otle['xstc']+$otle['cengprice']+$otle['gljj']+$otle['dianbu'],
				'收入'    =>$otle['xstc']+$otle['cengprice']+$otle['gljj']+$otle['dianbu']-$otle['cfxf']-$otle['fax'],
				'累计收入'=>$otle['incomeall'],
				);
				M('销售奖金')->badd($data);
			}
			M('销售奖金')->bupdate();
			unset($otles);
		}	
		if($tlelistall)
		{	
			dump("增加总账明细");
			$p2time=M('dg_periods',null)->getField('periods keyid,begintime,fftime');			
			//统计销售奖金总账 把每期的奖金累积导入总账表
			$otlealls = M()->query("select periods,sum(xstc) as xstc ,sum(cengprice) as cengprice,sum(gljj) as gljj,sum(cfxf) as cfxf,sum(dianbu) as dianbu,sum(fax) as fax from dg_jsrec group by periods  order by periods asc");
			foreach($otlealls as $otle)
			{
				$data=array(
				'计算日期'=>$p2time[$otle['periods']]['begintime'],
				'总业绩'      =>'0',
				'总奖金'      =>'0',
				'本期业绩'    =>'0',
				'新增会员'    =>'0',
				'全部会员'    =>'0',
				'发放日期'    =>isset($p2time[$otle['periods']]['fftime'])?$p2time[$otle['periods']]['fftime']:'0',
				'结算方式'    =>'0',
				'state'       =>isset($p2time[$otle['periods']]['fftime'])?1:0,	
				'本期奖金'    =>$otle['xstc']+$otle['cengprice']+$otle['gljj']+$otle['dianbu'],
				'点碰奖'  =>$otle['xstc'],
				'层碰奖'  =>$otle['cengprice'],
				'管理奖'  =>$otle['gljj'],
				'重复消费'=>-$otle['cfxf'],
				'管理津贴'=>$otle['dianbu'],
				'管理费'  =>-$otle['fax'],
				'购物奖'  =>0,
				);
				M('销售奖金总账')->badd($data);
			}
			M('销售奖金总账')->bupdate();
		}
		//更新服务中心状态
		//王忠振的业绩表
		//邮件
		//系统的省市区联动JS数据，要以老系统为准
		if($sumpv)
		{
			dump("处理会员表中的业绩");
			//得到了每个人的累计业绩
			$sum = M('dg_tdpv',null)->group('username')->getField('username,sum(num_2) c');
			//自己，左右区编号的表，根据左右区编号作为键，更新会员左右区业绩
			$users = M('会员')->field('id,管理_A区,管理_B区')->where("管理_A区<>'' or 管理_B区<>''")->select();
			
			foreach($users as $user)
			{
				$data=array('id'=>$user['id'],'管理_A区累计业绩'=>0,'管理_B区累计业绩'=>0);
				if($user['管理_A区']!='' && isset($sum[$user['管理_A区']]))
				{
					$data['管理_A区累计业绩'] = $sum[$user['管理_A区']];
				}
				if($user['管理_B区']!='' && isset($sum[$user['管理_B区']]))
				{
					$data['管理_B区累计业绩'] = $sum[$user['管理_B区']];
				}
				M('会员')->bsave($data);
			}
			M('会员')->bupdate();
			//更新新增业绩==================================================================
			//得到应该要结算的那天
			M()->startTrans();
			$sql_d1="select * from dg_periods where state>0 order by endtime desc limit 1";
			$rs_d1=M()->query($sql_d1);
			$stime = $rs_d1[0]['endtime'] + 86400;
			//得到了新增业绩的汇总
			$sum = M('dg_tdpv',null)->where("datediff(concat(year,'-',month,'-',day),from_unixtime(".$stime."))>=0")->group('username')->getField('username,sum(num_2) c');
			//由于新增奖金为少量，所以就不做优化了
			foreach($users as $user)
			{
				if($user['管理_A区']!='' && isset($sum[$user['管理_A区']]))
				{
					
					M('会员')->save(array('id'=>$user['id'],'管理_A区本期业绩'=>$sum[$user['管理_A区']],'管理_A区本日业绩'=>$sum[$user['管理_A区']]));
					
				}
				if($user['管理_B区']!='' && isset($sum[$user['管理_B区']]))
				{
					M('会员')->save(array('id'=>$user['id'],'管理_B区本期业绩'=>$sum[$user['管理_B区']],'管理_B区本日业绩'=>$sum[$user['管理_B区']]));
				}
			}
			M()->commit();
			//更新结转业绩=================================================================
			$periods = $rs_d1[0]['periods'];
			$sql="select username,leftsy,rightsy from dg_jsrec where periods='".$periods."'";
			$sum=M()->query($sql);
			$bh2id = M('会员')->getField('编号,id');
			foreach($sum as $sy)
			{
				M('会员')->bsave(array(
					'id'=>$bh2id[$sy['username']],
					'管理_A区结转业绩'=>$sy['leftsy'],
					'管理_B区结转业绩'=>$sy['rightsy'],
				));
			}
			M('会员')->bupdate();
			unset($sum);unset($bh2id);
		}
		//业绩
		if($netyeji)
		{
			dump("管理_业绩");
			M()->execute('truncate table `dms_管理_业绩`');
			$bh2id    = M('会员')->getField('编号,id');
			$bh2upreg = M('会员')->getField('编号,管理_上级编号 up,管理_位置 wz');
			$pvdatas  = M('dg_tdpv',null)->field('username,num_2,year,month,day')->select();
			M()->execute("delete FROM `dg_tdpv` where username not in (select username from dg_users)");
			foreach($pvdatas as $d)
			{
				$d['username']=strtolower($d['username']);
				//得到上级编号
				$upbh   = $bh2upreg[$d['username']]['up'];
				if($d['username'] =='cn360878')
				{
					continue;
				}
				$region = $bh2upreg[$d['username']]['wz'] == 'A' ? 1:2;
				$data=array(
					'time'  =>strtotime($d['year'].'-'.$d['month'].'-'.$d['day']),
					'userid'=>$bh2id[$upbh],
					'fromid'=>$bh2id[$d['username']],
					'val'   =>$d['num_2'],
					'region'=>$region,
					'pid'   =>1
					);
				M('管理_业绩')->badd($data);
			}
			M('管理_业绩')->bupdate();
			unset($pvdatas);
			//个人对上级而言的
		}
		//生成结算业绩表
		if($calyeji)
		{
			dump("结算业绩表prizelog");
			M()->execute('truncate table `dms_prizelog`');
			M()->execute('delete from dms_管理_业绩 where pid=-1');
			//得到最后期数
			$sql_d1="select * from dg_periods where state>0 order by endtime desc limit 1";
			$rs_d1=M()->query($sql_d1);
			$periods = $rs_d1[0]['periods'];
			$bh2id=M('会员')->getField('编号,id');
			for($i=$periods-4;$i<=$periods;$i++)
			{
				
				//预统计层奖
				$cplj    = M()->query('select username,leftsy,rightsy,cpnum,leftnum,rightnum from(select * from dg_cjdat1 where periods<='.$i.' order by periods desc)b group by username');
				$cjarr=array();
				foreach($cplj as $cp)
				{
					$cjarr[$cp['username']]=$cp;
				}
				$datas=M()->query("select * from dg_jsrec where periods='".$i."'");
				foreach($datas as $data)
				{
					$data['curleftyj']  || $data['curleftyj'] =0;
					$data['currightyj'] || $data['currightyj']=0;
					$data['leftall']    || $data['leftall']   =0;
					$data['rightall']   || $data['rightall']  =0;
					$data['leftsy']     || $data['leftsy'] =0;
					$data['rightsy']    || $data['rightsy']=0;
					$data['rank']       || $data['rank'] =0;
					$data['rank1']      || $data['rank1']=0;
					$data = array(
						'编号'  =>$data['username'],
						'userid'=>$bh2id[$data['username']],
						'时间'  =>$data['begintime'],
						'管理_A区本期业绩'  =>$data['curleftyj'],
						'管理_B区本期业绩'  =>$data['currightyj'],
						'管理_A区本日业绩'  =>$data['curleftyj'] ,
						'管理_B区本日业绩'  =>$data['currightyj'],
						'管理_A区累计业绩'  =>$data['leftall'],
						'管理_B区累计业绩'  =>$data['rightall'],
						'管理_A区结转业绩'  =>$data['leftall']  - $data['curleftyj'] ,
						'管理_B区结转业绩'  =>$data['rightall'] - $data['currightyj'],
						'会员级别'          =>$data['rank'],
						'管理级别'          =>$data['rank1'],
						'推荐_推荐人数'     =>0,
						'A业绩'            =>isset($cjarr[$data['username']]) ? $cjarr[$data['username']]['leftnum'] : 0,
						'B业绩'            =>isset($cjarr[$data['username']]) ? $cjarr[$data['username']]['rightnum']: 0,
						'A剩余'            =>isset($cjarr[$data['username']]) ? $cjarr[$data['username']]['leftsy']  : 0,
						'B剩余'            =>isset($cjarr[$data['username']]) ? $cjarr[$data['username']]['rightsy'] : 0,
					);
					M('prizelog')->badd($data);
					//增加管理_业绩负数记录
					if($data['userid'] == 691)
					{
						dump(array(
						'A'=>$data['管理_A区本期业绩']+$data['管理_A区结转业绩'],
						'B'=>$data['管理_B区本期业绩']+$data['管理_B区结转业绩']
						));
					}
					$ret=X('prize_bump@')->testbump('1:2',array(
						'A'=>$data['管理_A区本期业绩']+$data['管理_A区结转业绩'],
						'B'=>$data['管理_B区本期业绩']+$data['管理_B区结转业绩']
						));
					foreach(array('A','B') as $key=>$region)
					{
						if($ret[$region.'-']<0)
						{
							M('管理_业绩')->badd(
							array(
								'userid'=>$data['userid'],
								'fromid'=>$data['userid'],
								'time'  =>$data['时间']+86400-1,
								'region'=>$key+1,
								'pid'=>-1,
								'val'=>$ret[$region.'-'],
								)
							);
						}
					}
				}
				M('prizelog')->bupdate();
				M('管理_业绩')->bupdate();
			}
		}
		//
		$sql_d1="select * from dg_periods where state>0 order by endtime desc limit 1";
		$rs_d1=M()->query($sql_d1);
		$stime = $rs_d1[0]['endtime'] + 86400;
		//处理管理津贴累计
		if($gljtly)
		{
			$data=M('销售奖金')
				->field('id,编号,管理津贴')
				->order('编号,计算日期 asc')
				->bselect(array($this,'gljthz'),10000);
			M('销售奖金')->bupdate();
		}
		//更新结算起始日
		CONFIG('CAL_START_TIME',$stime);
		return ;
		die('OK导入完成,总用时:'.($time-$time1));
	}
	public function gljthz($datas)
	{
		static $bh='';
		static $sumyj=0;
		foreach($datas as $data)
		{
			if($data['编号']!=$bh)
			{
				$bh = $data['编号'];
				$sumyj=0;
			}
			$sumyj+=$data['管理津贴'];
			M('销售奖金')->bsave(array('id'=>$data['id'],'管理津贴累计'=>$sumyj));
		}
	}
	function mymd5($string,$action="EN")
	{ //字符串加密和解密
	    //global $webdb,$onlineip;//$webdb系统的一些配置信息,$onlineip 客户端ip 这个函数好像没有用到
	    $secret_string = '5*j,.^&;?.%#@!'; //绝密字符串,可以任意设定
	    //$webdb[mymd5] 可以自己设置一个字符串
	    if($string=="") return "";
	    if($action=="EN") $md5code=substr(md5($string),8,10); //动作是加密,则取明文的MD5码的 8到10位字符
	    else//动作是解密(DE)
	    {
	        $md5code=substr($string,-10);//取$string后10个字符
	        $string=substr($string,0,strlen($string)-10);//把$string倒数十个字符去掉
	    }
	    //$key = md5($md5code.$_SERVER["HTTP_USER_AGENT"].$secret_string);
	    $key = md5($md5code.$secret_string);//密匙
	    $string = ($action=="EN"?$string:base64_decode($string));//如果动作是解密则将要解密的字符串进行MIME base64解码
	    //base64_decode() 对使用 MIME base64 编码的数据进行解码
	    //base64_encode() 使用 MIME base64 对数据进行编码
	    $len = strlen($key);
	    $code = "";
	    for($i=0; $i<strlen($string); $i++)
	    {
	        $k = $i%$len;
	        $code .= $string[$i]^$key[$k];
	    }
	    $code = ($action == "DE" ? (substr(md5($code),8,10)==$md5code?$code:NULL) : base64_encode($code)."$md5code");
	    return $code;
	}
	//导数据做的一些判断
	function test(){
		$userM=M('会员');
		$netary=array("推荐"=>"推荐_上级编号");
		//判断有没有重复编号
		$cnum=M()->query("select count(*)num from dms_会员 group by 编号 having count(*)>1");
		if($cnum) dump("系统中含有重复编号");
		//判断网体
		dump("判断没有上级的都是什么人-----------");
		foreach($netary as $name=>$net){
			dump("-----".$name);
			$results=$userM->where("(".$net."='' or ".$net." is null)")->field('编号')->select();
			if($results){
				foreach($results as $rs){
					dump("-------".$rs['编号']);
				}
			}
		}
		dump("判断网体的人存不存在-----------");
		$alluser=$userM->getField("id,lower(编号)");
		foreach($netary as $name=>$net){
			$results=$userM->where($net."!=''")->group($net)->field($net)->select();
			foreach($results as $rs){
				if(!in_array(strtolower($rs[$net]),$alluser)){
					dump($name."-------上级已经不存在的".$rs[$net]);
				}
			}
		}
		dump("判断货币余额跟明细不符的");
		foreach(array("金币","金种子") as $name){
			$rss=M()->query("select a.id,a.编号,a.".$name.",b.c from  dms_会员 a,(select sum(金额)c,编号 from dms_".$name."明细 group by 编号)b where a.编号=b.编号 and a.".$name."!=b.c");
			foreach($rss as $rs){
				//if(abs($rs[$name]-$rs['c'])>0.1){
					dump($name."不符的：".$rs['编号']."__".$rs[$name]."__".$rs['c']);
				//}
			}
		}
		
	}
	//插入数据
	public function insertin($newzd,$base1,$table1,$table2){
		//导入数据
		$olds = M()->query("select * from ".$base1.'.'.$table2);
		foreach($olds as $old)
		{
			$ndata=array();
			foreach($newzd as $nkey=>$okey)
			{
				$ndata[$nkey]='';
				if($okey != '')
				{
					if($okey!='0'){
						if(isset($old[$okey]) && $old[$okey]!=null)
							$ndata[$nkey]=$old[$okey];
					}else{
						$ndata[$nkey]=$okey;
					}
				}
			}
			M($table1,null)->badd($ndata);
		}
		M($table1,null)->bupdate();
	}
}
function getconsume($title){
	$return="";
	switch($title){
		case 1:$return="充值";break;
		case 2:$return="电子货币转出";break;
		case 3:$return="提现";break;
		case 4:$return="奖金";break;
		case 5:$return="订货奖励";break;
		case 6:$return="报单周转";break;
		case 7:$return="重复消费";break;
		case 8:$return="消费重复消费";break;
		case 9:$return="购物消费";break;
		case 10:$return="电子货币转入";break;
		case 11:$return="注册会员";break;
		case 12:$return="删除会员";break;
        case 13:$return="会员订货";break;
        case 14:$return="网络管理费";break;
        case 15:$return="提现手续费";break;
        case 16:$return="升级补差";break;
        case 17:$return="提现手续费(冻结)";break;
        case 18:$return="提现金额(冻结)";break;
        case 19:$return="返还冻结提现提现手续费";break;
        case 20:$return="返还冻结提现金额";break;
        case 21:$return="短信费";break;
        case 22:$return="电子货币转为报单款";break;
		case 23:$return="为会员报单";break;
		case 24:$return="重复消费转出";break;
        case 25:$return="升级为代理";break;
		case 26:$return="撤销奖金";break;
		case 27:$return="充值购物金额";break;
		case 28:$return="可购物金额转出";break;
		case 29:$return="可购物金额转入";break;
		default:$return="";break;
	}
	return $return;
}
?>