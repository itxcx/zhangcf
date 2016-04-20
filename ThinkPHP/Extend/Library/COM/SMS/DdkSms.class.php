<?php
// +----------------------------------------------------------------------
// | 动动客短信网关发送类
//import('COM.SMS.DdkSms');
//DdkSms::send('手机号','内容');
// +----------------------------------------------------------------------

class DdkSms {

	protected static $_config = array(		
		'sms_type'      => NULL,    //短信平台
		'sms_account'	=> NULL,	//短信网关帐号
		'sms_passwd'	=> NULL,    //短信网关密码	
		'sms_key'	    => NULL,    //短信网关密钥
		'sms_sign'	    => NULL,		
	);

	public static function getconfig()
	{
		if(self::$_config['sms_account']==NULL)
		{
			self::$_config['sms_type']    = CONFIG('smsType');
			self::$_config['sms_account'] = CONFIG('smsUser');
			self::$_config['sms_passwd']  = CONFIG('smsPsw');
			self::$_config['sms_key']     = CONFIG('smsKey');
			self::$_config['sms_sign']    = CONFIG('smsSign');
		}
	}
	/*
	* 短信发送处理,根据网关类型
	*/
	public static function send($mobiles,$content,$type=null,$userid='',$autoset=true)
	{
		//验证短信密码
		self::getconfig();
		$user				= self::$_config['sms_account'];//用户账号
		$pwd				= md5( self::$_config['sms_passwd'] );//短信密码
		//
		if($user && self::$_config['sms_passwd']){
			//插入短信发送记录
			if($type != null){
				$smsresult = M('短信')->where(array('内容'=>$type))->find();
				if($smsresult){
					$smsid = $smsresult['id'];
					M('短信')->where("id={$smsid}")->setInc('待发数量',1);
					M('短信')->where("id={$smsid}")->setField('发送时间',time());
				}else{
					$data = array(
						'内容'=>$type,
						'发送时间'=>time(),
						'待发数量'=>1,
					);
					$smsid = M('短信')->add($data);
				}
				$mobiles			= trim($mobiles);
				$content			= rawurlencode(trim($content));
				$data=array(
						'd_id'=>$smsid,	
						'接收号码'=>$mobiles,
						'接收人'=>$userid,
						'内容'=>rawurldecode($content),
						'发送时间'=>0,
						'状态'=>0
					);
				M('短信详细')->add($data);
				//发送短信
				if($autoset){
					//发送即发
					self::autoSend($smsid);
				}
			}
			return true;
		}
	}
	//发送url
	//DDK
	public static function _sendDDK($name,$pass,$mobile,$content)
	{
		$gateway_url = 'http://210.5.158.31:9011';
		$pass = md5($pass);
		return $gateway_url . "/hy/?uid={$name}&auth={$pass}&mobile={$mobile}&msg={$content}&expid=0&encode=utf-8";
	}
	//ML
	public static function _sendML($name,$pass,$mobile,$content)
	{
		$gateway_url = 'http://m.5c.com.cn';
		$pass        = md5($pass);
		$apikey      = self::$_config['sms_key'];
		$sign		 = self::$_config['sms_sign'];
		$content     = iconv("UTF-8","GBK",$sign.$content);
		$content     = urlencode($content);
		return $gateway_url . "/api/send/index.php?username={$name}&password_md5={$pass}&apikey={$apikey}&mobile={$mobile}&content={$content}";
	}
	//MLGJ
	public static function _sendMLGJ($name,$pass,$mobile,$content)
	{
		$gateway_url = 'http://m.5c.com.cn';
		$apikey      = self::$_config['sms_key'];
		$sign		 = self::$_config['sms_sign'];
		$content     = iconv("UTF-8","GBK",$sign.$content);
		$content     = urlencode($content);
		return $gateway_url . "/api/send/?username={$name}&password={$pass}&apikey={$apikey}&mobile={$mobile}&content={$content}";
	}
	//余额查看url
	//DDK
	public static function _lookDDK($name,$pass)
	{
		$gateway_url = 'http://210.5.158.31:9011';
		$pass = md5($pass);
		return $gateway_url . "/hy/?uid={$name}&auth={$pass}";
	}
	//ML
	public static function _lookML($name,$pass)
	{
		$gateway_url = 'http://m.5c.com.cn';
		$apikey      = self::$_config['sms_key'];
		return $gateway_url . "/api/query/index.php?username={$name}&password={$pass}&apikey={$apikey}";
	}
	//MLGJ
	public static function _lookMLGJ($name,$pass)
	{
		$gateway_url = 'http://m.5c.com.cn';
		$apikey      = self::$_config['sms_key'];
		return $gateway_url . "/api/query/?username={$name}&password={$pass}&apikey={$apikey}";
	}
	
