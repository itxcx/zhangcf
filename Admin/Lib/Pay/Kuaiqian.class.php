<?php
/*
* 快钱在线支付类
*/
import("COM.Interface.PayInterface");

class Kuaiqian implements PayInterface{

	//接口对接静态信息
	public static $pay_interface=array(
								//支付接口中文名
								'pay_cname'=>'快钱支付',
								//支付接口英文名
								'pay_ename'=>'Kuaiqian',
								//支付接口简介
								'synopsis'=>'快钱支付清算信息有限公司（以下简称“快钱”）是国内领先的创新型互联网金融机构。基于十年在电子支付领域的积累，快钱充分整合数据信息，结合各类应用场景，为个人和企业提供丰富的支付工具，稳健的投资理财，便捷的融资信贷，以及个性化的营销优惠，使客户能够随时随地畅享便利、智慧的互联网金融服务。',
								//支付接口版本
								'version'=>'1.0v',
								//所有支付接口，统一使用的异步接口(网站根目录)
								'return_url'=>'/Pay_return.php',
								//支付接口接收服务器返回值的订单KEY名
								'order_key'=>'orderId'
								);

	public $Gateway_URL			= 'https://www.99bill.com/gateway/recvMerchantInfoAction.htm';			//快钱支付网关地址
	public $merchantAcctId		= '';			//人民币网关账户号
	public $key					= '';			//人民币网关密钥
	public $inputCharset		= "1";			//字符集  1代表UTF-8; 2代表GBK; 3代表gb2312
	public $pageUrl				= '';			//接受支付结果的页面地址
	public $bgUrl				= "";			//接受支付结果的服务器页面地址
	public $version				= "v2.0";		//网关版本.固定值
	public $language			= "1";			//语言种类. 1代表中文；2代表英文
	public $signType			= "1";			//签名类型  1代表MD5签名
	public $payerName			= '';			//支付人姓名
	public $payerContactType	= "1";			//支付人联系方式类型  1代表Email
	public $payerContact		= '';			//支付人联系方式,只能选择Email或手机号
	public $orderId				= '';			//商户订单号,由字母、数字、或[-][_]组成
	public $orderAmount			= 0;			//订单金额,以分为单位，必须是整型数字,比方2，代表0.02元	
	public $orderTime			= '';			//订单提交时间 14位数字。年[4位]月[2位]日[2位]时[2位]分[2位]秒[2位]
	public $productName			= "账户充值";		//商品名称
	public $productNum			= "1";			//商品数量,可为空，非空时必须为数字
	public $productId			= "电子币";		//商品代码,可为字符或者数字
	public $productDesc			= "1电子币=1元；";	//商品描述
	public $ext1				= "";			//扩展字段1,在支付结束后原样返回给商户
	public $ext2				= "";			//扩展字段2,在支付结束后原样返回给商户
		
	//支付方式.固定选择值
	///只能选择00、10、11、12、13、14
	///00：组合支付（网关支付页面显示快钱支持的各种支付方式，推荐使用）10：银行卡支付（网关支付页面只显示银行卡支付）.11：电话银行支付（网关支付页面只显示电话支付）.12：快钱账户支付（网关支付页面只显示快钱账户支付）.13：线下支付（网关支付页面只显示线下支付方式）.14：B2B支付（网关支付页面只显示B2B支付，但需要向快钱申请开通才能使用）
	public $payType			= "00";

	//银行代码
	///实现直接跳转到银行页面去支付,只在payType=10时才需设置参数
	public $bankId				= "";

	//同一订单禁止重复提交标志
	///固定选择值： 1、0
	///1代表同一订单号只允许提交1次；0表示同一订单号在没有支付成功的前提下可重复提交多次。默认为0建议实物购物车结算类商户采用0；虚拟产品类商户采用1
	public $redoFlag			= "0";


	public $pid					= ""; ///合作伙伴在快钱的用户编号
	
	public $message				= '';

