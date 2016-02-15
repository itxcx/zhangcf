<?php
import("COM.Interface.PayInterface");

/*
* 网银在线类
*
*/
class WangYin implements PayInterface{
	
	//接口对接静态信息
	public static $pay_interface=array(
								//支付接口中文名
								'pay_cname'=>'网银在线支付',
								//支付接口英文名
								'pay_ename'=>'WangYin',
								//支付接口简介
								'synopsis'=>'网银在线（北京）科技有限公司（以下简称网银在线）为京东集团全资子公司，是国内领先的电子支付解决方案提供商，专注于为各行业提供安全、便捷的综合电子支付服务。',
								//支付接口版本
								'version'=>'1.0v',
								//所有支付接口，统一使用的异步接口(网站根目录)
								'return_url'=>'/Pay_return.php',
								//支付接口接收服务器返回值的订单KEY名
								'order_key'=>'v_oid'
								);
	
	//WangYin支付网关地址
	public $Gateway_URL			= 'https://pay3.chinabank.com.cn/PayGate';				
	//网银的商户编号
	public $v_mid  = '';
	//网银的订单编号
	public $v_oid = '';
	//网银的订单总额
	public $v_amount = 0;
	//网银的使用币种
	public $v_moneytype = 'CNY';
	//网银的异步地址
	public $ServerUrl			= '';	
	//网银在线帐号key(商户证书)			
	public $Mer_key				= '';
	//转发URL
	public $ServerLocationUrl	= '';
	//支付成功失败提示的信息
	private $message			= ''; 

