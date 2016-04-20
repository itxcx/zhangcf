<?php
defined('APP_NAME') || die('不要非法操作哦!');
class PublicAction extends Action {
	public $con='';
	//用户登录
	public function login()
    {
		//已经是登录状态,直接跳转到首页
		if(isset($_SESSION[C('USER_AUTH_KEY')])) {
            $this->redirect('Index/index');
			die;
        }
		if(CONFIG('USER_LOGIN_URL')!="")
		{
			echo "<script language='javascript'>location.href='".CONFIG('USER_LOGIN_URL')."';</script>";
			die;
		}
		$SYSTEM_STATE=CONFIG('SYSTEM_STATE');
		//系统开放时间
		$startOpenTime = CONFIG('startOpenTime');
		$endOpenTime = CONFIG('endOpenTime');
		$SYSTEM_CLOSE_TITLE=CONFIG('SYSTEM_CLOSE_TITLE');
		
		$week = date('w',systemTime());
		$hour = date('G',systemTime());

		$startHour = $startOpenTime ? $startOpenTime[$week] : 0;
		$endHour = $endOpenTime ? $endOpenTime[$week] : 24;
		
		if($hour < $startHour || $hour >= $endHour){
			$this->assign('isOpenTime','0');
			$this->assign('openTimeStr','今日开放时间为：'.$startHour.':00-'.$endHour.':00 当前时间不可访问');
		}else{
			$this->assign('isOpenTime','1');
			$this->assign('openTimeStr','今日开放时间为：'.$startHour.':00-'.$endHour.':00 当前时间可以访问');
		}
		//维护状态
		if($SYSTEM_STATE==2){
			die('<div style="margin:200px 0 30px 0;text-align:center;"><img src="/Public/Images/wzzzwhz_img.jpg"></div><div style="margin-bottom:200px;text-align:center;font-size:35px;font-weight:bold">'.$SYSTEM_CLOSE_TITLE.'</div>');
		}
		//无法访问
		if($SYSTEM_STATE==3){
			die('<div style="margin:200px 0 30px 0;text-align:center;"><img src="/Public/Images/404.jpg"></div>');
		}
		//客户端ip
		if(!isset($_SESSION['ip'])){
			$_SESSION['ip']=get_client_ip();
		}
		if(!isset($_SESSION['logintype'])){
			$_SESSION['logintype']='';
		}
		
		$usernum=M('会员')->count();
		$this->assign('usernum',$usernum);

		//检测登录次数,超过三次显示验证码
		$USER_LOGIN_VERIFY=CONFIG('USER_LOGIN_VERIFY');
		if($USER_LOGIN_VERIFY==1){
			if($_SESSION['ip'])
			{
				import("COM.LoginVerify.LoginVerify");
				$res = LoginVerify::checkloginrs($_SESSION['ip']);
				$this->assign('dispCode',$res);
			}
		}elseif($USER_LOGIN_VERIFY==2){
			$this->assign('dispCode',true);
		}else{
			$this->assign('dispCode',false);
		}
		//登录口设置的预览
		if(isset($_GET['loginTempNumber']) && $_GET['loginTempNumber']!=''){
			$this->display('login:'.$_GET['loginTempNumber'].':index');
			die;
		}
		//判定手机版
	    B('CheckAccess');
		if(isset($_SESSION['isMobile']) && $_SESSION['isMobile'])
		{
			$this->display('login:phone:index');
		}
		else
		{
			//登录口模版选择
			$number	 = CONFIG('DEFAULT_LOGIN_THEME');
			if($number!=''){
				$this->display('login:'.$number.':index');
			}else{
				$this->display('login:14:index');
			}
		}
    }
    //判断当前登录账号最近状态
    function checkuserlogin(){
    	$username=I("post.username/s");
    	$userid=M("会员")->where(array("编号"=>$username))->getField('id');
    	if($userid){
	    	$loglists=M("log_user")->where(array("user_id"=>$userid,"content"=>array("like","登录")))->order("create_time desc")->limit(5)->getField('id,content,ip,create_time');
	    	$errnum=0;$i=0;
	    	foreach((array)$loglists as $loglist){
	    		$i++;
	    		//判断是否登录错误
	    		if(strstr($loglist['content'],'错误')){
	    			$errnum++;
	    		}
	    		if($errnum>=2 && $i==$errnum){
	    			$this->error(L("显示验证码"));
	    		}
	    	}
	    	if($errnum>3){
	    		$this->error(L("显示验证码"));
	    	}
    	}
    }
    //登录判断
	public function check()
	{
		//判断是否已登录系统
		if(isset($_SESSION[C('USER_AUTH_KEY')])){
			$this->redirect("Index/index");
			exit;
		}
		//开放时间检测
		$startOpenTime = CONFIG('startOpenTime');
		$endOpenTime = CONFIG('endOpenTime');
		$week = date('w',systemTime());
		$hour = date('G',systemTime());

		$startHour = $startOpenTime ? $startOpenTime[$week] : 0;
		$endHour = $endOpenTime ? $endOpenTime[$week] : 24;
		//
		if($hour < $startHour || $hour >= $endHour){
			$this->redirect_url(L('对不起，该时间段暂不可访问'),__URL__.'/login');
		}
		$SYSTEM_STATE=CONFIG('SYSTEM_STATE');
		//维护状态
		if($SYSTEM_STATE==2){
			die('<div style="margin:100px 0 30px 0;text-align:center;"><img src="/Public/Images/wzzzwhz_img.jpg"></div>');
		}
		//无法访问
		if($SYSTEM_STATE==3){
			die('<div style="margin:100px 0 30px 0;text-align:center;"><img src="/Public/Images/404.jpg"></div>');
		}
		if(I('post.act/s')=="" && I('post.act/s') != 'verify')
		{
			$this->redirect_url(L('参数错误'),__URL__.'/login');
		}
		if(I('session.ip/s')=="")
		{
			$_SESSION['ip']=get_client_ip();
		}
		$username=I('post.username/s');
		$password=I('post.password/s');
		if($username=='')
		{
			$this->redirect_url(L('账号不能为空'),__URL__.'/login');
		}
		if($password=='')
		{
			$this->redirect_url(L('密码不能为空'),__URL__.'/login');
		}
		//获取当前时间前五分钟
		$times = time()-300;
		//获取该IP的操作时间
		$num = M('log_user')->where(array('ip'=>$_SESSION['ip'],'create_time'=>array('egt',$times),'content'=>'登录密码错误'))->count();
		//限制会员的登录操作次数
		if((int)$num>=5)
		{
			$this->redirect_url(L('对不起,您操作频繁,请五分钟后再试!'),__URL__.'/login');
		}
		//验证密码的“<>”符号
		if(preg_match("/<|>/",$password))
		{
			$this->redirect_url(L('密码格式不正确'),__URL__.'/login');
		}
		//判断是否需要验证码验证
		if(I('post.captcha/s',"NULL")!=="NULL"){
			import("ORG.Util.Verify");
			$Verify=new Verify();
			//验证当前sessionid的验证码
			$result=$Verify->check(I('post.captcha/s'),session_id());
			if($result !== true){
				$this->redirect_url(L($result),__URL__.'/login');
			}
		}
		//使用用户名、密码和状态的方式进行认证
		$userobj=X('user');
		//获得加密函数
		$authInfo=M('会员')->where(array('编号'=>$username,"登陆锁定"=>0))->find();
		
		import("COM.LoginVerify.LoginVerify");
		if(false === $authInfo || $authInfo===NULL) {
			//不存在账号
			LoginVerify::uploginrs($_SESSION['ip']);
			$this->redirect_url(L('账号不存在或已经禁用'),__URL__.'/login');
		}
		//验证登录密码
		if( !chkpass($password,$authInfo['pass1'])) {
			LoginVerify::uploginrs($_SESSION['ip']);
			$this->redirect_url(L('密码错误'),__URL__.'/login');
		}
		//验证会员的状态
		if(!$userobj->unaccLog){
			if($authInfo['状态']=='无效'){
				$this->redirect_url('用户未审核，禁止登陆',__URL__.'/login');
			}
		}
		//写入会员操作日志
		$data = array();
		$datalog['user_id']=$authInfo['id'];
		$datalog['user_name']=$authInfo['姓名'];
		$datalog['user_bh']=$authInfo['编号'];
		$datalog['ip']=$_SESSION['ip'];
		$datalog['content']='会员登录';
		$datalog['create_time']=time();
		//获取会员的IP地址
		import("ORG.Net.IpLocation");
		$IpLocation				= new IpLocation("qqwry.dat");
		$loc					= $IpLocation->getlocation();
		$country				= mb_convert_encoding ($loc['country'] , 'UTF-8','GBK' );
		$area					= mb_convert_encoding ($loc['area'] , 'UTF-8','GBK' );
		$datalog['address']		= $country.$area;
		M('log_user')->add($datalog);
		//写入会员操作日志结束
		$_SESSION[C('USER_AUTH_KEY')]	=  $authInfo['id'];
		$_SESSION[C('USER_AUTH_NUM')]	=  $authInfo['编号'];
		$_SESSION['username']		    =  $authInfo['姓名'];
		//记录最后登入ip
		$this->assign('ip',$_SESSION['ip']);
		//保存配置文件设置的SESSION信息
		foreach($userobj->getcon('session',array('name'=>'','rename'=>'')) as $session)
		{
			if($session['rename'] !=''){
				$_SESSION[$session['rename']]=$this->userinfo[$session['name']];
			}else{
				$_SESSION[$session['name']]=$this->userinfo[$session['name']];
			}
		}
		if(adminshow('mustout')){//数据量大了后，每次刷新都更新时间有点占进程，需要优化
			$sessionid = session_id();
			$_SESSION['sessionid'] = $sessionid;
			M()->startTrans();
			M('会员')->where(array('id'=>(int)$authInfo['id']))->save(array('最后登入IP'=>$_SESSION['ip'],'sessionid'=>$sessionid));
			M()->commit();
		}
		$this->redirect("Index/index");
	}
	// 用户登出
    public function logout() {
    	  
    	
        if(isset($_SESSION[C('USER_AUTH_KEY')])) {
        	unset($_SESSION[$_SESSION[C('USER_AUTH_NUM')].'a']['showtime']);
            unset($_SESSION[C('USER_AUTH_KEY')]);
            unset($_SESSION[C('PWD_SAFE')]);
			unset($_SESSION[C('USER_AUTH_NUM')]);
			unset($_SESSION[C('SAFE_PWD')]);
			unset($_SESSION['logintype']);
			unset($_SESSION['username']);
			unset($_SESSION['ip']);
			unset($_SESSION['actionUrl']);
			unset($_SESSION[C('SAFE_PWD3')]);
			unset($_SESSION['showtime']);
            //设置登陆口指定网址
			$this->redirect('Index/login/');
        }else {
			$this->error(L('已经退出'),__URL__.'/login/');
        }
    }