	public $ServerLocationUrl	= '';//代理收发服务URL
	public $payname		= '';
	/*
	* 构造函数
	*/
	function __construct() 
	{
		//读取接口表中的pay_type
		$arr = M('pay_onlineaccount',' ')->where(array('pay_type'=>'Kuaiqian'))->order("pay_amount asc,id desc")->find();
		if($arr){
		//将$arr['pay_attr'] 返序列化
	    $data_arr = unserialize($arr['pay_attr']);//是一个二维数组 
	    //查询金额最小的金额的记录
	      $data = array();
		   foreach($data_arr as $key=>$v){
		         $data[$key] = $v;
		   }
		//读取数据库中的设置
		$account					= $data[self::$pay_interface['pay_ename'].'_account'];
		$key						= $data[self::$pay_interface['pay_ename'].'_key'];
		$proxy						= $data[self::$pay_interface['pay_ename'].'_proxy'];
		$this->merchantAcctId		= $account?$account:'';
		$this->key					= $key?$key:'';
		$this->ServerLocationUrl	= $proxy?$proxy:'';
		$this->orderTime			= date('YmdHis');
		}
	}
	
	//返回支付接口中文名称
	public static function getName()
	{
		return '快钱支付';
	}

	//返回接口中文介绍
	public static function getMemo()
	{
		return '快钱是支付产品最丰富、覆盖人群最广泛的电子支付企业，其推出的支付产品包括但不限于人民币支付，外卡支付，神州行卡支付，联通充值卡支付，VPOS支付等众多支付产品。';
	}

