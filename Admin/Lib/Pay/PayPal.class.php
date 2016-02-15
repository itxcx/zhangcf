<?php
import("COM.Interface.PayInterface");

/*
* 贝宝 支付类
*
*/

class PayPal implements PayInterface{
	
	//接口对接静态信息
	public static $pay_interface=array(
								//支付接口中文名
								'pay_cname'=>'贝宝支付',
								//支付接口英文名
								'pay_ename'=>'PayPal',
								//支付接口简介
								'synopsis'=>'PayPal是美国eBay公司的全资子公司，倍受全球亿万用户追捧的国际贸易支付工具，即时支付，即时到账，全中文操作界面，能通过中国的本地银行轻松提现，为您解决外贸收款难题，助您成功开展海外业务，决胜全球。您注册PayPal后就可立即开始接受信用卡付款。作为世界第一的在线付款服务，PayPal是您向全世界超过2.2亿的用户敞开大门的最快捷的方式。',
								//支付接口版本
								'version'=>'1.0v',
								//所有支付接口，统一使用的异步接口(网站根目录)
								'return_url'=>'/Pay_return.php',
								//支付接口接收服务器返回值的订单KEY名
								'order_key'=>'CUSTOM'
								);
	
	//测试支付地址
	//public $pay_url			= 'https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=';
		
	//正式支付地址
	public $pay_url				= 'https://www.paypal.com/webscr&cmd=_express-checkout&token=';

	//测试API地址
	//public $api_url			= 'https://api-3t.sandbox.paypal.com/nvp';

	//正式API地址
	public $api_url				= 'https://api-3t.paypal.com/nvp';
	
	public $version				= '65.1';			//版本
	public $orderId				= 0;				//订单号
	public $amount				= 0;				//金额
	public $currency			= '';				//支付币种
	public $username			= '';				//api帐号
	public $password			= '';				//api密码
	public $signature			= '';				//api签名
	public $exchange_rate_usd	= '';				//美元对人民币汇率
	public $exchange_rate_euro	= '';				//欧元对人民币汇率
	public $company_name		= '';				//支付页面左上角显示的企业名称
	public $return_url			= '';				//支付结果浏览器通知URL,完整路径带http
	public $cancel_url			= '';				//取消支付后跳转的地址，完整路径带http
	public $sendMsg				= '在线充值的时候请不要关闭页面！充值成功后页面自动跳转..';	//发送充值时的提示
	public $payname		= '';

	/*
	* 构造函数
	*/
	function __construct() 
	{
			//读取接口表中的pay_type
		$arr = M('pay_onlineaccount',' ')->where(array('pay_type'=>'PayPal'))->order("pay_amount asc,id desc")->find();
		if($arr){
		//将$arr['pay_attr'] 返序列化
	    $data_arr = unserialize($arr['pay_attr']);//是一个二维数组 
	    //查询金额最小的金额的记录
	      $data = array();
		   foreach($data_arr as $key=>$v)
		   {
		         $data[$key] = $v;
		   }
		//读取数据库中的设置
		//币种
		$currency					= $data[self::$pay_interface['pay_ename'].'_currency'];
		$this->currency				= $currency?$currency:'';

		//api帐号
		$username					= $data[self::$pay_interface['pay_ename'].'_account'];
		$this->username				= $username?$username:'';

		//api密码
		$password					= $data[self::$pay_interface['pay_ename'].'_password'];
		$this->password				= $password?$password:'';

		//api签名
		$signature					= $data[self::$pay_interface['pay_ename'].'_key'];
		$this->signature			= $signature?$signature:'';

		//美元对人民币汇率
		$exchange_rate_usd			= $data[self::$pay_interface['pay_ename'].'_exchange_rate_usd'];
		$this->exchange_rate_usd	= $exchange_rate_usd?$exchange_rate_usd:'';

		//欧元对人民币汇率
		$exchange_rate_euro			= $data[self::$pay_interface['pay_ename'].'_exchange_rate_euro'];
		$this->exchange_rate_euro	= $exchange_rate_euro?$exchange_rate_euro:'';
		
		//企业名称
		$company_name				= $data[self::$pay_interface['pay_ename'].'_company'];
		$this->company_name			= $company_name?$company_name:'';
		}
	}


	//返回支付接口中文名称
	public static function getName()
	{
		return 'PayPal支付';
	}

	//返回接口中文介绍
	public static function getMemo()
	{
		return 'PayPal是eBay旗下的一家公司，致力于让个人或企业通过电子邮件，安全、简单、便捷地实现在线付款和收款。PayPal账户是PayPal公司推出的最安全的网络电子账户，使用它可有效降低网络欺诈的发生。PayPal账户所集成的高级管理功能，使您能轻松掌控每一笔交易详情。目前，在跨国交易中超过85%的卖家和买家认可并正在用PayPal电子支付业务。';
	}

