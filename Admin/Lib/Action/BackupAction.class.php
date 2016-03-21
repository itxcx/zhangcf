<?php
defined('APP_NAME') || die('不要非法操作哦!');
import("Admin.Action.CommonAction");
class BackupAction extends CommonAction  {
 
    public $config = '';                                                        //相关配置
    public $model = '';                                                         //实例化一个model
    public $content;                                                            //内容
    public $dbName = '';
    public $dbuser = '';
    public $dbpass = '';                                                        //数据库名
    public $dir_sep = '/';                                                      //路径符号
	public $backupBeforeRecover = true;											//恢复数据前备份数据库
    
    //初始化数据
 
     public function _initialize() {
		//header("Content-type: text/html;charset=utf-8");
		//import('DmsAdmin.DMS.stru');
		//$this->con=new stru();
        parent::_initialize();
        set_time_limit(0);                                                      //不超时
        ini_set('memory_limit','1500M');
        $this->config = array(
            'path' => "dbbackup/",                          //备份文件存在哪里
            'isCompress' => 0,                                                  //是否开启gzip压缩 
        );
        $this->dbName = C('DB_NAME');  
        $this->dbuser = C('DB_USER');
        $this->dbpass = C('DB_PWD');                                         //当前数据库名称
        $this->model = new Model();
        if(!file_exists($this->config["path"])){
            mkdir($this->config["path"],0777);
        }
    }
    /*
    * 数据库备份文件列表
    */
    function index() {
        $list=$this->backList();
        $list=$this->tsort($list);
        $this->assign("dbname",$this->dbName);
        $this->assign('list', $list);
        $this->display();
    }
	function men_index(){
		//检查日志开启或者关闭情况
		$logdata=M()->query("show VARIABLES like 'general_log'");
		$this->assign('logstate', $logdata[0]['Value']);
		if( defined('APP_DEBUG') &&  APP_DEBUG )
		{
			$this->assign('product_reset', true);
		}
		$this->display();
	}
	/*
    * 备份操作界面
    */ 
    function back(){
        $this->display();
    }
   	public  function clear(){
        $this->display();
    }
    /* -
     * +------------------------------------------------------------------------
     * * @ 已备份数据列表
     * +------------------------------------------------------------------------
     */
    private function backList(){
        $path = $this->config['path'];
        $fileArr = $this->MyScandir($path);
        $list=array();
        foreach ($fileArr as $key => $value) {
            if ($key > 1) {
                //获取文件创建时间
                $fileTime = date('Y-m-d H:i:s', filemtime($path . $value));
                $fileSize=$this->getFileSize($value);
                //构建列表数组
                if(substr($value,-4)=='.zip'){
                    $shortname=substr($value,33,-4);
                }else if(substr($value,-4)=='.sql'){//sql文件
                    $shortname=base64_encode($value);
                }else if(substr($value,-5)=='.xsdb'){//sql文件
                    $shortname=substr($value,33,-5);
                }else if(strpos($value,'.bak')){//删除两天前.bak+time()的文件
                	if(substr($value,-10)<time()-86400*2){
                		unlink($path . $value);
                	}
                	continue;
                }else{
                	continue;
                }
                
				if(time() - filemtime($path . $value) > 72*3600 && base64_decode(str_replace(" ","+",urldecode($shortname))) == '数据恢复前备份'){
					unlink($path . $value);
				}else{
					$list[] = array(
						'shortname' =>base64_decode(str_replace(" ","+",urldecode($shortname))),
						'name' => urlencode($value),
						'time' => $fileTime,
						'size' => $fileSize,
					);
				}
            }
        }
        $list=$this->tsort($list);
        return $list;
    }
    private function getFileSize($file){
        $path = $this->config['path'].$file;
        if(is_dir($path)){
            $fileArr = $this->MyScandir($path);
            $fileSize=0;
            foreach ($fileArr as $key => $value) {
                if ($key > 1) {
                    $fileSize += filesize($path .'/'. $value) / 1024;     
                }
            }
        }else{
            $fileSize=filesize($path)/1024;
        }
        $fileSize=$fileSize<1024 ? number_format($fileSize, 2).' KB' : number_format($fileSize / 1024, 2) . ' MB';
        return $fileSize;
    }
    /* -
     * +------------------------------------------------------------------------
     * * @ 已备份列表  按时间排序
     * +------------------------------------------------------------------------
     */
     
