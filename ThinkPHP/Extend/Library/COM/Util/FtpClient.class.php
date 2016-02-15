<?php
/*
* FTP 文件传输类
*/
class FtpClient
{
	private $ip			= '';
	private $user		= '';
	private $passwd		= '';
	private $port		= '';
	private $timeout	= '';
	private $message	= '';
	private $pasv		= false;	//是否启用被动模式
	private $conn_id	= null;

	/*
	*	构造函数
	*	ip:			FTP 服务器的IP
	*	user:		帐号
	*	pass:		密码
	*	pasv:		是否启用被动模式,  true:被动模式 ,false:主动模式
	*	port:		端口
	*	timeout:	网络传输的超时时间限制
	*/
	function __construct($ip,$user,$passwd,$pasv=false,$port=21,$timeout=10) 
	{
		$this->ip		= $ip;
		$this->user		= $user;
		$this->passwd	= $passwd;
		$this->pasv		= $pasv;
		$this->port		= $port;
		$this->timeout	= $timeout;
	}

	/*
	*	链接FTP服务器
	*/
	public function connect()
	{
		$conn_id		= ftp_connect( $this->ip , $this->port , $this->timeout );
		if( !$conn_id )
		{
			$this->message = '无法链接FTP服务器!';
			return false;
		}

		$login_result	= ftp_login($conn_id, $this->user, $this->passwd);

		if( !$login_result )
		{
			$this->message = '无法登录FTP服务器!';
			return false;
		}
		
		//启用被动模式
		if( $this->pasv ) ftp_pasv($conn_id,true);

		$this->conn_id = $conn_id;
		return true;
	}

	/*
	*	上传文件至FTP服务器
	*
	*	localFile	: 本地文件路径
	*	remoteFile	: 远程文件路径
	*
	*/
	public function upload($localFile,$remoteFile)
	{
		//自动链接
		if( !$this->conn_id )
		{
			if( !$this->connect() )
			{
				return false; //返回链接失败
			}
		}
		//获取当前所在目录
		$pwd = ftp_pwd($this->conn_id);

		//创建存放目录
		$this->make_dir($remoteFile);

		//切换到根目录
		ftp_chdir($this->conn_id,$pwd);

		//开始上传
		return ftp_put($this->conn_id, $remoteFile, $localFile, FTP_BINARY);
	}
	
	public function nb_upload($localFile,$remoteFile,$callback=null)
	{
		$bl=0;
		//自动链接
		if( !$this->conn_id )
		{
			if( !$this->connect() )
			{
				return false; //返回链接失败
			}
		}
		//获取当前所在目录
		$pwd = ftp_pwd($this->conn_id);

		//创建存放目录
		$this->make_dir($remoteFile);

		//切换到根目录
		ftp_chdir($this->conn_id,$pwd);
		$source = fopen($localFile,"r");
		//开始上传
		$allsize=filesize($localFile);
		$ret = ftp_nb_fput($this->conn_id, $remoteFile, $source, FTP_BINARY);
		while ($ret == FTP_MOREDATA) {
			if($callback)
			{
				$nbl = intval(ftell($source)/$allsize*100);
				if($nbl>=$bl+2)
				{
					call_user_func_array($callback,array($nbl));
					$bl = $nbl;
				}
			}
		   $ret = ftp_nb_continue($this->conn_id);
		}
		
		if ($ret != FTP_FINISHED) {
		   echo "There was an error uploading the file...";
		   exit(1);
		}
		call_user_func_array($callback,array(100));
		return true;
	}
	/*
	*	上传文件至FTP服务器
	*
	*	localFile	: 本地文件路径
	*	remoteFile	: 远程文件路径
	*
	*/
	public function delete($remoteFile)
	{
		//自动链接
		if( !$this->conn_id )
		{
			if( !$this->connect() )
			{
				return false; //返回链接失败
			}
		}
		//获取当前所在目录
		$pwd = ftp_pwd($this->conn_id);

		//创建存放目录
		$this->make_dir($remoteFile);

		//切换到根目录
		ftp_chdir($this->conn_id,$pwd);
		ftp_delete($this->conn_id,$remoteFile);
	}

	/*
	* 循环遍历创建文件夹
	*/
	private function make_dir($path)
	{
		if($path=='/')
		{
			return true;
		}
		$dir	= explode("/", $path);
		$path	= "";
		$ret	= true;

		for ($i=0;$i<count($dir)-1;$i++)
		{
			$path.="/".$dir[$i];
			if(!ftp_chdir($this->conn_id,$path))
			{
				if(!ftp_mkdir($this->conn_id,$path))
				{
					$ret=false;
					break;
				}
			}
		}
		return $ret;
	} 


	/*
	* 获取错误内容
	*/
	public function getError()
	{
		return $this->message;
	}

	/*
	* 析构函数,释放链接
	*/
	function __destruct() 
	{
	   ftp_close( $this->conn_id );
	}

}
?>