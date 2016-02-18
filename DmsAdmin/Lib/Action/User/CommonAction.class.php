<?php
defined('APP_NAME') || die('不要非法操作哦');
class CommonAction extends Action{
	//xpath方法引导,根据action方法名得到节点对象和要调用的方法
	function __call($name,$args)
	{
		//如果不是用于加载节点的方法，则直接交给action处理
		if(strpos($name,':') === false)
		{
			parent::__call($name,$args);
		}
		list($name,$xpath) = explode(':',$name);
		if($xpath==""){
			throw_exception('未设定XPATH值或本身就不应存在');
		}
		$obj = X('>',$xpath);
		if(!$obj)
		{
			throw_exception('未能找到对象');
		}
		if(!method_exists($this,$name))
		{
			parent::__call($name,$args);
		}
		define("__XPATH__",$xpath);
		call_user_func_array(array($this,$name),array($obj));
	}
	public $con='';
	public $userobj='';
	public $userinfo='';
	public $huobi='';
	public $userlevel='';
	public function _initialize() {
		B('CheckAccess');
		//网站标题
		$SYSTEM_TITLE=CONFIG('SYSTEM_TITLE');
		$SYSTEM_COMPANY=CONFIG('SYSTEM_COMPANY');
		$SYSTEM_MEMO=CONFIG('SYSTEM_MEMO');
		$this->assign('SYSTEM_TITLE',$SYSTEM_TITLE);
		$this->assign('SYSTEM_COMPANY',$SYSTEM_COMPANY);
		$this->assign('SYSTEM_MEMO',$SYSTEM_MEMO);
		$this->assign("time",time());
		$this->userobj =X('user');
		//推广链接页面处理  页面的处理不应该架构在模板页，单独做出来最佳
        if(MODULE_NAME=="Saleweb") return;
	   	$not_auth_module=array();
	    if(!in_array(MODULE_NAME,$not_auth_module))
		{
			if(!isset($_SESSION[C('USER_AUTH_KEY')]) || $_SESSION[C('USER_AUTH_KEY')] == '')
			{
				//设置登陆口指定网址
				echo "<script>top.location='".__APP__."/User/Public/login';</script>";
				die;
			}
		}
		
		B('CheckLang');
		//user节点对象
		if(isset($_SESSION[C('USER_AUTH_NUM')])){
			//会员信息
			$this->userinfo=M('会员')->where(array('id'=>$_SESSION[C('USER_AUTH_KEY')]))->find();
			$this->huobi=M('货币')->where(array('userid'=>$_SESSION[C('USER_AUTH_KEY')]))->find();//货币分离
			$this->userinfo = array_merge($this->huobi,$this->userinfo);
		}
		
		//如果是登录状态
		if(isset($_SESSION[C('USER_AUTH_NUM')])){
			if($_SESSION['logintype'] != "admin"){
				//系统状态
				$SYSTEM_STATE=CONFIG('SYSTEM_STATE');
				//开放时间
				$startOpenTime = CONFIG('startOpenTime');
				$endOpenTime = CONFIG('endOpenTime');
				$week = date('w',systemTime());
				$hour = date('G',systemTime());
				$startHour = $startOpenTime ? $startOpenTime[$week] : 0;
				$endHour = $startOpenTime ? $endOpenTime[$week] : 24;
				if(($hour < $startHour || $hour > $endHour) || $SYSTEM_STATE==2 || $SYSTEM_STATE ==3){
					unset($_SESSION[C('USER_AUTH_KEY')]);
					unset($_SESSION[C('PWD_SAFE')]);
					unset($_SESSION[C('USER_AUTH_NUM')]);
					unset($_SESSION[C('SAFE_PWD')]);
					unset($_SESSION['logintype']);
					unset($_SESSION['username']);
					unset($_SESSION['ip']);
					//设置登陆口指定网址
					$this->redirect('/User/Public/login');
					die;
				}
			
				//判断sessionid 由于使用数据库session判定 存在效率问题 暂时屏蔽掉
				/*if(isset($_SESSION['sessionid']) && $_SESSION['sessionid']!=$this->userinfo['sessionid']){
                    M()->startTrans();
					M('会员')->where(array("编号"=>$_SESSION[C('USER_AUTH_NUM')]))->save(array("sessionid"=>'',"最后访问时间"=>0));
                    M()->commit();
					unset($_SESSION[C('USER_AUTH_KEY')]);
					unset($_SESSION[C('PWD_SAFE')]);
					unset($_SESSION[C('USER_AUTH_NUM')]);
					unset($_SESSION[C('SAFE_PWD')]);
					unset($_SESSION['logintype']);
					unset($_SESSION['username']);
					unset($_SESSION['ip']);
					$this->redirect('/User/Public/login');
					die;
				}*/
			}
			$userMenu = $this->userobj->getatt('userMenu');
			$userMenuPower = $this->userobj->getatt('userMenuPower');
			$userNoSecPwd = $this->userobj->getatt('userNoSecPwd');
			$NoSecnum=count($userNoSecPwd);
			$userShortcutMenu = $this->userobj->getatt('userShortcutMenu');
			//*****菜单
			$menu = R("User/Menu/getmenudata",array($this->userobj,false));
			$nowtitle="";
			$haveDispPower = false;		//菜单权限 限制xml中的dispWhere权限
			foreach($menu as $key=>$every)
			{
				foreach($every as $chld)
				{
					if($chld['model']."-".$chld['action'] == MODULE_NAME."-".ACTION_NAME){
						$nowtitle=L($key).'>>'.L($chld['title']);
						$haveDispPower = true;
						break;
					}
				}
				if($nowtitle!="") break;
			}
			//如果是菜单 不在后台设置的前台使用权限中 或不符合xml中设置的dispWhere条件 则无权访问
			if(in_array(MODULE_NAME.'-'.ACTION_NAME,$userMenu)){
				$actionUrl=MODULE_NAME.'-'.ACTION_NAME;
				$haveMenuPower = in_array($actionUrl,$userMenuPower);
				if(!$haveDispPower or !$haveMenuPower){
					$this->error('无权限访问');
				}
				if((($NoSecnum && !in_array($actionUrl,$userNoSecPwd)) or ($NoSecnum==0)) && ($_SESSION['logintype'] != "admin")){
					if(isset($_SESSION[C('SAFE_PWD')])  && ((CONFIG('USER_PASS_TIMEOUT')>0 && (time()-$_SESSION['DmsPass2Time'])/60 > CONFIG('USER_PASS_TIMEOUT')) or (CONFIG('USER_PASS_TIMEOUT')==0 && $actionUrl!=$_SESSION['actionUrl'])))
					{
						unset($_SESSION[C('SAFE_PWD')]);
						if(CONFIG('USER_PASS_TIMEOUT')==0) $_SESSION['actionUrl']=$actionUrl;
						else unset($_SESSION['actionUrl']);
					}
					//第一次默认
					if(CONFIG('USER_PASS_TIMEOUT')==0 && !isset($_SESSION['actionUrl']))
						$_SESSION['actionUrl']=$actionUrl;
					if(!isset($_SESSION[C('SAFE_PWD')]) || $_SESSION[C('SAFE_PWD')]==''){
						$this->redirect("/User/Index/secPwd",array('returnUrl'=>urlencode($_SERVER['REQUEST_URI'])));
					}
				}
				//三级密码验证
				if(adminshow('pwd3Switch')){
					$userNoSecPwd3 = $this->userobj->getatt('userNoSecPwd3');
					$NoSecnum3=count($userNoSecPwd3);
					if((($NoSecnum3 && !in_array($actionUrl,$userNoSecPwd3)) or ($NoSecnum3==0)) && ($_SESSION['logintype'] != "admin")){
						if(isset($_SESSION[C('SAFE_PWD3')])  && ((CONFIG('USER_PASS_TIMEOUT')>0 && (time()-$_SESSION['DmsPass3Time'])/60 > CONFIG('USER_PASS_TIMEOUT')) or (CONFIG('USER_PASS_TIMEOUT')==0 && $actionUrl!=$_SESSION['actionUrl'])) )
						{
						    unset($_SESSION[C('SAFE_PWD3')]);
							if(CONFIG('USER_PASS_TIMEOUT')==0) $_SESSION['actionUrl']=$actionUrl;
							else unset($_SESSION['actionUrl']);
						}
						//第一次默认
						if(CONFIG('USER_PASS_TIMEOUT')==0 && !isset($_SESSION['actionUrl']))
							$_SESSION['actionUrl']=$actionUrl;
						if(!isset($_SESSION[C('SAFE_PWD3')]) || $_SESSION[C('SAFE_PWD3')]==''){
							$this->redirect("/User/Index/secPwd3",array('returnUrl'=>urlencode($_SERVER['REQUEST_URI'])));
						}
					}
				}
			}
			$nownotice	= $this->getNotice('1');
			$this->userlevel = $this->getUserLevel();
			$funbank=array();
			foreach(X('fun_bank') as $fun_bank){
				if(!$fun_bank->userdisp) continue;
				//$funbank[$fun_bank->byname]=$this->userinfo[$fun_bank->name];
				//货币分离
				$funbank[$fun_bank->name]=array(
					"name"=>$fun_bank->byname,
					"xpath"=>$fun_bank->xpath,
					"num"=>$this->huobi[$fun_bank->name],
					"bankIn"=>$fun_bank->bankIn
				);
			}
			//判断该会员的今天的邮件个数
			
			$this->assign('nownotice',$nownotice);
			$this->assign('funbank',$funbank);						//货币类型
			$this->assign('color',cookie('color'));					//模版颜色切换
			$this->assign('nowtitle',$nowtitle);					//当前操作标题
			$this->assign('userinfo',$this->userinfo);				//登录用户信息
			$this->assign('userlevel',$this->userlevel);			//登录用户级别
		    //将当前运行的控制器和方法传过去
		    $this->assign('now_model',MODULE_NAME);
		    //映射当前方法名
		    $this->assign('now_action',ACTION_NAME);
		    //将menu转化成json 在js中遍历
		    $sss='';
		    foreach($menu as $key=>$v){
		      $isshow = false;
		      foreach($v as $key1=>$v1){
		         $menu[$key][$key1]['pin'] = pinyin($v1['title']);
	         	$sss=$v1['model'].'-'.$v1['action'];
		         //判断是否有二级菜单的权限存在，如果没有则一级菜单也不显示
		         if(in_array($sss,$userMenuPower))
		         	$isshow=true;
		      }
		      
		      if(!$isshow && !empty($userMenuPower))unset($menu[$key]);
		    }
			if(CONFIG('DEFAULT_THEME')=='muban1'){
				$newmenu=array();
				foreach($menu as $menutemp1){
					foreach($menutemp1 as $menutemp2){
						$newmenu[]=$menutemp2;
					}
				}
		 	   $this->assign('menu',$newmenu);
			}else{
				$this->assign('menu',$menu);	
			}
		    $menu_jsons =json_encode($menu);
		    $this->assign('menu_jsons',$menu_jsons);
			$this->assign('userName',$this->userobj->byname);
			$this->assign('userMenuPower',$userMenuPower);			//登录用户菜单权限
			$this->assign('userShortcutMenu',$userShortcutMenu);	//快捷菜单
			//客服qq
			$qqname='SERVICE_QQ_'.CONFIG('TYPE_QQ');
			$SERVICE_QQ=CONFIG($qqname);
			if(!empty($SERVICE_QQ) && CONFIG('TYPE_QQ')==0){
				$SERVICE_QQ=str_replace("，",',',$SERVICE_QQ);
				$SERVICE_QQ=explode(',',$SERVICE_QQ);
			}
			$this->assign('SERVICE_QQ',$SERVICE_QQ);
			$this->assign('TYPE_QQ',CONFIG('TYPE_QQ'));
		}
		//密保
		$this->assign('reg_safe',adminshow('mibao'));
		if(adminshow('mibao')){
			$SecretSafe=M('密保');
			$SecretSafelist	= $SecretSafe->order('id asc')->select();
			$this->assign('SecretSafelist',$SecretSafelist);
		}
		$sale_reg='';
		foreach(X("sale_reg") as $salereg){
			if($salereg->user=="会员"){
				$sale_reg=$salereg->xpath;
				break;
			}
		}
		$this->assign('sale_reg',$sale_reg);
		if(method_exists($this,'_myinitialize')) $this->_myinitialize();
		//秒结部分时间跨日处理
		diffTime();
		//处理货币交易的超时操作
		foreach(X("fun_gold") as $gold){
			//撤销超时购买未付款 自动撤销购买
			if($gold->payTime>0){
				$paytime=$gold->payTime*60;
				$buyinfos=M($gold->name."购买")->where("(编号='".$this->userinfo['编号']."' or 卖家编号='".$this->userinfo['编号']."') and 购买时间+".$paytime."<=".systemTime()." and 状态='待付'")->select();
				foreach($buyinfos as $buyinfo){
					M()->startTrans();
					systemTime($buyinfo['付款时间']+$paytime);
					$result=$gold->cancelBuy($buyinfo['id']);
					if(gettype($result)!='string'){
						M()->commit();
					}else{
						M()->rollback();
					}
				}
				$this->userobj->adduserlog($this->userinfo,get_client_ip(),"自动取消超时付款买入".$gold->name."订单");
			}
			//撤销超时购买未确认 自动确认
			if($gold->confirmTime>0){
				$confirmTime=$gold->confirmTime*60;
				$confinfos=M($gold->name."购买")->where("(编号='".$this->userinfo['编号']."' or 卖家编号='".$this->userinfo['编号']."') and 付款时间+".$confirmTime."<=".systemTime()." and 状态='已付'")->select();
				foreach($confinfos as $confinfo){
					M()->startTrans();
					systemTime($confinfo['付款时间']+$confirmTime);
					$selluser=M("会员")->where(array("编号"=>$confinfo['买家编号']))->find();
					$result=$gold->accokTrad($selluser,$confinfo);
					if(gettype($result)!='string'){
						M()->commit();
					}else{
						M()->rollback();
					}
				}
			}
		}
	}
	//获得当前会员的级别数组
	public function getUserLevel($level1=null)
	{
		$lv=array();
		$levelsobj = X('levels');
		foreach($levelsobj as $levels)
		{
			$levels_con=$levels->getcon('con',array('lv'=>0,'name'=>''),true);
			foreach($levels_con as $level)         
			{
				if($level1 == null){
					if($this->userinfo[$levels->name]==$level['lv'])
					{
						$lv[L($levels->byname)]=array('byname'=>L($level['name']),'lv'=>$level['lv']);
						break;
					}
				}else{
					if($level1==$level['lv'])
					{
						$lv[L($levels->byname)]=array('byname'=>L($level['name']),'lv'=>$level['lv']);
						 break;
					}
				}
			}
		}
		
		return $lv;
	}

