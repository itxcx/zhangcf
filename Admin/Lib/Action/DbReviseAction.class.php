<?php
defined('APP_NAME') || die('不要非法操作哦!');
class DbReviseAction extends CommonAction  {
     //初始化数据
     public function _initialize() {
        parent::_initialize();
        header("Content-type: text/html;charset=utf-8");
        set_time_limit(0);//不超时
        ini_set('memory_limit','1500M');
        $this->model = new Model();
    }
	public function index($success=true)
	{
		$syncM=D("DmsAdmin://SyncDmsAdminAdmin");
		import('DmsAdmin.DMS.stru');
		//返回数据库表字符串
		$xml=$syncM->getAllxml();
		if($xml === false)
		{
			echo '对应表的XML文件有语法错误';
			die;
		}
		/*
		* 循环配置文件的数据的表与字段
		*/
		$tabarr=array();
		$v_ts=$xml->xpath('./table');
		foreach($v_ts as $v_t)
		{
			 if(!array_key_exists((string)$v_t['name'],$tabarr))
			 {
			 	 if(isset($tabarr[(string)$v_t['name']]['engine']))
				 	$tabarr[(string)$v_t['name']]['engine']=(string)$v_t['engine'];
				 if(isset($v_t['comment']) && (string)$v_t['comment'] !='')
				 {
				 	$tabarr[(string)$v_t['name']]['comment']=(string)$v_t['comment'];
				 }
			 }
			 if(!isset($tabarr[(string)$v_t['name']]['field']))
			 	$tabarr[(string)$v_t['name']]['field']=array();
			 $fields=$v_t->xpath('./field');
             foreach($fields as $field)
			 {
				 if(array_key_exists((string)$field['name'],$tabarr[(string)$v_t['name']]['field']))
				 {
					//echo (string)$v_t['name'].' 表的 '.(string)$field['name']." 字段存在多个<br/>";
					continue;
				 }
				 $tabarr[(string)$v_t['name']]['field'][(string)$field['name']]=array(	 
					 'type'=>(string)$field['type'],
					 'primary'=>(string)$field['primary'],
					 'null'=>(string)$field['null'],
					 'auto_increment'=>(string)$field['auto_increment'],
				 );
				 if(isset($field["default"]))
				 {
					$tabarr[(string)$v_t['name']]['field'][(string)$field['name']]['default']=(string)$field['default'];
				 }
				 if(isset($field["comment"]) && $field["comment"]!='')
				 {
					$tabarr[(string)$v_t['name']]['field'][(string)$field['name']]['comment']=(string)$field['comment'];
				 }
				 unset($field);
			 }
			 unset($fields);
			 unset($v_t);
		 }
		 unset($v_ts);
		 /*
		 * 判定并修复数据库的表以及字段
		 */
		 //获得现有的所有表
		 $tables = $this->getTables();
		 foreach($tabarr as $tabkey=>$tab)
		 {
			$table="dms_".$tabkey;
			//判断是否已存在该表
			if(!in_array($table,$tables))
			{
				 $sql='';
				 $query="";
				 $query.="CREATE TABLE `".$table."`(";
				 foreach($tab['field'] as $fieldkey=>$field)
				 {
					if(strtolower($field["type"]) == 'key'){
						$strquery =$fieldkey;
					}elseif(strtolower($field["type"]) == 'trigger' || strtolower($field["type"]) == 'foreign'){
						continue;
					}else{
						$strquery="`".$fieldkey."` ";
						$strquery.=$field["type"];
						if($field['auto_increment']==1) $strquery.=" auto_increment";
						//if($field["null"]==1) 
						$strquery.=" NOT NULL";
						if($field["primary"]==1) $strquery.=" PRIMARY KEY";
					}
					if(isset($field["default"]))
					 {
						if((string)$field["default"]=='')
						 {
							$strquery.=" default ''";
						 }elseif((string)$field["default"]==' ')
						 {
							$strquery.=" default ' '";
						 }elseif((string)$field["default"]=='NULL')
						 {
							$strquery.=" default NULL";
						 }else{
						$strquery.=" default '".(string)$field['default']."'";
						 }
					  }
					if(isset($field["comment"]))
					{
						$strquery.=" COMMENT '".(string)$field["comment"]."'";
					}
					$query.=$strquery.",";		
				 }
				 if(substr($query,strlen($query)-1,1)==",") $query=substr($query,0,strlen($query)-1);
				 $query.=") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
				 $sql.=$query;
				 $result=M()->execute($sql);
				 if($result===false){
					$result=false;
				 }else{
				 	$result=true;
				 }
				 if($result==false){
					$this->error('数据表修正失败!'.$sql, '__APP__/Backup/index');
				 }
			}else{
				//判断数据表中的字段是否 缺失
				$views = array();//$this->getView($table);
				$curfields = array();// 存放字段的详细信息，用于判断字段类型有无改变
				$tmpfields = $this->getField($table);
				foreach($tmpfields as $item){
					$views[]=$item['Field'];
					$curfields[$item['Field']]=$item;
				}
				$keys = $this->getKey($table);
				$sql='';
				$query="";
				foreach($tab['field'] as $fieldkey=>$field)
				{
					if(!(in_array($fieldkey,$views) || in_array($fieldkey,$keys)))
					{
						$strquery ='';
						if(strtolower($field["type"]) == 'key'){
							//判断key是已经存在
							if(!in_array($fieldkey,$keys)){
								$strquery =$fieldkey;
							}
						}elseif(strtolower($field["type"]) == 'trigger' || strtolower($field["type"]) == 'foreign'){
							continue;
						}else{
							if(!in_array($fieldkey,$views)){
								$strquery="`".$fieldkey."` ";
								$strquery.=$field["type"];
								if($field['auto_increment']==1) $strquery.=" auto_increment";
								//if($field["null"]==1) 
								$strquery.=" NOT NULL";
								if($field["primary"]==1) $strquery.=" PRIMARY KEY";
							}
						}
						if(isset($field["default"]))
						{
							if((string)$field["default"]=='')
							 {
								$strquery.=" default ''";
							 }elseif((string)$field["default"]==' ')
							 {
								$strquery.=" default ' '";
							 }elseif((string)$field["default"]=='NULL')
							 {
								$strquery.=" default NULL";
							 }else{
							$strquery.=" default '".(string)$field['default']."'";
							 }
						}
						if(isset($field["comment"]))
						{
							$strquery.=" COMMENT '".(string)$field["comment"]."'";
						}
						if($strquery!='')$query.=" add (".$strquery."),";
						//if(substr($query,strlen($query)-1,1)==",") $query=substr($query,0,strlen($query)-1);
						//$query.="),";
					}else{
						if(strtolower($field["type"]) != 'key' && strtolower($field["type"]) != 'trigger' && strtolower($field["type"]) != 'foreign'){
							if(!isset($field["primary"]) || $field["primary"]==''){
								$tmp = $curfields[$fieldkey];
								$field["type"] = str_replace('numeric','decimal',$field["type"]);//numeric类型换成decimal
								$field["type"] = str_replace('integer','int',$field["type"]);//integer类型换成int
								$field["type"] = ($field["type"]=='int'?"int(11)":$field["type"]);//int类型没给长度的默认11
								$field["type"] = (strpos($field["type"],'decimal')!==false && strpos($field["type"],',')===false?str_replace(")",",0)",$field["type"]):$field["type"]);//decimal类型没给小数长度的默认0
								//if($filed['type']!=$curfields[$fieldkey]['Type'] || $filed['primary']==1 && $curfields[$fieldkey]['Key']!='PRI' || $filed['primary']==1 && $curfields[$fieldkey]['Key']!='PRI')
								$newfield="`".$fieldkey."` ".$field["type"].($field['auto_increment']==1?" auto_increment":"")." NOT NULL".(isset($field["default"])?" default '".(string)$field['default']."'":" default ''");
								$oldfield="`".$tmp['Field']."` ".$tmp["Type"].$tmp["Extra"]." NOT NULL default '".(strpos($tmp["Default"],'.')!==false?floor($tmp["Default"]):$tmp["Default"])."'";
								//dump($oldfield);dump($newfield);
								if($oldfield!=$newfield){
									$query.=" modify column ".$newfield.",";
								}
							}
						}
					}
				}
				if($query!=''){
					$sql.="alter table `".$table."`".$query;
					if(substr($sql,strlen($sql)-1,1)==",") $sql=substr($sql,0,strlen($sql)-1);
					$sqlresult=M()->execute($sql);
					if($sqlresult===false){
						$result=false;
					}else{
						$result=true;
					}
					if($result==false){
						$this->error('数据字段修正失败!'.$sql, '__APP__/Backup/index');
					}
				}
				
		   }
		}
		if(isset($result) && $result==true){
			if($success)
				$this->success('数据库修正成功!','__APP__/Backup/index');
		}else{
			if($success)
			$this->error('未修正数据!', '__APP__/Backup/index');
		}
	}
	/*
	* 查询数据库中的所有表名
	*/
	private function getTables($dbName = '') {
        if (!empty($dbName)) {
            $sql = 'SHOW TABLES FROM ' . $dbName;
        } else {
            $sql = 'SHOW TABLES ';
        }
        $result = $this->model->query($sql);
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }
	/*
	* 查询数据表中的所有字段名
	*/
	private function getView($table = ''){
		$info=M($table,' ')->get_Property("fields");
		if(isset($info['_autoinc']))
			unset($info['_autoinc']);
		if(isset($info['_pk']))
			unset($info['_pk']);
        return $info;
	}
	private function getField($table = ''){
		$info=array();
		if(!empty($table)){
			$sql = 'show columns from '.$table;
		}
		$re = $this->model->query($sql);
		if($re)$info = $re;
        return $info;
	}
	/*
	* 查询表中的索引
	*/
	private function getKey($table = ''){
		$info=array();
		if(!empty($table)){
			$sql = 'show index from '.$table;
		}
		$result = $this->model->query($sql);

		if(isset($result[0]['Sub_part'])){
			if(is_array($result) && !empty($result))
		  		$lianjie = "INDEX (`".$result[0]['Column_name']."`(".$result[0]['Sub_part'].")";
		}else{
			if(is_array($result) && !empty($result))
		  		$lianjie = "INDEX (`".$result['0']['Column_name']."`";
		} 
		foreach ($result as $key => $val) {
			if(isset($result[$key+1]) && $result[$key]['Key_name']==$result[$key+1]['Key_name']){
				if($result[$key+1]['Sub_part']){
			    	$lianjie .= ",`".$result[$key+1]['Column_name']."`(".$result[$key+1]['Sub_part'].")";
			    }else{
			    	$lianjie .= ",`".$result[$key+1]['Column_name']."`";
			    }
			}else{
				$lianjie .= ")";
			    $info[] = $lianjie;
			    if(isset($result[$key+1]) && $result[$key+1]['Non_unique']==0)$lianjie="unique ";else $lianjie='';//唯一键
			    if(isset($result[$key+1])){ 
				    if($result[$key+1]['Sub_part']){
				        $lianjie .= "INDEX (`".$result[$key+1]['Column_name']."`(".$result[$key+1]['Sub_part'].")";
				    }else{
				        $lianjie .= "INDEX (`".$result[$key+1]['Column_name']."`";
				    }
			    }
			}
		}

        return $info;
	}
	//对数据库日志的开关
	function logonoff()
	{	
		$logdata = M()->query("show VARIABLES like 'general_log'");
		if($logdata[0]['Value']=='ON')
		{
			$debugconf = require ROOT_PATH.'Admin/Conf/debug.php';
			$debugconf['DB_LOG']=false;
			$ret=file_put_contents(ROOT_PATH.'Admin/Conf/debug.php', strip_whitespace("<?php\nreturn " . var_export($debugconf , true) . ";\n?>"));
			M()->execute("set global general_log=off;");
			$this->success('关闭完成!','__APP__/Backup/mem_index');
		}
		else
		{
			$debugconf = require ROOT_PATH.'Admin/Conf/debug.php';
			$debugconf['DB_LOG']=true;
			$ret=file_put_contents(ROOT_PATH.'Admin/Conf/debug.php', strip_whitespace("<?php\nreturn " . var_export($debugconf , true) . ";\n?>"));

			M()->execute("set global general_log=on;");
			M()->execute("set global log_output='TABLE';");
			M()->execute("TRUNCATE TABLE mysql.general_log;");
			$this->success('启动完成!','__APP__/Backup/mem_index');
		}
	}
	function outlog()
	{
		header("Content-Type: application/text; charset=UTF-8");
		header("Content-Disposition: attachment; filename=\"log.sql\"");
		$datas=M()->query('select * from mysql.general_log');
		$noout=array(
		"set @@character_set_server='utf8'",
		"SET sql_mode=''",
		"show VARIABLES like 'general_log'",
		"SET NAMES 'utf8'",
		"set global general_log=off",
		'select * from mysql.general_log',
		);
		foreach($datas as $data)
		{
			if(strpos($data['argument'],'SHOW COLUMNS FROM') === false && $data['command_type']=='Query' && !in_array($data['argument'],$noout))
			{
				if(strpos($data['argument'],'set @file=')===false)
				{
					echo $data['event_time'].'|'.$data['thread_id'].'|'.$data['argument']."\r\n";
				}
				else
				{
					echo '/*'.str_replace(array("'",'set @file='),array('',''),$data['argument'])."*/\r\n";
				}
			}
		}
	}
	//清除日志
	function clearlog()
	{	
		M()->execute("truncate table log");
		M()->execute("truncate table dms_log_user");
		$this->success('清除完成!','__APP__/Backup/mem_index');
	}
}
?>