<?php
/*
*  һ����ʱ������ͨ��workerman��ʱ��ͻ��˷�������
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
    	//self::log()l
    	exec('php '.dirname(__FILE__).'/../../../../../../cli.php Admin Cal settlementExecute',$ret);
    }
}