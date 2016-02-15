<?php
/**
 * run with command 
 * php start.php start
 */

ini_set('display_errors', 'on');
use Workerman\Worker;

// 检查扩展
if(!extension_loaded('pcntl'))
{
    exit("Please install pcntl extension. See http://doc3.workerman.net/install/install.html\n");
}

if(!extension_loaded('posix'))
{
    exit("Please install posix extension. See http://doc3.workerman.net/install/install.html\n");
}

// 标记是全局启动
define('GLOBAL_START', 1);

require_once __DIR__ . '/Workerman/Autoloader.php';
//判断开启的进程
$children=array();
$children=array_merge($children,glob(__DIR__.'/Applications/'.$argv[2].'/start.php'));
// 加载所有Applications/*/start.php，以便启动所有服务
foreach($children as $start_file)
{
    require_once $start_file;
}
// 运行所有服务
Worker::$stdoutFile = '../DmsAdmin/Runtime/Logs/workerstdout.log';
Worker::$logFile    = '../DmsAdmin/Runtime/Logs/workerlog.log';
Worker::runAll();

//ps -ef | grep php-fpm | grep -v root | awk '{print $2}' | xargs kill -9