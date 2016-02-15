<?php
import("COM.Interface.PayInterface");

/*
* 环迅支付类
*
*/
class Huanxun implements PayInterface{
	
	//接口对接静态信息
	public static $pay_interface=array(
								//支付接口中文名
								'pay_cname'=>'环迅支付',
								//支付接口英文名
								'pay_ename'=>'Huanxun',
								//支付接口简介
								'synopsis'=>'上海环迅于2005年推出的新一代基于电子邮件的互联网多币种收付款工具。截止到目前，IPS账户具备在线充值、在线收付款、在线转账、网上退款和网上提款等多种功能，并支持多种账户充值方式。',
								//支付接口版本
								'version'=>'1.0v',
								//所有支付接口，统一使用的异步接口(网站根目录)
								'return_url'=>'/Pay_return.php',
								//支付接口接收服务器返回值的订单KEY名
								'order_key'=>'billno'
								);

       public $Gateway_URL			= 'http://newpay.ips.com.cn/psfp-entry/gateway/payment.html';				//环迅支付网关地址
//	public $Gateway_URL			= 'http://bankbackuat.ips.com.cn/psfp-entry/gateway/payment.html';				//环迅测试地址
    public    $pVersion = 'v1.0.0';//版本号
    public    $pMerCode = '';//商户号
    public    $pMerName = '';//商户名
    public    $pMerCert = '';//商户证书
    public    $pAccount  =  '';//账户号
    public    $pMsgId = '';//消息编号
    public    $pReqDate = '';//商户请求时间

    public    $pMerBillNo = '';//商户订单号
    public    $pAmount = '';//订单金额 
    public    $pDate = '';//订单日期
    public    $pCurrencyType = 'GB';//币种
    public    $pGatewayType = '01';//支付方式
    public    $pLang = '156';//语言
    public    $pMerchanturl = '';//支付结果成功返回的商户URL 
    public    $pFailUrl = "";//支付结果失败返回的商户URL 
    public    $pAttach = '';//商户数据包
    public    $pOrderEncodeTyp = '5';//订单支付接口加密方式 默认为5#md5
    public    $pRetEncodeType = '17';//交易返回接口加密方式
    public     $pRetType = '1';//返回方式 
    public    $pServerUrl = '';//Server to Server返回页面 
    public    $pBillEXP = 1;//订单有效期(过期时间设置为1小时)
    public    $pGoodsName = 'Huanxun onpay';//商品名称
    public    $pIsCredit = '';//直连选项
    public    $pBankCode = '';//银行号
    public   $pProductType= 1;//产品类型
    private $message			= ''; //消息提示

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
	   //dump($data_arr);
	   foreach($data_arr as $key=>$v)
	   {
			$data[$key] = $v;
	   }
		//读取数据库中的设置
		$Model						= M();
		$account					= $data[self::$pay_interface['pay_ename'].'_account'];
		$key						= $data[self::$pay_interface['pay_ename'].'_key'];
		$proxy						= $data[self::$pay_interface['pay_ename'].'_proxy'];
		$credit						= $data[self::$pay_interface['pay_ename'].'_credit'];
        $shanghao					= $data[self::$pay_interface['pay_ename'].'_shanghao'];
        $shangming					= $data[self::$pay_interface['pay_ename'].'_shangming'];
        $this->pAccount				= $account?$account:'';//账户号
		$this->pMerName				= $shangming?$shangming:'';//商户名
        $this->pMerCode				= $shanghao?$shanghao:'';//商户号
		$this->pMerCert				= $key?$key:'';//密钥
        $this->pMsgId				= "msg".rand(1000,9999);//消息编号
        $this->pReqDate				= date("Ymdhis");//商户请求时间
		$this->ServerLocationUrl	= $proxy?$proxy:'';
        $siteDomain	= $this->getSiteDomain();
        //设置$pMerchanturl
        $this->pMerchanturl = $siteDomain.'/index.php?s=/User/Index/index';
          