	//返回需要配置的项
	public static function getConfigInfo()
	{
		return array(
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_name',
				'config_value'=> 'PayPal支付',
				'name'=>'支付方式名称',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_account',
				'config_value'=> '',
				'name'=>'API帐号',
				'type'=>'text',
				'style'=>'width:150px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_password',
				'config_value'=> '',
				'name'=>'API密码',
				'type'=>'text',
				'style'=>'width:150px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_key',
				'config_value'=> '',
				'name'=>'API签名',
				'type'=>'text',
				'style'=>'width:380px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_exchange_rate_usd',
				'config_value'=> '6.1865',
				'name'=>'美元汇率',
				'type'=>'text',
				'style'=>'width:100px',
				'memo'=>'1美元折合的人民币数量'
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_exchange_rate_euro',
				'config_value'=> '8.0889',
				'name'=>'欧元汇率',
				'type'=>'text',
				'style'=>'width:100px',
				'memo'=>'1欧元折合的人民币数量'
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_currency',  //货币类型
				'config_value'=> '0',
				'name'=>'货币类型',
				'type'=>'radio',
				'options'=>array(
					'USD'=>'美元',
					'EUR'=>'欧元',
				),
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_company',
				'config_value'=> '',
				'name'=>'企业名称',
				'memo'=>'显示在付款页面左上角',
				'type'=>'text',
				'style'=>'width:100px',
			),
			
		);
	}


	//提交表单
	public function submit()
	{
		$money			= $this->getExchangeRateMoney();  //人民币 对 当前货币汇率 的 金额
		$nvpstr			=  "&L_DESC0=充值:".$this->amount.' 人民币';		//产品名称
		$nvpstr			.=  "&L_NUMBER0=".$this->orderId;		//产品说明
		$nvpstr			.= '&L_AMT0='.$money;			//产品价格
		$nvpstr			.= '&L_QTY0=1';							//购买数量
		$nvpstr			.= '&AMT='.$money;				//客户需承担的交易总费用，如果运费和税金已知，需要包含在里面
		$nvpstr			.= '&ITEMAMT='.$money;			//订单所有产品的价格
		$nvpstr			.= '&CALLBACKTIMEOUT=1';				//回调延时，单位:秒
		$nvpstr			.= '&CUSTOM='.$this->orderId;			//订单ID
		$nvpstr			.= '&BRANDNAME='.$this->company_name;	//企业名称，显示在支付页面左上角

		$nvpstr			.= "&ReturnUrl=".$this->return_url;		//成功后跳转地址
		$nvpstr			.= "&CANCELURL=".$this->cancel_url;		//取消后跳转地址
		$nvpstr			.= "&CURRENCYCODE=".$this->currency;	//币种
		$nvpstr			.= "&PAYMENTACTION=Sale";				//支付方式 


		$resArray		= $this->send_request("SetExpressCheckout",$nvpstr);

		$ack			= strtoupper($resArray["ACK"]);

		if( $ack=="SUCCESS" )
		{
			// Redirect to paypal.com here
			$token		= urldecode($resArray["TOKEN"]);
			$payPalURL	= $this->pay_url.$token;
			header("Location: ".$payPalURL);
		} 
		else  
		{
			$this->print_msg($resArray);
		}
		exit;
	}


	/*
	* 处理收到的数据
	*/
	public function receive()
	{
		$token				= urlencode( $_REQUEST['token']);

		$nvpstr				= "&TOKEN=".$token;
						
		//获取paypal订单的详情
		$orderDetail		= $this->send_request("GetExpressCheckoutDetails",$nvpstr);

		$ack				= strtoupper($orderDetail["ACK"]);
		

		$orderId			= $orderDetail['CUSTOM']; //订单id

		//获取本地订单信息
		$Model				= M();
		$where['orderId']	= $orderId;
		$info				= $Model->table('pay_order')->where($where)->find();

		if( !$info )
		{
			$this->message = '支付订单无效!';
			return false;
		}
		if ( $info['status'] != 0 )
		{
			$this->message = '支付订单不可重复提交!';
			return false;
		}



		//如果paypal订单存在
		if($ack == 'SUCCESS' || $ack == 'SUCCESSWITHWARNING')
		{
			
			$paymentAmount		= $orderDetail['AMT'] + $orderDetail['SHIPDISCAMT'];
			$payerID			= urlencode($_REQUEST['PayerID']);
			$serverName			= urlencode($_SERVER['SERVER_NAME']);

			$nvpstr				='&TOKEN='.$token.'&PAYERID='.$payerID.'&PAYMENTACTION=Sale';
			$nvpstr				.= '&AMT='.$paymentAmount.'&CURRENCYCODE='.$this->currency.'&IPADDRESS='.$serverName ;
			
			//执行付款
			$resArray 			= $this->send_request("DoExpressCheckoutPayment",$nvpstr);


			$ack				= strtoupper($resArray ["ACK"]);

			//如果支付失败
			if($ack != 'SUCCESS' && $ack != 'SUCCESSWITHWARNING')
			{
				$str			= '<table><tr><td colspan="2">支付出现错误!</td></tr>';
				foreach($resArray as $key => $value) 
				{
					$str		.= "<tr><td>{$key}:</td><td>{$value}</td></tr>";
				}
				$str			.= '</table>';
				$this->message	= $str;
				return false;
			}
			else
			{
				//支付成功
				$money			= $info['money'];
				$this->message	= "成功支付: {$money} 元(人民币)";
				return true;
			}
		}
	}

	//返回人民币对当前设置的 货币汇率金额
	private function getExchangeRateMoney()
	{
		//相当于多少美元
		if( $this->currency	== 'USD' )
		{
			return number_format( $this->amount / $this->exchange_rate_usd , 2 , '.' , '');
		}
		//相当于多少欧元
		else if( $this->currency == 'EUR' )
		{
			return number_format( $this->amount / $this->exchange_rate_euro , 2 , '.' , '');
		}
		return $this->amount;
	}

	/*
	* 发送请求
	*/
	private function send_request($methodName,$nvpStr)
	{
		// form header string
		$nvpheader	= "&PWD=".urlencode($this->password);
		$nvpheader	.="&USER=".urlencode($this->username)."&SIGNATURE=".urlencode($this->signature);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$this->api_url);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		//turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST, 1);
		
		$nvpStr	= $nvpheader.$nvpStr;

		//check if version is included in $nvpStr else include the version.
		if(strlen(str_replace('VERSION=', '', strtoupper($nvpStr))) == strlen($nvpStr)) {
			$nvpStr = "&VERSION=" . urlencode($this->version) . $nvpStr;
		}
		
		$nvpreq="METHOD=".urlencode($methodName).$nvpStr;
		
		//setting the nvpreq as POST FIELD to curl
		curl_setopt($ch,CURLOPT_POSTFIELDS,$nvpreq);

		//getting response from server
		$response = curl_exec($ch);

		//convrting NVPResponse to an Associative Array
		$nvpResArray=$this->deformatNVP($response);
		$nvpReqArray=$this->deformatNVP($nvpreq);

		if (curl_errno($ch)) 
		{
			  $msg = curl_errno($ch) ;
			  $this->print_msg($msg);
		} 
		else 
		{
			curl_close($ch);
		}

		return $nvpResArray;
	}

	//格式化参数
	private function deformatNVP($nvpstr)
	{
		$intial=0;
		$nvpArray = array();


		while(strlen($nvpstr))
		{
			//postion of Key
			$keypos= strpos($nvpstr,'=');
			//position of value
			$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

			/*getting the Key and Value values and storing in a Associative Array*/
			$keyval=substr($nvpstr,$intial,$keypos);
			$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
			//decoding the respose
			$nvpArray[urldecode($keyval)] =urldecode( $valval);
			$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
		}
		return $nvpArray;
	}


	
	//设置支付金额
	public function setMoney($money)
	{
		$this->amount = number_format($money,2,'.','');
	}

	//获取支付金额
	public function getMoney()
	{
		return $this->amount;
	}

	//设置订单id
	public function setOrderId($id)
	{
		$this->orderId = $id;
	}

	//获取订单id
	public function getOrderId()
	{
		return $this->orderId;
	}

	//设置支付返回地址
	public function setServerurl($url)
	{
		$this->return_url  = $url;

		//取消返回网站首页
		$serverPort			= $_SERVER['SERVER_PORT'];
		$this->cancel_url	= 'http://'.$_SERVER['SERVER_NAME'].($serverPort!=80?":{$serverPort}":'');
	}
	
	//设置浏览器跳转地址
	public function setLocationUrl($url)
	{
	
	}
	
	//是否支持银行直连
	public function isSupportCredit()
	{
		return false;
	}

	//返回支付失败的提示信息
	public function getMessage()
	{
		return $this->message;
	}

	private function print_msg($msg)
	{
		$str = '';
		if( is_array($msg) )
		{
			foreach($msg as $key => $value) 
			{
				$str .= "<tr><td> $key:</td><td>$value</td></tr>";
			}
		}
		else
		{
			$str = "<tr><td>错误信息:</td><td>{$msg}</td></tr>";
		}
		print <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<head>
</head>
<body>
<table width="280">
<tr>
	<td colspan="2" class="header">支付出现错误!</td>
</tr>
{$str}
</table>
</body>
</html>
EOF;
		exit;
	}
	//提供的直连银行的列表
	public static function getBankList(){}

	//设置直连的银行
	public function setCreditBank($bank){}

	//返回当前直连银行的中文名称
	public function getCreditBankName(){}

}
?>