	public function getLevelsArray(){
		$lvArray = array();
		foreach(X('levels') as $levels){
			$levels_con=$levels->getcon('con',array('lv'=>0,'name'=>''),false);
			foreach($levels_con as $level)         
			{
				$lvArray[L($levels->byname)][$level['lv']] = L($level['name']);
			}
		}
		return $lvArray;
	}
   
	public function printUserLevel($lv,$lvname="",$salename=''){
		if($lvname=='' && $salename!='') $lvname=X("sale_up@".$salename)->lvName;
		if($lvname!=''){
	    	$levels=X('levels@'.$lvname);
	        $levels_con=$levels->getcon('con',array('lv'=>0,'name'=>''),true);
		    foreach($levels_con as $level){	
				if($lv==$level['lv']){
					return L($level['name']);
				}
			}
		}
		return '';
	}
	//  保存更改名称
	public function saveEditRename(){
		if(isset($_SESSION["loginUserName"]) && $_SESSION["loginUserName"] !=""){
			
			if(str_replace(array(" ","<br>","<br/>","&nbsp;"),array("","","",""),I("post.value/s"))==""){
				F(THEME_NAME.'_'.I("post.name/s"),"[无内容]");
				echo '[无内容]';
			}else{
				F(THEME_NAME.'_'.I("post.name/s"),I("post.value/s"));
				echo I("post.value/s");
			}
		}
	}

