<?php
// +----------------------------------------------------------------------
// | 动动客短信网关发送类
//import('COM.SMS.DdkSms');
//DdkSms::send('手机号','内容');
// +----------------------------------------------------------------------

class Sms {

	protected static $_config = array(
		'gateway_url'	=> 'http://210.5.158.31:9011',
		'sms_account'	=> NULL,	//短信网关帐号
		'sms_passwd'	=> NULL,    //短信网关密码
	);
	public static function setAccount($name,$pass)
	{
		self::$_config['sms_account'] = $name;
		self::$_config['sms_passwd']  = $pass;
	}
	public static function getconfig()
	{
		if(self::$_config['sms_account']==NULL)
		{
			self::$_config['sms_account'] = CONFIG('smsUser');
			self::$_config['sms_passwd'] = CONFIG('smsPsw');
		}
	}
	public static function send($mobiles,$content)
	{
        $gateway_url = self::$_config['gateway_url'];
        $user		 = self::$_config['sms_account'];
        $pwd		 = md5( self::$_config['sms_passwd'] );
        $mobiles	 = trim($mobiles);
        $content	 = rawurlencode(trim($content));
        dump($gateway_url."/hy/?uid={$user}&auth={$pwd}&mobile={$mobiles}&msg={$content}&expid=0&encode=utf-8");
        $url		 = $gateway_url."/hy/?uid={$user}&auth={$pwd}&mobile={$mobiles}&msg={$content}&expid=0&encode=utf-8";
        $result = self::getResult($url);
        return $result;
	}
	/*
	* 短信发送处理,根据网关类型
	*/
	public static function send1($mobiles,$content,$type=null,$userid=null)
	{
		self::getconfig();
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
			}
			$gateway_url		=self::$_config['gateway_url'];
			$mobiles			= trim($mobiles);
			$content			= rawurlencode(trim($content));
		
			$url				= $gateway_url."/hy/?uid={$user}&auth={$pwd}&mobile={$mobiles}&msg={$content}&expid=0&encode=utf-8";
			$result = self::getResult($url);
			//插入短信发送记录
			if($type != null){
				M('短信')->where("id={$smsid}")->setDec('待发数量',1);
				$data=array(
						'd_id'=>$smsid,	
						'接收号码'=>$mobiles,
						'接收人'=>$userid,
						'内容'=>rawurldecode($content),
						'发送时间'=>time(),
						
					);
				if($result['status'] == true){
					M('短信')->where("id={$smsid}")->setInc('已发数量',1);
					$data['状态']=1;
					M('短信详细')->add($data);
				}else{
					M('短信')->where("id={$smsid}")->setInc('失败数量',1);
					$data['状态']=2;
					M('短信详细')->add($data);
				}
			}
			return $result;
		}
		
	}

	/*
	* 短信余额查看
	*/
	public static function lookSurplus()
	{
		self::getconfig();
		$user				= self::$_config['sms_account'];
		if($user == ''){
			return '-1';
		}
		$pwd				= md5( self::$_config['sms_passwd'] );
		$url				= self::$_config['gateway_url']."/hy/m?uid={$user}&auth={$pwd}";
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