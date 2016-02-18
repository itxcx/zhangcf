<?php
$_app_config = array(
	'APP_GROUP_LIST'		=>	'Admin,User,Api,Check',

	'DEFAULT_GROUP'		=>	'Admin',

	'LANG_SWITCH_ON'		=>	true,

	'My_LANG_SWITCH_ON'		=>	false,

	'DB_PREFIX'		=>	'dms_',

    'LOAD_EXT_CONFIG'=>'core_config,debug',//拓展配置文件名称

    'LOAD_EXT_CONFIG_PATH'=>ROOT_PATH.'Admin/Conf/',//拓展配置文件地址

);

return $_app_config;

?>