<?php
import("COM.Interface.PayInterface");

/*
* 易宝支付 支付类
*
*/
class YeePay implements PayInterface{
	
	//接口对接静态信息
	public static $pay_interface=array(
								//支付接口中文名
								'pay_cname'=>'易宝支付',
								//支付接口英文名
								'pay_ename'=>'YeePay',
								//支付接口简介
								'synopsis'=>'易宝于2003年8月成立，总部位于北京，现有上千名员工，在北京、上海、广东、深圳、天津、四川、山东、江苏、浙江、福建、陕西等设有32家分公司。自公司成立以来，易宝秉承诚信、尽责、激情、创新、分享的核心价值观，以交易服务改变生活为使命，致力成为世界一流的交易服务平台。2015年，易宝发布了“支付+金融+营销+征信”的战略，领跑电子支付、移动互联和互联网金融。',
								//支付接口版本
								'version'=>'1.0v',
								//所有支付接口，统一使用的异步接口(网站根目录)
								'return_url'=>'/Pay_return.php',
								//支付接口接收服务器返回值的订单KEY名
								'order_key'=>'r6_Order'
								);
	
	public $gateway_url			= 'https://www.yeepay.com/app-merchant-proxy/node';	//支付网关地址
	public $orderId				= 0;				//订单号
	public $amount				= 0;				//金额
	public $rate                = 1;
	public $currency			= 'CNY';			//支付币种

	public $merchant_id			= '';				//商户ID
	public $merchant_key		= '';				//商户签名
	

	public $cmd					= "Buy";			//业务类型 默认Buy
	public $product_name		= 'online pay';			//产品名称
	public $product_cate		= '';				//产品分类
	public $product_desc		= '';				//产品描述
	public $custom				= '';				//定制信息,支付成功时将原样返回.
	public $bank_type			= '';				//支付通道类型, 可直连到各大银行
	public $need_response		= 1;				//默认为"1": 需要应答机制;
	
	public $record_address		= 0;				//为"1": 需要用户将送货地址留在易宝支付系统;为"0": 不需要，默认为 "0".
	public $page_notify_url		= '';				//支付结果浏览器通知URL,完整路径带http
	public $proxy_url			= '';				//代理转发地址,针对于域名绑定的情况

	public $message				= '';
	public $sendMsg				= '在线充值的时候请不要关闭页面！充值成功后页面自动跳转..';	//发送充值时的提示
	public $payname		= '';	

