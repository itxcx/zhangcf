<? header("content-Type: text/html; charset=utf-8");?>
<?php

import("COM.Interface.PayInterface");

/*
*
* 云宝支付 支付类
*
*/

class ZhiFu implements PayInterface

{
	//支付网关地址
	public $gateway_url			= 'https://pay.dinpay.com/gateway?input_charset=UTF-8';	
	
	//接口版本	
	public $interface_version = 'V3.0'; 
		
	//业务类型	
    public $service_type = 'direct_pay';      
    
    //商家号
    public $merchant_code = '';
    
    //参数编码字符集
    public $input_charset =   'UTF-8';

    //服务器异步通知地址
    public $notify_url = '';
    
    //页面跳转同步通知地址
	public $return_url	= '';
	
	//签名方式
	public  $sign_type = 'MD5';		
	
	//签名
	public $sign = '';
		
    //订单号
    public  $order_no='';
    
    //商户订单的时间
    public  $order_time = '';
    
    //商户订单总金额
    public $order_amount = 0;
    
    //商品名称
    public $product_name = '智付在线支付';
    
    	//是否支持银行直连
    public $isSupportCredit		= false;		
    
    //加入银行通道
    public $bank            ='';                 

	
	//转发URL
	public $proxy = '';			
		//接口对接静态信息
	public static $pay_interface=array(
								//支付接口中文名
								'pay_cname'=>'direct_pay',
								//支付接口英文名
								'pay_ename'=>'direct_pay',
								//支付接口简介
								'synopsis'=>'',
								//支付接口版本
								'version'=>'1.0v',
								//所有支付接口，统一使用的异步接口(网站根目录)
								'return_url'=>'/Pay_return.php',
								//支付接口接收服务器返回值的订单KEY名
								'order_key'=>'invoice'
								);

	/*

	* 构造函数

	*/

	function __construct() 

	{
	  //读取接口表中的pay_type
		$arr = M('pay_onlineaccount',' ')->where(array('pay_type'=>'ZhiFu'))->order("pay_amount asc,id desc")->find();
		//将$arr['pay_attr'] 返序列化
	    $data_arr = unserialize($arr['pay_attr']);//是一个二维数组 
	    //查询金额最小的金额的记录
	      $data = array();
		   foreach((array)$data_arr as $key=>$v)
		   {
		   	   foreach((array)$v as $key1=>$v1){
		         $data[$key1] = $v1;
		      	}
		   }
		//读取数据库中的设置
		$Model						= M();
		$MerNo				        = isset($data['ZhiFu_MerNo'])?$data['ZhiFu_MerNo']:'';

		$this->merchant_code			    = $MerNo?$MerNo:'';

		$SignInfo				=isset($data['ZhiFu_SignInfo'])?$data['ZhiFu_SignInfo']:'';


		$this->sign			= $SignInfo?$SignInfo:'';

		$Hui_proxy					= isset($data['ZhiFu_proxy'])?$data['ZhiFu_proxy']:'';


		$this->proxy			= $Hui_proxy?$Hui_proxy:'';
		
		
        $credit						= isset($data['ZhiFu_credit'])?$data['ZhiFu_credit']:'0';
        $this->isSupportCredit		= $credit=='1'?true:false;

		$this->order_time            = date('Y-m-d H:i:s',systemTime());

	}


	//返回支付接口中文名称

	public static function getName()

	{

		return '智付';

	}



	//返回接口中文介绍

	public static function getMemo()

	{

		return '智付 （Dinpay）是中国领先的独立第三方支付公司';

	}



