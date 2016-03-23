<?php
defined('APP_NAME') || die(L('不要非法操作哦'));
class Fun_goldAction extends CommonAction {
	public function index(fun_gold $fun_gold){
		/* 市场中出售的记录 */
		if($fun_gold->open!=1){
			if($fun_gold->closeMsg==""){
				$this->error(L($fun_gold->name."已关闭，请耐心等待开启"));
			}else{
				$this->error(L($fun_gold->closeMsg."，".$fun_gold->name."已关闭"));
			}
		}
		$list = new TableListAction($fun_gold->name."挂单");
		$list->table('dms_'.$fun_gold->name."挂单 a")->join(" inner join dms_会员 u on u.编号=a.编号");
		$list->pagenum = 15;
		$list->where("a.编号!='".$this->userinfo['编号']."' and a.未成交数量>0");
		$list->pagenum = 15;
		$list ->addShow(L('交易编号'),array("row"=>"[编号]"));
		if($fun_gold->creditStyle!=""){
			$list ->addShow(L($fun_gold->name.'信誉'),array("row"=>array(array($this,"showxy"),"[".$fun_gold->name."信誉]",$fun_gold->creditStyle)));
		}
		$list ->addShow(L('出售数量'),array("row"=>"[未成交数量]"));
		$list ->addShow(L('单价'),array("row"=>"[单价]"));
		$list ->addShow(L('总额'),array("row"=>array(array(&$this,"sumje"),"[未成交数量]","[单价]")));
		$list ->addShow(L('操作'),array("row"=>array(array(&$this,"showdo"),"[id]")));
		$list ->field("a.*,u.".$fun_gold->name.'信誉');
		$data = $list->getData();
        $this->assign('data',$data);
		$this->display();
	}
	function showxy($xynum,$funcreditStyle){
		$creditStyle="";
		for($i=1;$i<=$xynum;$i++){
			$creditStyle.="<img style='display:inline;' src='".$funcreditStyle."'/>";
		}
		if($creditStyle==""){
			$creditStyle="<span style='color:#ff0000;'>信誉不足</span>";
		}
		return $creditStyle;
	}
	function sumje($num,$price){
		return $num*$price;
	}
	function showdo($id){
		return "<a href='__URL__/buy:__XPATH__/id/{$id}'>购买</a>";
		
	}
	//挂单页面
	public function sell(fun_gold $fun_gold){
		if($fun_gold->open!=1){
			if($fun_gold->closeMsg==""){
				$this->error(L($fun_gold->name."已关闭，请耐心等待开启"));
			}else{
				$this->error(L($fun_gold->closeMsg."，".$fun_gold->name."已关闭"));
			}
		}
		$this->assign("user",$this->userinfo);
		$this->assign("gold",$fun_gold);
		if($fun_gold->numSelect){
			$this->assign("numSelect",explode(",",$fun_gold->numSelect));
		}
		$this->assign("sellInput",explode(",",$fun_gold->sellInput));
		$this->assign("epsellInput",explode(",",$fun_gold->epsellInput));
		//我的挂单记录
		$list = new TableListAction($fun_gold->name."挂单");
		$list->pagenum = 15;
		$list->where("编号='".$this->userinfo['编号']."'");
		$list->pagenum = 15;
		$list ->addShow(L('时间'),array("row"=>"[时间]","format"=>"time"));
		$list ->addShow(L('出售数量'),array("row"=>"[数量]"));
		$list ->addShow(L('未出售数量'),array("row"=>"[未成交数量]"));
		$list ->addShow(L('成交中数量'),array("row"=>"[成交中数量]"));
		$list ->addShow(L('已成交数量'),array("row"=>"[已成交数量]"));
		$list ->addShow(L('单价'),array("row"=>"[单价]"));
		$list ->addShow(L('总额'),array("row"=>array(array(&$this,"sumje"),"[数量]","[单价]")));
		$list ->addShow(L('操作'),array("row"=>array(array(&$this,"showid"),"[id]","[购买数据]",'[未成交数量]','[状态]')));
		$data = $list->getData();
        $this->assign('data',$data);
		$this->display();
	}
	function showid($id,$idstr,$nosellnum,$status){
		//声明变量
		$returnstr = '';
		if($idstr!=""){
			$returnstr="<a href='__URL__/detail:__XPATH__/idstr/{$idstr}'>查看记录</a>";
		}
		if($status=="有效" && $nosellnum>0){
			$returnstr.=$returnstr==""?"":"|";
			$returnstr.="<a href='__URL__/cancelSell:__XPATH__/id/{$id}'>收回挂出</a>";
		}
		return $returnstr==""?$status:$returnstr;
	}
	public function cancelSell(fun_gold $fun_gold){
		$sellinfo=M($fun_gold->name."挂单")->where(array("id"=>I("get.id/d")))->find();
		if(!$sellinfo){
			$this->error(L("未找到出售挂单"));
		}
		M()->startTrans();
		$result=$fun_gold->cancelSell($sellinfo['id']);
		if($result!=true){
			M()->rollback();
			$this->error($result);
		}
		$this->userobj->adduserlog($this->userinfo,get_client_ip(),"收回".$fun_gold->name."挂单");
		M()->commit();
		$this->success(L("取消挂单完成"));
	}
	//挂单
	public function sellsave(fun_gold $fun_gold){
		/* 价格 */
		if(I("post.price/f")<=0){
			$this->error(L("出售价格不能小于0"));
		}
		//声明变量
		$mess = '';
		if(I("post.price/f")<$fun_gold->rmbMin){
			$mess="出售价格最小为".$fun_gold->rmbMin;
		}
		if(I("post.price/f")>$fun_gold->rmbMax && $fun_gold->rmbMax>0){
			$mess.="出售价格最大为".$fun_gold->rmbMax;
		}
		if($mess!=""){
			$this->error($mess);
		}
		$result=$fun_gold->checkbank(I("post."),explode(',',$fun_gold->sellInput));
		if($result){
			$this->error($result);
		}
		//声明变量
		$showdata = 0;
		/* 挂单数量 */
		$showdata[date("d",systemTime())];
		if($fun_gold->numSelect==""){
			if(I("post.num/d")<=0){
				$this->error(L("请填写挂单出售的数量"));
			}
			if(I("post.num/d")<$fun_gold->numMin){
				$this->error(L("挂单出售的最小数量".$fun_gold->numMin));
			}
			if($fun_gold->numMax>0)
			{
				if(I("post.num/d")>$fun_gold->numMax){
					$this->error(L("挂单出售的最大数量".$fun_gold->numMax));
				}
			}
			if($fun_gold->intNum>0)
			{
				if(I("post.num/d")%$fun_gold->intNum!=0){
					$this->error(L("挂单出售的数量须为".$fun_gold->intNum."倍数"));
				}
			}
		}else{
			if(I("post.num/d")<=0){
				$this->error(L("请选择挂单出售的数量"));
			}
		}
		//今日挂单量
		if($fun_gold->sellDayNum>0){
			/* 撤销是否计算在内 默认不计算在内了 */
			$sellDayNum=M($fun_gold->name."挂单")->where(array("编号"=>$this->userinfo['编号'],"时间"=>array(array("gt",strtotime(date("Y-m-d",systemTime()))),array("lt",systemTime()))))->sum("数量-撤销数量");
			if(($sellDayNum+I("post.num/d"))>$fun_gold->sellDayNum){
				$this->error(L("今日超出挂单量".$fun_gold->sellDayNum."，超出".(($sellDayNum+I("post.num/d"))-$fun_gold->sellDayNum)));
			}
		}
		//未成交挂单量
		if($fun_gold->sellMax>0){
			$sellMax=M($fun_gold->name."挂单")->where(array("编号"=>$this->userinfo['编号'],"状态"=>"有效"))->sum("数量-撤销数量");
			if(($sellMax+I("post.num/d"))>$fun_gold->sellMax){
				$this->error(L("已超出挂单量".$fun_gold->sellMax."，超出".(($sellMax+I("post.num/d"))-$fun_gold->sellMax)));
			}
		}
		/* 手续费 */
		$_POST['tax']=I("post.num/d")*$fun_gold->tax/100;
		M()->startTrans();
		$result=$fun_gold->setcompany($this->userinfo,I("post."));
		if(gettype($result)=='string'){
			M()->rollback();
			$this->error($result);
		}
		$this->userobj->adduserlog($this->userinfo,get_client_ip(),$fun_gold->name."挂单".I("post.num/d"));
		M()->commit();
		$this->success(L("挂单完成"));
	}
	public function buy(fun_gold $fun_gold){
		if($fun_gold->buyOpen==0){
			$this->error(L($fun_gold->name."购买已关闭，请等待开启..."));
		}
		if($fun_gold->creditStyle!=""){
			if($this->userinfo[$fun_gold->name.'信誉']<=0){
				$this->error(L("抱歉，您的信誉太低了..."));
			}
		}
		$sellinfo=M($fun_gold->name."挂单")->where(array("id"=>I("get.id/d")))->find();
		if(!$sellinfo){
			$this->error(L("未找到出售挂单"));
		}
		$this->assign("gold",$fun_gold);
		$this->assign("sellinfo",$sellinfo);
		$this->display();
	}
	public function buysave(fun_gold $fun_gold){
		M()->startTrans();
		$sellinfo = M($fun_gold->name."挂单")->lock(true)->where(array("id"=>I("post.id/d")))->find();
		if($fun_gold->buyAll==1){
			$_POST['buynum']=$sellinfo['未成交数量'];
		}
		$buynum = I("post.buynum/d");
		if($sellinfo['未成交数量']<$buynum){
			$this->error(L("出售不足" . $buynum));
		}
		if($buynum<=0)
		{
			$this->error(L("数据非法"));
		}
		//每日购买量
		if($fun_gold->buyDayNum>0){
			/* 撤销是否计算在内 默认不计算在内了 */
			$buyDayNum=M($fun_gold->name."购买")->where(array("编号"=>$this->userinfo['编号'],"购买时间"=>array(array("gt",strtotime(date("Y-m-d",systemTime()))),array("lt",systemTime())),"状态"=>array("neq","取消")))->sum("数量");
			if(($buyDayNum+$buynum)>$fun_gold->buyDayNum){
				$this->error(L("今日超出购买量".$fun_gold->buyDayNum."，超出".(($buyDayNum+$buynum)-$fun_gold->buyDayNum)));
			}
		}
		//未成交挂单量
		if($fun_gold->buyMax>0){
			$buyMax=M($fun_gold->name."购买")->where(array("编号"=>$this->userinfo['编号'],"状态"=>array("in","待付,已付,完成")))->sum("数量");
			if(($buyMax+$buynum)>$fun_gold->buyMax){
				$this->error(L("已超出购买量".$fun_gold->buyMax."，超出".(($buyMax+$buynum)-$fun_gold->buyMax)));
			}
		}
		$result=$fun_gold->buyComOrder($this->userinfo,$buynum,I("post.id/d"),'');
		if(gettype($result)=='string'){
			M()->rollback();
			$this->error($result);
		}
		$this->userobj->adduserlog($this->userinfo,get_client_ip(),$fun_gold->name."购买".$buynum);
		M()->commit();
		$this->success(L("购买完成"),"__URL__/detail:".$fun_gold->objPath());
	}
	public function detail(fun_gold $fun_gold){
		$list = new TableListAction($fun_gold->name."购买");
		$list->table("dms_".$fun_gold->name."购买 as a");
		$list->pagenum = 15;
		$list->join("dms_".$fun_gold->name."挂单 b on b.id=a.pid");
		if(I("get.idstr/s")==""){
			$where="find_in_set(a.id,'".I("get.idstr/s")."')";
		}else{
			$where="((a.编号='".$this->userinfo['编号']."' and a.买家删除!=1) or (b.编号='".$this->userinfo['编号']."' and a.卖家删除!=1))";
		}
		$list->where($where." and a.状态!='取消'")->field("a.*,b.编号 as 出售编号");
		$list->pagenum = 15;
		$list ->addShow(L('编号'),array("row"=>"[编号]"));
		$list ->addShow(L('交易数量'),array("row"=>"[数量]"));
		$list ->addShow(L('单价'),array("row"=>"[单价]"));
		$list ->addShow(L('汇款金额'),array("row"=>array(array(&$this,"sumje"),"[数量]","[单价]")));
		$list ->addShow(L('状态'),array("row"=>"[状态]"));
		$list ->addShow(L('出售编号'),array("row"=>"[出售编号]"));
		$list ->addShow(L('操作'),array("row"=>array(array(&$this,"bshowdo"),"[id]","[状态]","[编号]","[出售编号]")));
		$data = $list->getData();
        $this->assign('data',$data);
		$this->display();
	}
	//操作
	function bshowdo($id,$status,$buyname,$sellname){
		if($status=="待付"){
			if($this->userinfo['编号']==$sellname){
				$str="<a href='__URL__/detailview:__XPATH__/id/{$id}'>查看</a>|等待汇款";
			}else if($this->userinfo['编号']==$buyname){
				$str="<a href='__URL__/detailview:__XPATH__/id/{$id}'>查看</a> | <a href='__URL__/addrem:__XPATH__/id/{$id}'>汇款</a> | <a href='__URL__/cancelBuy:__XPATH__/id/{$id}'>取消</a>";
			}
		}elseif($status=="已付"){
			if($this->userinfo['编号']==$sellname){
				$str="<a href='__URL__/detailview:__XPATH__/id/{$id}'>查看</a>|<a href='__URL__/apply:__XPATH__/id/{$id}'>申请仲裁</a>|<a href='__URL__/accokrem:__XPATH__/id/{$id}'>确认</a>";
			}else if($this->userinfo['编号']==$buyname){
				$str="<a href='__URL__/detailview:__XPATH__/id/{$id}'>查看</a>|等待确认";
			}
		/*
		}elseif($status=="完成"){
			if($this->userinfo['编号']==$sellname){
				$str="<a href='__URL__/detailview:__XPATH__/id/{$id}'>查看</a>|<a href='__URL__/delhide:__XPATH__/id/{$id}'>删除</a>";
			}else if($this->userinfo['编号']==$buyname){
				$str="<a href='__URL__/detailview:__XPATH__/id/{$id}'>查看</a>|<a href='__URL__/delhide:__XPATH__/id/{$id}'>删除</a>";
			}*/
		}elseif($status=="仲裁"){
			if($this->userinfo['编号']==$sellname){
				$str="<a href='__URL__/detailview:__XPATH__/id/{$id}'>查看</a>|等待后台审核";
			}else if($this->userinfo['编号']==$buyname){
				$str="<a href='__URL__/detailview:__XPATH__/id/{$id}'>查看</a>|<a href='__URL__/updateimg:__XPATH__/id/{$id}'>提交凭据</a>";
			}
		}else{
			if($this->userinfo['编号']==$sellname || $this->userinfo['编号']==$buyname){
				$str="<a href='__URL__/detailview:__XPATH__/id/{$id}'>查看</a>";
			}
		}
		return $str;
	}
	public function detailview(fun_gold $fun_gold){
		$buyinfo=M($fun_gold->name."购买")->where(array("id"=>I("get.id/d")))->find();
		if(!$buyinfo){
			$this->error(L("未找到出售挂单"));
		}
		$sellinfo=M($fun_gold->name."挂单")->where(array("id"=>$buyinfo['pid']))->find();
		$this->assign("buyinfo",$buyinfo);
		$this->assign("sellinfo",$sellinfo);
		$this->assign("buyInput",explode(",",$fun_gold->buyInput));
		$this->assign("epbuyInput",explode(",",$fun_gold->epbuyInput));
		$this->display();
	}
	public function addrem(fun_gold $fun_gold){
		
		$buyinfo=M($fun_gold->name."购买")->where(array("id"=>I("get.id/d"),"编号"=>$this->userinfo['编号']))->find();
		if(!$buyinfo){
			$this->error(L("未找到出售挂单"));
		}
		$sellinfo=M($fun_gold->name."挂单")->where(array("id"=>$buyinfo['pid']))->find();
		$this->assign("user",$this->userinfo);
		$this->assign("buyinfo",$buyinfo);
		$this->assign("sellinfo",$sellinfo);
		$this->assign("buyInput",explode(",",$fun_gold->buyInput));
		$this->assign("epbuyInput",explode(",",$fun_gold->epbuyInput));
		$this->display();
	}
	public function remsave(fun_gold $fun_gold){
		$buyinfo=M($fun_gold->name."购买")->where(array("id"=>I("post.id/d"),"编号"=>$this->userinfo['编号']))->find();
		$result=$fun_gold->checkbank(I("post."),explode(',',$fun_gold->buyInput));
		if($result){
			$this->error($result);
		}
		if(I("post.remtime/s")==""){
			$this->error(L("请填写汇款日期"));
		}
		$_POST['remtime']=strtotime(I("post.remtime/s"));
		if(I("post.money/f")!=($buyinfo['数量']*$buyinfo['单价'])){
			$this->error(L("数据错误"));
		}
		M()->startTrans();
		$result=$fun_gold->givebank($this->userinfo,I("post."));
		if(gettype($result)=='string'){
			M()->rollback();
			$this->error($result);
		}
		M()->commit();
		$this->userobj->adduserlog($this->userinfo,get_client_ip(),$fun_gold->name."汇款".I("post.money/f"));
		$this->success(L("汇款完成"),"__URL__/detail:".$fun_gold->objPath());
	}
	public function accokrem(fun_gold $fun_gold){
		$buyinfo=M($fun_gold->name."购买")->where(array("id"=>I("get.id/d")))->find();
		if(!$buyinfo){
			$this->error(L("错误数据"));
		}
		M()->startTrans();
		$result=$fun_gold->accokTrad($this->userinfo,$buyinfo);
		if(gettype($result)=='string'){
			M()->rollback();
			$this->error($result);
		}
		$this->userobj->adduserlog($this->userinfo,get_client_ip(),"审核卖出".$fun_gold->name."的汇款");
		M()->commit();
		$this->success(L("审核完成"),"__URL__/detail:".$fun_gold->objPath());
	}
	public function cancelBuy(fun_gold $fun_gold){
		$buyinfo=M($fun_gold->name."购买")->where(array("id"=>I("get.id/d")))->find();
		if(!$buyinfo){
			$this->error(L("错误数据"));
		}
		M()->startTrans();
		$result=$fun_gold->cancelBuy($buyinfo['id']);
		if(gettype($result)=='string'){
			M()->rollback();
			$this->error($result);
		}
		M()->commit();
		$this->userobj->adduserlog($this->userinfo,get_client_ip(),"取消买入".$fun_gold->name."订单");
		$this->success(L("订单已取消"),"__URL__/detail:__XPATH__");
	}
	public function delhide(fun_gold $fun_gold){
		$buyinfo=M($fun_gold->name."购买")->where(array("id"=>I("get.id/d")))->find();
		if(!$buyinfo){
			$this->error(L("错误数据"));
		}
		if($buyinfo['编号']==$this->userinfo['编号']){
			$buyinfo['买家删除']=1;
			$show="买家删除";
		}else{
			$buyinfo['卖家删除']=1;$show="卖家删除";
		}
		M()->startTrans();
		M($fun_gold->name."购买")->save($buyinfo);
		$this->userobj->adduserlog($this->userinfo,get_client_ip(),$show.$fun_gold->name."订单");
		M()->commit();
		$this->success(L("操作完成"));
	}
	public function apply(fun_gold $fun_gold){
		$buyinfo=M($fun_gold->name."购买")->where(array("id"=>I("get.id/d")))->find();
		if(!$buyinfo){
			$this->error(L("错误数据"));
		}
		$sellinfo=M($fun_gold->name."挂单")->where(array("id"=>$buyinfo['pid']))->find();
		$this->assign("buyinfo",$buyinfo);
		$this->assign("sellinfo",$sellinfo);
		$this->display();
	}
	public function applysave(fun_gold $fun_gold){
		if(I("post.content/s")==""){
			$this->error(L("请提交仲裁申请说明"));
		}
		$buyinfo=M($fun_gold->name."购买")->where(array("id"=>I("post.id/d")))->find();
		if(!$buyinfo){
			$this->error(L("未找到购买记录"));
		}
		$buyinfo['卖家申请说明']=I("post.content/s");
		$buyinfo['状态']="仲裁";
		M()->startTrans();
		M($fun_gold->name."购买")->save($buyinfo);
		$this->userobj->adduserlog($this->userinfo,get_client_ip(),"申请仲裁".$fun_gold->name."订单");
		M()->commit();
		$this->success(L("订单已申请仲裁"),"__URL__/detail:__XPATH__");
	}
	public function updateimg(fun_gold $fun_gold){
		$buyinfo=M($fun_gold->name."购买")->where(array("id"=>I("get.id/d")))->find();
		if(!$buyinfo){
			$this->error(L("错误数据"));
		}
		$sellinfo=M($fun_gold->name."挂单")->where(array("id"=>$buyinfo['pid']))->find();
		$this->assign("buyinfo",$buyinfo);
		$this->assign("sellinfo",$sellinfo);
		$this->display();
	}
	public function updateimgsave(fun_gold $fun_gold){
		$buyinfo=M($fun_gold->name."购买")->where(array("id"=>I("post.id/d")))->find();
		if(!$buyinfo){
			$this->error(L("错误数据"));
		}
		if(I("post.img/s")!=""){
		import("ORG.Util.UploadFile");
        $upload						= new UploadFile();                         // 实例化上传类
        $upload->maxSize			= 838860;                                   // 默认允许上传的附件大小(800K)
        $upload->allowExts			= array('jpg', 'gif', 'png', 'jpeg');								// 默认允许上传的附件类型
        $upload->thumb				= true;                                     // 是否对图片进行缩略处理
        $upload->thumbPrefix        = 't_';										// 默认缩略图前缀
        $upload->thumbRemoveOrigin  = true;										// 默认缩略图片并删除原图
        $upload->thumbMaxWidth      = '600';									// 默认缩略图的最大宽度
        $upload->thumbMaxHeight     = '600';									// 默认缩略图的最大高度
        $upload->savePath           = ROOT_PATH."Upload/".date('Ym/d/');
		
        if(!file_exists_case($upload->savePath)) 
        {
            mk_dir($upload->savePath);  //如果目录不存在自动创建目录
        }
        if(!$upload->upload()) 
        {
        	$this->error($upload->getErrorMsg());
        }else{
        	// 上传成功获取上传文件信息
            $info		= $upload->getUploadFileInfo();
			// ROOT_PATH 替换为 / ;
			$file_url	= str_replace( ROOT_PATH , "/" , $info[0]['thumbfile'] );
			//执行文件同步
			import("COM.Util.FtpClient");
			$ftp = new FtpClient('127.0.0.1','ftpuser','ftppassword',true,21,10);
			$ftp->upload( $info[0]['thumbfile'] , $file_url );
        }
        M()->startTrans();
        M($fun_gold->name."购买")->where(array("id"=>I("post.id/d")))->save(array("买家凭据"=>$file_url));
        $this->userobj->adduserlog($this->userinfo,get_client_ip(),"上传".$fun_gold->name."订单支付凭据");
        M()->commit();
        }
		$this->success(L("订单已上传凭据"),"__URL__/detail:__XPATH__");
	}
}
?>