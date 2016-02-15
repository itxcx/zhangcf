<?php
$_app_config = array(
);

$_core_config = require ROOT_PATH.'Admin/Conf/core_config.php';

$_debug_config = require ROOT_PATH.'Admin/Conf/debug.php';

return array_merge($_core_config,$_app_config,$_debug_config);

?>