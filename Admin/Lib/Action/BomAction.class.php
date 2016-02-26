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
		$this->success('完成！');
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
		fclose($filenum);
		return true;
	}
}
?>