	//获得验证码
	public function verify()
	{
		
		import("ORG.Util.Verify");
		$Verify=new Verify();
		if(CONFIG('USER_LOGIN_VERIFY_TYPE')==1)
		{
			$Verify->usenum = true;
		}
		$Verify->fontSize = 15;
		$Verify->length   = 4;
		$Verify->useNoise = false;
		$Verify->entry(session_id());
	}
	
	//密码找回
	public function getUserPwd2(){
		$this->display('login:mobile:getPassWord');
	}
    
      //通过密保找回密码
    public function mibao(){
        $user=I("post.");
        $this->assign("waitSecond","5");
        if(!empty($user['userbh']) and !empty($user['userda']) and !is_array($user['userbh']) and !is_array($user['userda']) ){
            $m_mibao=M('密保');
            $m_user=M('会员')->where(array('编号'=>$user['userbh']))->find();
            if(!empty($m_user)){
                $mbdata=$m_mibao->where(array('uid'=>$m_user['id']))->find();
               if(!empty($mbdata) and $mbdata['密保答案']==trim($user['userda'])){
                    M()->startTrans() ;
                    M('会员')->where(array('编号'=>$m_user['编号']))->save(array('pass1'=>md100($m_user['编号']),'pass2'=>md100($m_user['编号'])));
                    
                    M()->commit() ;
                    $this->success(L('成功找回密码，新密码与您的帐号相同'),U('Index/index'));
                }
            }   
        }
        $this->error(L('您的答案不正确'));
    }
    
