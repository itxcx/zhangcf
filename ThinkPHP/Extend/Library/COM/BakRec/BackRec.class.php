<?php
/*
* 数据库备份与恢复
*/
class BackRec
{
	private $dir_sep = '/';                                                      //路径符号
	public $config = '';                                                        //相关配置
	//构造函数

	public function __construct()
    {
    	$this->config = array(
            'path' => "dbbackup/",                          //备份文件存在哪里
            'isCompress' => 0,                                                  //是否开启gzip压缩 
        );
    }
    /* -
     * +------------------------------------------------------------------------
     * * @ 备份数据 { 备份每张表、视图及数据 }
     * +------------------------------------------------------------------------
     * * @ $tables 需要备份的表数组
     * +------------------------------------------------------------------------
     */
 
    public function backup($tables,$fileName) {
    	
    	
    	$db = Db::getInstance();
    	$db->startTrans();

    	import("COM.ZipStream.ZipStream");
		$zipstream = new ZipStream($fileName);
		
		//$zip->create($fileName);
        $path = $this->setPath($fileName);
        $filesize=10;
        $message ="";
        if ($path != true) {
            return "无法创建备份目录目录 '$path'";
        }
        if (empty($tables)){
            //fclose($file);
            return '没有需要备份的数据表!';
        }
        
        $sqlfile='';
        $fileId=0;
        $sqlfile.=('/* This file is created by MySQLReback ' . date('Y-m-d H:i:s') . ' */');
        
        foreach ($tables as $i => $table) {
        	/******写入进程文件********/
			if(IS_CLI){
				file_put_contents(LOG_PATH.'cli.log', "备份||".$table.PHP_EOL, FILE_APPEND);
			}
			/**************************/
			if($table == 'session') continue;
            $table = $this->backquote($table);                                  //为表名增加 ``
            $tableRs = M()->query("SHOW CREATE TABLE {$table}");       //获取当前表的创建语句
            if (!empty($tableRs[0]["Create View"])) {
                $sqlfile.=("\r\n DROP VIEW IF EXISTS {$table};\r\n/* MySQLReback Separation */\r\n " . $tableRs[0]["Create View"] . ";\r\n/* MySQLReback Separation */");
            }
            if (!empty($tableRs[0]["Create Table"])) {
                $sqlfile.=("\r\n DROP TABLE IF EXISTS {$table};\r\n/* MySQLReback Separation */\r\n " . $tableRs[0]["Create Table"] . ";\r\n/* MySQLReback Separation */");
                
                $col='CONCAT(';
                $columns=M()->query("SHOW COLUMNS FROM {$table}");
                foreach($columns as $column)
                {
                	
                	$col.='QUOTE(`'.$column['Field'].'`),\',\',';
                }
                $col=trim($col,"',',").') rowstr';
                $queryID=mysql_unbuffered_query("SELECT {$col} FROM {$table}",$db->_linkID);
                $sqlstr='';
                do{
                	
                	$rows = mysql_fetch_assoc($queryID);
                	if($rows)
                	{
                		$sqlstr.=',('.$rows['rowstr'].')';
                	}
                	//第二个256不要设置太高，以免备份insert过大
                	if((strlen($sqlstr)>=1024*256 || !$rows) && $sqlstr!='')
                	{
                		$sqlstr=trim($sqlstr,',');
                		$sqlfile.=("\r\n INSERT INTO {$table} VALUES {$sqlstr};\r\n/* MySQLReback Separation */");
                		$sqlstr='';
                		if(strlen($sqlfile)>=1024*1024*$filesize)
                		{
                			$zipstream -> addFile($sqlfile, "data".$fileId.'.sql');
                			$sqlfile='';
                			$fileId++;
                		}
                	}
                }while($rows);
			}
        }
        if($sqlfile!='')
        {
        	$zipstream -> addFile($sqlfile, "data".$fileId.'.sql');
        }
		//备份奖金构成文件
        $dataname = ROOT_PATH.'DmsAdmin/PrizeData/';
        $zipstream->addDirectoryContent($dataname,'PrizeData');
        $zipstream->finalize();
		//关闭数据库
		$db->commit();
		$db->close();
        return "";
    }
    /* -
     * +------------------------------------------------------------------------
     * * @ 还原数据
     * +------------------------------------------------------------------------
     * * @ $fileName 文件名
     * +------------------------------------------------------------------------
     */
 
