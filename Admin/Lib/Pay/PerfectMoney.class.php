<?php
import("COM.Interface.PayInterface");

/*
* 瑞士 Perfect Money 支付接口
*
*/
class PerfectMoney implements PayInterface{
	
	//接口对接静态信息
	public static $pay_interface=array(
								//支付接口中文名
								'pay_cname'=>'完美支付',
								//支付接口英文名
								'pay_ename'=>'PerfectMoney',
								//支付接口简介
								'synopsis'=>'PerfectMoney（简称 PM）是一个瑞士的电子支付系统，类似于 PP/AP/LR，也是一种国际网银，可以用来交易美元（USD）/欧元（EURO）等国际货币。',
								//支付接口版本
								'version'=>'1.0v',
								//所有支付接口，统一使用的异步接口(网站根目录)
								'return_url'=>'/Pay_return.php',
								//支付接口接收服务器返回值的订单KEY名
								'order_key'=>'id'
								);

	public $pay_gateway_url		= 'https://perfectmoney.com/acct/confirm.asp';				//支付网关地址
	public $Billno				= 0;				//订单号
	public $Amount				= 0;				//金额
	public $Login_Account		= '';				//登录帐号
	public $Payer_Account		= '';				//付款帐号
	public $Payee_Account		= '';				//收款帐号
	public $exchange_rate		= '1';				//美元汇率
	public $sendMsg				= '在线充值的时候请不要关闭页面！充值成功后页面自动跳转..';	//发送充值时的提示
	public $isSupportCredit		= false;			//是否支持银行直连
	public $bank				= '';				//直连的银行
	private $message			= ''; //消息提示
	public $payname		= '';
	/*
	* 构造函数
	*/
	function __construct() 
	{
			//读取接口表中的pay_type
		$arr = M('pay_onlineaccount',' ')->where(array('pay_type'=>'PerfectMoney'))->order("pay_amount asc,id desc")->find();
		if($arr){
		//将$arr['pay_attr'] 返序列化
	    $data_arr = unserialize($arr['pay_attr']);//是一个二维数组 
	    //查询金额最小的金额的记录
      	$data = array();
	   	foreach($data_arr as $key=>$v){
	    	$data[$key] = $v;
	   	}
		//读取数据库中的设置
		$Payee_Account				= $data[self::$pay_interface['pay_ename'].'_account'];
		$this->Payee_Account		= $Payee_Account?$Payee_Account:'';
		$exchange_rate				= $data[self::$pay_interface['pay_ename'].'_exchange_rate'];
		$this->exchange_rate		= $exchange_rate?$exchange_rate:'1';
		}
	}


	//返回支付接口中文名称
	public static function getName()
	{
		return '完美货币';
	}

	//返回接口中文介绍
	public static function getMemo()
	{
		return 'PerfectMoney(完美货币）是一家网络银行，也是一种国际网银，可以用来交易美元（USD）/欧元（EURO）等国际货币。理想的金融机构将是今后全球虚拟经济的完美的归宿。Perfect Money公司的目的就是把金融操作完美地引入到互联网中。';
	}

