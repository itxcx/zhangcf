<?php
class InstallAction extends CommonAction{
    public $config = '';                                                        //相关配置
    public $model = '';                                                         //实例化一个model
    public $content;                                                            //内容
    public $dbName = '';
    public $dbuser = '';
    public $dbpass = '';                                                        //数据库名
    public $dir_sep = '/';                                                      //路径符号
	public $backupBeforeRecover = true;											//恢复数据前备份数据库
    
    //初始化数据 自动安装程序
 
     public function _initialize() {
        parent::_initialize();
        set_time_limit(0);                                                      //不超时
        ini_set('memory_limit','1500M');
        $this->config = array(
            'path' => ROOT_PATH."Admin/Common/dbbackup/",                          //备份文件存在哪里
            'isCompress' => 0,                                                  //是否开启gzip压缩 
        );
        $this->dbName = C('DB_NAME');  
        $this->dbuser = C('DB_USER');
        $this->dbpass = C('DB_PWD');                                         //当前数据库名称
        $this->model = new Model();
        if(!file_exists($this->config["path"])){
            mkdir($this->config["path"],0777);
        }

    }
    function index()
    {
    	
    	$this->display();
    }
    
    function readurl($url,$serverip)
    {
    	$matches = parse_url($url);
        !isset($matches['host']) && $matches['host'] = '';
        !isset($matches['path']) && $matches['path'] = '';
        !isset($matches['query']) && $matches['query'] = '';
        !isset($matches['port']) && $matches['port'] = '';
        $host = $matches['host'];
        $path = $matches['path'] ? $matches['path'].($matches['query'] ? '?'.$matches['query'] : '') : '/';
        $port = !empty($matches['port']) ? $matches['port'] : 80;
        
        $out = "GET $path HTTP/1.0\r\n";
        $out .= "Accept: */*\r\n";
        $out .= "Accept-Language: zh-cn\r\n";
        $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
        $out .= "Host: $host\r\n";
        $out .= "Connection: Close\r\n\r\n";
    	$fp = fsockopen($serverip, 80, $errno, $errstr, 15);
    	stream_set_blocking($fp, true);
    	stream_set_timeout($fp, 15);
    	fwrite($fp, $out);
		$limit=0;
		$return='';
    	$status = stream_get_meta_data($fp);
            if(!$status['timed_out']) {
                while (!feof($fp)) {
                    if(($header = @fgets($fp)) && ($header == "\r\n" ||  $header == "\n")) {
                        break;
                    }
                }
                $stop = false;
                while(!feof($fp) && !$stop) {
                    $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                    $return .= $data;
                    if($limit) {
                        $limit -= strlen($data);
                        $stop = $limit <= 0;
                    }
                }
            }    	
    	@fclose($fp);
    	return;
    }
    function msg($msg,$icon)
    {
        $caltime=date('H:i:s',time());
        print "<script  type='text/javascript' charset='UTF-8'>parent.addexemsg('$msg','$caltime','$icon');</script>";
        ob_flush(); //强制将缓存区的内容输出
        flush(); //强制将缓冲区的内容发送给客户端    
    }
    function dispbl($num)
    {
    	print "<script  type='text/javascript' charset='UTF-8'>parent.$('#baifenbi').html('$num%')</script>";
    	ob_flush(); //强制将缓存区的内容输出
        flush(); //强制将缓冲区的内容发送给客户端    
    }
	function deldir($dir) {
	  //先删除目录下的文件：
	  $dh=opendir($dir);
	  while ($file=readdir($dh)) {
		if($file!="." && $file!="..") {
		  $fullpath=$dir."/".$file;
		  if(!is_dir($fullpath)) {
			  unlink($fullpath);
		  } else {
			  $this->deldir($fullpath);
		  }
		}
	  }
	  closedir($dh);
	  //删除当前文件夹：
	  if(rmdir($dir)) {
		return true;
	  } else {
		return false;
	  }
	}
	function filter_path($path) {
		return preg_replace(array('/([\\\\\/]+)/', '/\/$/'), array('/', ''), $path);
	}	
	function run(){
		echo str_repeat(" ",4096);
        ob_flush(); //强制将缓存区的内容输出
        flush(); //强制将缓冲区的内容发送给客户端
		$this->msg('启动自动安装','/Public/Images/ExtJSicons/resultset_next.png');
		set_time_limit (0);
		if(CONFIG('DEFAULT_THEME')=='')
		{
			$this->msg('模板没有进行设置，请先设置模板在进行快速安装','/Public/Images/ExtJSicons/resultset_next.png');
			die();
		}
		import("COM.Util.FtpClient");
		$icode=I("post.icode/s");
		$icode=explode('|',$icode);
		$hostname=$icode[0];
		$ftpuser =$icode[1];
		$ftppass =$icode[2];
		$sqldata =$icode[3];
		$sqluser =$icode[4];
		$sqlpass =$icode[5];
		$serverip=$icode[6];
		$encode  =$icode[7];
		$rootpath=$icode[8];
		$md5     =$icode[9];
		$phpfile =isset($icode[10])?$icode[10]:"pc_deng.php";/*后台的入口文件名*/
		$md5val  =md5($icode[0].$icode[1].$icode[2].$icode[3].$icode[4].$icode[5].$icode[6].$icode[7].$icode[8].'uJPMLi0170rKGgl9');
		if($md5val != $md5)
		{
			//$this->error('校验不正确');
		}
		
		$ftp = new FtpClient($serverip,$ftpuser,$ftppass,true,21,10);
		$this->msg('连接FTP:'.$serverip.$ftp->getError(),'/Public/Images/ExtJSicons/computer_key.png');
		if(!$ftp->connect())
		{
			$this->msg('连接FTP错误:'.$ftp->getError(),'/Public/Images/ExtJSicons/computer_error.png');
			die();
		}
		$ftppath=$rootpath;
		//处理压缩部分
		$target_dir = ROOT_PATH.'encoded';
		$this->msg('删除压缩临时目录','/Public/Images/ExtJSicons/folder/folder_delete.png');
		if(is_dir($target_dir))
		{
			$this->deldir($target_dir);
		}
		mkdir($target_dir);
		
		$path = realpath(ROOT_PATH); 
		$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST); 
		foreach ($objects as $name => $object) {
			if(strpos($name,'.svn')===false && $objects->isFile() && pathinfo($name, PATHINFO_EXTENSION)=='zip')
			{
				unlink($name);
			}
		}
		$this->msg('开始对本地程序进行打包压缩','/Public/Images/ExtJSicons/compress.png');
		
