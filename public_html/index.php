<?php
ini_set('display_errors','On');
require '../function.php';
//设置项目信息
define('APP_NAME', 'DmsAdmin');
define('APP_PATH', '../DmsAdmin/');
//设置DEBUG模式
$debugstate = require '../Admin/Conf/debug.php';
define('APP_DEBUG'    , $debugstate['APP_DEBUG']);
//做推广注册处理
if($_GET && key($_GET)!=='s')
{
	$_GET['s']='/User/Saleweb/usereg/rec/'.key($_GET);
}
//做首页登入跳转
if(!$_GET)
{
	$_GET['s']='/User/Public/login'; 
}
require '../ThinkPHP/ThinkPHP.php';
?>