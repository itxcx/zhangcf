<?php
import("COM.Interface.PayInterface");

/*
* 盛付通 支付类
*
*/
class ShengPay implements PayInterface{
	
	//接口对接静态信息
	public static $pay_interface=array(
								//支付接口中文名
								'pay_cname'=>'盛付通',
								//支付接口英文名
								'pay_ename'=>'ShengPay',
								//支付接口简介
								'synopsis'=>'上海盛付通电子支付服务有限公司（简称“盛付通”）是国内领先的独立第三方支付平台，由盛大集团创办，致力于为互联网用户和商户提供“安全、便捷、稳定”的支付服务。',
								//支付接口版本
								'version'=>'1.0v',
								//所有支付接口，统一使用的异步接口(网站根目录)
								'return_url'=>'/Pay_return.php',
								//支付接口接收服务器返回值的订单KEY名
								'order_key'=>'OrderNo'
								);
	
	//正式支付地址
	public $pay_url				= 'https://mas.sdo.com/web-acquire-channel/cashier.htm';

	//测试API地址
	//public $api_url			= 'https://api-3t.sandbox.paypal.com/nvp';


	//正式API地址
	public $api_url				= 'https://mas.sdo.com/web-acquire-channel/cashier.htm';
	public $version				= 'V4.1.1.1.1';			//版本
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
	public $ServerLocationUrl	= '';				//代理收发服务URL
	public $isSupportCredit		= false;			//是否支持银行直连
	public $bank				= '';				//直连的银行
	public $PayType				= 'PT001';			//支付渠道  直连时必须是PTOO1
	public $PayChannel			= '19';				//19 储蓄卡，20 信用卡 12企业网银
	public $pageUrl				= '';				//
	private $payHost;
	private $debug=false;
	private $key='shengfutongSHENGFUTONGtest';
	private $params=array(
		'Name'=>'B2CPayment',
		'Version'=>'V4.1.1.1.1',
		'Charset'=>'UTF-8',
		'MsgSender'=>'100894',
		'SendTime'=>'',
		'OrderNo'=>'',
		'OrderAmount'=>'',
		'OrderTime'=>'',
		'PayType'=>'PT001',
		'PayChannel'=>'19', /*（19 储蓄卡，20 信用卡）做直连时，储蓄卡和信用卡需要分开*/
		'InstCode'=>'ICBC,ABC',  /*银行编码，参看接口文档*/
		'PageUrl'=>'',
		'NotifyUrl'=>'',
		'ProductName'=>'',
		'BuyerContact'=>'',
		'BuyerIp'=>'',
		'Ext1'=>'',
		'Ext2'=>'',
		'SignType'=>'MD5',
		'SignMsg'=>'',
	);
	public $payname		= '';
	/*
	* 构造函数
	*/
	function __construct() 
	{
		//读取接口表中的pay_type
		$arr = M('pay_onlineaccount',' ')->where(array('pay_type'=>'ShengPay'))->order("pay_amount asc,id desc")->find();
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
		$Model = M();
		
		//币种
		$currency = $data[self::$pay_interface['pay_ename'].'_name'];
		$this->currency = $currency?$currency:'';

		//api帐号
		$username =$data[self::$pay_interface['pay_ename'].'_account'];
		$this->username = $username?$username:'';

		//api密码
		$password =$data[self::$pay_interface['pay_ename'].'_key'];
		$this->password = $password?$password:'';
		//代理url
		$proxy =$data[self::$pay_interface['pay_ename'].'_proxy']; 

		$this->ServerLocationUrl = $proxy?$proxy:'';
		//是否支持直连
		$credit = $data[self::$pay_interface['pay_ename'].'_credit'];
		$this->isSupportCredit = $credit=='1'?true:false;
		}
	}


	//返回支付接口中文名称
	public static function getName()
	{
		return '盛付通支付';
	}

	//返回接口中文介绍
	public static function getMemo()
	{
		return '盛付通是盛大网络创办的中国领先的在线支付平台，致力于为互联网用户和企业提供便捷、安全的支付服务。通过与各大银行、通信服务商等签约合作，提供具备相当实力和信誉保障的支付服务。';
	}

	//返回需要配置的项
	public static function getConfigInfo()
	{
		return array(
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_name',
				'config_value'=> '盛付通',
				'name'=>'支付方式名称',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_account',
				'config_value'=> '',
				'name'=>'盛付通商户号',
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
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_proxy',
				'config_value'=> '',
				'name'=>'php转发Url',
				'type'=>'text',
				'style'=>'width:350px',
				'memo'=>'<a href="/Admin/Common/pay_location.php.txt" target="_blank">下载php转发文件</a>',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_credit',
				'config_value'=> '0',
				'name'=>'银行直连',
				'type'=>'radio',
				'options'=>array(
					'Yes'=>'使用',
					'No'=>'不使用'
				)
			),
		);
	}


	//提交表单
	public function submit()
	{
		$bank = '';
		$PayChannel = '';
		$PayType = '';
		if($this->isSupportCredit)
		{
			$bank = $this->bank?$this->bank:$bank;
			$PayChannel = $this->PayChannel;
			$PayType= $this->PayType;
		}
	
		$pageUrl = $this->pageUrl=='' ? 'http://'.$_SERVER['SERVER_NAME'].($serverPort!=80?":{$serverPort}":'').$_SERVER['REQUEST_URI'] : $this->pageUrl;
		
		$array=array(
			'Name'=>'B2CPayment',
			'Version'=>'V4.1.1.1.1',
			'Charset'=>'UTF-8',
			'MsgSender'=>$this->username,
			'SendTime'=>date('YmdHis'),
			'OrderTime'=>date('YmdHis'),
			'PayType'=>$PayType,
			'PayChannel'=>$PayChannel,/*（19 储蓄卡，20 信用卡）做直连时，储蓄卡和信用卡需要分开*/
			'InstCode'=>$bank,
			// 银行编码，参考接口文档
			'PageUrl'=>$pageUrl,
			'NotifyUrl'=>$this->return_url,
			'ProductName'=>'',//'盛付通支付接口测试',
			'BuyerContact'=>'',
			'BuyerIp'=>get_client_ip(),
			'Ext1'=>'',
			'Ext2'=>'',
			'SignType'=>'MD5',
		);
	
		$this->init($array);
		$this->setKey($this->password);
		
	
		$this->cancel_url	= 'http://'.$_SERVER['SERVER_NAME'].($serverPort!=80?":{$serverPort}":'');
		$this->params['OrderNo']=$this->orderId;
		$this->params['OrderAmount']=$this->amount;
		$origin='';
		foreach($this->params as $key=>$value){
			if(!empty($value))
				$origin.=$value;
		}
		$SignMsg=strtoupper(md5($origin.$this->key));
		$this->params['SignMsg']=$SignMsg;

		$_action_url			= $this->payHost;
		$_location_url			= '';
		//是否使用代理跳转
		if(	$this->ServerLocationUrl != '' )
		{
			$_action_url	= $this->ServerLocationUrl;
			$_location_url	= base64_encode($this->payHost);
		}
		
?>
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
	<span class="success"><?php echo $this->sendMsg; ?></span>
	</div>
	<div style="display:none">
		<form action="<?php echo $_action_url;?>" method="post" id="frm1">
		<input type="hidden" name="location_url" value="<?php echo $_location_url;?>"  />
<?php
			foreach($this->params as $key=>$value){
				echo '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
				
			}
?>
		</form>
	</div>
</div>
</body>
</html>
<script language="javascript">
document.getElementById("frm1").submit();
</script>

<?php
	}


	/*
	* 处理收到的数据
	*/
	public function receive()
	{
		
		$this->setKey($this->password);
		if($this->returnSign()){
			
			/*支付成功*/
			$orderId			= $_POST['OrderNo']; //订单id
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
			$money			=  $_POST['TransAmount'];
			$this->message	= "成功支付: {$money} 元(人民币)";
			
			echo 'OK';
			return true;
		}else{
			$this->message = '支付失败!';
			return false;
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
	
	//是否使用代理
	public function is_proxy()
	{
		return $this->ServerLocationUrl==''?false:true;
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
		return $this->isSupportCredit;
	}
	//获取
	public function getPayTypeList()
	{
		return array(
				'网银支付'=>'PT001',
				'余额支付'=>'PT002',
				'盛付通卡支付'=>'PT003',
				'一点充支付'=>'PT004',
				'手机充值卡支付'=>'PT005',
				'手机话费支付'=>'PT006',
				'固话支付'=>'PT007',
				'盛大卡支付'=>'PT008',
				'积分支付'=>'PT009',
				'银联快捷支付'=>'PT010',
				'线下支付'=>'PT011',
				'宽带支付'=>'PT012',
				'PPC预付卡支付'=>'PT013',
				'无磁无密支付'=>'PT014',
				'Pos线下银行卡'=>'PT015',
				'无卡支付'=>'PT016',
				'红包'=>	'PT017',
				'盛大互动娱乐卡'=>	'PT018',
				'银行卡委托代扣'=>'PT019',
				'盛付通娱乐一卡通'=>'PT020',
				'快捷支付'=>	'PT021',

		);
	}
	//设置支付渠道
	public function setPayChannel($channel)
	{
		$this->PayChannel = $channel;
	}
	
	//提供的直连银行的列表
	public static function getBankList()
	{
	
		return array(
			'ICBC'=>'工商银行',
			'ABC'=>'农业银行',
			'CCB'=>'建设银行',
			'CMB'=>'招商银行',
			'COMM'=>'交通银行',
			'SZPAB'=>'平安银行',
			'PSBC'=>'中国邮政储蓄银行',
			'BOC'=>'中国银行',
			'BCCB'=>'北京银行',
			'HXB'=>'华夏银行',
			'CEB'=>'光大银行',
			'BOS'=>'上海银行',
			'NBCB'=>'宁波银行',
			'WZCB'=>'温州银行',
			'BCCB'=>'北京银行',
			'CITIC'=>'中信银行'
		);

	}
	
	//设置支付类型编码
	public function setPayType($type)
	{
		$this->PayType = $type;
	}
	
	//设置直连的银行
	public function setCreditBank($bank)
	{
		$this->bank = $bank;
	}
	
	//返回当前直连银行的中文名称
	public function getCreditBankName()
	{
		$bankList = ShengPay::getBankList();
		return $bankList[$this->bank];
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

	function init($array=array()){
	
		if($this->debug)
			$this->payHost='https://mer.mas.sdo.com/web-acquire-channel/cashier.htm';
		else
			$this->payHost='https://mas.sdo.com/web-acquire-channel/cashier.htm';
		foreach($array as $key=>$value){
			$this->params[$key]=$value;
		}
	}

	function setKey($key){
		$this->key=$key;
	}
	function setParam($key,$value){
		$this->params[$key]=$value;
	}

	

	function returnSign(){
		$params=array(
			'Name'=>'',
			'Version'=>'',
			'Charset'=>'',
			'TraceNo'=>'',
			'MsgSender'=>'',
			'SendTime'=>'',
			'InstCode'=>'',
			'OrderNo'=>'',
			'OrderAmount'=>'',
			'TransNo'=>'',
			'TransAmount'=>'',
			'TransStatus'=>'',
			'TransType'=>'',
			'TransTime'=>'',
			'MerchantNo'=>'',
			'ErrorCode'=>'',
			'ErrorMsg'=>'',
			'Ext1'=>'',
			'Ext2'=>'',
			'SignType'=>'MD5',
		);
		foreach($_POST as $key=>$value){
			if(isset($params[$key])){
				$params[$key]=$value;
			}
		}
		$TransStatus=(int)$_POST['TransStatus'];
		$origin='';
		foreach($params as $key=>$value){
			if(!empty($value))
				$origin.=$value;
		}
		$SignMsg=strtoupper(md5($origin.$this->key));
		if($SignMsg==$_POST['SignMsg'] and $TransStatus==1){
			return true;
		}else{
			return false;
		}
	}





}
?>