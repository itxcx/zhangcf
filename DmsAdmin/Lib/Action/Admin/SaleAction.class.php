<?php
defined('APP_NAME') || die('小样，还想走捷径!');
class SaleAction extends CommonAction {
	//报单订单列表
	public function index(){
		//订单类别
		$select	= array();
		foreach(X('sale_*') as $sale){
			if($sale->productName=='' && $sale->use){
				$select[$sale->byname]=$sale->byname;
			}
		}
        $setButton=array( 
			"查看"=>array("class"=>"edit","href"=>__APP__."/Admin/Sale/view/id/{tl_id}","target"=>"dialog","height"=>"500","width"=>"800","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_magnify.png'),
			"删除"=>array("class"=>"delete","href"=>"__URL__/pre_delete/id/{tl_id}","target"=>"dialog","mask"=>"true"),
        );
        
        if(adminshow('baodan_wuliu'))
        {
        	if(adminshow('kuaidi')){
          		$setButton["发货/查看物流"]=array("class"=>"sended","href"=>'__URL__/send/id/{tl_id}/',"target"=>"dialog","height"=>"500","width"=>"800","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_magnify.png');
	          	//判断是否是豪华版 如果是豪华版的话自动快递查询
          		$setButton["快递查询"]=array("class"=>"edit","href"=>"http://www.kuaidi100.com/frame/app/index2.html","target"=>"_blank");
	        }else{
	          	$setButton["发货"]=array("class"=>"sended","href"=>'__URL__/sended/id/{tl_id}/',"target"=>"ajaxTodo","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_magnify.png');
	        }
        }
        $list=new TableListAction("报单");
        $list->table("dms_报单 as a");
        $list->join('dms_会员 as b on a.编号=b.编号')->field('a.*,b.姓名');
        $list->where(array("a.产品"=>0,"a.报单状态"=>array('neq','未确认')));
 
        $list->setButton = $setButton;       // 定义按钮显示
        $list->showPage=true;                // 是否显示分页 默认显示
        $list->order("购买日期 desc");
        $list->addshow($this->userobj->byname."编号",array("row"=>'<a href="'.__URL__.'/view/id/[id]" target="dialog" height="500" width="800" mask="true" title="查看" rSelect="true">[编号]</a>',"excelMode"=>"text","order"=>"a.编号","searchMode"=>"text",'searchGet'=>'userid',"searchPosition"=>"top",'searchRow'=>'a.编号')); 
        $list->addshow("姓名",array("row"=>"[姓名]","css"=>"width:100px","searchMode"=>"text","excelMode"=>"text")); 
        $list->addshow("订单状态"  ,array("row"=>"[报单状态]","searchPosition"=>"top","searchMode"=>"text","searchSelect"=>array('未确认'=>'未确认','已确认'=>'已确认','已结算'=>'已结算'),"order"=>"报单状态"));
        $list->addshow("付款日期"  ,array("row"=>"[到款日期]","format"=>"time","order"=>"到款日期","searchMode"=>"date",'searchGetStart'=>'daytimestart','searchGetEnd'=>'daytimeend')); 
        //是否有发货
        if(adminshow('baodan_wuliu'))
        {
	        $list->addshow("物流状态"  ,array("row"=>"[物流状态]",'searchGet'=>'sendstate',"searchPosition"=>"top","searchMode"=>"text","searchSelect"=>array('未发货'=>'未发货','已发货'=>'已发货','已收货'=>'已收货'))); 
	        $list->addshow("发货日期"  ,array("row"=>"[发货日期]",'format'=>'time',"order"=>"发货日期","searchMode"=>"date"));
	        $list->addshow("收货日期"  ,array("row"=>"[收货日期]",'format'=>'time',"order"=>"收货日期","searchMode"=>"date"));
		}
		//是否有服务中心
		if($this->userobj->shopWhere!=''){
        	$list->addshow("服务中心"  ,array("row"=>"[服务中心编号]","searchMode"=>"text",'searchRow'=>'a.服务中心编号'));
		}
        $list->addshow("付款人"    ,array("row"=>"[付款人编号]","searchMode"=>"text",));
		$list->addshow("注册人"  ,array("row"=>"[注册人编号]","searchMode"=>"text",'searchRow'=>'[a.注册人编号]')); 
        $list->addshow("订单类别"  ,array("row"=>"[报单类别]","searchMode"=>"text","searchPosition"=>"top",'searchGet'=>'saletype','searchRow'=>'[byname]',"searchSelect"=>$select));
        $list->addshow("报单金额"  ,array("row"=>"[报单金额]","searchMode"=>"num","sum"=>"报单金额","order"=>"报单金额","excelMode"=>"#,###0.00"));
		
        $list->addshow("实付款"  ,array("row"=>"[实付款]","searchMode"=>"num","sum"=>"实付款","order"=>"实付款","excelMode"=>"#,###0.00"));
        //有升级
        //if($this->userobj->haveUp()){
        	//$list->addshow("原级别",array("row"=>array(array(&$this,'_printUserLevel'),'[old_lv]','','[报单类别]'),"searchMode"=>"num","css"=>"width:100px;"));
        	//$list->addshow("新级别" ,array("row"=>array(array(&$this,'_printUserLevel'),'[升级数据]','','[报单类别]',"[id]"),"searchMode"=>"num","css"=>"width:100px;"));
        //}
        $this->assign('list',$list->getHtml());
        $this->display();
	}
	
	//产品订单列表
	public function proIndex(){
		//订单类别
		$select	= array();
		$logistic=false;
		foreach(X('sale_*') as $sale){
			if($sale->productName && $sale->use){
				$select[$sale->byname]=$sale->byname;
				if($sale->logistic) $logistic=true;
			}
		}
        $setButton=array( 
			"查看"=>array("class"=>"edit","href"=>__APP__."/Admin/Sale/view/id/{tl_id}","target"=>"dialog","height"=>"500","width"=>"800","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_magnify.png'),
			"删除"=>array("class"=>"delete","href"=>"__URL__/delete/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true"),
        );
        if(adminshow('baodan_wuliu_pro')){
	        if(adminshow('kuaidi_pro')){
	          	$setButton["发货/查看物流"]=array("class"=>"sended","href"=>'__URL__/send/id/{tl_id}/',"target"=>"dialog","height"=>"500","width"=>"800","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_magnify.png');
	          	//判断是否是豪华版 如果是豪华版的话自动快递查询
          		$setButton["快递查询"]=array("class"=>"edit","href"=>"http://www.kuaidi100.com/frame/app/index2.html","target"=>"_blank");
	        }else{
	          	$setButton["发货"]=array("class"=>"sended","href"=>'__URL__/sended/id/{tl_id}/',"target"=>"ajaxTodo","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_magnify.png');
	        }
        }
       
        $list=new TableListAction("报单");
        $list->table("dms_报单 as a");
        $list->join('dms_会员 as b on a.编号=b.编号')->field('a.*,b.姓名');
        $list->where(array("a.产品"=>1,"a.报单状态"=>array('neq','未确认')));
        $list->setButton = $setButton;       // 定义按钮显示
        $list->order("购买日期 desc");
        $list->addshow("订单ID"    ,array("row"=>"[id]","searchMode"=>"text","searchPosition"=>"top",'searchRow'=>'a.id',"css"=>"width:80px;"));
        $list->addshow($this->userobj->byname."编号",array("row"=>'<a href="'.__URL__.'/view/id/[id]" target="dialog" height="500" width="800" mask="true" title="查看" rSelect="true">[编号]</a>',"excelMode"=>"text","order"=>"a.编号","searchMode"=>"text",'searchGet'=>'userid',"searchPosition"=>"top",'searchRow'=>'a.编号')); 
        $list->addshow("姓名",array("row"=>"[姓名]","css"=>"width:100px","searchMode"=>"text","excelMode"=>"text")); 
        $list->addshow("订单状态"  ,array("row"=>"[报单状态]","searchPosition"=>"top","searchMode"=>"text","searchSelect"=>array('未确认'=>'未确认','已确认'=>'已确认','已结算'=>'已结算'),"order"=>"报单状态"));
        $list->addshow("付款日期"  ,array("row"=>"[到款日期]","searchPosition"=>"top","format"=>"time","order"=>"到款日期","searchMode"=>"date",'searchGetStart'=>'daytimestart','searchGetEnd'=>'daytimeend')); 
        if(adminshow('baodan_wuliu_pro')){
	        $list->addshow("物流状态"  ,array("row"=>"[物流状态]",'searchGet'=>'sendstate',"searchPosition"=>"top","searchMode"=>"text","searchSelect"=>array('未发货'=>'未发货','已发货'=>'已发货','已收货'=>'已收货'),)); 
	        $list->addshow("发货日期"  ,array("row"=>"[发货日期]",'format'=>'time',"order"=>"发货日期","searchMode"=>"date"));
	        $list->addshow("收货日期"  ,array("row"=>"[收货日期]",'format'=>'time',"order"=>"收货日期","searchMode"=>"date"));
		}
		if($this->userobj->shopWhere!=''){
        	$list->addshow("服务中心"  ,array("row"=>"[服务中心编号]","searchMode"=>"text",'searchRow'=>'a.服务中心编号'));
        }
        $list->addshow("付款人"    ,array("row"=>"[付款人编号]","searchMode"=>"text","css"=>"width:90px;"));
		$list->addshow("注册人"  ,array("row"=>"[注册人编号]","searchMode"=>"text","css"=>"width:90px;",'searchRow'=>'a.注册人编号')); 
        $list->addshow("订单类别"  ,array("row"=>"[报单类别]","searchMode"=>"text","searchPosition"=>"top",'searchGet'=>'saletype','searchRow'=>'[byname]',"searchSelect"=>$select));
        $list->addshow("报单金额"  ,array("row"=>"[报单金额]","searchMode"=>"num","sum"=>"报单金额","order"=>"报单金额","excelMode"=>"#,###0.00"));

        $list->addshow("购物金额"  ,array("row"=>"[购物金额]","searchMode"=>"num","sum"=>"[购物金额]",'order'=>'购物金额'));
        if(adminshow('sale_pv')){
        	$list->addshow("购物PV"    ,array("row"=>"[购物PV]"  ,"searchMode"=>"num","sum"=>"[购物PV]",'order'=>'购物PV'));
        }
        $list->addshow("实付款"  ,array("row"=>"[实付款]","searchMode"=>"num","sum"=>"[实付款]",'order'=>'实付款'));
        if($logistic){
	        //添加物流费显示
	        $list->addshow("物流费"  ,array("row"=>"[物流费]","searchMode"=>"num")); 
        }
        //有升级
        if($this->userobj->haveProUp()){
        	$list->addshow("原级别",array("row"=>array(array(&$this,'_printUserLevel'),'[old_lv]','','[报单类别]'),"searchMode"=>"num","css"=>"width:100px;"));
        	$list->addshow("新级别" ,array("row"=>array(array(&$this,'_printUserLevel'),'[升级数据]','','[报单类别]',"[id]"),"searchMode"=>"num","css"=>"width:100px;"));
        }
        $list->addshow("收货人"  ,array("row"=>"[收货人]","searchMode"=>"text","css"=>"width:70px;",'searchRow'=>'[a.收货人]'));
        $list->addshow("联系电话"  ,array("row"=>"[联系电话]","searchMode"=>"text","css"=>"width:80px;",'searchRow'=>'[a.联系电话]'));
        $list->addshow("收货地址"  ,array("row"=>"[收货省份][收货城市][收货地区][收货街道][收货地址]","searchMode"=>"text","css"=>"width:350px;"));
        $list->addshow("产品信息"  ,array("row"=>array(array(&$this,'allPro'),'[id]','[报单类别]'),"searchMode"=>"text","css"=>"width:350px;",'hide'=>true));
     

        $this->assign('list',$list->getHtml());
        $this->display();
	}
		public function allPro($id,$type){

		$productdata = M('产品订单')->where(array('报单id'=>$id))->select();
	
		$str=array();
		foreach($productdata as $v){
			$str[]=$v['名称'].'X'.$v['数量'];
		}
		return implode(',',$str);
	}
	
	//页面打印
	public function print_index(){
		foreach(X('sale_up') as $sale_up)
        {
			$name1	=$sale_up->name;
		}
		$id			= I("request.id/d");
		$where['id']= $id;
		$vo			= M('报单')->where($where)->find();
		$map['编号']= $vo['编号'];
		$ho         = M('会员')->where($map)->find();
		$nodelevels = X('sale_up');
		$nowlevel="";$oldlevel="";
		foreach($nodelevels as $nodelevel)
		{
			foreach($nodelevel->getcon("con",array("name"=>"","lv"=>0)) as $level)
			{
				if($level['lv']==$vo['升级数据'])
				{
					$nowlevel		= $level['name'];
				}
				if($level['lv']==$vo['old_lv'])
				{
					$oldlevel		= $level['name'];
				}
			}
		}
		if($vo['产品']){
			$productdata = M('产品订单')->where(array('报单id'=>$id))->select();
			//dump($productdata);
			$this->assign('productdata',$productdata);
		}
		
		$this->assign('modtime',date('Y-m-d',time()));
		$this->assign('sale_up',$name1);
		$this->assign('nowlevel',$nowlevel);
		$this->assign('oldlevel',$oldlevel);
		$this->assign('vo',$vo);
		$this->assign('ho',$ho);
		$this->display();
	}
	//订单查看
	public function view(){
		if(strpos(I("get.id/s"),',') !== false){
			$this->error('参数错误!');
		}
		$model		= M('报单');
		$id			= I("request.id/d");
		$where['id']= $id;
		$vo			= $model->where($where)->find();
		if($vo['升级数据']>0){
			$nowlevel = $this->_printUserLevel($vo['升级数据'],'',$vo['报单类别']);
			$oldlevel = $this->_printUserLevel($vo['old_lv'],'',$vo['报单类别']);
			$this->assign('nowlevel',$nowlevel);
			$this->assign('oldlevel',$oldlevel);
		}
		if($vo['产品']){
			$productdata = M('产品订单')->where(array('报单id'=>$id))->select();
			$this->assign('productdata',$productdata);
		}
		$this->assign('vo',$vo);
		//是否显示pv
		$this->assign('pvshow',adminshow('sale_pv'));

		$this->display();
	}
   //未审核列表
   public function auth(){
		$nodelevels = X('levels');
		$user=X('user');
		$lvNodeName = '';
		foreach($nodelevels as $levels){
			$lvNodeName .= 'b.'.$levels->name.',';
		}
        $list=new TableListAction("报单");
        $list->table('dms_报单 a');
		$list -> setButton=array(                 // 底部操作按钮显示定义
			//'确认审核'=>array("class"=>"edit","href"=>__URL__.'/accok/id/{tl_id}',"target"=>"ajaxTodo","mask"=>"true","title"=>"是否确认审核！"),
			//"删除"=>array("class"=>"delete","href"=>__URL__."/delete/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true"),
			'确认审核'=>array("class"=>"edit","href"=>__URL__.'/pre_accok/id/{tl_id}',"target"=>"dialog","mask"=>"true"),
			"删除"=>array("class"=>"delete","href"=>__URL__."/pre_delete/id/{tl_id}","target"=>"dialog","mask"=>"true"),
        );
        $where="a.报单状态 = '未确认'";
        //推广链接审核
        if(adminshow('tj_tuiguang') && adminshow('order_tuiguang')) $where.=" and a.是否推广链接=0";
		$list->join("dms_会员 as b on b.编号=a.编号")->where($where);
        $list->field($lvNodeName."b.姓名,a.*");
        $list->order("a.购买日期 desc");
		$list ->setShow = array(
            $user->byname."编号"=>array("row"=>"[编号]","searchMode"=>"text","searchPosition"=>"top","searchRow"=>'b.编号'),
            "姓名"=> array("row"=>"[姓名]"),
            "添加时间"=>array("row"=>"[购买日期]","format"=>"time","searchMode"=>"date"),
		);
		foreach($nodelevels as $levels)
        {
        	$_temp=array();
			foreach($levels->getcon("con",array("name"=>"","lv"=>"")) as $lvconf)
			{
				$_temp[ $lvconf['name'] ] = $lvconf['lv'];
 			}
        	$list->addshow($levels->byname,array("row"=>array(array(&$this,"_printUserLevel"),"[".$levels->name."]",$levels->name),"searchMode"=>"num","searchSelect"=>$_temp,"searchRow"=>"[".$levels->name."]"));
        }
		$list->addshow('报单金额',array("row"=>"[报单金额]","searchMode"=>"num",'order'=>'报单金额'));

		$list->addshow('购物金额',array("row"=>"[购物金额]","searchMode"=>"num"));
		if(adminshow('sale_pv')){
			$list->addshow("购物PV"    ,array("row"=>"[购物PV]"  ,"searchMode"=>"num","sum"=>"[购物PV]"));
		}
		if($this->userobj->haveUp() || $this->userobj->haveProUp()){
		//	$list->addshow('升级数据',array("row"=>array(array(&$this,"_printUserLevel"),"[升级数据]","","[报单类别]","[id]")));
		}
		if($this->userobj->shopWhere != ''){
			$list->addshow('服务中心',array("row"=>"[服务中心编号]","searchMode"=>"text","searchRow"=>'a.服务中心编号'));
		}
		$list->addshow('报单状态',array("row"=>"[报单状态]"));
		$list->addshow('报单类别',array("row"=>"[报单类别]"));
        $this->assign('list',$list->getHtml()); 
        $this->display();
     }
     //推广链接审核订单
  	 public function tj_auth(){
		$levels = X('levels@');
		$user   = X('user');
		$lvNodeName = 'b.'.$levels->name.',';
        $list=new TableListAction("报单");
        $list->table('dms_报单 a');
		$list -> setButton=array(                 // 底部操作按钮显示定义
			'确认审核'=>array("class"=>"edit","href"=>__URL__.'/tj_accok/id/{tl_id}',"target"=>"ajaxTodo","mask"=>"true","title"=>"是否确认审核！"),
			"删除"=>array("class"=>"delete","href"=>__URL__."/pre_delete/id/{tl_id}","target"=>"dialog","mask"=>"true"),
        );
		$list->join("dms_会员 as b on b.编号=a.编号")->where("a.报单状态 = '未确认' and 是否推广链接='1'");
        $list->field($lvNodeName."a.id,b.编号,b.注册日期,b.推荐_上级编号,b.姓名,a.报单状态,a.报单金额,a.服务中心编号,a.购物金额,a.购物PV,a.报单类别");
        $list->order("a.购买日期 desc");
		$list ->setShow = array(
            $user->byname."编号"=>array("row"=>"[编号]","searchMode"=>"text","searchPosition"=>"top","searchRow"=>'b.编号'),
            "姓名"=> array("row"=>"[姓名]"),
            "注册时间"=>array("row"=>"[注册日期]","format"=>"time","searchMode"=>"date"),
		);
		$list->addshow('推荐人编号',array("row"=>"[推荐_上级编号]"));
		
    	$_temp=array();
		foreach($levels->getcon("con",array("name"=>"","lv"=>"")) as $lvconf)
		{
			$_temp[ $lvconf['name'] ] = $lvconf['lv'];
		}
       	$list->addshow($levels->byname,array("row"=>array(array(&$this,"_printUserLevel"),"[".$levels->name."]",$levels->name),"searchMode"=>"num","searchSelect"=>$_temp,"searchRow"=>"[".$levels->name."]"));
        
		$list->addshow('报单金额',array("row"=>"[报单金额]","searchMode"=>"num"));

		$list->addshow('购物金额',array("row"=>"[购物金额]","searchMode"=>"num"));
		if(adminshow('sale_pv')){
			$list->addshow("购物PV"    ,array("row"=>"[购物PV]"  ,"searchMode"=>"num","sum"=>"[购物PV]"));
		}
		$list->addshow('报单状态',array("row"=>"[报单状态]"));
		$list->addshow('报单类别',array("row"=>"[报单类别]"));
        $this->assign('list',$list->getHtml()); 
        $this->display();
     }
     //审核确认前
    public function pre_accok(){
		$sdata = array();
		if(I("get.id/s")!=""){
			$sdata = M("报单")->where(array("id"=>array("in",I("get.id/s"))))->getField("id idkey,编号,购买日期,报单金额,购物金额,报单状态");
			$this->assign('ids',I("get.id/s"));
		}
		$this->assign('sdata',$sdata);
		$this->display();
	}
	//审核确认
    public function accok(){
		set_time_limit(1800);
		ini_set('memory_limit','500M');
		$errMsg = array();
		$succNum = 0;
		$errNum = 0;
		foreach(explode(',',I("post.ids/s")) as $saleid){
			if($saleid == '') continue;
			
			M()->startTrans();
			//用于锁会员表全表
			M('会员')->lock(true)->where('id<0')->find();
			$sdata = M("报单")->lock(true)->where(array('id'=>$saleid))->find();
			if($sdata['报单状态']!='未确认') continue;
			$salename=$sdata['报单类别'];
			$userid=$sdata['编号'];
			$sale=X('sale_*@'.$salename);
			if($userid=='' || $sale===false){
				$errNum++;
				//$errMsg .= $userid.'：参数错误！<br/>';
				$errMsg[$saleid]= array('msg'=>'参数错误');
				continue;
			}
			//审核 扣款
			$return = $sale->accok($sdata,true);
			if($return !== true){
				$errNum++;
				//$errMsg .= $userid.'：'.$return.'<br/>';
				$errMsg[$saleid]= array('msg'=>$return);
				M()->rollback();
				continue;
			}
			$errMsg[$saleid]= array('msg'=>'已确认');
			M()->commit();
			M()->startTrans();
			//审核短信发送
			sendSms("accok",$sdata['编号'],$sale->byname.'审核',$sdata);
			$this->saveAdminLog("","",'订单审核',"审核会员[".$sdata['编号']."]".date("Y-m-d",$sdata['购买日期']).$sdata['报单类别'].'订单');
			M()->commit();
			$succNum++;
		}
		echo json_encode($errMsg);
		/*if($errNum !=0){
			$this->error("审核成功：".$succNum .'条记录；审核失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("审核成功：".$succNum .'条记录；');
		}*/
	}
	
	public function tj_accok(){
		set_time_limit(1800);
		ini_set('memory_limit','500M');
		$errMsg = '';
		$succNum = 0;
		$errNum = 0;
		foreach(explode(',',I("get.id/s")) as $saleid){
			if($saleid == '') continue;
			
			M()->startTrans();
			M('会员')->where('id<0')->lock(true)->find();
			$sdata = M("报单")->lock(true)->where(array('id'=>$saleid))->find();
			//$new_user = M('会员')->where(array('编号'=>$sdata['编号']))->find();
			//$upuser = M('会员')->where(array('编号'=>$new_user['推荐_上级编号']))->find();
			$salename=$sdata['报单类别'];
			$userid=$sdata['编号'];
			$sale=X('sale_*@'.$salename);
			if($userid=='' || $sale===false){
				$errNum++;
				$errMsg .= $userid.'：参数错误！<br/>';
				continue;
			}
			//审核 扣款
			$return = $sale->accok($sdata,true);
			if($return !== true){
				$errNum++;
				$errMsg .= $userid.'：'.$return.'<br/>';
				M()->rollback();
				continue;
			}
			M()->commit();
			$succNum++;
			$this->saveAdminLog("","",'订单审核',"审核会员[".$sdata['编号']."]".date("Y-m-d",$sdata['购买日期']).$sdata['报单类别'].'订单');
		}
		if($errNum !=0){
			$this->error("审核成功：".$succNum .'条记录；审核失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("审核成功：".$succNum .'条记录；');
		}
	}

	//会员注册页面
	public function reg(sale_reg $sale_reg){
		$require=explode(',',CONFIG('USER_REG_REQUIRED'));
		$show=explode(',',CONFIG('USER_REG_SHOW'));
		
		//密保问题
		$this->assign('reg_safe',adminshow('mibao'));
		
		//注册是否选产品--product.html
		$zkbool=false;$logistic=false;
		if($sale_reg->productName){
			$proobj=X("product@".$sale_reg->productName);
			$productArr = $proobj->getProductArray($sale_reg);
			$this->assign('productArr',$productArr);
			$this->assign('proobj',$proobj);
			//是否有折扣
			$zkbool=$this->userobj->haveZhekou($sale_reg);
			//是否有物流费
			if($sale_reg->logistic) $logistic=true;
		}
		$this->assign('zkbool',$zkbool);
		$this->assign('logistic',$logistic);
   		//判断是否需要生成编号
		if($this->userobj->idAutoEdit){
			//创建新编号
			M()->startTrans();
			$newid=$this->userobj->getnewid();
			M()->commit();
			//如果不能编辑,则放到SESSION中
			if(!$this->userobj->idEdit){
				//赋值SESSION
				session('userid_reg',$newid);
			}
			$this->assign('userid',$newid);
		}
		
		$this->assign('sale',$sale_reg);
		$this->assign('alert',$sale_reg->alert);
		$this->assign('user',$this->userobj);
		
		//取得网体信息
		$nets=array();
		foreach(X('net_rec,net_place') as $net)
		{
			if(!$net->useBySale($sale_reg))
			continue;
			//需要调用的其他连带表单
			$otherpost='';
			if(isset($net->fromNet) && $net->fromNet!='')
			{
				$otherpost.=',net_'.$net->getPos();
				$otherpost.=',net_'.X('net_rec@'.$net->fromNet)->getPos();
			}
			$value				= "";
			//$position			= $net->getRegion();
			if(isset($net->setRegion) && $net->setRegion==true)
			{
				if(I("get.pid/s")!='')
				{
					$value		= I("get.pid/s");
				}
				$otherpost='net_'.$net->getPos()."_Region";
			}
			$nets[]=array("type"=>'text',"name"=>$net->name."人编号","inputname"=>"net_".$net->getPos(),"otherpost"=>$otherpost,"value"=>$value,'require'=>$net->mustUp);
			if(isset($net->setRegion) && $net->setRegion==true)
			{
				$RegionSet=array();
				foreach($net->getRegion() as $Region)
				{
					//是否可以显示这个region
					$regiondisp = true;
					//默认有where则关闭掉
					if(isset($Region['where']) && $Region['where']!='')
					{
						$regiondisp=false;
						//如果存在通过网络图点击得到的特定会员编号
						if($value)
						{
							//找到这个会员
							$upuser = M('会员')->where(array('编号'=>$value))->find();
							//对显示区域的where做判断
							$where = $Region['where'];
							$where=str_replace('{myrec}','false',$where);
							if($upuser && transform($where,$upuser))
							{
								//判断成功.这个区也可以显示
								$regiondisp=true;
							}
						}
					}
					if($regiondisp)
					{
						$RegionSet[]=$Region;
					}
				}
				$nets[]=array("type"=>'select',"Region"=>$RegionSet,"name"=>$net->byname."人位置","inputname"=>"net_".$net->getPos()."_Region","otherpost"=>'net_'.$net->getPos(),'require'=>$net->mustUp);
			}
			
		}
		$this->assign('nets',$nets);
		//取得级别信息
		$levels=X('levels@'.$sale_reg->lvName);
		$this->assign('levels',$levels);
		$levelsopt=array();
		foreach($levels->getcon("con",array("name"=>"","lv"=>0,'use'=>'')) as $opt)
		{
			if($opt['use']!='false'){
				$levelsopt[]=$opt;
			}
		}
		//xml中的fun_select配置  如:配置是否显示服务中心 套餐等
		$fun_selectarr=array();
		foreach(X('fun_select') as $fun_select)
		{
			if($fun_select->regDisp)
			{
				$select_cons=$fun_select->getcon('con',array('name'=>'','val'=>0));
				$select_pos=$fun_select->getPos();
				$fun_selectarr['select_'.$select_pos]['name']=$fun_select->name;
				$fun_selectarr['select_'.$select_pos]['default']=$fun_select->default;
				foreach($select_cons as $select_con)
				{
					$fun_selectarr['select_'.$select_pos]['con'][]=array('name'=>$select_con['name'],'val'=>$select_con['val']);
				}
			}
		}
		//xml中的附加配置注册显示字段  是否统一添加安智网
		$fun_arr=array();
		$funReg=array();
		foreach(X('fun_val') as $fun_val){
			if($fun_val->regDisp && $fun_val->resetrequest!='')
			{
				$fun_arr[$fun_val->name]='fun_'.$fun_val->getPos();
			}
			if($fun_val->regDisp){
				$funReg[]=$fun_val->name;
				if($fun_val->required){
					$require[] = $fun_val->name;
				}
			}
		}
		$Bank	= M('银行卡');
		$banklist	= $Bank->order('id asc')->select();
		$this->assign('banklist',$banklist);
		$this->assign('fun_val',$fun_arr);
		$this->assign('funReg',$funReg);
		$this->assign('fun_select',$fun_selectarr);
		$this->assign('jsrequire',json_encode($require));
		$this->assign('require',$require);
		$this->assign('show',$show);
		$this->assign('pwd3Switch',adminshow('pwd3Switch'));
		$this->assign('levelsopt',$levelsopt);
		$this->assign('haveuser',$this->userobj->have(''));
		//空点回填模式
		$regtype=array(0=>"实点");
		//有空点
		if(adminshow('admin_blank')){
			$regtype[1]="空点";
		}
		//有回填
		if(adminshow('admin_backfill')){
			$regtype[2]="空点回填";
		}
		$this->assign('regtype',$regtype);
		$this->display();
	}
	
	public function regSave(sale_reg $sale_reg){
		set_time_limit(1800);
		ini_set('memory_limit','500M');
		//获得当前注册单节点
		$m_user = M('会员');
		$m_user->startTrans();
		$m_user->where('id<0')->lock(true)->count();
		//空点或回填不用审核，如审核，需底层重构代码（报单中的各种值更改）
		if(I("post.nullMode/d")>0) 
			$sale_reg->confirm=true;
		
		$checkResult = $sale_reg->getValidate(I("post."));//自动验证
		//如果验证失败
		if($checkResult['error']){
			//输出错误内容
			$errorStr = '';
			foreach($checkResult['error'] as $error){
				$errorStr .= $error.'<br>';
			}
			$this->error($errorStr);
		}else{
			//执行注册操作
			$return = $sale_reg->regSave(I("post."));
			if(gettype($return)=='string')
			{
				$this->error($return);
			}
			$m_user->commit();
			$this->saveAdminLog('','',I("post.userid/s")."注册成功");
			$this->success('注册成功！');
		}
	}
	public function regAjax(sale_reg $sale_reg)
	{
		//如果编号为自动生成,并且不能编辑,则取得reg方法时生成的会员新编号
		$result = $sale_reg->getValidate(I("post."));		//自动验证
		$errs=funajax($result['error'],$this->userobj);
		$this->assign('errs',$errs);
		foreach($result['data'] as $key=>$data){
			$this->assign($key,$data);
		}
		$this->display();
	}

	//会员升级
	public function up(sale_up $sale_up)
	{
		//是否选产品
		$zkbool=false;$logistic=false;
		if($sale_up->productName){
			$proobj=X("product@".$sale_up->productName);
			$productArr = $proobj->getProductArray($sale_up);	
			$this->assign('productArr',$productArr);
			$this->assign('proobj',$proobj);
			//是否有折扣
			$zkbool=$this->userobj->haveZhekou($sale_up);
			//是否有物流费
			if($sale_up->logistic) $logistic=true;
		}
		$this->assign('zkbool',$zkbool);
		$this->assign('logistic',$logistic);
		
		//是否选择回填
		$upBackFill = false;//回填开启并且升级选择回填开启
		if(adminshow('admin_backfill') && adminshow('admin_up_backfill')){
			$name1=X("sale_reg@")->lvName;//第一种级别
			if($sale_up->lvName==$name1)$upBackFill=true;
		}
		$levels=X('levels@'.$sale_up->lvName);$area=array();
		$this->assign('levels'   ,$levels);
		$this->assign('levelsopt',$sale_up->getLvOption());
		$this->assign('area'     ,$sale_up->getLvArea());
		$this->assign('sale',$sale_up);
		$this->assign('user',$this->userobj);
		$this->assign('haveBackFill',$upBackFill);
		$this->display();
	}
	public function upSave(sale_up $sale_up)
	{
		set_time_limit(1800);
		ini_set('memory_limit','500M');
		$userid	= trim(I("post.userid/s"));       //处理表单提交时两端的空白字符
		if($userid == ''){
			$this->error("请填写会员编号");
		}
		M()->startTrans();
		$userdata = $this->userobj->getuser(strval($userid));
		if(!$userdata){
			$this->error("未获取到会员信息");
		}
		$oldlv	= $userdata[$sale_up->lvName];
		$newlv	= I("post.lv/d");
		$level=X("levels@".$sale_up->lvName);
		if($oldlv==I("post.lv/d"))
		{
			if(!$level->area) {
				$this->error('您选择的新级别和当前级别一致，无法操作');
			}else{//代理
				foreach($level->getcon("con",array("area"=>"","lv"=>0)) as $lvconf){
					if($lvconf['lv']==I("post.lv/d") && $lvconf['area']==''){
						$this->error('您选择的新级别和当前级别一致，无法操作');
					}
				}
			}
		}
		//回填的不应该扣币或产生业绩
		/*if(isset($_POST['backFill']) && $_POST['backFill']==1 && ((isset($_POST['point']) && $_POST['point']==0) || (isset($_POST['deduct_acc']) && $_POST['deduct_acc']==0))){
			$this->error("回填请不要选择'产生业绩'或'扣除货币'");
		}*/
		//回填不用审核，如审核，需底层重构代码（报单中的各种值更改），同上面的regsave
		if(I("post.backFill/d")==1)
			$sale_up->confirm=true;
		
		$checkResult = $sale_up->getValidate(I("post."));	//自动验证
		if($checkResult['error']){
			$errorStr = '';
			foreach($checkResult['error'] as $error){
				$errorStr .= $error . '<br/>';
			}
			$this->error($errorStr);
		}
		$return = $sale_up->upSave(I("post."));
		if(gettype($return)=='string')
		{
			$this->error($return);
		}
		M()->commit();
		$oldlevel	= $this->_printUserLevel($oldlv,$sale_up->lvName);
		$newlevel	= $this->_printUserLevel($newlv,$sale_up->lvName);
		$this->saveAdminLog(array($sale_up->lvName=>$oldlevel),array($sale_up->lvName=>$newlevel),X('user')->byname.'升级',$userid.'升级成功');
		$this->success('操作完成！');
	}
	public function upAjax(sale_up $sale_up)
	{
		$userid			= trim(I("get.userid/s"));   //表单输入完时处理字符串两端的空格
		$levels			= X('levels@'.$sale_up->lvName);
		$levelsopt		= $levels->getcon("con",array("name"=>"","lv"=>0));
		$m				= M('会员');
		$list		    = $m->where(array("编号"=>$userid))->find();
		$levelsopts		= array();
		if($list)
		{
			foreach($levelsopt as $key=>$level)
			{
				if($level['lv']==$list[$levels->name])
				{
					$levelsopts= array("name"=>$level['name'],"lv"=>$level['lv'],"姓名"=>$list['姓名']);
				}					
			}
			$this->ajaxReturn($levelsopts,'成功',1);
		}
		else
		{
			$this->ajaxReturn('','失败',0);
		}
	}
	public function showinfo(sale_up $sale_up)
	{
		$userid			= trim(I("get.userid/s"));
		if($userid=='')
		{
			$this->ajaxReturn('编号不能为空','失败',0);
		}
		$lv			    = M('会员')->where(array("编号"=>"$userid"))->getField($sale_up->lvName);
		if($lv)
		{
			$this->ajaxReturn($lv,'成功',1);
		}
		else
		{
			$this->ajaxReturn('','失败',0);
		}

	}
	//重复投资
	public function buy(sale_buy $sale_buy){
		//是否选产品
		$zkbool=false;$logistic=false;
		if($sale_buy->productName){
			$proobj=X("product@".$sale_buy->productName);
			$productArr = $proobj->getProductArray($sale_buy);
			$this->assign('productArr',$productArr);
			$this->assign('productName',$sale_buy->productName);
			$this->assign('proobj',$proobj);
			//是否有折扣
			$zkbool=$this->userobj->haveZhekou($sale_buy);
			//是否有物流费
			if($sale_buy->logistic) $logistic=true;
		}
		$this->assign('zkbool',$zkbool);
		$this->assign('logistic',$logistic);
		
		$this->assign('sale',$sale_buy);
		$this->assign('name',$sale_buy->byname);
		$this->assign('user',$this->userobj);
		$this->display();
	}
	public function buyAjax(sale_buy $sale_buy)
	{
		$userid			= I("post.userid/s");
		$userinfo	    = M('会员')->where(array("编号"=>$userid))->getField("id");
		if(!$userinfo)
		{
			$this->ajaxReturn('',$this->userobj->byname.'编号不存在！',0);
		}
	}
	public function buySave(sale_buy $sale_buy)
	{
		set_time_limit(1800);
		ini_set('memory_limit','500M');
		$this->buyAjax($sale_buy);
		M()->startTrans();
		$checkResult = $sale_buy->getValidate(I("post."));   //自动验证
		if($checkResult['error']){
			$errorStr = '';
			foreach($checkResult['error'] as $error){
				$errorStr .= $error . '<br/>';
			}
			$this->error($errorStr);
		}
		$where['编号']=I("request.userid/s");
		$userdata=M('会员')->where($where)->find();
		$rswhere=$sale_buy->iswhere($userdata);
		if($rswhere !== true){
			$this->error($rswhere);
		}
		$return = $sale_buy->buy(I("post."));
		if(gettype($return)=='string')
		{
			$this->error($return);
		}
		M()->commit();
		$this->saveAdminLog(I("post."),I("post."),$sale_buy->name.'提交',$sale_buy->name.'提交成功');
		$this->success($sale_buy->byname."成功");
	}
	//转正申请记录
	public function applist(){
		$setButton=array(
			"审核"=>array("class"=>"edit","href"=>"__URL__/applyview/id/{tl_id}","target"=>"dialog","height"=>"800","width"=>"800","mask"=>"true"),
			"撤销"=>array("class"=>"delete","href"=>"__URL__/applydel/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"是否确认撤销申请！"),
			"转正会员"=>array("class"=>"edit","href"=>"__URL__/addapply","target"=>"navtab","mask"=>"true"),
        );
        $list = new TableListAction("报单");
		$list->table("dms_报单 a");
		$list->join("inner join (select * from dms_申请回填 where 申请状态='未审核') b on a.id=b.saleid");
		$list->field('a.*,b.*');
        $list->showPage=true;                // 是否显示分页 默认显示
        $list->setButton=$setButton;
        $list->addshow("编号",array("row"=>"[编号]","css"=>"width:100px","searchRow"=>'a.编号',"searchMode"=>"text","searchPosition"=>"top"));
        $list->addshow("报单金额"  ,array("row"=>"[报单金额]","searchMode"=>"num","order"=>"a.报单金额","excelMode"=>"#,###0.00"));
        foreach(X("sale_reg") as $sale_reg){
        if($sale_reg->user=="admin" && $sale_reg->productName!=""){
	        $list->addshow("购物金额",array("row"=>"[购物金额]","searchMode"=>"num",'order'=>'a.购物金额',"excelMode"=>"#,###0.00"));
	        if(adminshow('sale_pv')){
	        	$list->addshow("购物PV",array("row"=>"[购物PV]","searchMode"=>"num",'order'=>'a.购物PV',"excelMode"=>"#,###0.00"));
	        }
        }
        }
        $list->addshow("回填金额",array("row"=>"[回填金额]","css"=>"width:70px","searchRow"=>"a.回填金额","searchMode"=>"num","order"=>'a.回填金额'));
        $list->addshow("申请日期",array("row"=>"[申请日期]","format"=>"time","order"=>"申请日期","searchMode"=>"date",'searchGetStart'=>'daytimestart','searchGetEnd'=>'daytimeend',"searchRow"=>"b.申请日期"));
        $list->addshow("转正方式",array("row"=>"[转正方式]","searchMode"=>"text",'searchRow'=>'b.转正方式',"searchSelect"=>array("回填转正"=>"回填转正","立即转正"=>"立即转正"),"searchPosition"=>"top"));
        $list->addshow("审核日期",array("row"=>"[审核日期]","format"=>"time","order"=>"审核日期","searchMode"=>"date",'searchGetStart'=>'daytimestart','searchGetEnd'=>'daytimeend',"searchRow"=>"b.审核日期"));
        $this->assign('list',$list->getHtml());
        $this->display();
	}
	//转正会员
	public function addapply(){
		$username="";
		if(I("get.uid/s")!=""){
			$username=I("get.uid/s");
			$map['报单状态']=array("in","空单,回填");
	    	$map['编号']=$username;
	    	$saleData=M("报单")->where($map)->find();
	    	if(!$saleData){
	    		$this->error("会员".$username."没有要回填的订单");
	    	}
			$this->assign('saleData',$saleData);
			$this->assign('adminshow',adminshow('sale_pv'));
			if($saleData['产品'] == 1){
				$productData = M('产品订单')->where(array('报单id'=>I("get.id/d")))->select();
				$this->assign('productData',$productData);
			}
			//奖金回填方案
			$this->assign("backfill",X("prize_backfill"));
		}
		foreach(X("fun_bank") as $fun_bank){
			$banks[$fun_bank->name]=$fun_bank->byname;
		}
		$this->assign("banks",$banks);
		$this->assign("username",$username);
		//奖金回填方案
		$this->assign("backfill",X("prize_backfill"));
		$this->display();
	}
	public function applysave(){
		if(trim(I("post.uid/s"))==""){
			$this->error("参数错误");
		}
		M()->startTrans();
		//查询当前会员的空单
		$map['报单状态']=array("in","空单,回填");
	    $map['编号']=trim(I("post.uid/s"));
		$saleData=M("报单")->where($map)->lock(true)->find();
		//订单状态的判断
		if($saleData['报单状态']!="空单"){
			if($saleData['报单状态']=="回填" && I("post.type/s")=="回填转正"){
				$this->error(L("报单已成为回填单"));
			}
			if($saleData['报单状态']!="回填"){
				$this->error(L("报单已回填完成"));
			}
		}
		//申请记录的状态判断
		$applydata=M("申请回填")->where(array("_complex"=>array("转正方式"=>I("post.type/s"),"申请状态"=>"未审核","_logic"=>"or"),"编号"=>$saleData['编号'],"saleid"=>$saleData['id']))->find();
		if(isset($applydata)){
			$this->error(L("已有申请提交等待审核或者已申请过".I("post.type/s")));
		}
		//保存申请记录
		$data=array(
			"saleid"=>$saleData['id'],
			"编号"=>$saleData['编号'],
			"转正方式"=>I("post.type/s"),
			"申请日期"=>systemTime(),
			"申请状态"=>"未审核"
		);
		$pid=M("申请回填")->add($data);
		$sale=X("@".$saleData['报单类别']);
		$saleData['pid']=$pid;$saleData['申请日期']=$data["申请日期"];$saleData['转正方式']=I("post.type/s");$saleData['申请状态']="未审核";
		$return=$sale->applyok($saleData,I("post.accbank/s"));
		if($return !== true){
			$this->error($return);
		}
		M()->commit();
		$this->saveAdminLog("","",'添加转正',"添加转正会员[".$saleData['编号']."]".date("Y-m-d",$saleData['申请日期']).$saleData['转正方式']);
		$this->success("操作完成");
	}
	//撤销转正申请
	public function applydel(){
		if(I("request.id/s")==""){
			$this->error("参数错误");
		}
		$errMsg = '';
		$succNum = 0;
		$errNum = 0;
		foreach(explode(',',I("request.id/s")) as $id){
			if(!$id) continue;
			M()->startTrans();
			$apply=M('申请回填')->table("dms_申请回填 as a")->join('dms_报单 as b on b.编号=a.编号 and b.id=a.saleid')->where(array("a.id"=>$id))->lock(true)->field("a.id as pid,a.saleid,a.编号,a.申请日期,a.申请状态,a.转正方式,b.*")->find();
			if(!$apply){
				$errNum++;
				$errMsg .= '转正申请：'.$id.'不存在<br/>';
				M()->rollback();
				continue;
			}
			$result=M('申请回填')->delete($id);
			if(!$result){
				$errNum++;
				$errMsg .= $apply['编号'].'转正申请：'.'撤销失败<br/>';
				M()->rollback();
				continue;
			}
			M()->commit();
			$this->saveAdminLog("","",'转正撤销',"转正会员[".$apply['编号']."]".date("Y-m-d",$apply['申请日期']).$apply['转正方式'].'申请');
			$succNum++;
		}
		if($errNum !=0){
			$this->error("撤销成功：".$succNum .'条记录；撤销失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("撤销成功：".$succNum .'条记录；');
		}
	}
	//审核信息
	public function applyview(){
		if(I("request.id/s")==""){
			$this->error("参数错误");
		}
		$applydatas=M('申请回填')->table("dms_申请回填 as a")->join('dms_报单 as b on b.编号=a.编号 and b.id=a.saleid')->where(array("a.id"=>array("in",I("request.id/s"))))->field("a.id as pid,a.saleid,a.编号,a.申请日期,a.申请状态,a.转正方式,b.*")->select();
		$this->assign('applydatas',$applydatas);
		$this->assign('adminshow',adminshow('sale_pv'));
		$this->assign('idstrs',I("request.id/s"));
		foreach(X("fun_bank") as $fun_bank){
			$banks[$fun_bank->name]=$fun_bank->byname;
		}
		$this->assign("banks",$banks);
		$this->display();
	}
	public function applyok(){
		if(I("request.idstrs/s")==""){
			$this->error("参数错误");
		}
		$accbank=I("post.accbank/s");
		$errMsg = '';
		$succNum = 0;
		$errNum = 0;
		foreach(explode(',',I("request.idstrs/s")) as $id){
			if(!$id) continue;
			M()->startTrans();
			$applydata=M('申请回填')->table("dms_申请回填 as a")->join('dms_报单 as b on b.编号=a.编号 and b.id=a.saleid')->where(array("a.id"=>$id))->lock(true)->field("a.id as pid,a.saleid,a.编号,a.申请日期,a.申请状态,a.转正方式,b.*")->find();
			$sale=X("@".$applydata['报单类别']);
			$return=$sale->applyok($applydata,$accbank);
			if($return !== true){
				$errNum++;
				$errMsg .= $applydata['编号'].'转正：'.$return.'<br/>';
				M()->rollback();
				continue;
			}
			M()->commit();
			$this->saveAdminLog("","",'转正审核',"转正会员[".$applydata['编号']."]".date("Y-m-d",$applydata['申请日期']).$applydata['转正方式'].'申请');
			$succNum++;
		}
		if($errNum !=0){
			$this->error("审核成功：".$succNum .'条记录；审核失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("审核成功：".$succNum .'条记录；');
		}
	}
	//返回姓名
	public function realnameAjax()
	{
		$user = M("会员")->where(array("编号"=>I("post.userid/s")))->find();
		if($user && I("post.userid/s")!= '')
		{
			$this->ajaxReturn(array('姓名'=>$user['姓名']),'成功',1);
		}
		else
		{
			$this->ajaxReturn('','失败',0);
		}
	}
	//订单删除前
    public function pre_delete()
	{
		$sdata = array();
		if(I("get.id/s")!=""){
			$sdata = M("报单")->where(array("id"=>array("in",I("get.id/s"))))->getField("id idkey,编号,购买日期,报单金额,购物金额,报单状态");
			$this->assign('ids',I("get.id/s"));
		}
		$this->assign('sdata',$sdata);
		$this->display();
	}
	//订单删除
	public function delete()
	{
		/*需要改进的终极效果，
		如果客户有多选删除，则应该弹出一个模式窗口，通过AJAX分别调用要删除的订单，并将结果以列表形式展现出来
		*/
		set_time_limit(1800);
		ini_set('memory_limit','500M');
		$errMsg = array();//'';
		$succNum = 0;
		$errNum  = 0;
		//foreach(explode(',',$_GET['id']) as $id)
		foreach(explode(',',I("post.ids/s")) as $id)
		{
			if($id == '') continue;
			M()->startTrans();
			$data = M("报单")->find($id);
			//已确认的订单可以进行处理
			if($data['报单状态']=='已生效')
			{
				$errNum++;
				$errMsg[$id]= array('msg'=>'已生效');
				continue;
			}
			
			$sale = X("sale_*@".$data['报单类别']);
			//判断如果是注册订单的话 则同步删除会员订单
			if(get_class($sale)=='sale_reg')
				$ret = X('user')->delete($data['userid']);
			else
				$ret = $sale->delete($data);

			if($ret === true)
			{
				$this->saveAdminLog($data,'','订单删除',$data['编号']."的订单".$id."删除成功");
				$succNum++;
				$errMsg[$id]= array('msg'=>'删除成功');
			}
			else
			{
				$errNum++;
				//$errMsg.=$ret;
				$errMsg[$id]= array('msg'=>$ret);
			}
			M()->commit();
		}
		echo json_encode($errMsg);
		/*if($errNum !=0){
			$this->error("删除成功：".$succNum .'条记录；删除失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("删除成功：".$succNum .'条记录；');
		}*/
	}
	//填写物流信息
	public function send(){
		if(strpos(I("get.id/s"),',') !== false){
			$this->error('参数错误!');
		}
		$sale=M("报单")->where(array("id"=>I("get.id/d")))->find();
		$this->assign('id',I("get.id/d"));
		$this->assign('sale',$sale);
		//快递公司
		$express=M("快递")->where(array("state"=>'是'))->field('company')->select();
		$this->assign('express',$express);
		//收货信息编辑
		$edit=false;
		if(($sale['产品']==0 && adminshow('kuaidi_edit')) || ($sale['产品']==1 && adminshow('kuaidi_edit_pro'))) $edit=true;
		$this->assign("edit",$edit);
		$this->assign('error','');
		$this->display();
	}
	//发货,或保存发货信息
	public function sended()
	{
		$errMsg = '';
		$succNum = 0;
		$errNum = 0;
		foreach(explode(',',I("post.id/s")) as $id){
			if($id == '') continue;
			M()->startTrans();
			$saledata = M("报单")->find($id);
			$userid =$saledata['编号'];
			//验证出库数量
			if($saledata['产品']==1 && adminshow('prostock')){
				$product=M("产品订单")->where(array("报单id"=>$saledata['id']))->select();
				if($product){
					foreach($product as $k=>$productdata){
						if($k==0)$proobj=X("product@".$productdata['产品节点']);
						$checkstr=$proobj->checknum($productdata['产品id'],$productdata['数量'],"数量");
						if($checkstr!='') {
							$errNum++;
							$errMsg .= $userid."：".$checkstr;
							M()->rollback();
							continue;
						}
					}
				}
			}
			//判断本次是否需要减库存
			if($saledata['物流状态']==1)
			{
				if($saledata['产品']==1){
					$proobj=X("product@");
					if($proobj) $proobj->outpro($saledata['id']);
				}
			}
			$saledata['物流状态']='已发货';
			$saledata['发货日期']=systemTime();
			$saledata['发货类型']='后台';
			$saledata['发货人']=$_SESSION['loginAdminAccount'];
			//快递选择
			if((adminshow('kuaidi') && $saledata['产品']==0) || (adminshow('kuaidi_pro') && $saledata['产品']==1)){
				if(I("post.company/s")=='' || I("post.kddd/s")=='') 
					$this->error("请完善快递信息");
				$saledata['快递公司']=I("post.company/s");
				$saledata['快递订单']=I("post.kddd/s");
				$saledata['快递备注']=I("post.kdmemo/s");
			}
			//收货信息
			if((adminshow('kuaidi_edit') && $saledata['产品']==0) || (adminshow('kuaidi_edit_pro') && $saledata['产品']==1)){
				if(I("post.city/s")=='' || I("post.receiver/s")=='' || I("post.mobile/s")=='' || I("post.address/s")=='')
					$this->error("请完善收货信息");
				$saledata['收货国家']=I("post.country/s");
				$saledata['收货省份']=I("post.province/s");
				$saledata['收货城市']=I("post.city/s");
				$saledata['收货地区']=I("post.county/s");
				$saledata['收货街道']=I("post.town/s");
				$saledata['收货人']	=I("post.receiver/s");
				$saledata['联系电话']=I("post.mobile/s");
				$saledata['收货地址']=I("post.address/s");
			}
			$result=M("报单")->where(array('id'=>$id))->save($saledata);
			$succNum++;
			$this->saveAdminLog($saledata,'','订单发货','['.$userid.']'."的订单发货");
			M()->commit();
		}
		if($errNum !=0){
			$this->error("操作成功：".$succNum .'条记录；操作失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("操作成功：".$succNum .'条记录；');
		}
	}
	
	public function report()
	{
		//销售月报表
		$thisday=systemTime();
		//开始时间
		$startTime=strtotime(date('Y-m-1',systemTime()));
		//创建缓存数据
		$data=array();
		$user=X('user');
		$sales=X('sale_*');
		for($day_i = 0; $day_i < date('t',systemTime());$day_i++)
		{
			$daydata=array();
			foreach($sales as $sale)
			{
				$sum=M('报单')->where(array('报单类别'=>$sale->name))->sum('报单金额');
				if($sum == null) $sum = 0;
				$daydata[$sale->name] = $sum;
			}
			$data[$startTime+$day_i*86400] = $daydata;
		}
		$this->display();
		
	}
	//导出环讯
	public function getHxExcel(){
		ini_set('memory_limit','600M');
		set_time_limit(400);
		$where = unserialize(base64_decode(I("get.where/s")));
		$whereArr = explode(' ',$where);
		$where = preg_replace("/(\S+)\s*[=><]/U",'a.$0',$where);
		$m= M("报单");
        $m->table("dms_报单 as a");	
	    $result=$m->join('dms_会员 as b on a.编号=b.编号')->field("a.id,a.编号,b.姓名,a.报单状态,a.到款日期,a.物流状态,a.发货日期,a.收货日期,a.服务中心编号,a.付款人编号,a.注册人编号,a.报单类别,a.报单金额,a.购物金额,a.购物PV")->where($where)->select();
		if(Extension_Loaded('zlib')){
			Ob_Start('ob_gzhandler');
		}
		Header("Content-type: text/html"); 
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        $title =date("YmdHis");
        header("Content-Disposition: attachment; filename=\"excel_{$title}.xls\"");
        echo '<html xmlns="http://www.w3.org/1999/xhtml">';
        echo '<head>';
        echo '<title>Untitled Document</title>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        echo '</head>';
        echo '<body>';
        echo '<table style="WIDTH: 80%" cellspacing="0" cellpadding="1" border="1" bandno="0">';
        echo '<tr><th>报单编号</th><th>会员编号</th><th>姓名</th><th>订单状态</th><th>付款日期</th><th>物流状态</th><th>发货日期</th><th>收货日期</th><th>服务中心</th><th>付款人</th><th>注册人</th><th>订单类别</th><th>报单金额</th><th>购物金额</th><th>购物PV</th></tr>';
        foreach($result as $val){
			echo '<tr>';
            foreach($val as $k=>$v){
                if($k !== 8){
					echo '<td style="vnd.ms-excel.numberformat:@">'.$v.'</td>';	
				}else{
					echo '<td>'.$v.'</td>';
				}
            }
            echo '</tr>';
			$ms= M("产品订单")->where(array("报单id"=>$val["id"]))->select();
			if($ms){
				echo '<tr></tr><tr style="border:0"><td border="0"></td><td colspan="3"><table style="WIDTH: 80%; background-color:#D8D8D8" cellspacing="0" cellpadding="1" border="1" bandno="0"><tr><td>名称</td><td>分类</td><td>数量</td></tr><tr>';
				$msa= M("产品订单")->where(array("报单id"=>$val["id"]))->select();
				foreach($msa as $vss){	
					echo '<td>'.$vss["名称"].'</td>';
					echo '<td>'.$vss["分类"].'</td>';
					echo '<td>'.$vss["数量"].'</td>';	
					echo "</tr>";
				}
				echo "</table></td></tr><tr></tr>";   
			}
		}
        echo '</table>';
        echo '</body>';
        echo '</html>';
		if(Extension_Loaded('zlib')) Ob_End_Flush(); 
	}
	//获取物流费和折扣并计算实付款
	function wuliufei(){
		$zhekou=1;$wlf=0;
		$province 	= I('post.province/s');
		$weight 	= I('post.weight/f');
		$zongjia 	= I('post.zongjia/f');
		$userid  	= trim(I('post.userid/s'));
		$salename   = I('post.salename/s');
		$sale=X("@".$salename);
		$saletype=get_class($sale);
		//计算折扣
		if(X('user')->haveZhekou($sale)){
			//注册的默认按照会员级别来计算折扣
			if($saletype=='sale_reg'){
				$name1=$sale->lvName;
				$user=array($name1=>I("post.lv/d"));
			}else{//升级或购买，按照填写的会员信息
				if($userid!=''){//升级按照统一的，没设计按照老级别还是新级别
					$user=M("会员")->where(array("编号"=>$userid))->find();
				}
			}
			if($user){
				$zhekou=$sale->getDiscount($user);
			}
		}
		//计算物流费
		if($sale->logistic){
			//后台升级和购物没设计填写物流信息，所以默认读会员
			if($saletype!='sale_reg' && I('post.province/s')!="" && $user){
				$province=$user['省份'];
			}
			$wlf=X("product@")->getWlf($weight,$province);
		}
		//返回
		$ress['zk']	 = $zhekou;
		$ress['wlf'] = $wlf;
		$ress['totalzf'] = $zongjia*$zhekou+$wlf;
		$this->ajaxReturn($ress,'成功',1);
    }
    	//导出订单信息
	public function outlist()
	{
		if(strpos($_GET['id'],',') !== false){
			$this->error('参数错误!');
		}
		$name1 = '';
		$lvlname='';
		$model		= M('报单');
		$id			= $_REQUEST ['id'];
		$where['id']= $id;
		$vo			= $model->where($where)->find();
		$productdata = M('产品订单')->where(array('报单id'=>$id))->select();
		//获取会员信息
		$user = M('会员')->where(array('编号'=>$vo['编号']))->find();
		$this->assign('user',$user);
		$this->assign('pvshow',adminshow('sale_pv'));
		$this->assign('productdata',$productdata);
		$this->assign('vo',$vo);
		$this->display();
	}   //生成随机数
 
}
?>