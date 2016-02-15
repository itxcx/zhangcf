<?php
/*
* 管理后台默认模块
*/
class ImageAction extends Action
{
	/*
	* 默认方法
	*/
    public function index()
	{
		ini_set('display_errors','On');
		$url = $_SERVER["REQUEST_URI"];
		$thumbnail=false;
		$url=str_replace('/Upload/?','/Upload/',$url);
		if(preg_match('/\/n_(\d+)_(\d+)/',$url,$matches))
		{
			$thumbnail=true;
			$Width  = $matches[1];
			$Height = $matches[2];
			$url    = str_replace($matches[0],'',$url);
		}
		$url=str_replace('/','\\',$url);
		//得到实际图片目录
		$filename="..".$url;
		
		$showfilename=$filename;
		if($thumbnail)
		{
			$path=substr($showfilename,0,strrpos($showfilename,"\\"));
			$file=substr($showfilename,strrpos($showfilename,"\\")+1);
			//dump($file);
			$showfilename  = $path . '\\thumb\\' . $file;
			$showfilename .= '.' . $Width . '_' . $Height.'.'.pathinfo($showfilename, PATHINFO_EXTENSION);
			if(!is_file($showfilename))
			{
	            $dir = dirname($showfilename);
	            // 目录不存在则创建
	            if (!is_dir($dir))
	                mkdir($dir);
	            import("ORG.Util.Image");
	            Image::thumb($filename, $showfilename, $type='', $Width, $Height);
			}
		}
		$size = getimagesize($showfilename); //获取mime信息 
		$fp=fopen($showfilename, "rb");      //二进制方式打开文件
		if ($size && $fp) { 
			header("Content-type: {$size['mime']}"); 
			fpassthru($fp); // 输出至浏览器 
		}
    }
}
?>