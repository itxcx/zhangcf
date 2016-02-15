<?php
/*
	File name: /include/zip.php
	Author:	Horace     2009/04/15
 */
class PHPZip{
	var $dirInfo = array("0","0");
	var $rootDir = '';
	var $datasec      = array();
	var $ctrl_dir     = array();
	var $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
	var $old_offset   = 0;
	var $new_offset   = 0;
	var $black        = array();
	function addblack($name)
	{
		$this->black[]=$name;
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
				$choose_model=CONFIG('DEFAULT_THEME');//赵小玮添加
				//第一次安装时，此变量未赋值//赵小玮添加
				if(empty($choose_model)) $choose_model='default_sj';//赵小玮添加
				$this->dirTree($dir,$dir,$zipfilename,$choose_model);//赵小玮增加$choose_model参数
			}
			$out = $this -> filezip();
			$fp = fopen($zipfilename,"a");
			fwrite($fp, $out, strlen($out));
			fclose($fp);
		}
		return true;
	}
	function dirTree($directory,$rootDir,$zipfilename,$choose_model){//赵小玮增加$choose_model参数
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
				if(is_dir($filepath) and $file!="." and $file!=".."  and $file!=".svn"){
					//------------------赵小玮增加$choose_model循环参数--------------begin------------------------
					//目录如果不是Dmsadmin/tpl/user,可以直接添加到压缩文件中，或者目录是，然后只添加里面固定的文件夹名					
					if(!strstr($directory,'DmsAdmin/Tpl/User') or (strstr($directory,'DmsAdmin/Tpl/User') and ($file=='core' || $file=='login' || $file==$choose_model ||	strstr($directory,'DmsAdmin/Tpl/User/core') or strstr($directory,'DmsAdmin/Tpl/User/login') or strstr($directory,'DmsAdmin/Tpl/User/'.$choose_model)))){
						$dirInfo[0]++;
						$rootDir ="$fileDir$file/";
						$this -> addFile('',"$rootDir",$zipfilename);
						$this->dirTree($filepath,$rootDir,$zipfilename,$choose_model);
					}
					//------------------赵小玮增加$choose_model循环参数-----------------end------------------------
				}else{
					if($file!="." and $file!=".." and $file!=".svn" and !in_array($exts,$ext)){
						$dirInfo[1]++;
						//$fd = fopen ("$directory/$file", "r");
						$fileValue = file_get_contents($filepath);
						//fclose ($fd);
						$this -> addFile($fileValue, "$fileDir$file",$zipfilename);
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
