<?php
import("COM.Interface.PayInterface");
/*
* 财付宝
*
*/
class YunBao implements PayInterface{
	
	//接口对接静态信息
	public static $pay_interface=array(
								//支付接口中文名
								'pay_cname'=>'财付宝',
								//支付接口英文名
								'pay_ename'=>'YunBao',
								//支付接口简介
								'synopsis'=>'财付宝-第三方线上支付金流系统基于互联网运营基础上所建立起的强大技术优势，为企业提供金融资金解决方案及丰富多样的产品选择，为个人提供多元化、多途径的收、付款等服务，提供在国际一流的标准化流程控制下规范化的专属服务。与此同时，我们向用户提供具有金融标准化与服务标准化的高质量服务。',
								//支付接口版本
								'version'=>'1.0v',
								//所有支付接口，统一使用的异步接口(网站根目录)
								'return_url'=>'/Pay_return.php',
								//支付接口接收服务器返回值的订单KEY名
								'order_key'=>'Billno'
								);
	
	public $gateway_url			= 'http://api.anlapay.com';	//支付网关地址
	public $orderId				= 0;				//订单号
	public $amount				= 0;				//金额
	public $rate                = 1;
	public $currency			= 'RMB';			//支付币种

	public $merchant_id			= '';				//商户ID
	public $merchant_key		= '';				//商户签名
	public $orderTime           = ''; //加入订单时间

