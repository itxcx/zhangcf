<?php
// 系统安装模块,功能封装
class InstallModel
{
	/*
	* 处理其他操作
	*
	*/
	function do_others($langs)
	{
		/****重置已安装的支付数据****/
		$payfile = ROOT_PATH."Admin/Runtime/Data/installedPayment.php";
		if(!is_dir(dirname($payfile)))
		    // 如果静态目录不存在 则创建
		    mkdir(dirname($payfile),0777,true);
		file_put_contents($payfile,'<?php return array (); ?>');
		/********** 插入应用节点 *********/
		$app_list   = isset($_POST['app_list'])		?   trim($_POST['app_list']) : '';
		if($app_list=='') exit($this->langs['not_select_app']);

		$app_list	= explode(',' , $app_list);

		

		$Node		= M('Node');
		foreach($app_list as $app)
		{
			$group				= '';
			//判读是否存在分组
			if( strpos($app, ':') === false )
			{
				//获取应用的信息
				$appInfo	= R("{$app}://Sync{$app}/returnApplicationInfo");
			}
			else
			{
				$_app_array		= explode(':',$app);
				$app			= $_app_array[0];
				$group			= $_app_array[1];
				//获取应用的信息
				$appInfo	= R("{$app}://{$group}/Sync{$app}{$group}/returnApplicationInfo");
			}
			
			if( $appInfo )
			{
				//插入应用节点
				$data['name']			= $app;
				$data['group']			= $group;
				$data['title']			= $appInfo['title'];
				$data['level']			= 1;
				$data['status']			= 1;
				$data['sort']			= isset($appInfo['sort'])?$appInfo['sort']:1;
				$data['is_sync_node']	= isset($appInfo['is_sync_node'])?$appInfo['is_sync_node']:0;
				$data['is_sync_menu']	= isset($appInfo['is_sync_menu'])?$appInfo['is_sync_menu']:0;
				$data['is_quick_search']= isset($appInfo['is_quick_search'])?$appInfo['is_quick_search']:0;
				
				if( !$Node->add($data) )
				{
					return array( false , "保存应用{$app}失败." );
				}
			}
			else
			{
				return array( false , "获取应用{$app}基本信息失败." );
			}
		}
		return array( true , 'ok' );
	}