		ini_set('display_errors','On');
		
		/*
		import("COM.zip.zip");
		$zip = new zip();
		//所有日志文件不需要上传
		$zip->addblack('.log');
		$zip->addblack('.zip');
		$zip->addblack('/.svn');
		//Install目录不需要上传
		
		$zip->addblack('/Install');
		//默认删除掉所有的下属会员
		$zip->addblack('/DmsAdmin/Tpl/User');
		//当前模板需要进入白名单
		$zip->addwhite('/DmsAdmin/Tpl/User/'.CONFIG('DEFAULT_THEME'));
		//核心模板
		$zip->addwhite('/DmsAdmin/Tpl/User/core');
		//登入口
		$zip->addwhite('/DmsAdmin/Tpl/User/login');
		*/
		//$zipfile = time().".zip"; 
		//$zip ->createZip(ROOT_PATH,ROOT_PATH.$zipfile);
		$zipfile = R('Lock/compress',array(true,$encode=='1'));
		$this->msg('开始执行FTP上传<font id=baifenbi>0%</font>','/Public/Images/ExtJSicons/computer_key.png');
		if(!$ftp->nb_upload(ROOT_PATH.$zipfile , $ftppath."dms.zip" ,array($this,'dispbl')))
		{
			$this->msg('文件上传错误:'.$ftp->getError(),'/Public/Images/ExtJSicons/computer_error.png');
		}
		
		$this->msg('上传自解压临时程序','/Public/Images/ExtJSicons/page/page_go.png');
		//创建解压临时文件---------------------------------------
		$fp	= @fopen( ROOT_PATH."unzip.php", 'wb+');
		$config_content = "<?php
			\$zip = new ZipArchive();
			\$rs = \$zip->open('./dms.zip');
			\$zip->extractTo('./');
			\$zip->close();
		?>";
		if(!@fwrite($fp, trim($config_content)))
		{
			return array(false,'解压程序生成失败');
		}
		@fclose($fp);
		
