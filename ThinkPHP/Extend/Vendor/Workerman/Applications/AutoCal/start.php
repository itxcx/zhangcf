<?php 
use \Workerman\Worker;
use \Workerman\Autoloader;
use \Applications\AutoCal;
// 自动加载类
require_once __DIR__ . '/../../Workerman/Autoloader.php';
Autoloader::setRootPath(__DIR__);
$task =new Task();
$task->name = 'AutoCal';
$task->count = 1;
// gateway 进程
//$gateway = new Gateway("Websocket://0.0.0.0:8585");
// 名称，以便status时查看方便
//$gateway->name = 'TodpoleGateway';
// 开启的进程数，建议与cpu核数相同
//$gateway->count = 4;


// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
	
    Worker::runAll();
}
