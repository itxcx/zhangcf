<?php
defined('APP_NAME') || die('不要非法操作哦!');
class ToolsAction extends CommonAction{

    public function index()	
    { 
		$lvobjs=X('levels');
		//得到会员级别
		$levels = array();
		foreach($lvobjs as $lvobj)
		{
			if(!empty($lvobj))
			{
				$pos=$lvobj->getPos();
				$levels[$lvobj->name]['pos']=$pos;
				$lvs=$lvobj->getcon('con',array('lv'=>1,'name'=>'name'));
				foreach($lvs as $lv)
				{
					$levels[$lvobj->name][]=array('lv'=>$lv['lv'],'name'=>$lv['name']);
				}
			}
		}
		//得到会员的推荐
		$net_recs=array();
		foreach(X('net_rec') as $net_rec)
		{
				$pos=$net_rec->getPos();
				$net_recs[$pos]=$net_rec->name;
		}

		//得到安置网络
		$net_places=array();
		foreach(X('net_place') as $net_place)
		{
				$pos=$net_place->getPos();
				$net_places[$net_place->name]=array('pos'=>$pos,'BranchNum'=>count($net_place->getBranch()));
		}
		//得到投资类别
		$fun_selects=array();
		foreach(X('fun_select') as $fun_select)
		{
			$pos=$fun_select->getPos();
			$fun_selects[$fun_select->name]['pos']=$pos;
			$lvs=$fun_select->getcon('con',array('val'=>'val','name'=>'name'));
			foreach($lvs as $lv)
			{
				$fun_selects[$fun_select->name][]=array('val'=>$lv['val'],'name'=>$lv['name']);
			}
		}
		//得到注册单类型
		$sale_regs = array();
		foreach(X('sale_reg') as $vreg)
		{
			$pos=$vreg->getPos();
			$sale_regs[$vreg->name]=array('blank'=>$vreg->nullMode,'user'=>$vreg->user,'lvname'=>$vreg->lvName);
		}

		$funbanks = array();
		foreach(X('fun_bank') as $funbank){
			$funbanks[]=$funbank -> name;
		}
		$tleobjs=X('tle');
		$SHOW_BULKREG=CONFIG('SHOW_BULKREG') ? CONFIG('SHOW_BULKREG') : 0;
		$this->assign('SHOW_BULKREG',$SHOW_BULKREG);
		$this->assign('levels',$levels);
		$this->assign('net_recs',$net_recs);
		$this->assign('net_places',$net_places);
		$this->assign('fun_selects',$fun_selects);
		$this->assign('sale_regs',$sale_regs);
		$this->assign('funbanks',$funbanks);
		$this->assign('tlepath',$tleobjs[0]->objPath());

		$this->display();
    }
    public function countInfo(){
    	//会员信息
    	$daytime=strtotime(date("Y-m-d",systemTime()));
    	$countuser=M('会员')->count('id');//总人数
		$day_join_user=M('会员')->where("登入日期>='".$daytime."' and 登入日期<'".($daytime+86400)."'")->count('id');//当天注册人数
		$day_reg_user=M('会员')->where("注册日期>='".$daytime."' and 注册日期<'".($daytime+86400)."'")->count('id');//活跃人数
		$userinfo['总人数']=$countuser;
		$userinfo['本日登录人数']=$day_join_user;
		$userinfo['本日注册人数']=$day_reg_user;
		$this->assign("userinfo",$userinfo);
    	//报单信息
    	$saleinfo=array();
		foreach(X('sale_*') as $sale)
		{
			$day_num=M('报单','dms_')->where(array('报单类别'=>$sale->name,'到款日期'=>array(array('egt',$daytime),array('lt',$daytime+86400))))->count('id');
			$all_num=M('报单','dms_')->where(array('报单类别'=>$sale->name))->count('id');
			$saleinfo[$sale->name]=array('day'=>$day_num,'all'=>$all_num);
		}
		$this->assign("saleinfo",$saleinfo);
    	//奖金信息
    	$tlenum=0;$prizenumall=0;
    	foreach(X("tle*") as $tle){
    		$tlenum+=M($tle->name)->where("1=1")->sum("收入");
    		$prizenumall+=M($tle->name)->where("1=1")->sum("奖金");
    	}
    	$this->assign("tlenum",$tlenum);
    	$this->assign("prizenumall",$prizenumall);
    	$prizeinfo=array();
    	foreach(X("prize_*") as $prize){
    		if($prize->prizeMode>0){
    			$prizenum=M($prize->parent()->name."总账")->where(array($prize->name=>array("neq",0)))->sum($prize->name);
    			$prizeinfo[$prize->byname]=array("num"=>isset($prizenum)?$prizenum:"0.00","rate"=>$prizenumall>0?(round(($prizenum/$prizenumall),4)*100)."%":"0.00%");
    		}
    	}
    	$this->assign("prizeinfo",$prizeinfo);
    	
    	//财务信息
    	$trande_money=0;$funbankinfo=array();
    	foreach(X('fun_bank') as $bank){
    		//会员货币余额
    		$c_bank=M('货币')->sum($bank->name);
    		$funbankinfo[$bank->byname."余额"]=isset($c_bank)?$c_bank:'0.00';
    		//后台充值
    		$a_bank=M($bank->name.'明细')->where('adminuser!=""')->sum('金额');
    		$funbankinfo[$bank->byname."充值"]=isset($a_bank)?$a_bank:"0.00";
    		//汇款充值
    		$trande_money+=M($bank->name.'明细')->where(array("类型"=>'类型'))->sum('金额');
    	}
    	//汇款记录
    	$funbankinfo['汇款充值']=$trande_money;
    	$notranders=M('汇款通知')->where('状态=0')->count('id');
    	$funbankinfo['未审核汇款']=$notranders;
    	$this->assign("funbankinfo",$funbankinfo);
    	$this->display();
    }
    function index1(){
    	
      	$lvobjs=X('levels');
		//得到会员级别
		$levels = array();
		foreach($lvobjs as $lvobj)
		{
			if(!empty($lvobj))
			{
				$pos=$lvobj->getPos();
				$levels[$lvobj->name]['pos']=$pos;
				$lvs=$lvobj->getcon('con',array('lv'=>1,'name'=>'name'));
				foreach($lvs as $lv)
				{
					$levels[$lvobj->name][]=array('lv'=>$lv['lv'],'name'=>$lv['name']);
				}
			}
		}
		//得到会员的推荐
		$net_recs=array();
		foreach(X('net_rec') as $net_rec)
		{
				$pos=$net_rec->getPos();
				$net_recs[$pos]=$net_rec->name;
		}

		//得到安置网络
		$net_places=array();
		foreach(X('net_place') as $net_place)
		{
				$pos=$net_place->getPos();
				$net_places[$net_place->name]=array('pos'=>$pos,'BranchNum'=>count($net_place->getBranch()));
		}
		//得到投资类别
		$fun_selects=array();
		foreach(X('fun_select') as $fun_select)
		{
			$pos=$fun_select->getPos();
			$fun_selects[$fun_select->name]['pos']=$pos;
			$lvs=$fun_select->getcon('con',array('val'=>'val','name'=>'name'));
			foreach($lvs as $lv)
			{
				$fun_selects[$fun_select->name][]=array('val'=>$lv['val'],'name'=>$lv['name']);
			}
		}
		//得到注册单类型
		$sale_regs = array();
		foreach(X('sale_reg') as $vreg)
		{
			$pos=$vreg->getPos();
			$sale_regs[$vreg->name]=array('blank'=>$vreg->nullMode,'user'=>$vreg->user,'lvname'=>$vreg->lvName);
		}

		$funbanks = array();
		foreach(X('fun_bank') as $funbank){
			$funbanks[]=$funbank -> name;
		}
		$tleobjs=X('tle');
		$SHOW_BULKREG=CONFIG('SHOW_BULKREG') ? CONFIG('SHOW_BULKREG') : 0;
		$this->assign('SHOW_BULKREG',$SHOW_BULKREG);
		$this->assign('levels',$levels);
		$this->assign('net_recs',$net_recs);
		$this->assign('net_places',$net_places);
		$this->assign('fun_selects',$fun_selects);
		$this->assign('sale_regs',$sale_regs);
		$this->assign('funbanks',$funbanks);
		$this->assign('tlepath',$tleobjs[0]->objPath());

		$this->display();
    
    }
	function isuseregs(){
   		if(I("post.SHOW_BULKREG/s")!=""){
   	   		M()->startTrans();
			CONFIG('SHOW_BULKREG',I("post.SHOW_BULKREG/s"));
			M()->commit();
		}
		$this->success('设置成功',__APP__.'/Admin/Tools/index');
	}
	// 批量注册
	public function userInsert()
	{
		//echo "暂停使用批量注册，在母程序压测完成后开放";
		//die();
		M()->startTrans();
		srand(trim(I("post.srand/s")));		//设置随机种子
		// 注册起始会员
		if(I("post.originUserNum/s") != ''){	
			$originUserNum = I("post.originUserNum/s");
			$originUser = $this->userobj->getuser(strval($originUserNum));
			if(!$originUser){
				$this->error('起始'.$this->userobj->byname.'编号不存在!');
			}
			foreach(X('net_rec,net_place') as $net){
				if($net->getdown($originUser)){
					$this->error('该'.$this->userobj->byname.'已有下级网体关系，不能作为起始'.$this->userobj->byname);
				}
			}
		}
		if($this->userobj===false){
			$this->error('参数错误');
		}
		$tleday   = I("post.tleday/d");
		$sale_reg = I("post.sale_reg/s");
		$thread   = I("post.thread/b");
		//$funbank = $_POST['funbank'];
		$sale_regobj=X('sale_reg@'.$sale_reg);
		$alldata = I("post.");
	    /*$regtype=$alldata['regtype'];
		if(!isset($regtype) || $regtype=='')
		{
			$this->error('未指定注册订单类型');
		}
        */
		$usernum=$alldata['num'];			//注册数量
		if(!isset($usernum)||$usernum==''||$usernum=='0')
		{
		   $this->error('未指定添加用户的数量');
		}
		$lvobjs=X('levels');
		
		//获得推荐数据
		$net_recs=X('net_rec');

		//获得安置数据
		$net_places=X('net_place');
		$regdate=$alldata['regdate'];			//注册时间
		if(!isset($regdate)||$regdate==''||$regdate=='0')
		{
		   $this->error('未指定添加用户的注册时间');
		}
		$todaynum=$alldata['todaynum'];
		$everyadd=$alldata['everyadd'];
		if($regdate=='递增')
		{
			
			if(!isset($todaynum) || $todaynum=='0')
			{
				$this->error('未指定首日注册人数');
			}
			if($todaynum>$usernum)
			{
				$this->error('首日人数不可以大于注册人数');
			}
			
			if(!isset($everyadd))
			{
				$this->error('未指定注册时间的增幅');
			}
		   
		}
           
		$startserial=$alldata['serial'];
		if(!isset($startserial)||$startserial=='')
		{
			$this->error('未指定添加用户的起始编号');
		}

		if(isset($originUserNum)){
			$strlen = strlen($startserial);
			$startserial = sprintf("%0{$strlen}u",intval($startserial)-1);
			$usernum = $usernum + 1;
		}

		//保存会员信息的数组
		$userarr=array();

		//第一个会员的数组
		$user1=array();
		//管理左右等数组
		
		//保存还可以推荐的会员数组
		$ableuserarr=array();
		
		if(I("post.originUserNum/s") == ''){
			
			CONFIG('TIMEMOVE_DAY',0);
			CONFIG('CAL_START_TIME',strtotime(date('Y-m-d',time())));
			CONFIG('DIFFTIME',strtotime(date('Y-m-d',time())));
			
		}
		
		//先第一个会员
	    $user1=array(
		  'userid'=>$startserial,
		  'addtime'=>$this->getUserregtime($userarr,$usernum,$regdate,$todaynum,$everyadd),
		);
		//默认密码
		//CONFIG('DEFAULT_USER_PASS1')?$user1['pass1']=CONFIG('DEFAULT_USER_PASS1'):$user1['pass1']=$user1["userid"];
		//CONFIG('DEFAULT_USER_PASS2')?$user1['pass2']=CONFIG('DEFAULT_USER_PASS2'):$user1['pass2']=$user1["userid"];
		//CONFIG('DEFAULT_USER_PASS3')?$user1['pass3']=CONFIG('DEFAULT_USER_PASS3'):$user1['pass3']=$user1["userid"];
		$user1['pass1']=1;
		$user1['pass2']=1;
		$user1['pass3']=1;
		if(isset($originUserNum)){
			$user1['userid'] = $originUserNum;
		}
		//获得第一个会员的会员级别
		$lvobjs=X('levels');
		foreach($lvobjs as $lvobj)
		{
			if($lvobj->name == $sale_regobj->lvName){
			   
				if(!empty($lvobj))
				{
					$pos=$lvobj->getPos();
					$level=$alldata['level'.$pos];
					$levelsname=$lvobj->name;
					if($level=='rand' || $level=='lowmore' || $level=='highmore')
					{
						$levels=$lvobj->getcon('con',array('lv'=>1));
						$j=1;
						foreach($levels as $v)
						{
							$levelarr[$levelsname][$j]=$v['lv'];
							$j+=1;
						}
					}else{
						$levelarr[$levelsname][]=$level;
					}
					$gelevel=$this->getUserlevel($levelarr[$levelsname],$level);
					//$user1[$levelsname]=$gelevel;
					//$user1['申请'.$levelsname]=$gelevel;
					
					$user1['lv']=$gelevel;
					if(isset($originUserNum)){
						$user1['lv']=$originUser[$lvobj->name];
					}
				}
		    }
		}

		//得到投资类别
		//初始电子货币
		foreach(X('fun_bank') as $fk=>$funbank){
			$user1['funbank'][$funbank->name]=I("data.".$fk."/f",'','',I("post.funbank/a"));
			//if($_POST['funbank'][$fk]>0)
			//{
			//	M($funbank->name.'明细')->add(array('编号'=>$user1['userid'],'金额'=>$_POST['funbank'][$fk],'时间'=>systemtime(),'类型'=>'自动注册'));
			//}
		}
		//第一个会员的安置
		foreach($net_places as $net_place)
		{
			if($net_place->useBySale($sale_regobj))
			{
				$placearr=$net_place->getBranch();
				$ppos=$net_place->getPos();
				$user1['prenetstr'.$ppos]='';
				foreach($placearr as $v0)
		        {
					$user1['net_'.$ppos]='';
					$user1['net_'.$ppos."_Region"]='';
					$user1[$net_place->name.'_人数']=0;
		        }
			}
		}
		//第一个会员的推荐
		foreach($net_recs as $net_rec)
		{
			if($net_rec->useBySale($sale_regobj))
			{
				 $pos=$net_rec->getPos();
				 $user1['net_'.$pos]="";
				 //$user1[$net_rec->name]='';
				 // $user1[$net_rec->name."_上级编号"]='';
				 $user1[$net_rec->name.'_推荐人数']=0;
				 $user1['alltj'.$pos]=$this->getTjnum($alldata['tjnum1'.$pos]);
				 $user1['tjnetstr'.$pos]='';
				
			}
		}

		$userarr[$startserial]=$user1;
		$ableuserarr[$startserial]=$user1;
		$randkey=$startserial;

		//dump($userarr);die;
		while(count($userarr)<$usernum){

		foreach($net_recs as $net_rec)
		{
			if($net_rec->useBySale($sale_regobj))
			{
				$recpos=$net_rec->getPos();
				$recname=$net_rec->name;
				$randuser=$userarr[$randkey];
				//dump($randuser);
		        if($randuser['alltj'.$recpos]>$randuser[$recname.'_推荐人数'])
		        {
					$user=$user1;
					//$user[$recname]=$randuser['userid'];
					$user['net_'.$recpos]=$randuser['userid'];
					//$user[$recname."_上级编号"]=$randuser['userid'];
					if($user['tjnetstr'.$recpos]!='')
					{
						$user['tjnetstr'.$recpos].=','.$user['net_'.$recpos];
					}else{
						$user['tjnetstr'.$recpos]=$user['net_'.$recpos];
					}
                    $user[$recname.'_推荐人数']=0;
					$user['addtime']=$this->getUserregtime($userarr,$usernum,$regdate,$todaynum,$everyadd);
					//会员级别
                    foreach($lvobjs as $lvobj)
					{
						if($lvobj->name == $sale_regobj->lvName){
							if(!empty($lvobj)){
								$lvpos=$lvobj->getPos();
								$level=$alldata['level'.$lvpos];
								$levelsname=$lvobj->name;
								if($level=='rand' || $level=='lowmore' || $level=='highmore')
								{
									$levels=$lvobj->getcon('con',array('lv'=>1));
									$j=1;
									foreach($levels as $v)
									{
										$levelarr[$levelsname][$j]=$v['lv'];
										$j+=1;
									}
								}else{
									$levelarr[$levelsname][]=$level;
								}
								$gelevel=$this->getUserlevel($levelarr[$levelsname],$level);
								$user['lv']=$gelevel;
							}
						}
					}
					
					$user['userid']=$this->getUserserial($userarr);
					


					foreach($net_places as $net_place)
					{
						if($net_place->useBySale($sale_regobj))
						{
							$placename=$net_place->name;
							$plapos=$net_place->getPos();
							$place=$alldata['place'.$plapos];
							$placearr=$net_place->getBranch();
							//$placenum=(int)$alldata['place'.$plapos.'_num'];
							//if($placenum!=0)
							//{
								//$placearr=array_slice($placearr,0,$placenum);
							//}
							$user[$placename.'_人数']=0;
		  
							//设置 安置人  这是相当于靠近自己
							if($place == 'balance')
							{
								foreach($userarr as $k=>$v)
								{  	 
									if(strpos($v['prenetstr'.$plapos],$user['net_'.$recpos]) !==false || $v['userid']==$user['net_'.$recpos])
									{
										if($v[$placename.'_人数']<count($placearr))
										{
											//$user[$placename]=$v['userid'];
											$user['net_'.$plapos]=$v['userid'];
											$user['net_'.$plapos.'_Region']=$placearr[$v[$placename.'_人数']];
											//$user[$placename.'_位置']=$placearr[$v[$placename.'_人数']];
											
											$userarr[$k][$placename.'_'.$placearr[$v[$placename.'_人数']].'区']=$user['userid'];
											$userarr[$k][$placename.'_人数']+=1;

											if($user['prenetstr'.$plapos]!='')
											{
												$user['prenetstr'.$plapos].=','. $v['userid'];
											}else{
												$user['prenetstr'.$plapos]= $v['userid'];
											}
											break;
										}else{
											if($user['prenetstr'.$plapos]!='')
											{
												$user['prenetstr'.$plapos].=','. $v['userid'];
											}else{
											    $user['prenetstr'.$plapos]= $v['userid'];
											}
										}
									}		
								}
							}
							//向下安置
							if($place == 'desc')
							{
								$ablepre=array();
								foreach($userarr as $k=>$v)
								{
									if(strpos($v['prenetstr'.$plapos],$user['net_'.$recpos]) !==false || $v['userid']==$user['net_'.$recpos])
									{
										if($v[$placename.'_人数']<count($placearr))
										{
											$ablepre[]=$k;
										}
									}
								}
								$pre=array_rand($ablepre,1);
								$randpre=$ablepre[$pre];
								$prenum=$userarr[$randpre][$placename.'_人数'];
								//$user[$placename.'_位置']=$placearr[$prenum];
								//$user[$placename]=$userarr[$randpre]['userid'];
								$user['net_'.$plapos]=$userarr[$randpre]['userid'];
								$user['net_'.$plapos.'_Region']=$placearr[$prenum];
								if($userarr[$randpre]['prenetstr'.$plapos]=='')
								{
									$user['prenetstr'.$plapos]=$userarr[$randpre]['userid'];
								}else{
									$user['prenetstr'.$plapos]=$userarr[$randpre]['prenetstr'.$plapos].','.$userarr[$randpre]['userid'];
								}
								//$userarr[$randpre][$placename.'_'.$placearr[$prenum].'区']=$user['userid'];
								//if($randpre == $originUserNum){
								//	$userarr[$startserial][$placename.'_人数']+=1;
								//}else{
									$userarr[$randpre][$placename.'_人数']+=1;
								//}
							
							}
						}
					}


					$user['alltj'.$recpos]=$this->getTjnum($alldata['tjnum1'.$recpos]);
					$userarr[$user['userid']]=$user;
					$userarr[$randkey][$recname.'_推荐人数']=$userarr[$randkey][$recname.'_推荐人数']+1;
					$ableuserarr[$user['userid']]=$user;
				}else{
					unset($ableuserarr[$randkey]);
					$randkey=array_rand($ableuserarr,1);
				}
			}
		}

		}

		
		if(isset($originUserNum)){
			array_shift($userarr);
		}
		set_time_limit(0);
		if(!isset($originUserNum)){
			$this->userobj->callevent('sysclear',array());
            //删除奖金构成文件
            import("COM.BakRec.BackRec");
			$BakRec = new BackRec();
			$BakRec->remove_directory(ROOT_PATH.'DmsAdmin/PrizeData/',false);
		}else{
			foreach($userarr as $userInfo){
				$originUser = $this->userobj->getuser(strval($userInfo['userid']));
				if($originUser){
					$this->error($this->userobj->byname.'编号'.$userInfo['userid'].'已存在!');
				}
			}
		}
		

		$this->reguser($userarr,$sale_regobj,$thread);
		$this->saveAdminLog('','',"批量注册");
		if($tleday>0){
			$oldays= CONFIG('TIMEMOVE_DAY');
			$oldstart=CONFIG('CAL_START_TIME');
			$newdays=$tleday+$oldays;
			$caltime=$oldstart+($tleday-1)*24*3600;
		    CONFIG('TIMEMOVE_DAY',$newdays);
		    M()->commit();
			R("DmsAdmin://Admin/Cal/settlementExecute",array($caltime,false));
			M()->startTrans();
		}
		M()->commit();
		$this->success('完成');
	}