     private function tsort($ary){
        for($i=0; $i<count($ary) ;$i++){
            for($j=0; $j<$i; $j++){
                if($ary[$i]['time'] > $ary[$j]['time']){
                    $temp = $ary[$i];
                    $ary[$i] = $ary[$j];
                    $ary[$j] = $temp;
                }
	        }
        }
        return $ary;
    }
    
    /* -
     * +------------------------------------------------------------------------
     * * @ 获取数据表
     * +------------------------------------------------------------------------
     */
 
    function tablist() {
        $list = $this->model->query("SHOW TABLE STATUS FROM {$this->dbName}");  //得到表的信息
        //echo $Backup->getLastSql();
        $this->assign('list', $list);
        $this->display();
    }
    /* -
     * +------------------------------------------------------------------------
     * * @ 判断是否cli模式调用备份
     * +------------------------------------------------------------------------
     */
 	function prebackall(){
 		$name='新数据备份';
 		$name = I("post.backname/s")==""?$name:I("post.backname/s");
 		if(adminshow('cliSwitch')){
	 		//判断Windows还是Linux
	 		$wordpath =getcwd();//当前工作路径
	 		if(IS_WIN){
	 			$ini = ini_get_all();                    
		        $path = $ini['extension_dir']['local_value'];     
		        $php_path = str_replace('\\', '/', $path);             
		        $php_path = str_replace(array('/ext/', '/ext'), array('/', '/'), $php_path);           
		        $real_path =  'php.exe';
				chdir($wordpath);//更改当前工作路径
				$cmd = $real_path." ".$wordpath."/clibr.php Backup backall backname,".$name." >recerr.log";
				pclose(popen("start /B ". $cmd, "r"));  
	 		}else{
	 			chdir($wordpath);
	 			$cmd="php ".$wordpath."/clibr.php Backup backall backname,".$name." >recerr.log";
				exec($cmd . " &",$out,$re);
	 		}
	 		//$this->ajaxReturn(array(),"正在备份中",0);
 		}else{
			$result=$this->backall($name);
			if($result==""){
				$this->ajaxReturn(array(),"备份完成,用时".G('run','end').'秒',1);
			}else{
				$this->ajaxReturn(array(),$result,0);
			}
		}
 	}
 
