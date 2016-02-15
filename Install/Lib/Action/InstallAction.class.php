<?php
/*
* 系统安装模块
*/
class InstallAction extends Action 
{
	public $lang_valid	= array('zh_cn','zh_tw','en_us');
	public $lang		= 'zh_cn';
	public $langs		= array();

	function _initialize()
    {
		if((isset($_REQUEST['step']) ? $_REQUEST['step']:'') !='done' && ACTION_NAME != 'done' && file_exists('./install.lock')){
			die('重新安装请删除/Install/install.lock文件');
		}

		$lang	= 'zh_cn';
		if( isset($_REQUEST['lang']) && in_array($_REQUEST['lang'],$this->lang_valid) )
		{
			$lang = $_REQUEST['lang'];
		}
		$this->lang		= $lang;

		
		include(COMMON_PATH.'languages/'.$lang.'.php');

		$this->assign('lang',$lang);
		$this->assign('langs',$_LANG);
	}

	/*
	* 默认方法
	*/
    public function index()
	{
		$step = isset($_REQUEST['step']) ? $_REQUEST['step'] : 'setup'; 
		if($step=='done'){
			echo "<script>location.href='/pc_denglu.php';</script>";exit;
		}
		$this->assign('step',$step);
		$this->display();
    }

	/*
	* 配置系统
	*/
	public function setup()
	{
		$passed_ips	= array('127.0.0.1','192.168.0.1');
		$clientip =  get_client_ip();
		
		if(in_array($clientip,$passed_ips) && file_exists(ROOT_PATH.'Admin/Conf/core_config.php')){
			$oldconfig = require(ROOT_PATH.'Admin/Conf/core_config.php');
		}else{
			$oldconfig = array(
				"DB_HOST" => "127.0.0.1",
				"DB_NAME" => "xsfm",
				"DB_USER" => "root",
				"DB_PWD" => "",
				"DB_PORT" => "3306",
			);
		}
		$this->assign('oldconfig',$oldconfig);
		//选择安装的项目列表
		$app_list = array(
				'应用中心'=>array('app'=>'Admin','checked'=>true,'disabled'=>true),
				'结算系统'=>array('app'=>'DmsAdmin','group'=>'Admin','checked'=>true),
			);
		$this->assign('app_list',$app_list);
		$this->display();
	}

	/*
	* 欢迎页面
	*/
	public function welcome()
	{
		$this->display();
	}

	/*
	* 检查环境
	*/
	public function check()
	{
		//获取应用的环境检测和目录检测
		$app_list		= explode(',',$_REQUEST['app_list']);
		
		$checkEnviro	= array(
			'extensions'	=> array(),
			'functions'		=> array(),
			'ini'			=> array()
		);
		$checkDir		= array();
		foreach($app_list as $app)
		{
			//判读是否存在分组
			if( strpos($app, ':') === false )
			{
				$_checkEnviro	= R("{$app}://Sync{$app}/checkEnviro");
				$_checkDir		= R("{$app}://Sync{$app}/checkDir");
			}
			else
			{
				$_app_array		= explode(':',$app);
				$app			= $_app_array[0];
				$group			= $_app_array[1];
				$_checkEnviro	= R("{$app}://{$group}/Sync{$app}{$group}/checkEnviro");
				$_checkDir		= R("{$app}://{$group}/Sync{$app}{$group}/checkDir");
			}
			if($_checkEnviro && is_array($_checkEnviro) && !empty($_checkEnviro) )
			{
				$checkEnviro['extensions'] = array_merge( $checkEnviro['extensions'] , $_checkEnviro['extensions'] );
				$checkEnviro['functions'] = array_merge( $checkEnviro['functions'] , $_checkEnviro['functions'] );
				$checkEnviro['ini'] = array_merge( $checkEnviro['ini'] , $_checkEnviro['ini'] );
			}
			if($_checkDir) $checkDir = array_merge( $checkDir , $_checkDir );
		}

		//判断是否有未通过的项目
		$is_passed		= true;
		$checkResult	= array();
		foreach( $checkEnviro['extensions'] as $extension )
		{
			if( !extension_loaded($extension) )
			{
				$is_passed = false;
				$checkResult[] = array( "{$extension} 扩展" , '不支持' , false  );
			}
			else
			{
				$checkResult[] = array( "{$extension} 扩展" , '支持' , true );
			}
		}
		foreach( $checkEnviro['functions'] as $function )
		{
			if( is_array($function) )
			{
				$func_name  = $function[0];		//函数名称
				$func_exp	= $function[1];		//计算的表达式
				$func_val	= $function[2];		//期望的值
				$hint_msg	= $function[3];		//提示信息
				$val		= call_user_func($func_name);

				if( isset($function[4]) )
				{
					$val	= call_user_func($function[4],$val);
				}
				eval('$check='.$val.$func_exp.$func_val.';');
				if($check)
				{
					$checkResult[] = array( "{$func_name} {$func_exp} {$func_val}" , '支持' , true  );
				}
				else
				{
					$is_passed = false;
					$checkResult[] = array( "{$func_name} {$func_exp} {$func_val}" , $hint_msg , false  );
				}
			}	
			else
			{
				if( !function_exists($function) )
				{
					$is_passed = false;
					$checkResult[] = array( "{$function} 函数" , '不支持' , false  );
				}
				else
				{
					$checkResult[] = array( "{$function} 函数" , '支持' , true );
				}
			}
		}
		
		foreach( $checkEnviro['ini'] as $extension )
		{
			$ext_name	= $extension[0];
			$ext_value	= $extension[1];
			$hint_msg	= $extension[2];
			
			$value		= ini_get($ext_name);
			$check		= false;

			if( is_array($ext_value) )
			{
				$check	= in_array($value,$ext_value);
				if( !$check )
				{
					$is_passed = false;
					$checkResult[] = array( "{$ext_name} 配置" , "应设置为 ".implode(' 或 ',$ext_value) , false  );
				}
				else
				{
					$checkResult[] = array( "{$ext_name} 配置" , '正常' , true );
				}
			}
			else
			{
				$check	= $value!=$ext_value?false:true;
				if( !$check )
				{
					$is_passed = false;
					$checkResult[] = array( "{$ext_name} 配置" , $hint_msg , false  );
				}
				else
				{
					$checkResult[] = array( "{$ext_name} 配置" , '正常' , true );
				}
			}
		}

		foreach( $checkDir as $dir )
		{
			if( isset($dir[2]) and $dir[2] == false )
			{
				$is_passed = false;
			}
		}
		
		//dump($checkResult);
		$this->assign('checkEnviro',$checkResult);
		$this->assign('checkDir',$checkDir);
		$this->assign('is_passed',$is_passed);
		$this->assign('app_list',$app_list);
		$this->display();
	}

	

