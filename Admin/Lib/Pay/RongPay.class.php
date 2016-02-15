<?php
import("COM.Interface.PayInterface");

/*
* 名称：融宝支付类
* 版本：1.0v
* 修档：2015/07/17
* 开发者：0025
* 验收人：冯露露
* 开发信息：临沂市新商网络技术有限公司
*/
class RongPay implements PayInterface{
	
	//接口对接静态信息
	public static $pay_interface=array(
								//支付接口中文名
								'pay_cname'=>'融宝支付',
								//支付接口英文名
								'pay_ename'=>'RongPay',
								//支付接口简介
								'synopsis'=>'天津融宝支付网络有限公司（原名天津荣程网络科技有限公司），简称“融宝支付”，是中国民营企业40强天津荣程祥泰投资控股集团有限公司控股子公司，注册资金3亿元人民币，2012年融宝支付获得人民银行颁发的《支付业务许可证》，经营业务类型涵盖互联网支付和移动电话支付，经营区域覆盖全国。目前，融宝支付已创建了拥有独立知识产权的第三方支付“融宝”与互联网金融解决方案“融托富”两大品牌。融宝致力于为客户提供优质、高效、安全的支付服务，提升支付的便利性和企业资金的运转效率。截止至2014年12月，已与国内外一百余家银行达成合作伙伴关系，搭建了一个为企业解决全方位资金清结算服务的跨银行、跨地域的金融支付平台。',
								//支付接口版本
								'version'=>'1.0v',
								//所有支付接口，统一使用的异步接口(网站根目录)
								'return_url'=>'/pay_return.php',
								//支付接口接收服务器返回值的订单KEY名
								'order_key'=>'order_no'
								);
	//API相关信息
	public $pay_data=array(
							/*
							*接口名称:service
							*类型:String
							*参数:固定值online_pay
							*/
					 		'service'=>'online_pay',
							/*
							*API接口地址:pay_url
							*类型:String
							*参数:固定值https://epay.reapal.com/portal?
							*/
							'pay_url'=>'https://epay.reapal.com/portal?',
							/*
							*融宝支付商户ID:merchant_id
							*类型:String
							*/
					 		'merchant_id'=>'',
							/*
							*签名:sign
							*类型:String
							*/
					 		'sign'=>'',
							/*
							*签名方式:sign_type
							*类型:String
							*参数:固定值MD5
							*/
					 		'sign_type'=>'MD5',
					 		/*
							*卖家Email:seller_email
							*类型:String(100)
							*/
					 		'seller_email'=>'',
					 		/*
							*通知接收URL:notify_url
							*类型:String(190)
							*/
					 		'notify_url'=>'',
						 	/*
							*结果返回URL:return_url
							*类型:String(190)
							*/
					 		'return_url'=>'',
							/*
							*支付方式:paymethod
							*类型:String
							*参数:bankPay,网银支付|directPay,银行直连
							*/
							'paymethod'=>'bankPay',
							/*
							*支付类型:payment_type
							*类型:String(4)
							*参数:1借记卡支付|2贷记卡支付|3全通道
							*/
							'payment_type'=>'1',
							/*
							*字符编码格式:charset
							*类型:String
							*参数:gbk|utf-8
							*/
							'charset'=>'utf-8',
							/*
							*是否银行直连:defaultbank
							*类型:String
							*参数:false|true
							*/
							'defaultbank'=>false,
							/*
							*商户订单号:order_no
							*类型:String(64)
							*/
							'order_no'=>'',
							/*
							*交易金额:total_fee
							*类型:Number(13,2)
							*/
							'total_fee'=>'',
							/*
							*直连银行中文名:bank
							*类型:String
							*/
							'bank'=>'',
							/*
							*支付币种:currency
							*类型:String
							*/
							'currency'=>'',
							/*
							*代理服务器:serverlocationurl
							*类型:String
							*/
							'serverlocationurl'=>'',
						 	/*
							*接受服务器端口:serverport
							*类型:String
							*/
					 		'serverport'=>'',
						 	/*
							*访问模式:transport
							*类型:String
							*/
					 		'transport'=>'http',
						 	/*
							*notify网关地址:transport
							*类型:String
							*/
					 		'gateway'=>'http://interface.reapal.com/verify/notify?',
					 		
						);
	//融宝FORM拼接临时数组
	public $parameter;
	//获得签名结果
	public $mysign;
	//报错信息
	public $message;
	
