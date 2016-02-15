<?php
import("COM.Interface.PayInterface");

/*
* 汇付天下 支付类
*
*/
class Huifu implements PayInterface{
	
	//接口对接静态信息
	public static $pay_interface=array(
								//支付接口中文名
								'pay_cname'=>'汇付天下',
								//支付接口英文名
								'pay_ename'=>'Huifu',
								//支付接口简介
								'synopsis'=>'汇付天下有限公司于2006年7月成立，投资额近10亿元人民币，核心团队由中国金融行业资深管理人士组成，致力于为中国小微企业、金融机构、行业客户和投资者提供金融支付、账户托管、理财平台等综合支付服务。',
								//支付接口版本
								'version'=>'1.0v',
								//所有支付接口，统一使用的异步接口(网站根目录)
								'return_url'=>'/Pay_return.php',
								//支付接口接收服务器返回值的订单KEY名
								'order_key'=>'OrdId'
								);
	
	public $gateway_url			= 'http://mas.chinapnr.com/gar/RecvMerchant.do';	//ezybonds支付网关地址
	public $version				= 10;				//版本号
	public $cmdId				= "Buy";			//消息类型
	public $orderId				= 0;				//订单号
	public $amount				= 0;				//金额
	public $currency			= 'RMB';			//支付币种
	public $productid			= '';				//产品编号
	public $private_data		= '';				//商户私有数据项(做为自定义加密串)
	public $gateId				= '';				//银行网关号
	public $mobile				= '';				//用户手机号
	public $detail				= '';				//分账明细
	public $pay_userid			= '';				//付款人用户号
	public $accountid			= '';				//汇付天下 收款帐号ID
	public $sign_type			= '';				//签名方式 
	public $com					= '';				//COM组件名称
	public $gateway				= '';				//签名网关
	public $private_key			= '';				//签名私钥文件
	public $pubic_key			= '';				//签名公钥文件
	public $page_notify_url		= '';				//支付结果浏览器通知URL,完整路径带http
	public $server_notify_url	= '';				//支付结果服务器通知URL,完整路径带http
	private $errorMsg			= '支付失败';  //错误消息
	public $sendMsg				= '在线充值的时候请不要关闭页面！充值成功后页面自动跳转..';	//发送充值时的提示
   public $payname		= '';

	/*
	* 构造函数
	*/
	function __construct() 
	{
			//读取接口表中的pay_type
		$arr = M('pay_onlineaccount',' ')->where(array('pay_type'=>'Huifu'))->order("pay_amount asc,id desc")->find();
		if($arr){
		//将$arr['pay_attr'] 返序列化
	    $data_arr = unserialize($arr['pay_attr']);//是一个二维数组 
	    //查询金额最小的金额的记录
	      $data = array();
		   foreach($data_arr as $key=>$v){
		         $data[$key] = $v;
		   }
		
		$accountid					= $data[self::$pay_interface['pay_ename'].'_account'];
		$this->accountid			= $accountid?$accountid:'';

		$sign_type					= $data[self::$pay_interface['pay_ename'].'_sign_type'];
		$this->sign_type			= $sign_type?$sign_type:'';

		$com						= $data[self::$pay_interface['pay_ename'].'_com'];
		$this->com					= $com?$com:'';

		$gateway					= $data[self::$pay_interface['pay_ename'].'_gateway'];
		$this->gateway				= $gateway?$gateway:'';

		$private_key				= $data[self::$pay_interface['pay_ename'].'_private_key'];
		$this->private_key			= $private_key?$private_key:'';

		$pubic_key					= $data[self::$pay_interface['pay_ename'].'_pubic_key'];
		$this->pubic_key			= $pubic_key?$pubic_key:'';
		}
	}


	//返回支付接口中文名称
	public static function getName()
	{
		return '汇付天下';
	}

	//返回接口中文介绍
	public static function getMemo()
	{
		return '汇付天下于2006年7月成立，总部设于上海，并在北京、广州、深圳、成都等10多个城市设立分公司。汇付天下投资额近10亿元人民币，核心团队由中国金融行业资深管理人士组成。2011年5月汇付天下首批获得央行颁发的《支付业务许可证》，首家获得证监会批准开展网上基金销售支付结算业务，是中国支付清算协会网络支付工作委员会副理事长单位';
	}