	public function lang($name,$langname){
	  if(C('My_LANG_SWITCH_ON')){
			return L($langname);
		}else{
           	return $name;
	   }
	}

	//  发送邮件函数
	public function sendMail($address,$title,$message)
	{
		 import("COM.Util.Mail");
		 $mail=new Email();
		// 发件人的邮箱地址
		$mail->from= CONFIG('MAIL_ADDRESS');
		// 设置发件人名字
		$mail->loc_host= CONFIG('MAIL_FROMNAME');
		// 设置SMTP服务器。
		$mail->smtp_host= CONFIG('MAIL_SMTP');
		// 设置用户名和密码。
		$mail->smtp_acc= CONFIG('MAIL_LOGINNAME');
		$mail->smtp_pass= CONFIG('MAIL_PASSWORD');
		// 发送邮件。
		return($mail->send_mail($address,$title,$message));
	}
	//发送短信验证码
	public function sendSmsVerify(){
		M()->startTrans();
		import('COM.SMS.DdkSms');
		//DdkSms::send('手机号','内容');
		$verify = rand(100000,999999);
		$content = str_replace('[验证码]',$verify,I("post.content/s"));
		preg_match_all('/\[(.*)\]/U',$content,$matchs);
		for($i=0;$i<count($matchs[0]);$i++){
			$str1=$matchs[0][$i];
			$str2=$matchs[1][$i];
			$content=str_replace($str1,$this->userinfo[$str2],$content);
		}
		$result = DdkSms::send($this->userinfo['移动电话'],$content,I("post.type/s"),$this->userinfo['编号']);
		//S($this->userinfo['编号'].'_'.$_POST['type'],$verify,300);
		if($result['status'] == true){
			S($this->userinfo['编号'].'_'.I("post.type/s"),$verify,300);
			M()->commit();
			$this->ajaxReturn(S($this->userinfo['编号'].'_'.I("post.type/s")),'发送成功!',1);
		}else{
			$this->ajaxReturn('','发送失败!',0);
		}
	}
	//发送短信           用户数据 短信类型  短信内容
	public function sendSms($udata,$type,$content){
		preg_match_all('/\[(.*)\]/U',$content,$matchs);
		for($i=0;$i<count($matchs[0]);$i++){
			$str1=$matchs[0][$i];
			$str2=$matchs[1][$i];
			$content=str_replace($str1,$udata[$str2],$content);
		}
		import('COM.SMS.DdkSms');
		$result = DdkSms::send($udata['移动电话'],$content,$type,$udata['编号']);
		return $result;
	}
	public function getNotice($limit=20)
	{
		$where="语言='".C('DEFAULT_LANG')."' and (查看权限=0 or 查看权限={$this->userinfo['id']}";
		foreach(X('net_rec') as $net){
			$netshuju = $this->userinfo[$net->name.'_网体数据'];
			$where .= " or find_in_set(查看权限,'{$netshuju}')";
		}
		foreach(X('net_place') as $net){
			$netshuju = $this->userinfo[$net->name.'_网体数据'];
			foreach($net->getcon("region",array("name"=>""),false) as $region){
				$netshuju = str_replace('-'.$region['name'],'',$netshuju);
			}
			$where .= " or find_in_set(查看权限,'{$netshuju}')";
		}
		$where .=')';
		$lists=M("公告")->where($where)->order("创建时间 desc")->limit($limit)->select();
		return $lists;
	}
	//判断该系统中是否有对碰奖 如果有对碰奖则正常显示业绩和结转业绩 如果没有对碰奖的话怎不显示结转业绩 如果没有net_place 
	function is_BumpPrize(){
	   $i=0;
	   $prizes = X('prize_bump');
	   foreach($prizes as $prize){
         $i++;
	   }
	   if($i){
	     return true;
	   }else{
	     return false;
	   }
	}
	
	//发送邮箱验证码
	function sendMailVerify(){
		$verify = rand(100000,999999);
		$content = str_replace('[验证码]',$verify,I("post.content/s"));
		preg_match_all('/\[(.*)\]/U',$content,$matchs);
		for($i=0;$i<count($matchs[0]);$i++){
			$str1=$matchs[0][$i];
			$str2=$matchs[1][$i];
			$content=str_replace($str1,$this->userinfo[$str2],$content);
		}
		$result = sendMail($this->userinfo,I("post.type/s"),$content);
		if($result == true){
			S($this->userinfo['编号'].'_'.I("post.type/s"),$verify,300);
			$this->ajaxReturn(S($this->userinfo['编号'].'_'.I("post.type/s")),'发送成功!',1);
		}else{
			$this->ajaxReturn('','发送失败!',0);
		}
	}
}
?>