	//构造函数
	function __construct(){
		//读取后台商户设置数据
		$arr = M('pay_onlineaccount',' ')->where(array('pay_type'=>self::$pay_interface['pay_ename']))->order("pay_amount asc,id desc")->find();
		if($arr){
			$data = array();
			foreach(unserialize($arr['pay_attr']) as $key=>$v){
				$data[$key] = $v;
			}
			//支付币种
			$this->pay_data['currency'] = $data[self::$pay_interface['pay_ename'].'_name']?$data[self::$pay_interface['pay_ename'].'_name']:'';
			//商户ID
			$this->pay_data['merchant_id'] =$data[self::$pay_interface['pay_ename'].'_account']?$data[self::$pay_interface['pay_ename'].'_account']:'';
			//签名
			$this->pay_data['sign'] =$data[self::$pay_interface['pay_ename'].'_key']?$data[self::$pay_interface['pay_ename'].'_key']:'';
			//商户邮箱
			$this->pay_data['seller_email'] =$data[self::$pay_interface['pay_ename'].'_email']?$data[self::$pay_interface['pay_ename'].'_email']:'';
			//代理url
			$this->pay_data['serverlocationurl']=$data[self::$pay_interface['pay_ename'].'_proxy']?$data[self::$pay_interface['pay_ename'].'_proxy']:'';
			//是否支持直连
			$this->pay_data['defaultbank'] = $data[self::$pay_interface['pay_ename'].'_credit']=='Yes'?true:false;
			//设置返回地址
			$this->pay_data['return_url']='http://'.$_SERVER['HTTP_HOST'];
			unset($arr,$data);
		}
	}
	
	//析构函数
	function __destruct(){
		unset($this->pay_data);
	}
	
	//返回支付接口中文名称
	public static function getName(){
		return self::$pay_interface['pay_cname'];
	}
	
	//返回接口中文介绍
	public static function getMemo(){
		return self::$pay_interface['synopsis'];
	}
	//返回需要配置的项
	public static function getConfigInfo(){
		return array(
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_name',
				'config_value'=>self::$pay_interface['pay_cname'],
				'name'=>'支付方式名称',
				'type'=>'text',
				'style'=>'width:100px'
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_account',
				'config_value'=> '',
				'name'=>'商户ID',
				'type'=>'text',
				'style'=>'width:200px',
				'memo'=>'<a style="color:red" href="/Admin/Common/'.self::$pay_interface['pay_ename'].'/readme.doc" target="_blank">安装说明</a>'
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_key',
				'config_value'=> '',
				'name'=>'安全校验码',
				'type'=>'text',
				'style'=>'width:200px',
				'memo'=>'用于传输加密'
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_email',
				'config_value'=> '',
				'name'=>'商户电子邮箱',
				'type'=>'text',
				'style'=>'width:200px',
				'memo'=>'卖家在融宝的注册Email'
			),
			array(
				'config_name'=>self::$pay_interface['pay_ename'].'_proxy',
				'config_value'=> '',
				'name'=>'php转发Url',
				'type'=>'text',
				'style'=>'width:350px',
				'memo'=>'<a href="/Admin/Common/pay_location.php.txt" target="_blank">下载php转发文件</a>'
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
			)
		);
	}
	
	//提交表单
	public function submit(){
		//表单构造数组
		$parameter = array(
					"service"=>$this->pay_data['service'],
        			"payment_type"=> $this->pay_data['payment_type'],
        			"merchant_ID"=> $this->pay_data['merchant_id'],
        			"seller_email"=> $this->pay_data['seller_email'],
        			"return_url"=>$this->pay_data['return_url'],
        			"notify_url"=> $this->pay_data['notify_url'],
        			"charset"=> $this->pay_data['charset'],
        			"order_no"=> $this->pay_data['order_no'],
        			"title"=> '充值',
        			"body"=>'会员充值',
        			"total_fee"=> $this->pay_data['total_fee'],
        			"paymethod"=> $this->pay_data['paymethod']
					);
		
		if($this->pay_data['defaultbank']===true){
			$parameter['paymethod']='directPay';
			$parameter['defaultbank']=$this->pay_data['bank'];
		}
		
		$this->rongpay_service($parameter);
		$sHtmlText = $this->BuildForm();
		echo '<!DOCTYPE HTML PUBLIC"-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><meta http-equiv="Content-Type"content="text/html;charset=utf-8"><title>融宝支付双功能付款</title><style type="text/css">.font_content{font-family:"宋体";font-size:14px;color:#FF6600}.font_title{font-family:"宋体";font-size:16px;color:#FF0000;font-weight:bold}table{border:1px solid#CCCCCC}</style></head><body><table align="center"width="350"cellpadding="5"cellspacing="0"><tr><td align="center"class="font_title"colspan="2">订单确认</td></tr><tr><td class="font_content"align="right">订单号：</td><td class="font_content"align="left">'.$this->pay_data['order_no'].'</td></tr><tr><td class="font_content"align="right">付款总金额：</td><td class="font_content"align="left">'.$this->pay_data['total_fee'].'</td></tr><tr><td align="center"colspan="2">'.$sHtmlText.'</td></tr></table></body></html>';
	}
	
	//处理收到的数据
	public function receive(){
		if($this->pay_data['transport'] == "https"){
			$this->pay_data['gateway'] = "";
		}
		$verify_result = $this->notify_verify();  //计算得出通知验证结果
		if($verify_result && $_REQUEST['trade_status']== 'TRADE_FINISHED'){
			/*支付成功*/
			$orderId			= $_REQUEST['order_no']; //订单id
			//获取本地订单信息
			$Model				= M();
			$where['orderId']	= $orderId;
			$info				= $Model->table('pay_order')->where($where)->find();

			if(!$info){
				$this->message = '支付订单无效!';
				echo 'success';
				return false;
			}
			if ( $info['status'] != 0 ){
				$this->message = '支付订单不可重复提交!';
				echo 'success';
				return false;
			}
			$money			=  $_POST['TransAmount'];
			$this->message	= "成功支付: {$money} 元(人民币)";
			echo 'success';
			return true;
		}else{
			echo '111'; 
			$this->message = '支付失败!';
			echo "fail";
			return false;
		}
	}
	
