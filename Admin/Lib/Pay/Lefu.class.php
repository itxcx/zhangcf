<?php
import("COM.Interface.PayInterface");

/*
* 乐富支付类
*
*/
class Lefu implements PayInterface{
	
	//接口对接静态信息
	public static $pay_interface=array(
								//支付接口中文名
								'pay_cname'=>'乐富支付',
								//支付接口英文名
								'pay_ename'=>'Lefu',
								//支付接口简介
								'synopsis'=>'乐富支付有限公司，是经中国人民银行认可并颁发了《支付业务许可证》的国内优秀第三方支付公司。公司于2011年7月在云南注册成立，注册资金1.05亿元人民币，目前在全国共设有28个分公司。公司以银行卡收单业务为核心业务，在全国近32个城市共布放超过150万台POS终端，累计合作商户超过100万户，与中国银联、中国银行、中国工商银行、中国建设银行等超过30家以上的金融机构有紧密业务合作。',
								//支付接口版本
								'version'=>'1.0v',
								//所有支付接口，统一使用的异步接口(网站根目录)
								'return_url'=>'/Pay_return.php',
								//支付接口接收服务器返回值的订单KEY名
								'order_key'=>'outOrderId'
								);
	
	public $Gateway_URL			= 'https://pay.lefu8.com/gateway/trade.htm';
	//签名
	public $key			= '';
	//账号
	public $partner			= '';
	//buyuser
	public $buyer			= '';
	//apiCode
	public $apiCode			= 'directPay';
    //currency
    public $currency = "CNY";
    
    public $inputCharset = "UTF-8";
    
    public $paymentType = "ALL";
    
    //html跳转
    public $redirectURL = "";
    
    //付完款后跳转的页面 要用 http://格式的完整路径，不允许加?id=123这类自定义参数
	public $notifyURL = "";

	public $retryFalg = "TRUE";

	public $signType = "MD5";

	public $timeout = "1D";

	public $versionCode = "1.0";
	
	//是否直连
	public $isSupportCredit=false;
	
	//订单id
	public $outOrderId='';
	
	//买家联系方式类型
	public $buyerContactType='phone';
	
	//买家联系方式
	public $buyerContact='';
	
	//支付金额
	public $Amount=0;
	
	//代理跳转
	public $_action_url='';

	/*
	* 构造函数
	*/
	function __construct() 
	{
		//读取接口表中的pay_type
		$arr = M('pay_onlineaccount',' ')->where(array('pay_type'=>'Lefu'))->order("pay_amount asc,id desc")->find();
		if($arr){
		//将$arr['pay_attr'] 返序列化
	    $data_arr = unserialize($arr['pay_attr']);//是一个二维数组 
	    //查询金额最小的金额的记录
	      $data = array();
	   foreach($data_arr as $key=>$v){
	         $data[$key] = $v;
	   }
		//读取数据库中的设置
		$Model						= M();
		$account					= $data[self::$pay_interface['pay_ename'].'_account'];
		$key						= $data[self::$pay_interface['pay_ename'].'_key'];
		$account                    = $data[self::$pay_interface['pay_ename'].'_account'];
		$key                        = $data[self::$pay_interface['pay_ename'].'_key'];
		$proxy						= $data[self::$pay_interface['pay_ename'].'_proxy'];
		$credit						= $data[self::$pay_interface['pay_ename'].'_credit'];
		$this->buyer				= '0001469347';
		$this->partner				=$account?$account:''; 
		$this->key					= $key?$key:'';
		$this->ServerLocationUrl	= $proxy?$proxy:'';
		$this->isSupportCredit		= $credit=='1'?true:false;
		}
	}
	

	//设置支付返回地址
	public function setServerurl($url)
	{
		$this->notifyURL  = $url;
	}

	//设置浏览器跳转地址
	public function setLocationUrl($url)
	{
	  //$this->htmlurl  = $url;
	}

	//设置订单id
	public function setOrderId($id)
	{
		$this->outOrderId = $id;
	}

