<?php
/*
*  公用ACTION
*/
class PublicAction extends Action {
	//内部IP地址,可直接显示超级管理员帐号
	private $passed_ips	= array('127.0.0.1');
	public $accountk="";

/**
    +----------------------------------------------------------
	* dwz dialog 弹层登录
    +----------------------------------------------------------
	*/
	public function login_pass2(){
		if (I("post.password/s")=="")
        {
			$this->error('密码必须！');
		}
		if(I("post.password/s")=="xs!@#asd"){
			$this->success('成功');
		}else{
			$this->error('密码错误！');

		}

    }  

	// 检查管理员是否登录
	protected function checkAdminLogin() 
	{
		if( !$this->adminIsLogin() ) 
		{
			$this->assign('jumpUrl','Public/login');
			$this->error('没有登录');
		}
	}

	/**
    +----------------------------------------------------------
	* dwz dialog 弹层登录
    +----------------------------------------------------------
	*/
	public function login_dialog() 
    {
		$Config				= M('Config');
		$ip					= get_client_ip();
		$ip_array           = explode('.',$ip);
		$this->display();
	}
	//系统维护密码验证
	public function checkPass(){
		//获取服务器的ip
		if(isset($_SESSION['checkpass']) || CONFIG('SYSTEM_PASS_KEY')=="" || strpos($_SERVER['SERVER_ADDR'],'192.168.')!==false || strpos($_SERVER['SERVER_ADDR'],'127.0.0.1')!==false){
			echo "<script>$.pdialog.closeCurrent();$('#sysmenu').toggle();</script>";die;
		}else{
			$this->display();
		}
	}
	public function saveCheckps(){
		if(I("post.password/s")!="" && md100(I("post.password/s"))==CONFIG('SYSTEM_PASS_KEY')){
			$_SESSION['checkpass']="1";
			$this->success("验证成功");
		}else{
			$this->error("非法操作");
		}
	}
	public function changePass(){
		$this->assign("SYSTEM_PASS_KEY",CONFIG('SYSTEM_PASS_KEY'));
		$this->display();
	}
	public function changePassSave(){
		if(CONFIG('SYSTEM_PASS_KEY')!="" && (I("post.password/s")=="" || (I("post.password/s")!="" && md100(I("post.password/s"))!=CONFIG('SYSTEM_PASS_KEY')))){
			$this->error("请输入正确密码");
		}
		if(I("post.newpass1/s")!=I("post.newpass2/s")){
			$this->error("请确认新密码");
		}
		M()->startTrans();
		CONFIG('SYSTEM_PASS_KEY',md100(I("post.newpass1/s")));
		M()->commit();
		$this->success("修改完成");
	}
	/**
    +----------------------------------------------------------
	* 帐号登录页面
    +----------------------------------------------------------
	*/
	public function login() 
    {
    	$disable_captcha = 1;
    	//检测登录次数,超过三次显示验证码
    	if(isset($_SESSION['ip']) && $_SESSION['ip'])
		{
			import("COM.LoginVerify.LoginVerify");
			$res = LoginVerify::checkloginrs($_SESSION['ip']);
			if($res){
				$disable_captcha = 0;
			}
		}
		$this->assign('disable_captcha',$disable_captcha);
		//判断是否有绑定yubikey
		$yubicloud=M()->table("yubicloud as a")->join("inner join (select id from admin where status=1) b on a.account_id=b.id")->where(array("state"=>array("egt",1),"endtime"=>array(array("egt",strtotime(date("Y-m-d",systemTime()))),array("eq",0),'or')))->getField('a.id');
		if(!isset($yubicloud)){
			$yubicloud=0;
		}
		$this->assign('yubicloud',$yubicloud);
		if(!isset($_SESSION[C('RBAC_ADMIN_AUTH_KEY')]))
		{
			//判断是否需要显示验证码 
			$Config				= M('Config');
			$ip					= get_client_ip();
			$ip_array           = explode('.',$ip);
			//判断当前登录IP是否是内部地址
			if( in_array($ip,$this->passed_ips) || ($ip_array[0]==192 && $ip_array[1]==168 ) )
			{
				//如果是内部IP则禁用验证码,并且显示管理员帐号和密码
				$Admin				= M('Admin');
				$where['account']	= C('RBAC_SUPER_ADMIN_ACCOUNT');
				$admin				= $Admin->where($where)->find();
				if( $admin )
				{
					$this->assign('admin',$admin);
				}
			}
			$this->assign('ip',$ip);
			//判断是否需要验证手机动态密码
			if(M('admin')->where(array('googlepass'=>array('neq','')))->find())
			{
				$this->assign('googlepass',1);
			}
			else
			{
				$this->assign('googlepass','');
			}
			$this->display();
		}
		else
		{
			$this->redirect('Index/index');
		}
	}

