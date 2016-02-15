<?php
	//ver1.3
	ini_set('display_errors','On');
	set_time_limit(100);
class patch
{
	public $dom;
	public $xpath;
	function __construct()
	{
		$this->dom =new DOMDocument();            //建一个DOMDocument对象
		$this->dom->loadXML($this->getpatchxml());// 加载Xml文件
		$this->xpath = new DOMXPath($this->dom);
	}
	function run()
	{
		//处理文件替换
		foreach($this->xpath->query('/patch/replace') as $replace)
		{   
			//文件，要替换的内容，要替换成新的内容
		    $file   =$replace->getAttribute('file');
			$findval=$this->xpath->query('.//find',$replace)->item(0)->nodeValue;
			$newsval=$this->xpath->query('.//news',$replace)->item(0)->nodeValue;
			//取得本地文件内容
			$file_contents=$this->getlocalfile($file,$filepath);
			//如果未找到要替换的文件则跳过
			if($file_contents===null)
				continue;
			//判断have和nohave
			//查找文件是否存在要替换的字符
			if(mb_strpos($file_contents,$findval,0,"UTF-8")===false)
			{
				continue;
			}
			if(!$this->is_have('fhave',$upfile,$file_contents) && !$this->is_nothave('fnothave',$upfile,$file_contents))
			{
				//have或者nothave都失败
				if(!$this->is_have('have',$upfile,$file_contents) || $this->is_nothave('nothave',$upfile,$file_contents))
				{
					//跳过这个文件
					continue;
				}
			}
			$file_contents=str_replace($findval,$newsval,$file_contents);
			//替换
			file_put_contents($filepath,$file_contents);
			echo "replace ok";
		}
		//整体文件做更新
		//处理文件替换
		$upfileList = $this->xpath->query('/patch/upfile');
		foreach($upfileList as $upfile)
		{
			//文件，要替换的内容，要替换成新的内容
		    $file    = $upfile->getAttribute('file');
		    $newfile = $upfile->getAttribute('newfile');
		    //绝对文件名
			$now=false;
			$isup=true;
			$file_contents = $this->getlocalfile($file,$filepath);
			//判断have和nohave
			//判断文件必须含有那些内容
			//首先没有强制更新需求
			if(!$this->is_have('fhave',$upfile,$file_contents) && !$this->is_nothave('fnothave',$upfile,$file_contents))
			{
				//have或者nothave都失败
				if(!$this->is_have('have',$upfile,$file_contents) || $this->is_nothave('nothave',$upfile,$file_contents))
				{
					//跳过这个文件
					continue;
				}
			}
			//判断文件不能含有那些内容
			//更新
			$file_contents = getpathfile($newfile);
			if($this->is_have('uphave',$upfile,$file_contents))
			{
				//更新文件
				file_put_contents($filepath,$file_contents);
				echo "upfile ok";
			}
		}
		$upfileList = $this->xpath->query('/patch/find');
		foreach($upfileList as $upfile)
		{
			//文件，要替换的内容，要替换成新的内容
		    $file    = $upfile->getAttribute('file');
		    //绝对文件名
			$now=false;
			$isup=true;
			$file_contents = $this->getlocalfile($file,$filepath);
			//判断have和nohave
			//判断文件必须含有那些内容
			//首先没有强制更新需求
			//have或者nothave都失败
			if(!$this->is_have('have',$upfile,$file_contents) || $this->is_nothave('nothave',$upfile,$file_contents))
			{
				//跳过这个文件
				continue;
			}
			
			echo "findfile:".$file."\r\n";
		}
	}
	//内容是否存符合全部的have信息
	function is_have($nodename,$parentnode,$contents)
	{
		$ret=true;
		foreach($this->xpath->query('.//'.$nodename,$parentnode) as $upfaileset)
		{
			if(mb_strpos($contents,$upfaileset->nodeValue,0,"UTF-8")===false)
			{
				return false;
			}
		}
		return true;
	}
	function is_nothave($nodename,$parentnode,$contents)
	{
		$ret=true;
		foreach($this->xpath->query('.//'.$nodename,$parentnode) as $upfaileset)
		{
			if(mb_strpos($contents,$upfaileset->nodeValue,0,"UTF-8")!==false)
			{
				return false;
			}
		}
		return true;
	}
	//内容是否存符合全部的have信息
	function is_now($nodename,$parentnode,$contents)
	{
		$ret=true;
		foreach($this->xpath->query('.//'.$nodename,$parentnode) as $upfaileset)
		{
			if(mb_strpos($contents,$upfaileset->nodeValue,0,"UTF-8")===false)
			{
				$ret=false;
			}
		}
		return $ret;
	}
	//取得站点本地文件
	function getlocalfile($file,&$filepath)
	{
		$fileall='';
		if(file_exists($_SERVER['DOCUMENT_ROOT'].'/'.$file))
		{
			$fileall = $_SERVER['DOCUMENT_ROOT'].'/'.$file;
		}
		if(@is_writable($_SERVER['DOCUMENT_ROOT'].'/../') && file_exists($_SERVER['DOCUMENT_ROOT'].'/../'.$file))
		{
			$fileall = $_SERVER['DOCUMENT_ROOT'].'/../'.$file;
		}
		$filepath=$fileall;
		if($fileall=='')
		{
			return null;
		}
		else
		{
			return file_get_contents($fileall);
		}
	}
	//或者更新配置信息
	function getpatchxml()
	{
		$filename=isset($_GET['path'])? $_GET['path']:'patch.xml';
		if(!preg_match('/[0-1a-zA-Z]+\.xml/',$filename))
		{
			echo 'xmlerror';
			die();
		}
		$ch = curl_init();
		//设置选项，包括URL
		curl_setopt($ch, CURLOPT_URL, "http://www.patch10245.com/".$filename);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		//执行并获取HTML文档内容
		$output = curl_exec($ch);
		//释放curl句柄
		if($output===false)
		{
			echo "load patch error" .curl_error($ch);
			die();
		}
		curl_close($ch);
		if(!xml_parser($output))
		{
			echo 'xmlerror';
			die();
		}
		return $output;
	}
	
}
$patch=new patch();
$patch->run();

//取得补丁文件
function getpathfile($file)
{
	$ch = curl_init();
	//设置选项，包括URL
	curl_setopt($ch, CURLOPT_URL, "http://www.patch10245.com/".$file);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	//执行并获取HTML文档内容
	$output = curl_exec($ch);
	//释放curl句柄
	if($output===false)
	{
		echo "load patchfile error" .curl_error($ch);
		die();
	}
	return $output;
}

//打印获得的数据
function xml_parser($str){   
       $xml_parser = xml_parser_create();   
       if(!xml_parse($xml_parser,$str,true)){   
           xml_parser_free($xml_parser);   
           return false;   
       }else {   
           return (json_decode(json_encode(simplexml_load_string($str)),true));   
       }
   }
echo "runover";
?>