	/*
	* 获取数据库列表
	*/
	public function get_db_list()
	{
		$Install	= D('Install');
		foreach($_POST as $post){
			if( preg_match('/\'|\"|;|select|truncate|drop|insert|update|delete|join|union|into|load_file|outfile/i',$post, $matches) ){
				echo json_encode('非法表单数据');
				die;
			}
		}
		$db_host    = isset($_POST['db_host']) ? trim($_POST['db_host']) : '';
		$db_port    = isset($_POST['db_port']) ? trim($_POST['db_port']) : '';
		$db_user    = isset($_POST['db_user']) ? trim($_POST['db_user']) : '';
		$db_pass    = isset($_POST['db_pass']) ? trim($_POST['db_pass']) : '';


		$databases  = $Install->get_db_list($db_host, $db_port, $db_user, $db_pass);

		if ($databases[0] === false)
		{
			echo json_encode( $databases[1] );
		}
		else
		{
			$result = array('msg'=> 'OK', 'list'=>implode(',', $databases[1]));
			echo json_encode($result);
		}
	}

	/*
	* 创建配置文件
	*/
	public function create_config_file()
	{
		$Install		= D('Install');
		$db_host		= isset($_POST['db_host'])      ?   trim($_POST['db_host']) : '';
		$db_port		= isset($_POST['db_port'])      ?   trim($_POST['db_port']) : '';
		$db_user		= isset($_POST['db_user'])      ?   trim($_POST['db_user']) : '';
		$db_pass		= isset($_POST['db_pass'])      ?   trim($_POST['db_pass']) : '';
		$db_name		= isset($_POST['db_name'])      ?   trim($_POST['db_name']) : '';
		$app_list		= isset($_POST['app_list'])		?   trim($_POST['app_list']) : '';
		$super_admin	= isset($_POST['super_admin'])	?   trim($_POST['super_admin']) : '';
		$timezone		= isset($_POST['timezone'])     ?   trim($_POST['timezone']) : 'Asia/Shanghai';
		foreach($_POST as $post){
			if( preg_match('/\'|\"|;|select|truncate|drop|insert|update|delete|join|union|into|load_file|outfile/i',$post, $matches) ){
				echo('非法表单数据');
				die;
			}
		}
		$conn		= @mysql_connect($db_host.':'.$db_port, $db_user, $db_pass);

		if ($conn === false){
			exit( '数据库链接失败!' );
		}
		
		if($app_list=='') exit( '请选择要安装的应用' );

		if($db_port=='') exit( '请填写数据库端口' );

		if($db_user=='') exit( '请输入数据库帐号' );

		if($db_name=='') exit( '请输入数据库名称' );

		if($super_admin=='') exit( '请输入管理员帐号' );


		//创建核心配置文件
		$result	= $Install->create_core_config($db_host, $db_port, $db_user, $db_pass, $db_name, $timezone, $super_admin, $this->langs);
		
		if ( $result[0] === false )
		{
			exit($result[1]);
		}

		//生成安装应用配置文件
		$result	= $Install->create_config_file('Install', array(), $this->langs);
		if ( $result[0] === false )
		{
			exit($result[1]);
		}
		
		$app_list	= explode(',' , $app_list);
		foreach($app_list as $app)
		{
			//判读是否存在分组
			if( strpos($app, ':') === false )
			{
				//获取应用定义的配置项
				$config_list	= R("{$app}://Sync{$app}/returnConfigList");
			}
			else
			{
				$_app_array		= explode(':',$app);
				$app			= $_app_array[0];
				$group			= $_app_array[1];
				//获取应用定义的配置项
				$config_list	= R("{$app}://{$group}/Sync{$app}{$group}/returnConfigList");
			}

			$result				= $Install->create_config_file($app, $config_list, $this->langs);
			
			if ( $result[0] === false )
			{
				exit($result[1]);
			}
		}
		echo 'OK';
	}

