<?php
defined('APP_NAME') || die('不要非法操作哦!');
/*奖金结算功能
方法介绍
结算主页
public function settlement()
结算运行
public function settlementExecute()
取得结算周期,用于结算主页显示
private function tlemodename($tlemode)
发放奖金
public function givePrice(tle $tle)
*/
class CalAction extends CommonAction {
    public function settlement(){
    	$tleobjs=X('tle');
    	$tles=array();
    	$calbut=false;
    	foreach($tleobjs as $tle)
    	{
    		$tles[]=array("name"=>$tle->name,"modename"=>$this->tlemodename($tle->tleMode));
    		if($tle->tleMode!='s')
    		{
    			$calbut=true;
    		}
    	}
    	$this->assign ('tles',$tles);
    	$this->assign ('calbut',$calbut);
    	$this->assign ('CAL_START_TIME'  ,CONFIG('CAL_START_TIME'));
    	$diffday=floor((systemTime()-CONFIG('CAL_START_TIME'))/(24*3600));
    	$selectday=array();
    	for($i=1;$i<=$diffday;$i++)
    	{
    		$selectday[]=CONFIG('CAL_START_TIME')+($i-1)*24*3600;
    	}
		arsort($selectday);
		$this->assign ('if_exec'  ,$this->if_exec());
        $this->assign ('is_autojisuan'  ,(adminshow('AUTO_AutoCal')?1:0));
		$this->assign ('is_runing'  ,$this->is_runing());
		$this->assign ('if_cli'  ,(adminshow('cliSwitch')?1:0));
    	$this->assign ('diffday'  ,$diffday);
    	$this->assign ('selectday',$selectday);
    	$ip = getIP();//获取IP
        $this->assign('ips',$ip);
        $this->display();
    }
    /* -
     * +------------------------------------------------------------------------
     * * @ 判断是否cli模式进行结算
     * +------------------------------------------------------------------------
     */
 	public function presettlementExecute(){
 		file_put_contents(LOG_PATH.'clical.log','');
 		$caltime = I("post.caltime/s");
		if(adminshow('cliSwitch')){
	 		//判断Windows还是Linux
            $wordpath =getcwd();//当前工作路径
	 		if(IS_WIN){
	 			$ini = ini_get_all();                    
		        $path = $ini['extension_dir']['local_value'];           
		        $php_path = str_replace('\\', '/', $path);           
		        $php_path = str_replace(array('/ext/', '/ext'), array('/', '/'), $php_path);           
		        $real_path =  'php.exe';//$php_path .
				chdir($wordpath);//更改当前工作路径
				$cmd = $real_path." ".$wordpath."/clical.php Admin Cal settlementExecute caltime,".$caltime." >calerr.log";
				pclose(popen("start /B ". $cmd, "r"));  
	 		}else{
                chdir($wordpath);
	 			$cmd="php ".$wordpath."/clical.php Admin Cal settlementExecute caltime,".$caltime." >calerr.log";
				exec($cmd . " &",$out,$re);
	 		}
	 		//$this->ajaxReturn(array(),"正在结算中",0);
 		}else{
			$this->settlementExecute(NULL,NULL,I("post."));
		}
 	}
    public function settlementExecute($tleday=NULL,$calmsg=NULL,$post=NULL){
    	/*	结算流程
    		1、操作时启动文件锁操作  通过文件来锁定结算进程
    		2、查询结算的起止日期 判断要结算几天的奖金 
    		3、for循环按起止之间的天数，每天的结算开始时开启事务，结算完成后提交这一天结算的事务
	    	4、判断是否发放奖金  也就是结算直接发放，开启事务，发放未发放的奖金，最后提交事务
	    	注：循环计算每期奖金时由于每期都有事务的开启与关闭
    	*/
    	//定义执行结算常量，用于日志分析 并且判断是否进行结算过程的clamsg结算信息输出显示
    	//如果传入参数,认为是批量注册结算调用.则不在输出
    	if(!$tleday)
    	{
    		define('runcal',true);
    	}
    	//创建文件锁定进程，判断结算进程是否已在运行中
    	if(!lockfile('cal','结算')){
    		calmsg('结算已有正在运行中，请等待...',"/Public/Images/ExtJSicons/lock/lock_delete.png");
			die;
    	}
    	//清空日志
    	callog(false);
		if(file_exists(ROOT_PATH.APP_NAME.'/config.php')){
			require_once ROOT_PATH.APP_NAME.'/config.php';
		}
    	//计算明天的
    	if(isset($post) && isset($post['geri'])){
			//得到实际偏移的小时数往后偏移一天
			$movehour=1*24;
			$old_TIMEMOVE_HOUR=CONFIG('TIMEMOVE_HOUR');
			$old_TIMEMOVE_DAY =CONFIG('TIMEMOVE_DAY');
			M()->startTrans();
			CONFIG('TIMEMOVE_DAY',$old_TIMEMOVE_DAY+1);
			CONFIG('TIMEMOVE_HOUR',0);
			M()->commit();
		}
		if(!$tleday)
		{
			F('autoRunTime',systemTime());
		}
		//查询结算时间  反序列化处理
		$CAL_START_TIME = M('config',null)->where(array('name'=>'CAL_START_TIME'))->find();
		$starday = unserialize($CAL_START_TIME['data']);
		$stardayforweek = $starday;
    	if($tleday==NULL){
			//$calovertime=(int)$_REQUEST['caltime'];
			if(I("get.caltime/d")>0){
    			$calovertime=I("get.caltime/d");
			}elseif(isset($post) && isset($post['caltime'])){
    			$calovertime=(int)$post['caltime'];
			}else{
				$calovertime = $starday;
				if($starday+24*3600 > systemTime()){
					die();
				}
			}
    	}else{
    		$calovertime=$tleday;
    	}
    	$caloverforweek = $calovertime;
    	if(IS_CLI)
    	{
    		//clical.log进程文件
			file_put_contents(LOG_PATH.'clical.log', "结算||开始。。。||".date('H:i:s',time())."||/Public/Images/ExtJSicons/resultset_next.png".PHP_EOL);
    		//list($s1, $s2) = explode(' ', microtime());
    		//resetStd(LOG_PATH.'callog/'.Date('YmdHis').'_'.($s1*10000000).'.txt');
    	}
    	set_time_limit(0);
		ini_set('memory_limit','2000M');
		//结算前备份数据库
		if(isset($post) && isset($post['backupdb']) || isset($post) && !isset($post['caltime']) || IS_CLI){
			calmsg('进行结算前数据库备份',"/Public/Images/ExtJSicons/database_save.png");
			R('Admin://Backup/backall',array(Date('Ymd',$starday).'-'.Date('Ymd',$calovertime).'结算前备份',true));
		}
		$time = time();
    	$calLen=($calovertime-$starday)/(24*3600);//循环周期天数
		$tleobjs=X('tle');
		//循环所有的天数
		for($i=0;$i<=$calLen;$i++)
		{
			M()->startTrans();
			calmsg('开始结算' . Date('Y-m-d',$starday) . '奖金(Mysql ID:'.mysql_thread_id(M()->get_Property('db')->_linkID).')',"/Public/Images/ExtJSicons/resultset_next.png");
			//处理缓存数据 主要级别
			X("user")->callevent("getCache",array('caltime'=>$starday,"user"=>array()));
			foreach($tleobjs as $tle)
			{
				//判断当天是否已结算
				$ledger_model = M($tle->name."总账");
				$day_calid=$ledger_model->where(array("计算日期"=>$starday,"结算方式"=>2))->field('id')->find();
				if($day_calid){
					calmsg(Date('Y-m-d',$starday) . '奖金已参与结算',"/Public/Images/ExtJSicons/exclamation.png");
					continue;
				}
				//结算
				$tle->cal($starday);
				unset($ledger_model);
			}
			X('user')->callevent('caldayover',array('caltime'=>$starday));
			/***完成奖金构成信息***/
			import('DmsAdmin.DMS.SYS.PrizeData');
	        PrizeData::commit($starday,true);
	        /*********************/
            
            //增加系统日志
            
                			
        	$this->saveAdminLog('','','结算'.date('Y-m-d',$starday).'日奖金');
			$starday += 24 * 3600;
            if(isset($post) && isset($post['test']) && $post['test']=='1')
    		{
                M()->rollback();
            }else{
    			CONFIG('CAL_START_TIME',$starday);
                X('user')->callevent('commit',array());
    			M()->commit();
    			//因为B方法使用的引参,不能直接传值;
    			$true=true;
    			B('SaveConfig',$true);
            }
		}
		if(isset($post) && isset($post['test']) && $post['test']=='1')
		{
			calmsg('测试性结算完成！用时：'.(time()-$time).'秒','/Public/Images/ExtJSicons/information.png');
			exit();
		}
		calmsg('结算完成！用时：'.(time()-$time).'秒','/Public/Images/ExtJSicons/tick.png');
		//发放前查询下数据库进程情况，多的话等待
		do{
			$cnt = M('information_schema.processlist',null)->where("DB='".C('DB_NAME')."' and Time>=30 and state IS NOT NULL and COMMAND<>'Sleep'")->count();
			if($cnt>0){
				sleep(30);
			}else{
				break;
			}
		}while(true);
		//发放奖金
		foreach($tleobjs as $tle){
			if($tle->autoGive){
				calmsg('开始发放'.$tle->byname,"/Public/Images/ExtJSicons/resultset_next.png");
				$ledger_model=M($tle->name."总账");
				M()->startTrans();
				$wherestr = "state=0";
				if($tle->autoGiveDelay>0){
					$wherestr .= " and 计算日期<".($starday-$tle->autoGiveDelay*86400);
				}
				$ledger=$ledger_model->lock(true)->where($wherestr)->order('计算日期')->select();
				foreach($ledger as $ledgerinfo){
					if(!$ledgerinfo){
						continue;
					}
					if($ledgerinfo['state'] == 1 ){
						calmsg(date('Y-m-d',$ledgerinfo['计算日期']) ."：该销售奖金已经发放","/Public/Images/ExtJSicons/exclamation.png");
						continue;
					}
					if($ledger_model->where(array('计算日期'=>array('LT',$ledgerinfo['计算日期']),'state'=>array('NEQ',1)))->count())
					{
						calmsg(date('Y-m-d',$ledgerinfo['计算日期']) ."：请先发放上期奖金","/Public/Images/ExtJSicons/exclamation.png");
						break;
					}
					$tlewhere=array();
					$tlewhere['计算日期']=array('eq',$ledgerinfo['计算日期']);
					$tlewhere['收入']=array('gt',0);
					//$tlewhere['state']=array('eq',0);
					/*$tlelist=M($tle->name)->lock(true)->where($tlewhere)->select();
					$tle->givePrice($tlelist);*/
					M($tle->name)->lock(true)->where($tlewhere)->bSelect(
						function($data,$para){
							$tle = $para[0];
							$tle->givePrice($data);
						}
					,array($tle));
					$ledger_model->where(array('id'=>$ledgerinfo['id']))->save(array('state'=>1,'发放日期'=>systemTime()));
					$this->saveAdminLog('','',$tle->byname.'发放','发放'.date('Y-m-d',$ledgerinfo['计算日期']).'的奖金');
					calmsg('发放'.date('Y-m-d',$ledgerinfo['计算日期']).'奖金成功','/Public/Images/ExtJSicons/tick.png');
				}
				M()->commit();
			}
			//判断日结周发的奖金发放
			if($tle->weekAutoGive){
				$weekok = false;
				$calLen=($caloverforweek-$stardayforweek)/(24*3600);
				for($i=0;$i<=$calLen;$i++){
					if(date('N',$caloverforweek)==(int)$tle->weekGiveDay){
						$weekok = true;
						break;
					}
					$caloverforweek -= 24 * 3600;
				}
				//判断是周几要开始发放
				if($weekok){
					calmsg('开始发放'.$tle->byname,"/Public/Images/ExtJSicons/resultset_next.png");
					//判断一下是否是日结
					if($tle->tleMode == 'd'){
						M()->startTrans();
	                    //将之前没有发放的都给发放
						$oldprizes=M($tle->name."总账")->lock(true)->where(array('计算日期'=>array('elt',$caloverforweek),'state'=>0))->select();
						foreach($oldprizes as $key=>$oldprize){
	                     	 //找到总账,并且未发放
							if($oldprize && $oldprize['state']==0)
	                        {
	                            M($tle->name)->lock(true)->where("计算日期=".$oldprize['计算日期'])->bSelect(
										function($data,$para){
											$tle = $para[0];
											$tle->givePrice($data);
										}
									,array($tle));
	                            $res = M($tle->name."总账")->where(array('计算日期'=>$oldprize['计算日期']))->save(array('state'=>1,'发放日期'=>systemTime()));
	                            $this->saveAdminLog('','',$tle->byname.'发放','发放'.date('Y-m-d',$oldprize['计算日期']).'的奖金');
	                            calmsg('发放'.date('Y-m-d',$oldprize['计算日期']).'奖金成功','/Public/Images/ExtJSicons/tick.png');
	                        }
	                    }
	                    M()->commit();
					}
				}
			}
	       	calmsg('SUCCESS','/Public/Images/ExtJSicons/tick.png');
		}
		//解除锁定文件
		//flock($fp,LOCK_UN);
		//fclose ($fp);
    }
	private function tlemodename($tlemode)
	{
		switch ($tlemode) 
		{
			//秒结
			case 's':
				return "秒结";
				break;
			//日结
			case 'd':
				return "日结";
				break;
			//周结
			case 'w':
				return "周结";
				break;
			//月结
			case 'm':
				return "月结";
				break;
			//年结
			case 'y':
				return "年结";
				break;
			//审核日期间隔
			case 'r':
                return "周期结";
				break;
			break;
		}
	}
	//发放奖金
	public function givePrice(tle $tle)
	{
		set_time_limit(1800);
		if(I("request.id/s")=='' || $tle==false){
			$this->error('参数错误');
		}
		$succNum = 0;
		$errNum = 0; 
		$errMsg = '';
		$idary=explode(',',I("get.id/s"));
		sort($idary);
		foreach($idary as $id){
			M()->startTrans();
			$ledger_model=M($tle->name."总账");
			$tle_model=M($tle->name);
			$user_model=M('会员');
			$ledgerinfo=$ledger_model->where(array('id'=>$id))->find();
			if(!$ledgerinfo){
				$errNum++;
				$errMsg .= date('Y-m-d',$ledgerinfo['计算日期']) ."：查询奖金信息失败<br/>";
				M()->rollback();
				continue;
			}
			if($ledgerinfo['state'] == 1 ){
				$errMsg .= date('Y-m-d',$ledgerinfo['计算日期']) ."：该销售奖金已经发放<br/>";
				M()->rollback();
				continue;
			}
			if($ledger_model->where(array('计算日期'=>array('LT',$ledgerinfo['计算日期']),'state'=>array('NEQ',1)))->count())
			{
				$errNum++;
				$errMsg .= date('Y-m-d',$ledgerinfo['计算日期']) ."：请先发放上期奖金<br/>";
				M()->rollback();
				break;
			}
			if(($ledgerinfo['计算日期']+86400)>systemTime()){
				$errNum++;
				$errMsg .= date('Y-m-d',$ledgerinfo['计算日期']) ."：当天的奖金请在第二天发放<br/>";
				M()->rollback();
				break;
			}
			$tlewhere=array();
			$tlewhere['计算日期']=array('eq',$ledgerinfo['计算日期']);
			//$tlewhere['收入']=array('egt',0);
			$tlewhere['state']=array('eq',0);
			/*$tlelist=$tle_model->where($tlewhere)->select();
			$tle->givePrice($tlelist);*/
			$tle_model->lock(true)->where($tlewhere)->bSelect(
				function($data,$para){
					$tle = $para[0];
					$tle->givePrice($data);
				}
			,array($tle));
			$ledger_model->where(array('id'=>$id))->save(array('state'=>1,'发放日期'=>systemTime()));
			M()->commit();
			$this->saveAdminLog('','',$tle->byname.'发放','发放'.date('Y-m-d',$ledgerinfo['计算日期']).'的奖金');
			$succNum++;
		}
		if($errNum !=0){
			$this->error("发放成功：".$succNum .'条记录；发放失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("发放成功：".$succNum .'条记录；');
		}
	}
	//删除奖金
	public function delPrice(tle $tle)
	{
		if(I("request.id/s")=='' || $tle==false){
			$this->error('参数错误');
		}
		$ledger_model=M($tle->name."总账");
		$tle_model=M($tle->name);
		$user_model=M('会员');
		$succNum = 0;
		$errNum = 0; 
		$errMsg = '';
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
			M()->startTrans();
			$ledgerinfo=$ledger_model->where(array('id'=>$id))->find();

			if($ledgerinfo['state'] == 1 ){
				$errNum++;
				$errMsg .= date('Y-m-d',$ledgerinfo['计算日期']) ."：该销售奖金已经发放<br/>";
				M()->rollback();
				continue;
			}
			if($ledgerinfo['state'] == 2 ){
				$errNum++;
				$errMsg .= date('Y-m-d',$ledgerinfo['计算日期']) ."：该销售奖金已经删除<br/>";
				M()->rollback();
				continue;
			}
			$tlewhere=array();
			$tlewhere['计算日期']=array('eq',$ledgerinfo['计算日期']);
			$tlelist=$tle_model->where($tlewhere)->delete();
			$ledger_model->where(array('id'=>$id))->save(array('state'=>2));
			$this->saveAdminLog('','',$tle->byname.'明细记录删除','删除['.date('Y-m-d',$ledgerinfo['计算日期']).']的奖金明细记录<br/>');
			M()->commit();
			$succNum++;
		}
		if($errNum !=0){
			$this->error("删除成功：".$succNum .'条记录；删除失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("删除成功：".$succNum .'条记录；');
		}
	}
	public function ExecuteAjax(){
		$tleobjs=X('tle');;
		$startTime = CONFIG('CAL_START_TIME');
    	$diffday=floor((systemTime()-$startTime)/(24*3600));
    	$selectday=array();
    	for($i=1;$i<=$diffday;$i++)
    	{
    		$selectday[]=array($startTime+($i-1)*24*3600,date('Y-m-d',$startTime+($i-1)*24*3600));
    	}
		arsort($selectday);
        $this->ajaxReturn(array(date('Y-m-d',$startTime),$selectday,$diffday));
	}
	//是否允许使用exec函数
	private function if_exec()
	{
		$ret=true;
		if(ini_get("disable_functions") != '')
		{
			if(in_array('exec',explode(',',ini_get("disable_functions"))))
			{
				$ret = false;
			}
		}
		return $ret;
	}
	//判断守护进程是否在运行
	private function is_runing()
	{
		//如果不能执行
		if(!$this->if_exec())
		{
			return false;
		}
		exec('php '.VENDOR_PATH.'Workerman/start.php AutoCal runstatic',$ret);
		if( count($ret)>1 && $ret[1]==='runing')
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	//自动结算地址
	public function AutoSet()
	{
		$this->assign('url',U('/Cal/settlementExecute?calpass='.F('calpass'),'',true,false,true));
		$this->assign('time',F('autoRunTime'));
		$qrurl='http://'.$_SERVER['HTTP_HOST'].'/';
		$is_exec   = true;
		$serverrun = false;
		if(ini_get("disable_functions") != '')
		{
			if(in_array('exec',explode(',',ini_get("disable_functions"))))
			{
				$is_exec = false;
			}
		}
		if($is_exec)
		{
			exec('php '.VENDOR_PATH.'Workerman/start.php AutoCal status',$ret);
			if($ret && count($ret)>=2 && $ret[1]==='runing')
			{
				$serverrun = true;
			}
		}
		$this->assign('is_exec',$is_exec);
		$this->assign('serverrun',$serverrun);
		$this->display();
	}
	function runset()
	{
		M()->startTrans();
		CONFIG('SYSTEM_SERVICE',I("get.val/s"));
		M()->commit();
		//如果设置了启动.则尝试启动
		if(CONFIG('SYSTEM_SERVICE'))
		{
			exec('php '.VENDOR_PATH.'Workerman/start.php AutoCal start',$ret);
		}
		else
		{
			exec('php '.VENDOR_PATH.'Workerman/start.php AutoCal stop',$ret);
		}
		$this->success("设置完成");
	}
	public function getcalstateajax(){
	 	//判断cal.lock文件是否存在 如果存在 则是正在进行结算
    	if(is_lockfile('cal')) {  
	        $farray = file(LOG_PATH.'clical.log');
	        foreach($farray as $k=>$val){
	        	$farray[$k] = explode('||',$val);
	        }
			$this->ajaxReturn($farray,'运行中',1);
	 	}else{
	 		$data=array();$farray=array();
	 		if(file_exists(LOG_PATH.'clical.log')){
	 			$farray = file(LOG_PATH.'clical.log');
	 			foreach($farray as $k=>$val){
		        	$farray[$k] = explode('||',$val);
		        }
	 		}
	 		$this->ajaxReturn($farray,'无程序在运行',0);
	 	}
	 }

}
?>