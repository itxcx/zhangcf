<?php
class RunTimeClear
{
	static function clear()
	{
		self::deldir(ROOT_PATH.'Admin/Runtime/Data/_fields');
		self::deldir(ROOT_PATH.'Admin/Runtime/Cache');
		self::deldir(ROOT_PATH.'DmsAdmin/Runtime/Data/_fields');
		self::deldir(ROOT_PATH.'DmsAdmin/Runtime/Cache');
	}
	static function deldir($dir) {
        if(!is_dir($dir))
        {
            return;
        }
        //先删除目录下的文件：
        $dh=opendir($dir);
        while ($file=readdir($dh)) {
		if($file!="." && $file!="..") {
		  $fullpath=$dir."/".$file;
		  if(!is_dir($fullpath)) {
			  unlink($fullpath);
		  } else {
			  self::deldir($fullpath);
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
}
?>