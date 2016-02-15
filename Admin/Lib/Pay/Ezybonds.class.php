<?php
import("COM.Interface.PayInterface");

/*
* 英国 ezybonds 支付类
*
*/
class Ezybonds implements PayInterface{
	
		//接口对接静态信息
	public static $pay_interface=array(
								//支付接口中文名
								'pay_cname'=>'ezybonds',
								//支付接口英文名
								'pay_ename'=>'Ezybonds',
								//支付接口简介
								'synopsis'=>'EZYBONDS公司提供全球性软件結算平台EZYBONDS INTERNATIONAL LTD. –GLOBAL PAYMENTS拥有全世界第一家联結全球免稅的〝全球线上付款窗口交易系統〞；倍受全球用户、商家及企业关注和拥护。',
								//支付接口版本
								'version'=>'1.0v',
								//所有支付接口，统一使用的异步接口(网站根目录)
								'return_url'=>'/Pay_return.php',
								//支付接口接收服务器返回值的订单KEY名
								'order_key'=>'invoice'
								);
	
	public $gateway_url			= 'https://www.ezybonds.com/merchant/default.asp';	//ezybonds支付网关地址
	public $orderId				= 0;				//订单号
	public $amount				= 0;				//金额
	public $currency			= 'USD';			//支付币种
	public $merchant			= '';				//ezybonds 收款帐号ID
	public $return_url			= '';				//支付成功浏览器重定向的URL,完整路径带http
	public $cancel_url			= '';				//支付失败浏览器重定向的URL,完整路径带http
	public $sendMsg				= '在线充值的时候请不要关闭页面！充值成功后页面自动跳转..';	//发送充值时的提示
	public $ezybonds_key		= '';
	public $type				= 'payment';
	public $product_desc		= 'product_desc';
	public $payname		= '';
	/*
	* 构造函数
	*/
	function __construct()
	{
		//读取接口表中的pay_type
		$arr = M('pay_onlineaccount',' ')->where(array('pay_type'=>'Ezybonds'))->order("pay_amount asc,id desc")->find();
		//将$arr['pay_attr'] 返序列化
		if($arr){
	    $data_arr = unserialize($arr['pay_attr']);//是一个二维数组 
	    //查询金额最小的金额的记录
	    $data = array();
		foreach($data_arr as $key=>$v){
			$data[$key] = $v;
		}
		//读取数据库中的设置
		$Model						= M();
		$merchant					= $data[self::$pay_interface['pay_ename'].'_account'];
		$this->merchant				= $merchant?$merchant:'';

		$ezybonds_key				= $data[self::$pay_interface['pay_ename'].'_key'];
		$this->ezybonds_key			= $ezybonds_key?$ezybonds_key:'';

		$this->return_url			= $this->getSiteDomain().'/Admin/Common/ezybonds/pay_success.html';
		$this->cancel_url			= $this->getSiteDomain().'/Admin/Common/ezybonds/pay_error.html';
		}
	}


	//返回支付接口中文名称
	public static function getName()
	{
		return 'Ezybonds';
	}

	//返回接口中文介绍
	public static function getMemo()
	{
		return 'Ezybonds是一家英国上市公司，提供全球性电子交易系统结算平台，它将世界带入亚洲、使亚洲与世界接轨，更使企业及个人销售与世界同步。';
	}