    //密保AJAX
    public function mibaoajax(){
        $user=I("post.");
        if(!empty($user['id']) and !is_array($user['id'])){
            $m_user=M('会员')->where(array('编号'=>$user['id']))->find();
            if(!empty($m_user)){
                $mbdata=M('密保')->where(array('uid'=>$m_user['id']))->getField('密保问题');
                if(!empty($mbdata)){
                    echo json_encode($mbdata);
                }
            }
        }
    }
    
	//通过短信找回密码
	public function getMess(){
		if(I("post.userid/s") != "" || I("post.telNum/s") != ""){
			$userid = trim(I("post.userid/s"));
			$telephone = trim(I("post.telNum/s"));
			$res = M('会员');
			$res2 = $res->where(array('编号'=>$userid))->find();
			
			if(!$res2){
				$this->error(L('该会员不存在'));
			}
			if($res2['移动电话'] != $telephone){
				$this->error(L('手机号码不正确'));
			}
			$pass1 = rand(100000,999999);
			$pass2 = rand(100000,999999);
			
			$hy['pass1'] = md100($pass1);
			$hy['pass2'] = md100($pass2);
			
			M()->startTrans();
			$res->where(array('编号'=>$userid))->save($hy);
		 if($res){
		 	 //发送找回密码的短信
		 	  $content = "尊敬的会员".$userid."!您通过短信找回密码，您的一级密码：".$pass1.",二级密码：".$pass2."。请尽快登录网站修改您的密码。";
		 	   $memo='会员找回密码';
		 	    //加载短信发送的类
		 	    import('COM.SMS.DdkSms');
		 	    $sms = new DdkSms();
		 	    $sms::send($telephone,$content,$memo,$userid);
		 	     M()->commit();
		 	     $this->success('短信正在发送中',U('Index/index'));
		 	      }
		 	       else{
		 	       	   $this->error('信息不完整');
		 	       }
		}
	}
	//发送短信方法
	function runThread($id,$count){
        $fp = fsockopen($_SERVER['HTTP_HOST'], $_SERVER["SERVER_PORT"], $errno, $errstr, 30);
		if (!$fp) {
			echo "$errstr ($errno)<br />\n";
		}else{
			//调用发送短信的方法
			$out  = "GET /index.php?s=/Admin/Sms/sendsmslist/id/".$id." / HTTP/1.1\r\n";
			$out .= "Host: ".$_SERVER['HTTP_HOST']."\r\n";
			$out .= "Connection: Close\r\n\r\n";
			
		    fwrite($fp, $out);
			//忽略执行结果//while (!feof($fp)) {echo fgets($fp, 128);}die;
			fclose($fp);
		}
	}
	