	/*
	* 构造函数
	*/
	function __construct() 
	{
		//读取接口表中的pay_type
		$arr = M('pay_onlineaccount',' ')->where(array('pay_type'=>'YeePay'))->order("pay_amount asc,id desc")->find();
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
		$Model						= M();
		$merchant_id				= $data[self::$pay_interface['pay_ename'].'_account'];
		$this->merchant_id			= $merchant_id?$merchant_id:'';

		$merchant_key				= $data[self::$pay_interface['pay_ename'].'_key'];
		$this->merchant_key			= $merchant_key?$merchant_key:'';

		$proxy_url					= $data[self::$pay_interface['pay_ename'].'_proxy'];
		$this->proxy_url			= $proxy_url?$proxy_url:'';

		$rate                       = $data[self::$pay_interface['pay_ename'].'_merchant_rate'];
		$this->rate			        = $rate ? (float)$rate : 1;
		}
	}


	//返回支付接口中文名称
	public static function getName()
	{
		return '易宝支付';
	}

	//返回接口中文介绍
	public static function getMemo()
	{
		return '易宝支付与支付宝，财付通一样，是中国领先的独立第三方支付平台，2003年8月由北京通融通信息技术有限公司创建，其最大的特点，是实现了招行银行信用卡还款到账功能。';
	}

	//返回需要配置的项
	public static function getConfigInfo()
	{
		return array(
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_name',
				'config_value'=> '易宝支付',
				'name'=>'支付方式名称',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_account',
				'config_value'=> '',
				'name'=>'商户ID',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_key',
				'config_value'=> '',
				'style'=>'width:430px',
				'name'=>'商户签名',
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
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_merchant_rate',
				'config_value'=> '1',
				'name'=>'实付倍数',
				'type'=>'text',
				'style'=>'width:50px',
				'memo'=>'如设置为6倍，则表示支付1元电子币，实际要支付6元人民币',
			),
		);
	}


	//提交表单
	public function submit()
	{
		
		$_action_url			= $this->gateway_url;
		$_location_url			= '';
		//得到要支付的货币金额
		$amount                 = $this->amount;
		//得到实际支付金额
		$real_amount            = $amount*$this->rate;

		$this->amount			= $real_amount;

		$auth = $this->getAuth();


		//是否使用代理跳转
		if(	$this->proxy_url != '' )
		{
			$_action_url	= $this->proxy_url;
			$_location_url	= base64_encode($this->gateway_url);
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
	<span class="success">{$this->sendMsg}</span>
	</div>
	<div style="display:none">
		<form action="{$_action_url}" method="post" id="frm1">
		<input type="hidden" name="location_url" value="{$_location_url}" />
		<input type='hidden' name='p0_Cmd' value='{$this->cmd}' />
		<input type='hidden' name='p1_MerId' value='{$this->merchant_id}' />
		<input type='hidden' name='p2_Order' value='{$this->orderId}' />
		<input type='hidden' name='p3_Amt' value='{$real_amount}' />
		<input type='hidden' name='p4_Cur' value='{$this->currency}' />
		<input type='hidden' name='p5_Pid' value='{$this->product_name}' />
		<input type='hidden' name='p6_Pcat'	value='{$this->product_cate}' />
		<input type='hidden' name='p7_Pdesc' value='{$this->product_desc}' />
		<input type='hidden' name='p8_Url' value='{$this->page_notify_url}' />
		<input type='hidden' name='p9_SAF'	value='{$this->record_address}' />
		<input type='hidden' name='pa_MP' value='{$this->custom}' />
		<input type='hidden' name='pd_FrpId' value='{$this->bank_type}' />
		<input type='hidden' name='pr_NeedResponse'	value='{$this->need_response}' />
		<input type='hidden' name='hmac' value='{$auth}' />
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
	* 处理收到的数据
	*/
	public function receive()
	{
		$r0_Cmd			= $_REQUEST['r0_Cmd'];		// 业务类型, 固定值 ”Buy”. 
		$r1_Code		= $_REQUEST['r1_Code'];		// 支付结果, 固定值 “1”, 代表支付成功.
		$r2_TrxId		= $_REQUEST['r2_TrxId'];	// 易宝支付交易流水号
		$r3_Amt			= $_REQUEST['r3_Amt'];		// 支付金额, 精确到分
		$r4_Cur			= $_REQUEST['r4_Cur'];		// 交易币种,返回时是"RMB"
		$r5_Pid			= $_REQUEST['r5_Pid'];		// 商品名称
		$r6_Order		= $_REQUEST['r6_Order'];	// 订单号
		$r7_Uid			= $_REQUEST['r7_Uid'];		// 易宝支付会员ID
		$r8_MP			= $_REQUEST['r8_MP'];		// 商户扩展信息
		$r9_BType		= $_REQUEST['r9_BType'];	// 交易结果返回类型为“1”: 浏览器重定向; 为“2”: 服务器点对点通讯
		$hmac			= $_REQUEST['hmac'];

		$bRet			= $this->checkAuth($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType,$hmac);
		if($bRet) //签名验证通过
		{
			if($r1_Code=="1") //支付成功
			{
				$Model				= M();
				$where['orderId']	= $r6_Order;
				$info				= $Model->table('pay_order')->where($where)->find();
				$this->message = "success";
				return true;
			}
			else
			{
				$this->message = "支付失败";
				return false;
			}
		}
		else
		{
			$this->message = "交易信息被篡改";
			return false;
		}
	}


	/*
	* 检查签名
	*/
	private function checkAuth($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType,$auth)
	{
		#取得加密前的字符串
		$sbOld = "";
		#加入商家ID
		$sbOld = $sbOld.$this->merchant_id;
		#加入消息类型
		$sbOld = $sbOld.$r0_Cmd;
		#加入业务返回码
		$sbOld = $sbOld.$r1_Code;
		#加入交易ID
		$sbOld = $sbOld.$r2_TrxId;
		#加入交易金额
		$sbOld = $sbOld.$r3_Amt;
		#加入货币单位
		$sbOld = $sbOld.$r4_Cur;
		#加入产品Id
		$sbOld = $sbOld.$r5_Pid;
		#加入订单ID
		$sbOld = $sbOld.$r6_Order;
		#加入用户ID
		$sbOld = $sbOld.$r7_Uid;
		#加入商家扩展信息
		$sbOld = $sbOld.$r8_MP;
		#加入交易结果返回类型
		$sbOld = $sbOld.$r9_BType;

		if( $auth == $this->HmacMd5($sbOld,$this->merchant_key) )
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	/*
	* 获取签名
	*/
	private function getAuth()
	{
		#进行签名处理，一定按照文档中标明的签名顺序进行
		$sbOld = "";
		#1加入业务类型
		$sbOld = $sbOld.$this->cmd;
		#2加入商户编号
		$sbOld = $sbOld.$this->merchant_id;
		#3加入商户订单号
		$sbOld = $sbOld.$this->orderId;     
		#4加入支付金额
		$sbOld = $sbOld.$this->amount;
		#5加入交易币种
		$sbOld = $sbOld.$this->currency;
		#6加入商品名称
		$sbOld = $sbOld.$this->product_name;
		#7加入商品分类
		$sbOld = $sbOld.$this->product_cate;
		#8加入商品描述
		$sbOld = $sbOld.$this->product_desc;
		#9加入商户接收支付成功数据的地址
		$sbOld = $sbOld.$this->page_notify_url;
		#10加入送货地址标识
		$sbOld = $sbOld.$this->record_address;
		#11加入商户扩展信息
		$sbOld = $sbOld.$this->custom;
		#12加入支付通道编码
		$sbOld = $sbOld.$this->bank_type;
		#13加入是否需要应答机制
		$sbOld = $sbOld.$this->need_response;
		return $this->HmacMd5($sbOld,$this->merchant_key);
	}

	private function HmacMd5($data,$key)
	{
		//需要配置环境支持iconv，否则中文参数不能正常处理
		$key = iconv("GB2312","UTF-8",$key);
		$data = iconv("GB2312","UTF-8",$data);

		$b = 64; // byte length for md5
		if (strlen($key) > $b) {
			$key = pack("H*",md5($key));
		}
		$key = str_pad($key, $b, chr(0x00));
		$ipad = str_pad('', $b, chr(0x36));
		$opad = str_pad('', $b, chr(0x5c));
		$k_ipad = $key ^ $ipad ;
		$k_opad = $key ^ $opad;

		return md5($k_opad . pack("H*",md5($k_ipad . $data)));
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
		$this->page_notify_url  = $url;
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


	//提供的直连银行的列表
	public static function getBankList(){}

	//设置直连的银行
	public function setCreditBank($bank){}

	//返回当前直连银行的中文名称
	public function getCreditBankName(){}

}
?>