		//上传临时解压文件
		if(!$ftp->upload(ROOT_PATH.'unzip.php' , $ftppath.'unzip.php'))
		{
			$this->msg('上传失败:'.$ftp->getError(),'/Public/Images/ExtJSicons/computer_error.png');
		}
		//-----------------------------------------------------
		//执行解压操作
		$this->msg('进行在线解压','/Public/Images/ExtJSicons/computer_key.png');
		$this->readurl('http://'.$hostname.'/unzip.php',$serverip);
		
		//-----------------------------------------------------
		//对数据库进行处理
		$data = file_get_contents(ROOT_PATH.'Admin/Conf/core_config.php');
		$data = preg_replace("/'DB_HOST'					=>	'.*'/","'DB_HOST'					=>	'127.0.0.1'",$data);
		$data = preg_replace("/'DB_NAME'					=>	'.*'/","'DB_NAME'					=>	'".$sqldata."'",$data);
		$data = preg_replace("/'DB_USER'					=>	'.*'/","'DB_USER'					=>	'".$sqluser."'",$data);
		$data = preg_replace("/'DB_PWD'					=>	'.*'/","'DB_PWD'					=>	'".$sqlpass."'",$data);
		$fp	= fopen( ROOT_PATH."core_config.php", 'wb+');
		fwrite($fp, trim($data));
		fclose($fp);
		$this->msg('更新core_config','/Public/Images/ExtJSicons/computer_key.png');
		$ftp->upload(ROOT_PATH.'core_config.php' , $ftppath."Admin/Conf/core_config.php" );
		
