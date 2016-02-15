<?php
// | Author: yuetao <bossyt@163.com>
// +----------------------------------------------------------------------
// $Id$
class Email extends Think 
{ 
	public $loc_host;
	public $smtp_acc;
	public $smtp_pass;
	public $smtp_host;
	public $from;

    /**
    *   发送邮件函数
    *
    *   @param to	收件人邮箱,多个之间用,分开
	*
    */
	public function send_mail($to, $subject = 'No subject', $body = 'No content') 
	{
	    $loc_host	= $this->loc_host;							//发信计算机名，可随意
	    $smtp_acc	= $this->smtp_acc;				        //Smtp认证的用户名，
	    $smtp_pass	= $this->smtp_pass;  					//Smtp认证的密码，一般等同pop3密码
	    $smtp_host	= $this->smtp_host;      					//SMTP服务器地址，类似 smtp.tom.com
	    $from		= $this->from;          				//发信人Email地址，你的发信信箱地址
		//$subject	= iconv("UTF-8","GB2312",$subject); //邮件标题转码
		$headers	= "Content-Type: text/html; charset=\"utf-8\"\r\nContent-Transfer-Encoding: base64";
		$lb			= "\r\n"; //linebreak

		$hdr = explode($lb,$headers); //解析后的hdr
		if($body) {$bdy = preg_replace("/^\./","..",explode($lb,$body));}//解析后的Body

		$smtp = array(
			//1、EHLO，期待返回220或者250
			array("EHLO {$loc_host}{$lb}","220,250","HELO error: "),

			//2、发送Auth Login，期待返回334
			array("AUTH LOGIN{$lb}","334","AUTH error:"),

			//3、发送经过Base64编码的用户名，期待返回334
			array(base64_encode($smtp_acc).$lb,"334","AUTHENTIFICATION error : "),

			//4、发送经过Base64编码的密码，期待返回235
			array(base64_encode($smtp_pass).$lb,"235","AUTHENTIFICATION error : ")
		);

		//5、发送Mail From，期待返回250
		$smtp[] = array("MAIL FROM: <{$from}>{$lb}","250","MAIL FROM error: ");

		//检查收件人

		$toEmail = explode(',',$to);

		foreach($toEmail as $_em)
		{
			//6、发送Rcpt To。期待返回250
			$smtp[] = array("RCPT TO: <{$_em}>{$lb}","250","RCPT TO error: ");
		}
		

		
		//7、发送DATA，期待返回354
		$smtp[] = array("DATA{$lb}","354","DATA error: ");

		//8.0、发送From
		$smtp[] = array("From: {$from}{$lb}","","");

		//8.2、发送To
		$smtp[] = array("To: {$to}{$lb}","","");

		//8.1、发送标题
		$smtp[] = array("Subject: {$subject}{$lb}","","");

		//8.3、发送其他Header内容
		foreach($hdr as $h) {$smtp[] = array($h.$lb,"","");}

		//8.4、发送一个空行，结束Header发送
		$smtp[] = array($lb,"","");

		//8.5、发送信件主体
		if($bdy) {foreach($bdy as $b) {$smtp[] = array(base64_encode($b.$lb).$lb,"","");}}

		//9、发送“.”表示信件结束，期待返回250
		$smtp[] = array(".{$lb}","250","DATA(end)error: ");

		//10、发送Quit，退出，期待返回221
		$smtp[] = array("QUIT{$lb}","221","QUIT error: ");

		//打开smtp服务器端口
		$fp = @fsockopen($smtp_host, 25);
		if (!$fp) {
            return '链接服务器失败!';  //链接失败
		}
		while($result = @fgets($fp, 1024)){
			if(substr($result,3,1) == " ") { break;
			}
		}

		$result_str="";
		//发送smtp数组中的命令/数据
		foreach($smtp as $req){
			//发送信息
			@fputs($fp, $req[0]);
			//如果需要接收服务器返回信息，则
			if($req[1]){
				//接收信息
				while($result = @fgets($fp, 1024)){
					//echo $result;
					if(substr($result,3,1) == " ") {
						break;
					}
				};
				if (!strstr($req[1],substr($result,0,3))){
					$result_str.=$req[2].$result."<br>";
				}
			}
		}
		//$result_str.= '<p>发送成功!</p>';
		//关闭连接
		@fclose($fp);
		//dump($result_str);
		return $result_str;
	}
}
?> 