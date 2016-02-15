<?php
$DEFAULT_THEME=CONFIG('DEFAULT_THEME')?CONFIG('DEFAULT_THEME'):"blanc_default";
if(is_mobile_request())
{
	$DEFAULT_THEME='wap';
}
$_app_config = array(
	'APP_GROUP_LIST'		=>	'Admin,User',
	'DEFAULT_GROUP'			=>	'Admin',
	'DB_PREFIX'				=>'dms_',
	'USER_AUTH_KEY'			=>'userid',//保存登陆用户的ID
	'SAFE_PWD'				=>'repass',//保存登陆用户的编号
	'USER_AUTH_NUM'			=>'useriral',
	//'DEFAULT_THEME'			=> CONFIG('DEFAULT_THEME')?CONFIG('DEFAULT_THEME'):"default_sj",
	'DEFAULT_THEME'			=> $DEFAULT_THEME,
	//'配置项'=>'配置值'  发邮件 
	 'MAIL_Port'=>'25', // 端口
	//多语言
	
	'LANG_SWITCH_ON'		=>	true,
	'My_LANG_SWITCH_ON'		=>	true,
	
	//表单令牌验证
	 'TOKEN_ON'=>true,  // 是否开启令牌验证
	 'TOKEN_NAME'=>'__hash__',    // 令牌验证的表单隐藏字段名称
	 'TOKEN_TYPE'=>'md5',  //令牌哈希验证规则 默认为MD5
	 'TOKEN_RESET'=>false,  //令牌验证出错后是否重置令牌 默认为true
);

$_core_config = require ROOT_PATH.'Admin/Conf/core_config.php';

return array_merge($_core_config,$_app_config);

?>