<?php
// 本类由系统自动生成，仅供测试用途
defined('APP_NAME') || die('小样，还想走捷径!');
class LockAction extends CommonAction {
	public $bai=array();
	public $hei=array();
	
	public function index(){
		$this->display();
	}
	/*
	* 执行加密操作
	* 
	*/
	public function lock(){
		//符合黑名单条件不加密，但是如果内部存在白名单定义，会局部加密
		$this->hei=array("ThinkPHP/",'config.inc','Conf/','Lang/','PrizeData/','InstallData/','Runtime/','languages/');
		//符合白名单出现必须加密
		$this->bai=array("ThinkPHP/Extend/Library/COM/");
		@set_time_limit(0);
		/**
		 * 源码文件夹
		 * 目标文件夹 
		 * 
		 */
		$source_dir = ROOT_PATH;
		$target_dir = ROOT_PATH;
		$source_dir = $this->filter_path($source_dir);
		$target_dir = $this->filter_path($target_dir);
		if (!extension_loaded("jinhou")) {
			$this->error('jinhou扩展 尚未安装。');
		}
		/*if ($this->is_related($source_dir, $target_dir)) {
			$this->error('源文件夹和目标文件夹不可为从属关系！');
		}*/
		//if(!$this->is_related($source_dir,$target_dir)){
		$source_list = array($source_dir);
		//}
		$dir = null;
		clearstatcache();
		while (count($source_list) > 0) {
			$dir = array_pop($source_list);
			$files = glob($dir . '/*');
			if(in_array($target_dir,$files)){
				$key = array_search($target_dir,$files);
				array_splice($files,$key,1);
			}
			foreach ($files as $file) {
				$file = $this->filter_path($file);
				if (is_file($file)) {
					$target_path = str_replace($source_dir,$target_dir, $file);
					$this->make_dir($target_dir);
					//判断是否需要加密
					if ($this->is_encode($file)){
						//判断是否已经加密
						if($this->is_encoded($file)){
							$this->encode_check($file);
							$code = file_get_contents($file);
							$code = $this->enclose($code);
							//加密函数/链接加密函数
							$code = jhen($code);
							$code = $this->output($code);
							file_put_contents($target_path,$code);
						}
					}
				} else {
					$this->make_dir($dir);
					$source_list[] = $file;
				}
			}
		}
		$this->success('加密完成');
	}
	/**
	*  判断文件是否需要加密
	*  需要加密的文件会被复制
	* 
	* @param string $path 文件路径
	* @return boolean  是否需要加密
	**/
	function is_encode($path) {
		//默认进行加密
		$ret=true;
		//取得文件名
		$name = basename($path);
		//得到文件相对全路径
		$xdpath=str_replace(ROOT_PATH,'',$path);
		$xdpath=str_replace($name,'',$xdpath);
		//取得扩展名
		$ext = pathinfo($path, PATHINFO_EXTENSION);		
		//判定扩展名不需要加密，则直接退
		if (!in_array($ext, array('php', 'phpt', 'php4', 'php5'))) {
			return false;
		}
		//遍历黑名单，不加密名单
		foreach($this->hei as $hei)
		{
			//如果完全等于黑名单文件名
			if($hei == $name)
			{
				$ret=false;
			}
			//如果路径中包含黑名单
			if(strpos($xdpath,$hei)!==false)
			{
				$ret=false;
			}
		}
		//遍历黑名单，必加密名单
		foreach($this->bai as $bai)
		{
			//如果完全等于白名单文件名
			if($bai == $name)
			{
				$ret=true;
			}
			//如果路径中包含黑名单
			if(strpos($xdpath,$bai)!==false)
			{
				$ret=true;
			}
		}
		return $ret;
	}
	function filter_path($path) {
		return preg_replace(array('/([\\\\\/]+)/', '/\/$/'), array('/', ''), $path);
	}
	function make_dir($dir) {
		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
			chmod($dir, 0777);
		}
	}

	/**
	* PHP必须使用 \ 为转义   ?\> 结尾
	* 
	* @param string $code
	* @return string 
	**/
	function enclose($code) {
		do {
			$tokens = token_get_all($code);
			if (!$tokens) {
				break;
			}
			$enclose = false;
			$last_token = end($tokens);
			if ($last_token == ';') {
				$enclose = true;
				break;
			}
			if (is_array($last_token)) {
				if ($last_token[0] != T_INLINE_HTML && $last_token[0] != T_CLOSE_TAG) {
					$enclose = true;
				}
			}
		} while (0);
		if ($enclose) {
			$code .= '?>';
		}
		return $code;
	}
	/**
	 *  判断文件是否已加密
	 */
	function is_encoded($path) {
		$php_list = $this->find_all_php($path);
		foreach( $php_list as $php )
		{
			$content = file_get_contents($php);
			if( strpos($content,'xs_run')=== false && strpos($content,'jhgo(')=== false){
				return true;
			}
			return false;
		}
	}
	/**
	 *  判断文件是否写结束
	 *	若没写则在这里补全
	 */
	function encode_check($dir)
	{
	  $php_list = $this->find_all_php($dir);
	  //遍历所有php文件检查是否写 结束
	  foreach( $php_list as $php)
	  {
		$content = file_get_contents($php);
		if( strpos($content,'?>') === false )
		{
		  //echo "error::::{$php}\r\n";
		  $content .= "\r\n?>";
		  //此处自动补足尾部结束符
		  file_put_contents($php,$content);
		}
	  }
	}
	//找出所有php文件
	function find_all_php($dir)
	{
	  $file_list = array();
	  $list  = $this->listFile($dir);
	  foreach( $list as $file)
	  {
		//var_dump($file);
		if( $file['type'] == 'dir' )
		{
		  $this->list_dir($file['pathname']."\\",$file_list);
		}
		else
		{
		  if( $file['ext'] == 'php' )
		  {
			$file_list[] = $file['pathname'];
		  }
		}
	  }
	  return $file_list;
	}
	//递归获取php文件
	function list_dir($path,&$file_list)
	{
	  $list  = $this->listFile($path);
	  foreach( $list as $file)
	  {
		//echo $file['pathname']."\r\n";
		if( $file['type'] == 'dir' )
		{
		  //echo $file['pathname']."\r\n";
		  $this->list_dir($file['pathname']."\\",$file_list);
		}
		else
		{
		  if( $file['ext'] == 'php' )
		  {
			
			$file_list[] = $file['pathname'];        
		  }
		}
	  }
	}
	//列出目录下的所有文件
	function listFile($pathname,$pattern='*')
	{
	  $guid  =  md5($pathname.$pattern);
	  $dir = array();
	  $list  =  glob($pathname.$pattern);
	  foreach ($list as $i=>$file){
		  $dir[$i]['filename']    = basename($file);
		  $dir[$i]['pathname']    = realpath($file);
		  $dir[$i]['type']        = filetype($file);
		  $dir[$i]['ext']      =  is_file($file)?strtolower(substr(strrchr(basename($file), '.'),1)):'';
	  }
	  return $dir;
	}
	/**
	* 输出到模板
	* @param string $code 加密后的密文
	* @return string 最终输出代码 
	**/
	function output($code) {
		$copyright = "本程序已加密: ";
		$date = date('Y-m-d H:i:s');
		return sprintf('<?php
		/* %s */
		jhgo(\'%s\');
		?>', $copyright.$date, $code);
	}
	/*
	* 压缩文件
	*/
	public function compress($isfun=false,$encode=false){
		if(is_dir(ROOT_PATH.'encoded'))
		{
			$this->deldir( ROOT_PATH.'encoded');
		}
		//删除更目录下的zip压缩文件
		$filelist = glob(ROOT_PATH . '/*');
		if(in_array(ROOT_PATH,$filelist)){
			$key = array_search(ROOT_PATH,$filelist);
			array_splice($filelist,$key,1);
		}
		foreach($filelist as $file_name){
			$file_name = $this->filter_path($file_name);
			if(is_file($file_name)){
				$ext = pathinfo($file_name, PATHINFO_EXTENSION);
				if($ext=="zip"){
					unlink($file_name);
				}
			}
		}
		import("COM.zip.zip");
		$zip = new zip();
		//所有日志文件不需要上传
		$zip->addblack('.log');
		$zip->addblack('.zip');
		$zip->addblack('/.svn');
        $zip->addblack('/.git');
		$zip->addblack('dbbackup');
		if(I("get.encode/s","null")!="null" || $encode)
		{
			$zip->addFilter(array($this,'encodeFilter'));
		}
		srand((double)microtime() * 1000000); 
		$encrypt_key = md5(rand(0, 32000)); 
		$cold=$encrypt_key.".zip";
		$result = $zip ->createZip( ROOT_PATH,ROOT_PATH.$cold);
		if(!$isfun)
		{
			if($result){
				$this->success('操作完成');
			}else{
				$this->error('操作失败');
			}
		}
		else
		{
			return $cold;
		}
	}
	//加密过滤器
	public function encodeFilter($rootpath,$path,$data)
	{
		$name = basename($path);
		$ext = pathinfo($rootpath, PATHINFO_EXTENSION);		
		//判定扩展名不需要加密，则直接退
		if (!in_array($ext, array('php', 'phpt', 'php4', 'php5'))) {
			return $data;
		}
		foreach(array("ThinkPHP/",'config.inc','Conf/','Lang/','PrizeData/','config.php','Runtime/','languages/') as $hei)
		{
			if($name == $hei)
			{
				return $data;
			}
			//如果路径中包含不需要加密的类型
			if(strpos($path,$hei)!==false)
			{
				return $data;
			}
		}
		//已经加密过
		if( strpos($data,'xs_run')!== false || strpos($data,'jhgo(')!== false ){
			return $data;
		}
		$data = $this->enclose($data);
		//加密函数/链接加密函数
		$data = jhen($data);
		$data = $this->output($data);
		return $data;
	}
	/*
	*	先加密文件，然后将加密文件进行压缩操作
	*
	*	压缩文件
	*
	*/
	function deldir($dir) {
	  //先删除目录下的文件：
	  $dh=opendir($dir);
	  while ($file=readdir($dh)) {
		if($file!="." && $file!="..") {
		  $fullpath=$dir."/".$file;
		  if(!is_dir($fullpath)) {
			  unlink($fullpath);
		  } else {
			  $this->deldir($fullpath);
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