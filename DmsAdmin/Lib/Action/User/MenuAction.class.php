<?php
//前台菜单模块
class MenuAction extends Action {
	public function getmenudata($user,$allshow=true)
	{
		$menu='';
		if(!$allshow){
			$fieldstr="";
			foreach(X("fun_bank") as $funbank){
				$fieldstr.=',b.'.$funbank->name.',b.'.$funbank->name.'提现累计,b.'.$funbank->name.'锁定';
			}
			$userinfo		= M('会员')->table("dms_会员 a")->join("inner join dms_货币 b on a.id=b.userid")->where(array('a.id'=>$_SESSION[C('USER_AUTH_KEY')]))->field("a.*".$fieldstr)->find();
		}
        //资料管理
		$infoManageName='资料管理';
		$menu[$infoManageName][]=array('model'=>'User','action'=>'view','title'=> '资料查看','level'=>1,"secPwd"=>'false');
		if(!$allshow){
			if(!$allshow || CONFIG('USER_EDIT_SHOW') != ''){
				$menu[$infoManageName][]=array('model'=>'User','action'=>'edit','title'=> '资料修改','level'=>1,"secPwd"=>'false');
			}
		}else{
			 $menu[$infoManageName][]=array('model'=>'User','action'=>'edit','title'=>'资料修改','level'=>1,"secPwd"=>'false');
		}
		$menu[$infoManageName][]=array('model'=>'User','action'=>'setPass','title'=>'密码修改','level'=>1,"secPwd"=>'false');
		
		//业务管理
		$workManageName='业务管理';
		$mydoreg=false;
		$confirm=true;
		foreach(X('sale_*') as $sale)
		{
			if($sale->user == $user->name){
				if($allshow || transform($sale->dispWhere,$userinfo))
				{
					if($sale->use){
						if(get_class($sale)=='sale_reg')
						{
							$baodan = CONFIG('USER_SHOP_SALEONLY');
							if(($allshow) || !$baodan || ($baodan && $userinfo['服务中心'])){
								$mydoreg=true;
								$menu[$workManageName][]=array('model'=>'Sale','action'=>substr(get_class($sale),5).':'.$sale->objPath(),'title'=>$sale->byname,'level'=>1);
								
								//判断是否开启推广链接
								if($sale->user==$sale->parent()->name && adminshow('tj_tuiguang')){
									$menu[$workManageName][]=array('model'=>'User','action'=>'getSpreadCode','title'=>"推广链接",'level'=>1);
								}
							}
						}
						if(get_class($sale)=='sale_buy')
						{
							$menu[$workManageName][]=array('model'=>'Sale','action'=>substr(get_class($sale),5).':'.$sale->objPath(),'title'=>$sale->byname,'level'=>1);
						}
						if(get_class($sale)=='sale_shop')
						{
							$menu[$workManageName][]=array('model'=>'Saleshop','action'=>'buy_shop:'.$sale->objPath(),'title'=>$sale->byname,'level'=>1);
							$menu[$workManageName][]=array('model'=>'Saleshop','action'=>'chongxiao_gouwuche:'.$sale->objPath(),'title'=>$sale->byname.'购物车','level'=>1);
						}
						if(get_class($sale)=='sale_up')
						{
							$menu[$workManageName][]=array('model'=>'Sale','action'=>substr(get_class($sale),5).':'.$sale->objPath(),'title'=>$sale->byname,'level'=>1);
						}
						if((!$sale->confirm && $sale->useracc) || $allshow){
							$confirm=false;
						}
					}
				}
			}
	    }
	    //空点回填 扣币回填成实单  空单申请回填
	    if((adminshow('admin_backfill') || adminshow('admin_blank')) && adminshow('user_bank_backfill')){
	    	$menu[$workManageName][]=array('model'=>'Sale','action'=>'apply_back','title'=>"申请转正",'level'=>1);
	    }
	    //判断是否有推广链接的审核
        if(adminshow('tj_tuiguang')){
        	//添加推广链接订单审核
          	$menu[$workManageName][]=array('model'=>'Sale','action'=>'tj_acclist','title'=>"推广链接审核",'level'=>1);
        }
		if(!$confirm){
			$menu[$workManageName][]=array('model'=>'Sale','action'=>'acclist','title'=>$sale->parent()->byname."订单审核",'level'=>1);
		}
		if($mydoreg == true)
		{
			$menu[$workManageName][]=array('model'=>'User','action'=>'myreg','title'=>'我的'.$user->byname.'订单','level'=>1,"secPwd"=>'false');
		}
		if(($allshow) || $user->haveProduct()){
			   $menu[$workManageName][]=array('model'=>'Sale','action'=>'productmysale','title'=>'我的产品订单','level'=>1,"secPwd"=>'false');
		}
		$menu[$workManageName][]=array('model'=>'Sale','action'=>'mysale','title'=>'我的操作订单','level'=>1,"secPwd"=>'false');
		foreach(X("product_stock",false) as $stock){
			if($allshow || transform($stock->dispWhere,$userinfo)){
				$menu[$workManageName][]=array('model'=>'Sale','action'=>'stock:'.$stock->objPath(),'title'=>$stock->byname.'表','level'=>1);
			}
		}

		//网络管理
		if($allshow || $userinfo['状态']=='有效'){
			$netManageName='网络管理';
			foreach(X('net_place') as $v)
			{
				if(($allshow)
				 || (!$allshow && (($v->userNetDisp && $userinfo[$v->name.'网络显示']=='自动') || $userinfo[$v->name.'网络显示']=='是'))
				 || (!$allshow && (($v->shopNetDisp && transform($user->shopWhere,$userinfo))))
				){
					$menu[$netManageName][]=array('model'=>'Net','action'=>'disp:'.$v->objPath(),'title'=>$v->byname.'网络','level'=>1);
				}
				//网络的列表只有豪华版才能看到 简化版是没有这个功能的 
					if(($allshow) || (!$allshow && (($v->userListDisp && $userinfo[$v->name.'网络显示']=='自动') || $userinfo[$v->name.'网络显示']=='是'))
							 	  || (!$allshow && $v->shopListDisp && transform($user->shopWhere,$userinfo))
					){
						$menu[$netManageName][]=array('model'=>'Net','action'=>'listDisp:'.$v->objPath(),'title'=>$v->byname."列表",'level'=>1);
					}
			}
			foreach(X('net_place2') as $v)
			{
					$menu[$netManageName][]=array('model'=>'Net','action'=>'place2','title'=>$v->byname.'网络','level'=>1);
			}
	        foreach(X('net_rec') as $v)
			{
				if(($allshow)
				 || (!$allshow && (($v->userNetDisp && $userinfo[$v->name.'网络显示']=='自动') || $userinfo[$v->name.'网络显示']=='是'))
				 || (!$allshow && $v->shopNetDisp && transform($user->shopWhere,$userinfo))
				 ){
					$menu[$netManageName][]=array('model'=>'Net','action'=>'disp:'.$v->objPath(),'title'=>$v->byname."网络",'level'=>1);
				}
				//网络的列表只有豪华版才能看到 简化版是没有这个功能的 
				if(($allshow)
					|| (!$allshow && (($v->userListDisp && $userinfo[$v->name.'网络显示']=='自动') || $userinfo[$v->name.'网络显示']=='是'))
					|| (!$allshow && $v->shopListDisp && transform($user->shopWhere,$userinfo))
					){
					$menu[$netManageName][]=array('model'=>'Net','action'=>'listDisp:'.$v->objPath(),'title'=>$v->byname."列表",'level'=>1);
				}
			}
			//幸运网
			foreach(X('fun_ifnum') as $luck){
				$menu[$netManageName][]=array('model'=>'Net','action'=>'lineList:'.$luck->objPath(),'title'=>$luck->byname,'level'=>1);		
			}
			foreach(X('tle') as $tle)
	        foreach(X('prize_split',$tle) as $v)
			{
				if($allshow || $userinfo[$v->name.'_盘号'] > 0)
				{
					$menu[$netManageName][]=array('model'=>'PrizeSplit','action'=>'index:'.$v->objPath(),'title'=>$v->getname(),'xpath'=>$v->objPath(),'level'=>1);
				}
			}	
		}	
        //财务管理
		$moneyManageName='财务管理';
		$bankIn = false;
		$menu[$moneyManageName][]=array('model'=>'Fun_bank','action'=>'rem','title'=>'汇款通知','level'=>1);
		if(CONFIG('giveMoney')==1){
			$menu[$moneyManageName][]=array('model'=>'Transfer','action'=>'index','title'=>'货币转账','level'=>1);
		}		
		if($allshow || $userinfo['状态']=='有效'){
			foreach(X('tle') as $v)
			{
				$menu[$moneyManageName][]=array('model'=>'Tle','action'=>'index:'.$v->objPath(),'title'=>$v->byname.'表','level'=>1);
				//$menu[$moneyManageName][]=array('model'=>'Bouns','action'=>'disp','title'=>$v->name.'构成','xpath'=>$v->objPath(),'level'=>1);
			}
			foreach(X('fun_fuli') as $v)
			{
				$menu[$moneyManageName][]=array('model'=>'Tle','action'=>'fun_fuli:'.$v->objPath(),'title'=>$v->byname.'信息','level'=>1);
			}
			//X('tle')
	        foreach(X('fun_bank') as $v)
			{
				if($v->use && $v->userListDisp)
					$menu[$moneyManageName][]=array('model'=>'Fun_bank','action'=>'index:'.$v->objPath(),'title'=>$v->byname.'明细','level'=>1);
				if($v->getMoney && $v->use)
	                $menu[$moneyManageName][]=array('model'=>'Fun_bank','action'=>'get:'.$v->objPath(),'title'=>$v->byname.'提现','level'=>1);
				if($v->use && $v->bankIn){
					$bankIn=true;
				}
			}
			
			foreach(X('tle') as $tle)
			{	
				foreach(X('prize_pile',$tle) as $v)
				{
					if($v->use)
						$menu[$moneyManageName][]=array('model'=>'Prize_pile','action'=>'index:'.$v->objPath(),'title'=>$v->byname.'明细','level'=>1);
				}
			}
	        /*foreach(X('fun_bank') as $v)
			{
			    if($v->giveMoney && $v->use && $v->userTransferDisp)
	                $menu[$moneyManageName][]=array('model'=>'Fun_bank','action'=>'give:'.$v->objPath(),'title'=>$v->byname.'转账','level'=>1);
			}*/
			

			//货币交易
			if($user->tradeMoney!=''){
				$bankObj=X('fun_bank@'.$user->tradeMoney);
				$menu[$moneyManageName][]=array('model'=>'Fun_ep_deal','action'=>'deal_list:'.$v->objPath(),'title'=>$user->tradeMoney."卖出",'level'=>1);
				$menu[$moneyManageName][]=array('model'=>'Fun_ep_deal','action'=>'index:'.$v->objPath(),'title'=>$user->tradeMoney."买入",'level'=>1);
			}
		}
		if($bankIn){
			$menu[$moneyManageName][]=array('model'=>'Fun_pay','action'=>'index','title'=>"在线支付",'level'=>1);
			$menu[$moneyManageName][]=array('model'=>'Fun_pay','action'=>'paylist','title'=>"支付订单",'level'=>1);
		}
		if(X('fun_gold')){
			$goldManageName='EP交易';
			foreach(X('fun_gold') as $gold)
			{
				$menu[$goldManageName][]=array('model'=>'Fun_gold','action'=>'index:' .$gold->objPath(),'title'=>$gold->name."市场",'level'=>1);
				$menu[$goldManageName][]=array('model'=>'Fun_gold','action'=>'detail:'.$gold->objPath(),'title'=>$gold->name."记录",'level'=>1);
				$menu[$goldManageName][]=array('model'=>'Fun_gold','action'=>'sell:'  .$gold->objPath(),'title'=>$gold->name."挂出",'level'=>1);
			}
		}
		//信息管理
		$messManageName='信息管理';
		$menu[$messManageName][]=array('model'=>'Mail','action'=>'index'     ,'title'=>'邮件列表','level'=>1);
		$menu[$messManageName][]=array('model'=>'Mail','action'=>'send'      ,'title'=>"发送邮件",'level'=>1);
		$menu[$messManageName][]=array('model'=>'Mail','action'=>'sendbox'   ,'title'=>"发件箱",'level'=>1);
		$menu[$messManageName][]=array('model'=>'User','action'=>'viewNotice','title'=>'公告管理','level'=>1,"secPwd"=>'false');
		
		if($allshow || $userinfo['状态']=='有效'){
			//自动拆分股票管理,无买入卖出
	        foreach(X('fun_stock2') as $fun_stock)
			{
				$menu[$fun_stock->byname][]=array('model'=>'Fun_deal','action'=>'index:'      .$fun_stock->objPath(),'title'=>'交易大厅','level'=>1);
			    $menu[$fun_stock->byname][]=array('model'=>'Fun_deal','action'=>'deal_list:'  .$fun_stock->objPath(),'title'=>'交易记录','level'=>1);
			    $menu[$fun_stock->byname][]=array('model'=>'Fun_deal','action'=>'deal_detail:'.$fun_stock->objPath(),'title'=>'交易挂单','level'=>1);
			    //$menu[$stockManageName][]=array('model'=>'Fun_deal','action'=>'stock_change','title'=>'股票互转','level'=>1);
			}
			//自由交易股票管理
			
	        foreach(X('fun_stock') as $fun_stock)
			{
				$menu[$fun_stock->byname."管理"][]=array('model'=>'Fun_stock','action'=>'index:'      .$fun_stock->objPath(),'title'=>'交易大厅','level'=>1);
			    $menu[$fun_stock->byname."管理"][]=array('model'=>'Fun_stock','action'=>'selllist:'   .$fun_stock->objPath(),'title'=>'卖买挂单','level'=>1);
			    $menu[$fun_stock->byname."管理"][]=array('model'=>'Fun_stock','action'=>'deal_list:'  .$fun_stock->objPath(),'title'=>'交易记录','level'=>1);
			    $menu[$fun_stock->byname."管理"][]=array('model'=>'Fun_stock','action'=>'deal_detail:'.$fun_stock->objPath(),'title'=>'账户明细','level'=>1);
			}
		}
		return $menu;
	}
}
?>