	//获取订单id
	public function getOrderId()
	{
		return $this->outOrderId;
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
		return '乐富';
	}

	//返回接口描述
	public static function getMemo()
	{
		return '“乐富支付”是乐富POS机品牌名称。POS机的选择建议大家选择第三方支付的POS代理。';
	}

	//是否支持银行直连
	public function isSupportCredit()
	{
		return $this->isSupportCredit;
	}

	//设置直连的银行
	public function setCreditBank($bank)
	{
		$this->bank = $bank;
	}

	/*
	* 返回配置信息
	*/
	public static function getConfigInfo()
	{
		return array(
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_name',
				'config_value'=> '乐富支付',
				'name'=>'支付方式名称',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_account',
				'config_value'=> '',
				'name'=>'乐富帐号',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_key',
				'config_value'=> '',
				'name'=>'商户证书',
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
			'B2C_BOB-DEBIT_CARD'=>'北京银行',
			'B2C_BEA-DEBIT_CARD'=>'东亚银行',
			'B2C_CEB-DEBIT_CARD'=>'光大银行',
			'B2C_CGB-DEBIT_CARD'=>'广发银行',
			'B2C_HXB-DEBIT_CARD'=>'华夏银行',
			'B2C_BCM-DEBIT_CARD'=>'交通银行',
			'B2C_CMBC-DEBIT_CARD'=>'民生银行',
			'B2C_NBCB-DEBIT_CARD'=>'宁波银行',
			'B2C_NJCB-DEBIT_CARD'=>'南京银行',
			'B2C_PAB-DEBIT_CARD'=>'平安银行',
			'B2C_SPDB-DEBIT_CARD'=>'浦发银行',
			'B2C_BOS-DEBIT_CARD'=>'上海银行',
			'B2C_CDB-DEBIT_CARD'=>'成都银行',
			'B2C_CIB-DEBIT_CARD'=>'兴业银行',
			'B2C_PSBC-DEBIT_CARD'=>'邮政储蓄',
			'B2C_CMB-DEBIT_CARD'=>'招商银行',
			'B2C_ICBC-DEBIT_CARD'=>'中国工商银行',
			'B2C_CCB-DEBIT_CARD'=>'中国建设银行',
			'B2C_ABC-DEBIT_CARD'=>'中国农业银行',
			'B2C_BOC-DEBIT_CARD'=>'中国银行',
			'B2C_CNCB-DEBIT_CARD'=>'中信银行',
		);
	}
	//返回银行名称
	public function getCreditBankName()
	{
		$bankList = Lefu::getBankList();
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
		//是否使用代理跳转
		if(	$this->ServerLocationUrl != '' )
		{
			$_action_url	= $this->ServerLocationUrl;
		}
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<title>乐富支付</title>
		<style type="text/css">
			.font_content
			{
				font-family:"宋体";
				font-size:14px;
				color:#FF6600;
			}
			.font_title
			{
				font-family:"宋体";
				font-size:16px;
				color:#FF0000;
				font-weight:bold;
			}
			table
			{
				border: 1px solid #CCCCCC;
			}
		</style>
	</head>
	<?php 
		$buyerContactType = 'phone';
		
		$buyerContact = $_POST['tel'];

		$outOrderId = $this->outOrderId;

		$submitTime = date('YmdHis');

		$amount = $this->Amount;
		
		//notify_url 交易过程中服务器通知的页面 要用 http://格式的完整路径，不允许加?id=123这类自定义参数
			$notifyURL = $this->notifyURL;
			$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
		
		//付完款后跳转的页面 要用 http://格式的完整路径，不允许加?id=123这类自定义参数
		$return_url = $http_type.$_SERVER['HTTP_HOST']."/DmsAdmin/success.php";
	
		$parameter = array(
			"apiCode"		    => $this->apiCode,               			        
			"versionCode"       => $this->versionCode,
			"inputCharset"		=> $this->inputCharset,
			"signType"			=> $this->signType,
			"redirectURL"		=> $return_url,
			"notifyURL"         => $notifyURL,
			"currency"		    => $this->currency,
			"amount"	        => ($amount+0),
			"outOrderId"	    => $outOrderId,	
		//	"outOrderId"	    => '20150701174031',	
			"buyerContact"		=> $buyerContact,
			"buyer"		        => '000000000',
			"partner"	        => $this->partner,
			"paymentType"		=> $this->paymentType,
			"retryFalg"			=> $this->retryFalg,
			"submitTime"		=> $submitTime,
			"timeout"		    => $this->timeout,
			"buyerContactType"  => $buyerContactType
        );
        	
        $lefu = new lefupay_service($parameter,$this->key,$this->signType);
		$sHtmlText = $lefu->BuildForm($_action_url);

	?>

		<body>
		 <table align="center" width="350" cellpadding="5" cellspacing="0">
            <tr>
                <td align="center" class="font_title" colspan="2">订单确认</td>
            </tr>
            <tr>
                <td class="font_content" align="right">订单号：</td>
                <td class="font_content" align="left"><?php echo $outOrderId; ?></td>
            </tr>
            <tr>
                <td class="font_content" align="right">付款总金额：</td>
                <td class="font_content" align="left"><?php echo $this->Amount; ?></td>
            </tr>
            <tr>
                <td align="center" colspan="2"><?php echo $sHtmlText; ?></td>
            </tr>
        </table>
	</body>
</html>
		
		
		
		<?php
	
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
		$lefupay = new lefupay_notify();    //构造通知函数信息
        $verify_result = $lefupay->notify_verify($this->key,$this->signType);
		if($verify_result)
		{
            //请在这里加上商户的业务逻辑程序代码
			$outOrderId	 = $_REQUEST['outOrderId'];				
			$amount = $_REQUEST['amount'];
			if($_REQUEST['handlerResult']=="0000")
			{
			
				$this->message = '支付成功!';
				  return true;
			}
			else 
			{		
				    //支付失败的处理
					$this->message =  "handlerResult=".$_REQUEST['handlerResult'];
					return false;
			}

		}else{
		    $this->message = '签名验证失败!';
			return false;
		}
	}

}
/*
function  的所有的函数

*/
function build_mysign($sort_array,$key,$signType = "MD5") 
{
    $prestr = create_linkstring($sort_array);     	//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
    $prestr = $prestr.$key;							//把拼接后的字符串再与安全校验码直接连接起来
  
    $mysgin = sign($prestr,$signType);			    //把最终的字符串签名，获得签名结果
    return $mysgin;
}	