	//返回需要配置的项
	public static function getConfigInfo()
	{
		return array(
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_name',
				'config_value'=> '快钱支付',
				'name'=>'支付方式名称',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_account',
				'config_value'=> '',
				'name'=>'快钱账号',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_key',
				'config_value'=> '',
				'style'=>'width:430px',
				'name'=>'快钱密钥',
				'type'=>'text',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_proxy',
				'config_value'=> '',
				'name'=>'php转发Url',
				'type'=>'text',
				'style'=>'width:350px',
				'memo'=>'<a href="/Admin/Common/pay_location.php.txt" target="_blank">下载php转发文件</a>',
			),			
		);
	}

	public function submit()
	{
		//发送的数据摘要
		$signMsg	= $this->getSendMD5();
		$_action_url			= $this->Gateway_URL;
		$_location_url			= '';

		//是否使用代理跳转
		if(	$this->ServerLocationUrl != '' )
		{
			$_action_url	= $this->ServerLocationUrl;
			$_location_url	= base64_encode($this->Gateway_URL);
		}
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
	<span class="success">在线充值的时候请不要关闭页面！充值成功后页面自动跳转..</span>
	</div>
	<div style="display:none">
		<form action="{$_action_url}" method="post" id="frm1">
			<input type="hidden" name="inputCharset" value="{$this->inputCharset}"/>
			<input type="hidden" name="bgUrl" value="{$this->bgUrl}"/>
			<input type="hidden" name="pageUrl" value="{$this->pageUrl}"/>
			<input type="hidden" name="version" value="{$this->version}"/>
			<input type="hidden" name="language" value="{$this->language}"/>
			<input type="hidden" name="signType" value="{$this->signType}"/>
			<input type="hidden" name="signMsg" value="{$signMsg}"/>
			<input type="hidden" name="merchantAcctId" value="{$this->merchantAcctId}"/>
			<input type="hidden" name="payerName" value="{$this->payerName}"/>
			<input type="hidden" name="payerContactType" value="{$this->payerContactType}"/>
			<input type="hidden" name="payerContact" value="{$this->payerContact}"/>
			<input type="hidden" name="orderId" value="{$this->orderId}"/>
			<input type="hidden" name="orderAmount" value="{$this->orderAmount}"/>
			<input type="hidden" name="orderTime" value="{$this->orderTime}"/>
			<input type="hidden" name="productName" value="{$this->productName}"/>
			<input type="hidden" name="productNum" value="{$this->productNum}"/>
			<input type="hidden" name="productId" value="{$this->productId}"/>
			<input type="hidden" name="productDesc" value="{$this->productDesc}"/>
			<input type="hidden" name="ext1" value="{$this->ext1}"/>
			<input type="hidden" name="ext2" value="{$this->ext2}"/>
			<input type="hidden" name="payType" value="{$this->payType}"/>
			<input type="hidden" name="bankId" value="{$this->bankId}"/>
			<input type="hidden" name="redoFlag" value="{$this->redoFlag}"/>
			<input type="hidden" name="pid" value="{$this->pid}"/>
			<input type="hidden" name="location_url" value="{$_location_url}" />
		</form>
	</div>
</div>
</body>
</html>
<script language="javascript">
document.getElementById("frm1").submit();
</script>
EOF;
	}

	/*
	* 处理收到的数据
	*/
	public function receive()
	{
		/*
		* @Description: 快钱人民币支付网关接口范例
		* @Copyright (c) 上海快钱信息服务有限公司
		* @version 2.0
		*/

		//获取人民币网关账户号
		$merchantAcctId=trim($_REQUEST['merchantAcctId']);

		//设置人民币网关密钥
		///区分大小写
		$key=$this->key;

		//获取网关版本.固定值
		///快钱会根据版本号来调用对应的接口处理程序。
		///本代码版本号固定为v2.0
		$version=trim($_REQUEST['version']);

		//获取语言种类.固定选择值。
		///只能选择1、2、3
		///1代表中文；2代表英文
		///默认值为1
		$language=trim($_REQUEST['language']);

		//签名类型.固定值
		///1代表MD5签名
		///当前版本固定为1
		$signType=trim($_REQUEST['signType']);

		//获取支付方式
		///值为：10、11、12、13、14
		///00：组合支付（网关支付页面显示快钱支持的各种支付方式，推荐使用）10：银行卡支付（网关支付页面只显示银行卡支付）.11：电话银行支付（网关支付页面只显示电话支付）.12：快钱账户支付（网关支付页面只显示快钱账户支付）.13：线下支付（网关支付页面只显示线下支付方式）.14：B2B支付（网关支付页面只显示B2B支付，但需要向快钱申请开通才能使用）
		$payType=trim($_REQUEST['payType']);

		//获取银行代码
		///参见银行代码列表
		$bankId=trim($_REQUEST['bankId']);

		//获取商户订单号
		$orderId=trim($_REQUEST['orderId']);

		//获取订单提交时间
		///获取商户提交订单时的时间.14位数字。年[4位]月[2位]日[2位]时[2位]分[2位]秒[2位]
		///如：20080101010101
		$orderTime=trim($_REQUEST['orderTime']);

		//获取原始订单金额
		///订单提交到快钱时的金额，单位为分。
		///比方2 ，代表0.02元
		$orderAmount=trim($_REQUEST['orderAmount']);

		//获取快钱交易号
		///获取该交易在快钱的交易号
		$dealId=trim($_REQUEST['dealId']);

		//获取银行交易号
		///如果使用银行卡支付时，在银行的交易号。如不是通过银行支付，则为空
		$bankDealId=trim($_REQUEST['bankDealId']);

		//获取在快钱交易时间
		///14位数字。年[4位]月[2位]日[2位]时[2位]分[2位]秒[2位]
		///如；20080101010101
		$dealTime=trim($_REQUEST['dealTime']);

		//获取实际支付金额
		///单位为分
		///比方 2 ，代表0.02元
		$payAmount=trim($_REQUEST['payAmount']);

		//获取交易手续费
		///单位为分
		///比方 2 ，代表0.02元
		$fee=trim($_REQUEST['fee']);

		//获取扩展字段1
		$ext1=trim($_REQUEST['ext1']);

		//获取扩展字段2
		$ext2=trim($_REQUEST['ext2']);

		//获取处理结果
		///10代表 成功; 11代表 失败
		///00代表 下订单成功（仅对电话银行支付订单返回）;01代表 下订单失败（仅对电话银行支付订单返回）
		$payResult=trim($_REQUEST['payResult']);

		//获取错误代码
		///详细见文档错误代码列表
		$errCode=trim($_REQUEST['errCode']);

		//获取加密签名串
		$signMsg=trim($_REQUEST['signMsg']);



		//生成加密串。必须保持如下顺序。
		$signature= $this->appendParam($signature,"merchantAcctId",$merchantAcctId);
		$signature= $this->appendParam($signature,"version",$version);
		$signature= $this->appendParam($signature,"language",$language);
		$signature= $this->appendParam($signature,"signType",$signType);
		$signature= $this->appendParam($signature,"payType",$payType);
		$signature= $this->appendParam($signature,"bankId",$bankId);
		$signature= $this->appendParam($signature,"orderId",$orderId);
		$signature= $this->appendParam($signature,"orderTime",$orderTime);
		$signature= $this->appendParam($signature,"orderAmount",$orderAmount);
		$signature= $this->appendParam($signature,"dealId",$dealId);
		$signature= $this->appendParam($signature,"bankDealId",$bankDealId);
		$signature= $this->appendParam($signature,"dealTime",$dealTime);
		$signature= $this->appendParam($signature,"payAmount",$payAmount);
		$signature= $this->appendParam($signature,"fee",$fee);
		$signature= $this->appendParam($signature,"ext1",$ext1);
		$signature= $this->appendParam($signature,"ext2",$ext2);
		$signature= $this->appendParam($signature,"payResult",$payResult);
		$signature= $this->appendParam($signature,"errCode",$errCode);
		$signature= $this->appendParam($signature,"key",$key);
		$signature= md5($signature);

		//md5摘要不一样
		if( strtoupper($signMsg) != strtoupper($signature) )
		{
			$this->message = '签名验证失败!';
			return false;
		}
		//md5摘要一样
		else
		{
			if( $payResult == '10' )	//支付成功
			{
				$this->message = '支付成功!';
				return true;
			}
			else
			{
				$this->message = '支付失败!';
				return false;
			}
		}
		return $data;	
	}

	/*
	* 摘要发送的数据
	*/
	private function getSendMD5()
	{
		//生成加密签名串
		///请务必按照如下顺序和规则组成加密串！
		$signMsgVal ='';
		$signMsgVal = $this->appendParam($signMsgVal,"inputCharset",$this->inputCharset);
		$signMsgVal = $this->appendParam($signMsgVal,"pageUrl",$this->pageUrl);
		$signMsgVal = $this->appendParam($signMsgVal,"bgUrl",$this->bgUrl);
		$signMsgVal = $this->appendParam($signMsgVal,"version",$this->version);
		$signMsgVal = $this->appendParam($signMsgVal,"language",$this->language);
		$signMsgVal = $this->appendParam($signMsgVal,"signType",$this->signType);
		$signMsgVal = $this->appendParam($signMsgVal,"merchantAcctId",$this->merchantAcctId);
		$signMsgVal = $this->appendParam($signMsgVal,"payerName",$this->payerName);
		$signMsgVal = $this->appendParam($signMsgVal,"payerContactType",$this->payerContactType);
		$signMsgVal = $this->appendParam($signMsgVal,"payerContact",$this->payerContact);
		$signMsgVal = $this->appendParam($signMsgVal,"orderId",$this->orderId);
		$signMsgVal = $this->appendParam($signMsgVal,"orderAmount",$this->orderAmount);
		$signMsgVal = $this->appendParam($signMsgVal,"orderTime",$this->orderTime);
		$signMsgVal = $this->appendParam($signMsgVal,"productName",$this->productName);
		$signMsgVal = $this->appendParam($signMsgVal,"productNum",$this->productNum);
		$signMsgVal = $this->appendParam($signMsgVal,"productId",$this->productId);
		$signMsgVal = $this->appendParam($signMsgVal,"productDesc",$this->productDesc);
		$signMsgVal = $this->appendParam($signMsgVal,"ext1",$this->ext1);
		$signMsgVal = $this->appendParam($signMsgVal,"ext2",$this->ext2);
		$signMsgVal = $this->appendParam($signMsgVal,"payType",$this->payType);	
		$signMsgVal = $this->appendParam($signMsgVal,"bankId",$this->bankId);
		$signMsgVal = $this->appendParam($signMsgVal,"redoFlag",$this->redoFlag);
		$signMsgVal = $this->appendParam($signMsgVal,"pid",$this->pid);
		$signMsgVal = $this->appendParam($signMsgVal,"key",$this->key);
		return strtoupper(md5($signMsgVal));
	}

	//参数链接
	private function appendParam($returnStr,$paramId,$paramValue)
	{
		if($returnStr!="")
		{
			if($paramValue!="")
			{
				$returnStr .= "&".$paramId."=".$paramValue;
			}
		}
		else
		{
			if($paramValue!="")
			{
				$returnStr = $paramId."=".$paramValue;
			}
		}
		return $returnStr;
	}

	//设置支付返回地址
	public function setServerurl($url)
	{
		$this->pageUrl  = $url;
		$this->bgUrl  = $url;
	}

	//设置浏览器跳转地址
	public function setLocationUrl($url)
	{
	
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

	//设置支付金额
	public function setMoney($money)
	{
		//快钱默认的为分
		$this->orderAmount = intval($money*100);
	}

	//获取支付金额
	public function getMoney()
	{
		return $this->orderAmount;
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

	//是否使用代理
	public function is_proxy()
	{
		return $this->ServerLocationUrl==''?false:true;
	}

	//提供的直连银行的列表
	public static function getBankList(){}

	//设置直连的银行
	public function setCreditBank($bank){}

	//返回当前直连银行的中文名称
	public function getCreditBankName(){}
}
?>