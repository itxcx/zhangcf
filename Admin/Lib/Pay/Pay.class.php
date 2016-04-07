<?php
/*
* 支付处理核心类
*/
class Pay
{
	//支付接口对象
	private $payment;
	//支付订单编号
	private $_orderId;
	//支付金额
	private $_money;
	//回调函数
	private $_callback;
	//账户类型
	private $type;
	//构造函数,根据参数构造特定的支付类
	/*
	* payname	: 支付方式
	* init		: 是否进行初始化
	* money		: 支付金额
	*/
	public function __construct($payname = NULL,$init = true,$money = 0,$type = '',$memo = '')
    {
		//支付金额
		$this->_money	= $money;
		//账户类型
		$this->type		= $type;
		//产生订单号
		$this->_orderId = $this->createOrderId();
		//直连的银行
		$bank			= '';  
		//判断是否使用直连
		if( !strpos($payname,':') === false )
		{
			$payment_array	= explode(':',$payname);
			$payname		= $payment_array[0];
			$bank			= $payment_array[1];
		}
    	//存在支付类型,但是不存在订单号
    	if( $payname && Pay::checkPayName($payname) )
		{
			import("Admin.Pay.$payname");
        	//实例化支付类
        	$this->payment=new $payname;
			$this->payment->setMoney($money);
			$this->payment->setOrderId($this->_orderId);
			$siteDomain	= $this->getSiteDomain();
			$this->payment->setServerurl($siteDomain);
			if( $this->isSupportCredit() && $bank != '' )
			{
				$this->setCreditBank($bank);
			}
			//生成订单
			if( $init )
				$this->createOrder($this->_orderId,$money,$payname,$memo);
        }
    }

	//接收服务器支付结果返回
	public function receive($orderId)
	{
		$payResult			= $this->payment->receive();
		if( $payResult )
		{
			//$this->printMessage($this->payment->getMessage());
			//支付成功
			$this->touchEvent('success',$orderId);
			return true;
		}
		else
		{
			//$this->printMessage($this->payment->getMessage());
			//支付失败
			//$this->touchEvent('fail',$orderId);
			return false;
		}
	}
	//执行事件调用
	public function touchEvent($event,$orderId)
	{
		//检查该订单是否已绑定事件
		$Model				= M();
		$where['orderid']	= $orderId;
		$where['event']		= $event;
		$info				= $Model->table('pay_event')->where($where)->find();
		$orderInfo			= $Model->table('pay_order')->where("orderId='{$orderId}'")->find();
		//如果找到了绑定的事件
		if( $info )
		{
			//初始化事件不进行状态更新
			if( $event=='init' || $orderInfo['status'] == 0 )
			{
				//初始化事件不进行状态更新
				if( $event!='init' )
					$Model->table('pay_order')->where("orderId='{$orderId}'")->setField('status',1);
				$app			= $info['app'];
				$group			= $info['group'];
				$model			= $info['model'];
				$method			= $info['method'];
				$args			= (array)json_decode($info['args']); //强制转换为数组格式
				//得到事件模型类
				$eventModel		= D("{$app}://{$model}");
				call_user_func( array($eventModel, $method)  , $orderId , $args );
			}
		}
	}
	//创建订单
	public function createOrder($orderId,$money,$payment,$memo)
	{
		//创建之前判断订单是否已存在
		$Model					= M();
		$where['orderId']		= $orderId;
		if( !$Model->table('pay_order')->where($where)->find() )
		{
			$data['orderId']		= $orderId;
			$data['money']			= $money;
			$data['realmoney']		= $money;
			//是否开启转换
			$bank=X('fun_bank@'.$this->type);
			if($bank->bank_scale!=1){
				$data['realmoney']			= (float)($money/$bank->bank_scale);
			}
			$data['payment_class']	= $payment;
			$data['payment']		= $this->isSupportCredit()?$this->getPayName().':'.$this->getCreditBankName():$this->getPayName();
			$data['create_time']	= time();
			$data['status']			= 0;
			$data['memo']			= $memo;
			$data['userid']         = $_SESSION[C('USER_AUTH_NUM')];
			$data['username']       = $_SESSION['username'];
			$data['type']			= $this->type;
			$Model->table('pay_order')->add($data);
		}
	}
	
	//进行事件绑定,model,方法名,参数信息
    /*
	* 事件绑定
	* event : 事件  success成功，faild失败    
	* app :  应用名称
	* modelName : 模型类名
	* method : 模型类方法名
	* args : 参数
	*

		$events		= array(

			'success' => array(
				'app'		=> 'Admin',
				'group'		=> '',
				'model'		=> 'PayResult',
				'method'	=> 'success',
				'args'		=> array(
					'memo'	=> '测试提交成功'
				),
			),

			'fail' => array(
				'app'		=> 'Admin',
				'group'		=> '',
				'model'		=> 'PayResult',
				'method'	=> 'fail',
				'args'		=> array(
					'memo'	=> '测试提交失败'
				),
			)

		);
	*/
    public function bind($events)
    {
    
		$Model				= M();
		foreach($events as $event=>$eventData)
		{
			//将事件绑定存到表中,等待支付完成时,回调
			$data['orderid']		= $this->_orderId;
			$data['event']			= $event;
			$data['app']			= $eventData['app'];
			$data['group']			= $eventData['group'];
			$data['model']			= $eventData['model'];
			$data['method']			= $eventData['method'];
			$data['args']			= json_encode($eventData['args']);
			$data['create_time']	= time();
			//dump($data);
			$Model->table('pay_event')->add($data);
			//检查是否定义初始化方式回调
			if( $event == 'init' )
			{
				$this->touchEvent($event,$this->_orderId);
			}
		}
	}

