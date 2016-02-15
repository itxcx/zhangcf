<?php
/*
	File name: /include/zip.php
	Author:	Horace     2009/04/15
 */
class zip{
	public $dirInfo = array("0","0");
	public $rootDir = '';
	public $datasec      = array();
	public $ctrl_dir     = array();
	public $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
	public $old_offset   = 0;
	public $new_offset   = 0;
	public $black        = array();
	public $white        = array();
	public $Filter       = array();
	//增加压缩文件过滤器
	public function addFilter($func)
	{
		$this->Filter[] = $func;
	}
	public function addwhite($name)
	{
		$this->white[] = $name;
	}
	public function addblack($name)
	{
		$this->black[] = $name;
	}
	//检查文件是否被黑名单隔离
	private function check($filename)
	{
		//dump($filename);
		//先遍历黑名单
		$ret = true;
		$layer = 0;
		foreach($this->black as $black)
		{
			//如果黑名单描述的是扩展名
			if(strpos($black,'.') === 0)
			{
				$ext = pathinfo($filename, PATHINFO_EXTENSION);
				if($black == ".".$ext && $ext != '')
				{
					$ret = false;
				}
			//判定是根目录基准的
			}elseif(strpos($black,'/') === 0 && strpos($filename,$black) === 0)
			{
				$ret = false;
				if($layer < count(explode('/',trim($black,'/'))))
				{
					$layer = count(explode('/',trim($black,'/')));
				}
			}
			elseif(strpos($filename,$black) !== false)
			{
				if($layer < 1)
				{
					$layer = 1;
				}
			}
		}
		foreach($this->white as $white)
		{
			if(strpos($white,'/') === 0 && strpos($filename,$white) === 0)
			{
				
				if($layer < count(explode('/',trim($white,'/'))) && !$ret)
				{
					$ret = true;
				}
			}
		}
		return $ret;
	}
	function createZip($dir, $zipfilename){
		if (function_exists('gzcompress')== true){
			set_time_limit(0);
			if (is_array($dir)== true){
				$fd = fopen ($dir, "r");
				$fileValue = fread ($fd, filesize ($filename));
				fclose ($fd);
				if (is_array($dir)== true) $filename = basename($dir);
				$this -> addFile($fileValue, "$filename",$zipfilename);
			}else{
				//第一次安装时，此变量未赋值//赵小玮添加
				$this->dirTree($dir,$dir,$zipfilename);
			}
			$out = $this -> filezip();
			$fp = fopen($zipfilename,"a");
			fwrite($fp, $out, strlen($out));
			fclose($fp);
		}
		return true;
	}
	function dirTree($directory,$rootDir,$zipfilename){
		global $_SERVER,$dirInfo,$rootDir;
		$fileDir=$rootDir;
		$myDir=dir($directory);
		while($file=$myDir->read()){
			if(!in_array($file,$this->black) || (in_array($file,$this->black) && substr($directory,-6)=='/Admin')){
				$ext = pathinfo("$fileDir$file", PATHINFO_EXTENSION);
				$exts=array('rar','zip','gz');
				if(substr($directory,-1)=='/'){
					$filepath=$directory.$file;
				}else{
					$filepath=$directory.'/'.$file;
				}
				if(is_dir($filepath) and $file!="." and $file!=".." and $file!=".svn"){
					$this->dirInfo[0]++;
					$rootDir ="$fileDir$file/";
					if($this->check("/$rootDir"))
					{
						$rootDir=iconv("utf-8","gb2312",$rootDir);
						if($rootDir){
							$this -> addFile('',"$rootDir",$zipfilename);
							$this->dirTree($filepath,$rootDir,$zipfilename);
						}
					}
				}else{
					if($file!="." and $file!=".." and $file!=".svn" and !in_array($ext,$exts)){
						$this->dirInfo[1]++;
						if($this->check("/$rootDir$file"))
						{
							$fileValue = file_get_contents($filepath);
							foreach($this->Filter as $filter)
							{
								$fileValue = call_user_func_array($filter,array($filepath,$fileDir.$file,$fileValue));
							}
							$path=iconv("utf-8","gbk",$fileDir.$file);
							if($path){
								$this -> addFile($fileValue, $path,$zipfilename);
							}
						}
					}
				}
			}
		}
		$myDir->close();
	}
    function unix2DosTime($unixtime = 0) {
        $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);