/********************************************************************************/

/**
    *把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	*$array 需要拼接的数组
	*return 拼接完成以后的字符串
*/
function create_linkstring($array) 
{
    $arg  = "";
    while (list ($key, $val) = each ($array)) 
	{
        $arg.=$key."=".$val."&";
    }
    $arg = substr($arg,0,count($arg)-2);		     //去掉最后一个&字符
    return $arg;
}

/********************************************************************************/

/**
    *除去数组中的空值和签名参数
	*$parameter 签名参数组
	*return 去掉空值与签名参数后的新签名参数组
 */
function para_filter($parameter) 
{
    $para = array();
    while (list ($key, $val) = each ($parameter)) 
	{
        if($key == "sign" || $val == "")
		{
			continue;
		}
        else
		{
			$para[$key] = $parameter[$key];
		}
    }
    return $para;
}
/********************************************************************************/

/**对数组排序
	*$array 排序前的数组
	*return 排序后的数组
 */
function arg_sort($array) 
{
    ksort($array);
    reset($array);
    return $array;
}

/********************************************************************************/

/**签名字符串
	*$prestr 需要签名的字符串
	*return 签名结果
 */
function sign($prestr,$signType) 
{
    $sign='';
    if($signType == 'MD5') 
	{
        $sign =  strtoupper(md5($prestr));
    }
	else 
	{
        die("暂不支持".$sign_type."类型的签名方式");
    }
    return $sign;
}

/*
到此结束

*/