	//返回需要配置的项
	//返回需要配置的项
	public static function getConfigInfo()
	{
		return array(
			array(
				'config_name'=>'ZhiFu_name',
				'config_value'=> '智付',
				'name'=>'支付方式名称',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>'ZhiFu_MerNo',
				'config_value'=> '',
				'name'=>'商户号',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>'ZhiFu_SignInfo',
				'config_value'=> '',
				'style'=>'width:430px',
				'name'=>'商户签名',
				'type'=>'text',
			),
			array(
				'config_name'=>'ZhiFu_proxy',
				'config_value'=> '',
				'name'=>'php转发Url',
				'type'=>'text',
				'style'=>'width:350px',
				'memo'=>'<a href="/Admin/Common/pay_location.php.txt" target="_blank">下载php转发文件</a>',
			),
			array(
				'config_name'=>'ZhiFu_credit',
				'config_value'=> '0',
				'name'=>'银行直连',
				'type'=>'radio',
				'options'=>array(
					'1'=>'使用',
					'0'=>'不使用'
				),
			),
		);
	}
	//提交表单
	public function submit()
	{
		//提交的地址 支付地址
		$_action_url			= $this->gateway_url;
		//跳转
		$_location_url			= '';
		//得到要支付的货币金额
		$Amount                 = $this->order_amount;
		//是否使用代理跳转
		if(	$this->proxy != '' )
		{
			$_action_url	= $this->proxy;
			$_location_url	= base64_encode($this->gateway_url);
		}
		
		//商家号（必填）
		$merchant_code = $this->merchant_code;
		//业务类型(必填)
		$service_type = $this->service_type;
		//接口版本(必选)固定值:V3.0
		$interface_version = $this->interface_version;
		//支付类型
		$pay_type="";
		//签名方式(必填)
		$sign_type = $this->sign_type;
		//参数编码字符集(必选)
		$input_charset = $this->input_charset;
		//后台通知地址(必填)
		$notify_url = $this->notify_url;
		//商家定单号(必填)
		$order_no = $this->order_no;
		//商家定单时间(必填)
		$order_time = $this->order_time;
		//定单金额（必填）
		$order_amount = $this->order_amount;
		//商品名称（必填）
		$product_name = 'ZhiFu payment';
		//商品编号(选填)
		$product_code = '';
		//商品描述（选填）
		$product_desc = '';
		//端口数量(选填)
		$product_num ='';
		//商品展示地址(选填)
		$show_url = '';
		//客户端IP（选填）
		$client_ip = '';
		// 直联通道代码（选填）
		$bank_code = $this->bank;
		//商家订单号重复校验
		$redo_flag="";
		//公用业务扩展参数（选填）
		$extend_param = '';
		//公用业务回传参数（选填）
		$extra_return_param = '';
		//页面跳转同步通知地址(选填)
		$return_url = '';
		/* 注  new String(参数.getBytes("UTF-8"),"此页面编码格式"); 若为GBK编码 则替换UTF-8 为GBK*/
		if($product_name != ""){		
			$product_name = mb_convert_encoding($product_name, "UTF-8", "UTF-8");	 
		}
		if($product_code != ""){		
			$product_code = mb_convert_encoding($product_code, "UTF-8", "UTF-8");	 
		}
		if($product_desc != ""){		
			$product_desc = mb_convert_encoding($product_desc, "UTF-8", "UTF-8");		
		}	
		if($order_no != ""){		
			$order_no = mb_convert_encoding($order_no, "UTF-8", "UTF-8");	 
		}
		if($extend_param != ""){		
			$extend_param = mb_convert_encoding($extend_param, "UTF-8", "UTF-8");	 
		}
		if($extra_return_param != ""){		
			$extra_return_param = mb_convert_encoding($extra_return_param, "UTF-8", "UTF-8");	 
		}
		if($notify_url != ""){		
			$notify_url = mb_convert_encoding($notify_url, "UTF-8", "UTF-8");		
		}
		if($return_url != ""){		
			$return_url = mb_convert_encoding($return_url, "UTF-8", "UTF-8");		
		}
		if($show_url != ""){		
			$show_url = mb_convert_encoding($show_url, "UTF-8", "UTF-8");		
		}
		//签名验证码
		$signStr= "";
		if($bank_code != ""){
			$signStr = $signStr."bank_code=".$bank_code."&";
		}
		if($client_ip != ""){
			$signStr = $signStr."client_ip=".$client_ip."&";
		}
		if($extend_param != ""){
			$signStr = $signStr."extend_param=".$extend_param."&";
		}
		if($extra_return_param != ""){
			$signStr = $signStr."extra_return_param=".$extra_return_param."&";
		}
		
		$signStr = $signStr."input_charset=".$input_charset."&";	
		$signStr = $signStr."interface_version=".$interface_version."&";	
		$signStr = $signStr."merchant_code=".$merchant_code."&";	
		$signStr = $signStr."notify_url=".$notify_url."&";		
		$signStr = $signStr."order_amount=".$order_amount."&";		
		$signStr = $signStr."order_no=".$order_no."&";		
		$signStr = $signStr."order_time=".$order_time."&";	
		if($pay_type != ""){
			$signStr = $signStr."pay_type=".$pay_type."&";
		}
		if($product_code != ""){
			$signStr = $signStr."product_code=".$product_code."&";
		}	
		if($product_desc != ""){
			$signStr = $signStr."product_desc=".$product_desc."&";
		}
		$signStr = $signStr."product_name=".$product_name."&";
		if($product_num != ""){
			$signStr = $signStr."product_num=".$product_num."&";
		}	
		if($redo_flag != ""){
			$signStr = $signStr."redo_flag=".$redo_flag."&";
		}
		if($return_url != ""){
			$signStr = $signStr."return_url=".$return_url."&";
		}
		$signStr = $signStr."service_type=".$service_type."&";
		if($show_url != ""){
			$signStr = $signStr."show_url=".$show_url."&";
		}
		//设置密钥
		$signStr = $signStr."key=".$this->sign;
		$singInfo =  $signStr;
		$sign = md5($singInfo);
		print <<<EOF

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body >
	<div style="display:none">
<form name="dinpayForm" method="post" id="frm1" action="{$_action_url}">
		
		<input type="hidden" name="sign" value="{$sign}" />
		<input type="hidden" name="merchant_code" value="{$merchant_code}" />
		<input type="hidden" name="bank_code"  value="{$bank_code}" />
		<input type="hidden" name="order_no"  value="{$order_no}" />
		<input type="hidden" name="order_amount"  value="{$order_amount}" />
		<input type="hidden" name="service_type"  value="{$service_type}" />
		<input type="hidden" name="input_charset"  value="{$input_charset}" />
		<input type="hidden" name="notify_url"  value="{$notify_url}" />
		<input type="hidden" name="interface_version"  value="{$interface_version}"/>
		<input type="hidden" name="sign_type"  value="{$sign_type}" />
		<input type="hidden" name="order_time"  value="{$order_time}" />
		<input type="hidden" name="product_name"  value="{$product_name}" />
		<input Type="hidden" Name="client_ip"  value="{$client_ip}" />
		<input Type="hidden" Name="extend_param"  value="{$extend_param}" />
		<input Type="hidden" Name="extra_return_param"  value="{$extra_return_param}" />
		<input Type="hidden" Name="pay_type"  value="{$pay_type}"/>
		<input Type="hidden" Name="product_code"  value="{$product_code}" />
		<input Type="hidden" Name="product_desc"  value="{$product_desc}" />
		<input Type="hidden" Name="product_num"  value="{$product_num}" />
		<input Type="hidden" Name="return_url"  value="{$return_url}" />
		<input Type="hidden" Name="show_url"  value="{$show_url}" />
		<input Type="hidden" Name="redo_flag"     value="{$redo_flag}"/>
		<input type="hidden" name="location_url" value="{$_location_url}" />
	</form>
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
		//商户号
		$merchant_code	= $_REQUEST["merchant_code"];
		$interface_version = isset($_REQUEST["interface_version"])?$_REQUEST["interface_version"]:"";
		$sign_type = $_REQUEST["sign_type"];
		$dinpaySign = $_REQUEST["sign"];
		$notify_type = $_REQUEST["notify_type"];
		$notify_id = $_REQUEST["notify_id"];
		$order_no = $_REQUEST["order_no"];
		$order_time = $_REQUEST["order_time"];	
		$order_amount = $_REQUEST["order_amount"];
		$trade_status = $_REQUEST["trade_status"];
		$trade_time = $_REQUEST["trade_time"];
		$trade_no = $_REQUEST["trade_no"];
		$bank_seq_no = $_REQUEST["bank_seq_no"];
		$extra_return_param = $_REQUEST["extra_return_param"];
		/**
		*签名顺序按照参数名a到z的顺序排序，若遇到相同首字母，则看第二个字母，以此类推，
		*同时将商家支付密钥key放在最后参与签名，组成规则如下：
		*参数名1=参数值1&参数名2=参数值2&……&参数名n=参数值n&key=key值
		**/
		//组织订单信息
		$signStr = "";
		
		if($bank_seq_no != ""){
			$signStr = $signStr."bank_seq_no=".$bank_seq_no."&";
		}
		if($extra_return_param != ""){
			$signStr = $signStr."extra_return_param=".$extra_return_param."&";
		}	
		$signStr = $signStr."interface_version=".$interface_version."&";	
		$signStr = $signStr."merchant_code=".$merchant_code."&";
		$signStr = $signStr."notify_id=".$notify_id."&";
		$signStr = $signStr."notify_type=".$notify_type."&";
	    $signStr = $signStr."order_amount=".$order_amount."&";	
	    $signStr = $signStr."order_no=".$order_no."&";	
	    $signStr = $signStr."order_time=".$order_time."&";	
	    $signStr = $signStr."trade_no=".$trade_no."&";	
	    $signStr = $signStr."trade_status=".$trade_status."&";
		if($trade_time != ""){
			$signStr = $signStr."trade_time=".$trade_time."&";
		}
		$key=$this->sign;
		$signStr = $signStr."key=".$key;
		$signInfo = $signStr;
		//将组装好的信息MD5签名
		$merSign = MD5($signInfo);
		if($dinpaySign==$merSign) //签名验证通过
		{
			return true;
		}
		else
		{
			$this->message = "签名不正确！";
			return false;
		}
	}
	//设置支付金额
	public function setMoney($money)
	{
		$this->order_amount = number_format($money,2,'.','');
	}



