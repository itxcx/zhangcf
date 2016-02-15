<?php
class Myfun
{
	function getip(){
	if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
     $ip = getenv("HTTP_CLIENT_IP");

     else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))

     $ip = getenv("HTTP_X_FORWARDED_FOR");

     else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
     $ip = getenv("REMOTE_ADDR");

     else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))

     $ip = $_SERVER['REMOTE_ADDR'];

     else

     $ip = "unknown";

     return($ip);
   }
//遍历得到目录及子目录内的所有文件
   public function scan_dir($path)
	{
	   $path_source= opendir($path);
	   while(($file=readdir($path_source)) !== false)
		{
		   if(is_dir($path.'/'.$file) && $file !='.' && $file !='..')
			{
			   echo  $path.'/'.$file.'<br/>';
			     scan_dirs($path.'/'.$file);
			}else{
				echo $path.'/'.$file.'<br/>';
			}
		}
	}

	//遍历得到目录下的所有文件名 并放到一个数组内
	public function dir_file($path)
	{
	   $filearr=array();
       $path_source= opendir($path);	     while(($file=readdir($path_source)) !== false)
		{
		   if(is_file($path.'/'.$file))
			{
			   $filearr[]=$file;
			}
		}
		return $filearr;
	}

	//自动调用类
}
?>