class lefupay_notify
	{
		
	    /********************************************************************************/

		/**
		* 对notify_url的认证
		* 返回的验证结果：true/false
		*/
		 function notify_verify($key,$signType)
		 {

			  if(empty($_REQUEST)) 
			  {							//判断POST来的数组是否为空
				return false;
			  }
			  else
			  {
			  	   //清楚_URL_
			  	  if($_REQUEST['_URL_'])
			  	  unset($_REQUEST['_URL_']);
				  $post          = para_filter($_REQUEST);	//对所有POST返回的参数去空
				  $sort_post     = arg_sort($post);	    //对所有POST反馈回来的数据排序
			      $mysign  = build_mysign($sort_post,$key,$signType);   //生成签名结果

				  //判断veryfy_result是否为ture，生成的签名结果mysign与获得的签名结果sign是否一致
				 //$veryfy_result的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			    //mysign与sign不等，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
				if ($mysign == $_REQUEST["sign"]) 
				{
					return true;
				} 
				else 
				{
					return false;
			    }
			  }

		 }

	/********************************************************************************/

	/**对return_url的认证
	*return 验证结果：true/false
    */

		function return_verify($key,$signType)
		{

			 //生成签名结果
			if(empty($_REQUEST)) 
			{							//判断GET来的数组是否为空
				return false;
			}
			else 
			{
				 //清楚_URL_
			  	  if($_REQUEST['_URL_'])
			  	  unset($_REQUEST['_URL_']);
				$post          = para_filter($_REQUEST);	    //对所有GET反馈回来的数据去空
				$sort_post     = arg_sort($post);		    //对所有GET反馈回来的数据排序
				$mysign  = build_mysign($sort_post,$key,$signType);    //生成签名结果
		
		
				//判断veryfy_result是否为ture，生成的签名结果mysign与获得的签名结果sign是否一致
				//$veryfy_result的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
				//mysign与sign不等，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
				if ($mysign == $_REQUEST["sign"]) 
				{   
					return true;
				}
				else 
				{
					return false;
				}
			}
		}
}

class lefupay_service
{
	var $gateway;			//网关地址
    var $_key;				//安全校验码
    var $mysign;			//签名结果
    var $sign_type;			//签名类型
    var $parameter;			//需要签名的参数数组
    var $_input_charset;    //字符编码格式

	/**构造函数
	*从配置文件及入口文件中初始化变量
	*$parameter 需要签名的参数数组
	*$key 安全校验码
	*$sign_type 签名类型
    */

	function lefupay_service($parameter,$key,$signType) 
	{
	
        $this->gateway		= "https://pay.lefu8.com/gateway/trade.htm?";
        $this->_key  		= $key;
        $this->signType	    = $signType;
        $this->parameter	= para_filter($parameter);
		


        //获得签名结果
        $sort_array   = arg_sort($this->parameter);    //得到从字母a到z排序后的签名参数数组
        
        $this->mysign = build_mysign($sort_array,$this->_key,$this->signType);
    }

	function BuildForm($_action_url)
	{
		//GET方式传递
        $sHtml = "<form id='lefupaysubmit' name='lefupaysubmit' action='".$this->gateway."' method='post'>";
        //$sHtml = $sHtml."<input type='hidden' name='location_url' value='".$this->gateway."'/>";
		//POST方式传递（GET与POST二必选一）
		//$sHtml = "<form id='lefupaysubmit' name='lefupaysubmit' action='".$this->gateway."_input_charset=".$this->parameter['_input_charset']."' method='post'>";

        while (list ($key, $val) = each ($this->parameter)) 
		{
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>\r\n";
        }
        $sHtml = $sHtml."<input type='hidden' name='sign' value='".$this->mysign."'/>";
		$sHtml = $sHtml."<input type='hidden' name='signType' value='MD5'/>";
		//submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit' value='乐富支付确认付款'></form>";
		
		$sHtml = $sHtml."<script>document.forms1['lefupaysubmit'].submit();</script>";
			
        return $sHtml;
	}
}



?>