        if ($timearray['year'] < 1980) {
	         $timearray['year']    = 1980;
	         $timearray['mon']     = 1;
	         $timearray['mday']    = 1;
	         $timearray['hours']   = 0;
	         $timearray['minutes'] = 0;
	         $timearray['seconds'] = 0;
        }

        return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
                ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
    }
    function addFile($data, $name,$zipfilename,$time = 0){
		$time     =1359609146;
        $name     = str_replace('\\', '/', $name);

        $dtime    = dechex($this->unix2DosTime($time));
        $hexdtime = '\x' . $dtime[6] . $dtime[7]
                  . '\x' . $dtime[4] . $dtime[5]
                  . '\x' . $dtime[2] . $dtime[3]
                  . '\x' . $dtime[0] . $dtime[1];
        eval('$hexdtime = "' . $hexdtime . '";');

        $fr   = "\x50\x4b\x03\x04";
        $fr   .= "\x14\x00";            // ver needed to extract
        $fr   .= "\x00\x00";            // gen purpose bit flag
        $fr   .= "\x08\x00";            // compression method
        $fr   .= $hexdtime;             // last mod time and date

        // "local file header" segment
        $unc_len = strlen($data);
        $crc     = crc32($data);
        $zdata   = gzcompress($data);

        $c_len   = strlen($zdata);

        $zdata   = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug

        $fr      .= pack('V', $crc);             // crc32
        $fr      .= pack('V', $c_len);           // compressed filesize
        $fr      .= pack('V', $unc_len);         // uncompressed filesize
        $fr      .= pack('v', strlen($name));    // length of filename
        $fr      .= pack('v', 0);                // extra field length
        $fr      .= $name;


        // "file data" segment
        $fr .= $zdata;

        // "data descriptor" segment (optional but necessary if archive is not
        // served as file)
        $fr .= pack('V', $crc);                 // crc32
        $fr .= pack('V', $c_len);               // compressed filesize
        $fr .= pack('V', $unc_len);             // uncompressed filesize
			$fp = fopen($zipfilename,"a");
			fwrite($fp, $fr);
			fclose($fp);
		//die();
        // add this entry to array
        //$this -> datasec[] = $fr;

        $this->new_offset += strlen($fr);

        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";                // version made by
        $cdrec .= "\x14\x00";                // version needed to extract
        $cdrec .= "\x00\x00";                // gen purpose bit flag
        $cdrec .= "\x08\x00";                // compression method
        $cdrec .= $hexdtime;                 // last mod time & date
        $cdrec .= pack('V', $crc);           // crc32
        $cdrec .= pack('V', $c_len);         // compressed filesize
        $cdrec .= pack('V', $unc_len);       // uncompressed filesize
        $cdrec .= pack('v', strlen($name) ); // length of filename
        $cdrec .= pack('v', 0 );             // extra field length
        $cdrec .= pack('v', 0 );             // file comment length
        $cdrec .= pack('v', 0 );             // disk number start
        $cdrec .= pack('v', 0 );             // internal file attributes
        $cdrec .= pack('V', 32 );            // external file attributes - 'archive' bit set

        $cdrec .= pack('V', $this -> old_offset ); // relative offset of local header
        $this -> old_offset = $this->new_offset;

        $cdrec .= $name;

        // optional extra field, file comment goes here
        // save to central directory
        $this -> ctrl_dir[] = $cdrec;
    }
    function filezip(){
        $data    = implode('', $this -> datasec);
        $ctrldir = implode('', $this -> ctrl_dir);
		//dump(strlen($data));
		//dump(strlen(file_get_contents(ROOT_PATH.'encode.zip')));

        return
            $data .
            $ctrldir .
            $this -> eof_ctrl_dir .
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries "on this disk"
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries overall
            pack('V', strlen($ctrldir)) .           // size of central dir
            pack('V', $this->new_offset) .              // offset to start of central dir
            "\x00\x00";                             // .zip file comment length
    }
}
?>
