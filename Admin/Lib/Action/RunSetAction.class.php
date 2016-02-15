<?php
//运行状态模块，设置系统日志，以及运行模式，清空缓存功能
class RunSetAction extends CommonAction 
{
	public function index()
	{
		$debugCon = require ROOT_PATH.'Admin/Conf/debug.php';
		$this->assign('debug_state'  ,$debugCon['APP_DEBUG']);
		$this->assign('warning_state',$debugCon['WARNING_STATE']);
		$this->assign('sql_state'    ,$debugCon['LOG_RUNSQL']);
		//错误级别
		$logLevelArr = explode(',' ,$debugCon['LOG_LEVEL']);
		$this->assign('logLevelArr',$logLevelArr);
		//日志文件信息
		$adminlog = array();
		foreach(glob(ROOT_PATH.'/Admin/Runtime/Logs/*') as $start_file)
		{
			$adminlog[]=array('name'=>basename($start_file),'size'=>$this->getFileSize($start_file));
		}
		$this->assign('adminlog',$adminlog);
		
		//日志文件信息
		$adminlog = array();
		foreach(glob(ROOT_PATH.'/DmsAdmin/Runtime/Logs/*') as $start_file)
		{
			$adminlog[]=array('name'=>basename($start_file),'size'=>$this->getFileSize($start_file));
		}
		$this->assign('dmslog',$adminlog);		
		$this->display();
	}
	//sql日志设置
	public function sqlLogSet()
	{
		$state = I('get.state/d');
		$debugCon=require ROOT_PATH.'Admin/Conf/debug.php';
		
		//关闭调试模式
		if($state=='0')
		{
			$debugCon['LOG_RUNSQL']=false;
			//清空缓存
		}
		else
		{
			$debugCon['LOG_RUNSQL']=true;
		}
		F('debug',$debugCon,ROOT_PATH.'Admin/Conf/');
		$this->success("设置完成",'',array('state'=>$debugCon['LOG_RUNSQL'],'name'=>'sql'));
	}
	public function sqlLogClear()
	{
		file_put_contents(ROOT_PATH.'Admin/Runtime/Logs/runsql.sql','');
		$this->success("清空完成");
	}
	public function debugSet()
	{
		$state = I('get.state/d');
		$debugCon=require ROOT_PATH.'Admin/Conf/debug.php';
		
		//关闭调试模式
		if($state=='0')
		{
			$debugCon['APP_DEBUG']=false;
			//清空缓存
			import("COM.RunTime.RunTimeClear");
			RunTimeClear::clear();
		}
		else
		{
			$debugCon['APP_DEBUG']=true;
		}
		F('debug',$debugCon,ROOT_PATH.'Admin/Conf/');
		$this->success("设置完成",'',array('state'=>$debugCon['APP_DEBUG'],'name'=>'debug'));
	}
	//设置警告开关
	public function waringSet()
	{
		$state = I('get.state/d');
		$debugCon=require ROOT_PATH.'Admin/Conf/debug.php';
		
		//关闭警告提醒
		if($state=='0')
		{
			$debugCon['WARNING_STATE']=false;
		}
		else
		{
			$debugCon['WARNING_STATE']=true;
		}
		F('debug',$debugCon,ROOT_PATH.'Admin/Conf/');
		$this->success("设置完成",'',array('state'=>$debugCon['WARNING_STATE'],'name'=>'warning'));
	}

	public function logeSet()
	{
		$logLevel = '';
		foreach(I("post.LOG_LEVEL/a") as $level){
			$logLevel .= $level.',';
		}
		$logLevel = trim($logLevel,',');
		
		$debugCon=require ROOT_PATH.'Admin/Conf/debug.php';
		$debugCon['LOG_LEVEL'] =$logLevel;
		$debugCon['LOG_RECORD']=($logLevel != '');
		F('debug',$debugCon,ROOT_PATH.'Admin/Conf/');
		$this->success("日志设置完成");
	}
	//下载日志
	public function getlog()
	{
		ob_start(
			function ($content) 
			{ 
				if( !headers_sent() && 
				extension_loaded("zlib") && 
				strstr($_SERVER["HTTP_ACCEPT_ENCODING"],"gzip")) 
				{ 
				$content = gzencode($content,9); 
				header("Content-Encoding: gzip"); 
				header("Vary: Accept-Encoding"); 
				header("Content-Length: ".strlen($content)); 
				} 
				return $content;
			} 
		);
		$type     = I('get.type');
		$filename = I('get.filename');
		$filearray=array();
		if($type == 'admin')
		{
			$filearray=glob(ROOT_PATH.'/Admin/Runtime/Logs/*');
		}
		if($type == 'dms')
		{
			$filearray=glob(ROOT_PATH.'/DmsAdmin/Runtime/Logs/*');
		}
		if($filearray)
		{
			foreach($filearray as $file)
			{
				if(basename($file) == $filename)
				{
					//输出文件
					//文件的类型
					header('Content-type: application/octet-stream');
					//下载显示的名字
					header('Content-Disposition: attachment; filename="'.$filename.'"');
					readfile($file);
					ob_end_flush(); 
					exit();
					die();
				}
			}
		}
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
}
?>