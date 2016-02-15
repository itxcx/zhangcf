<?php
defined('APP_NAME') || die('不要非法操作哦!');
class SqlLogAction extends CommonAction {
    public function index(){
	    $log = M()->query("select argument from mysql.general_log");
    	$sum=array();
		foreach($log as $sql)
		{
			$sql=preg_replace('/\d+/','x',$sql['argument']);
			if(!isset($sum[$sql]))
				$sum[$sql]=0;
			$sum[$sql]+=1;
		}   
		echo "总SQL执行数量:".count($sqlline).'条<br>';
		arsort($sum);
		foreach($sum as $key=>$s)
		{
			echo "执行".$s.'次:'.$key.'<br>';
		}
	}
}
?>