	public function reguser($userarr,$sale,$thread)
	{

		//dump($userarr);
		//die();
		if($thread)
		{
			$posid=X('net_rec@')->getPos();
			foreach($userarr as $k=>$v)
			{
				F('reguser_'.$k,$v);
				$down = array();
				foreach($userarr as $k2=>$v2)
				{
					if($v2['tjnetstr'.$posid]==$v['userid'])
					{
						$down[] = $v2['userid'];
					}
				}
				F('reguser_down'.$k,$down);
			}
				
				//对自己进行回调
				$fp = fsockopen($_SERVER['HTTP_HOST'], $_SERVER["SERVER_PORT"], $errno, $errstr, 30);
				$link = U('Admin/Tools/threadReg:'.$sale->objPath().'?key=000001','');
				$out = "GET {$link} HTTP/1.1\r\n";
				$out .= "Host: ".$_SERVER['HTTP_HOST']."\r\n";
				$out .= "Connection: Close\r\n\r\n";
				fwrite($fp, $out);
				///忽略执行结果
				//while (!feof($fp)) {echo fgets($fp, 128);}
				fclose($fp);
			return;
		}
		
		foreach($userarr as $k=>$v)
		{
			M()->execute('set autocommit=0;');
			unset($_REQUEST);
			unset($_POST);
			$_REQUEST=$v;
			$_POST=$v;
			//进行时间偏移设置
			
			CONFIG('TIMEMOVE_DAY',$v['addtime']);
			//从新根据当前设置定义时间
			systemTime(0);
			//执行时间差处理.
			M()->commit();
			diffTime();
			M()->startTrans();
			//注册
			$sale->regSave(I("post."));
		}
	}
	//多线程注册
	public function threadReg($sale)
	{
		unset($_REQUEST);
		unset($_POST);
		$data = F('reguser_'.I("get.key/s"));

		$_REQUEST = $data;//unserialize(base64_decode($_GET['post']));
		$_POST    = $data;//unserialize(base64_decode($_GET['post']));

		M()->execute('set autocommit=0;');
		CONFIG('TIMEMOVE_DAY',I('post.addtime/s'));
		$sale->regSave(I("post."));
		//
		$down = F('reguser_down'.I("get.key/s"));
		foreach($down as $k=>$v)
		{
				$fp = fsockopen($_SERVER['HTTP_HOST'], $_SERVER["SERVER_PORT"], $errno, $errstr, 30);
				$link = U('Admin/Tools/threadReg:'.$sale->objPath().'?key='.$v,'');
				$out = "GET {$link} HTTP/1.1\r\n";
				$out .= "Host: ".$_SERVER['HTTP_HOST']."\r\n";
				$out .= "Connection: Close\r\n\r\n";
				fwrite($fp, $out);
				///忽略执行结果
				//while (!feof($fp)) {echo fgets($fp, 128);}
				fclose($fp);
		}
	}
	//循环递归得到已推荐为基点随机推荐  但是只能得到80个 已被舍弃无用
	public function getusertj($userarr,$ableuserarr,$num,$tjnum)
	{
		if(count($ableuserarr)!=0){
			$key=array_rand($ableuserarr,1);
			$user1=$userarr[$key];
			if($user1['alltj']>$user1['推荐_推荐人数'])
			{
				$user=$user1;
				$user['推荐']=$user1['编号'];
				$user['编号']=$this->getUserserial($userarr);
				$user['alltj']=$this->getTjnum($tjnum);
				$user['推荐_推荐人数']=0;
				if(count($userarr)<$num)
				{
					$userarr[$user['编号']]=$user;
					$userarr[$key]['推荐_推荐人数']=$userarr[$key]['推荐_推荐人数']+1;
					$ableuserarr[$user['编号']]=$user;
					$userarr=$this->getUser($userarr,$ableuserarr,$num,$tjnum);
				}
			}else{
				unset($ableuserarr[$key]);
				$userarr=$this->getUser($userarr,$ableuserarr,$num,$tjnum);
			}
		}
		return $userarr;
	}

