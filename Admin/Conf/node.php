<?php

$arr = array(
	'系统日志'	=> array(
		'module'	=> 'Log',
		'sort'		=> 1,
		'childs'	=> array(
			'查看列表'	=> array('action'=>'index','parent'=>'系统日志','setParent'=>"系统日志"),
			'查看详情'	=> array('action'=>'view','parent'=>'系统日志','setParent'=>"系统日志"),
		),
	),
	'区域管理' => array(
		'module'	=> 'Area',
		'sort'		=> 2,
		'childs'	=> array(
			'区域管理'	=> array('action'=>'index','parent'=>'区域管理','setParent'=>"区域管理"),
			'区域管理设置'=> array('action'=>'update,country_add_save,area_add_save,area_delete','parent'=>'区域管理','setParent'=>"区域管理"),
		),
	),
	'数据库维护' =>array(
		'module'	=> 'Backup',
		'sort'		=> 3,
		'childs'	=> array(
			'数据库备份列表'=>  array('action'=>'index,backAjax,getstateajax','parent'=>'数据库维护','setParent'=>"数据库备份列表"),
			'恢复'			=>  array('action'=>'recover,prerecover','parent'=>'数据库维护','setParent'=>"数据库备份列表"),
			'删除'			=>  array('action'=>'deletebak','parent'=>'数据库维护','setParent'=>"数据库备份列表"),
			'备份数据库'	=>  array('action'=>'back,prebackall,backall,backup','parent'=>'数据库维护','setParent'	=>"数据库备份列表"),
			'清空数据库'	=>  array('action'=>'clear,cleandb,cleanfun','parent'=>'数据库维护','setParent'=>"数据库备份列表"),
		),
	),
	'首页信息'=>array(
		'module'	=> 'Index',
		'sort'		=> 12,
		'childs'	=> array(
			'首页信息显示'=> array('action'=>'checkxml','parent'=>'首页信息','setParent'=>"首页信息显示")
		),
	),
	'权限组管理'=>array(
		'module'	=> 'Role',
		'sort'		=> 10,
		'childs'	=> array(
			'权限组列表'		=> array('action'=>'index','parent'=>'权限组管理','setParent'=>"权限组列表"),
			'添加'		=> array('action'=>'add,insert','parent'=>'权限组管理','setParent'=>"权限组列表"),
			'修改'		=> array('action'=>'edit,update','parent'=>'权限组管理','setParent'=>"权限组列表"),
			'删除'		=> array('action'=>'delete','parent'=>'权限组管理','setParent'=>"权限组列表"),
		),
	),
	'管理员管理'=>array(
		'module'	=> 'Admin',
		'sort'		=> 11,
		'childs'	=> array(
			'管理员列表'		=> array('action'=>'index','parent'=>'管理员管理','setParent'=>"管理员列表"),
			'添加'		=> array('action'=>'add,insert','parent'=>'管理员管理','setParent'=>"管理员列表"),
			'修改'		=> array('action'=>'edit,update','parent'=>'管理员管理','setParent'=>"管理员列表"),
			'删除'		=> array('action'=>'delete','parent'=>'管理员管理','setParent'=>"管理员列表"),
			'重置权限列表'		=> array('action'=>'updateNode','parent'=>'管理员管理','setParent'=>"管理员列表"),
			'后台登陆域名绑定'		=> array('action'=>'bind','parent'=>'管理员管理','setParent'=>"管理员列表"),
			'yubicloud'		=> array('action'=>'addyubicloudprefix,addyubicloudprefix_save,delyubicloudprefix,cancelyubiprefix','parent'=>'管理员管理','setParent'=>"管理员列表"),
			'后台登陆域名绑定'		=> array('action'=>'bind','parent'=>'管理员管理','setParent'=>"管理员列表"),
		),
	)
);

if(adminshow('emailSwitch')){
	$arr['邮件设置']=array(
		'module'	=> 'Mail',
		'sort'		=> 3,
		'childs'	=> array(
			'邮件设置'=>array('action'=>'index,mailupdate,testsendmail','parent'=>'邮件设置','setParent'=>"邮件设置"),
		),
	);
}
if(adminshow('languageSwitch')){
	$arr['简繁设置']=array(
		'module'	=> 'Language',
		'sort'		=> 3,
		'childs'	=> array(
			'简繁设置'=>array('action'=>'index,mailupdate','parent'=>'简繁设置','setParent'=>"简繁设置"),
		),
	);
}
if(adminshow('payOnlineSwitch')){
	$arr['支付接口安装'] = array(
		'module'	=> 'Pay',
		'sort'		=> 7,
		'childs'	=> array(
			'列表'	=> array('action'=>'index','parent'=>'支付接口安装','setParent'=>"支付接口安装"),
			'安装'	=> array('action'=>'install','parent'=>'支付接口安装','setParent'=>"支付接口安装"),
			'卸载'	=> array('action'=>'uninstall','parent'=>'支付接口安装','setParent'=>"支付接口安装"),
			'修改'	=> array('action'=>'edit','parent'=>'支付接口安装','setParent'=>"支付接口安装"),
		),
	);
	$arr['支付订单管理'] = array(
		'module'	=> 'PayOrder',
		'sort'		=> 8,
		'childs'	=> array(
			'订单列表'	=> array('action'=>'index','parent'=>'支付订单管理','setParent'=>"订单列表"),
			'审核'	=> array('action'=>'pass','parent'=>'支付订单管理','setParent'=>"订单列表"),
			'撤销'	=> array('action'=>'cancel','parent'=>'支付订单管理','setParent'=>"订单列表"),
			'删除'	=> array('action'=>'delete','parent'=>'支付订单管理','setParent'=>"订单列表"),
		),
	);
	$arr['支付测试'] = array(
		'module'	=> 'PayTest',
		'sort'		=> 9,
		'childs'	=> array(
			'支付页面'	=> array('action'=>'index','parent'=>'支付订单管理','setParent'=>"订单列表"),
			'支付提交'	=> array('action'=>'pay_confirm','parent'=>'支付订单管理','setParent'=>"订单列表"),
		),
	);
}
return $arr;
?>