//---------------------------------融宝独立模块↓--------------------
	//融宝支付form生成模块
	public function rongpay_service($parameter){
        $this->parameter=$this->para_filter($parameter);
        //设定charset的值,为空值的情况下默认为GBK
        if($parameter['charset'] == ''){
            $this->parameter['charset'] = 'GBK';
		}
        $this->charset=$this->parameter['charset'];
        //获得签名结果
        $sort_array=$this->arg_sort($this->parameter);    //得到从字母a到z排序后的签名参数数组
        $this->mysign=$this->build_mysign($sort_array,$this->pay_data['sign'],$this->pay_data['sign_type']);
    }
	/*
	 * 功能：构造表单提交HTML
	 * @param merchant_ID 合作身份者ID
	 * @param seller_email 签约融宝支付账号或卖家融宝支付帐户
	 * @param return_url 付完款后跳转的页面 要用 以http开头格式的完整路径，不允许加?id=123这类自定义参数
	 * @param notify_url 交易过程中服务器通知的页面 要用 以http开格式的完整路径，不允许加?id=123这类自定义参数
	 * @param order_no 请与贵网站订单系统中的唯一订单号匹配
	 * @param subject 订单名称，显示在融宝支付收银台里的“商品名称”里，显示在融宝支付的交易管理的“商品名称”的列表里。
	 * @param body 订单描述、订单详细、订单备注，显示在融宝支付收银台里的“商品描述”里
	 * @param total_fee 订单总金额，显示在融宝支付收银台里的“交易金额”里
	 * @param buyer_email 默认买家融宝支付账号
	 * @param input_charset 字符编码格式 目前支持 GBK 或 utf-8
	 * @param key 安全校验码
	 * @param sign_type 签名方式 不需修改
	 * @return 表单提交HTML文本
	 */
	public function BuildForm(){
		if(!empty($this->pay_data['serverlocationurl'])){
			$location_url	= base64_encode($this->pay_data['pay_url']."charset=".$this->parameter['charset']);
		}else{
			$this->pay_data['serverlocationurl']=$this->pay_data['pay_url']."charset=".$this->parameter['charset'];
		}
		
		//GET方式传递
        //$sHtml = "<form id='rongpaysubmit' name='rongpaysubmit' action='".$this->pay_data['serverlocationurl']."' method='get'>";
		//POST方式传递（GET与POST二必选一）
		$sHtml = "<form id='rongpaysubmit' name='rongpaysubmit' action='".$this->pay_data['serverlocationurl']."' method='post'>";
        while (list ($key, $val) = each ($this->parameter)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }
        $sHtml = $sHtml."<input type='hidden' name='sign' value='".$this->mysign."'/>";
        $sHtml = $sHtml."<input type='hidden' name='sign_type' value='".$this->pay_data['sign_type']."'/>";
       	if(isset($location_url)){
       		$sHtml = $sHtml."<input type='hidden' name='location_url' value='".$location_url."'/>";
       	}
        $sHtml = $sHtml."<input type='submit' value='融宝支付确认付款'></form>";
		$sHtml = $sHtml."<script>document.forms['rongpaysubmit'].submit();</script>";
        return $sHtml;
	}
	
	/*
	*功能：融宝支付接口公用函数
	*详细：该页面是请求、通知返回两个文件所调用的公用函数核心处理文件，不需要修改
	*修改日期：2012-01-04
	*说明：
	*以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
	*该代码仅供学习和研究融宝支付接口使用，只是提供一个参考。
	*/
	public function build_mysign($sort_array,$key,$sign_type="MD5"){
		$prestr = $this->create_linkstring($sort_array);     	//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $prestr.$key;									//把拼接后的字符串再与安全校验码直接连接起来
		$mysgin = $this->sign($prestr,$sign_type);			    //把最终的字符串签名，获得签名结果
		return $mysgin;
	}	
	
	/*
	*把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	*$array 需要拼接的数组
	*return 拼接完成以后的字符串
	*/
	public function create_linkstring($array){
		$arg  = "";
		while (list ($key, $val) = each ($array)) {
		    $arg.=$key."=".$val."&";
		}
		$arg = substr($arg,0,count($arg)-2);		     //去掉最后一个&字符
		return $arg;
	}
	
	/*
	*除去数组中的空值和签名参数
	*$parameter 签名参数组
	*return 去掉空值与签名参数后的新签名参数组
	*/
	public function para_filter($parameter){
		$para = array();
		while (list ($key, $val) = each ($parameter)){
		    if($key == "sign" || $key == "sign_type" || $val == ""){
				continue;
			}else{
				$para[$key] = $parameter[$key];
			}
		}
		return $para;
	}
	
	/*
	*对数组排序
	*$array 排序前的数组
	*return 排序后的数组
	*/
	public function arg_sort($array){
		ksort($array);
		reset($array);
		return $array;
	}
	
	/*
	*签名字符串
	*$prestr 需要签名的字符串
	*return 签名结果
	*/
	public function sign($prestr,$sign_type){
		$sign='';
		if($sign_type == 'MD5'){
		    $sign = md5($prestr);
		}else{
		    die("融宝支付暂不支持".$sign_type."类型的签名方式");
		}
		return $sign;
	}
	
	/*
	* 对notify_url的认证
	* 返回的验证结果：true/false
	*/
	public function notify_verify(){
		//获取远程服务器ATN结果，验证是否是融宝支付服务器发来的请求
		if($this->$this->pay_data['transport'] == "https"){
			$veryfy_url = $this->pay_data['gateway']."service=notify_verify" ."&merchant_ID=" .$this->pay_data['merchant_id']. "&notify_id=".$_POST["notify_id"];
		}else{
			$veryfy_url = $this->pay_data['gateway']. "merchant_ID=".$this->pay_data['merchant_id']."&notify_id=".$_POST["notify_id"];
		}
		$veryfy_result = file_get_contents($veryfy_url);
		//$veryfy_result = f('veryfy_url');
		//$veryfy_result='true';
		//f('veryfy_url',$veryfy_result);
		$veryfy_result='true';
		//判断POST来的数组是否为空
		if(empty($_POST)){
			return false;
		}else{
			$post          = $this->para_filter($_POST);	//对所有POST返回的参数去空
			$sort_post     = $this->arg_sort($post);	    //对所有POST反馈回来的数据排序
			$this->mysign=$this->build_mysign($sort_post,$this->pay_data['sign'],$this->pay_data['sign_type']);	//生成签名结果
			//判断veryfy_result是否为ture，生成的签名结果mysign与获得的签名结果sign是否一致
			//$veryfy_result的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//mysign与sign不等，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if(preg_match("/true$/i",$veryfy_result) && $this->mysign == $_POST["sign"]){
				return true;
			}else{
				return false;
			}
		}
	}
	
    /*
    *获取远程服务器ATN结果
	*$url 指定URL路径地址
	*return 服务器ATN结果集
    */
    public function get_verify($url,$time_out = "60"){
		$urlarr     = parse_url($url);
		$errno      = "";
		$errstr     = "";
		$transports = "";
		if($urlarr["scheme"] == "https"){
		    $transports = "ssl://";
		    $urlarr["port"] = "443";
		}else{   
		    $transports = "tcp://";
		    $urlarr["port"] = "18183";
		}
		$fp=@fsockopen($transports . $urlarr['host'],$urlarr['port'],$errno,$errstr,$time_out);
		if(!$fp){
		    die("ERROR: $errno - $errstr<br />\n");
		}else{
		    fputs($fp, "POST ".$urlarr["path"]." HTTP/1.1\r\n");
		    fputs($fp, "Host: ".$urlarr["host"]."\r\n");
		    fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
		    fputs($fp, "Content-length: ".strlen($urlarr["query"])."\r\n");
		    fputs($fp, "Connection: close\r\n\r\n");
		    fputs($fp, $urlarr["query"] . "\r\n\r\n");
		    while(!feof($fp)){
		        $info[]=@fgets($fp, 1024);
		    }
		    fclose($fp);
		    $info = implode(",",$info);
		    return $info;
		}
	}