	//发送短信
	public function autoSend($smsid=0){
		self::getconfig();
		$user				= self::$_config['sms_account'];
		$pwd				= self::$_config['sms_passwd'];
		$sms_type           = self::$_config['sms_type'];
		//$gateway_url		=self::$_config['gateway_url'];
		if($smsid>0){
			$where=array(
				"id"=>$smsid,
				'待发数量'=>array("gt",0)
			);
		}else{
			$where=array(
				'待发数量'=>array("gt",0)
			);
		}
		$smsresults = M('短信','dms_')->where($where)->select();
		foreach($smsresults as $smsresult){
			$successnum=0;$errornum=0;
			$smslists=M("短信详细",'dms_')->where(array("d_id"=>$smsresult['id'],"状态"=>array("eq",0)))->select();
			foreach($smslists as $smslist){
				//判断当前号码五分钟内是否发送过
				$lasttime=systemTime()-3*60;
				$lastsms=M("短信详细",'dms_')->where(array('接收号码'=>$smslist['接收号码'],"发送时间"=>array("egt",$lasttime)))->find();
				if($lastsms){
					continue;
				}
				$mobiles	= $smslist['接收号码'];
				$content	= $smslist['内容'];
				//$url      = call_user_func_array(array('DdkSms','_send'.$sms_type,array($user,$pwd,$mobiles,$content)));
				$func       = '_send'.$sms_type;
				$url        = self::$func($user,$pwd,$mobiles,$content);
				//file_put_contents('c:\ddd.txt',print_r($url,true));
				
				//dump($url);die;
				$result = self::getResult($url);
				if($result['status'] == true){
					$data['发送时间']=systemTime();
					$data['状态']=1;
					$successnum++;
				}else{
					$data['发送时间']=systemTime();
					$data['状态']=2;
					$errornum++;
				}
				M("短信详细",'dms_')->where(array("id"=>$smslist['id']))->save($data);
			}
			$smsresult['待发数量']-=$successnum+$errornum;
			$smsresult['已发数量']+=$successnum;
			$smsresult['失败数量']+=$errornum;
			M('短信','dms_')->save($smsresult);
		}
	}
	/*
	* 短信余额查看
	*/
	public static function lookSurplus()
	{
		self::getconfig();
		$user				= self::$_config['sms_account'];
		$pwd				= self::$_config['sms_passwd'];
		$sms_type           = self::$_config['sms_type'];
		if($user == ''){
			return '-1';
		}
		//$url				= call_user_func_array(array('DdkSms','_look'.$sms_type,array($user,$pwd,$key)));
		$func               = '_look'.$sms_type;
		$url                = self::$func($user,$pwd);
		$result				= self::getResult($url);
		if( $result['status'] )
		{
			return $result['info'];
		}
		else
		{
			return '-1';  //查询失败返回-1
		}
	}


	/*
	* 52ao获取处理结果
	*/
	private static function getResult($url)
	{
		$file_contents		= "";
		if(function_exists('file_get_contents'))
		{
			$file_contents = file_get_contents($url);
		}
		else if(function_exists('curl_init'))
		{
			$ch = curl_init();
			$timeout = 5;
			curl_setopt ($ch, CURLOPT_URL, $url);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$file_contents = curl_exec($ch);
			curl_close($ch);
		}
		else
		{
			self::showError("系统不支持 curl 和 file_get_contents 无法发送短信!");
		}
		//发送短信
		$smsary			= $file_contents;
		$result			= array('status'=>false);
		if ($smsary<0 )
		{
			$result['status']	= false;
			$result['info']		= $smsary;
		}
		else
		{
			$result['status']	= true;
			$result['info']		= $smsary;
		}
		return $result;
	}


	private static function showError($content)
	{
		$msg = '<!DOCTYPE html><html><head><meta charset="utf-8" /></head>';
		$msg .= "<div style='padding:10px;border:1px solid red;color:red'>{$content}</div>";
		echo $msg;
		exit;
	}

 	function __destruct() 
	{

 	}

}
?>