<?php    //加载框架入口文件
ini_set('display_errors','on');
require '../function.php';
define('APP_NAME', 'Admin');
define('APP_PATH', '../Admin/');
//设置DEBUG模式
$debugstate = require '../Admin/Conf/debug.php';
define('APP_DEBUG'    , $debugstate['APP_DEBUG']);
require '../ThinkPHP/ThinkPHP.php'; 
?>
