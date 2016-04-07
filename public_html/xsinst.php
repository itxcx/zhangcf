<?php    //加载框架入口文件
header("Content-type:text/html;charset=utf-8");
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('当前php版本小于5.3！请使用5.3及以上版本');
require '../function.php';
ini_set('display_errors','On');
define('APP_NAME', 'Install');
define('APP_PATH', '../Install/');
define('APP_DEBUG', true);
require '../ThinkPHP/ThinkPHP.php';
?>