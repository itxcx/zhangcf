<?php
// 本类由系统自动生成，仅供测试用途
defined('APP_NAME') || die('小样，还想走捷径!');
class BomAction extends CommonAction {
	
	public function index(){
		$this->display();
	}
	/*
	*	查询BOM头
	*/
	public function check(){
		$filelist='';
		$basedir = ROOT_PATH;
		$result=$this->checkdir($basedir);
		//if($result){
			//echo "完成";
		//}
		$this->success('完成！');
	}
	public function filediff()
	{
		$url=I("post.url/s");
		if(strpos('http:',$filelist)===false)
		$url='http://'.$url;
		$url.='/Admin/?s=/Bom/getfilecheck';
		//$basedir = ROOT_PATH;
		//$result=$this->checkftp($basedir);
    		$ch = curl_init();
			// 2. 设置选项，包括URL
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			// 3. 执行并获取HTML文档内容
			$output = curl_exec($ch);
			// 4. 释放curl句柄
			curl_close($ch);
			$filedatas=explode('|',$output);
			$chkdata=array();
			foreach($filedatas as $filedata)
			{
				$filed=explode('@',$filedata);
				$chkdata[$filed[0]]=array('md5'=>$filed[1],'time'=>(int)$filed[2]);
			}
			//dump(count($chkdata));
			$basedir = ROOT_PATH;
			//dump($chkdata);
			echo '<div class="pageContent" layoutH="0">';
			$this->checkdiff($basedir,$chkdata);
			echo '</div>';
	}
	//获得文件对比信息
	public function getfilecheck()
	{
		//$url=$_POST['url'];
		//$url
		//$filelist='';
		$basedir = ROOT_PATH;
		$result=$this->checkftp($basedir);
		
	}
	//
	public function checkdiff($basedir,&$chkdata){
		$files=array();
		if($dh = opendir($basedir)){ 
			
			while(($file = readdir($dh)) !== false){
				if($file != '.' && $file != '..' && $file != '.svn'){
					if(!is_dir($basedir."/".$file)){
						$ext = pathinfo($file, PATHINFO_EXTENSION);
						if(in_array($ext, array('php', 'html','js'))){
							//$filelist = $this->checkBOM("$basedir/$file");
							//$this->checkCRLF("$basedir/$file");
							
							$urlmd5=md5(str_replace(ROOT_PATH,'',"$basedir/$file"));
							//dump($urlmd5);
							//dump(str_replace(ROOT_PATH,'',"$basedir/$file"));
							//dump(md5_file("$basedir/$file"));
							
							if(isset($chkdata[$urlmd5]))
							{
								if(md5_file("$basedir/$file")==$chkdata[$urlmd5]['md5'])
								{
									//echo "相同<br>";
								}
								else
								{
									if(strpos("$basedir/$file",'Runtime')===false)
									{
										if($chkdata[$urlmd5]['time']<filemtime("$basedir/$file"))
										{
											echo "<font  color='#ff0000'>".str_replace(ROOT_PATH,'',"$basedir/$file")."</font><br>";
										}
										else
										{
											echo str_replace(ROOT_PATH,'',"$basedir/$file")."不同<br>";
										}
									}
								}
							}
							else
							{
								
							}
							//echo md5("$basedir/$file").'@';
							//echo md5_file("$basedir/$file").'@';
							//echo filemtime("$basedir/$file");
							//echo '|';
						}
					}else{ 
						$dirname = $basedir."/".$file; 
						$this->checkdiff($dirname,$chkdata); 
					} 
				}
				if($filelist){
					if($files == ''){
						$files[]=$filelist;
					}else{
						$files[].=$filelist;
					}
				}
			}
			closedir($dh);
			
		}
		return true;
	}
	
	
	
	
	
	
	public function checkftp($basedir){
		$files=array();
		if($dh = opendir($basedir)){ 
			while(($file = readdir($dh)) !== false){
				if($file != '.' && $file != '..' && $file != '.svn'){
					if(!is_dir($basedir."/".$file)){
						$ext = pathinfo($file, PATHINFO_EXTENSION);
						if(in_array($ext, array('php', 'html','js'))){
							//$filelist = $this->checkBOM("$basedir/$file");
							//$this->checkCRLF("$basedir/$file");
							echo md5(str_replace(ROOT_PATH,'',"$basedir/$file")).'@';
							echo md5_file("$basedir/$file").'@';
							echo filemtime("$basedir/$file");
							echo '|';
						}
					}else{ 
						$dirname = $basedir."/".$file; 
						$this->checkftp($dirname); 
					} 
				}
				if($filelist){
					if($files == ''){
						$files[]=$filelist;
					}else{
						$files[].=$filelist;
					}
				}
			}
			closedir($dh);
		}
		return true;
	}
	
	/*
	*	循环文件
	*/
	function checkdir($basedir,$files=array(),$havecheck=array()){
		if(($dh = opendir($basedir))!= false){ 
			while(($file = readdir($dh)) !== false){
				if($file != '.' && $file != '..' && $file != '.svn'){
					if(substr($basedir,-1)=='/'){
						$filepath=$basedir.$file;
					}else{
						$filepath=$basedir.'/'.$file;
					}
					if(!is_dir($filepath) && !in_array($filepath,$havecheck) && !in_array($filepath,$files)){
						$havecheck[]=$filepath;
						$ext = pathinfo($file, PATHINFO_EXTENSION);
						if(in_array($ext, array('php', 'html'))){
							$filelist = $this->checkBOM($filepath);
							$this->checkCRLF($filepath);
							if($filelist!='' && !in_array($filelist,$files) && $filepath==$filelist){
								echo "文件<font color='#ff0000'>".$filelist."</font>存在并修正<br>";
								$files[]=$filelist;
							}
						}
					}else{
						$dirname = $filepath;
						$this->checkdir($dirname,$files,$havecheck); 
					}
				}else{
					continue;
				}
			}
			closedir($dh);
		}else{
			echo "未打开文件<br>";
			return false;
		}
		return true;
	}
	/*
	*	查找文件中的BOM头，若有返回文件名
	*/
function checkBOM($filename){
		@set_time_limit("0");
		$contents = file_get_contents($filename);
		$charset[1] = substr($contents, 0, 1);
		$charset[2] = substr($contents, 1, 1);
		$charset[3] = substr($contents, 2, 1);
		/*if($filename=="D:/www/047/index.php"){
			dump(ord($charset[1]));
			dump(ord($charset[2]));
			die;
		}*/
		if(ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191){
			$rest = substr($contents, 3);
            if($this->rewrite($filename, $rest)){
				return $filename;
			}else{
				return $filename.'失败';
			}
		}else if(ord($charset[1])==13 || ord($charset[1])==10){
			$rest = substr($contents, 1);
			if($this->rewrite($filename, $rest)){
				return $filename;
			}else{
				return $filename.'失败';
			}
		}else{
			return ;
		}
	}
	function checkCRLF($filename)
	{
		@set_time_limit("0");
		$contents = file_get_contents($filename); 
		if(strpos($contents,"\r") !== false)
		{
			$contents = str_replace("\r\n", "\n", $contents);
			$this->rewrite($filename, $contents);
		}
		if(strpos($filename,'.class.php') !== false)
		{
			$newcontent=trim($contents);
			if($newcontent!=$contents)
			{
				$this->rewrite($filename, $newcontent);
			}
		}
	}
	function rewrite($filename, $data){ 
		$filenum = fopen($filename, "w");
		flock($filenum, LOCK_EX); 
		fwrite($filenum, $data);
		$i++; 
		fclose($filenum);
		return true;
	}
}
?>