	//邮箱找回
	//public function find(){
	//	$this->display('login:hfd:find');
//	}
	//邮箱找回密码
	public function getEmail(){
			if(I("post.txtUserName/s") != "" || I('post.txtEmail/s') != ""){
			$userid = trim(I("post.txtUserName/s"));
			$email = trim(I('post.txtEmail/s'));
			$res = M('会员');
			$res2 = $res->where(array('编号'=>$userid))->find();
			
			if(!$res2){
				$this->error(L('该会员不存在'));
			}
			if($res2['email'] != $email){
				$this->error(L('邮箱不正确'));
			}
			$pass1 = rand(100000,999999);
			$pass2 = rand(100000,999999);
		
			$hy['pass1'] = md100($pass1);
			$hy['pass2'] = md100($pass2);
			
			M()->startTrans();
			$res->where(array('编号'=>$userid))->save($hy);
			$content = "尊敬的会员".$userid."!您通过邮箱找回密码，您的一级密码：".$pass1.",二级密码：".$pass2."。请尽快登录网站修改您的密码。";
			$subject = '密码找回';
			$model = M('站外邮件');
			$data['内容'] = $content;
			$data['发送时间'] = time();
			$data['发件人'] = '管理员';
			$data['标题'] = '密码找回';
			$data['收件人'] = $userid;
			$result = $model->add($data);
			if($result){
				$this->sendMail($email,$subject,$content);
				M()->commit();
				$this->success(L('邮件正在发送中'),U('Public/login'));
			}else{
				M()->rollback();
				$this->error(L('邮件未发送'));
			}
		}else{
			$this->error(L('信息不完整'));
		}
	}
	public function sendMail($email,$subject,$content)
	{
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
		//$this->setAddress($_REQUEST['mail_to'], 'Test User', 'to');
		 $Mail->Body = $content;
		 $Mail->addAddress($email, '');
		 $Mail->send();
		 //die();
		// 发件人的邮箱地址
		//$mail->from= CONFIG('MAIL_ADDRESS');
		// 设置发件人名字
		//$mail->loc_host= CONFIG('MAIL_FROMNAME');
		// 设置SMTP服务器。
		//$mail->smtp_host= CONFIG('MAIL_SMTP');
		// 设置用户名和密码。
		//$mail->smtp_acc= CONFIG('MAIL_LOGINNAME');
	//	$mail->smtp_pass= CONFIG('MAIL_PASSWORD');
		// 发送邮件。
	//	return($mail->send_mail($email,$subject,$content));
	}
	/*邮件找回密码
	public function getPassWord(){
		//此功能暂时停用
		die('此功能暂时停用');
		//停用原因是因为密码改为不可逆形式，所以找回密码流程需要从新设计
		$number=CONFIG('DEFAULT_LOGIN_THEME');
		if(isset($_REQUEST['submit']))
		{
			$where['编号']	= $_REQUEST['userid'];
			$email	= $_REQUEST['email'];
			$model	= M('会员');
			if($_REQUEST['userid']=='')
			{
				$this->ajaxReturn(L("请输入您的账号"),"",0);
				die;
			}
			if($email=='')
			{
				$this->ajaxReturn(L("请输入您的注册邮箱"),"",0);
				die;
			}
			$list	= $model->where($where)->find();
			if($list)
			{
				if($list['email']!=$email)
				{
					$this->ajaxReturn(L("邮件错误"),"",0);
				}
				else
				{
					//$pass	= mymd5($list['pass1'],'DE');
					$subject = '找回密码';
					$content = '尊敬的客户，你以前的密码:'.$pass.'
					****************************************************************************************************************
						';
					if($this->sendMail($email,$subject,$content)) {
						$this->ajaxReturn("","",1) ;die;
					}else{
						$this->ajaxReturn(L("邮件发送失败"),"",0);die;
					}
				}
			}
			else
			{
				$this->ajaxReturn(L("账户与邮箱不正确"),"",0);die;
			}
		}
		$this->display('login:'.$number.':getPassWord');
		
	
	}

	//短信找回密码
	public function getPassWordbySms(){
		die('此功能暂停使用');
		$number=CONFIG('DEFAULT_LOGIN_THEME');
		if(isset($_REQUEST['submit']))
		{
			$telephone	= $_REQUEST['telephone'];
			if($_REQUEST['userid']=='')
			{
				$this->ajaxReturn(L("请输入您的账号"),"",0);
				die;
			}
			if($telephone=='')
			{
				$this->ajaxReturn(L("请输入您的手机号码"),"",0);
				die;
			}
			$model	= M('会员');
			$where['编号']	= $_REQUEST['userid'];
			
			$list	= $model->where($where)->find();
			
			if($list)
			{
				if($list['移动电话']!=$telephone)
				{
					$this->ajaxReturn(L("手机号码错误"),"",0);
					die;
				}
				else
				{
					$pass	= mymd5($list['pass1'],'DE');
				
					import('COM.SMS.DdkSms');
					$result = DdkSms::send($list['移动电话'],'尊敬的'.$list['姓名'].'，会员编号'.$list['编号'].',您的一级密码:'.mymd5($list['pass1'],'DE').',二级密码:'.mymd5($list['pass2'],'DE').',登录网址:http://'.$_SERVER['HTTP_HOST']);

					if($result['status'] == true) {
						$this->ajaxReturn("","",1) ;
						die;
					}else{
						$this->ajaxReturn($result['info'],"",0);
						die;
					}
				}
			}
			else
			{
				$this->ajaxReturn(L("账户不正确"),"",0);
				die;
			}
		}
		$this->display('login:'.$number.':getPassWord');
	}*/
}
?>