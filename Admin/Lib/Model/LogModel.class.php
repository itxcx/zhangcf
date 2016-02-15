<?php
// 日志模型
class LogModel extends Model 
{
	/*
	* 保存后台日志
	*/
	public function saveAdminLog($oldData,$newData=null,$content=null,$memo=null)
	{
		//过滤所有的pass 保存信息为********
		if(isset($_POST)){
			foreach($_POST AS $pkey=>$val){
				if(preg_match('/(pass|pwd|密码)\S*$/',strtolower($pkey))){
					$_POST[$pkey]="********";
				}
			}
		}
		if(isset($_GET)){
			foreach($_GET AS $gkey=>$gval){
				if(preg_match('/(pass|pwd|密码)\S*$/',strtolower($gkey))){
					$_GET[$gkey]="********";
				}
			}
		}
		if(isset($oldData)){
			foreach((array)$oldData AS $okey=>$oval){
				if(preg_match('/(pass|pwd|密码)\S*$/',strtolower($okey))){
					$oldData[$okey]="********";
				}
			}
		}
		if(isset($newData)){
			foreach((array)$newData AS $nkey=>$nval){
				if(preg_match('/(pass|pwd|密码)\S*$/',strtolower($nkey))){
					$newData[$nkey]="********";
				}
			}
		}
		//保存数据
		$data['content']		= $content;
		$data['application']	= APP_NAME;
		$data['group']			= defined('GROUP_NAME') ? GROUP_NAME : '';
		$data['module']			= MODULE_NAME;
		$data['action']			= ACTION_NAME;
		$data['admin_id']		= isset($_SESSION[C ('RBAC_ADMIN_AUTH_KEY')])?$_SESSION[C ('RBAC_ADMIN_AUTH_KEY')]:'0';
		$data['old_data']		= $oldData?json_encode($oldData):'';
		$data['new_data']		= $newData?json_encode($newData):'';
		$data['post_data']		= $_POST?json_encode($_POST):'';
		$data['get_data']		= $_GET?json_encode($_GET):'';
		$data['ip']				= IS_CLI ? '0' : get_client_ip() ;
		$data['create_time']	= time();
		$data['memo']			= $memo?$memo:'';
		if(IS_CLI)
		{
			$country='';
			$area='';
		}
		else
		{
			import("ORG.Net.IpLocation");
			$IpLocation				= new IpLocation("qqwry.dat");
			$loc					= $IpLocation->getlocation();
			$country				= mb_convert_encoding ($loc['country'] , 'UTF-8','GBK' );
			$area					= mb_convert_encoding ($loc['area'] , 'UTF-8','GBK' );
		}
		$data['address']		= $country.$area;
		return M()->table('log')->add($data);
	}
}
?> 