//---------------------------------融宝独立模块↑--------------------
	
	//返回人民币对当前设置的 货币汇率金额
	private function getExchangeRateMoney(){}
	
	//是否使用代理
	public function is_proxy(){
		return $this->pay_data['serverlocationurl']==''?false:true;
	}

	//设置支付金额
	public function setMoney($money){
		$this->pay_data['total_fee']=number_format($money,2,'.','');
	}

	//获取支付金额
	public function getMoney(){
		return $this->pay_data['total_fee'];
	}

	//设置订单id
	public function setOrderId($id){
		$this->pay_data['order_no']=$id;
	}

	//获取订单id
	public function getOrderId(){
		return $this->pay_data['order_no'];
	}

	//设置支付返回地址
	public function setServerurl($url){
		$this->pay_data['notify_url']=$url.self::$pay_interface['return_url'];
	}
	
	//设置浏览器跳转地址
	public function setLocationUrl($url){}
	
	//是否支持银行直连
	public function isSupportCredit(){
		return $this->pay_data['defaultbank'];
	}
	
	//提供的直连银行的列表
	public static function getBankList()
	{
		return array(
			'CMB'=>'招商银行',
			'ICBC'=>'中国工商银行',
			'CCB'=>'建设银行',
			'BOC'=>'中国银行',
			'ABC'=>'中国农业银行',
			'BOCM'=>'交通银行',
			'SPDB'=>'浦发银行',
			'GDB'=>'广发银行',
			'CITIC'=>'中信银行',
			'CEB'=>'中国光大银行',
			'CEB'=>'光大银行',
			'CIB'=>'兴业银行',
			'SDB'=>'深圳发展银行',
			'CMBC'=>'中国民生银行',
			'HXB'=>'华夏银行',
			'SPA'=>'平安银行',
			'PSBC'=>'中国邮政储蓄银行',
			'BHBK'=>'渤海银行',
			'BEA'=>'BEA东亚银行',
			'NBBK'=>'宁波银行',
			'HSBK'=>'徽商银行',
			'FDBK'=>'富滇银行',
			'GZCBK'=>'广州银行',
			'SHRCB'=>'上海农村商业银行',
			'DLCBK'=>'大连银行',
			'DGCBK'=>'东莞银行',
			'HBBK'=>'河北银行',
			'JSBK'=>'江苏银行',
			'NXBK'=>'宁夏银行',
			'QLBK'=>'齐鲁银行',
			'XMCBK'=>'厦门银行',
			'SZCBK'=>'苏州银行',
			'WZMBK'=>'温州市商业银行',
			'SHBANK'=>'上海银行',
			'HZBANK'=>'杭州银行',
			'NJB'=>'南京银行'
		);
	}
	
	//设置支付类型编码
	public function setPayType($type){
		$this->pay_data['payment_type'] = $type;
	}
	
	//设置直连的银行
	public function setCreditBank($bank){
		$this->pay_data['bank'] = $bank;
	}
	
	//返回当前直连银行的中文名称
	public function getCreditBankName(){
		$bankList = RongPay::getBankList();
		return $bankList[$this->pay_data['bank']];
	}

	//返回支付失败的提示信息
	public function getMessage(){
		return $this->message ;
	}
}
?>