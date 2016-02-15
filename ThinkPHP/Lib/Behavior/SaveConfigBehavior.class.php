<?php
/**
 +------------------------------------------------------------------------------
 * 生成CONFIG函数配置信息到静态文件
 +------------------------------------------------------------------------------
 */
class SaveConfigBehavior extends Behavior {
    // 行为扩展的执行入口必须是run
    public $notcache=array('area');
    public function run(&$params){
		//判断是否有对config的修改
    	if(CONFIG() || $params===true)
    	{
    		$fp=fopen(THINK_PATH.'config/config.php','w');
    		flock($fp,LOCK_EX);
			$m_con=M('config',null);
			$data=$m_con->getField('name,data');
			if(isset($data))
			foreach($data as $key=>&$gd)
    		{
    			if(!in_array($key,$this->notcache))
    			{
    				$gd = unserialize($gd);
    			}
    		}
			fwrite($fp,strip_whitespace("<?php\nreturn " . var_export($data, true) . ";\n?>"));
    		flock($fp, LOCK_UN);
	    	fclose($fp);
    	}
    }
}
?>
