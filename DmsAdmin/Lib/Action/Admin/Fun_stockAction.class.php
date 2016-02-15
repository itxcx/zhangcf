<?php
defined('APP_NAME') || die('不要非法操作哦!');
class Fun_stockAction extends CommonAction
{
	public $fun_stock;
	//股票设置
	public function config(fun_stock $fun_stock)
	{
		$this->assign('stockname',$fun_stock->byname);//
		//公司发行量
		$this->assign('stockLimit',$fun_stock->getatt('stockLimit'));
		$stockAllinfo=M($fun_stock->name."发行")->where(array("日期"=>array("lt",systemTime())))->find();
		if(!$stockAllinfo){
			$stockAllinfo=array(
				"发行价"=>$fun_stock->stockStartPrice,
				"发行量"=>0,
				"余量"=>0,
				"发行总量"=>0,
				"认购总量"=>0,
				"回购总量"=>0
			);
		}
		$this->assign('stockAllinfo',$stockAllinfo);
		$this->assign('fun_stock',$fun_stock);
		//会员买卖设置	
		M()->startTrans();
		if(!CONFIG($fun_stock->name.":StockSplit")){
			$fun_stock->setatt('StockSplit',2);
		}
		$this->assign('StockSplit',$fun_stock->getatt('StockSplit'));
		if(!CONFIG($fun_stock->name.":stockHighPrice")){
			$fun_stock->setatt('stockHighPrice',0);
		}
		if(!CONFIG($fun_stock->name.":stockLowPrice")){
			$fun_stock->setatt('stockLowPrice',0);
		}
		if(!CONFIG($fun_stock->name.":stockDrop")){
			$fun_stock->setatt('stockDrop',0);
		}
		if(!CONFIG($fun_stock->name.":stockRise")){
			$fun_stock->setatt('stockRise',0);
		}
		if(!CONFIG($fun_stock->name.":stockSec")){
			$fun_stock->setatt('stockSec',0);
		}
		M()->commit();
		
		$this->assign('stockMinint',$fun_stock->getatt('stockMinint'));//买卖整数倍
		$this->assign('stockInputPrice',$fun_stock->getatt('stockInputPrice'));//买卖是否录入价格
		$this->assign('stockHighPrice',$fun_stock->getatt('stockHighPrice'));//买卖最高价格
		$this->assign('stockLowPrice',$fun_stock->getatt('stockLowPrice'));//买卖最低价格
		$this->assign('stockDrop',$fun_stock->getatt('stockDrop'));//买卖最高跌幅
		$this->assign('stockRise',$fun_stock->getatt('stockRise'));//买卖最高涨幅
		
		$this->assign('stockNowPrice',$fun_stock->getPrice());//股票当前价格			
		$this->assign('stockClose',$fun_stock->getatt('stockClose'));//股票休市
		$this->assign('stockBuybutton',$fun_stock->getatt('stockBuybutton'));//买入按钮开启
		$this->assign('stockSellbutton',$fun_stock->getatt('stockSellbutton'));//卖出按钮开启
		$this->assign('stockBuycancel',$fun_stock->getatt('stockBuycancel'));//买入撤销按钮开启
		$this->assign('stockSellcancel',$fun_stock->getatt('stockSellcancel'));//卖出撤销按钮开启
		$this->assign('stockSec',$fun_stock->getatt('stockSec'));//会员提交间隔
			
		//获取会员级别
		$levels=X("levels@");
		//持有量封顶值
		$maxary=array();
		$stockMaxary=$fun_stock->getatt('stockMax');
		foreach($levels->getcon("con",array("name"=>"","lv"=>0)) as $lev)
		{
			$maxary[$lev['lv']]['name'] = $lev['name'] ;
			$maxary[$lev['lv']]['max'] = isset($stockMaxary[$lev['lv']])?$stockMaxary[$lev['lv']]:0;	
		}
		$this->assign('maxary',$maxary);
		//自动挂单
		$this->assign('sellauto',$fun_stock->getatt('sellauto'));
		$stockAutosell=$fun_stock->getatt('stockAutosell');
		$autoSellary=array();
		foreach($levels->getcon("con",array("name"=>"","lv"=>0)) as $lev)
		{
			$autoSellary[$lev['lv']]['name'] = $lev['name'] ;
			$autoSellary[$lev['lv']]['max'] = isset($stockAutosell[$lev['lv']])?$stockAutosell[$lev['lv']]:0;	
		}
		$this->assign('autoSellary',$autoSellary);
		//股票拆分
		$this->assign('splitStart',$fun_stock->splitStart);
		$this->assign('buyfComStock',$fun_stock->getatt('buyfComStock'));//购买公司发行
		$this->display();
	}
	public function issue(fun_stock $fun_stock){
		M()->startTrans();
		//是否改变发行量的限制
		if(I("post.stockLimit/s")=='true' && I("post.stockNum/d")<=0){
			$this->error("请确认发行量为大于0的整数");
		}
		if(I("post.stockLimit/s")=='true'){
			$stockLimit=true;
		}else{
			$stockLimit=false;
		}
		if($fun_stock->stockLimit != $stockLimit){
			$fun_stock->setatt('stockLimit',$stockLimit);
		}
		//找出最新的发行记录
		$stockAllinfo=M($fun_stock->name."发行")->where(array("日期"=>array("lt",systemTime())))->find();
		if($stockLimit==false){
			$_POST['stockNum']=0;
			$messge=$fun_stock->name."发行出售量已设置为不受限制";
		}
		//发行本次的记录
		if($stockAllinfo){
			if(I("post.cleartrade/s")=="true"){
				$stockAllinfo['认购总量']=0;
				$stockAllinfo['认购金额']=0;
				$stockAllinfo['成交量']=0;
				$stockAllinfo['成交金额']=0;
			}
			$issuedata=array(
				"发行量"=>I("post.stockNum/d"),
				"发行价"=>I("post.stockPrice/f"),
				"余量"=>$stockAllinfo['余量']+I("post.stockNum/d"),
				"发行总量"=>$stockAllinfo['发行总量']+I("post.stockNum/d"),
				"回购总量"=>$stockAllinfo['回购总量'],
				
				"认购总量"=>$stockAllinfo['认购总量'],
				"认购金额"=>$stockAllinfo['认购金额'],
				"成交量"=>$stockAllinfo['成交量'],
				"成交金额"=>$stockAllinfo['成交金额'],
				
				"日期"=>systemTime()
			);
			if(I("post.stockNum/d")!=0)
				$messge=$fun_stock->name."追加发行量为:".I("post.stockNum/d");
		}else{
			$issuedata=array(
				"发行量"=>I("post.stockNum/d"),
				"发行价"=>I("post.stockPrice/f"),
				"余量"=>I("post.stockNum/d"),
				"发行总量"=>I("post.stockNum/d"),
				"回购总量"=>0,
				
				"认购总量"=>0,
				"认购金额"=>0,
				"成交量"=>0,
				"成交金额"=>0,
				
				"日期"=>systemTime()
			);
			if(I("post.stockNum/d")!=0)
				$messge=$fun_stock->name."首次发行量为:".I("post.stockNum/d");
		}
		M($fun_stock->name."发行")->add($issuedata);
		$this->saveAdminLog('',I("post."),"股票发行","股票发行设置");
		M()->commit();
		$this->success($messge);
	}
	//配置信息保存
	public function configSave($fun_stock)
	{
		M()->startTrans();
		$fun_stock->setatt('stockMinint',I("post.stockMinint/d"));
		if(I("post.stockInputPrice/s")=='true')
		{
		  	$fun_stock->setatt('stockInputPrice',true);
		}else{
		 	$fun_stock->setatt('stockInputPrice',false);
		}
		
		$fun_stock->setatt('stockLowPrice',I("post.stockLowPrice/f"));
		$fun_stock->setatt('stockHighPrice',I("post.stockHighPrice/f"));
		$fun_stock->setatt('stockDrop',I("post.stockDrop/f"));
	    $fun_stock->setatt('stockRise',I("post.stockRise/f"));
	    $fun_stock->setatt('stockSec',I("post.stockSec/d"));
	    if(I("post.stockClose/s")=='true')
		{
			$fun_stock->setatt('stockClose',true);
		}else{
			$fun_stock->setatt('stockClose',false);
		}
		if(!$fun_stock->buyDisp){
			if(I("post.stockBuybutton/s")=='true')
			{
				$fun_stock->setatt('stockBuybutton',true);
			}else{
				$fun_stock->setatt('stockBuybutton',false);
			}
			if(I("post.stockBuycancel/s")=='true')
			{
				$fun_stock->setatt('stockBuycancel',true);
			}else{
				$fun_stock->setatt('stockBuycancel',false);
			}
		}
		if(!$fun_stock->sellDisp){
			if(I("post.stockSellbutton/s")=='true')
			{
				$fun_stock->setatt('stockSellbutton',true);
			}else{
				$fun_stock->setatt('stockSellbutton',false);
			}
			if(I("post.stockSellcancel/s")=='true')
			{
				$fun_stock->setatt('stockSellcancel',true);
			}else{
				$fun_stock->setatt('stockSellcancel',false);
			}
		}
	    $fun_stock->setatt('StockSplit',I("post.StockSplit/f"));
	    if(I("post.buyfComStock/s")=='true')
		{
		  	$fun_stock->setatt('buyfComStock',true);
		}else{
		 	$fun_stock->setatt('buyfComStock',false);
		}
		//获取会员级别,存放封顶值
		$data=I("post.stockMax/a");$stockMax=array();
		foreach(I("post.stockMax/a") as $lv=>$lvval){
			$stockMax[$lv]=I("data.".$lv."/f",'0','',$data);
		}
		$fun_stock->setatt('stockMax',$stockMax);
		//自动卖出
		if(I("post.sellauto/s")=="true"){
			$fun_stock->setatt('sellauto',true);
		}else{
			$fun_stock->setatt('sellauto',false);
		}
		$aurodata=I("post.autoSell/a");
		$autoSell=array();
		if(count($aurodata)>0){
			foreach(I("post.autoSell/a") as $lv=>$lvval){
				$autoSell[$lv]=I("data.".$lv."/f",'0','',$aurodata);
			}
		}
		$fun_stock->setatt('stockAutosell',$autoSell);
		$this->saveAdminLog('',I("post."),"股票参数","股票参数设置");
		M()->commit();
		$this->success('设置完成');
	}
	//股票列表，显示有股票的会员
	public function index($fun_stock)
	{
		$wherestr="";$where=array();
		if(I("get.userid/s")!=""){
        	$where['编号']=I("get.userid/s");
        	$wherestr="/userid/".I("get.userid/s");
        }
		$setButton=array(    
			$fun_stock->byname.'充值'=>array("class"=>"add","href"=>__APP__."Admin/Fun_stock/addin:__XPATH__{$wherestr}","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"260"),
        );
        
        $list=new TableListAction($fun_stock->name.'明细');
        $list->setButton = $setButton;
        $list->where($where);
		$list->order("时间 desc");
        $list->addshow("时间",array("row"=>"[时间]","css"=>"width:25px;","searchMode"=>"date","format"=>"time","searchPosition"=>"top","order"=>"时间"));
        $list->addshow("编号",array("row"=>"[编号]","css"=>"width:15px;","searchMode"=>"text","excelMode"=>"text","searchPosition"=>"top"));
		$list->addshow("类型",array("row"=>"[类型]","css"=>"width:10px;","searchMode"=>"text","searchPosition"=>"top"));
        $list->addshow("价格",array("row"=>"[价格]","css"=>"width:10px;","excelMode"=>"num","searchMode"=>"num"));
        $list->addShow("数量",array("row"=>array(array($this,'myfun'),"[数量]"),"css"=>"width:10px;","searchMode"=>"num","excelMode"=>"num"));
		$list->addShow("余量",array("row"=>"[余量]","css"=>"width:10px;","searchMode"=>"num","excelMode"=>"num"));
		$list->addshow("备注",array("row"=>"[备注]","excelMode"=>"text"));
        $this->assign('list',$list->getHtml());
        $this->display();
	}
	function myfun($str){
        if($str > 0){
            return '<font color="green">'.$str.'</font>';
        }else{
            return '<font color="red">'.$str.'</font>';
        }
    }
	//股票充值页面
	public function addin($fun_stock)
	{
		$this->assign('userid',I("get.userid/s"));
        $this->display();
	}
	//股票充值保存
	public function savein($fun_stock)
	{
		$user_model=M("会员");
		$data=array();
		$data['编号']=I("post.编号/s");
		$data['num']=I("post.num/d");
		if($data['num']=='' || $data['num']<=0)  $this->error('请输入充值数量');
		M()->startTrans();
		$user=$user_model->where(array('编号'=>$data['编号']))->find();
		if($user){
			$fun_stock->setrecord($data['编号'],$fun_stock->getPrice(),$data['num'],"后台充值".I("post.memo/s"),'后台充值');
			$this->saveAdminLog('',I("post."),"股票充值","股票充值".I("post.编号/s"));
			M()->commit();
			$this->success("充值".$fun_stock->name.'成功');
		}else{
			M()->rollback();
			$this->error("会员".$data['编号']."不存在");
		}
	}
	//股票交易
	public function trade($fun_stock)
	{
        $list=new TableListAction($fun_stock->name.'交易');
        //$list->setButton = $setButton;
		$list->order("成交时间 desc");
        $list->addshow("成交时间",array("row"=>"[成交时间]","css"=>"width:25px;","searchMode"=>"date","format"=>"time","order"=>"时间"));
		$list->addshow("买单ID",array("row"=>"[买入ID]","searchMode"=>"text","excelMode"=>"text","searchPosition"=>"top","css"=>"width:10px;"));
        $list->addshow("买入编号",array("row"=>"[买入编号]","searchMode"=>"text","excelMode"=>"text","searchPosition"=>"top","css"=>"width:25px;"));
        $list->addShow("交易数量",array("row"=>"[交易量]","searchMode"=>"num","excelMode"=>"num","order"=>"交易量","css"=>"width:25px;"));
        $list->addshow("交易价格",array("row"=>array(array(&$this,'formatPrice'),"[交易价]",$fun_stock->decimalLen),"css"=>"width:25px;","order"=>"交易价","excelMode"=>"num","searchMode"=>"num"));
		$list->addShow("交易额度",array("row"=>array(array(&$this,"stockAllprice"),"[交易价]","[交易量]",$fun_stock->decimalLen),"css"=>"width:25px;"));
		$list->addshow("卖单ID",array("row"=>"[卖出ID]","searchMode"=>"text","excelMode"=>"text","searchPosition"=>"top","css"=>"width:10px;"));
        $list->addshow("卖出编号",array("row"=>"[卖出编号]","css"=>"width:25px;","excelMode"=>"text","searchMode"=>"text","searchPosition"=>"top"));
        $list->addshow("备注",array("row"=>"[备注]","excelMode"=>"text","css"=>"width:50px;"));
        $this->assign('list',$list->getHtml());
        $this->display();
	}

	//股票市场
	public function shop($fun_stock)
	{
		$this->fun_stock=$fun_stock;
		// 底部操作按钮显示定义
		$setButton=array(
			/*'添加交易'=>array("class"=>"add","href"=>__APP__."Admin/Fun_stock/add:__XPATH__","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"260"),
			'编辑'=>array("class"=>"edit","href"=>__APP__."Admin/Fun_stock/edit:__XPATH__/id/{tl_id}","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"260"),*/
			'撤销'=>array("class"=>"delete","href"=>__APP__."Admin/Fun_stock/cancelall:__XPATH__/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要撤销正在交易的买卖订单吗？"),
			'撤销全部'=>array("class"=>"delete","href"=>__APP__."Admin/Fun_stock/cancelall:__XPATH__","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要撤销所有正在交易的买卖订单吗？"),
        );
        $list=new TableListAction($fun_stock->name.'市场');
        $list->setButton = $setButton;
		$list->order("挂单时间 desc");
		$list->addShow("挂单ID",array("row"=>"[id]"));
		$list->addshow("挂单时间",array("row"=>"[挂单时间]","format"=>"time","searchMode"=>"text","excelMode"=>"text","searchPosition"=>"top"));
        $list->addshow("会员编号",array("row"=>"[编号]","searchMode"=>"text","excelMode"=>"text","searchPosition"=>"top"));
        $list->addShow("挂单总量",array("row"=>"[挂单总量]","searchMode"=>"num","excelMode"=>"num"));
		$list->addShow("已成交量",array("row"=>"[成交量]","searchMode"=>"num","excelMode"=>"num"));
		$list->addShow("剩余数量",array("row"=>"[剩余量]","searchMode"=>"num","excelMode"=>"num"));
		$list->addShow("挂单价格",array("row"=>array(array(&$this,'formatPrice'),"[挂单价]",$fun_stock->decimalLen),"searchMode"=>"num","excelMode"=>"num"));
		$list->addShow("挂单总价值",array("row"=>array(array(&$this,"stockAllprice"),"[挂单价]","[挂单总量]",$fun_stock->decimalLen)));
		$list->addShow("类型",array("row"=>"[类型]","searchMode"=>"text","searchPosition"=>"top",'searchRow'=>'[类型]',"searchSelect"=>array('卖出'=>'卖出','买入'=>'买入')));
		$list->addShow("状态",array("row"=>"[状态]","searchMode"=>"text","searchPosition"=>"top",'searchRow'=>'[状态]',"searchSelect"=>array('挂单中'=>'挂单中','已成交'=>'已成交','已撤销'=>'已撤销')));
		$list->addShow("交易信息",array("row"=>array(array(&$this,"tradeInfo"),"[id]","[tradeinfo]")));
        $this->assign('list',$list->getHtml()); // 分配到模板
        $this->display();
	}
	//撤销所有挂单
	public function cancelall(fun_stock $fun_stock)
	{
		$where=array();
		$where['剩余量']=array('gt',0);
		$where['状态']=array('eq','挂单中');
		if(I("get.id/s")!=''){
			$where['id']=array("in",I("get.id/s"));
		}
		M()->startTrans();
		//统计是否有需要撤销的订单
		$allnum=M($fun_stock->name."市场")->where($where)->count('id');
		if(!$allnum){
			$this->error('没有符合的挂单');
		}
		//撤销所有挂单
		$fun_stock->cancelall($where);
		$this->saveAdminLog('','',"挂单撤销","挂单撤销");
		M()->commit();
		$this->success('挂单撤销成功');
	}
	//股票拆骨
	public function stockSplit(fun_stock $fun_stock)
	{
		$nowprice=$fun_stock->getPrice();
		$SplitNum=$fun_stock->SplitNum;
		$splitAfter=$this->formatPrice($nowprice/$SplitNum,$fun_stock->decimalLen);
		$this->assign('Stockprice',$nowprice);
		$this->assign('StockSplit',$SplitNum);
		$this->assign('splitAfter',$splitAfter);
		$this->display();
	}
	//拆分输入管理员密码
	public function intwp()
	{
		$this->assign("splitnum",I("request.splitnum/d"));
		$this->display();
	}
	//股票拆股
	public function splitSave(fun_stock $fun_stock)
	{
		$repwd=I("request.repwd/s");
		if($repwd=='') $this->error("请填写密码");
		$where['id'] = $_SESSION[C('RBAC_ADMIN_AUTH_KEY')];
		M()->startTrans();
        $result=M()->table("admin")->where($where)->field("password")->find();
        if($result['password']!=md100($repwd)){
            $this->error("管理员密码错误");
        }
        //拆分倍数
		$num=$fun_stock->SplitNum;
		if($num=="" || $num<=0 || $num==1){
			M()->rollback();
		  	$this->error("拆分倍数不合法，请重新设置拆分倍数");
		}
		//撤销所有的挂单
		$fun_stock->cancelall(array("类型"=>"卖出"));
		//拆分股票
		$fun_stock->splitstock($num,$fun_stock->getPrice());
		$this->saveAdminLog('','',"股票拆分","股票拆分");
		//提交事物
		M()->commit();
		$this->success('拆分完成');
	}
	//一键挂单显示
	public function stockComSell($fun_stock)
	{
		$this->assign('stockname',$fun_stock->name);
		$this->assign('stockNowPrice',$fun_stock->stockPrice());//股票当前价格
		//$this->assign('stockHighPrice',$fun_stock->getatt('stockHighPrice'));//买卖最高价格
		//$this->assign('stockLowPrice',$fun_stock->getatt('stockLowPrice'));//买卖最低价格
		//获取会员级别
		$level=array();
		$levels=X("levels@");

		foreach($levels->getcon("con",array("name"=>"","lv"=>"")) as $lvconf)
		{
			$level[$lvconf['lv']]['name'] = $lvconf['name'] ;
			$level[$lvconf['lv']]['max'] = $fun_stock->getatt('stockfd'.$lvconf['lv']);
		}
      
		$this->assign('levels',$level);
		$this->assign('formula',$fun_stock->getatt('formula'));
		$this->display();
	}
	//一键挂单操作
	public function stockComSellSave($fun_stock)
	{
		$u_model=M("会员");
		$uname="会员";
		$account=$fun_stock->name;
		if($fun_stock===false){
		   $this->error(L('参数错误'));
		}
		/*
		if($_POST['minnum']!='' && (!is_numeric($_POST['minnum'])  || $_POST['minnum']<=0)){
			$this->error(L('填写的最低出售数量不合法'));
		}
		if($_POST['maxnum']!='' && (!is_numeric($_POST['maxnum'])  || $_POST['maxnum']<=0)){
			$this->error(L('填写的最高出售数量不合法'));
		}*/
		if(I("post.formula/d")<=0){
			$this->error(L('填写的公式分母不合法'));
		}
		if(I("post.sellprice/f")=="" || I("post.sellprice/f")<=0){
			$this->error(L('填写的出售金额不合法'));
		}
		//出售价格
		$price=$fun_stock->stockPrice();//默认当前价格
		if(I("post.sellprice/f")>0) $price=I("post.sellprice/f");
		if($price<=0){
			$this->error(L('系统暂无当前价格'));
		}
		//初始化查询条件
		$where=$account.">0";
		//先更新参数
		M()->startTrans();
		$fun_stock->setatt('formula',I("post.formula/d"));
		//获取会员级别
		$level=array();
		$levels=X("levels@");
		$maxary=array();
			foreach($levels->getcon("con",array("name"=>"","lv"=>"")) as $lvconf)
			{
				$fun_stock->setatt('stockfd'.$lvconf['lv'],I("post.stockfd".$lvconf['lv']."/d"));
				//同时根据级别查找条件
				if($lvconf['lv']==1) $where.=" and (";
				else $where.=" or ";
				$where.="(".$uname."级别=".$lvconf['lv']." and ".$account.">".I("post.stockfd".$lvconf['lv']."/d").")";
				//每一级别的限制数量
				$maxary[$lvconf['lv']]=I("post.stockfd".$lvconf['lv']."/d");
 			}
   
		$where.=")";
		//dump($where);
		//dump($maxary);
		/*
		//查询符合条件的会员
		if($_POST['minnum']>0)
		{
			$where[$fun_stock->name]=array("egt",intval($_POST['minnum']));
		}else{
			$where[$fun_stock->name]=array("gt",0);
		}
		*/
		
		$users=$u_model->where($where)->field('编号,'.$account.",".$uname."级别")->select();

		if($users){
			foreach($users as $user){
				$num=floor(($user[$account]-$maxary[$user[$uname."级别"]])/I("post.formula/d"));
				//扣除交易货币流程
				$fun_stock-> setrecord($user['编号'],$price,$num,"公司一键发布".$fun_stock->name."卖出订单",'卖出');
				//创建挂单记录
				$fun_stock->setcompany($user['编号'],$price,$num,'卖出');
			}
			M()->commit();
			$this->success(L("出售完成"));
		}else{
			M()->rollback();
			$this->error(L('没有符合条件的用户'));
		}


	}
	
	//股票走势
	public function stockTrend($fun_stock)
	{
        $list=new TableListAction($fun_stock->name.'走势');
        $setButton=array(                 // 底部操作按钮显示定义
				'编辑'=>array("class"=>"edit"  ,"href"=>__APP__."Admin/Fun_stock/stockTrendedit:__XPATH__/id/{tl_id}","target"=>"dialog","mask"=>"true","width"=>"350","height"=>"220","title"=>"编辑走势"),
				'删除'=>array("class"=>"delete","href"=>__APP__."Admin/Fun_stock/stockTrenddelete:__XPATH__/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要删除该数据吗？"),
        );
		//$list->setButton = $setButton;
		$list->order("日期 desc");
        $list->addshow("时间",array("row"=>"[日期]","searchMode"=>"date","format"=>"date","order"=>"[日期]","searchMode"=>"date","searchPosition"=>"top"));
        $list->addshow("最后成交价格",array("row"=>"[价格]"));
        $list->addShow("成交量",array("row"=>array(array(&$this,'formatNum'),"[成交量]")));
        $list->addshow("认购量",array("row"=>array(array(&$this,'formatNum'),"[认购量]")));
        $list->addshow("成交金额",array("row"=>"[成交金额]"));
        $this->assign('list',$list->getHtml());
        $this->display();
	}
	//限制交易价格小数位
	public function formatPrice($price,$decimalLen){;
		return number_format($price,$decimalLen,'.','');
	}
	//限制股票显示
	public function formatNum($price){
		return intval($price);
	}
	public function tradenum($num,$type){
        if($type==1) return "-".$num;
		if($type==2) return "+".$num;
	}
	
	
	public function tradedetail($fun_stock)
	{
		if(I("request.id/d")<=0 || $fun_stock==false){
		   $this->error("参数错误");
		}
		$tradeinfo=M($fun_stock->name."市场")->where(array('id'=>I("request.id/d")))->getField('tradeinfo');
		$this->assign('info',unserialize($tradeinfo));
		//小数位数
		$decimalLen=$fun_stock->getatt('decimalLen');
		$this->assign('decimalLen',$decimalLen);
		$this->display();

	}
	public function tradeInfo($id,$tradeinfo)
	{
		$tradeinfo=unserialize($tradeinfo);
		if(empty($tradeinfo)){
			return "未售";
		}
	   	return "<a href='__URL__/tradedetail:__XPATH__/id/".$id."' target='dialog' mask='true'>点击查看</a>";
	}
	//计算股票价格
	public function stockAllprice($price,$num,$decimalLen)
	{
         return $this->formatPrice($price*$num,$decimalLen);
	}
	
	//股票增加
	public function add($fun_stock)
	{
		$this->assign('type',$fun_stock->type);
        $this->assign('name',$fun_stock->name);
		$this->assign('username',"会员编号");
		$this->assign('stocknumName',$fun_stock->name."数量");
		$this->assign('stockprizeName',$fun_stock->name."价格");
		$this->assign('stockprize',$fun_stock->stockPrice());
        $this->display();
	}
	public function save($fun_stock)
	{
		$modle=M("会员".'_'.$fun_stock->name."市场");
		$data=$modle->create();
		if(!$data) $this->error('获取数据失败');
		M()->startTrans();
		$user_model=M("会员");
		$user=$user_model->where("编号='".$data['编号']."'")->find();
		if($user){
           if($user[$fun_stock->name]<$data['num']){
           	   M()->rollback();
		     $this->error('该'."会员".'的'.$fun_stock->name.'数量不足'.$data['num']);
		   }else{
		     $data['addtime']=systemTime();
			 $data['num1']=$data['num'];
			 $data['tradeinfo']=$fun_stock->encode();
		      $rsadd=$modle->add($data);
		        if($rsadd){
					$fun_stock->setrecord1($data['编号'],$data['price'],$data['num'],$data['type']);
					M()->commit();
		            $this->success("添加".$fun_stock->name.'成功');
	            }else{
	            	M()->rollback();
			          $this->error("添加".$fun_stock->name.'失败');
		           }
		   }
		}else{
			M()->rollback();
			$this->error("会员".$data['编号']."不存在");
		}

	}
	public function edit($fun_stock)
	{
        $this->assign('name',$fun_stock->name);
        $this->display();
	}
	public function delete($fun_stock)
	{
		$id=I("request.id/d");
		M()->startTrans();
		if(M("会员".'_'.$fun_stock->name)->where(array("id"=>$id))->delete()){
			M()->commit();
			$this->success('删除成功');
		}else{
			M()->rollback();
		    $this->error('删除失败');
		}
	}
	

	

	public function stockTrendedit($fun_stock)
	{
		$vo=M($fun_stock->name.'走势')->where(array("id"=>I("request.id/d")))->find();
		$this->assign('vo',$vo);
        $this->display();
	}
	public function stockTrenddelete($fun_stock)
	{
		$id=I("request.id/d");
		M()->startTrans();
		if(M("会员".'_'.$fun_stock->name."走势")->where(array("id"=>$id))->delete()){
			M()->commit();
			$this->success('删除成功');
		}else{
			M()->rollback();
		    $this->error('删除失败');
		}
	}
	public function stockTrendsave($fun_stock)
	{
		$id=I("request.id/d");
		$modle=M("会员".'_'.$fun_stock->name."走势");
		$data=$modle->create();
		if(!$data) $this->error('获取数据失败');
		M()->startTrans();
		$rsadd=$modle->where(array("id"=>$id))->save($data);
		 if($rsadd){
			M()->commit();
		    $this->success('成功');
	     }else{
	     	 M()->rollback();
			$this->error('失败');
		 }
	}

	//
	public function stockAnalysis($fun_stock)
	{
		$hyname=$fun_stock->parent()->name;
		$m_user = M($hyname);
		$m_sc = M($fun_stock->name.'市场');
		//持有交易股
		$tradeHave = $m_user->sum($fun_stock->name.'账户');
		//持有托管股
		$trustHave = $m_user->sum($fun_stock->name.'托管');
		//普通股卖出
		$tradeSell = $m_sc->where("type=1 and num>0 and 账户='".$fun_stock->name."账户'")->sum('num');
		//普通股买入
		$tradeBuy  = $m_sc->where("type=2 and num>0 and 账户='".$fun_stock->name."账户'")->sum('num');
		//托管股卖出
		$trustSell = $m_sc->where("type=1 and num>0 and 账户='".$fun_stock->name."托管'")->sum('num');
		//总持有量
		$allHave   = $tradeHave + $trustHave + $tradeSell + $trustSell;
		$this->assign('data',array('allHave'=>$allHave,'tradeHave'=>$tradeHave,'trustHave'=>$trustHave,'tradeSell'=>$tradeSell,'trustSell'=>$trustSell));
		$this->display();
	}
	
}
?>