	/*
	* 创建管理员帐号
	*
	* @param   array		$langs		: 语言包
	* @return  boolean      成功返回true，失败返回false
	*/
	function create_admin_passport($langs)
	{
		$admin_name			= $_REQUEST['admin_name'];			//帐号
		$admin_password		= $_REQUEST['admin_password'];		//密码
		$admin_password2	= $_REQUEST['admin_password2'];
		$admin_email		= $_REQUEST['admin_email'];			//邮箱

		if ($admin_name === '')
		{
			exit( '管理员帐号为空' );
		}

		if ($admin_password === '')
		{
			exit( '管理员密码为空');
		}

		if ( $admin_password != $admin_password2 )
		{
			exit( '二次密码不一致' );
		}

		/*
		if (!(strlen($admin_password) >= 6 && preg_match("/\d+/",$admin_password) && preg_match("/[a-zA-Z]+/",$admin_password)))
		{
			exit( $langs['js_languages']['password_invaild'] );
		}
		*/
		

		//自动登录之前,清空SESSION，防止未退出后台直接安装，出现菜单打不开的情况
		session_destroy();
		session_start();

		$data['nickname']		= '超级管理员';
		$data['account']		= $admin_name;
		$data['admin_status']		= 1;
		$data['password']		= md100($admin_password);
		$data['email']			= $admin_email;
		$data['create_time']	= time();
		$data['status']			= 1;
		$Admin					= M('Admin');
		$result					= $Admin->add($data);

		if( $result )
		{
			import ( 'ORG.Util.RBAC' );
			/*********** 自动登录 **********/
			$_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ]	= $result;
			$_SESSION['loginAdminName']				= '超级管理员';
			$_SESSION['loginAdminAccount']			= $admin_name;
			$_SESSION['loginIp'][]			        = get_client_ip();
			$_SESSION[C('RBAC_SUPER_ADMIN_KEY')] = true;

			// 缓存访问权限
			RBAC::saveAccessList();
			/*******************************/
		}
		return $result?true:false;
	}

	/**
	 * 创建应用配置文件
	 *
	 * @access  public
	 * @param   string      $app			所属应用
	 * @param   string      $config_list	配置列表
	 * @param   array       $langs			语言包
	 * @return  boolean     成功返回true，失败返回false
	 */
	function create_config_file($app,$config_list,&$langs)
	{
		
		$content = "<?php\n";
		$content .= '$_app_config = array('."\n";
		foreach( $config_list as $key=>$value )
		{	
			if( is_string($value) ) $value	= "'{$value}'";
			if( is_bool($value) ) $value = $value?'true':'false';
			if( is_array($value) ) $value = var_export($value,true);
			$content .= "\t'".$key."'\t\t=>\t".$value.",\n\n";
		}
		$content	.= ");\n\n";

		$content	.= '$_core_config = require ROOT_PATH.\'Admin/Conf/core_config.php\';'."\n\n";
		$content	.= '$_debug_config = require ROOT_PATH.\'Admin/Conf/debug.php\';'."\n\n";
		$content	.= 'return array_merge($_core_config,$_app_config,$_debug_config);'."\n\n";
		$content	.= '?>';

		$file_path	= ROOT_PATH."$app/Conf/config.php";
		$fp			= @fopen( $file_path, 'wb+');
		if (!$fp)
		{
			return array(false,$file_path.'无法打开应用配置文件');
		}
		if (!@fwrite($fp, trim($content)))
		{
			return array(false,$file_path.'无法写入应用配置文件');
		}
		@fclose($fp);

		return array(true,'ok');
	}


	/**
	 * 创建指定名字的数据库
	 *
	 * @access  public
	 * @param   string      $db_host        主机
	 * @param   string      $db_port        端口号
	 * @param   string      $db_user        用户名
	 * @param   string      $db_pass        密码
	 * @param   string      $db_name        数据库名
	 * @return  boolean     成功返回true，失败返回false
	 */
	public function create_database($db_host, $db_port, $db_user, $db_pass, $db_name)
	{
		$db_host	= $this->construct_db_host($db_host, $db_port);
		$conn		= @mysql_connect($db_host, $db_user, $db_pass);

		if ($conn === false)
		{
			return array(false,'数据库连接失败');
		}

		$mysql_version = mysql_get_server_info($conn);
		$this->keep_right_conn($conn, $mysql_version);
		if (mysql_select_db($db_name, $conn) === false)
		{
			$sql = $mysql_version >= '4.1' ? "CREATE DATABASE $db_name DEFAULT CHARACTER SET utf8" : "CREATE DATABASE $db_name";
			if (mysql_query($sql, $conn) === false)
			{
				return array(false,'无法创建数据库');
			}
		}else{//备份
	        $backname = '重新安装前备份';
	        $backname = base64_encode($backname);
			
			$bktype = 0;//原始备份方式
			//判断网站和数据库是否在同一台服务器上
			$dbhost = strtolower(C('DB_HOST'));
			if($dbhost=='localhost' || $dbhost=='127.0.0.1'){
				//判断用户不是root用户
				if(strtolower(C('DB_USER'))=='root'){
					$bktype = 1;
				}else{//判断当前非root用户是否有FILE权限
					$grant = M()->query("show grants");
					if($grant){
						foreach($grant as $val){
							$gt = current($val);
							if(stripos($gt,'FILE')!==false && stripos($gt,'ON *.*')!==false ){
								$bktype = 1;
							}
						}
					}
				}
			}
			if($bktype==1){
				import("COM.BakRec.BakRec");
				$BakRec = new BakRec(realpath("dbbackup/"));
				$fileName = $BakRec->trimPath($BakRec->config['path'] . md5(date('YmdHis')) . 'B' . $backname. '.zip');
			}else{
				import("COM.BakRec.BackRec");
				$BakRec = new BackRec();
				$fileName = $BakRec->trimPath($BakRec->config['path'] . md5(date('YmdHis')) . 'A' . $backname. '.zip');
			}
			$tables = $BakRec->getTables();
			$mess = $BakRec->backup($tables,$fileName);
			//删除奖金构成文件
			$BakRec->remove_directory(ROOT_PATH.'DmsAdmin/PrizeData/',false);
			
		}
		@mysql_close($conn);

	   return array(true,'ok');
	}

	/**
	 * 获得数据库列表
	 *
	 * @access  public
	 * @param   string      $db_host        主机
	 * @param   string      $db_port        端口号
	 * @param   string      $db_user        用户名
	 * @param   string      $db_pass        密码
	 * @return  mixed       成功返回数据库列表组成的数组，失败返回false
	 */
	public function get_db_list($db_host, $db_port, $db_user, $db_pass)
	{
		$databases	= array();
		$filter_dbs = array('information_schema', 'mysql');
		$db_host	= $this->construct_db_host($db_host, $db_port);
		$conn		= @mysql_connect($db_host, $db_user, $db_pass);

		if ($conn === false)
		{
			return array(false,'连接数据库失败');
		}
		$this->keep_right_conn($conn);

		$result = mysql_query('SHOW DATABASES', $conn);
		
		if ($result !== false)
		{
			while (($row = mysql_fetch_assoc($result)) !== false)
			{
				if (in_array($row['Database'], $filter_dbs))
				{
					continue;
				}
				$databases[] = $row['Database'];
			}
		}
		else
		{
			return array(false,'获取数据库列表失败');
		}
		@mysql_close($conn);

		return array(true,$databases);
	}

	/**
	 * 保证进行正确的数据库连接（如字符集设置）
	 *
	 * @access  public
	 * @param   string      $conn                      数据库连接
	 * @param   string      $mysql_version        mysql版本号
	 * @return  void
	 */
	public function keep_right_conn($conn, $mysql_version='')
	{
		if ($mysql_version === '')
		{
			$mysql_version = mysql_get_server_info($conn);
		}

		if ($mysql_version >= '4.1')
		{
			mysql_query('SET character_set_connection=utf8 character_set_results=utf8, character_set_client=binary', $conn);

			if ($mysql_version > '5.0.1')
			{
				mysql_query("SET sql_mode=''", $conn);
			}
		}
	}

	/**
	 * 安装数据
	 *
	 * @access  public
	 * @param   array         $sql_str       sql语句字符串
	 * @return  boolean       成功返回true，失败返回false
	 */
	public function install_data($sql_str,$app)
	{
		include_once(COMMON_PATH . 'includes/cls_mysql.php');
		include_once(COMMON_PATH . 'includes/cls_sql_executor.php');

		$config = include( ROOT_PATH."$app/Conf/config.php");

		$db = new cls_mysql($config['DB_HOST'].':'.$config['DB_PORT'], $config['DB_USER'], $config['DB_PWD'], $config['DB_NAME'],'utf8');
		
		$se = new sql_executor($db, 'utf8', $config['DB_PREFIX'], $config['DB_PREFIX']);
		
		$result = $se->run_all($sql_str);
		if ($result !== true )
		{
			return $result;
		}

		return true;
	}

	/**
	 * 把host、port重组成指定的串
	 *
	 * @access  public
	 * @param   string      $db_host        主机
	 * @param   string      $db_port        端口号
	 * @return  string      host、port重组后的串，形如host:port
	 */
	public function construct_db_host($db_host, $db_port)
	{
		return $db_host . ':' . $db_port;
	}

	/**
	 * 创建核心配置文件
	 *
	 * @access  public
	 * @param   string      $db_host        主机
	 * @param   string      $db_port        端口号
	 * @param   string      $db_user        用户名
	 * @param   string      $db_pass        密码
	 * @param   string      $db_name        数据库名
	 * @param   string      $config_list	配置列表
	 * @param   string      $timezone       时区
	 * @return  boolean     成功返回true，失败返回false
	 */
	function create_core_config($db_host, $db_port, $db_user, $db_pass, $db_name, $timezone,$super_admin,&$langs)
	{
$config_content= <<<EOT
<?php
return array(
		'DB_HOST'					=>	'$db_host',
		'DB_NAME'					=>	'$db_name',
		'DB_USER'					=>	'$db_user',
		'DB_PWD'					=>	'$db_pass',
		'DB_PORT'					=>	'$db_port',
		'DB_TYPE'					=>	'mysql',
	
		'URL_MODEL'                 =>  3,					// 如果你的环境不支持PATHINFO 请设置为3
		'DB_LIKE_FIELDS'            =>  'title|remark',

		'SESSION_AUTO_START'        =>  true,
		'TMPL_ACTION_ERROR'         =>  'Public:success',	// 默认错误跳转对应的模板文件
		'TMPL_ACTION_SUCCESS'       =>  'Public:success',	// 默认成功跳转对应的模板文件
		'TOKEN_ON'					=>  false,				// 是否开启令牌验证
		
		
		'DEFAULT_TIMEZONE'			=>  '{$timezone}',		//默认时区

		'RBAC_ADMIN_AUTH_ON'        =>  true,				// RBAC启用管理员后台管理验证
		'RBAC_ADMIN_AUTH_GATEWAY'	=>  '/Public/login',	// RBAC后台管理员认证网关
		'RBAC_ADMIN_AUTH_TYPE'		=>  1,					// RBAC后台管理,验证方式   1:SESSION认证(性能好)   2:实时认证(性能差)
		'RBAC_ADMIN_AUTH_TABLE'     =>  'admin',			// RBAC后台管理,验证数据表模型
		'RBAC_ADMIN_AUTH_KEY'		=>  'adminId',			// RBAC后台管理,保存管理员验证的字段名


		'RBAC_USER_AUTH_ON'			=>  true,				// RBAC启用会员后台管理验证
		'RBAC_USER_AUTH_GATEWAY'	=>  '/Public/login',	// RBAC会员后台认证网关
		'RBAC_USER_AUTH_TYPE'		=>  1,					// RBAC会员后台管理,验证方式   1:SESSION认证(性能好)   2:实时认证(性能差)
		'RBAC_USER_AUTH_TABLE'		=>  'user',				// RBAC会员后台管理,验证数据表模型
		'RBAC_USER_AUTH_KEY'		=>  'userId',			// RBAC会员后台管理,保存会员验证的字段名

		'RBAC_NOT_AUTH_MODULE'      =>  'Public,Tle',			// RBAC默认无需认证模块
		'RBAC_NOT_AUTH_ACTION'      =>  'getfilecheck',		// RBAC默认无需认证操作
		'RBAC_REQUIRE_AUTH_MODULE'  =>  '',					// RBAC默认需要认证模块
		'RBAC_REQUIRE_AUTH_ACTION'  =>  '',					// RBAC默认需要认证操作

		'RBAC_SUPER_ADMIN_KEY'		=>	'__super_admin',	// RBAC后台管理,超级管理员SESSION标识
	    'RBAC_SUPER_ADMIN_ACCOUNT'	=>	'{$super_admin}',	// 超级管理员帐号名称
	    'RBAC_SUPER_STATUS'	=>	'1',						// 超级管理员帐号名称

		'RBAC_NODE_TABLE'			=>  'node',				// RBAC节点表
		'RBAC_ROLE_TABLE'           =>  'role',				// RBAC角色表
		'RBAC_ROLE_ADMIN_TABLE'     =>  'role_admin',		// RBAC角色管理员关联表
		'RBAC_ROLE_USER_TABLE'      =>  'role_user',		// RBAC角色会员关联表
		'RBAC_ROLE_ACCESS_TABLE'	=>  'role_access',		// RBAC角色授权表
		'RBAC_ADMIN_ACCESS_TABLE'	=>	'admin_access',		// RBAC管理员授权表
		//'SESSION_TYPE'              =>  'Db',
		'SESSION_TABLE'             =>  'session',
		'SESSION_EXPIRE'            =>  1800,
		'SHOW_PAGE_TRACE'           =>  0 ,					//显示调试信息

		'VERSION_SWITCH'			=>  '0',			//版本切换用于切换母程序简化版和完整版之间的切换。1:简化版，0：完整版。
		'decimalLon'				=>	14,				//决定double类型字段的整个长度
		'decimalLen'				=>	2,				//决定double类型字段的小数点长度
);
?>
EOT;
		$fp			= @fopen( ROOT_PATH."Admin/Conf/core_config.php", 'wb+');
		if (!$fp)
		{
			return array(false,$file_path.'无法打开核心配置文件');
		}
		if (!@fwrite($fp, trim($config_content)))
		{
			return array(false,$file_path.'无法写入核心配置文件');
		}
		@fclose($fp);

		return array(true,'ok');
	}
}
?>