	//返回需要配置的项
	public static function getConfigInfo()
	{
		return array(
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_name',
				'config_value'=> '完美货币',
				'name'=>'支付方式名称',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_account',
				'config_value'=> '',
				'name'=>'收款帐号',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_exchange_rate',
				'config_value'=> '6.1865',
				'name'=>'美元汇率',
				'type'=>'text',
				'style'=>'width:100px',
				'memo'=>'1美元折合的人民币数量'
			),
			/*
			array(
				'config_name'=>'PerfectMoney_login_account',
				'config_value'=> '',
				'name'=>'登录ID',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>'PerfectMoney_pay_account',
				'config_value'=> '',
				'name'=>'付款帐号',
				'type'=>'text',
				'style'=>'width:100px',
			),
			*/
		);	
	
	}

	//提供的直连银行的列表
	public static function getBankList()
	{
		return array();
	}

	//翻译错误提示
	private function translate($msg)
	{
		$msg = strtolower($msg);
		if( $msg == "can't login with passed accountid and passphrase" )
		{
			return '无法通过此帐号ID和密码登录!';
		}
		else if( $msg == 'invalid payer_account')
		{
			return '付款帐号无效!';
		}
		return $msg;
	}

	//提交到perfectMoney官网
	public function receive()
	{
		//获取订单信息
		$Model						= M();
		$orderId					= $_POST['id'];
		$where['orderId']			= $orderId;
		$info						= $Model->table('pay_order')->where($where)->find();
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



		$url	= $this->pay_gateway_url;
		$out	= $this->send_post($url,$_POST);

		if(!preg_match_all("/<input name='(.*)' type='hidden' value='(.*)'>/", $out, $result, PREG_SET_ORDER))
		{
		   $this->message = '调用接口失败';
		   return false;
		}

		$returnData="";
		foreach($result as $item)
		{
			$key					= $item[1];
			$returnData[$key]		= $item[2];

			//检查是否存在错误
			if( strtoupper($key) == 'ERROR' )
			{
				$this->message		= $this->translate($item[2]);
				return false;
			}
		}
		
		$money	= $info['money'];
		$this->message	= "成功支付: {$money} 元(人民币)";
		return true;
	}

	//提交表单
	public function submit()
	{
		$money_usd = number_format( $this->Amount / $this->exchange_rate , 2 , '.' , '');
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
div.message{clear:both;padding:5px;border:0px solid silver; text-align:center; width:100%;padding-top: 407px;}
span.wait{color:blue;font-weight:bold}
span.error{color:red;font-weight:bold}
span.success{color:blue;font-weight:bold;}
div.msg{margin:20px 0px}
</style>
</head>
<body>
<div style="background: url('/Public/Images/pm.gif') no-repeat;height:922px;line-height: 100%;margin: auto;width: 1410px;">
<div class="message">
	<div id="msg" style="display:none" class="msg">
	<span class="success">{$this->sendMsg}</span>
	</div>
	<div id="content" style="text-align:center;width:100%">
		<form action="?s=/Payment/receive" method="post" id="frm1">
		<input type="hidden" name="PAYMENT_ID" value="{$this->Billno}" />
		<input type="hidden" name="Payee_Account" value="{$this->Payee_Account}" />
		<input type="hidden" name="Amount" value="{$money_usd}" />
		<input type="hidden" name="id" value="{$this->Billno}" />
		<table width="300" align="center">
		<tr>
		<td width="150"  align="right">Account ID:</td><td><input type="text" name="AccountID" value="" /></td>
		</tr>
		<tr>
		<td  align="right">Password:</td><td><input type="password" name="PassPhrase" value="" /></td>
		</tr>
		<tr>
		<td  align="right">Pay Account:</td><td><input type="text" name="Payer_Account" value="" /></td>
		</tr>
		<tr>
		<td  align="right">Pay Amount:</td><td align="left">{$this->Amount} RMB (USD:{$money_usd})</td>
		</tr>
		<tr>
		<td colspan="2">
			<input type="submit" value="Submit" onclick="submit()" />
		</td>
		</tr>
		</table>
		</form>
	</div>
</div>
</div>
</body>
</html>
<script language="javascript">
function submit()
{
	document.getElementById('content').style.display='none';
	document.getElementById('msg').style.display='block';
}
</script>
EOF;
	}
	
	//设置支付金额
	public function setMoney($money)
	{

		$this->Amount = number_format($money,2,'.','');
	}

	//获取支付金额
	public function getMoney()
	{
		return $this->Amount;
	}

	//设置订单id
	public function setOrderId($id)
	{
		$this->Billno = $id;
	}

	//获取订单id
	public function getOrderId()
	{
		return $this->Billno;
	}

	//设置支付返回地址
	public function setServerurl($url)
	{
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

	//设置直连的银行
	public function setCreditBank($bank)
	{
		$this->bank = $bank;
	}

	//返回当前直连银行的中文名称
	public function getCreditBankName()
	{
		return '';
	}

	public function getMessage()
	{
		return $this->message;
	}

	//模拟发送 post 请求
	public function send_post($url,$post_data)
	{
		$result			= '';

		$url_info		= parse_url($url);

		$referrer		= $_SERVER["SCRIPT_URI"];
		 
		foreach($post_data as $key=>$value){
			$values[]	= "$key=".urlencode($value);
		}
	
		$data_string	= implode("&",$values);

		$port			= $url_info['scheme']=='https'?443:80;
		$host			= $url_info['scheme']=='https'?'ssl://'.$url_info["host"]:$url_info["host"];

		$request		.="POST ".$url_info["path"]." HTTP/1.1\n";
		$request		.="Host: ".$url_info["host"]."\n";
		$request		.="Referer: $referrer\n";
		$request		.="Content-type: application/x-www-form-urlencoded\n";
		$request		.="Content-length: ".strlen($data_string)."\n";
		$request		.="Connection: close\n";
		$request		.="\n";
		$request		.=$data_string."\n";

		$fp				= fsockopen($host,$port);

		fputs($fp, $request);
		while(!feof($fp)) {
			$result .= fgets($fp, 128);
		}
		fclose($fp);
		return $result;
	}
}
?>