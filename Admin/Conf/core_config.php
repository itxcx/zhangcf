<?php
return array(
		'DB_HOST'					=>	'127.0.0.1',
		'DB_NAME'					=>	'waibao',
		'DB_USER'					=>	'root',
		'DB_PWD'					=>	'',
		'DB_PORT'					=>	'3306',
		'DB_TYPE'					=>	'mysql',
	
		'URL_MODEL'                 =>  3,					// 如果你的环境不支持PATHINFO 请设置为3
		'DB_LIKE_FIELDS'            =>  'title|remark',

		'SESSION_AUTO_START'        =>  true,
		'TMPL_ACTION_ERROR'         =>  'Public:success',	// 默认错误跳转对应的模板文件
		'TMPL_ACTION_SUCCESS'       =>  'Public:success',	// 默认成功跳转对应的模板文件
		'TOKEN_ON'					=>  false,				// 是否开启令牌验证
		
		
		'DEFAULT_TIMEZONE'			=>  'Asia/Shanghai',		//默认时区

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
	    'RBAC_SUPER_ADMIN_ACCOUNT'	=>	'admin',	// 超级管理员帐号名称
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