	//返回会员的安置人  也用不到
	public function getUserpre($userarr,$tjuserial,$placename)
	{
		$res='';
		foreach($userarr as $k=>$v)
		{
		   if(strpos($v['prenetstr'],$tjuserial) !=false || $v['编号']==$tjuserial)
			{
			   if($v[$placename.'_人数']<2)
				{
				   $userarr[$k][$placename.'_人数']+=1;
				   $res=$v['编号'];
				}
			}
	    }
	}
  
	//忘了是干嘛的 但是用不到
	public function getprearr($prearr,$userarr,$prenum)
	{
		if($prenum!='')
		{
			foreach($userarr as $val)
			{
				if($val['管理']==$prenum)
				{
					$prearr[]=$val['编号'];
				}
			}
			$prearr=$this->getprearr($prearr,$userarr,$prenum);
		}
		return $prearr;
	
	}
	/*
	$prearr=array();
	   while($preserial!='')
		{
		   $prearr[]=$preserial;
		   $preserial=$userarr[$tjuser['推荐']];
		}
   */
	//参数为会员数组,返回会员数组最后一个编号+1
	public function getUserserial($serial)
	{
		$last=end($serial);
		$lastkey=key($serial);
		//$string='cn';
		$len=strlen($lastkey);
		$serial1=$lastkey+1;
		$len1=strlen($serial1);
		$diff=$len-$len1;
		if($diff>0)
		{
			for($j=0;$j<$diff;$j++)
			{
				$serial1='0'.$serial1;
			}
		}
		return $serial1;
	}
	//参数为1-5格式 或者 2 获得推荐返回内的随机一个数值
	public function getTjnum($tjnum)
	{
		$pos=strpos($tjnum,'-');
		if($pos!==false)
		{
			$tjnumlen=strlen($tjnum);
			$tjnum1=substr($tjnum,0,$pos);
			$tjnum2=substr($tjnum,$pos+1);
			if($tjnum1>$tjnum2 || $pos==0 || $pos==$tjnumlen-1 )
			{
				$this->error('推荐参数错误');
	      	} 
			return rand($tjnum1,$tjnum2);
		 }else{
			return $tjnum;
		}
	}
	//获得会员的级别
	public function getUserlevel($levelarr,$level)
	{
		if($level=='rand')
		{
			$randkey=array_rand($levelarr,1);
			$lv=$levelarr[$randkey];
		}elseif($level=='highmore'){
			$lvrand=rand(0,100);
			$levelarr1=$levelarr;
			$lvk=array_flip($levelarr1);
			$totalv=array_sum($lvk);
			$levelrate=array();
			foreach($levelarr as $key=>$val)
			{
               $levelrate[$val]=$key*100/$totalv;
			   while($key>1)
				{
			   $levelrate[$val]+=($key-1)*100/$totalv;
			   $key-=1;
			    }
	
			}
			foreach($levelrate as $k=>$v)
			{
				if($lvrand<=$v)
				{
                   $lv=$k;
				   
				   break;
				}
			}
		}elseif($level=='lowmore'){	
			$lvrand=rand(0,100);
			$levelarr1=$levelarr;
			$lvk=array_flip($levelarr1);
			$totalv=array_sum($lvk);
			$levelrate=array();
			$levelarr2=array_reverse($levelarr);
		   foreach($levelarr2 as $key=>$val)
			{
               $levelrate[$val]=($key+1)*100/$totalv;
			   while($key>0)
				{
			   $levelrate[$val]+=$key*100/$totalv;
			   $key-=1;
			    }
	
			}
			foreach($levelrate as $k=>$v)
			{
				if($lvrand<=$v)
				{
                   $lv=$k;
				   
				   break;
				}
			}
		}else{
			$lv=(int)$levelarr[0];
		}
		return $lv;

	}

