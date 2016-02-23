<?php
/**
+----------------------------------------------------------
* 权限模块
+----------------------------------------------------------
*规范写法
$menu[]=array(
'model'=>'User（模块名）',
'action'=>'index（方法名）',
'title'=>$user->byname."查询（目录标题名）",
'level'=>1（目录的级别一级目录显示二级目录操作）,
'parent'=>$parent（上级目录）,
'setParent'=>$user->byname."列表",
"actions"=>"(关联操作同权限)"
);

关于参数
	方法名后面
	'place2:'.$xpath  最好这样写  出现过一个错误：这样写的'place2:{$xpath}'保存到数据库没有将参数转换出来
	不知道两者在thinkphp中有什么区别
关于actions  有时候比如修改资料，有一个界面还有一个提交保存的方法名
这就需要在actions写入"edit,editsave"或者带着参数"actions"=>"edit:".$xpath.",editsave:".$xpath。
没有时可以不写
+----------------------------------------------------------
*/
defined('APP_NAME') || die('不要非法操作哦!');
class MenuAction extends Action
{
	/**
	生成结算系统所需的后台菜单项
	*/
    public function getMenu()
	{
		//创建菜单显示数组
		$menu=array();
		//先循环出会员管理.专卖店管理中的内容
		$user = X('user');
		//设定一级菜单栏目名
		$parent=$user->byname."管理";
		//会员查询
		$menu[]=array('model'=>'User','action'=>'index','title'=>$user->byname."查询",'level'=>1,'parent'=>$parent,'setParent'=>$user->byname."列表");
		//是否显示未激活，会员多了显示比较直观
		if($user->haveNoregConfirm() && adminshow("user_noacc"))
		{
			$menu[]=array('model'=>'User','action'=>'noConfirm','title'=>"未激活".$user->byname,'level'=>1,'parent'=>$parent,'setParent'=>$user->byname."列表");
			$menu[]=array('model'=>'User','action'=>'deleteAllInvalidUser','title'=>"删除无效",'level'=>2,'parent'=>$parent,'setParent'=>$user->byname."列表");
		}
		//服务中心单独显示
		if(adminshow('user_shop')){
			$menu[]=array('model'=>'Shop','action'=>'index','title'=>"服务中心查询",'level'=>1,'parent'=>$parent,'setParent'=>$user->byname."列表");
		}
		$menu[]=array('model'=>'User','action'=>'loginToUser','title'=>$user->byname."授权登入",'level'=>2,'parent'=>$parent,'setParent'=>$user->byname."列表");
		$menu[]=array('model'=>'User','action'=>'view,userForm','title'=>"查看",'level'=>2,'parent'=>$parent,'setParent'=>$user->byname."列表");
		$menu[]=array('model'=>'User','action'=>'edit,update','title'=>"修改",'level'=>2,'parent'=>$parent,'setParent'=>$user->byname."列表");
		$menu[]=array('model'=>'User','action'=>'pre_delete,delete','title'=>"删除",'level'=>2,'parent'=>$parent,'setParent'=>$user->byname."列表");
		$menu[]=array('model'=>'User','action'=>'suoding,jiesuo','title'=>"锁定解锁",'level'=>2,'parent'=>$parent,'setParent'=>$user->byname."列表");
		$menu[]=array('model'=>'User','action'=>'bulkUpLevel,bulkUpLevelExecute','title'=>"批量升级",'level'=>2,'parent'=>$parent,'setParent'=>$user->byname."列表");
		//区域代理
		if($user->haveAreaLv()){
			$menu[]=array('model'=>'User','action'=>'areaIndex','title'=>"代理查询",'level'=>1,'parent'=>$parent,'setParent'=>$user->byname."列表");
		}
		//会员订单查询
		if($user->haveProduct()){
		    $menu[]=array('model'=>'Sale','action'=>'proIndex' ,'title'=>"产品订单查询",'level'=>1,'parent'=>$parent,'setParent'=>"订单列表");
		}
		$menu[]=array('model'=>'Sale','action'=>'index','title'=>"会员操作记录查询",'level'=>1,'parent'=>$parent,'setParent'=>"订单列表");
		$menu[]=array('model'=>'Sale','action'=>'view,print_index','title'=>"订单查看",'level'=>2,'parent'=>$parent,'setParent'=>"订单列表");
		$menu[]=array('model'=>'Sale','action'=>'pre_delete,delete','title'=>"订单删除",'level'=>2,'parent'=>$parent,'setParent'=>"订单列表");
		//判断是否有快递发货菜单
		if(adminshow('kuaidi') || adminshow('kuaidi_pro')){
		  	$menu[]=array('model'=>'Sale','action'=>'send,sended','title'=>"快递发货",'level'=>2,'parent'=>$parent,'setParent'=>"订单列表");
		  	$menu[]=array('model'=>'Sale','action'=>'sendview,sendsave','title'=>"物流查看修改",'level'=>2,'parent'=>$parent,'setParent'=>"订单列表");
		}else{
			$menu[]=array('model'=>'Sale','action'=>'sended','title'=>"订单发货",'level'=>2,'parent'=>$parent,'setParent'=>"订单列表");
		}
        //判断是否有推广链接的审核 只有豪华版或者指定才可以开启推广链接
        if(adminshow('tj_tuiguang') && adminshow('order_tuiguang')){
          	//添加推广链接订单审核
            $menu[]=array('model'=>'Sale','action'=>'tj_auth','title'=>"推广链接订单审核",'level'=>1,'parent'=>$parent,'setParent'=>"订单审核");
			$menu[]=array('model'=>'Sale','action'=>'tj_accok','title'=>"推广链接订单审核操作",'level'=>2,'parent'=>$parent,'setParent'=>"订单审核");
        }
		if($user->haveConfirm())
		{
			$menu[]=array('model'=>'Sale','action'=>'auth','title'=>"订单审核",'level'=>1,'parent'=>$parent,'setParent'=>"订单审核");
			$menu[]=array('model'=>'Sale','action'=>'accok','title'=>"订单审核操作",'level'=>2,'parent'=>$parent,'setParent'=>"订单审核");
            $menu[]=array('model'=>'Sale','action'=>'pre_accok,accok','title'=>"订单审核操作",'level'=>2,'parent'=>$parent,'setParent'=>"订单审核");
			$menu[]=array('model'=>'Sale','action'=>'pre_delete,delete','title'=>"删除订单操作",'level'=>2,'parent'=>$parent,'setParent'=>"订单审核");
		}
		//对各种sale的操作进行输出
		$logistic=false;		
		foreach(X('sale_*') as $sale)
		{
			//是否开启物流费
			if($sale->logistic) $logistic=true;
			if($sale->user == 'admin' && $sale->use)
			{
                //一级菜单 reg:sale_reg[2]
                $op_1 = substr(get_class($sale),5).':'.$sale->xpath;
                //二级菜单 regSave:sale_reg[2],regAjax:sale_reg[2]
                $op_2 = substr(get_class($sale),5).'Save:'.$sale->xpath.','.substr(get_class($sale),5).'Ajax:'.$sale->xpath;
				//输出订单操作的菜单
				$menu[]=array('model'=>"Sale",'action'=>$op_1,'title'=>$sale->byname,'level'=>1,'parent'=>$parent,'setParent'=>$sale->byname.'&nbsp;');
				$menu[]=array('model'=>"Sale",'action'=>$op_2,'title'=>$sale->byname.'操作','level'=>2,'parent'=>$parent,'setParent'=>$sale->byname.'&nbsp;');
				if(substr(get_class($sale),5) == 'reg')
				{
					$menu[]=array('model'=>"Sale",'action'=>'wuliufei','title'=>'获取物流费和折扣并计算实付款','level'=>2,'parent'=>$parent,'setParent'=>$sale->byname.'&nbsp;');
				}
			}
		}
		//回填申请
		if((adminshow('admin_backfill') || adminshow('admin_blank'))){
			if(adminshow('user_bank_backfill')){
				$menu[]=array('model'=>"Sale",'action'=>'applist','title'=>'转正申请记录','level'=>1,'parent'=>$parent,'setParent'=>$user->byname."列表");
				$menu[]=array('model'=>"Sale",'action'=>'applyview,applyok','title'=>'转正申请审核','level'=>2,'parent'=>$parent,'setParent'=>$user->byname."列表");
				$menu[]=array('model'=>"Sale",'action'=>'applydel','title'=>'撤销转正申请','level'=>2,'parent'=>$parent,'setParent'=>$user->byname."列表");
			}
			if(adminshow('admin_bank_backfill')){
				$menu[]=array('model'=>"Sale",'action'=>'addapply,applysave','title'=>'转正会员','level'=>1,'parent'=>$parent,'setParent'=>$user->byname."列表");
			}
		}
		//注册协议设置
		if($user->agreement){
			$menu[]=array('model'=>'User','action'=>'agreement','title'=>'注册协议','level'=>1,'parent'=>$parent,'setParent'=>'注册协议');
			$menu[]=array('model'=>'User','action'=>'saveAgreement','title'=>'注册协议修改','level'=>2,'parent'=>$parent,'setParent'=>'注册协议');
		}
		//密保
		if(adminshow('mibao')){
			$menu[]=array('model'=>'Secret','action'=>'index','title'=>'密保管理','level'=>1,'parent'=>$parent,'setParent'=>'密保管理');
			$menu[]=array('model'=>'Secret','action'=>'addsecret,savesecret','title'=>"添加密保",'level'=>2,'parent'=>$parent,'setParent'=>"密保管理");
			$menu[]=array('model'=>'Secret','action'=>'editsecret,saveEditsecret','title'=>"修改密保",'level'=>2,'parent'=>$parent,'setParent'=>"密保管理");
			$menu[]=array('model'=>'Secret','action'=>'delsecret','title'=>"删除密保",'level'=>2,'parent'=>$parent,'setParent'=>"密保管理");
		}
		if(adminshow('mustout')){
			//在线会员列表
			$menu[]=array('model'=>'User','action'=>'userOnline','title'=>'在线会员','level'=>1,'parent'=>$parent,'setParent'=>'在线会员');
			//下线操作
			$menu[]=array('model'=>'User','action'=>'onlineBreak','title'=>'下线','level'=>2,'parent'=>$parent,'setParent'=>'在线会员');
		}
		/*输出网络管理相关菜单*/
		foreach(X('net_*') as $net)
		{
			$xpath=$net->xpath;
			if(get_class($net)=='net_rec' || get_class($net)=='net_place')
			{
				$menu[]=array('model'=>'NetTree','action'=>'index:'.$xpath,'title'=>$net->byname."网络",'level'=>1,'parent'=>'网络管理','setParent'=>'网络图','actions'=>'index:'.$xpath.',getChild:'.$xpath);
				//只有豪华版才能享受业绩分析这个功能 如果有客户需求 
               
				if(adminshow('place_analysis')){
					if(get_class($net) == 'net_place'){
						$menu[]=array('model'=>'Net','action'=>"achieve:".$xpath,'title'=>$net->byname.'业绩分析','level'=>1,'parent'=>'网络管理','setParent'=>'业绩分析');
					}
				}
			}
			if(get_class($net)=='net_place2')
			{
				$menu[]=array('model'=>'NetTree','action'=>'place2:'.$xpath ,'title'=>$net->byname."网络",'level'=>1,'parent'=>'网络管理','setParent'=>'网络图');
			}
		}
		foreach(X("fun_treenum") as $funtree){
			$menu[]=array('model'=>'Net','action'=>"funachieve:".$funtree->xpath,'title'=>$funtree->netName.'业绩分析','level'=>1,'parent'=>'网络管理','setParent'=>'业绩分析');
		}
		if(adminshow('user_downnetdel')){
			$menu[]=array('model'=>'Net','action'=>'delNetDown,delNetDowncfm','title'=>"删除会员网络",'level'=>1,'parent'=>'网络管理','setParent'=>'网络图');
		}
		//判断是否有幸运网络
		$user=X('user');
		foreach(X('fun_ifnum') as $luck){
			$xpath=$luck->xpath;
			//幸运网络图
			$menu[]=array('model'=>'NetTree','action'=>'showLineTree:'.$xpath,'title'=>$luck->byname.'网络','level'=>1,'parent'=>'网络管理','setParent'=>'网络图');
		}
		//只有开关开启才能看到网络修改 这个网络修改功能不管是豪华版和简化版都是看不到的 只有特殊要求必须开启系统维护的开关才可以使用
		if(adminshow('edit_wangluo')){
		     $menu[]=array('model'=>'Net','action'=>'editLog,editList,edit,editSave','title'=>'网络修改','level'=>1,'parent'=>'网络管理','setParent'=>'网络修改');
 		}
 		//只有豪华版才能看到网络显示
		$menu[]=array('model'=>'Net','action'=>'netSet,saveNetSet'  ,'title'=>'网络显示设置','level'=>1,'parent'=>'网络管理','setParent'=>'网络显示设置');
		if(adminshow('edit_wangluoprint'))
			$menu[]=array('model'=>'Net','action'=>'netSet_print,savenetSet_print,myPrintPreview'  ,'title'=>'网络打印设置','level'=>1,'parent'=>'网络管理','setParent'=>'网络打印设置');
		//++++++++++产品模块++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		foreach(X('product') as $product)
		{
			$parent='产品管理';
			$xpath=$product->xpath;
			//分类
			$menu[]=array('model'=>'ProductCategory','action'=>"index:".$xpath,	'title'=>$product->byname."分类",'level'=>1,'parent'=>$parent,'setParent'=>$product->byname.'分类管理');
			$menu[]=array('model'=>'ProductCategory','action'=>"add:".$xpath.",addSave:".$xpath,	'title'=>$product->byname."添加",'level'=>2,'parent'=>$parent,'setParent'=>$product->byname.'分类管理');
			$menu[]=array('model'=>'ProductCategory','action'=>"edit:".$xpath.",editSave:".$xpath,	'title'=>$product->byname."修改",'level'=>2,'parent'=>$parent,'setParent'=>$product->byname.'分类管理');
			$menu[]=array('model'=>'ProductCategory','action'=>"delete:".$xpath,'title'=>$product->byname."删除",'level'=>2,'parent'=>$parent,'setParent'=>$product->byname.'分类管理');
			//产品
			$menu[]=array('model'=>'Product','action'=>'index:'.$xpath.',UploadPhoto:{$xpath}',			'title'=>$product->byname."列表",	'level'=>1,	'parent'=>$parent,'setParent'=>$product->byname.'列表管理');
            $menu[]=array('model'=>'Product','action'=>"UploadPhoto:".$xpath.",UploadPhotoSave:".$xpath,	'title'=>$product->byname."产品图片上传",'level'=>2,'parent'=>$parent,'setParent'=>$product->byname.'列表管理');
            $menu[]=array('model'=>'Product','action'=>"setpros:".$xpath.",setprosSave:".$xpath,			'title'=>"套餐添加产品",	'level'=>2,	'parent'=>$parent,'setParent'=>$product->byname.'列表管理');
            $menu[]=array('model'=>'Product','action'=>"setprosdel:".$xpath,		'title'=>"套餐删除产品",	'level'=>2,	'parent'=>$parent,'setParent'=>$product->byname.'列表管理');
			$menu[]=array('model'=>'Product','action'=>"add:".$xpath.",addSave:".$xpath,			'title'=>$product->byname."添加",	'level'=>2,	'parent'=>$parent,'setParent'=>$product->byname.'列表管理');
			$menu[]=array('model'=>'Product','action'=>"edit:".$xpath.",editSave:".$xpath,			'title'=>$product->byname."修改",	'level'=>2,	'parent'=>$parent,'setParent'=>$product->byname.'列表管理');
			$menu[]=array('model'=>'Product','action'=>"delete:".$xpath,		'title'=>$product->byname."删除",	'level'=>2,	'parent'=>$parent,'setParent'=>$product->byname.'列表管理');
            
            //产品套餐设置
			$menu[]=array('model'=>'Producttaoset','action'=>"index:".$xpath,	'title'=>$product->byname."套餐列表",'level'=>1,'parent'=>$parent,'setParent'=>$product->byname.'套餐列表');
			$menu[]=array('model'=>'Producttaoset','action'=>"edit:".$xpath.",editSave:".$xpath,'title'=>"修改",'level'=>2,'parent'=>$parent,'setParent'=>$product->byname.'套餐列表');
			$menu[]=array('model'=>'Producttaoset','action'=>"delete:".$xpath,'title'=>"删除",'level'=>2,'parent'=>$parent,'setParent'=>$product->byname.'套餐列表');
			$menu[]=array('model'=>'Producttaoset','action'=>"UploadPhoto",'title'=>"上传产品图片",'level'=>2,'parent'=>$parent,'setParent'=>$product->byname.'套餐列表');
			$menu[]=array('model'=>'Producttaoset','action'=>"UploadPhotoSave",'title'=>"上传产品图片保存",'level'=>2,'parent'=>$parent,'setParent'=>$product->byname.'套餐列表');
            
			//出入库
			if(adminshow('prostock')){
				$menu[]=array('model'=>'Product','action'=>"addproNum:".$xpath ,'title'=>$product->byname."入库列表",'level'=>1,'parent'=>$parent,'setParent'=>$product->byname."入库列表");
			    $menu[]=array('model'=>'Product','action'=>"add_pronum:".$xpath."addSavepronum:".$xpath,'title'=>$product->byname."入库",'level'=>2,'parent'=>$parent,'setParent'=>$product->byname."入库列表");
				//出库列表
				$menu[]=array('model'=>'Product','action'=>"proOut:".$xpath,'title'=>$product->byname."出库列表",'level'=>1,'parent'=>$parent,'setParent'=>$product->byname."出库列表");
			}
		}
		//快递（物流）公司
		if(adminshow('kuaidi') || adminshow('kuaidi_pro')){
			$menu[]=array('model'=>'ProductLogistics','action'=>"express",'title'=>"快递公司管理",'level'=>1,'parent'=>$parent,'setParent'=>'快递公司管理');
			$menu[]=array('model'=>'ProductLogistics','action'=>"addexpress,saveExpress",'title'=>"快递添加",'level'=>2,'parent'=>$parent,'setParent'=>'快递公司管理');
			$menu[]=array('model'=>'ProductLogistics','action'=>"editexpress,saveEditexpress",'title'=>"快递修改",'level'=>2,'parent'=>$parent,'setParent'=>'快递公司管理');
			$menu[]=array('model'=>'ProductLogistics','action'=>"delexpress",'title'=>"快递删除",'level'=>2,'parent'=>$parent,'setParent'=>'快递公司管理');
		}
		//物流费
		if($logistic){
		    $menu[]=array('model'=>'ProductLogistics','action'=>"index",'title'=>"物流费管理",	'level'=>1,'parent'=>$parent,'setParent'=>'物流费管理');
			$menu[]=array('model'=>'ProductLogistics','action'=>"add,addSave",	'title'=>"添加",		'level'=>2,'parent'=>$parent,'setParent'=>'物流费管理');
			$menu[]=array('model'=>'ProductLogistics','action'=>"edit,editSave",'title'=>"修改",		'level'=>2,'parent'=>$parent,'setParent'=>'物流费管理');
			$menu[]=array('model'=>'ProductLogistics','action'=>"delete",'title'=>"删除",		'level'=>2,'parent'=>$parent,'setParent'=>'物流费管理');
		}
		//++++++++++产品模块++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		/*财务管理*/
		//是否显示提现相关
		$getMoney=false;
		$model="FunBank";
		foreach(X('fun_bank') as $bank)
		{
			$parent="财务管理";
			$xpath=$bank->xpath;
			$menu[]=array('model'=>$model,'action'=>"index:".$xpath     ,'title'=>$bank->byname."明细"    ,'level'=>1,'parent'=>$parent,'setParent'=>$bank->byname.'管理');
			$menu[]=array('model'=>$model,'action'=>"recharge:".$xpath.",rechargeSave:".$xpath.",realnameAjax:".$xpath  ,'title'=>$bank->byname."充值"    ,'level'=>2,'parent'=>$parent,'setParent'=>$bank->byname.'管理');
			$menu[]=array('model'=>$model,'action'=>"rechargepl:".$xpath.",rechargeSavepl:".$xpath,'title'=>$bank->byname."批量充值",'level'=>2,'parent'=>$parent,'setParent'=>$bank->byname.'管理');
			//提现与否判断
			if($bank->getMoney)
			{
				$getMoney=true;
			}
			$menu[]=array('model'=>$model,'action'=>'config:'.$xpath.",addGive:".$xpath.",saveAddGive:".$xpath.",funbankConfigUpdate:".$xpath.",deleteGiveCon:".$xpath,'title'=>$bank->byname."设置",'level'=>2,'parent'=>$parent,'setParent'=>$bank->byname."管理");
		}
		if(CONFIG('sureGiveMoney')){
			$menu[]=array('model'=>'FunBank','action'=>'Zmoney','title'=>"转账列表",'level'=>1,'parent'=>'财务管理','setParent'=>"转账列表");
			$menu[]=array('model'=>$model,'action'=>'givemoneyacc','title'=>"转账审核",'level'=>2,'parent'=>'财务管理','setParent'=>"转账列表");
			$menu[]=array('model'=>$model,'action'=>'givemoneyunacc,givemoneyunpage','title'=>"转账撤销",'level'=>2,'parent'=>'财务管理','setParent'=>"转账列表");
		}
		if($getMoney){
			$menu[]=array('model'=>$model,'action'=>'getmoney,getmoneyapplyList,getmoneyaccList,getmoneyunaccList','title'=>"提现列表",'level'=>1,'parent'=>'财务管理','setParent'=>"提现列表");
			$menu[]=array('model'=>$model,'action'=>'getmoneyacc','title'=>"提现审核",'level'=>2,'parent'=>'财务管理','setParent'=>"提现列表");
			$menu[]=array('model'=>$model,'action'=>'getmoneyunacc,getmoneyunpage','title'=>"提现撤销",'level'=>2,'parent'=>'财务管理','setParent'=>"提现列表");
			$menu[]=array('model'=>$model,'action'=>'getmoneydel','title'=>"删除",'level'=>2,'parent'=>'财务管理','setParent'=>"提现列表");
			$menu[]=array('model'=>$model,'action'=>'getmoneygive','title'=>"发放",'level'=>2,'parent'=>'财务管理','setParent'=>"提现列表");
		}
		if(X('fun_bank')){
			//增加提现的撤销申请
			if($bank->allowBack_apply){
			    $menu[]=array('model'=>$model,'action'=>"allowBack_apply",	'title'=>"提现撤销审核",'level'=>1,'parent'=>'财务管理','setParent'=>$bank->byname.'管理','setParent'=>$bank->byname."提现列表");
				$menu[]=array('model'=>$model,'action'=>"apply_aggree",		'title'=>"同意",		'level'=>2,'parent'=>'财务管理','setParent'=>$bank->byname."提现列表");
				$menu[]=array('model'=>$model,'action'=>"apply_notaggree",	'title'=>"拒绝",		'level'=>2,'parent'=>'财务管理','setParent'=>$bank->byname."提现列表");
			}
			$menu[]=array('model'=>'FunBank','action'=>'adminin','title'=>'公司充值明细','level'=>1,'parent'=>'财务管理');
			//汇款公司账户设置
			$menu[]=array('model'=>$model,'action'=>'banks','title'=>"汇款账户管理",'level'=>1,'parent'=>'财务管理','setParent'=>'汇款账户管理');
			$menu[]=array('model'=>$model,'action'=>'addbanks,savebank','title'=>"添加",'level'=>2,'parent'=>'财务管理','setParent'=>'汇款账户管理');
			$menu[]=array('model'=>$model,'action'=>'editbanks,saveEditBanks','title'=>"修改",'level'=>2,'parent'=>'财务管理','setParent'=>'汇款账户管理');
			$menu[]=array('model'=>$model,'action'=>'delbanks','title'=>"删除",'level'=>2,'parent'=>'财务管理','setParent'=>'汇款账户管理');
			$menu[]=array('model'=>'FunBank','action'=>'rem','title'=>'汇款通知','level'=>1,'parent'=>'财务管理','setParent'=>'汇款通知');
			$menu[]=array('model'=>'FunBank','action'=>'remitSet,remitSetSave','title'=>'汇款设置','level'=>2,'parent'=>'财务管理','setParent'=>'汇款通知');
			//添加汇款方式
			if(adminshow('huikuan')){
			   $menu[]=array('model'=>'FunBank','action'=>'rem_types','title'=>'添加汇款方式','level'=>1,'parent'=>'财务管理','setParent'=>'添加汇款方式');
			   $menu[]=array('model'=>"FunBank",'action'=>"add_huikuantype",'title'=>'添加','level'=>2,'parent'=>"财务管理",'actions'=>"add_huikuantype,saveadd_huikuantype");
			   $menu[]=array('model'=>"FunBank",'action'=>"edit_huikuantype",'title'=>'修改','level'=>2,'parent'=>"财务管理",'actions'=>"edit_huikuantype,saveEditHuikuan");
			   $menu[]=array('model'=>"FunBank",'action'=>"delete_huikuantype",'title'=>'删除','level'=>2,'parent'=>"财务管理");
			}
			$menu[]=array('model'=>'FunBank','action'=>'confirmRem,confirm','title'=>'汇款审核','level'=>2,'parent'=>'财务管理','setParent'=>'汇款通知');
			$menu[]=array('model'=>'FunBank','action'=>'del1','title'=>'汇款删除','level'=>2,'parent'=>'财务管理','setParent'=>'汇款通知');
			$menu[]=array('model'=>'Transfer','action'=>'index','title'=>"转账设置",'level'=>1,'parent'=>$parent,'setParent'=>'财务管理');
			$menu[]=array('model'=>'Transfer','action'=>'add,addsave','title'=>"转账设置添加",'level'=>2,'parent'=>$parent,'setParent'=>'财务管理');
			$menu[]=array('model'=>'Transfer','action'=>'edit,editSave','title'=>"转账设置修改",'level'=>2,'parent'=>$parent,'setParent'=>'财务管理');
			$menu[]=array('model'=>'Transfer','action'=>'del','title'=>"转账设置删除",'level'=>2,'parent'=>$parent,'setParent'=>'财务管理');
			$menu[]=array('model'=>'Transfer','action'=>'givemoneyconfig,gmconfigsave','title'=>"转账高级设置",'level'=>2,'parent'=>$parent,'setParent'=>'财务管理');
		}
		//货币交易
		foreach(X('fun_gold') as $gold)
		{
			$xpath=$gold->xpath;
			$menu[]=array('model'=>'Gold','action'=>'index:'.$xpath,'title'=>$gold->byname."市场",'level'=>1,'parent'=>'货币交易');
			$menu[]=array('model'=>'Gold','action'=>'sellconcel','title'=>$gold->byname."撤销挂单",'level'=>2,'parent'=>'货币交易');
			$menu[]=array('model'=>'Gold','action'=>'tradelist:'.$xpath,'title'=>$gold->byname."记录",'level'=>1,'parent'=>'货币交易');
			$menu[]=array('model'=>'Gold','action'=>'detailview:'.$xpath,'title'=>$gold->byname."记录查看",'level'=>2,'parent'=>'货币交易');
			$menu[]=array('model'=>'Gold','action'=>'arbitrate:'.$xpath,'title'=>$gold->byname."仲裁操作",'level'=>2,'parent'=>'货币交易');
			$menu[]=array('model'=>'Gold','action'=>'tradeconcel:'.$xpath.',arbitratesave:'.$xpath,'title'=>$gold->byname."撤销购买",'level'=>2,'parent'=>'货币交易');
			$menu[]=array('model'=>'Gold','action'=>'config:'.$xpath.',configUpdate:'.$xpath,'title'=>$gold->byname."设置",'level'=>1,'parent'=>'货币交易');
			$menu[]=array('model'=>'Gold','action'=>'recharge:'.$xpath.',rechargeSave:'.$xpath,'title'=>$gold->byname."信誉充值",'level'=>1,'parent'=>'货币交易');
		}
		//原货币交易
		if($user->tradeMoney!=""){
			$menu[]=array('model'=>"Bank_trade",'action'=>'tradeMoney'  ,'title'=>"货币买卖管理",'level'=>1,'parent'=>"财务管理",'setParent'=>'货币买卖');
			$menu[]=array('model'=>"Bank_trade",'action'=>'config','title'=>"货币买卖设置"      ,'level'=>1,'parent'=>"财务管理",'setParent'=>'货币买卖');
		}
		///数据统计信息
		$menu[]=array('model'=>'Tools','action'=>'countInfo','title'=>"信息统计",'level'=>1,'parent'=>'财务管理','setParent'=>"信息统计");
		/*奖金管理*/
		$parent='奖金管理';
		foreach(X('tle') as $tle)
		{
			if($tle->tleMode!='s'){
				$menu[]=array('model'=>'Cal','action'=>'settlement,presettlementExecute,settlementExecute,ExecuteAjax,runset,getcalstateajax','title'=>'结算操作','level'=>1,'parent'=>$parent,'setParent'=>'结算操作管理');
			}
			$xpath=$tle->xpath;
			$menu[]=array('model'=>'Tle','action'=>"index:".$xpath.',prizeForm:'.$xpath,		'title'=>$tle->byname."查询",		'level'=>1,'parent'=>$parent,'setParent'=>$tle->byname.'管理');
			$menu[]=array('model'=>'Tle','action'=>"edit:".$xpath.',editTle:'.$xpath,		'title'=>$tle->byname."修改",		'level'=>2,'parent'=>$parent,'setParent'=>$tle->byname.'管理');
			$menu[]=array('model'=>'Tle','action'=>"ledger:".$xpath,	'title'=>$tle->byname."总账",		'level'=>1,'parent'=>$parent,'setParent'=>$tle->byname.'总账');
			$menu[]=array('model'=>'Cal','action'=>"givePrice:".$xpath,	'title'=>$tle->byname."发放",		'level'=>2,'parent'=>$parent,'setParent'=>$tle->byname.'总账');
			$menu[]=array('model'=>'Cal','action'=>"delPrice:".$xpath,	'title'=>$tle->byname."删除明细",	'level'=>2,'parent'=>$parent,'setParent'=>$tle->byname.'总账');
			$menu[]=array('model'=>'Tle','action'=>"getTotalExcel:".$xpath,	'title'=>"导出奖金汇总表",	'level'=>2,'parent'=>$parent,'setParent'=>$tle->byname.'总账');
			$menu[]=array('model'=>'Tle','action'=>"outday:".$xpath,	'title'=>"导出本日奖金",	'level'=>2,'parent'=>$parent,'setParent'=>$tle->byname.'总账');
			$menu[]=array('model'=>'Tle','action'=>"rollback:".$xpath,	'title'=>"删除结算",	'level'=>2,'parent'=>$parent,'setParent'=>$tle->byname.'总账');
		}
        
		foreach(X('fun_fuli') as $fuli){
			$xpath=$fuli->xpath;
			$menu[]=array('model'=>'Fun_fuli','action'=>"index:".$xpath ,'title'=>$fuli->byname."查询",		'level'=>1,'parent'=>$parent,'setParent'=>$fuli->byname.'管理');
			$menu[]=array('model'=>'Fun_fuli','action'=>"fafang:".$xpath,'title'=>$fuli->byname."发放奖励", 'level'=>2,'parent'=>$parent,'setParent'=>$fuli->byname.'管理');
		}
		$menu[]=array('model'=>'PrizeLock','action'=>'index', 'title'=>'奖金黑名单'  , 'level'=>1, 'parent'=>'奖金管理', 'setParent'=>$parent);
		$menu[]=array('model'=>'PrizeLock','action'=>'editByName,editByNameRun', 'title'=>'根据编号修改'  , 'level'=>1, 'parent'=>'奖金管理', 'setParent'=>$parent);
		$menu[]=array('model'=>'PrizeLock','action'=>'editByNet,editByNetRun', 'title'=>'根据网络关系修改'  , 'level'=>1, 'parent'=>'奖金管理', 'setParent'=>$parent);
		
		$menu[]=array('model'=>'Config','action'=>'tleedit,tleupdate', 'title'=>'奖金参数设置', 'level'=>1, 'parent'=>'奖金管理', 'setParent'=>$parent);
		//只有豪华版才能享受业绩分析这个功能 如果有客户需求 
		if(adminshow('PrizeSwitch')){
			$menu[]=array('model'=>'Config', 'action'=>'prizeEdit,prizeEditSave', 'title'=>'奖金开关设置', 'level'=>1, 'parent'=>'奖金管理', 'setParent'=>$parent);
		}
		//假期设置 周末或者特定日期不结算
		foreach(X('fun_dateset') as $fun)
		{
			$xpath=$fun->xpath;
			$menu[]=array('model'=>"Dateset",'action'=>"index:".$xpath,'title'=>$fun->byname,'level'=>1,'parent'=>"奖金管理",'actions'=>"index:".$xpath.",saveSet:".$xpath);
		}
		
		/*股票管理注册自动挂单，拆分，可使用，待简化*/
		foreach(X("fun_stock2") as $stock)
		{
			$model ="Fun_stock2";
			$parent=$stock->name."管理";
			$xpath =$stock->xpath;
			$menu[]=array('model'=>$model,'action'=>"config:".$xpath    ,'title'=>$stock->name."设置"    ,'level'=>1,'parent'=>$parent);	
			$menu[]=array('model'=>$model,'action'=>"stockHave:".$xpath ,'title'=>$stock->name."持有"    ,'level'=>1,'parent'=>$parent);			
			$menu[]=array('model'=>$model,'action'=>"saleList:".$xpath  ,'title'=>$stock->name."挂单"    ,'level'=>1,'parent'=>$parent);	
			$menu[]=array('model'=>$model,'action'=>"splitList:".$xpath ,'title'=>$stock->name."拆分记录",'level'=>1,'parent'=>$parent);
			$menu[]=array('model'=>$model,'action'=>"record:".$xpath    ,'title'=>$stock->name."明细"    ,'level'=>1,'parent'=>$parent);
		}	
		//公司发行，会员自由出价买卖
		foreach(X("fun_stock") as $stock)
		{
			$model ="Fun_stock";
			$parent=$stock->byname."管理";
			$xpath =$stock->xpath;
			$menu[]=array('model'=>$model,'action'=>"config:".$xpath.",issue:".$xpath.",configSave:".$xpath,'title'=>$stock->byname."设置"    ,'level'=>1,'parent'=>$parent);
			$menu[]=array('model'=>$model,'action'=>"index:".$xpath       ,'title'=>$stock->byname."明细",'level'=>1,'parent'=>$parent);
			$menu[]=array('model'=>$model,'action'=>"addin:".$xpath.",savein:".$xpath       ,'title'=>$stock->byname."充值",'level'=>1,'parent'=>$parent);
			$menu[]=array('model'=>$model,'action'=>"trade:".$xpath       ,'title'=>$stock->byname."成交记录",'level'=>1,'parent'=>$parent);
			$menu[]=array('model'=>$model,'action'=>"shop:".$xpath        ,'title'=>$stock->byname."买卖市场",'level'=>1,'parent'=>$parent);
			$menu[]=array('model'=>$model,'action'=>"cancelall:".$xpath   ,'title'=>$stock->byname."挂单撤销"               ,'level'=>2,'parent'=>$parent);
			if($stock->splitStart)
				$menu[]=array('model'=>$model,'action'=>"stockSplit:".$xpath.",intwp,splitSave:".$xpath  ,'title'=>$stock->byname."拆分"    ,'level'=>1,'parent'=>$parent);			
			$menu[]=array('model'=>$model,'action'=>"stockTrend:".$xpath  ,'title'=>$stock->byname."走势"    ,'level'=>1,'parent'=>$parent);
		}
		/*信息管理*/
		$menu[]=array('model'=>'Mail','action'=>'index','title'=>'站内邮件列表','level'=>1,'parent'=>'信息管理','setParent'=>'站内邮件管理');
		$menu[]=array('model'=>'Mail','action'=>'view','title'=>'站内邮件查看','level'=>2,'parent'=>'信息管理','setParent'=>'站内邮件管理');
		$menu[]=array('model'=>'Mail','action'=>'answer,answerSave','title'=>'站内邮件回复','level'=>2,'parent'=>'信息管理','setParent'=>'站内邮件管理');
		$menu[]=array('model'=>'Mail','action'=>'del','title'=>'删除','level'=>2,'parent'=>'信息管理','setParent'=>'站内邮件管理');
		$menu[]=array('model'=>'Mail','action'=>'send,send_email','title'=>'站内邮件发送','level'=>1,'parent'=>'信息管理','setParent'=>'站内邮件管理');
		$menu[]=array('model'=>'Notice','action'=>'index,view','title'=>'站内公告管理','level'=>1,'parent'=>'信息管理','setParent'=>'站内公告管理');
		$menu[]=array('model'=>'Notice','action'=>'send,send_notice','title'=>'发布公告','level'=>2,'parent'=>'信息管理','setParent'=>'站内公告管理');
		$menu[]=array('model'=>'Notice','action'=>'edit,editSave','title'=>'修改','level'=>2,'parent'=>'信息管理','setParent'=>'站内公告管理');
		$menu[]=array('model'=>'Notice','action'=>'del','title'=>'删除','level'=>2,'parent'=>'信息管理','setParent'=>'站内公告管理');
		$menu[]=array('model'=>'Notice','action'=>'editTop','title'=>'置顶','level'=>2,'parent'=>'信息管理','setParent'=>'站内公告管理');
		if(adminshow('smsSwitch')){
			$menu[]=array('model'=>'Sms','action'=>'send,check,addgroup,do_addgroup,getnum,putinto,addmember,add_all,add_num,add_team,smsSave','title'=>'短信发送','level'=>1,'parent'=>'信息管理','setParent'=>'短信发送');
			$menu[]=array('model'=>'Sms','action'=>'smslist','title'=>'短信管理','level'=>1,'parent'=>'信息管理','setParent'=>'短信管理');
			$menu[]=array('model'=>'Sms','action'=>'smsdatail,smsview','title'=>'查看','level'=>2,'parent'=>'信息管理','setParent'=>'短信管理');
			$menu[]=array('model'=>'Sms','action'=>'dele','title'=>'删除','level'=>2,'parent'=>'信息管理','setParent'=>'短信管理');
		}
		//站外邮件列表显示
		if(adminshow('emailSwitch') && adminshow('mimazhaohui')){
			$menu[]=array('model'=>'Mail','action'=>'zwemail','title'=>'站外邮件列表','level'=>1,'parent'=>'信息管理','setParent'=>'站外邮件管理');
			$menu[]=array('model'=>'Mail','action'=>'zwdel','title'=>'删除','level'=>2,'parent'=>'信息管理','setParent'=>'站外邮件列表');
		}
		//判断使用的模版是否是ion
		if(CONFIG('DEFAULT_THEME')=='ion' || CONFIG('DEFAULT_THEME')=='muban1'){
		  $menu[]=array('model'=>'Notice','action'=>'indeximg','title'=>'首页图片管理','level'=>1,'parent'=>'信息管理','setParent'=>'首页图片管理');
		  $menu[]=array('model'=>'Notice','action'=>'upimg','title'=>'上传图片','level'=>2,'parent'=>'信息管理','setParent'=>'首页图片管理','actions'=>'upimg,upimgsave');
		  $menu[]=array('model'=>'Notice','action'=>'editimg','title'=>'修改','level'=>2,'parent'=>'信息管理','setParent'=>'首页图片管理','actions'=>'editimg,editimgSave');
		  $menu[]=array('model'=>'Notice','action'=>'delimg','title'=>'删除','level'=>2,'parent'=>'信息管理','setParent'=>'首页图片管理');
		}
		/*系统设置*/
		$menu[]=array('model'=>'Config','action'=>'sysedit,sysupdate','title'=>'系统设置','level'=>1,'parent'=>'系统设置','setParent'=>'系统设置');
		$menu[]=array('model'=>'Sec','action'=>'index,sysupdate','title'=>'信息及安全设置','level'=>1,'parent'=>'系统设置','setParent'=>'系统设置');
		$menu[]=array('model'=>'Sec','action'=>'Voice,VoiceTest,VoiceSave,VoiceGetInfo','title'=>'语音通道设置','level'=>2,'parent'=>'系统设置','setParent'=>'信息及安全设置');
		$menu[]=array('model'=>'Sec','action'=>'Mail,MailLoginTest','title'=>'邮件通道设置','level'=>2,'parent'=>'系统设置','setParent'=>'信息及安全设置');
		//开启自动运行控制台
		$children=glob(VENDOR_PATH.'Workerman/Applications/*/start.php');
		$AUTOopen=false;
		foreach($children as $child){
			$filename=basename(dirname($child));
			if(adminshow("AUTO_".$filename)){
				$AUTOopen=true;
				break;
			}
		}
		if($AUTOopen){
			$menu[]=array('model'=>'Config','action'=>'autoList','title'=>'自动执行设置','level'=>1,'parent'=>'系统设置','xpath'=>'','actions'=>'autoList,autoSetsave,autostatus');
		}
		
		$menu[]=array('model'=>'Config','action'=>'LoginTempSetup,tempChange,viewLoginTemp,loginUrl','title'=>'登陆口设置','level'=>1,'parent'=>'系统设置','setParent'=>'系统设置');
		$menu[]=array('model'=>'Config','action'=>'userMenuEdit,userMenuUpdate','title'=>'前台菜单设置','level'=>1,'parent'=>'系统设置','setParent'=>'系统设置');
		$menu[]=array('model'=>'Config','action'=>'system_do_info,doaddfile','title'=>'系统使用说明书','level'=>1,'parent'=>'系统设置','setParent'=>'系统设置');
		if(CONFIG('SHOW_BULKREG')){
			$menu[]=array('model'=>'Tools','action'=>'index1','title'=>'批量注册','level'=>1,'parent'=>'系统设置','setParent'=>'系统设置');
		}
		
		return $menu;
	}
}
?>