    /* -
     * +------------------------------------------------------------------------
     * * @ 备份整个数据库
     * +------------------------------------------------------------------------
     */
    function backall($name='新数据备份',$return = false) {
    	/**启动CLI模式备份，创建个标识文件，以控制整个备份过程是否完成**/
	    if(!lockfile('cliing','备份')){
    		if(file_get_contents(ROOT_PATH.'cliing.lock')=='备份'){
    			if(IS_CLI){
    				die;
    			}
    			else{
    				$this->error("备份进行中，请等待...",'__URL__/index');
    			}
    		}
    	}
    	$op = file_get_contents(ROOT_PATH.'cliing.lock');
    	if(IS_CLI){
			file_put_contents(LOG_PATH.'cli.log', $op."||开始备份.".PHP_EOL);
		}
		////********************************************************///
    	G('run');
    	$stime = time();
        $name = I("get.backname/s")!=""?I("get.backname/s"): $name;
        $backname=trim($name);
        $backname=str_replace(" ","",$backname);
        $backname = base64_encode($backname);///???
		$tables = $this->getTables();
		
		$bktype = 0;//原始备份方式
		//判断网站和数据库是否在同一台服务器上
		$dbhost = strtolower(C('DB_HOST'));
		if($dbhost=='localhost' || $dbhost=='127.0.0.1'){
			//判断用户不是root用户
			if(strtolower($this->dbuser)=='root'){
				$bktype = 1;
			}else{//判断当前非root用户是否有FILE权限
				$grant = M()->query("show grants");
				if($grant){
					foreach($grant as $val){
						$gt = current($val);
						if(stripos($gt,'FILE')!==false && stripos($gt,'ON *.*')!==false ){
							$bktype = 1;
						}
					}
				}
			}
		}
        $bktype = 0;//由于测试和正式权限经常不足，暂时不用outfile备份数据
		srand((double)microtime() * 1000000); 
		$encrypt_key = rand(0, 32000);
		if($bktype==1){
			$fileName = $this->trimPath($this->config['path'] . md5(date('YmdHis').$encrypt_key) . 'B' . $backname. '.xsdb');
			import("COM.BakRec.BakRec");
			$BakRec = new BakRec(realpath($this->config['path']));
		}else{
			$fileName = $this->trimPath($this->config['path'] . md5(date('YmdHis').$encrypt_key) . 'A' . $backname. '.xsdb');
			import("COM.BakRec.BackRec");
			$BakRec = new BackRec();
		}
		$mess = $BakRec->backup($tables,$fileName);
		if(IS_CLI){
			if ($mess =="") {
				$this->saveAdminLog('','',"数据库备份",'备份名称：'.$name);
				file_put_contents(LOG_PATH.'cli.log', $op."||SUCCESS".PHP_EOL, FILE_APPEND);
			}else{
				file_put_contents(LOG_PATH.'cli.log', $op."||ERROR：".$mess.PHP_EOL, FILE_APPEND);
			}
		}else{
			if ($mess =="") {
				$this->saveAdminLog('','',"数据库备份",'备份名称：'.$name);
				if(!$return){
					return $mess;
				}
			} else {
				if(!$return){
					return $mess;
				}
			}
		}
    }