	public $cmd					= "Buy";			//业务类型 默认Buy
	public $product_info		= 'Yun Po online payment';			//商品信息
    public $Pamp                ='';                 //添加备注信息
    public $Bankcode            ='';                 //加入银行通道
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
		$arr = M('pay_onlineaccount',' ')->where(array('pay_type'=>'YunBao'))->order("pay_amount asc,id desc")->find();
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
		$this->orderTime            = date('Ymd',systemTime());
		}
	}


	//返回支付接口中文名称
	public static function getName()
	{
		return '云宝';
	}

	//返回接口中文介绍
	public static function getMemo()
	{
		return '云宝与易宝支付，财付通一样，是中国领先的独立第三方支付平台，2003年8月由北京通融通信息技术有限公司创建，其最大的特点，是实现了招行银行信用卡还款到账功能。';
	}

	//返回需要配置的项
	public static function getConfigInfo()
	{
		return array(
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_name',
				'config_value'=> '云宝',
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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=GBK" />
<title>云宝支付</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<style type="text/css">
<!--
body { margin:0; padding:0; font-size:14px;}
* { margin:0; padding:0;}
.shuoming { margin:0; font-size:16px; padding:10px 0px; font-weight:bold;}
.color4  { color:#FF0000;}
.font16 { font-weight:bold; font-size:16px;}
-->
</style>
</head>
<body>
<div class="message">
	<div class="msg">
	<span class="success">{$this->sendMsg}</span>
	</div>
	<div style="display:none">
		<form action="{$_action_url}" method="post" id="frm1" />
	      <input type="hidden" name="Merid" value="{$this->merchant_id}" />
		  <input type="hidden" name="pcmd" value="{$this->cmd}" />
	      <input type="hidden" name="Billno" value="{$this->orderId}" />
	      <input type="hidden" name="Amount" value="{$this->amount}" />
	      <input type="hidden" name="Date" value="{$this->orderTime}" />
	      <input type="hidden" name="CurrencyType" value="{$this->currency}" />
	      <input type="hidden" name="Merchanturl" value="{$this->page_notify_url}" />
	      <input type="hidden" name="Attach" value="{$this->product_info}" />
	      <input type="hidden" name="Pamp" value="{$this->Pamp}" />
		  <input type="hidden" name="Bankcode" value="{$this->bank}" />
	      <input type="hidden" name="NeedResponse" value="{$this->need_response}" />
	      <input type="hidden" name="hmac" value="{$auth}" />
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
		$rOrder			= $_REQUEST['rOrder'];		//订单编号 
		$rAmt	 	    = $_REQUEST['rAmt'];		// 订单金额
		$rAttach		= $_REQUEST['rAttach'];	    // 商品数据包
		$rPamp			= $_REQUEST['rPamp'];		// 备注
		$rSucc			= $_REQUEST['rSucc'];		// 支付成功
		$rDate			= $_REQUEST['rDate'];		// 日期
		$rBankorder		= $_REQUEST['rBankorder'];	// 银行订单号
		$rBtype			= $_REQUEST['rBtype'];		// 应答方式 0用于浏览器应答 1 服务器点对点应答
		$rMercode		= $_REQUEST['rMercode'];		// 商户编号
		$hcmack		    = $_REQUEST['hcmack'];	// 返回参数加密签名
		$pmerkey        = $this->merchant_key ;        //密钥

		$bRet			= $this->checkAuth($rMercode,$rOrder,$rAmt,$rAttach,$rPamp,$rSucc,$rDate,$rBankorder,$rBtype,$pmerkey);
		if($bRet==hcmack) //签名验证通过
		{
			if($rSucc=='Y') //支付成功
			{
				if($rBtype==0){
				
				}
				else if($rBtype==1){
					$Model				= M();
					$where['orderId']	= $rOrder;
					$info				= $Model->table('pay_order')->where($where)->find();
					$this->message = "success";
					return true;
				}
			}
			else
			{
				$this->message = "支付失败";
				return false;
			}
		}
		else
		{
			$this->message = "签名不正确！";
			return false;
		}
	}


	/*
	* 检查签名
	*/
	private function checkAuth($rMercode,$rOrder,$rAmt,$rAttach,$rPamp,$rSucc,$rDate,$rBankorder,$rBtype,$pmerkey)
	{
		#取得加密前的字符串
		$sbOld = "";
		#加入商家ID
		$sbOld = $sbOld.'[['.$rMercode.']]';
		#加入订单编号
		$sbOld = $sbOld.'[['.$rOrder.']]';
		#加入金额
		$sbOld = $sbOld.'[['.$rAmt.']]';
		#加入商品信息
		$sbOld = $sbOld.'[['.$rAttach.']]';
		#加入备注信息
		$sbOld = $sbOld.'[['.$rPamp.']]';
		#加入支付状态
		$sbOld = $sbOld.'[['.$rSucc.']]';
		#加入支付日期
		$sbOld = $sbOld.'[['.$rDate.']]';
		#加入银行订单号
		$sbOld = $sbOld.'[['.$rBankorder.']]';
		#加入应答方式
		$sbOld = $sbOld.'[['.$rBtype.']]';
		#加入密钥信息
		$sbOld = $sbOld.'[['.$pmerkey.']]';
		$sbOld = iconv("GB2312","UTF-8",$sbOld);
		#返回值进行2次md5加密
	    return md5(md5($sbOld));
	}
	/*
	* 获取签名
	*/
	private function getAuth()
	{
		#进行签名处理，一定按照文档中标明的签名顺序进行
		$sbOld = "";
		#加入商户编号
		$sbOld = $sbOld.'['.$this->merchant_id.']';
		#加入业务类型
		$sbOld = $sbOld.'['.$this->cmd.']';
		#加入订单号
		$sbOld = $sbOld.'['.$this->orderId.']';     
		#加入金额
		$sbOld = $sbOld.'['.$this->amount.']';
		#加入订单时间
 	    $sbOld = $sbOld.'['.$this->orderTime.']';
		#加入交易币种
		$sbOld = $sbOld.'['.$this->currency.']';
		#加入商户接收支付成功数据的地址
		$sbOld = $sbOld.'['.$this->page_notify_url.']';
		#加入商品信息
		$sbOld = $sbOld.'['.$this->product_info.']';
		#加入备注信息
	    $sbOld = $sbOld.'['.$this->Pamp.']';
    	#加入银行通道
	    $sbOld = $sbOld.'['.$this->bank.']';
	    #加入是否需要应答机制
		$sbOld = $sbOld.'['.$this->need_response.']';
		#加入商户密钥
		$sbOld = $sbOld.'['.$this->merchant_key.']';
		$sbOld = iconv("GB2312","UTF-8",$sbOld);
		return md5(md5($sbOld));
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
	 // $this->return_url  = $url;
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
	public static function getBankList(){
	   return array(
	   'ICBC-NET-B2C'=>'中国工商银行',
	   'CCB-NET-B2C'=>'中国建设银行',
	   'ABC-NET-B2C'=>'中国农业银行',
	   'BOC-NET-B2C'=>'中国银行',
	   'CIB-NET-B2C'=>'兴业银行',
	   'CMBCHINA-NET-B2C'=>'招商银行',
	   'BOCO-NET-B2C'=>'交通银行',
	   'POST-NET-B2C'=>'邮政储蓄',
	   'CMBC-NET-B2C'=>'民生银行',
	   'PINGANBANK-NET'=>'平安银行',
	   'CEB-NET-B2C'=>'光大银行',
	   'SPDB-NET-B2C'=>'浦东发展银行',
	   'HKBEA-NET-B2C'=>'东亚银行',
	   'GDB-NET-B2C'=>'广发银行',
	   'SDB-NET-B2C'=>'深圳发展银行',
	   'BJRCB-NET-B2C'=>'北京农村商业银行',
	   'BCCB-NET-B2C'=>'北京银行',
	   'CBHB-NET-B2C'=>'渤海银行',
	   'HZBANK-NET-B2C'=>'杭州银行',
	   'NBCB-NET-B2C'=>'宁波银行',
	   'SHB-NET-B2C'=>'上海银行',
	   'CZ-NET-B2C'=>'浙商银行'	   	   
	   );
	}

	//设置直连的银行
	public function setCreditBank($bank){
	    $this->bank = $bank;
	}

	//返回当前直连银行的中文名称
	public function getCreditBankName(){
	    $bankList = YunBao::getBankList();
		return $bankList[$this->bank];
	}

}
?>