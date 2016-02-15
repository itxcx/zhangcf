<?php
defined('APP_NAME') || die(L('not_allow'));
class Fun_stockAction extends CommonAction {
	public function index(fun_stock $fun_stock)
	{
		$stockClose=$fun_stock->getatt('stockClose');
		if($stockClose){
			$this->error("交易市场尚未开启，请耐心等待");
		}
		//会员信息
		$userinfo=$this->userinfo;
		//获取当前价格
		$price=$this->formatPrice($fun_stock->getPrice(),$fun_stock);
		$this->assign('price',$price);
		//出售中股票数
		$stockSale=M($fun_stock->name."市场")->where(array("编号"=>$userinfo['编号'],'类型'=>"卖出",'状态'=>"出售中"))->sum("剩余量");
		if(!$stockSale)
			$stockSale=0;
		//账户拥有量
		$stockinfo=M($fun_stock->name)->where(array("userid"=>$_SESSION[C('USER_AUTH_KEY')]))->find();
		$stockTotal=$stockSale+$stockinfo['数量'];
		$this->assign("stockSale",$stockSale);
		$this->assign("stockTotal",$stockTotal);
		$this->assign('tradeName',$fun_stock->tradeBank);//交易账户	
		if($fun_stock->tradeBank!=""){
			$this->assign('trade',$userinfo[$fun_stock->tradeBank]);//交易账户
		}
		$this->assign('decimalLen',$fun_stock->getatt('decimalLen'));//小数位数
		$this->assign('stockBuycancel',$fun_stock->getatt('stockBuycancel'));//买入撤销按钮开启
		$this->assign('stockSalecancel',$fun_stock->getatt('stockSellcancel'));//卖出撤销按钮开启
		
		$this->assign('stockName',$fun_stock->byname);
		//获取滚动图数据
		$list = $this->getxml($fun_stock);
		$this->list = $list;
		//最新的成交数据
		$todaynum=M($fun_stock->name."交易")->where(array("成交时间"=>array("egt",strtotime(date("Y-m-d",systemTime())))))->count(1);
		if($todaynum>1){
			$MAXS=M($fun_stock->name."交易")->where(array("成交时间"=>array("egt",strtotime(date("Y-m-d",systemTime())))))->order("成交时间 asc")->getField("交易价");
			$MAXP=M($fun_stock->name."交易")->where(array("成交时间"=>array("egt",strtotime(date("Y-m-d",systemTime())))))->MAX("交易价");
		}else{
			$MAXP=$MAXS=M($fun_stock->name."交易")->where(array("成交时间"=>array("lt",strtotime(date("Y-m-d",systemTime())))))->order("成交时间 desc")->getField("交易价");
		}
		//之前的数据
		$lastdaynum=M($fun_stock->name."交易")->where(array("成交时间"=>array(array("egt",(strtotime(date("Y-m-d",systemTime()))-86400)),array("lt",strtotime(date("Y-m-d",systemTime()))))))->count(1);
		if($lastdaynum>1){
			$MINE=M($fun_stock->name."交易")->where(array("成交时间"=>array(array("egt",(strtotime(date("Y-m-d",systemTime()))-86400)),array("lt",strtotime(date("Y-m-d",systemTime()))))))->order("成交时间 desc")->getField("交易价");
			$MINP=M($fun_stock->name."交易")->where(array("成交时间"=>array(array("egt",(strtotime(date("Y-m-d",systemTime()))-86400)),array("lt",strtotime(date("Y-m-d",systemTime()))))))->MIN("交易价");
		}else{
			$MINP=$MINE=M($fun_stock->name."交易")->where(array("成交时间"=>array("lt",(strtotime(date("Y-m-d",systemTime()))-86400))))->order("成交时间 desc")->getField("交易价");
		}
		$this->assign("MAXS",$MAXS);
		$this->assign("MAXP",$MAXP);
		$this->assign("MINE",$MINE);
		$this->assign("MINP",$MINP);
		$lists=M($fun_stock->name."市场")->where(array('编号'=>$userinfo['编号'],'状态'=>"挂单中"))->order("挂单时间 desc")->select();
		$this->assign('lists',$lists);
		$this->assign('fun_stock',$fun_stock);
		$this->display();
	}
	//限制交易价格小数位
	public function formatPrice($price,$fun_stock){
		$decimalLen=$fun_stock->getatt('decimalLen');
		return number_format($price,$decimalLen,'.','');
	}
	//滚动图
	public function getxml($fun_stock)
	{
	   	$model = M($fun_stock->name.'走势');
		$num = $model->count();
		$num2 = $num;
		$list = array();
		if($num && $num<=30){
			$list=$model->limit($num)->order("id asc")->field('日期,价格')->select();
			$dtime = $list[$num-1]['日期'];
			for($i=0;$i<(30-$num2);$i++){
				$num++;	
				//补足不够的天数
				$list[$num]['价格'] = $fun_stock->getPrice();
				$list[$num]['日期'] = $dtime+($i+1)*24*3600;
			}
		}else if($num && $num>30){
			$list = $model->limit(30)->order('id desc')->field('日期,价格')->select();
			asort($list);					//倒序
		}else{
			for($i=0;$i<(30-$num2);$i++){
				$list[$num]['价格'] = $fun_stock->getPrice();
				$list[$num]['日期'] = time()+$i*24*3600;
				$num++;	
			}
		}
		$minprice='0.00';$maxprice='0.00';
		//获取最大最小值
		foreach($list as $key=>$pricelist){
			if((string)$pricelist['价格']>(string)$maxprice){
				$maxprice=round($pricelist['价格'],1);
				if((string)$maxprice<=(string)$pricelist['价格']){
					$maxprice+=0.1;
				}
			}
			if($minprice=='0.00'){
				$minprice=round($pricelist['价格'],1);
				if($minprice>=$pricelist['价格']){
					$minprice-=0.1;
				}
			}else{
				if((string)$pricelist['价格']<(string)$minprice){
					$minprice=round($pricelist['价格'],1);
					if((string)$minprice>=(string)$pricelist['价格']){
						$minprice-=0.1;
					}
				}
			}
		}
		$this->assign('minprice',$minprice);
		$this->assign('maxprice',$maxprice);
		$this->assign('addprice',($maxprice-$minprice)/10);
		return $list; 
	}
	//买卖挂单
	public function trade(fun_stock $fun_stock){
		//会员信息
		$userinfo=$this->userinfo;
		//获取当前价格
		$price=$this->formatPrice($fun_stock->getPrice(),$fun_stock);
		$this->assign('price',$price);
		$this->assign('stockName',$fun_stock->byname);
		$this->assign('decimalLen',$fun_stock->getatt('decimalLen'));//小数位数
		$this->assign('stockBuybutton',$fun_stock->getatt('stockBuybutton'));//买入按钮开启
		$this->assign('stockSalebutton',$fun_stock->getatt('stockSellbutton'));//卖出按钮开启
		$this->assign('stockBuycancel',$fun_stock->getatt('stockBuycancel'));//买入撤销按钮开启
		$this->assign('stockSalecancel',$fun_stock->getatt('stockSellcancel'));//卖出撤销按钮开启
		$this->assign('stockInputPrice',$fun_stock->getatt('stockInputPrice'));//买卖是否录入价格
		$LowPrice=$fun_stock->getatt('stockLowPrice');
		//判断当前价格与最小价格 最大价格的关系
		if($fun_stock->getPrice()<$LowPrice){
			$LowPrice=$fun_stock->getPrice();
		}
		if($fun_stock->getatt('stockDrop')>0 && ($fun_stock->getatt('stockDrop')*$fun_stock->getPrice()/100) < $fun_stock->getatt('stockLowPrice')){
			$LowPrice=$fun_stock->getatt('stockDrop')*$fun_stock->getPrice()/100;
		}
		$this->assign('lowprice',$LowPrice);//最低价
		$HighPrice=$fun_stock->getatt('stockHighPrice');
		if($fun_stock->getPrice()<$HighPrice){
			$LowPrice=$fun_stock->getPrice();
		}
		if($fun_stock->getatt('stockRise')>0 && ($fun_stock->getatt('stockRise')*$fun_stock->getPrice()/100) > $fun_stock->getatt('stockLowPrice')){
			$HighPrice=$fun_stock->getatt('stockRise')*$fun_stock->getPrice()/100;
		}
		$this->assign('highprice',$HighPrice);//最高价
		$this->assign('fun_stock',$fun_stock);
		$this->display();
	}
	//判断录入条件
	public function submitJudge($type,$fun_stock){
		$hint='';
		$stockClose=$fun_stock->getatt('stockClose');
		if($stockClose){
			$this->error("交易市场尚未开启，请耐心等待");
		}
		//当前价
		$price=$fun_stock->getPrice();
		
		//提交间隔
		$stockSec=$fun_stock->getatt('stockSec');
		//10秒内只允许提交一次交易委托
		if($stockSec>0){
			$rschk=M($fun_stock->name."市场")->where("编号='".$this->userinfo['编号']."' and  (挂单时间+".$stockSec.")>".systemTime())->field('id')->find();
			if ($rschk['id']){
				$hint.=$stockSec."秒内只允许提交一次交易委托<br>";
			}
		}
		if(I("post.num/d")<=0){
			$hint.=L('数量输入有误')."<br>";
		}
		if(I("post.price/f")<=0){
			$hint.=L('单价输入有误')."<br>";
		}else{
			//如果开启了会员自定义价格
			if($fun_stock->getatt('stockInputPrice')){
				$lowprice=$fun_stock->getatt('stockLowPrice');
				$highprice=$fun_stock->getatt('stockHighPrice');
				$stockDrop=$fun_stock->getatt('stockDrop');
				$stockRise=$fun_stock->getatt('stockRise');
				//先判断涨跌幅
				if ($price>I("post.price/f")){	
					if (($price-$price*$stockDrop/100)>I("post.price/f")){
						$hint.="价格超出了跌的限制"."<br>";
					}
				}else{
					if (($price+$price*$stockRise/100)<I("post.price/f")){
						$hint.="价格超出了涨的限制"."<br>";
					}
				}
				if($price*$stockDrop/100<$lowprice){
					$lowprice=$price*$stockDrop/100;
				}
				if(I("post.price/f")<$lowprice && $lowprice>0){
					$hint.=L('单价不能低于').$lowprice."<br>";
				}
				if($price*$stockRise/100>$highprice){
					$highprice=$price*$stockRise/100;
				}
				if(I("post.price/f")>$highprice && $highprice>0){
					$hint.=L('单价不能高于').$highprice."<br>";
				}
			}else{
				$_POST['price']=$price;//当前价
			}
		}
		//判断密码
		if(I("post.password/s")==''){
			$hint.="请输入交易密码<br>";
		}elseif(md100(I("post.password/s")) !== $this->userinfo['pass2']){
			$hint.="交易密码错误<br>";
		}
		return $hint;
	}
   	//1卖出 2 买入
	public function stock_buy(fun_stock $fun_stock)
	{
		//防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
	    B('XSS');
		M()->startTrans();
		$userinfo=$this->userinfo;
		if(!$fun_stock->getatt('stockBuybutton')){
			$this->error(L("买入交易临时关闭，稍后开启，请耐心等待"));
		}
		//判断录入条件
		$msg=$this->submitJudge('买入',$fun_stock);
		if($msg!=''){
			$this->error($msg);
		}
		//判断封顶，出售中
		$stockSale=M($fun_stock->name."市场")->where(array("编号"=>$userinfo['编号'],'类型'=>'买入','状态'=>'出售中'))->sum("剩余量");
		//账户拥有量
		$stockinfo=M($fun_stock->name)->where(array("userid"=>$_SESSION[C('USER_AUTH_KEY')]))->find();
		$stockTotal=$stockSale+$stockinfo['数量'];
		//会员最多买入
		if(isset($fun_stock->getatt('stockMax')[$userinfo[$fun_stock->parent()->name.'级别']])){
			$maxnum=$fun_stock->getatt('stockMax')[$userinfo[$fun_stock->parent()->name.'级别']];
			$total=$stockTotal+I("post.num/d");
			if($maxnum>0){
				if($stockTotal>=$maxnum){
					$this->error("您买入的".$fun_stock->name."已达到封顶值");
				}elseif($total>$maxnum){
					$cha=$total-$maxnum;
					$this->error("您还能买入".$cha);
				}
			}
		}
		list($price,$num,$money)=$this->sellbuydata($fun_stock);
		$tradeBank=M("货币")->where(array("编号"=>$userinfo['编号']))->getField($fun_stock->tradeBank);
		if($tradeBank<$money){
			  $this->error(L($fun_stock->tradeBank).L('余额不足'));
		}
		// 防止点击多次提交按钮，重复提交
        $checks = M('会员');
        if(!$checks->autoCheckToken(I("post."))){
        	$this->error(L("请不要重复提交"),__URL__."/trade:".__XPATH__);
        }
		//扣款
		bankset($fun_stock->tradeBank,$userinfo['编号'],-$money,L($fun_stock->name).L('买入'),L('购买').$num.L('股'));
		//创建挂单记录
	    $fun_stock->setcompany($userinfo['编号'],$price,$num,'买入');
		M()->commit();
		$this->success(L("完成"));
	}
	//股票出售
    public function stock_sell(fun_stock $fun_stock)
	{
		//防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
	    B('XSS');
		M()->startTrans();
		if(!$fun_stock->getatt('stockSellbutton')){
			$this->error(L('卖出交易临时关闭，稍后开启，请耐心等待'));
		}
		$user=$this->userinfo;
		//判断录入条件
		$msg=$this->submitJudge('卖出',$fun_stock);
		if($msg!=''){
			$this->error($msg);
		}
		list($price,$num,$money)=$this->sellbuydata($fun_stock);
		//判定余额
		$nownum=M($fun_stock->name)->where(array("userid"=>$user['id']))->getField("数量");
		if($nownum<$num){
			 $this->error(L($fun_stock->byname).L('数量不足'));
		}
		// 防止点击多次提交按钮，重复提交
        $checks = M('会员');
        if(!$checks->autoCheckToken(I("post."))){
            $this->error(L("请不要重复提交"),__URL__."/trade:".__XPATH__);
        }
		//扣除交易货币流程
		$fun_stock->setrecord($user['编号'],$price,-$num,L('发布').L($fun_stock->name).L('卖出订单').L('出售').$num.L('股'),'卖出');
		//创建挂单记录
	    $fun_stock->setcompany($user['编号'],$price,$num,'卖出');
		M()->commit();
		$this->success(L("完成"));
	}
	//取得买入卖出的基础数据
	public function sellbuydata($fun_stock)
	{
		$price=I("request.price/f")>0?I("request.price/f"):$fun_stock->getPrice();
		$num  = I("request.num/d");
		if($fun_stock->getatt('stockMinint')>0)
		{
            if($num%$fun_stock->getatt('stockMinint') !=0){
				$this->error(L('数量不合法，必须为').$fun_stock->getatt('stockMinint').L('的整数倍'));
			}
		}
		return array($price,$num,$price*$num);
	}