	//备份config表
	public function backConfig(){
		unlink(ROOT_PATH.'Admin/Common/dbbackup/sysconfig.sql');
		$fileName = $this->trimPath($this->config['path'] .'sysconfig.sql');
		import("COM.BakRec.BakRec");
		$BakRec = new BakRec(realpath($this->config['path']));
		$mess = $BakRec->backup(array('config'),$fileName);
		if ($mess =="") {
			$this->success("备份完成",'__URL__/index');
		} else {
            $this->error($mess,'__URL__/index');
        }
	}
	//还原配置文件
	public function recoverConfig(){
		$message=$this->recoverFile('sysconfig.sql');
		if ($message=="") {
			 $this->success('数据库还原成功！');
		} else {
			 $this->error($message.'<br/>数据库还原失败!');
		}
	}
	/* -
     * +------------------------------------------------------------------------
     * * @ 判断是否cli模式调用还原
     * +------------------------------------------------------------------------
     */
 	function prerecover(){
 		$name=urlencode(I("request.file/s"));
 		if(adminshow('cliSwitch')){
	 		//判断Windows还是Linux
            $wordpath =getcwd();//当前工作路径
	 		if(IS_WIN){
	 			$ini = ini_get_all();                    
		        $path = $ini['extension_dir']['local_value'];           
		        $php_path = str_replace('\\', '/', $path);           
		        $php_path = str_replace(array('/ext/', '/ext'), array('/', '/'), $php_path);           
		        $real_path = 'php.exe';//$php_path . 
				chdir($wordpath);//更改当前工作路径
				$cmd = $real_path." ".$wordpath."/clibr.php Backup recover file,".$name." >recerr.log";
				pclose(popen("start /B ". $cmd, "r"));  
	 		}else{
	 			chdir($wordpath);
	 			$cmd="php ".$wordpath."/clibr.php Backup recover file,".$name." >recerr.log";
				exec($cmd . " &",$out,$re);
	 		}
	 		$this->ajaxReturn(array(),"正在还原中",1);
 		}else{
			$result=$this->recover($name);
			if(is_array($result)){
				$this->success($result['info']);
			}else{
				$this->error($result);
			}
		}
 	}
    //还原数据库
    function recover($name='') {
    	$SYSTEM_STATE=
    	M()->startTrans();
    	CONFIG('SYSTEM_STATE',2);//维护
    	M()->commit();
    	$true=true;
		B('SaveConfig',$true);
    	/**启动CLI模式还原，创建个标识文件，以控制整个备份过程是否完成**/
	    if(!lockfile('cliing','还原')){
    		if(file_get_contents(ROOT_PATH.'cliing.lock')=='还原'){
    			if(IS_CLI){
    				die;
    			}else{
    				return "还原进行中，请等待。。。";
    			}
    		}
    	}
		////********************************************************///
    	G('run');
		$backname = ($name!='')?$name:I("get.file/s");
		if(substr($backname,-4)=='.sql'){//sql文件
            $name=$backname;
        }else{
			$name=base64_decode(str_replace(" ","+",urldecode(substr($backname,33,-4))));
		}
		if(IS_CLI){
			//写入cli.log
			file_put_contents(LOG_PATH.'cli.log', "还原||开始还原".PHP_EOL);
		}
		// 还原数据库前对数据库备份
		if($this->backupBeforeRecover){
			$mess = '';
			if(IS_CLI){
				//写入cli.log
				file_put_contents(LOG_PATH.'cli.log', "还原||还原前备份".PHP_EOL, FILE_APPEND);
				$this->backall('数据恢复前备份',true);
			}else{
				if(get_client_ip()!='127.0.0.1'){
					$this->backall('数据恢复前备份',true);
				}
			}
			if(IS_CLI){
				if ($mess =="") {
					//$this->saveAdminLog('','',"数据恢复前备份",'恢复备份：'.$name.'前备份数据库');
				}else{
					file_put_contents(LOG_PATH.'cli.log', "还原||备份ERROR：".$mess.PHP_EOL, FILE_APPEND);
					exit;
				}
			}else{
				if ($mess =="" ) {
					$this->saveAdminLog('','',"数据恢复前备份",'恢复备份：'.$name.'前备份数据库');
				} else {
					return $mess;
				}
			}
		}
		M()->execute('unlock tables');
		//还原操作
		//$backname = str_replace(' ','+',$backname);
        $file_name = $this->trimPath($this->config['path'] . $backname);
        if(substr($backname,-5)=='.xsdb' || substr($backname,32,1)=="A" || substr($backname,32,1)=="B" || substr($backname,-4)=='.sql'){
        	if(substr($backname,32,1)=="B" || substr($backname,-4)=='.sql'){
	        	import("COM.BakRec.BakRec");
				$BakRec  = new BakRec(realpath($this->config['path']));
        	}else{
        		import("COM.BakRec.BackRec");
				$BakRec  = new BackRec();
        	}
            $message = $BakRec->recoverFile(urldecode($backname));
            if(IS_CLI){
				if ($message =="") {
					$this->saveAdminLog('','',"数据库还原",'还原备份：'.$name);
					file_put_contents(LOG_PATH.'cli.log', "还原||SUCCESS".PHP_EOL, FILE_APPEND);
				}else{
					file_put_contents(LOG_PATH.'cli.log', "还原||ERROR：".$message.PHP_EOL, FILE_APPEND);
				}
			}else{
	            if ($message=="") {
					$this->saveAdminLog('','',"数据库还原",'还原备份：'.$name);
					$t=true;
					B('SaveConfig',$t);
					////////////还原后修正数据//////////////
					$DbRevise = (A('DbRevise'));
	    			$DbRevise->index(false);
	    			////////////////////////////////////////
					Log::write('数据库还原成功！,用时'.G('run','end').'秒');
					if(!adminshow('cliSwitch'))
						return array('state'=>true,"info"=>'数据库还原成功！,用时'.G('run','end').'秒');
	            }else{
	            	return $message.'<br/>数据库还原失败!';
	            }
            }
        }
        $true=true;
		B('SaveConfig',$true);
    }
    //删除数据备份
    function deletebak(){
		$succNum = 0;
		$errNum = 0; 
		foreach(explode(',',I("get.file/s")) as $id){
			if($id == '') continue;
			$backname = str_replace(' ','+',$id);
			$file=$this->config['path'].$backname;
			//	判断目录 防止删除其他文件
			if(dirname(realpath($file))!==realpath($this->config['path'])){
				$this->error("删除失败");
			}
			$shortname=base64_decode(str_replace(" ","+",urldecode(substr($backname,33,-4))));
			if(substr($backname,-4)==".zip"){
				//不删文件 ，改.zip 为.bak+时间戳
				$newbackname = str_replace('.zip','.bak'.time(),$backname);
			}else if(substr($backname,-5)==".xsdb"){
				//不删文件 ，改.zip 为.bak+时间戳
				$newbackname = str_replace('.xsdb','.bak'.time(),$backname);
			}
			if (rename($file,$this->config['path'].$newbackname)) {
				$this->saveAdminLog('','',"数据库备份文件删除",'删除：'.$shortname);
				$succNum++;
			} else {
				$errNum++;
			}
		}
		if($errNum !=0){
			$this->error("删除成功：".$succNum .'条记录；删除失败：'.$errNum .'条记录；');
		}else{
			$this->success("删除成功：".$succNum .'条记录；');
		}
    }
    //自定义删除数据库备份
    function delectzi(){	
    	$list = $this->backList();
        foreach ($list as $key => $value) {		
			//获取文件创建时间
			$fileTime[] = strtotime($value['time']);
			$filname1[] = $value['name'];
		}
		if(I("request.shanj/s")!="" && I("request.shanq/s")!=""){
			$qitime=strtotime(I("request.shanq/s"));
			$jietime=strtotime(I("request.shanj/s"));
			$f=0;
			foreach($fileTime as $key=>$times){
				if($qitime<=$times && $times<=$jietime){
					$shortname=base64_decode(str_replace(" ","+",urldecode(substr($filname1[$key],33,-4))));
					$file=$this->config['path'].$filname1[$key];
					if(unlink($file)){
						$f++;
						$this->saveAdminLog('','',"数据库备份文件删除",'删除：'.$shortname);
					}
				}
			}
			if($f){
				$this->success('删除成功');
			}else{
				$this->error('删除失败');
			}
		}else{
			$this->error("删除失败，请填写结束日期和起始日期！");
		}
    }
    //清空数据库
    function cleandb() {
        $admin=M("admin");
		$where['id'] = $_SESSION[C('RBAC_ADMIN_AUTH_KEY')];
        $result=$admin->where($where)->field("password")->find();
        //修改，添加yubicloud验证通过
        if(chkpass(I("post.repwd/s"),$result['password']) || chkyubicloud(I("post.repwd/s"),$_SESSION['loginAdminAccount'])){
        	$this->backall('清空数据库前备份',true);
        	M()->startTrans();
            $this->cleanfun();
            M()->commit();
            //删除奖金构成文件
            import("COM.BakRec.BakRec");
			$BakRec = new BakRec(realpath($this->config['path']));
			$BakRec->remove_directory(ROOT_PATH.'DmsAdmin/PrizeData/',false);
			$this->saveAdminLog('','',"清空数据库");
            $this->success("操作成功！");
        }else{
            $this->error("管理员密码错误");
        }
    }
    function cleanfun(){
		$DmsApp = M('node',null)->where("name='DmsAdmin' and level=1 and is_sync_menu=1 and type=0")->order("sort asc")->find();
		if($DmsApp){
			R('DmsAdmin://Admin/SyncDmsAdminAdmin/clearSystemData');
		}
		CONFIG('TIMEMOVE_DAY' ,0);
		CONFIG('TIMEMOVE_HOUR',0);
		CONFIG('CAL_START_TIME',strtotime(date('Y-m-d',time())));
		$model = M();
		$model->execute('truncate table `dms_邮件'.'`');
		$model->execute('truncate table `dms_公告'.'`');
		$model->execute('truncate table `dms_短信'.'`');
		$model->execute('truncate table `dms_短信详细'.'`');
		$model->execute('truncate table `dms_短语'.'`');
    }
 