	//获取支付金额

	public function getMoney()

	{

		return $this->order_amount;

	}



	//设置订单id

	public function setOrderId($id)

	{

		$this->order_no = $id;

	}



	//获取订单id

	public function getOrderId()

	{

		return $this->order_no;

	}



	//设置支付返回地址

	public function setServerurl($url)

	{

		$this->notify_url  = $url;

	}



	//设置浏览器跳转地址

	public function setLocationUrl($url)

	{

	 	$this->return_url  = $url;

	}

	

	//是否支持银行直连

	public function isSupportCredit()

	{

		return $this->isSupportCredit;

	}



	//返回支付失败的提示信息

	public function getMessage()

	{

		return '支付失败';

	}

	//提供的直连银行的列表

	public static function getBankList(){

	   return array(
	   	
	    'ABC'=>'中国农业银行',
	    'ICBC'=>'中国工商银行',
        'CCB'=>'中国建设银行',
	    'BOCOM'=>'交通银行',
        'BOC'=>'中国银行',
	    'CMB'=>'招商银行',
        'CMBC'=>'民生银行',
	    'CEBB'=>'光大银行', 
        'CIB'=>'兴业银行', 
	    'PSBC'=>'中国邮政', 
        'SPABANK'=>'平安银行',
        'ECITIC'=>'中信银行', 
        'GDB'=>'广东发展银行',	 
	    'SPDB'=>'浦发银行',
        'HXB'=>'华夏银行',
        'BEA'=>'东亚银行',
        'CMPAY'=>'中国移动手机支付',
	    'ZYC'=>'代金券支付',

	   );

	}



	//设置直连的银行

	public function setCreditBank($bank){

	    $this->bank = $bank;

	}



	//返回当前直连银行的中文名称

	public function getCreditBankName(){

	    $bankList = ZhiFu::getBankList();

		return $bankList[$this->bank];

	}



}

?>