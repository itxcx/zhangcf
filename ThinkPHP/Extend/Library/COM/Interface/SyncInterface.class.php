<?php
/*
* 应用同步接口
*/
interface SyncInterface 
{
	/*
	* 返回应用下的菜单列表
	*
	* 数据格式如下：
	*

	return array(
		'菜单组名称'=>array(
			'childs'=>array(
				'菜单名称'=>array(
					'childs'=>array(
						'子菜单名称'=>array(
							'childs'=>array(
								'菜单1' =>array(
									'url'	=> '/admin.php?s=/Admin/index',
									'target'=> 'dialog',[可选]
									'rel'	= 'xxxxx',[可选]
									'attrs'	=> array('mask'=>'true','width'=>'550','height'=>'330'),[可选]
								),
								//[.....] 可以无限向下延伸
							),
						),
						'子菜单名称'=>array(
							'url'	=> '/admin.php?s=/Admin/index',
							'args'	=> array('name'=>'管理员'),[可选]
							'rel'	= 'xxxxx',[可选]
							'target'=> 'dialog',[可选]
							'attrs'	=> array('mask'=>'true','width'=>'550','height'=>'330'),[可选]
						),
						//[......] 更多的子菜单
					),
				),

				或

				'菜单名称'=>'链接地址',
			),
		),
		//[......] 更多的菜单组
	);

	*
	*/
	public function returnMenuList();

	/*
	*
	* 返回应用下的节点列表
	*
	* 数据格式如下：
	*
	*

	return array(
		'模块名称'=>array(
			'module'	=>'模块类名',	//如 AdminAction.class.php  只需要填写  Admin
			'args'		=>array('args'=>'/con/user0'), //支持参数传递,会被base64_encode(json_encode('xxx'))
			'sort'		=>1,			//排序值
			'childs'=>array(
				'会员查询'=>array('action'=>'find'),
				'修改产品'=>array('action'=>'edit'),
				'删除产品'=>array('action'=>'delete'),
				//[......] 更多的方法
			),
		),
		//[......] 更多的模块
	);

	*/
	public function returnNodeList();


	/*
	* 返回应用对环境的检查结果
	*
	*
	return array(
		0 => array( 'PHP版本'					, '5.3.1' ),
		1 => array( 'Mysql版本'					, '5.5' ),
		2 => array( 'GD版本'					, '2' ),
		3 => array( '是否支持Mysql'				, '不支持' , false ),
		4 => array( '是否支持JPEG'				, '不支持' , false),
		5 => array( '是否支持GIF'				, '支持' ),
		6 => array( '是否支持PNG'				, '支持' ),
		7 => array( '服务器是否开启安全模式'	, '关闭' ),
	);
	*/
	public function checkEnviro();

	/*
	* 返回应用对目录的检查结果
	*
	*
	return array(
		0 => array( 'Admin/Runtime/Cache'		, '可写' ),
		1 => array( 'Admin/Runtime/Data'		, '可写' ),
		2 => array( 'Admin/Runtime/Logs'		, '可写' ),
		3 => array( 'Admin/Runtime/Temp'		, '不可写' ),
	);
	*/
	public function checkDir();


	/*
	* 返回应用安装时要生成配置项
	*
	return array(
		'URL_MODEL'                 =>  3, // 如果你的环境不支持PATHINFO 请设置为3
		'DB_TYPE'                   =>  'mysql',
		'DB_PREFIX'                 =>  '',
		'SESSION_AUTO_START'        =>  true,
		'TMPL_ACTION_ERROR'         =>  'Public:success',	// 默认错误跳转对应的模板文件
		'TMPL_ACTION_SUCCESS'       =>  'Public:success',	// 默认成功跳转对应的模板文件
		'TOKEN_ON'					=>  false,				// 是否开启令牌验证
		
		'DB_LIKE_FIELDS'            =>  'title|remark',

		'RBAC_ADMIN_AUTH_ON'        =>  true,				// RBAC启用后台管理验证
		'RBAC_ADMIN_AUTH_GATEWAY'	=>  '/Public/login',	// RBAC后台管理员认证网关
		'RBAC_ADMIN_AUTH_TYPE'		=>  1,					// RBAC后台管理,验证方式   1:SESSION认证(性能好)   2:实时认证(性能差)
		'RBAC_ADMIN_AUTH_TABLE'     =>  'admin',			// RBAC后台管理,验证数据表模型
		'RBAC_ADMIN_AUTH_KEY'		=>  'adminId',			// RBAC后台管理,保存管理员验证的字段名

		'RBAC_NOT_AUTH_MODULE'      =>  'Public',			// RBAC默认无需认证模块
		'RBAC_NOT_AUTH_ACTION'      =>  '',					// RBAC默认无需认证操作
		'RBAC_REQUIRE_AUTH_MODULE'  =>  '',					// RBAC默认需要认证模块
		'RBAC_REQUIRE_AUTH_ACTION'  =>  '',					// RBAC默认需要认证操作

		'RBAC_SUPER_ADMIN_KEY'		=>	'__super_admin',	// RBAC后台管理,超级管理员SESSION标识
		'RBAC_SUPER_ADMIN_ACCOUNT'	=>  'admin',			// RBAC后台管理,超级管理员帐号
	   

		'RBAC_NODE_TABLE'			=>  'node',				// RBAC节点表
		'RBAC_ROLE_TABLE'           =>  'role',				// RBAC角色表
		'RBAC_ROLE_USER_TABLE'      =>  'role_admin',		// RBAC角色管理员关联表
		'RBAC_ROLE_ACCESS_TABLE'	=>  'role_access',		// RBAC角色授权表
		'RBAC_ADMIN_ACCESS_TABLE'	=>	'admin_access',		// RBAC管理员授权表
		

		'SHOW_PAGE_TRACE'           =>  0 ,					//显示调试信息
	);
	*/
	public function returnConfigList();

	
	/*
	* 返回应用要创建的基础数据
	* sql语句,每条之间用 ; 隔开
	*
	*/
	public function returnSqlStr();


	/*
	* 返回应用的相关信息
	*
	return array(
		'is_sync_node'	=> '1',	//是否同步节点数据
		'is_sync_menu'	=> '1',	//是否同步菜单数据
		'title'			=> '应用中心',	//应用名称
	);
	*/
	public function returnApplicationInfo();
}
?>