	//返回需要配置的项
	public static function getConfigInfo()
	{
		return array(
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_name',
				'config_value'=> 'Ezybonds支付',
				'name'=>'支付方式名称',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_account',
				'config_value'=> '',
				'name'=>'Ezybonds帐号ID',
				'type'=>'text',
				'style'=>'width:100px',
				'memo'=>'<a style="color:red" href="/Admin/Common/ezybonds/readme.doc" target="_blank">安装说明</a>',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_key',
				'config_value'=> '',
				'name'=>'自定义私钥',
				'type'=>'text',
				'style'=>'width:200px',
				'memo'=>'用于传输加密',
			),
			
		);
	}



	//提交表单
	public function submit()
	{
		$md5			= $this->make_md5();
		print <<<EOF
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html>
<head>
<title>页面提示</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
html, body{margin:0; padding:0; border:0 none;font:14px Tahoma,Verdana;line-height:150%;background:white}
a{text-decoration:none; color:#174B73; border-bottom:1px dashed gray}
a:hover{color:#F60; border-bottom:1px dashed gray}
div.message{margin:10% auto 0px auto;clear:both;padding:5px;border:0px solid silver; text-align:center; width:45%}
span.wait{color:blue;font-weight:bold}
span.error{color:red;font-weight:bold}
span.success{color:blue;font-weight:bold}
div.msg{margin:20px 0px}
</style>
</head>
<body>
<div class="message">
	<div class="msg">
	<span class="success">{$this->sendMsg}</span>
	</div>
	<div style="display:none">
		<form action="{$this->gateway_url}" method="post" id="frm1">
		<input type="hidden" name="type" value="{$this->type}" />
		<input type="hidden" name="merchant" value="{$this->merchant}" />
		<input type="hidden" name="currency" value="{$this->currency}" />
		<input type="hidden" name="return_url" value="{$this->return_url}" />
		<input type="hidden" name="cancel_url" value="{$this->cancel_url}" />
		<input type="hidden" name="product_desc" value="{$this->product_desc}" />
		<input type="hidden" name="product_id" value="{$this->orderId}" />
		<input type="hidden" name="invoice" value="{$this->orderId}" />
		<input type="hidden" name="amount" value="{$this->amount}" />
		<input type="hidden" name="custom01" value="{$md5}" />
		</form>
	</div>
</div>
</body>
</html>
<script language="javascript">
document.getElementById("frm1").submit();
</script>
EOF;
		exit;
	}
	
	/*
	* 获取摘要
	*/
	private function make_md5()
	{
		return md5(
			$this->type.$this->merchant.$this->ezybonds_key.
			$this->currency.$this->product_desc.$this->orderId.$this->orderId.$this->amount
		);
	}

	/*
	* 处理收到的数据
	*/
	public function receive()
	{
		$this->merchant		= trim($_POST["merchant"]);			//帐号id
		$this->orderId		= trim($_POST["invoice"]);			//订单ID
		$this->product_desc	= trim($_POST["product_desc"]);		//产品说明
		$this->amount		= trim($_POST["amount"]);			//支付金额
		$this->currency		= trim($_POST["currency"]);;		//货币类型
		$custom_md5			= trim($_POST["custom01"]);			//自定义字段1(摘要数据)

		$notify_type		= trim($_POST["notify_type"]);		//通知类型 分 两步 第一步:CHECK 第二步:PAYMENT
		
		//获取本地订单信息
		$where['orderId']	= $this->orderId;
		$order_info			= M()->table('pay_order')->where($where)->find();

		//检查MD5值
		$md5				= $this->make_md5();


		//支付检查
		if ($notify_type == "CHECK" && $notify_type != "") 
		{
			if( $custom_md5 != $md5 )
			{
				exit("DECLINE");
			}
			
			//如果订单无效
			if( $order_info['status']!=0 )
			{
				exit("DECLINE");
			}
			exit("CONFIRM");
		}
		//完成支付
		else if($notify_type == "PAYMENT" && $notify_type != "")
		{
			//如果订单是未处理状态
			if( $order_info['status']==0 )
			{
				//检查MD5值
				if( $custom_md5 == $md5 )
				{
					return true;
				}
				else
				{
					M()->table('pay_order')->where($where)->setField('memo','传输加密校检未通过.');
					//更新订单状态为支付失败
					return false;
				}
			}
			exit("ACKNOWLEDGE");
		}
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
		$this->cancel_url  = $url;
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
		return '支付失败!';
	}


	//提供的直连银行的列表
	public static function getBankList(){}

	//设置直连的银行
	public function setCreditBank($bank){}

	//返回当前直连银行的中文名称
	public function getCreditBankName(){}


	//获取当前的域名带 协议
	private function getSiteDomain()
	{
		$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

		return $http_type.$_SERVER['SERVER_NAME'];
	}
}
?>