	//获得会员的注册时间
    public function getUserregtime($userarr,$num,$regdate,$todaynum,$everyadd)
	{
		if($regdate=='递增')
		{
		  $todaynum=(integer)$todaynum;
          $everyadd=(integer)$everyadd/100;
		  $hasuser=count($userarr)+1;
		  $eveasc=$todaynum*$everyadd;

		  $ascall=$todaynum;
		  $i=0;
		  while($hasuser>$ascall)
			{
              $i+=1;
			  $ascall+=$todaynum+$eveasc*$i;
			  
			  }
			  return $i;
		//return systemTime()+24*3600*$i;
		}else{
			//每天应该注册的
         $every=ceil($num/$regdate);
		 //已经注册的会员数
	     $hasuser=count($userarr)+1;
		 //$nexttime=systemTime()+24*3600*(ceil($hasuser/$every)-1);
		 //$showtime=(integer)$nexttime;
		 return (ceil($hasuser/$every)-1);
		 //return $showtime;
		}
	}
	

	
	//-------------------------从新统计会员各个奖金表的累计收入字段值
	public function reTleSum()
	{
		
		foreach(X('tle') as $tle)
		{
			$m_users=M('会员')->select();
			foreach($m_users as $m_user)
			{
				$srlj = 0;
				$m_tles=M($tle->name)->where(array('编号'=>$m_user['编号']))->order('计算日期 asc,id asc')->select();
				foreach($m_tles as $m_tle)
				{
					$srlj += $m_tle['收入'];
					M($tle->name)->where('id='.$m_tle['id'])->save(array('累计收入'=>$srlj));
				}
			}
		}
	
	}
  	public function reBank()
	{	
		foreach(X('fun_bank') as $bank)
		{
			M('会员')->where('1=1')->save(array($bank->name=>0));
			$m_users=M('会员')->select();
			foreach($m_users as $m_user)
			{
				$srlj = 0;
				$m_tles=M($bank->name.'明细')->where(array('编号'=>$m_user['编号']))->order('时间 asc,id asc')->select();
				//dump($m_tles);
				foreach($m_tles as $m_tle)
				{
					$srlj += $m_tle['金额'];
					M($bank->name.'明细')->where(array('id'=>$m_tle['id']))->save(array('余额'=>$srlj));
				}
				
				M('会员')->where(array('编号'=>$m_user['编号']))->save(array($bank->name=>$srlj));
			}
		}
	}
	