    /* -
     * +------------------------------------------------------------------------
     * * @ 获取 目录下文件数组
     * +------------------------------------------------------------------------
     * * @ $FilePath 目录路径
     * * @ $Order    排序
     * +------------------------------------------------------------------------
     * * @ 获取指定目录下的文件列表，返回数组
     * +------------------------------------------------------------------------
    */
    private function MyScandir($FilePath = './', $Order = 0) {
        $FilePath	= opendir($FilePath);
		$passed		= array('.svn','sysconfig.sql');
		
        while ($filename = readdir($FilePath)) {
			if(in_array($filename,$passed)) continue;
            $fileArr[] = $filename;
        }
        $Order == 0 ? sort($fileArr) : rsort($fileArr);
        return $fileArr;
    }
    private function trimPath($path) {
        return str_replace(array('/', '\\', '//', '\\\\'), $this->dir_sep, $path);
    }
    /* -
     * +------------------------------------------------------------------------
     * * @ 获取数据库的所有表
     * +------------------------------------------------------------------------
     * * @ $dbName  数据库名称
     * +------------------------------------------------------------------------
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
            if(current($val)=='log')continue;//过滤掉log表
            $info[$key] = current($val);
        }
        return $info;
    }
    /* -
     * +------------------------------------------------------------------------
     * * @ 把传过来的数据 按指定长度分割成数组
     * +------------------------------------------------------------------------
     * * @ $array 要分割的数据
     * * @ $byte  要分割的长度
     * +------------------------------------------------------------------------
     * * @ 把数组按指定长度分割,并返回分割后的数组
     * +------------------------------------------------------------------------
     */
 
