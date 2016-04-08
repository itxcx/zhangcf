<?php
// 本类由系统自动生成，仅供测试用途
defined('APP_NAME') || die('不要非法操作哦!');
class GoldAction extends CommonAction {
	//货币设置
	public function config($gold)
	{
		$this->assign('gold' ,$gold);
		$setmoney=true;$numSelect="";
		if($gold->numSelect!=""){
			$numSelect=preg_replace("/,/","\n",$gold->numSelect);
			$setmoney=false;
		}
		$this->assign('numSelect',$numSelect);
		$this->assign('setmoney',$setmoney);
		$this->assign('goldsellInput',explode(',',$gold->sellInput));
		$this->assign('goldepsellInput',explode(',',$gold->epsellInput));
		$this->assign('goldbuyInput',explode(',',$gold->buyInput));
		$this->assign('goldepbuyInput',explode(',',$gold->epbuyInput));
		$this->assign('xpath',$gold->objPath());
        $this->display();
	}
	public function configUpdate($gold){
		//判断是否设置了数量选择项
		if(I("post.setmoney/s")=='false' && I("post.numSelect/s")==""){
			$this->error("请设置数量选择项，设置一个或多个数字");
		}
		if(I("post.numSelect/s")!=""){
			//处理数量选择项的值
			$numSelect=trim(preg_replace("/\r/","",preg_replace("/\n/",",",I("post.numSelect/s"))),',');
			if($numSelect==""){
				$this->error("请设置数量选择项，设置一个或多个数字");
			}
		}
		if(I("post.rmbMin/f")>I("post.rmbMax/f")){
			$this->error("请调整下出售单价的大小关系");
		}
		M()->startTrans();
		$gold->setatt("open",I("post.open/d"));//交易大厅开关
		$gold->setatt("closeMsg",I("post.closeMsg/s"));//交易大厅关闭提示语
		$gold->setatt("sellOpen",I("post.sellOpen/d"));//卖出操作开关
		$gold->setatt("buyOpen",I("post.buyOpen/d"));//买入操作开关
		
		$rmbMin=I("post.rmbMin/f");
		$rmbMax=I("post.rmbMax/f");
		$tax=I("post.tax/f");
		$buyAll=I("post.buyAll/d");
		$buyMax=I("post.buyMax/d");
		$buyDayNum=I("post.buyDayNum/d");
		$sellMax=I("post.sellMax/d");
		$sellDayNum=I("post.sellDayNum/d");
		
		$gold->setatt("rmbMin",$rmbMin);//最小出售金额
		$gold->setatt("rmbMax",$rmbMax);//最大出售金额
		$gold->setatt("tax",$tax);//手续费比例
		$gold->setatt("buyAll",$buyAll);//是否购买挂单的全额
		$gold->setatt("buyMax",$buyMax);//未成交购买挂单上限
		$gold->setatt("buyDayNum",$buyDayNum);//日购买数量上限
		$gold->setatt("sellMax",$sellMax);//未成交出售挂单上限
		$gold->setatt("sellDayNum",$sellDayNum);//日出售数量上限
		
		if(I("post.creditStyle/s")=="sung"){
			if($gold->creditStyle!=I("post.image_1/s") && I("post.image_1/s")!=""){
				$gold->setatt("creditStyle",I("post.image_1/s"));				//信誉值显示图例
			}
		}else{
			$gold->setatt("creditStyle",'');
		}
		$creditNum=I("post.creditNum/d")>0?I("post.creditNum/d"):10;
		$gold->setatt("creditNum",$creditNum);//信誉值默认值
		/* 是否可有卖家设置出售数量 */
		if(I("post.setmoney/s")=='true'){
			$numMin=I("post.numMin/d");
			$numMax=I("post.numMax/d");
			$intNum=I("post.intNum/d");
			
			$gold->setatt("numMin",$numMin);//出售最小数量
			$gold->setatt("numMax",$numMax);//出售最大数量
			$gold->setatt("intNum",$intNum);//倍数设置
			$gold->setatt("numSelect","");//清空出售挂单强制数量
		}else{
			$gold->setatt("numSelect",$numSelect);//设置出售挂单强制数量
		}
		$payTime=I("post.payTime/d");
		$confirmTime=I("post.confirmTime/d");
		
		$gold->setatt("payTime",$payTime);//买家付款时限
		$gold->setatt("confirmTime",$confirmTime);//卖家确认时限
		
		$sellInput=implode(",",I("post.sellInput/a"));
		$epsellInput=implode(",",I("post.epsellInput/a"));
		$buyInput=implode(",",I("post.buyInput/a"));
		$epbuyInput=implode(",",I("post.epbuyInput/a"));
		
		$gold->setatt("sellInput",$sellInput);
		$gold->setatt("epsellInput",$epsellInput);
		$gold->setatt("buyInput",$buyInput);
		$gold->setatt("epbuyInput",$epbuyInput);
		M()->commit();
		$this->success("设置完成");
	}
	public function UploadPhoto($gold)
    {
    	$id="";
    	I("get.id/d")>0 && $id=I("get.id/d");
    	$this->assign('gold' ,$gold);
		$this->assign('id',$id);
        $this->display();
    }
    public function UploadPhotoSave($gold){
		if(I("post.img_src/s")==""){
			echo json_encode(array('error' => 1, 'message' =>'请选择图例样式'));
		}
		M()->startTrans();
		$gold->setatt("creditStyle",I("post.img_src/s"));
		M()->commit();
		echo json_encode(array('error' => 0, 'url' =>I("post.img_src/s")));
	}
	//货币明细
	public function index($gold)
	{
        $setButton=array(
        	"撤销挂单"=>array("class"=>"delete","href"=>__APP__."Admin/Gold/sellconcel:__XPATH__/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true"),
	        );
        $list=new TableListAction($gold->name."挂单");
		$list->table("dms_".$gold->name."挂单 a");
        $list->setButton = $setButton;       // 定义按钮显示
		$list->join('left join dms_会员 as b on a.编号=b.编号')->field('a.*,b.姓名');
        $list->where("1=1")->order("a.时间 desc,a.id desc");
        $list->addshow("挂单时间",array("row"=>"[时间]","url"=>__APP__."/Admin/Gold/tradelist:__XPATH__/idstr/[购买数据]","target"=>"navTab","urlAttr"=>'mask="true" width="700" height="480" title="交易明细"',"searchMode"=>"date","format"=>"time","order"=>"时间",'searchRow'=>'a.时间'));
        $list->addshow($this->userobj->byname."编号",array("row"=>"[编号]","searchMode"=>"text",'searchGet'=>'userid',"excelMode"=>"text","searchPosition"=>"top",'searchRow'=>'a.编号'));   
		$list->addshow("姓名",array("row"=>"[姓名]","searchMode"=>"text","excelMode"=>"text"));
        $list->addShow("未成交数量",array("row"=>"[未成交数量]","searchMode"=>"num","excelMode"=>"num",'searchRow'=>'a.未成交数量',"order"=>"a.未成交数量"));
        $list->addShow("成交中数量",array("row"=>"[成交中数量]","searchMode"=>"num","excelMode"=>"num",'searchRow'=>'a.成交中数量',"order"=>"a.成交中数量"));
        $list->addShow("已成交数量",array("row"=>"[已成交数量]","searchMode"=>"num","excelMode"=>"num",'searchRow'=>'a.已成交数量'));
        $list->addshow("单价",array("row"=>"[单价]","searchMode"=>"num",'searchRow'=>'a.单价',"searchPosition"=>"top","order"=>"a.单价"));
        $list->addshow("状态",array("row"=>"[状态]","searchSelect"=>array("有效"=>"有效","完成"=>"完成","撤销"=>"撤销"),"searchMode"=>"text",'searchRow'=>'a.状态'));
        $this->assign('list',$list->getHtml()); 
        $this->display();
	}
	public function sellconcel($gold){
		set_time_limit(1800);
		ini_set('memory_limit','500M');
		$errMsg = '';
		$succNum = 0;
		$errNum = 0;
		foreach(explode(',',I("get.id/s")) as $sellid){
			if($sellid == '') 
				continue;
			$sellinfo=M($gold->name."挂单")->where(array("id"=>$sellid))->find();
			if(!$sellinfo){
				$errNum++;
				$errMsg.=$sellid."撤销：获取信息错误<br/>";
				continue;
			}
			M()->startTrans();
			$result=$gold->cancelSell($sellinfo['id']);
			if(gettype($result)=='string'){
				$errNum++;
				$errMsg.=$sellinfo['id']."撤销：".$result.'<br/>';
				M()->rollback();
				continue;
			}
			M()->commit();
			$this->saveAdminLog("","","撤销购买","撤销会员[".$sellinfo['编号']."]".time("Y-m-d H:i:s",$sellinfo['时间']).$gold->name."挂单");
			$succNum++;
		}
		if($errNum !=0){
			$this->error("撤销成功：".$succNum .'条记录；撤销失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("撤销成功：".$succNum .'条记录；');
		}
	}
    public function tradelist($gold){
    	$setButton=array(
    		"查看"    =>array("class"=>"edit"  ,"href"=>__APP__."Admin/Gold/detailview:__XPATH__/id/{tl_id}" ,"target"=>"navTab",'icon'=>'/Public/Images/ExtJSicons/application/application_form_magnify.png'),
    		"确认审核"=>array("class"=>"edit"  ,"href"=>__APP__."Admin/Gold/accoktrade:__XPATH__/id/{tl_id}" ,"target"=>"ajaxTodo","mask"=>"true"),
    		"仲裁操作"=>array("class"=>"edit"  ,"href"=>__APP__."Admin/Gold/arbitrate:__XPATH__/id/{tl_id}"  ,"target"=>"dialog","width"=>"780","height"=>"450","mask"=>"true"),
    		"撤销购买"=>array("class"=>"delete","href"=>__APP__."Admin/Gold/tradeconcel:__XPATH__/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true"),
	        );
        $list=new TableListAction($gold->name."购买");
		$list->table("dms_".$gold->name."购买 a");
        $list->setButton = $setButton;       // 定义按钮显示
		$list->join('left join dms_会员 as b on a.编号=b.编号');
		if(I("request.idstr/s")){
			$list->where(array("a.id"=>array("in",I("request.idstr/s"))));
		}
		$list->field('a.*,b.姓名');
        $list->order("a.购买时间 desc,a.id desc");
        $list->addshow("购买时间",array("row"=>"[购买时间]","searchMode"=>"date","format"=>"time","order"=>"购买时间",'searchRow'=>'a.购买时间'));
        $list->addshow("买家编号",array("row"=>"[编号]","searchMode"=>"text",'searchGet'=>'userid',"excelMode"=>"text","searchPosition"=>"top",'searchRow'=>'a.编号'));   
        $list->addShow("数量",array("row"=>"[数量]","searchMode"=>"num","excelMode"=>"num",'searchRow'=>'a.数量',"order"=>"a.数量"));
        $list->addShow("单价",array("row"=>"[单价]","searchMode"=>"num","excelMode"=>"num",'searchRow'=>'a.单价',"order"=>"a.单价"));
        $list->addShow("总额",array("row"=>array(array($this,"showmoney"),"[单价]","[数量]")));
        $list->addshow("卖家编号",array("row"=>"[卖家编号]","searchMode"=>"text",'searchGet'=>'userid',"excelMode"=>"text",'searchRow'=>'a.卖家编号'));
        $list->addshow("汇款时间",array("row"=>"[汇款时间]","searchMode"=>"date","format"=>"date",'searchRow'=>'a.汇款时间'));
        $list->addshow("状态",array("row"=>"[状态]","searchMode"=>"text","searchSelect"=>array("待付"=>"待付","已付"=>"已付","完成"=>"完成","取消"=>"取消","仲裁"=>"仲裁","仲裁卖家"=>"仲裁卖家","仲裁买家"=>"仲裁买家"),'searchRow'=>'a.状态'));
        $list->addshow("付款时间",array("row"=>"[付款时间]","searchMode"=>"date","format"=>"time",'searchRow'=>'a.付款时间'));
        $this->assign('list',$list->getHtml()); 
        $this->display();
    }
    function showmoney($price,$num){
    	return $price*$num;
    }
    public function detailview($gold){
    	$buyinfo=M($gold->name."购买")->where(array("id"=>I("request.id/d")))->find();
    	if(!$buyinfo){
    		$this->error("数据错误");
    	}
    	$sellinfo=M($gold->name."挂单")->where(array("id"=>$buyinfo['pid']))->find();
    	$this->assign("buyinfo",$buyinfo);
    	$this->assign("sellinfo",$sellinfo);
		$this->display();
    }
    public function arbitrate($gold){
    	$buyinfo=M($gold->name."购买")->where(array("id"=>I("request.id/d")))->find();
    	if(!$buyinfo){
    		$this->error("数据错误");
    	}
    	$sellinfo=M($gold->name."挂单")->where(array("id"=>$buyinfo['pid']))->find();
    	if($buyinfo['状态']!="仲裁"){
    		//$this->error("状态错误");
    	}
    	$this->assign("buyinfo",$buyinfo);
    	$this->assign("sellinfo",$sellinfo);
		$this->display();
    }
    public function arbitratesave($gold){
    	$buyinfo=M($gold->name."购买")->where(array("id"=>I("post.id/d")))->find();
    	if(!$buyinfo){
    		$this->error("数据错误");
    	}
    	$sellinfo=M($gold->name."挂单")->where(array("id"=>$buyinfo['pid']))->find();
    	if($buyinfo['状态']!="仲裁"){
    		$this->error("状态错误");
    	}
    	if(I("post.type/s")=="仲裁买家"){
    		$arbitratename=$buyinfo['编号'];
    	}else if(I("post.type/s")=="仲裁卖家"){
    		$arbitratename=$buyinfo['卖家编号'];
    	}else{
    		$this->error("请选择仲裁对象");
    	}
    	if(I("post.xynum/d")<=0){
    		$this->error("请填写仲裁的扣分数量");
    	}
    	M()->startTrans();
		$gold->arbitrate($arbitratename,$buyinfo['id'],I("post.xynum/d"),I("post.zccontent/s"));
		M()->commit();
		$this->saveAdminLog("","",'仲裁'.$gold->name.'违规',"仲裁会员[".$arbitratename."]扣除信誉点".I("post.xynum/d"));
		$this->success("仲裁完成","__URL__/tradelist:__XPATH__");
    }
    //撤销购买
    public function tradeconcel($gold){
    	set_time_limit(1800);
		ini_set('memory_limit','500M');
		$errMsg = '';
		$succNum = 0;
		$errNum = 0;
		foreach(explode(',',I("get.id/s")) as $buyid){
			if($buyid == '') 
				continue;
			$buyinfo=M($gold->name."购买")->where(array("id"=>$buyid))->find();
			if(!$buyinfo){
				$errNum++;
				$errMsg.=$buyid."撤销：获取信息错误<br/>";
				continue;
			}
			M()->startTrans();
			$result=$gold->cancelBuy($buyinfo['id']);
			if(gettype($result)=='string'){
				$errNum++;
				$errMsg.=$buyinfo['id']."撤销：".$result.'<br/>';
				M()->rollback();
				continue;
			}
			M()->commit();
			$this->saveAdminLog("","","撤销购买","撤销会员[".$buyinfo['编号']."]".date("Y-m-d",$buyinfo['购买时间']).$gold->name."购买订单");
			$succNum++;
		}
		if($errNum !=0){
			$this->error("撤销成功：".$succNum .'条记录；撤销失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("撤销成功：".$succNum .'条记录；');
		}
    }
    public function accoktrade($gold){
    	set_time_limit(1800);
		ini_set('memory_limit','500M');
		$errMsg = '';
		$succNum = 0;
		$errNum = 0;
		foreach(explode(',',I("get.id/s")) as $buyid){
			if($buyid == '') 
				continue;
			$buyinfo=M($gold->name."购买")->where(array("id"=>$buyid))->find();
			if(!$buyinfo){
				$errNum++;
				$errMsg.=$buyid."审核：获取信息错误<br/>";
				continue;
			}
			M()->startTrans();
			$userinfo=M("会员")->where(array("编号"=>$buyinfo['卖家编号']))->find();
			$result=$gold->accokTrad($userinfo,$buyinfo);
			if(gettype($result)=='string'){
				$errNum++;
				$errMsg.=$buyinfo['id']."审核：".$result.'<br/>';
				M()->rollback();
				continue;
			}
			M()->commit();
			$this->saveAdminLog("","","审核购买","审核会员[".$buyinfo['编号']."]".date("Y-m-d",$buyinfo['购买时间']).$gold->name."购买订单");
			$succNum++;
		}
		if($errNum !=0){
			$this->error("审核成功：".$succNum .'条记录；审核失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("审核成功：".$succNum .'条记录；');
		}
    }
    public function recharge($gold){
    	$this->assign("name",$gold->name."信誉充值");
    	$this->display();
    }
    //返回姓名
	public function realnameAjax()
	{
		if(I('post.userid/s')!= ''){
			$user = $this->userobj->getuser(I('post.userid/s'));
			if($user)
			{
				$this->ajaxReturn(array('姓名'=>$user['姓名']),'成功',1);
			}
			else
			{
				$this->ajaxReturn('','失败',0);
			}
		}else{
			$this->ajaxReturn('','编号不能为空',0);
		}
	}
	public function rechargeSave($gold){
    	if(I('post.userid/s')==""){
    		$this->error("请填写会员编号");
    	}
    	if(I('post.chargeSum/d')<=0){
    		$this->error("请填写充值信誉值");
    	}
    	M()->startTrans();
    	$result=$gold->recharge(I('post.userid/s'),I('post.chargeSum/d'));
    	if(gettype($result)=='string'){
    		$this->error($result);
    	}
    	M()->commit();
    	$this->success('充值成功!');
    }
}
?>