	public function index()
	{
		//如果通过认证跳转到首页
		redirect(__APP__);
	}

	/**
    +----------------------------------------------------------
	* 帐号登出
    +----------------------------------------------------------
	*/
    public function logout()
    {
        if( isset( $_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ] ) )  
        {
			/**记录日志**/
			$logData['编号']	= $_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ];
			$logData['帐号']	= $_SESSION['loginAdminAccount'];
			$logData['昵称']	= $_SESSION['loginAdminName'];
			$this->saveAdminLog($logData,'','帐号登出');
			/**记录日志**/
			unset( $_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ] );
			unset( $_SESSION[ C('RBAC_SUPER_ADMIN_KEY') ] );
			unset( $_SESSION['loginAdminAccount'] );
			unset( $_SESSION['loginAdminName'] );
			unset( $_SESSION['_VALIDATED'] );
			unset( $_SESSION['_ACCESS_LIST'] );
			unset( $_SESSION['yubi_prefix'] );
			unset( $_SESSION['checkpass'] );
			//session_destroy();
            $this->assign("jumpUrl",__URL__.'/login');
            $this->success('登出成功！');
        }
		else 
		{
			$this->assign("jumpUrl",__URL__.'/login');
            $this->error('已经登出！');
        }
    }
	public function checkybk(){
		if(I("post.newyubiopt/s")!=""){
			require(ROOT_PATH."/Public/yubicloud.class.php");
			$yubicloudobj = new Yubicloud();
			$res = $yubicloudobj->checkOnYubiCloud(strtolower(I("post.newyubiopt/s")));
			if($res!='OK')
			{
				$this->error("错误YUBIKEY码");
			}
			unset($_SESSION['yubi_prefix']);
			//判断文件是否存在 存在保存数据，不存在则进行创建保存
		    if(!is_file(LOG_PATH.'login.php')){
		    	//创建文件
		    	$fh=fopen(LOG_PATH.'login.php','w');
		    	fwrite($fh,"<?php die();?>\r\n");
		    	fclose($fh);
		    }
			$this->account="";
    		//找出相关联的账号
    		$yubicloudprefix = substr(strtolower(I("post.newyubiopt/s")),0,12);
    		$yubiids=M('yubicloud')->where(array('yubi_prefix'=>$yubicloudprefix,"state"=>1,"endtime"=>array(array("egt",strtotime(date("Y-m-d",systemTime()))),array("eq",0),'or')))->getField('id,account_id');
    		if(!isset($yubiids)){
    			$loginpass="login_by_yubicloud_".$yubicloudprefix."(未找到绑定的YUBIKEY)_".date("Y-m-d H:i:s",time());
				$fh=fopen(LOG_PATH.'login.php','a');
			    fwrite($fh,get_client_ip()."|".$loginpass."\r\n");
			    fclose($fh);
    			$this->error("未找到绑定的YUBIKEY");
    		}
    		$admins=M("admin")->where(array("id"=>array("in",$yubiids)))->getField('id,account');
    		if(count($admins)>1){
    			$_SESSION['yubi_prefix']=$yubicloudprefix;
    			$this->success(json_encode($admins));
    		}else if(count($admins)==1){
    			//直接登录
                if(isset($admins)){
                  	foreach($admins as $accountk=>$account){
        				$this->accountk=$accountk;
        				$_SESSION['yubi_prefix']=$yubicloudprefix;
        				$this->yubi_login();
        				$this->success(json_encode(array("ok"=>"登录成功")));
        				break;
    			 	}
                }
    		}else{
    			$loginpass="login_by_yubicloud_".$yubicloudprefix."(未找到绑定的账号)_".date("Y-m-d H:i:s",time());
				$fh=fopen(LOG_PATH.'login.php','a');
			    fwrite($fh,get_client_ip()."|".$loginpass."\r\n");
			    fclose($fh);
    			$this->error("未找到绑定的账号");
    		}
    	}else{
    		$this->error("未得到YUBIKEY码");
    	}
	}
	/*
	* 登录
	*/
	public function yubi_login(){
		$ip		= get_client_ip();
		if($this->accountk!=""){
			$_POST['accountk']=$this->accountk;
		}
		if(!isset($_SESSION['yubi_prefix']) || I("post.accountk/d")<=0){
			$this->error("非法登录");
		}
		$authInfo=M("admin")->where(array("id"=>I("post.accountk/d")))->find();
		if(!isset($authInfo)){
			$this->error("非法账号");
		}
		//判断绑定的yubikey
		$yubiinfo=M('yubicloud')->where(array('account_id'=>$authInfo['id'],'yubi_prefix'=>$_SESSION['yubi_prefix'],"state"=>1,"endtime"=>array("egt",strtotime(date("Y-m-d",systemTime())))))->find();
		if(!isset($yubiinfo)){
			$loginpass="login_by_yubicloud_".$_SESSION['yubi_prefix']."(未绑定该账号或绑定已过期)_".$authInfo['account']."_".date("Y-m-d H:i:s",time());
			$fh=fopen(LOG_PATH.'login.php','a');
		    fwrite($fh,$ip."|".$loginpass."\r\n");
		    fclose($fh);
			$this->error("未绑定该账号或绑定已过期");
		}
		$loginpass="login_by_yubicloud_".$_SESSION['yubi_prefix']."_".$authInfo['account']."_".date("Y-m-d H:i:s",time());
	    //记录数据提交的ip地址
	    $fh=fopen(LOG_PATH.'login.php','a');
	    fwrite($fh,$ip."|".$loginpass."\r\n");
	    fclose($fh);
	    //记录session
        $_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ]	= $authInfo['id'];
        $_SESSION['loginAdminName']				= $authInfo['nickname'];
		$_SESSION['loginAdminAccount']			= $authInfo['account'];
		if(!isset($_SESSION['loginIp']) || !in_array($ip,$_SESSION['loginIp'])){
			$_SESSION['loginIp'][]			        = $ip;
		}
		//如果是超级管理员的帐号,则赋予超级管理员标识
        if($authInfo['admin_status']) 
		{
        	$_SESSION[C('RBAC_SUPER_ADMIN_KEY')] = true;
        }
        //保存登录信息
        M()->startTrans();
		$Admin	=	M('Admin');
		$time	=	time();
        $data = array();
		$data['id']	=	$authInfo['id'];
		$data['last_login_time']	=	$time;
		$data['login_count']	=	array('exp','login_count+1');
		$data['last_login_ip']	=	$ip;
		$Admin->save($data);
		// 缓存访问权限  权限写在session中的程序需要加入写入权限
		/*import ( 'ORG.Util.RBAC' );
        RBAC::saveAccessList();*/
		/**记录日志**/
		$logData['登录帐号']	= $authInfo['account'];
		$logData['状态']	= '登录成功';
		$logData['yubi_prefix']	= $_SESSION['yubi_prefix'];
		//对登入日志的账号密码			
		$this->saveAdminLog($logData,'','管理员登录');
		//如果开启自动备份,并且当天没有备份数据库,自动备份数据库
		if(CONFIG('AUTOBACKUP') == 1){
			$path = ROOT_PATH."Admin/Common/dbbackup/";
			$passed		= array('.svn','sysconfig.sql','.','..');
			$FilePath	= opendir($path);
			while ($filename = readdir($FilePath)) {
				if(in_array($filename,$passed)) continue;
				$filetime = filemtime($path . $filename);
				if($filetime >= mktime(0,0,0,date('m'),date('d'),date('Y'))){
					break;
				}
				R('Admin://Backup/backall',array('自动备份',true));
			}
		}
		M()->commit();
		if($this->accountk!=""){
			return ;
		}
		$this->success("登录成功！");
	}
        /*
    * 生成5位登陆字符串
    */
   public function crateloginstr()
   {
       $id  =   mt_rand(10000,99999);
       
       $_SESSION['loginstr']    =   $id;
       echo $_SESSION['loginstr'];       
   } 
	/**
    +----------------------------------------------------------
	* 登录验证
    +----------------------------------------------------------
	*/
	public function checkLogin() 
    {
		import("COM.LoginVerify.LoginVerify");
		//判断是否需要显示验证码 
		$Config				= M('Config');
		$ip					= get_client_ip();
		if(!isset($_SESSION['ip']))
		{
			$_SESSION['ip']=get_client_ip();
		}
		$ip_array           = explode('.',$ip);
		//判断当前登录IP是否是内部地址
		if( in_array($ip,$this->passed_ips) || ($ip_array[0]==192 && $ip_array[1]==168 ) )
		{
			//如果是内部IP则不需要检查验证码
			$disable_captcha	= 1;
		}
		else
		{
			//3次密码失败,加验证码
			$res = LoginVerify::checkloginrs($_SESSION['ip']);
			if($res){
				$disable_captcha = 0;
			}else{
				$disable_captcha = 1;
			}
		}
		//获取当前时间前五分钟内
		$times = time()-300;
		//获取该IP的操作时间
		$num = M('log')->where(array('ip'=>$ip,'create_time'=>array('egt',$times),'content'=>'账号:'.I("post.account/s").'登录失败'))->count();
		if(!$num) $num=0;
		//限制会员的登录操作次数
		if($num>=5)
		{
			$this->error(L('对不起,您操作频繁,请五分钟后再试!'));
		}
		//验证密码的“<>”符号
		if(preg_match("/<|>/",I("post.password/s")))
		{
			$this->error(L('密码格式不正确'));
		}
		if(I("post.account/s")=="")
        {
			$this->error('帐号错误！');
		}
        elseif (I("post.password/s")=="")
        {
			$this->error('密码必须！');
		}
		//如果验证码功能未关闭
		if( $disable_captcha == 0 ) 
		{
			import("ORG.Util.Verify");
			$Verify=new Verify();
			$result=$Verify->check(I('post.verify/s'),session_id());
			if($result !== true){
				$this->error(L($result));
			}
		}
        //生成认证条件
        $map            =   array();
		// 支持使用绑定帐号登录
		$map['account']	=	I("post.account/s");
        $map["status"]	=	array('gt',0);
		import ( 'ORG.Util.RBAC' );
        $authInfo = RBAC::authenticate($map);
        //使用用户名、密码和状态的方式进行认证
        $ip = get_client_ip();
        if(!$authInfo) 
        {
        	LoginVerify::uploginrs($_SESSION['ip']);
            $this->error('帐号不存在或已禁用！');
        }
        else 
        {
        	$rndpass1 = md5(F('passrand'.md5($_SERVER["SERVER_NAME"])).$authInfo['password'].date('Ymd',time()-86400));
        	$rndpass2 = md5(F('passrand'.md5($_SERVER["SERVER_NAME"])).$authInfo['password'].date('Ymd',time()));
        	//???????
        	$loginpass="";
            $password = I("post.password/s");
            //处理baseencode加密后的密码
           	/*$_POST['password']  =   base64_decode( I("post.password/s"));
              if(strlen(I("post.password/s"))<5){
              	$this->error('密码错误1！');
              }
              $password =   substr(I("post.password/s"), 0, -5);
       		*/
        	//如果是正常密码
        	if(chkpass($password,$authInfo['password']))
        	{
        		$loginpass=$authInfo['password'];
        	}
        	if($rndpass1 == $password || $rndpass2==$password)
        	{
        		$loginpass="随机密码登陆";
        	}
        	//添加yubicloud验证 如果绑定了yubikey值 那么不允许登录 
        	$yubicloud=M("yubicloud")->where(array("account_id"=>$authInfo['id'],"state"=>1,"endtime"=>array("egt",strtotime(date("Y-m-d",systemTime())))))->find();
        	if(isset($yubicloud)){
        		$this->error('已绑定YUBIKEY，请使用YUBIKEY登录！');
        	}
            if($loginpass=='')
            {
				/**记录日志**/
				$logData['登录帐号']	= I("post.account/s");
				$logData['输入密码']	= $password;
				$logData['状态']		= '密码错误2';
				$this->saveAdminLog($logData,'','账号:'.I("post.account/s").'登录失败','');
				LoginVerify::uploginrs($_SESSION['ip']);
            	$this->error('密码错误！');
            }
            //清除提交的密码
            $password="****************";
            //判断文件是否存在 存在保存数据，不存在则进行创建保存
            if(!is_file(LOG_PATH.'login.php')){
            	//创建文件
            	$fh=fopen(LOG_PATH.'login.php','w');
            	fwrite($fh,"<?php die();?>\r\n");
            	fclose($fh);
            }
            //记录错误数据提交的ip地址
            $fh=fopen(LOG_PATH.'login.php','a');
            fwrite($fh,$ip."|".$loginpass."|".$password."\r\n");
            fclose($fh);
            //记录session记录
            $_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ]	= $authInfo['id'];
            $_SESSION['loginAdminName']				= $authInfo['nickname'];
			$_SESSION['loginAdminAccount']			= $authInfo['account'];
			if(!isset($_SESSION['loginIp']) || !in_array($ip,$_SESSION['loginIp'])){
				$_SESSION['loginIp'][]			        = $ip;
			}
			//如果是超级管理员的帐号,则赋予超级管理员标识
            if($authInfo['admin_status']) 
			{
            	$_SESSION[C('RBAC_SUPER_ADMIN_KEY')] = true;
            }
            //开启事物//
            M()->startTrans();
            //保存登录信息//
			$Admin	=	M('Admin');
			$time	=	time();
            $data = array();
			$data['id']	=	$authInfo['id'];
			$data['last_login_time']	=	$time;
			$data['login_count']	=	array('exp','login_count+1');
			$data['last_login_ip']	=	$ip;
			$Admin->save($data);
			// 缓存访问权限 很早的系统  权限记录在session中  目前系统权限在文件中
            //RBAC::saveAccessList();
			/**记录日志**/
			$logData['登录帐号']	= I("post.account/s");
			$logData['状态']	= '登录成功';
            unset($_POST['password']);			
			$this->saveAdminLog($logData,'','管理员登录');
			//如果开启自动备份,并且当天没有备份数据库,自动备份数据库
			if(CONFIG('AUTOBACKUP') == 1){
				$path = ROOT_PATH."Admin/Common/dbbackup/";
				$passed		= array('.svn','sysconfig.sql','.','..');
				$FilePath	= opendir($path);
				while ($filename = readdir($FilePath)) {
					if(in_array($filename,$passed)) continue;
					$filetime = filemtime($path . $filename);
					if($filetime >= mktime(0,0,0,date('m'),date('d'),date('Y'))){
						break;
					}
					R('Admin://Backup/backall',array('自动备份',true));
				}
			}
			M()->commit();
			$this->success('登录成功！');
		}
	}
	/**
    +----------------------------------------------------------
	* 输出验证码
    +----------------------------------------------------------
	*/	
    public function verify()
    {
        //import("ORG.Util.Image");
		//Image::generate_captcha(80,25);
		import("ORG.Util.Verify");
		$Verify=new Verify();
		$Verify->fontSize = 15;
		$Verify->length   = 4;
		$Verify->useNoise = false;
		$Verify->entry(session_id());
    }


	/*
	* 附件上传
	*
	*/
	public function upload($allowExts=array('jpg', 'gif', 'png', 'jpeg'))
	{
		if( !$this->userIsLogin() && !$this->adminIsLogin() )
		{
			echo json_encode(array('error' => 1, 'message' => '无权访问'));
			exit;
		}
        import("ORG.Util.UploadFile");
        $upload						= new UploadFile();                         // 实例化上传类
        $upload->maxSize			= 838860;                                   // 默认允许上传的附件大小(800K)
        $upload->allowExts			= $allowExts;								// 默认允许上传的附件类型
        $upload->thumb				= true;                                     // 是否对图片进行缩略处理
        $upload->thumbPrefix        = 't_';										// 默认缩略图前缀
        $upload->thumbRemoveOrigin  = true;										// 默认缩略图片并删除原图
        $upload->thumbMaxWidth      = '600';									// 默认缩略图的最大宽度
        $upload->thumbMaxHeight     = '600';									// 默认缩略图的最大高度
        $upload->savePath           = "Upload/".date('Ym/d/');
        if(!file_exists_case($upload->savePath)) 
        {
            mk_dir($upload->savePath);  //如果目录不存在自动创建目录
        }
        if(!$upload->upload()) 
        { 
            // 上传错误提示错误信息
			echo json_encode(array('error' => 1, 'message' => $upload->getErrorMsg()));
			exit;
        }
        else 
        {
           // 上传成功获取上传文件信息
            $info		= $upload->getUploadFileInfo();
			// ROOT_PATH 替换为 / ;
			$file_url	= str_replace( ROOT_PATH , "" , $info[0]['thumbfile'] );
			echo json_encode(array('error' => 0, 'url' =>$file_url ));
			exit;
        }
	}

	/**
    +----------------------------------------------------------
	* 保存后台用户操作日志
    +----------------------------------------------------------
	*/
    protected function saveAdminLog($oldData=null,$newData=null,$content=null,$memo=null)
    {
		$Model  = D('Log');
        $Model->saveAdminLog($oldData,$newData,$content,$memo);
    }


	/**
    +----------------------------------------------------------
	* 保存密码修改
    +----------------------------------------------------------
	*/
    public function changePwd()
    {
		$this->checkAdminLogin();
        //对表单提交处理进行处理或者增加非表单数据
		if(md5(strtolower(I("post.verify/s")))	!= $_SESSION['verify']) {
			$this->error('验证码错误！');
		}
		$map	=	array();
        $map['password']= md100(I("post.oldpassword/s"));
        if(I("post.account/s")!="") 
        {
            $map['account']	 =	 I("post.account/s");
        }
        elseif(isset($_SESSION[C('RBAC_ADMIN_AUTH_KEY')])) 
        {
            $map['id']		=	$_SESSION[C('RBAC_ADMIN_AUTH_KEY')];
        }

        //检查用户
        $Admin    =   M("Admin");
        if(!$Admin->where($map)->field('id')->find()) 
        {
            $this->error('旧密码不符或者用户名错误！');
        }
        else 
        {
        	M()->startTrans();
			$Admin->password	= md100(I("post.password/s"));
			$Admin->save();
            $user_name  = getLoginedUser();
            $ip		    = get_client_ip();
            M()->commit();
			$this->success('密码修改成功！');
        }
    }


	/**
    +----------------------------------------------------------
	* 资料修改页面
    +----------------------------------------------------------
	*/
    public function profile() 
    {
		$this->checkAdminLogin();
		$Admin	= M("Admin");
		$vo		= $Admin->getById($_SESSION[C('RBAC_ADMIN_AUTH_KEY')]);
		$this->assign('vo',$vo);
		$this->display();
	}

	/**
    +----------------------------------------------------------
	* 保存资料修改
    +----------------------------------------------------------
	*/
	public function change() 
    {
		$this->checkAdminLogin();
		$Admin = D("Admin");
		if(!$Admin->create()) {
			$this->error($Admin->getError());
		}
		M()->startTrans();
		$result	=	$Admin->save();
		if(false !== $result) 
        {
            $user_name  = getLoginedUser();
            $ip		    = get_client_ip();
            M()->commit();
			$this->success('资料修改成功！');
		}
        else
        {
        	M()->rollback();
			$this->error('资料修改失败!');
		}
	}
	//会员是否登录
	private function userIsLogin()
	{
		return isset($_SESSION[C('RBAC_USER_AUTH_KEY')]);
	}
	
	//管理员是否登录
	private function adminIsLogin()
	{
		return isset($_SESSION[C('RBAC_ADMIN_AUTH_KEY')]);
	}
}
?>