        $this->pDate	= date("Ymd");
		$this->pIsCredit		= $credit=='1'?1:0;
    	$this->pIsCredit		= 0;
		$this->Date					= date('Ymd');
	}
    }
 	//获取当前的域名带 协议
	private function getSiteDomain()
	{
		$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

		return $http_type.$_SERVER['HTTP_HOST'];
	}	

	//设置支付返回地址
	public function setServerurl($url)
	{
		$this->pServerUrl  = $url;
	}

	//设置浏览器跳转地址
	public function setLocationUrl($url)
	{
	
	}

	//设置订单id
	public function setOrderId($id)
	{
		$this->pMerBillNo = $id;
	}

	//获取订单id
	public function getOrderId()
	{
		return $this->pMerBillNo;
	}

	//设置支付金额
	public function setMoney($money)
	{

		$this->pAmount = number_format($money,2,'.','');
	}

	//获取支付金额
	public function getMoney()
	{
		return $this->pAmount;
	}


	//返回接口名称
	public static function getName()
	{
		return '环迅';
	}
    	//返回支付失败的提示信息

	public function getMessage()

	{

		return $this->message;

	}

	//返回接口描述
	public static function getMemo()
	{
		return 'PS(www.ips.com)账户是上海环迅于2005年推出的新一代基于电子邮件的互联网多币种收付款工具。截止到目前，IPS账户具备在线充值、在线收付款、在线转账、网上退款和网上提款等多种功能，并支持多种账户充值方式。';
	}

	//是否支持银行直连
	public function isSupportCredit()
	{
		return $this->pIsCredit;
	}

	//设置直连的银行
	public function setCreditBank($bank)
	{
		$this->pBankCode = $bank;
	}

	/*
	* 返回配置信息
	*/
	public static function getConfigInfo()
	{
		return array(
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_name',
				'config_value'=> '环迅支付',
				'name'=>'支付方式名称',
				'type'=>'text',
				'style'=>'width:100px',
			),
            array(
				'config_name'=>self::$pay_interface['pay_ename'].'_shangming',
				'config_value'=> '',
				'name'=>'商户名',
				'type'=>'text',
				'style'=>'width:100px',
			),
            array(
				'config_name'=>self::$pay_interface['pay_ename'].'_shanghao',
				'config_value'=> '',
				'name'=>'商户号',
				'type'=>'text',
				'style'=>'width:100px',
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_account',
				'config_value'=> '',
				'name'=>'账户号',
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
			'00056'=>'北京农村商业银行',
			'00050'=>'北京银行',
			'00095'=>'渤海银行',
			'00096'=>'东亚银行',
			'00057'=>'光大银行',
			'00052'=>'广发银行',
			'00081'=>'杭州银行',
			'00041'=>'华夏银行',
			'00005'=>'交通银行',
			'00013'=>'民生银行',
			'00085'=>'宁波银行',
			'00087'=>'平安银行',
			'00032'=>'浦东发展银行',
			'00084'=>'上海银行',
			'00023'=>'深圳发展银行',
			'00016'=>'兴业银行',
			'00051'=>'邮政储蓄',
			'00021'=>'招商银行',
			'00086'=>'浙商银行',
			'00004'=>'中国工商银行 | 银行卡支付',
			'00026'=>'中国工商银行 | 手机支付',
			'00015'=>'中国建设银行',
			'00017'=>'中国农业银行',
			'00083'=>'中国银行',
			'00054'=>'中信银行',
		);
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
        //是否使用代理跳转
		if(	$this->ServerLocationUrl != '' )
		{
			$_action_url	= $this->ServerLocationUrl;
			$_location_url	= base64_encode($this->Gateway_URL);
		}
        
       $reqParam="商户号".$this->pMerCode
          ."商户名".$this->pMerName
          ."账户号".$this->pAccount
          ."消息编号".$this->pMsgId
          ."商户请求时间".$this->pReqDate
          ."商户订单号".$this->pMerBillNo
          ."订单金额".$this->pAmount
          ."订单日期".$this->pDate
          ."币种".$this->pCurrencyType
          ."支付方式".$this->pGatewayType
          ."语言".$this->pLang
          ."支付结果成功返回的商户URL".$this->pMerchanturl
          ."支付结果失败返回的商户URL".$this->pFailUrl
          ."商户数据包".$this->pAttach
          ."订单支付接口加密方式".$this->pOrderEncodeTyp
          ."交易返回接口加密方式".$this->pRetEncodeType
          ."返回方式".$this->pRetType
          ."Server to Server返回页面 ".$this->pServerUrl
          ."订单有效期".$this->pBillEXP
          ."商品名称".$this->pGoodsName
          ."直连选项".$this->pIsCredit
          ."银行号".$this->pBankCode
          ."产品类型".$this->pProductType;
      file_put_contents(PATH_LOG_FILE,date('y-m-d h:i:s').'提交参数信息:'.$reqParam."\r\n",FILE_APPEND);
        if($pIsCredit==0)
         {
             $pBankCode="";
             $pProductType='';
         } 
         //请求报文的消息体
          $strbodyxml= "<body>"
        	         ."<MerBillNo>".$this->pMerBillNo."</MerBillNo>"
        	         ."<Amount>".$this->pAmount."</Amount>"
        	         ."<Date>".$this->pDate."</Date>"
        	         ."<CurrencyType>".$this->pCurrencyType."</CurrencyType>"
        	         ."<GatewayType>".$this->pGatewayType."</GatewayType>"
                         ."<Lang>".$this->pLang."</Lang>"
        	         ."<Merchanturl>".$this->pMerchanturl."</Merchanturl>"
        	         ."<FailUrl>".$this->pFailUrl."</FailUrl>"
                         ."<Attach>".$this->pAttach."</Attach>"
                         ."<OrderEncodeType>".$this->pOrderEncodeTyp."</OrderEncodeType>"
                         ."<RetEncodeType>".$this->pRetEncodeType."</RetEncodeType>"
                         ."<RetType>".$this->pRetType."</RetType>"
                         ."<ServerUrl>".$this->pServerUrl."</ServerUrl>"
                         ."<BillEXP>".$this->pBillEXP."</BillEXP>"
                         ."<GoodsName>".$this->pGoodsName."</GoodsName>"
                         ."<IsCredit>".$this->pIsCredit."</IsCredit>"
                         ."<BankCode>".$this->pBankCode."</BankCode>"
                         ."<ProductType>".$this->pProductType."</ProductType>"
        	      ."</body>";
         
      $Sign=$strbodyxml.$this->pMerCode.$this->pMerCert;//签名明文
      file_put_contents(PATH_LOG_FILE,date('y-m-d h:i:s').'签名明文:'.$Sign."\r\n",FILE_APPEND);
      $pSignature = md5($strbodyxml.$this->pMerCode.$this->pMerCert);//数字签名 
        //请求报文的消息头
      $strheaderxml= "<head>"
                   ."<Version>".$this->pVersion."</Version>"
                   ."<MerCode>".$this->pMerCode."</MerCode>"
                   ."<MerName>".$this->pMerName."</MerName>"
                   ."<Account>".$this->pAccount."</Account>"
                   ."<MsgId>".$this->pMsgId."</MsgId>"
                   ."<ReqDate>".$this->pReqDate."</ReqDate>"
                   ."<Signature>".$pSignature."</Signature>"
              ."</head>";
     //提交给网关的报文
        $strsubmitxml =  "<Ips>"
                      ."<GateWayReq>"
                      .$strheaderxml
                      .$strbodyxml
        	      ."</GateWayReq>"
                    ."</Ips>";
        //提交给网关明文
        file_put_contents(PATH_LOG_FILE,date('y-m-d h:i:s').'提交给网关明文:'.$strsubmitxml."\r\n",FILE_APPEND);
    
       ?>
     <form name="form1" id="form1" method="post" action="<?php  echo $_action_url;  ?>" target="_self">
    <input type="hidden" name="pGateWayReq" value="<?php echo $strsubmitxml; ?>" />
    <input type="hidden" name="location_url" value="<?php echo $_location_url; ?>" />
    </form>  
   <script language="javascript">document.form1.submit();</script>
   <?php 
     }
	/*
	* 处理收到的数据
	*/
	public function receive()
	{
	
		   $paymentResult = $_REQUEST["paymentResult"];//获取信息
              
    file_put_contents(PATH_LOG_FILE,date('y-m-d h:i:s')."S2S接收到的报文信息:".$paymentResult."\r\n",FILE_APPEND);
    $xml=simplexml_load_string($paymentResult,'SimpleXMLElement', LIBXML_NOCDATA); 

      //读取相关xml中信息
       $ReferenceIDs = $xml->xpath("GateWayRsp/head/ReferenceID");//关联号
       //var_dump($ReferenceIDs); 
       $ReferenceID = $ReferenceIDs[0];//关联号
       $RspCodes = $xml->xpath("GateWayRsp/head/RspCode");//响应编码
       $RspCode=$RspCodes[0];
       $RspMsgs = $xml->xpath("GateWayRsp/head/RspMsg"); //响应说明
       $RspMsg=$RspMsgs[0];
       $ReqDates = $xml->xpath("GateWayRsp/head/ReqDate"); // 接受时间
        $ReqDate=$ReqDates[0];
       $RspDates = $xml->xpath("GateWayRsp/head/RspDate");// 响应时间
        $RspDate=$RspDates[0];
       $Signatures = $xml->xpath("GateWayRsp/head/Signature"); //数字签名
        $Signature=$Signatures[0];
       $MerBillNos = $xml->xpath("GateWayRsp/body/MerBillNo"); // 商户订单号
        $MerBillNo=$MerBillNos[0];
       $CurrencyTypes = $xml->xpath("GateWayRsp/body/CurrencyType");//币种
        $CurrencyType=$CurrencyTypes[0];
       $Amounts = $xml->xpath("GateWayRsp/body/Amount"); //订单金额
        $Amount=$Amounts[0];
       $Dates = $xml->xpath("GateWayRsp/body/Date");    //订单日期
        $Date=$Dates[0];
       $Statuss = $xml->xpath("GateWayRsp/body/Status");  //交易状态
        $Status=$Statuss[0];
       $Msgs = $xml->xpath("GateWayRsp/body/Msg");    //发卡行返回信息
        $Msg=$Msgs[0];
       $Attachs = $xml->xpath("GateWayRsp/body/Attach");    //数据包
        $Attach=$Attachs[0];
       $IpsBillNos = $xml->xpath("GateWayRsp/body/IpsBillNo"); //IPS订单号
        $IpsBillNo=$IpsBillNos[0];
       $IpsTradeNos = $xml->xpath("GateWayRsp/body/IpsTradeNo"); //IPS交易流水号
        $IpsTradeNo=$IpsTradeNos[0];
       $RetEncodeTypes = $xml->xpath("GateWayRsp/body/RetEncodeType");    //交易返回方式
        $RetEncodeType=$RetEncodeTypes[0];
       $BankBillNos = $xml->xpath("GateWayRsp/body/BankBillNo"); //银行订单号
        $BankBillNo=$BankBillNos[0];
       $ResultTypes = $xml->xpath("GateWayRsp/body/ResultType"); //支付返回方式
        $ResultType=$ResultTypes[0];
       $IpsBillTimes = $xml->xpath("GateWayRsp/body/IpsBillTime"); //IPS处理时间
        $IpsBillTime=$IpsBillTimes[0];
	
        $resParam="关联号:"
          .$ReferenceID
          ."响应编码:"
          .$RspCode
          ."响应说明:"
          .$RspMsg
          ."接受时间:"
          .$ReqDate
          ."响应时间:"
          .$RspDate
          ."数字签名:"
          .$Signature
          ."商户订单号:"
          .$MerBillNo
          ."币种:"
          .$CurrencyType
          ."订单金额:"
          .$Amount
          ."订单日期:"
          .$Date
          ."交易状态:"
          .$Status
          ."发卡行返回信息:"
          .$Msg
          ."数据包:"
          .$Attach
		   ."IPS订单号:"
		     .$IpsBillNo
		   ."交易返回方式:"
          .$RetEncodeType
		   ."银行订单号:"
		     .$BankBillNo
			  ."支付返回方式:"
		     .$ResultType
			   ."IPS处理时间:"
		     .$IpsBillTime;
     file_put_contents(PATH_LOG_FILE,date('y-m-d h:i:s').'S2S新系统获取参数信息:'.$resParam."\r\n",FILE_APPEND);

         $arrayMer=$this->getMerInfo($this->pMerCode);
         $sbReq = "<body>"
                                  . "<MerBillNo>" . $MerBillNo . "</MerBillNo>"
                                  . "<CurrencyType>" . $CurrencyType . "</CurrencyType>"
                                  . "<Amount>" . $Amount . "</Amount>"
                                  . "<Date>" . $Date . "</Date>"
                                  . "<Status>" . $Status . "</Status>"
                                  . "<Msg><![CDATA[" . $Msg . "]]></Msg>"
                                  . "<Attach><![CDATA[" . $Attach . "]]></Attach>"
                                  . "<IpsBillNo>" . $IpsBillNo . "</IpsBillNo>"
                                  . "<IpsTradeNo>" . $IpsTradeNo . "</IpsTradeNo>"
                                  . "<RetEncodeType>" . $RetEncodeType . "</RetEncodeType>"
                                  . "<BankBillNo>" . $BankBillNo . "</BankBillNo>"
                                  . "<ResultType>" . $ResultType . "</ResultType>"
                                  . "<IpsBillTime>" . $IpsBillTime . "</IpsBillTime>"
                               . "</body>";
        $sign=$sbReq.$this->pMerCode.$arrayMer['mercert'];
        file_put_contents(PATH_LOG_FILE,date('y-m-d h:i:s').'S2S验签明文:'.$sign."\r\n",FILE_APPEND);
        $md5sign=  md5($sign);
        file_put_contents(PATH_LOG_FILE,date('y-m-d h:i:s').'S2S验签密文:'.$md5sign."\r\n",FILE_APPEND);
        	//判断签名
        if($Signature==$md5sign)
        {
            file_put_contents(PATH_LOG_FILE,date('y-m-d h:i:s')."S2S验签成功.\r\n",FILE_APPEND);
              if($RspCode=='000000')
            {
            	file_put_contents(PATH_LOG_FILE,date('y-m-d h:i:s')."S2S订单支付成功.\r\n",FILE_APPEND);	
                $this->message = '支付成功!';
    				return true;
            }else{
               $this->message = '支付失败!';
    				return false;
            }
            
         }
        else
        {
         file_put_contents(PATH_LOG_FILE,date('y-m-d h:i:s')."S2S验签失败.\r\n",FILE_APPEND);
           	$this->message = '签名验证失败!';
			return false;
        }
	}


   private function getMerInfo($mercode)
   {
      $arrayMer=array();
     
      $arrayMer=array (
                         'mername'=>$this->pMerName,
                         'mercert'=>$this->pMerCert,
                         'acccode'=>$this->pMerCode
                           );
           
     return $arrayMer;
   }

}
?>