	/*
	* 创建数据库
	*/
	public function create_database()
	{
		$Install	= D('Install');
		$db_host    = isset($_POST['db_host'])      ?   trim($_POST['db_host']) : '';
		$db_port    = isset($_POST['db_port'])      ?   trim($_POST['db_port']) : '';
		$db_user    = isset($_POST['db_user'])      ?   trim($_POST['db_user']) : '';
		$db_pass    = isset($_POST['db_pass'])      ?   trim($_POST['db_pass']) : '';
		$db_name    = isset($_POST['db_name'])      ?   trim($_POST['db_name']) : '';

		$result		= $Install->create_database($db_host, $db_port, $db_user, $db_pass, $db_name);
		if ($result[0] === false)
		{
			echo $result[1];
			exit;
		}
		else
		{
			echo 'OK';
		}
	}

	/*
	* 安装基础数据
	*/
	public function install_base_data()
	{
		$Install	= D('Install');

		$app_list   = isset($_POST['app_list'])		?   trim($_POST['app_list']) : '';

		if($app_list=='') exit( '请选择要安装的应用' );

		$app_list	= explode(',' , $app_list);

		foreach($app_list as $app)
		{
			//判读是否存在分组
			if( strpos($app, ':') === false )
			{
				//获取应用定义的sql 文件
				$sql_str		= R("{$app}://Sync{$app}/returnSqlStr");
			}
			else
			{
				$_app_array		= explode(':',$app);
				$app			= $_app_array[0];
				$group			= $_app_array[1];
				//获取应用定义的sql 文件
				$sql_str		= R("{$app}://{$group}/Sync{$app}{$group}/returnSqlStr");
			}
			
			if(!$sql_str) continue;
			$result		= $Install->install_data($sql_str,$app);
			if ($result !== true )
			{
				echo $app.':<br />'.$result;
				exit;
			}
		}
		//安装基本配置
		$instcon = require APP_PATH.'InstallData/config.php';
		foreach($instcon as $key=>$val)
		{
			M('config',null)->add(array('name'=>$key,'data'=>serialize($val)));
		}
		echo 'OK';
	}

	/*
	* 创建超级管理员帐号
	*/
	public function create_admin_passport()
	{
		//创建超级管理员帐号
		$Install	= D('Install');

		$result		= $Install->create_admin_passport($this->langs);

		if( !$result ) exit( '创建管理员帐号失败' );

		echo 'OK';
	}

	/*
	* 处理其他操作 
	*/
	public function do_others()
	{
		$Install	= D('Install');

		$result		= $Install->do_others($this->langs);

		if ($result[0] === false)
		{
			exit($result[1]);
		}
		else
		{
			$passed_ips	= array('127.0.0.1','192.168.0.1');
			$clientip =  get_client_ip();
			if(!in_array($clientip,$passed_ips)){
				file_put_contents(ROOT_PATH.'Install/install.lock','重新安装请删除该文件!');
			}
			!is_dir(ROOT_PATH.'ThinkPHP/config') && mkdir(ROOT_PATH.'ThinkPHP/config',0777,true);
			$dh = opendir(ROOT_PATH.'ThinkPHP/config');
			while ($file=readdir($dh)) {
				if($file!='.' && $file!='..') {
					$fullpath=ROOT_PATH.'ThinkPHP/config/'.$file;
					if(!is_dir($fullpath)) {
						unlink($fullpath);
					}
				}
			}
			$t = true;
			B('SaveConfig',$t);
			
			echo 'OK';
		}
	}

	/*
	* 安装完成
	*/
	public function done()
	{
		$this->display();
	}
}
?>