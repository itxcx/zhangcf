<?php
defined('APP_NAME') || die('不要非法操作哦');
class CommonAction extends Action{
  	//没写.....
	public function _initialize() {
		/*
		* 结算与其他系统进行对接是生成调用接口的日志文件，放在系统根目录下的logs文件夹内，以年月日为文件名
		*/
		/*if($_REQUEST){
			$wj_names=date('Ymd',time());
			$wj_array1=array(date('Y-m-d H:i:s',time()),get_client_ip(),"http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
			$wj_content = array_merge ($wj_array1);
			$wj_char=implode(' ',$wj_content);
			$wj_char='\r\n' . ($wj_char) . ';';
			mkdir("../logs"); 
			$this->wj_fsosavefile("../logs/".$wj_names.".txt",$wj_char);
		}*/
		//秒结跨日检查
		diffTime();
	}
	function wj_fsosavefile($file,$content,$mode='a+')
	{
		$fp = fopen($file,"a+",true);
		fwrite($fp,stripcslashes($content));
		fclose($fp);
		return true;
	}
  	protected function saveAdminLog($oldData,$newData=null,$content,$memo=null)
    {
		$Model  = D('Admin://Log');
        return $Model->saveAdminLog($oldData,$newData,$content,$memo);
    }
}
?>