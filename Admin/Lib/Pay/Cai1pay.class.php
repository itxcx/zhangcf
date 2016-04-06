<?php
import("COM.Interface.PayInterface");

/*
* 财易付
*
*/
class Cai1pay implements PayInterface{
	
	//接口对接静态信息
	public static $pay_interface=array(
	//支付接口中文名
	'pay_cname'=>'财易付',
	//支付接口英文名
	'pay_ename'=>'Cai1pay',
	//支付接口简介
	'synopsis'=>'财易付是一个功能强大基于网络的在线支付平台，由公司投入大量资金和科研实力开发完成，以其自身个性化的设计为客户提供了安全、多渠道、多语言的在线支付服务。',
	//支付接口版本
	'version'=>'1.0.1',
	//所有支付接口，统一使用的异步接口(网站根目录)
	'return_url'=>'/Pay_return.php',
	//支付接口接收服务器返回值的订单KEY名
	'order_key'=>'MerOrderNo'
	);

    public $Gateway_URL			= 'https://payment.cai1pay.com/gateway.aspx';				//财易付支付地址
	//public $Gateway_URL			= 'http://testpay.cai1pay.com/gateway.aspx';				//财易付测试地址
    public    $MerCode         = '';     //商户在财易付开通的交易账户号
    public    $MerCert         = '';     //证书
    public    $MerOrderNo      = '';	 //商户订单编号
    public    $Amount          = '';     //订单金额
    public    $OrderDate       = '';     //订单日期 
    public    $Currency        = 'RMB';  //币种
    public    $GatewayType     = '01';     //支付方式
    public    $Language        = 'GB';   //语言 
    public    $ReturnUrl       = '';     //支付结果成功返回的商户URL
    public    $GoodsInfo       = '';    //商品描述信息
    public    $OrderEncodeType = '2';    //订单支付接口加密方式  1：订单支付采用RSA认证方式 2：订单支付采用MD5的摘要认证方式
    public    $RetEncodeType   = '12';   //交易返回接口加密方式  11：交易返回采用RSA的签名认证方式 12：交易返回采用Md5的摘要认证方式
    public    $Rettype         = '1';    //是否选择Server to Server返回方式  0：不选 1：选择
    public    $ServerUrl       = '';     //Server to Server返回页面
    public    $SignMD5         = '';     //订单支付接口的Md5摘要  Sigmd5=订单号+金额(保留2位小数)+日期+支付币种+财易付证书
    public    $DoCredit        = '1';    //是否直连  当DoCredit=1，表示用直连方式
    public    $BankCode        = '';     //银行编码  当用直连方式时，这个银行编码不能为空
  