    private function chunkArrayByByte($array, $byte = 51200) {
        $i = 0;
        $sum = 0;
        $return = array();
        foreach ($array as $v) {
            $sum += strlen($v);
            if ($sum < $byte) {
                $return[$i][] = $v;
            } elseif ($sum == $byte) {
                $return[++$i][] = $v;
                $sum = 0;
            } else {
                $return[++$i][] = $v;
                $i++;
                $sum = 0;
            }
        }
        return $return;
    }
 

    function query_sql()
	{
		$this->display();
	}
	 function querysql()
	{
		$sql	= trim(base64_decode(str_replace(" ","+",I("request.sql/s"))));
		$list	= M()->query($sql);
		if(!$list)
		{
			$this->error("没有查询结果，请检查输入的SQL语句是否准确！");
		}
		else
		{
			$data	= "<table><tr>";
			
			foreach($list[0] as $key =>$value)
			{
				$data	.= "<td><nobr>".$key."</nobr></td>";
			}
			foreach($list as $value)
			{
				$data.='<tr>';
				foreach($value as $rowval)
				{
				$data.="<td><nobr>".$rowval."</nobr></td>";
				}
				$data.='</tr>';
			}
			$data	.= "</tr></table>";
			$this->assign('vo',$data);
		 	$this->display();
		}
	}

public function querysql2(){
		$sql	= trim(I("request.sql/s"));
		//dump($sql);
		$list = M()->query($sql);


		if(Extension_Loaded('zlib')){Ob_Start('ob_gzhandler');}
        
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        $title =date("YmdHis");
        header("Content-Disposition: attachment; filename=\"excel_{$title}.xls\"");
       
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        echo '<html xmlns="http://www.w3.org/1999/xhtml">';
        echo '<head>';
        echo '<title>Untitled Document</title>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        echo '</head>';
        echo '<body>';
	   echo '<table cellspacing="0" cellpadding="3" rules="all" bordercolor="#93BEE2" border="1" style="background-color:White;border-color:#93BEE2;border-width:1px;border-style:None;width:auto;border-collapse:collapse;">';
		echo '<tr align="center" style="color:white;background-color:#337FB2;font-weight:bold;">';
        foreach($list[0] as $key =>$value){
           echo '<th>'.$key.'</th>';
        }
        echo '</tr>';
        
		foreach($list as $value){
			echo '<tr>';
			foreach($value as $rowval)
			{
				echo "<td><nobr>".$rowval."</nobr></td>";
			}
			echo '</tr>';
		}
			
        echo '</table>';
        echo '</body>';
        echo '</html>';
        //G('end');
        //echo G('begin','end').'s';
		//if(Extension_Loaded('zlib'))  
		if(Extension_Loaded('zlib')) Ob_End_Flush();
	}

