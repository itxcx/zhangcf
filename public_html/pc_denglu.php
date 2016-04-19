<?php    //加载框架入口文件
header('Content-type:text/html;Charset=utf-8');
if(substr($_SERVER["REMOTE_ADDR"],0,8) != '192.168.' && substr($_SERVER["REMOTE_ADDR"],0,4) != '127.' && substr($_SERVER["REMOTE_ADDR"],0,3) != '10.'){
	if($_SERVER['HTTPS'] != 'on' && $_SERVER['HTTP_X_FORWARDED_PROTO'] != 'https'){
		die('不显示登录口');
	}
}
ini_set('display_errors','on');
require '../function.php';
define('APP_NAME', 'Admin');
define('APP_PATH', '../Admin/');
//设置DEBUG模式
$debugstate = require '../Admin/Conf/debug.php';
define('APP_DEBUG'    , $debugstate['APP_DEBUG']);
require '../ThinkPHP/ThinkPHP.php'; 
?>