    public function recoverFile($fileName) {
        $message='';
        $fileName = $this->trimPath($this->config['path'] . $fileName);
        if (is_file($fileName)) {
            $ext = strrchr($fileName, '.');
            $filecontent = "";
            if ($ext == '.sql') {
            	$this->model->execute("SET FOREIGN_KEY_CHECKS = 0; ");//去掉外键约束
                $file = fopen($fileName,"r");
                //$filecontent = file_get_contents($fileName);
                while(! feof($file)){
                    $fget = fgets($file);
                    if(trim($fget) !='/* MySQLReback Separation */' && trim($fget) !=';/* MySQLReback Separation */'){
                        $filecontent .= $fget;
                    }else{
                        $sql = trim($filecontent);
                        if (!empty($sql)) {
                            $mes = $this->model->execute($sql);
                            if (false === $mes) {           //如果 null 写入失败，换成 ''
                                $table_change = array('null' => '\'\'');
                                $sql = strtr($sql, $table_change);
                                $mes = $this->model->execute($sql);
                            }
                            if (false === $mes) {                                     
                                $message .='备份文件代码遇到错误!';
                            }
                        }
                        $filecontent ="";
                    }
                    
                }
                fclose($file);
                $this->model->execute("SET FOREIGN_KEY_CHECKS = 1; ");//恢复外键约束
            } elseif($ext == '.zip' || $ext == '.xsdb') {
				M()->execute("SET FOREIGN_KEY_CHECKS = 0;");//去掉外键约束
				$zip=new ZipArchive();
				$fileid=0;
				if($zip->open($fileName)===TRUE){
					//解压缩到文件夹
   					if(IS_WIN){
                        $tmpdir = dirname($fileName)."/tmp/";
                    }else{
                        $tmpdir = sys_get_temp_dir()."/tmp/";
                    }
   				   	$this->remove_directory($tmpdir);
					$zip->extractTo($tmpdir);
					while(is_file($tmpdir.'data'.$fileid.'.sql'))
					{
						/******写入进程文件********/
						if(IS_CLI){
							file_put_contents(LOG_PATH.'cli.log', "还原||data".$fileid.'.sql'.PHP_EOL, FILE_APPEND);
						}
						/**************************/
						$this->runsql(file_get_contents($tmpdir.'data'.$fileid.'.sql'));
						$fileid++;
					}
					$this->remove_directory(ROOT_PATH."DmsAdmin/PrizeData/",false);
					if(is_dir($tmpdir."PrizeData")){//恢复奖金构成文件
						/******写入进程文件********/
						if(IS_CLI){
							file_put_contents(LOG_PATH.'cli.log', "还原||恢复奖金构成文件".PHP_EOL, FILE_APPEND);
						}
						/**************************/
						$this->xCopy($tmpdir."PrizeData",ROOT_PATH."DmsAdmin/PrizeData/",1);
					}
					$zip->close();
					$this->remove_directory($tmpdir);
				}
				M()->execute("SET FOREIGN_KEY_CHECKS = 1; ");//恢复外键约束	
            } else {
                $message .='无法识别的文件格式!';
            }
        } else {
            $message .='文件不存在!';
        }
        return $message;
        
    }
    private function setPath($fileName) {
        $dirs = explode($this->dir_sep, dirname($fileName));
        $tmp = '';
        foreach ($dirs as $dir) {
            $tmp .= $dir . $this->dir_sep;
            if (!file_exists($tmp) && !@mkdir($tmp, 0777))
                return $tmp;
        }
        return true;
    }
    /* -
     * +------------------------------------------------------------------------
     * * @ 给字符串添加 ` `
     * +------------------------------------------------------------------------
     * * @ $str 字符串
     * +------------------------------------------------------------------------
     * * @ 返回 `$str`
     * +------------------------------------------------------------------------
     */
 
    private function backquote($str) {
        return "`{$str}`";
    }
    public function trimPath($path) {
        return str_replace(array('/', '\\', '//', '\\\\'), $this->dir_sep, $path);
    }
     
    /* -
     * +------------------------------------------------------------------------
     * * @ 获取数据库的所有表
     * +------------------------------------------------------------------------
     * * @ $dbName  数据库名称
     * +------------------------------------------------------------------------
     */
 
    public function getTables($dbName = '') {
        if (!empty($dbName)) {
            $sql = 'SHOW TABLES FROM ' . $dbName;
        } else {
            $sql = 'SHOW TABLES ';
        }
        $result = M()->query($sql);
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }
    public function remove_directory($dir,$me=true){
		if(is_dir($dir) && $handle=opendir("$dir")){
			while(false!==($item=readdir($handle))) {
				if($item!="." && $item!=".."){
					if(is_dir("$dir/$item")){
						$this->remove_directory("$dir/$item",true);
					}else{
						unlink("$dir/$item");
					}
				}
			} 
			closedir($handle);
			if($me)rmdir($dir);
		}
		return '';
	}
	public function runsql($data){
		$sqldatas=explode("\r\n",$data);
		//dump(count($sqldata));
		$filecontent = "";
		foreach($sqldatas as &$sqldata)
		{
			
            if(trim($sqldata) !='/* MySQLReback Separation */' && trim($sqldata) !=';/* MySQLReback Separation */'){
                $filecontent .= $sqldata;
            }else{
                $sql = trim($filecontent);
                if (!empty($sql)) {
                    $mes = M()->execute($sql);
                    if (false === $mes) {           //如果 null 写入失败，换成 ''
                        $table_change = array('null' => '\'\'');
                        $sql = strtr($sql, $table_change);
                        $mes = M()->execute($sql);
                    }
                    if (false === $mes) {                                     
                        $message .='备份文件代码遇到错误!';
                    }
                }
                $filecontent ="";
            }
		}
	}

	public function xCopy($source, $destination, $child){
	    if (!file_exists($destination))
	    {
			if (!mkdir(rtrim($destination, '/'), 0777))
	        {
		        return false;
	        }
	        @chmod($destination, 0777);
	     }
		if(!is_dir($source)){ 
			return false;
		}
		if(!is_dir($destination)){
			mkdir($destination,0777);  
		}
		$handle=dir($source);
		while($entry=$handle->read()){
			if(($entry!=".")&&($entry!="..")){
				if(is_dir($source."/".$entry)){
					if($child)
					$this->xCopy($source."/".$entry,$destination."/".$entry,$child);
				}
				else{
					copy($source."/".$entry,$destination."/".$entry);
				}
			}
		}
		return true;
	}
}
?>