	function parse_sql_str($sql)
    {
        /* 如果SQL文件不存在则返回false */
        if ( $sql == '' )
        {
            return false;
        }


        /* 删除SQL注释，由于执行的是replace操作，所以不需要进行检测。下同。 */
        //$sql = $this->remove_comment($sql);

        /* 删除SQL串首尾的空白符 */
        //$sql = trim($sql);

        /* 如果SQL文件中没有查询语句则返回false */
        if (!$sql)
        {
            return false;
        }

        /* 替换表前缀 */
        //$sql = $this->replace_prefix($sql);

        /* 解析查询项 */
        $query_items=array();
        $sql = str_replace("\r", '', $sql);
		$sql = str_replace("\n", '', $sql);
		//按照文字拆分成数组
		$sqlarrs=$this->mbstringtoarray($sql,"utf-8");
		//当前嵌套字符
		$nestingstr='';
		//当前行字符
		$linestr='';
		foreach($sqlarrs as $arr)
		{
			//判断如果存在嵌套字符判断
			if($arr=='"'||$arr=="'")
			{
				//如果没有嵌套,则设置嵌套字符
				if($nestingstr=='')
				{
					$nestingstr=$arr;
				}
				else
				{
					//如果存在嵌套,并且当前字符和嵌套字符一样,表示嵌套闭合
					if($nestingstr==$arr)
					{
						$nestingstr='';
					}
				}
			}
			//当前行字符增加
			$linestr.=$arr;
			//判断如果有;并且当前是属于嵌套闭合,则输出行
			if($arr==';'&&$nestingstr=='')
			{
				$query_items[]=$linestr;
				$linestr='';
			}
		}
        return $query_items;
    }

	public function mbstringtoarray($str,$charset) {
		set_time_limit(0);
		$strlen=mb_strlen($str);
		while($strlen){
			$array[]=mb_substr($str,0,1,$charset);
			$str=mb_substr($str,1,$strlen,$charset);
			$strlen=mb_strlen($str);
		}
		return $array;
	 }  
	 
	 public function getstateajax(){
	 	//判断cliing.lock文件是否锁定 如果存在 则是正在进行备份、还原中
    	if(is_lockfile('cliing')) {  
	        $fp = file(LOG_PATH.'cli.log');
	        $re = explode("||",$fp[count($fp)-1]);
			$data = array("log"=>$re[1],'op'=>$re[0]);
			$this->ajaxReturn($data,'运行中',1);
	 	}else{
	 		$data=array();
	 		if(file_exists(LOG_PATH.'cli.log')){
	 			$fp = file(LOG_PATH.'cli.log');
	 			$re = explode("||",$fp[count($fp)-1]);
				$data = array("log"=> str_replace(PHP_EOL, '', $re[1]),'op'=>$re[0]);
	 		}
	 		$this->ajaxReturn($data,'无程序在运行',0);
	 	}
	 }
}

?>