<?php
@set_time_limit(0);
$servername=$_SERVER['HTTP_HOST'];
$url="http://".$servername."/Install/index.php?s=/Install/index";
//$filepath='./encode.zip';
$filelist = glob('./*');$source_dir = './';
if(in_array($source_dir,$filelist)){
	$key = array_search($source_dir,$filelist);
	array_splice($filelist,$key,1);
}
foreach($filelist as $file_name){
	$file_name = filter_path($file_name);
	if(is_file($file_name)){
		$ext = pathinfo($file_name, PATHINFO_EXTENSION);
		if($ext=="zip"){
			$filepath=$file_name;
		}
	}
}
$zip = new ZipArchive();
$rs = $zip->open($filepath);
if($rs){
	$zip->extractTo('./');
	$zip->close();
	echo '<div style="margin:0 auto;text-align:center;">解压成功<a href="'.$url.'">点击进入创建</a></div>';
}
function filter_path($path) {
	return preg_replace(array('/([\\\\\/]+)/', '/\/$/'), array('/', ''), $path);
}
?>