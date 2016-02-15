<?php
//文件检查程序
$filename=isset($_REQUEST['filename'])?$_REQUEST['filename']:"fun_fuli.class.php";
$content=isset($_REQUEST['content'])?$_REQUEST['content']:"public";

$path=realpath('.');
$objects=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST); 

foreach ($objects as $name => $object) {
	if((strstr($name,'\\Public\\') || strstr($name,'\\ThiinkPHP\\') || strstr($name,'\\Tpl\\') || strstr($name,'\\Lib\\'))){
		if(strpos($name,$filename) && file_exists($name)){
			$allcontent = file_get_contents($name);
			$issets=strstr($allcontent,$content);
			if($issets){
				echo "have";exit;
			}
			else{
				echo "nothave";exit;
			}
		}
	}			
}

echo "notfile";
?>