   //股票市场中的交易记录查看
	public function tradedetail(fun_stock $fun_stock)
	{
	    if(I("request.id/d")<=0 || $fun_stock===false){
			$this->error(L('参数错误'));
		}
		$info=M($fun_stock->name."市场")->where(array('id'=>I("request.id/d")))->find();
		$this->assign('infos',unserialize($info['tradeinfo']));
		$this->assign("name",L($fun_stock->parent()->name).' '.$info['编号'].L($fun_stock->name).L('交易记录'));
		$this->display();
	}
	//查看股票账户前100个交易 1卖出 2 买入
	public function viewlist()
	{
		$fun_bank=X('>',__XPATH__);
	    if(I("request.mode/s")==''){
		   $this->error(L('参数错误'));
		}
		if(I("request.account/s")=='')
		{
			$this->error(L('参数错误'));
		}
		if(I("request.mode/s")=='sell') $type=1;
		if(I("request.mode/s")=='buy') $type=2;
		if(I("request.mode/s")=='zhuan') die;
		$account=(I("request.account/s")=='stock')?$fun_bank->stockBank:$fun_bank->name."托管";
        //$p=isset($_REQUEST['p'])?$_REQUEST['p']:0;
		//$num=10;
		$where=array();
        $where=array(
			'账户'=>$account,
			'type'=>$type,
			'state'=>0,
			'num'=>array('gt',0)
			);
		if($type==1){
			$order="price asc,addtime asc";
		}else{
			$order="price desc,addtime asc";
		}
		//先查出第100条的id
		$count = M($fun_bank->parent()->name."_".$fun_bank->name."市场")->where($where)->count();
		if($count>100){
			$list100=M($fun_bank->parent()->name."_".$fun_bank->name."市场")->where($where)->order($order)->limit('0,100')->select();
			$idstr='';
			foreach($list100 as $val){
				if($idstr!='') $idstr.=',';
				$idstr.=$val['id'];
			}
			$where['id']=array('in',$idstr);
		}
		//重新统计100条
		//$count = M($fun_bank->parent()->name."_".$fun_bank->name."市场")->where($where)->count();
		$list1 = new TableListAction($fun_bank->parent()->name."_".$fun_bank->name."市场");
        $list1 ->where($where)->order($order);
        /*$list1 ->setShow = array(
            L('账号') => array("row"=>"[编号]"),
			L('数量') => array("row"=>"num"),
            L('交易价格') => array("row"=>"[price]"),
			L('时间') => array("row"=>"[addtime]","format"=>"time"),
        );*/
        $list = $list1 ->getData();
		$this->assign("data",$list);
		$this->assign('decimalLen',$fun_bank->getatt('decimalLen'));//小数位数
		$this->display();
	}
	public function deal_list(fun_stock $fun_stock)
	{
		$list = new TableListAction($fun_stock->name."交易");
        $list ->where(array('买入编号'=>$this->userinfo['编号'],"卖出编号"=>$this->userinfo['编号'],"_logic"=>"or"))->order("成交时间 desc,id desc")->limit(15);
        $list ->setShow = array(
            L('成交时间')=>array("row"=>"[成交时间]","format"=>"time"),
            L('买入编号')=>array("row"=>array(array($this,"showuser"),"[买入编号]")),
            L('交易量')=>array("row"=>"[交易量]"),
            L('成交价')=>array("row"=>"[交易价]"),
            L('卖出编号')=>array("row"=>array(array($this,"showuser"),"[卖出编号]")),
        );
        $data = $list->getData();
        $this->assign('data',$data);
		$this->display();
	}
	function showuser($username){
		if($username==$this->userinfo['编号']){
			return "<span style='color:green'>".$username."</span>";
		}else{
			return "<span style='color:red'>".$username."</span>";
		}
	}
	//股票变动明细
	public function deal_detail(fun_stock $fun_stock)
	{
		$list = new TableListAction($fun_stock->name."明细");
        $list ->where(array('编号'=>$this->userinfo['编号']))->order("时间 desc,id desc")->limit(15);
        $list ->setShow = array(
            L('时间')=>array("row"=>"[时间]","format"=>"time"),
            L('数量')=>array("row"=>"[数量]"),
            L('价格')=>array("row"=>"[价格]"),
            L('余量')=>array("row"=>"[余量]"),
            L('类型')=>array("row"=>"[类型]"),
            L('备注')=>array("row"=>"[备注]"),
        );
        $data = $list->getData();
        $this->assign('data',$data);
		$this->display();
	}
	//挂单列表
	public function selllist(fun_stock $fun_stock){
		$list = new TableListAction($fun_stock->name.'市场');
        $list ->where(array('编号'=>$this->userinfo['编号']))->order("挂单时间 desc,id desc")->limit(15);
        $list ->setShow = array(
            L('挂单日期')=>array("row"=>"[挂单时间]","format"=>"time"),
            L('类型')=>array("row"=>"[类型]"),
            L('挂单价')=>array("row"=>"[挂单价]"),
            L('交易量')=>array("row"=>"[成交量]"),
            L('剩余量')=>array("row"=>"[剩余量]"),
            L('挂单总量')=>array("row"=>"[挂单总量]"),
            L('操作')=>array("row"=>array(array(&$this,"opreat"),"[剩余量]","[tradeinfo]","[id]","[类型]"))
        );
        $data = $list->getData();
        $this->assign('data',$data);
		$this->display();
	}
	//操作显示判断
	function opreat($num,$tradeinfo,$id,$type){
		$cxstr="<a href='__URL__/stockcancel:__XPATH__/id/{$id}'>撤销</a>";
		$str="<a href='__URL__/tradedetail:__XPATH__/id/{$id}'>查看交易明细</a>";
		$fun_stock=X('>',__XPATH__);
		$tradeinfo=unserialize($tradeinfo);
		if($tradeinfo){	
			if($num==0){
				$cxstr="";
			}
		}else{
			$str="尚未进行交易";
		}
		if((!$fun_stock->getatt('stockBuycancel') && $type=='买入') || ($type=='卖出' && !$fun_stock->getatt('stockSellcancel'))) $cxstr='';
			return $str."&nbsp;&nbsp;".$cxstr;
	}
	//撤销
	public function stockcancel(fun_stock $fun_stock)
	{
		//防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
	    B('XSS');
	    M()->startTrans();
		if(I("request.id/d")<=0){
			$this->error(L('参数错误'));
		}
		$mark_m=M($fun_stock->name."市场");
		$user_m=M($fun_stock->parent()->name);
		$markinfo=$mark_m->where(array('id'=>I("request.id/d")))->find();
		if(!$markinfo)
			$this->error(L('获取订单失败'));
		if($markinfo['状态']!='挂单中' || $markinfo['剩余量']==0)
			$this->error(L('订单状态错误'));
		if($markinfo['类型']=='买入'){
			//剩余量的所支付的金额
			$money=$markinfo['剩余量']*$markinfo['挂单价'];
			bankset($fun_stock->tradeBank,$markinfo['编号'],$money,L($fun_stock->name)."买入撤销","撤销挂单买入".L($fun_stock->name).L('剩余').$markinfo['剩余量'].L('股,每股').$markinfo['挂单价']);
		}
		if($markinfo['类型']=='卖出'){
			$num=$markinfo['剩余量'];
    		$fun_stock->setrecord($markinfo['编号'],$markinfo['挂单价'],$markinfo['剩余量'],$markinfo['账户'],"撤销挂单卖出".$fun_stock->name.L('剩余').$markinfo['剩余量'].L('股'),"撤销");
		}
        $mark_m->where(array('id'=>I("request.id/d")))->save(array('状态'=>'已撤销','剩余量'=>0));
        M()->commit();
		$this->success(L('完成'));
	}
	public function stock_change()
	{
		R("DmsAdmin://User/Index/header");
		$this->display();
	}
	//删除
	public function stockdelete()
	{
		$fun_stock=X('>',__XPATH__);
		if(!isset($_REQUEST['id']) || $fun_stock==false){
			$this->error(L('参数错误'));
		}
		$mark_m=M($fun_stock->name."市场");
		$markinfo=$mark_m->where(array('id'=>$_REQUEST['id']))->find();
		if(!$markinfo)$this->error(L('获取订单失败'));
		//已成交过的无法删除
		if($markinfo['state']==0 && $markinfo['num']>0 ) $this->error(L('订单状态错误'));
		M()->startTrans();
		$rs=$mark_m->where(array('id'=>$_REQUEST['id']))->delete();
		if($rs){
			M()->commit();
			$this->success(L('完成'));
		}else{
			M()->rollback();
			$this->error(L('失败'));
		}
	}
}
?>