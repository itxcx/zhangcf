<?php
// 会员模块
class SecAction extends CommonAction 
{
	public function index()
	{
		$this->assign('VoiceStatus',CONFIG('VOICE_STATUS'));
		$this->assign('SmsStatus'  ,CONFIG('SMS_STATUS'));
		$this->display();
	}
	
	//语音通道设置
	public function Voice()
	{
		$this->assign('status'     ,CONFIG('VOICE_STATUS'));
		$this->assign('account_sid',CONFIG('VOICE_ACCOUNT_SID'));
		$this->assign('auth_token' ,CONFIG('VOICE_AUTH_TOKEN'));
		$this->assign('app_id'     ,CONFIG('VOICE_APP_ID'));
		
		$temp_id_check = CONFIG('VOICE_TEMP_ID_CHECK');
		if($temp_id_check=='')
		{
			$temp_id_check=1;
		}
		$this->assign('temp_id_check'     ,$temp_id_check);
		$this->display();
	}
	//语音通道发送测试
	public function VoiceTest()
	{
		import('COM.VoiceVerify.VoiceVerify');
		$VoiceVerify=new VoiceVerify();
		$VoiceVerify->setAccount($_GET['account_sid'],$_GET['auth_token']);
		$VoiceVerify->setAppId($_GET['app_id']);
		if($_GET['type']=='sms')
		{
			$result = $VoiceVerify->sendTemplateSMS($_GET['phone_number'],array('123456'),$_GET['temp_id_check']);
		}
		else
		{
			$result = $VoiceVerify->voiceVerify("123456",'1',$_GET['phone_number'],"15904261873","",'zh','','','');
		}
         if($result == NULL ) {
            $this->error("发送失败,未得到任何返回信息!");;
            break;
        }

        if($result->statusCode!=0) {
        	$this->error("发送错误,代码:".$result->statusCode.'错误信息:'.$result->statusMsg);
            //TODO 添加错误处理逻辑
        } else{
        	$this->success("发送成功");
            //echo "voiceverify success!<br>";
            // 获取返回信息
            //$voiceVerify = $result->VoiceVerify;
            //echo "callSid:".$voiceVerify->callSid."<br/>";
            //echo "dateCreated:".$voiceVerify->dateCreated."<br/>";
           //TODO 添加成功处理逻辑
        }
	}
	//保存设置
	public function VoiceSave()
	{
		M()->startTrans();
		CONFIG('VOICE_STATUS'       ,I('post.status/d'));
		CONFIG('VOICE_ACCOUNT_SID'  ,I('post.account_sid/s'));
		CONFIG('VOICE_AUTH_TOKEN'   ,I('post.auth_token/s'));
		CONFIG('VOICE_APP_ID'       ,I('post.app_id/s'));
		CONFIG('VOICE_TEMP_ID_CHECK',I('post.temp_id_check/s'));
		M()->commit();
		$this->success("保存设置成功");
	}
	//
	public function VoiceGetInfo()
	{
		import('COM.VoiceVerify.VoiceVerify');
		$VoiceVerify=new VoiceVerify();
		$VoiceVerify->setAccount(CONFIG('VOICE_ACCOUNT_SID'),CONFIG('VOICE_AUTH_TOKEN'));
		$VoiceVerify->setAppId(CONFIG('VOICE_APP_ID'));
		$result=$VoiceVerify->queryAccountInfo();
        if($result == NULL ) {
        	$this->error('连接失败');
        }
        if($result->statusCode!=0) {
     	    $this->error('语音通道代码获取失败,错误代码:'.$result->statusCode.'类型:'.$result->statusMsg);
        }else{
     	    $account = $result->Account;
     	    $this->success("成功",'',array('balance'=>$account->balance));
        }
	}
	public function Mail()
	{
		$this->assign('status',CONFIG('MAIL_STATUS'));
		$this->display();
	}
	public function MailLoginTest()
	{
		import('COM.Mail.PHPMailer');
		
		
		$mail=new PHPMailer(true);
		$mail->Username=$_GET['mail'];
		$mail->Password=$_GET['pass'];
		$mail->Host    =$_GET['smtp_host'];
		$mail->SMTPAuth=true;
		$mail->isSMTP();
		$mail->SMTPSecure = 'ssl';
		$mail->Port = 465;
		$mail->CharSet = 'UTF-8';
		dump($mail->smtpConnect());
		//dump($_GET);
	}
}
?>