	//检测当前支付接口是否支持直连
	public function isSupportCredit()
	{
		return $this->payment->isSupportCredit();
	}

	//设置直连的银行
	public function setCreditBank($bank)
	{
		$this->payment->setCreditBank($bank);
	}

	//获取直连的银行的名称
	public function getCreditBankName()
	{
		return $this->payment->getCreditBankName();
	}

	//获取支付接口的中文名称
	public function getPayName()
	{
		return $this->payment->getName();
	}

	//支付接口的信息
	public static function getPayInfo($payment)
	{
		//获取类名
		import("Admin.Pay.$payment");
		$payment_ob			= new $payment();
		$result				= array();
		$result["name"]		= $payment::getName();
		$result["order_key"]= $payment::$pay_interface['order_key'];
		//$result["config"]	= $payment::getConfigInfo();
		//if( $payment_ob->isSupportCredit()){		//是否直连
		//	$result["banklist"]	= call_user_func("$payment::getBankList");
		//}
		//取出支付类中的名称和键名
		return $result;
	}

	//检查支付接口是否已经安装
	public static function checkPayment($payment)
	{
		if(CONFIG('Admin',$payment.'_installed') == $payment)
		{
			return 1;
		}
		return 0;
	}

	//获取已安装的支付类
	public static function getPayList()
	{
		$payList		= Pay::getPayClassList();
		$payListInfo	= array();
		$Model			= M();
		foreach( $payList as $key=>$pay)
		{
			if( Pay::checkPayment($pay) == 1 )
			{
				$payListInfo[$pay]	= Pay::getPayInfo($pay);
			}
		}
		return $payListInfo;
	}

	//获取可用的支付接口类(对于PayAction已经废弃)
	public static function getPayClassList()
	{
		$file_path	= ROOT_PATH.'Admin/Lib/Pay';
		$handle		= opendir($file_path);
		$file_list	= array();
		//取出有效的支付类
		while (($_file = readdir($handle)) !== false)
		{
			$_ignore = array('.' , '..' , '.svn' , 'Pay.class.php' );
			if( in_array($_file,$_ignore) )
			{
				continue;
			}
			//获取类名
			$_class_name	= explode('.',$_file);
			$class_name		= $_class_name[0];
			$file_list[]	= $class_name;
		}
		return $file_list;
	}

	//判断是否为合法的支付类型
	public static function checkPayName($payname)
	{
		if( in_array($payname,Pay::getPayClassList()) )
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/*
	* 测试性提交
	* payResult : true | false   支付结果
	*/
    public function testSubmit($payResult=true)
    {
		if( $payResult )
		{
			//支付成功
			$this->touchEvent('success',$this->_orderId);
			$this->printMessage("支付成功!(测试提交)");
		}
		else
		{
			//支付失败
			$this->touchEvent('fail',$this->_orderId);
			$this->printMessage("支付失败!(测试提交)");
		}
	}

    //提交表单
    public function submit()
    {
		$this->payment->submit();
		exit;
    }

    //取得支付订单号
    public function getOrderId()
    {
    	return $this->_orderId;
    }

    //产生新支付订单编号
    private function createOrderId()
    {
    	return date('YmdHis').rand(100000,999999);
    }

	//获取当前的域名带 协议
	private function getSiteDomain()
	{
		$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
		return $http_type.$_SERVER['HTTP_HOST'];
	}

	private function printMessage($msg)
	{
		echo '<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>'.$msg.'</body></html>';
	}

	//增删改操作重写安装支付缓存
	public static function iud(){
		$a=M()->query('select a.id,a.pay_type,a.name,a.credit from pay_onlineaccount a inner join (select min(`id`)as id1  from pay_onlineaccount WHERE `state`=1 group by `pay_type`) b on a.id=b.id1');
		$b=array();
		if(!empty($a)){
			foreach($a as $key=>&$value){
				if(strtolower($value['credit'])==strtolower('Yes')){
					import($this->interface_data['app'].".Pay.".$value['pay_type']);
					$va=$value['pay_type']::getBankList();
					$banklist=F('banklist');
					$value['banklist']=array_flip(array_intersect(array_flip($va),$banklist[$value['pay_type']]));
				}
				$b[$value['pay_type']]=$a[$key];
			}
		}
		F('installedPayment',$b);
		CONFIG('INPAYMD5',MD5(json_encode($b)));
	}
}
?>