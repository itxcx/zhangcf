<?php
$_app_config = array(
	'APP_GROUP_LIST'		=>	'Admin,User,Api,Check',

	'DEFAULT_GROUP'		=>	'Admin',

	'LANG_SWITCH_ON'		=>	true,

	'My_LANG_SWITCH_ON'		=>	false,

	'DB_PREFIX'		=>	'dms_',

);

$_core_config = require ROOT_PATH.'Admin/Conf/core_config.php';

$_debug_config = require ROOT_PATH.'Admin/Conf/debug.php';

return array_merge($_core_config,$_app_config,$_debug_config);

?>