	//随机压力注册测试
	public function userBulkInsert()
	{
		define('BULK_INSERT',true);
		set_time_limit(1800);
		$bh=(substr(md5(microtime()),0,10));
		$sale_reg = X('sale_reg@'.I("post.sale_reg/s"));

		$net_recs=array();
		foreach(X('net_rec') as $net_rec)
		{
			if($net_rec->useBySale($sale_reg))
			{
				$pos=$net_rec->getPos();
				$net_recs[$pos]=$net_rec->name;
			}
		}
		
		//得到安置网络
		$net_places=array();
		foreach(X('net_place') as $net_place)
		{
			if($net_place->useBySale($sale_reg))
			{
				$pos=$net_place->getPos();
				$net_place->setRegion = false;
				$net_places[$net_place->name]=array('pos'=>$pos,'BranchNum'=>count($net_place->getBranch()));
				$netname=$net_place->name;
			}
		}
		$users = M('会员')->where('('.$netname.'_左区="" or '.$netname.'_右区="")')->Field('编号')->limit(I("post.num/d"))->select();
		$_POST['saleid']=microtime();
		$i=0;
		$_POST['lv']=1;
		G('run');
		foreach($users as $user)
		{
			$i++;
			$_POST['userid']=$bh.'_'.$i;
			
			foreach($net_recs as $key=>$val)
			{
				$_POST['net_'.$key]=$user['编号'];
			}
			foreach($net_places as $key=>$val)
			{
				
				$_POST['net_'.$val['pos']]=$user['编号'];
			}
			M()->startTrans();
			$reg = $sale->regSave((I("post.")));
			M()->commit();
		}
		
		echo "一共注册".$i.'人,用时'.G('run','end').'s';
	}
	
