<?php
import("COM.Interface.SyncInterface");
import("COM.Interface.QuickSearchInterface");
class SyncAdminAction extends Action implements SyncInterface,QuickSearchInterface
{
	/*
	* 返回应用的相关信息
	*
	*/
	public function returnApplicationInfo()
	{
		return array(
			'is_sync_node'		=> '1',			//是否同步节点数据
			'is_sync_menu'		=> '1',			//是否同步菜单数据
			'is_quick_search'	=> '0',			//是否启用快捷搜索
			'sort'				=> '9999',		//排序值越大,在后台居右显示
			'title'				=> '系统设置',	//应用名称
		);
	}
	
	/*
	* 返回快捷搜索的html结果
	*/
	public function returnQuickSearch($name)
	{
		return ''; 
	}
	public function returnMenuList()
	{
		//菜单声明
		$qxset=array();
		$qxset['系统日志']='?s=/Log/index';
		$qxset['区域管理']='?s=/Area/index';
		if(adminshow('emailSwitch')){
			$qxset['邮件设置']='?s=/Mail/index';
		}
		$qxset['数据库维护']	= '?s=/Backup/index';
		if(adminshow('payOnlineSwitch')){
			$qxset['支付管理']=array(
				'childs' => array(
					'安装支付'		=> '?s=/Pay/index',
					'订单管理'		=> '?s=/PayOrder/index',
				),
			);
		}
		$qxset['权限管理']=array(
			'childs'=> array(
				'管理员管理' =>  array(
					'url'	=> '?s=/Admin/index',
				),
				'权限组管理' => '?s=/Role/index',
			),
		);
		if(CONFIG('SHOW_TIMESET')){
			$qxset['系统时间设置'] = '?s=/System/settime';
		}
		$return = array(
			'系统设置'	=> array(
				'childs'	=> $qxset,
			),
		);
		return $return;
	}
	/*
	**引用节点 后台设置权限显示的数组
	*/
	public function returnNodeList()
	{
		return require_once(ROOT_PATH."Admin/Conf/node.php");
	}
	/*
	**返回应用对环境的检查结果
	**
	**
	*/
	public function checkEnviro()
	{
		return array(
			/*要求开启的扩展*/
			'extensions'	=> array(
				0 => 'mysql',
				1 => 'gd',
				2 => 'curl',
				3 => 'iconv',
			),
			/*要求开启的函数,或要求函数有指定的返回值(支持表达式)*/
			'functions'		=> array(
				0 => 'ini_get',
				1 => 'file_get_contents',
				2 => array( 'phpversion', '>=' , '5.2' ,'php版本须5.2以上','floatval'),
				3 => 'mb_convert_encoding',
			),
			/*php.ini中要求的配置项*/
			'ini'			=> array(
				0 => array('safe_mode',array('off',''),'需要关闭'),
			),
		);
	}

	/*
	* 返回应用对目录的检查结果
	*
	*/
	public function checkDir()
	{
		return array(
			//0 => check_dirs_priv( 'Admin/Runtime' ),
		);
	}
	
	/*
	* 返回配置文件项
	*/
	public function returnConfigList()
	{
		return array(
			'DB_PREFIX'                 =>  '',
		);
	}
	/*
	* 返回应用要创建的基础数据
	* sql语句,每条之间用 ; 隔开
	*
	*/
	public function returnSqlStr()
	{
		$time=serialize(strtotime(date("Y-m-d",time())));
		$sql		= file_get_contents("../Admin/xsfm.sql");
		return $sql;
	}

}
?>