	/*
	* 构造函数
	*/
	function __construct() 
	{
			//读取接口表中的pay_type
		$arr = M('pay_onlineaccount',' ')->where(array('pay_type'=>'WangYin'))->order("pay_amount asc,id desc")->find();
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
		//账号
		$account					= $data[self::$pay_interface['pay_ename'].'_account'];
		//秘钥
		$key						= $data[self::$pay_interface['pay_ename'].'_key'];
		//转发url
		$proxy						= $data[self::$pay_interface['pay_ename'].'_proxy'];
		$this->v_mid				= $account?$account:'';
		$this->Mer_key				= $key?$key:'';
		$this->ServerLocationUrl	= $proxy?$proxy:'';
		}
	}
	

	//设置支付返回地址
	public function setServerurl($url)
	{
		$this->ServerUrl  = $url;
	}

	//设置浏览器跳转地址
	public function setLocationUrl($url)
	{
	
	}

	//设置订单id
	public function setOrderId($id)
	{
		$this->v_oid = $id;
	}

	//获取订单id
	public function getOrderId()
	{
		return $this->v_oid;
	}

	//设置支付金额
	public function setMoney($money)
	{

		$this->v_amount = number_format($money,2,'.','');
	}

	//获取支付金额
	public function getMoney()
	{
		return $this->v_amount;
	}


	//返回接口名称
	public static function getName()
	{
		return '网银在线';
	}

	//返回接口描述
	public static function getMemo()
	{
		return '网银在线（北京）科技有限公司（以下简称网银在线）于2003年成立，京东集团全资子公司，为京东电商业务提供全面的支付解决方案，是国内领先的电子支付解决方案提供商，专注于为各行业提供安全、便捷的综合电子支付服务。核心业务包含支付处理（在线支付网关、网银钱包、快捷支付）及预付费卡等服务。';
	}

	//是否支持银行直连
	public function isSupportCredit()
	{
		return false;
	}

	//设置直连的银行
	public function setCreditBank($bank)
	{
	//	$this->bank = $bank;
	}

	/*
	* 返回配置信息
	*/
	public static function getConfigInfo()
	{
		return array(
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_name',
				'config_value'=> '网银在线',
				'name'=>'支付方式名称',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_account',
				'config_value'=> '',
				'name'=>'商户编号',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_key',
				'config_value'=> '',
				'name'=>'商户密钥',
				'type'=>'text',
				'style'=>'width:400px',
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

	//提供的直连银行的列表
	public static function getBankList()
	{
		return array();
	}

	//返回银行名称
	public function getCreditBankName()
	{
		$bankList = Huanxun::getBankList();
		return $bankList[$this->bank];
	}

	//是否使用代理
	public function is_proxy()
	{
		return $this->ServerLocationUrl==''?false:true;
	}

	/*
	* 发送充值请求
	*/
	public function submit()
	{

		$_action_url			= $this->Gateway_URL;
		$_location_url			= '';
		$text = $this->v_amount.$this->v_moneytype.$this->v_oid.$this->v_mid.$this->ServerUrl.$this->Mer_key;
        $v_md5info = strtoupper(md5($text));
		//是否使用代理跳转
		if(	$this->ServerLocationUrl != '' )
		{
			$_action_url	= $this->ServerLocationUrl;
			$_location_url	= base64_encode($this->Gateway_URL);
		}
		$other_content			= '';
		print <<<EOF
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gb2312">
<title>在线支付接口</title>

<link href="css/index.css" rel="stylesheet" type="text/css">
</head>
<body onLoad="javascript:document.E_FORM.submit()">
<form method="post" name="E_FORM" action="{$_action_url}">
	<input type="hidden" name="v_mid"         value="{$this->v_mid}">
	<input type="hidden" name="v_oid"         value="{$this->v_oid}">
	<input type="hidden" name="v_amount"      value="{$this->v_amount}">
	<input type="hidden" name="v_moneytype"   value="{$this->v_moneytype}">
	<input type="hidden" name="v_url"         value="{$this->ServerUrl}">
	<input type="hidden" name="v_md5info"     value="{$v_md5info}">
 
 <!--以下几项项为网上支付完成后，随支付反馈信息一同传给信息接收页 -->	
	
	<input type="hidden" name="remark1"       value="">
	<input type="hidden" name="remark2"       value="">



<!--以下几项只是用来记录客户信息，可以不用，不影响支付 -->
	<input type="hidden" name="v_rcvname"      value="">
	<input type="hidden" name="v_rcvtel"       value="">
	<input type="hidden" name="v_rcvpost"      value="">
	<input type="hidden" name="v_rcvaddr"      value="">
	<input type="hidden" name="v_rcvemail"     value="">
	<input type="hidden" name="v_rcvmobile"    value="">

	<input type="hidden" name="v_ordername"    value="">
	<input type="hidden" name="v_ordertel"     value="">
	<input type="hidden" name="v_orderpost"    value="">
	<input type="hidden" name="v_orderaddr"    value="">
	<input type="hidden" name="v_ordermobile"  value="">
	<input type="hidden" name="v_orderemail"   value="">
    <input type=submit value="网银在线支付">
</form>
				

</BODY></HTML>
<script language="javascript">
document.getElementById("frm1").submit();
</script>
EOF;
		exit;
	}
	

	public function getMessage()
	{
		return $this->message;
	}

	/*
	* 处理收到的数据
	*/
	public function receive()
	{
	        $v_mid = $this->v_mid;//商户编号
	        //秘钥
	        $miyao   = $this->Mer_key;
	        //返回URL
	        $v_url  = $this->ServerUrl;
			$v_oid     =trim($_REQUEST['v_oid']);       // 商户发送的v_oid定单编号   
			$v_pmode   =trim($_REQUEST['v_pmode']);    // 支付方式（字符串）   
			$v_pstatus =trim($_REQUEST['v_pstatus']);   //  支付状态 ：20（支付成功）；30（支付失败）
			$v_pstring =trim($_REQUEST['v_pstring']);   // 支付结果信息 ： 支付完成（当v_pstatus=20时）；失败原因（当v_pstatus=30时,字符串）； 
			$v_amount  =trim($_REQUEST['v_amount']);     // 订单实际支付金额
			$v_moneytype  =trim($_REQUEST['v_moneytype']); //订单实际支付币种    
			$remark1   =trim($_REQUEST['remark1' ]);      //备注字段1
			$remark2   =trim($_REQUEST['remark2' ]);     //备注字段2
			$v_md5str  =trim($_REQUEST['v_md5str' ]);   //拼凑后的MD5校验值  
				
			$md5string=strtoupper(md5($v_oid.$v_pstatus.$v_amount.$v_moneytype.$miyao));

		//md5摘要不一样
		if( $v_md5str!=$md5string)
		{
			$this->message = '校验失败,数据可疑!';
			return false;
		}
		//md5摘要一样
		else
		{
			if( $v_pstatus=="20" )	//支付成功
			{
			
				$this->message = "支付成功!查看支付订单，请点击 <a href='https://www.ruitebi.com/index.php?s=/User/Fun_pay/paylist'>返回系统</a>";
				return true;
			}
			else
			{
				$this->message = '支付失败!';
				return false;
			}
		}
	}

}
?>