	/*
	* 构造函数
	*/
	function __construct() 
	{
		//读取接口表中的pay_type
		$arr = M('pay_onlineaccount',' ')->where(array('pay_type'=>self::$pay_interface['pay_ename']))->order("pay_amount asc,id desc")->find();
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
			$account					= $data[self::$pay_interface['pay_ename'].'_account'];
			$key						= $data[self::$pay_interface['pay_ename'].'_key'];
			$credit						= $data[self::$pay_interface['pay_ename'].'_credit'];
			$proxy						= $data[self::$pay_interface['pay_ename'].'_proxy'];
			//商城转发url
			$this->ServerLocationUrl	= $proxy?$proxy:'';
			$this->MerCode				= $account?$account:'';     //账户号
			$this->MerCert				= $key?$key:'';             //证书
			$this->DoCredit		        = $credit=='Yes'?1:0;
			//$siteDomain	= $this->getSiteDomain();
			//设置ReturnUrl
			//$this->ReturnUrl = $siteDomain .'/payrecive.php?s=/Payment/receive';			
		}
    }
 	//获取当前的域名带 协议
	private function getSiteDomain()
	{
		$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

		return $http_type.$_SERVER['HTTP_HOST'];
	}	

	//是否使用代理
	public function is_proxy()
	{
		return $this->ServerLocationUrl==''?false:true;
	}
	//设置支付返回地址
	public function setServerurl($url)
	{
		$this->ServerUrl  = $url;
	}

	//设置浏览器跳转地址
	public function setLocationUrl($url)
	{
		$this->Gateway_URL = $url;
	}

	//设置订单id
	public function setOrderId($id)
	{
		$this->MerOrderNo = $id;
	}

	//获取订单id
	public function getOrderId()
	{
		return $this->MerOrderNo;
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


	//返回接口名称
	public static function getName()
	{
		return '财易付';
	}
	
    //返回支付失败的提示信息
	public function getMessage()
	{
		return $this->message;
	}

	//返回接口描述
	public static function getMemo()
	{
		return '财易付(www.Cai1pay.com)';
	}

	//是否支持银行直连
	public function isSupportCredit()
	{
		return $this->DoCredit;
	}

	//设置直连的银行
	public function setCreditBank($bank)
	{
		$this->BankCode = $bank;
	}

	/*
	* 返回配置信息
	*/
	public static function getConfigInfo()
	{
		$ServerUrl =  '';
		return array(
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_name',
				'config_value'=> '财易付',
				'name'=>'支付方式名称',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_account',
				'config_value'=> '',
				'name'=>'交易账户',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_key',
				'config_value'=> '',
				'name'=>'商户证书',
				'type'=>'text',
				'style'=>'width:500px',
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

	//提供的直连银行的列表
	public static function getBankList()
	{
		return array(
			'01100'=>	'工商银行',
			'01101'=>	'农业银行',
			'01106'=>	'建设银行',
			'01107'=>	'中国银行',
			'01102'=>	'招商银行',
			'01119'=>   '邮政储蓄',
			'01110'=>	'民生银行',
			'01121'=>	'平安银行',
			'01108'=>	'交通银行',
			'01103'=>   '兴业银行',
			'01104'=>	'中信银行',
			'01114'=>	'广东发展银行',
			'01109'=>   '浦东发展银行',
			'01111'=>	'华夏银行',
			'01112'=>	'光大银行',
			'01113'=>	'北京银行',
			'99999'=>	'银联',

		);
	}

	//返回银行名称
	public function getCreditBankName()
	{
		$bankList = self::getBankList();
		return $bankList[$this->BankCode];
	}

	/*
	* 发送请求
	*/
	public function submit()
	{
		$_action_url = $this->Gateway_URL;
		//是否使用代理跳转
		if(	$this->ServerLocationUrl != '' )
		{
			$_action_url	= $this->ServerLocationUrl;
			$_location_url	= base64_encode($this->Gateway_URL);
		}
		//设置ServerUrl
		if($this->Rettype == '1'){
			$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        		
        	//付完款后跳转的页面 要用 http://格式的完整路径，不允许加?id=123这类自定义参数
        	$return_url = $http_type.$_SERVER['HTTP_HOST']."/payrecive.php";
			$this->ServerUrl .= $return_url;
		}
		//订单日期
		$this->OrderDate = date('Ymd');
		//md5签名
		if($this->OrderEncodeType== '2'){
			$this->SignMD5 = md5($this->MerOrderNo . $this->Amount . $this->OrderDate . $this->Currency . $this->MerCert);
		}
		//银行编码
		if($this->DoCredit== 0 ){
			$this->BankCode= '';
		}
		?>
			<html>
			  <head>
			    <title>跳转......</title>
			    <meta http-equiv="content-Type" content="text/html; charset=gb2312" />
			  </head>
			  <body>
			    <form action="<?php echo $_action_url; ?>" method="post" id="frm1">
			      <input type="hidden" name="MerCode" value="<?php echo $this->MerCode ?>">
			      <input type="hidden" name="MerOrderNo" value="<?php echo $this->MerOrderNo ?>">
			      <input type="hidden" name="Amount" value="<?php echo $this->Amount ?>" >
			      <input type="hidden" name="OrderDate" value="<?php echo $this->OrderDate ?>">
			      <input type="hidden" name="Currency" value="<?php echo $this->Currency ?>">
			      <input type="hidden" name="GatewayType" value="<?php echo $this->GatewayType ?>">
			      <input type="hidden" name="Language" value="<?php echo $this->Language ?>">
			      <input type="hidden" name="ReturnUrl" value="<?php echo $this->ReturnUrl ?>">
			      <input type="hidden" name="GoodsInfo" value="<?php echo $this->GoodsInfo ?>">
			      <input type="hidden" name="OrderEncodeType" value="<?php echo $this->OrderEncodeType ?>">
			      <input type="hidden" name="RetEncodeType" value="<?php echo $this->RetEncodeType ?>">
			      <input type="hidden" name="Rettype" value="<?php echo $this->Rettype ?>">
			      <input type="hidden" name="ServerUrl" value="<?php echo $this->ServerUrl ?>">
			      <input type="hidden" name="SignMD5" value="<?php echo $this->SignMD5 ?>">
			   	  <input type="hidden" name="DoCredit" value="<?php echo $this->DoCredit ?>">
			      <input type="hidden" name="BankCode" value="<?php echo $this->BankCode ?>">
				<?php if($this->ServerLocationUrl != ''){ ?>
				  <input type="hidden" name="location_url" value="<?php echo $_location_url; ?>" />
				<?php }?>
			    </form>
			    <script language="javascript">
			      document.getElementById("frm1").submit();
			    </script>
			  </body>
			</html>
		<?php 
     }
	
	/*
	* 处理服务器收到的数据
	*/
	public function receive()
	{
		//接收数据
		$billno = $_REQUEST['MerOrderNo'];
		$amount = $_REQUEST['Amount'];
		$mydate = $_REQUEST['OrderDate'];
		$succ = $_REQUEST['Succ'];
		$msg = $_REQUEST['Msg'];
		$attach = $_REQUEST['GoodsInfo'];
		$ipsbillno = $_REQUEST['SysOrderNo'];
		$retEncodeType = $_REQUEST['RetencodeType'];
		$currency_type = $_REQUEST['Currency'];
		$signature = $_REQUEST['Signature'];

		//交易返回Md5摘要认证
		$content = $billno . $amount . $mydate . $succ . $ipsbillno . $currency_type;
		//证书
		$cert = $this->MerCert;
		$signature_1ocal = md5($content . $cert);
		if($signature_1ocal == $signature){
			if ($succ == 'Y'){
				M('Fun_pay')->success($billno);
			    $this->message = '交易成功';				
			}else{
				M('Fun_pay')->fail($billno);
			    $this->message = '交易失败';			
			}
		}else{
			$this->message = '签名不正确';	
		}
	}

	/*
	* 处理浏览器收到的数据
	*/
	public function _receive()
	{
		//接收数据
		$billno = $_GET['MerOrderNo'];
		$amount = $_GET['Amount'];
		$mydate = $_GET['OrderDate'];
		$succ = $_GET['Succ'];
		$msg = $_GET['Msg'];
		$attach = $_GET['GoodsInfo'];
		$ipsbillno = $_GET['SysOrderNo'];
		$retEncodeType = $_GET['RetencodeType'];
		$currency_type = $_GET['Currency'];
		$signature = $_GET['Signature'];

		//交易返回Md5摘要认证
		$content = $billno . $amount . $mydate . $succ . $ipsbillno . $currency_type;
		//证书
		$cert = $this->MerCert;
		$signature_1ocal = md5($content . $cert);
		if($signature_1ocal == $signature){
			if ($succ == 'Y'){
			    $this->message = '交易成功';				
			}else{
			    $this->message ='交易失败';			
			}
		}else{
			$this->message = '签名不正确';	
		}
	}

   private function getMerInfo($mercode){
      $arrayMer=array (
         'MerCode'=>$this->MerCode,
         'MerCert'=>$this->MerCert
      );          
     return $arrayMer;
   }

}
?>