		//------------------------------------------------------
		//备份当前的数据库
		$this->msg('备份和上传数据库备份','/Public/Images/ExtJSicons/database.png');
		$tables=$this->getTables();
		$this->backup($tables,ROOT_PATH."copysql.sql");
		$ftp->upload(ROOT_PATH.'copysql.sql' , $ftppath."copysql.sql" );
		$this->msg('上传恢复数据库专用Action','/Public/Images/ExtJSicons/page/page_go.png');
		//创建临时数据库恢复程序
$config_content= <<<EOT
<?php
class huifuAction extends Action  {
    public function recoverFile() {
        \$message='';
        \$fileName = ROOT_PATH."copysql.sql";
        if (is_file(\$fileName)) {
            \$ext = strrchr(\$fileName, '.');
            if (\$ext == '.sql') {
                \$file = fopen(\$fileName,"r");
                //\$filecontent = file_get_contents(\$fileName);
                \$filecontent = "";
                while(! feof(\$file)){
                    \$fget = fgets(\$file);
                    if(trim(\$fget) !=';/* MySQLReback Separation */'){
                        \$filecontent .= \$fget;
                    }else{
                        \$sql = trim(\$filecontent);
                        if (!empty(\$sql)) {
                            \$mes = M()->execute(\$sql);
                            if (false === \$mes) {           //如果 null 写入失败，换成 ''
                                \$table_change = array('null' => '\'\'');
                                \$sql = strtr(\$sql, \$table_change);
                                \$mes = M()->execute(\$sql);
                            }
                            if (false === \$mes) {                                     
                                \$message .='备份文件代码遇到错误!';
                            }
                        }
                        \$filecontent ="";
                    }
                    
                }
                fclose(\$file);
            } else {
                \$message .='无法识别的文件格式!';
            }
        } else {
            \$message .='文件不存在!';
        }
        return \$message;
    }
}
?>
EOT;
		$fp	= fopen( ROOT_PATH."huifuAction.class.php", 'wb+');
		fwrite($fp, trim($config_content));
		fclose($fp);
		$ftp->upload(ROOT_PATH.'huifuAction.class.php' , $ftppath."Admin/Lib/Action/huifuAction.class.php" );
		//恢复数据库
		$this->msg('执行数据恢复','/Public/Images/ExtJSicons/database_gear.png');
		$this->readurl('http://'.$hostname.$phpfile.'?s=/huifu/recoverFile',$serverip);
		$this->msg('删除在线临时文件','/Public/Images/ExtJSicons/folder/folder_delete.png');
		//删除原始压缩文件
		$ftp->delete($ftppath."dms.zip" );
		//删除解压程序
		$ftp->delete($ftppath."unzip.php" );
		//删除数据库备份
		$ftp->delete($ftppath."copysql.sql");
		//删除还原程序
		$ftp->delete($ftppath."Admin/Lib/Action/huifuAction.class.php");
		$this->msg('删除本地临时文件','/Public/Images/ExtJSicons/folder/folder_delete.png');
		unlink (ROOT_PATH.'huifuAction.class.php');
		unlink (ROOT_PATH.'copysql.sql');
		unlink (ROOT_PATH.'core_config.php');
		unlink (ROOT_PATH.'unzip.php');
		unlink (ROOT_PATH.$zipfile);
		$this->msg('完成,<a href="http://'.$icode[0].'/pc_denglu.php" target="_blank">点击登入</font>','/Public/Images/ExtJSicons/tick.png');
	}
    private function getTables($dbName = '') {
        if (!empty($dbName)) {
            $sql = 'SHOW TABLES FROM ' . $dbName;
        } else {
            $sql = 'SHOW TABLES ';
        }
        $result = $this->model->query($sql);
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }
    private function setPath($fileName) {
        $dirs = explode($this->dir_sep, dirname($fileName));
        $tmp = '';
        foreach ($dirs as $dir) {
            $tmp .= $dir . $this->dir_sep;
            if (!file_exists($tmp) && !@mkdir($tmp, 0777))
                return $tmp;
        }
        return true;
    }
     private function backquote($str) {
        return "`{$str}`";
    }
    private function chunkArrayByByte($array, $byte = 51200) {
        $i = 0;
        $sum = 0;
        $return = array();
        foreach ($array as $v) {
            $sum += strlen($v);
            if ($sum < $byte) {
                $return[$i][] = $v;
            } elseif ($sum == $byte) {
                $return[++$i][] = $v;
                $sum = 0;
            } else {
                $return[++$i][] = $v;
                $i++;
                $sum = 0;
            }
        }
        return $return;
    }
    private function backup($tables,$fileName) {
        $path = $this->setPath($fileName);
        $message ="";
        if ($path != true) {
            return "无法创建备份目录目录 '$path'";
        }
        $file = fopen($fileName,"a");
        if (empty($tables)){
            fclose($file);
            return '没有需要备份的数据表!';
        }
        fwrite($file ,'/* This file is created by MySQLReback ' . date('Y-m-d H:i:s') . ' */');
        foreach ($tables as $i => $table) {
			if($table == 'session') continue;
            $table = $this->backquote($table);                                  //为表名增加 ``
            $tableRs = $this->model->query("SHOW CREATE TABLE {$table}");       //获取当前表的创建语句
            if (!empty($tableRs[0]["Create View"])) {
                fwrite($file ,"\r\n DROP VIEW IF EXISTS {$table} \r\n;/* MySQLReback Separation */\r\n " . $tableRs[0]["Create View"] . "\r\n;/* MySQLReback Separation */");
            }
            if (!empty($tableRs[0]["Create Table"])) {
                fwrite($file ,"\r\n DROP TABLE IF EXISTS {$table} \r\n;/* MySQLReback Separation */\r\n " . $tableRs[0]["Create Table"] . "\r\n;/* MySQLReback Separation */");
                
                $count = $this->model->table($table)->count();
                $num = ceil($count/10000);
                
                for($i=0;$i<$num;$i++){
                    $valuesArr = array();
                    $srow = intval($i*10000);
                    $tableDateRow = $this->model->query("SELECT * FROM {$table} WHERE 1=1 LIMIT {$srow},10000");
                
                    //$tableDateRow = $this->model->query("SELECT * FROM {$table}");
                    $values = '';
                    if (false != $tableDateRow) {
                        foreach ($tableDateRow as &$y) {
                            foreach ($y as &$v) {
                               if ($v === null){                    //纠正empty 为0的时候  返回tree
                                    $v = 'null';                          //为空设为null
                                }else{
                                    $v = "'" . mysql_real_escape_string($v) . "'";       //非空 加转意符
								}
                            }
                            $valuesArr[] = '(' . implode(',', $y) . ')';
                        }
                    }
                    $temp = $this->chunkArrayByByte($valuesArr);
                    if (is_array($temp)) {
                        foreach ($temp as $v) {
                            $values = implode(',', $v) . "\r\n;/* MySQLReback Separation */";
                            if ($values != "\r\n;/* MySQLReback Separation */") {
                                fwrite($file ,"\r\n INSERT INTO {$table} VALUES {$values}");
                            }
                        }
                    }
                    $temp = "";
                }
            }
        }
        fclose($file);
        return "";
    }
}
?>