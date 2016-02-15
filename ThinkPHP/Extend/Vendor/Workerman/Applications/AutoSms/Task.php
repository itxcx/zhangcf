<?php
/*
*  一个定时任务，向通过workerman定时向客户端发送数据
*/
use \Workerman\Worker;
use \Workerman\Lib\Timer;
use \Workerman\Protocols\GatewayProtocol;
use \GatewayWorker\Lib\Lock;
use \GatewayWorker\Lib\Store;
use \Workerman\Autoloader;
class Task extends Worker
{
	public $onWorkerStart = null;
    public function run()
    {
    	$this->onWorkerStart = array($this, 'onWorkerStart');
        parent::run();
    }
    public function onWorkerStart()
    {
    	Timer::add(2,array($this,'tack'));
    }
    public function tack()
    {
    	exec('php '.dirname(__FILE__).'/../../../../../../cli.php Api Smsapi index',$ret);
    }
}