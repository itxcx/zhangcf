<?php
// 支付订单模块
class PayOrderAction extends CommonAction 
{
		/**
    +----------------------------------------------------------
	* 在线支付订单列表
    +----------------------------------------------------------
	*/
	public function index(){
	  $list=new TableListAction('Pay_order');
      $list->order("id desc");
      $button=array(
			"审核订单"=>array("class"=>"edit","href"=>__URL__."/pass/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要审核此订单吗？"),
			//"撤销订单"=>array("class"=>"edit","href"=>__URL__."/cancel/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要撤销此订单吗？"),
			//"删除订单"=>array("class"=>"delete","href"=>__URL__."/delete/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要删除此订单吗？"),
		    "支付测试"=>array("class"=>"add","href"=>__APP__."/PayTest/index","target"=>"dialog","mask"=>"true")
        );
        $list->setButton = $button;
        $list->addshow("编号",array("row"=>'[id]'));
        $list->addshow("会员",array("row"=>"[userid]","searchMode"=>"text","searchPosition"=>"top","searchRow"=>'userid'));
        $list->addshow("订单号",array("row"=>"[orderId]","searchMode"=>"text","searchPosition"=>"top","searchRow"=>'orderId'));
        $list->addshow("充值金额",array("row"=>"[money]"));
        $list->addshow("实际金额",array("row"=>"[realmoney]"));
        $list->addshow("账户类型",array("row"=>"[type]"));
        $list->addshow("支付方式",array("row"=>"[payment]"));
        $list->addshow("支付时间",array("row"=>"[create_time]","format"=>"time"));
        $list->addshow("备注",array("row"=>"[memo]"));
        $list->addshow('状态',array('row'=>array(array($this,'zhifu_status'),'[status]'),"searchMode"=>"text","searchPosition"=>"top",'searchRow'=>'status',"searchSelect"=>array("未支付"=>"0","支付成功"=>"1","支付失败"=>"2")));
        $this->assign('list',$list->getHtml());
        $this->display();
	
	}
	function zhifu_status($status){
	   if($status==0){
	     return '未支付';
	   }else if($status==1){
	     return '支付成功';
	   }else if($status==2){
	     return '支付失败';
	   }else{
	   return '无';
	   }
	}
	/**
    +----------------------------------------------------------
	* 审核订单
    +----------------------------------------------------------
	*/
	public function pass() 
    {
		$model				= M('PayOrder');
		$pk					= $model->getPk();
		$succNum = 0;
		foreach(explode(',',I("get.".$pk."/s")) as $id){
			if($id == '')
				continue;
			M()->startTrans();
			$info				= $model->find($id);
			import("Admin.Pay.Pay");
			//获取到它使用的支付接口
			$payment			= $info['payment'];
			$pay				= new Pay($payment,false);
			$pay->touchEvent('success',$info['orderId']);
			$this->saveAdminLog('','',$id."支付订单审核");
			
			$succNum++;
			M()->commit();
		}
		$this->success("审核成功：".$succNum .'条记录；');
	}

	/**
    +----------------------------------------------------------
	* 撤销订单
    +----------------------------------------------------------
	*/
	public function cancel() 
    {
		$model				= M('PayOrder');
		$pk					= $model->getPk();
		$succNum = 0;
		foreach(explode(',',I("get.".$pk."/s")) as $id){
			if($id == '') continue;
			M()->startTrans();
			$info				= $model->find($id);
			import("Admin.Pay.Pay");
			//获取到它使用的支付接口
			$payment			= $info['payment'];
			$pay				= new Pay($payment,false);
			$pay->touchEvent('fail',$info['orderId']);
			$this->saveAdminLog('','',$id."支付订单撤销");
			$succNum++;
			M()->commit();
		}
		$this->success("撤销成功：".$succNum .'条记录；');
	}
}
?>