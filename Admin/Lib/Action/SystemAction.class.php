<?php
// 管理员模块
class SystemAction extends CommonAction {
	
	//系统运行设置
	public function index(){

		$viewarr=explode(',',CONFIG('ADMIN_SHOW'));
		$this->assign('viewarr',$viewarr);
		//日志
		$debugConfig = require ROOT_PATH.'Admin/Conf/debug.php';
		$logLevelArr = explode(',' ,$debugConfig['LOG_LEVEL']);
		$this->assign('logLevelArr',$logLevelArr);
		$this->assign('logRecord',$debugConfig['LOG_RECORD']);
		$this->assign('appDebug',$debugConfig['APP_DEBUG']);
		//workerMan
		$children=glob(VENDOR_PATH.'Workerman/Applications/*/start.php');
		$autoary=array();
		foreach($children as $child){
			$filename=basename(dirname($child));
			//判断是否有开启
			if(file_exists(VENDOR_PATH.'Workerman/Applications/'.$filename.'/taskset.php')){
				$autoset=require_once(VENDOR_PATH.'Workerman/Applications/'.$filename.'/taskset.php');
				if($autoset['use']){
					$autoary[$filename]=$autoset;
				}
			}
		}
		$this->assign('autoary',$autoary);
		//客户可设置项
		$this->assign('USER_SHOP_SALEONLY',CONFIG('USER_SHOP_SALEONLY'));
		$this->assign('SHOW_SHOPSET',CONFIG('SHOW_SHOPSET'));
		$this->display();
	}
	public function save(){
		//日志
		//dms项
		//客户可设置项
		$showstrss = '';
		foreach(I("post./a") as $k=>$v)
		{
		  $showstrss.=",".$k;
		}
		M()->startTrans();
		CONFIG('ADMIN_SHOW',trim($showstrss,","));
		CONFIG("USER_SHOP_SALEONLY",I("post.USER_SHOP_SALEONLY/d"));
		CONFIG("SHOW_SHOPSET"  ,I("post.SHOW_SHOPSET/d"));
		M()->commit();
		$this->success('设置完成!');
	}
	//系统时间设置
	public function settime(){
		$settlementTime = CONFIG('CAL_START_TIME');
		$TIMEMOVE_HOUR  = CONFIG('TIMEMOVE_HOUR');
		$TIMEMOVE_DAY   = CONFIG('TIMEMOVE_DAY');
		$shifttime=($TIMEMOVE_HOUR+$TIMEMOVE_DAY*24)*3600*1000;
		$this->assign('shifttime',$shifttime);
		$this->assign('tle',$settlementTime);
		$this->assign('hour',$TIMEMOVE_HOUR);
		$this->assign('day',$TIMEMOVE_DAY);
		$this->assign('SHOW_TIMESET',CONFIG('SHOW_TIMESET'));
		$this->display();
	}
	//系统时间设置更新
	function timeupdate(){
		$data=array();
		if(I("get.type",'null')==='null')
		{
			//手动设置
			$TIMEMOVE_HOUR=I("post.hour/d");
			$TIMEMOVE_DAY=I("post.day/d");
		}
		else
		{
			//手动设置
			$TIMEMOVE_HOUR=CONFIG('TIMEMOVE_HOUR');
			$TIMEMOVE_DAY =CONFIG('TIMEMOVE_DAY');
			if(I("get.type/s")=='day')
			{
				$TIMEMOVE_DAY+=1;
			}
			if(I("get.type/s")=='week')
			{
				$TIMEMOVE_DAY+=7;
			}
		}
	//	dump($TIMEMOVE_HOUR);dump($TIMEMOVE_DAY);
		if(I("post.tle/s")!=""){
			$settlement=strtotime(I("post.tle/s"));
		}
	//	dump($settlement);exit;
		//得到实际偏移的小时数
		$movehour=(int)$TIMEMOVE_DAY*24+(int)$TIMEMOVE_HOUR;
		$old_TIMEMOVE_HOUR=CONFIG('TIMEMOVE_HOUR');
		$old_TIMEMOVE_DAY =CONFIG('TIMEMOVE_DAY');
		$old_movehour=(int)$old_TIMEMOVE_DAY*24+(int)$old_TIMEMOVE_HOUR;
		
		if($old_movehour>$movehour)
		{
		//	$this->error('偏移时间不能比当前时间提前');
		}
		M()->startTrans();
		CONFIG('TIMEMOVE_DAY',(int)$TIMEMOVE_DAY);
		CONFIG('TIMEMOVE_HOUR',(int)$TIMEMOVE_HOUR);
		
		if(isset($settlement)){
			CONFIG('CAL_START_TIME',(int)$settlement);
		}
        if(I("post.SHOW_TIMESET")!=='')
		CONFIG('SHOW_TIMESET',I("post.SHOW_TIMESET/d"));
		M()->commit();
		$this->saveAdminLog('','',"系统时间设置");
        $this->success('修改完成',__URL__.'/settime',array('day'=>$TIMEMOVE_DAY));
	}
	
}

?>