	//返回需要配置的项
	public static function getConfigInfo()
	{
		return array(
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_name',
				'config_value'=> '汇付天下',
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
				'config_name'=>self::$pay_interface['pay_ename'].'_sign_type',
				'config_value'=> 'com',
				'name'=>'签名方式',
				'type'=>'radio',
				'options'=>array(
					'com'=>'COM组件',
					'gateway'=>'服务器'
				),
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_com',
				'config_value'=> 'CHINAPNR.NetpayClient',
				'name'=>'COM组件名称',
				'type'=>'text',
				'memo'=>'<a style="color:red" href="/Admin/Common/huifu/dll.zip">下载COM组件</a>',
				'style'=>'width:200px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_gateway',
				'config_value'=> '127.0.0.1:9733',
				'name'=>'服务器IP端口',
				'type'=>'text',
				'memo'=>'<span style="color:#555555">例如:127.0.0.1:9733 <a style="color:red" href="/Admin/Common/huifu/server.zip">下载服务器套件</a></span>',
				'style'=>'width:200px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_private_key',
				'config_value'=> '',
				'name'=>'签名私钥文件',
				'type'=>'text',
				'memo'=>'<span style="color:#555555">相对于网站根目录的位置</span>',
				'style'=>'width:250px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_pubic_key',
				'config_value'=> '',
				'name'=>'签名公钥文件',
				'type'=>'text',
				'memo'=>'<span style="color:#555555">例如:keys/PgPubk.key</span>',
				'style'=>'width:250px',
			),
		);
	}

	//返回验证字符串
	private function getAuthData()
	{
		$data =  $this->version.$this->cmdId.$this->accountid.$this->orderId.$this->amount.$this->currency;
		$data .= $this->productid.$this->page_notify_url.$this->private_data.$this->gateId.$this->mobile;
		$data .= $this->detail.$this->pay_userid.$this->server_notify_url;
		return $data;
	}

	//提交验证
	private function getAuth()
	{
		if( $this->sign_type == 'com' )
		{
			//加签 
			$SignObject		= new COM("CHINAPNR.NetpayClient");
			$data		= $this->getAuthData();
			$MerFile		= ROOT_PATH.$this->private_key;

			$ChkValue		= $SignObject->SignMsg0($this->accountid,$MerFile,$data,strlen($data));
			return $ChkValue;
		}
		else if( $this->sign_type == 'gateway' )
		{
			$gateway_data = explode(":",$this->gateway);
			$fp = fsockopen($gateway_data[0], $gateway_data[1], $errno, $errstr, 10);
			if (!$fp) 
			{
				exit( "Huifu Error: $errstr ($errno)" );
			} 
			else 
			{
				$data		= $this->getAuthData();
				$data_len	= strlen($data);
				if($data_len < 100 )
				{
					$data_len = '00'.$data_len;
				}
				elseif($data_len < 1000 )
				{
					$data_len = '0'.$data_len;
				}

				$out = 'S'.$this->accountid.$data_len.$data;

				$out_len = strlen($out);
				if($out_len < 100 )
				{
					$out_len = '00'.$out_len;
				}
				elseif($out_len < 1000 )
				{
					$out_len = '0'.$out_len;
				}
				$out =$out_len.$out;
				fputs($fp, $out);

				
				while (!feof($fp)) {
					$ChkValue .= fgets($fp, 128);
				}

				$ChkValue = substr($ChkValue, 15,256);
				fclose($fp);

				return $ChkValue;
			}
		}
		return '';
	}



	//提交表单
	public function submit()
	{
		$this->private_data = $this->my_auth($this->accountid,$this->orderId,$this->amount,$this->currency,$this->version);
		$auth = $this->getAuth();
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
		<input type=hidden name="Version" value="{$this->version}" />
		<input type=hidden name="CmdId" value="{$this->cmdId}" />
		<input type=hidden name="MerId" value="{$this->accountid}" />
		<input type=hidden name="OrdId" value="{$this->orderId}" />
		<input type=hidden name="OrdAmt" value="{$this->amount}" />
		<input type=hidden name="CurCode" value="{$this->currency}" />
		<input type=hidden name="Pid" value="{$this->productid}" />
		<input type=hidden name="RetUrl" value="{$this->page_notify_url}" />
		<input type=hidden name="BgRetUrl" value="{$this->server_notify_url}" />
		<input type=hidden name="MerPriv" value="{$this->private_data}" />
		<input type=hidden name="GateId" value="{$this->gateId}" />
		<input type=hidden name="UsrMp" value="{$this->mobile}" />
		<input type=hidden name="DivDetails" value="{$this->detail}">
		<input type=hidden name="PayUsrId" value="{$this->pay_userid}">
		<input type=hidden name="ChkValue" value="{$auth}" />
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
	
	//自定义加密串
	private function my_auth($MerId,$OrdId,$OrdAmt,$CurCode,$version)
	{
		return md5($MerId.'x'.$OrdId.'s'.$OrdAmt.'y'.$CurCode.'t'.$version);
	}


	//接收验证
	private function checkReceiveAuth()
	{
		$CmdId		= $_POST['CmdId'];			//消息类型
		$MerId		= $_POST['MerId']; 	 		//商户号
		$OrdAmt		= $_POST['OrdAmt']; 		//金额
		$CurCode	= $_POST['CurCode']; 		//币种
		$OrdId		= $_POST['OrdId'];  		//订单号
		$MerPriv	= $_POST['MerPriv'];  		//商户私有域

		if( $MerPriv ==  $this->my_auth($MerId,$OrdId,$OrdAmt,$CurCode,$this->version) )
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/*
	* 处理收到的数据
	*/
	public function receive()
	{
		$RespCode	= $_POST['RespCode']; 		//应答返回码
		if( $this->checkReceiveAuth() )
		{
			if( $RespCode == '000000' )
			{
				
				$this->errorMsg = '支付成功';
				return true;  //支付成功
			}
			else
			{
				$this->errorMsg = '接口返回支付失败';
				return false;	//支付失败
			}
		}
		else
		{
			$this->errorMsg = '签名验证失败';
			return false;	//签名验证失败
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
		$this->page_notify_url  = $url;
		$this->server_notify_url  = $url;
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
		return $this->errorMsg;
	}


	//提供的直连银行的列表
	public static function getBankList(){}

	//设置直连的银行
	public function setCreditBank($bank){}

	//返回当前直连银行的中文名称
	public function getCreditBankName(){}

}
?>