	//货币归档
	function arrange(){
		$bankarr = array();
		foreach(X('fun_bank') as $v)
		{
			$bankarr[]=$v->name;
		}
		$this->assign('banks',$bankarr);
		$this->display();
	}
	function arrangecommit(){
		if(I("request.funname/s")==''){
			$this->error("请选择货币类型");
		}
		if(I("request.arrangeDate/s")==''){
			$this->error("请选择归档时间");
		}
		set_time_limit(0);
		ini_set('memory_limit','1000M');
		$todate = I("request.arrangeDate/s");
		foreach(X('fun_bank') as $v)
		{
			if($v->name==I("request.funname/s")){
				M()->startTrans();
				$sql = "insert into dms_".$v->name."明细(时间,编号,来源,类型,金额,余额,备注,dataid)select (UNIX_TIMESTAMP('".$todate."')-1),编号,编号,'归档',sum(金额),sum(金额),'".$todate."前数据归档到此条记录',-1 from dms_".$v->name."明细 where 时间<UNIX_TIMESTAMP('".$todate."')  and 类型<>'归档' group by   编号";
				M()->execute($sql);
				M($v->name."明细")->where(array("时间"=>array("lt","UNIX_TIMESTAMP('".$todate."')"),"类型"=>array("neq","归档")))->delete();
				M()->commit();
				$this->success($v->name.'归档成功');
			}
		}
	}
}
?>