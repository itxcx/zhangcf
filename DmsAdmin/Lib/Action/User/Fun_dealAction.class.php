<?php
defined('APP_NAME') || die(L('不要非法操作哦'));
class Fun_dealAction extends CommonAction {
	public function index(fun_stock $fun_stock)
	{
		$userinfo=$this->userinfo;
		$price=$fun_stock->getPrice();
		$lists=M($fun_stock->name."流水")->where(array('编号'=>$userinfo['编号']))->order("addtime desc,id desc")->select();
		$lists1=M($fun_stock->name."流水")->where(array('编号'=>$userinfo['编号']))->order("addtime desc,id desc")->select();
		$today=strtotime(date("Y-m-d",systemTime()));
        //这个变量不知道怎么来的
        //if($yeprice==NULL){
        
           $yeprice=$price;
	    //}
	    
		$list = $this->getxml();
		//会员总股票
		$nums = M($fun_stock->name.'持有')->field('sum(nownum) as num,isSell,price')->where(array('isSell'=>0,'编号'=>$this->userinfo['编号']))->find();
		
		$uprate=(floatval($price)-floatval($yeprice))*100/floatval($yeprice);
		$this->list = $list;
		$this->assign('decimalLen',$fun_stock->priceLen);
		$this->assign('lists',$lists);
		$this->assign('lists1',$lists1);
		$this->assign('nums',(int)$nums['num']);
		$this->assign('user',$userinfo);
		$this->assign('zongjia',(int)$nums['num']*$price);
		$this->assign('price',$price);
		$this->assign('startprice',$fun_stock->getatt('startprice'));
		$this->assign('name',$fun_stock->name);
		$this->assign('stockClose',$fun_stock->getatt('stockClose'));
		$this->display();
	}
   //1卖出 2 买入
	public function stock_buy(fun_stock $fun_stock)
	{
		//防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
	     B('XSS');
		M()->startTrans();
		//判断是否休市
		if($fun_stock->getatt('stockClose') == true){
			$this->error(L($fun_stock->getatt('stockCloseMsg')));
		}
		$user = M("会员")->lock(true)->where(array('编号'=>$this->userinfo['编号']))->find();
		$money=$_REQUEST['money'];
	
		if($money>$this->userinfo[$fun_stock->tradeBank]){
				$this->error($fun_stock->tradeBank.L('余额不足'));
		}
		//当前股价
		$pricenow=$fun_stock->getPrice();
		$allstock=M($fun_stock->name.'设置')->order("id desc")->getField('stockAllNum');
		//$sell =M($fun_stock->name.'交易')->where(array('剩余量'=>array('gt',0)))->find();

		if($allstock>0 && $allstock < floor($money/$pricenow)){
			$this->error(L('公司发行股票已不足'));
		}
		//if((int)$allstock<floor($money/$pricenow && $sell['剩余量'] <= 0)){
		//	$this->error(L('公司发行股票已不足'));
		//}
		// 防止点击多次提交按钮，重复提交
        $checks = M('会员');
        if(!$checks->autoCheckToken(I("post."))){
            redirect(__URL__."/index:__XPATH__",2,L("完成"));
        }
		//扣除交易货币流程
		$fun_stock->buy($user['编号'],$money,$user);

	    M()->commit();
		$this->success(L("完成"));
		
	}
	
	
	//股票卖出
	
	public function stock_sell(fun_stock $fun_stock)
	{
		//防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
	     B('XSS');
		$user=$this->userinfo;
		M()->startTrans();
		$result = $fun_stock->autoBack($user['编号']);
		if($result){
			M()->commit();
		  $this->success(L("完成"));
		}else{
			M()->rollback();
		   $this->error(L("还未达到回购的条件"));
		}
	    
	}
	
	//交易记录
	public function deal_list(fun_stock $fun_stock)
	{
		$list = M($fun_stock->name.'流水');
		
		import("ORG.Util.Page");
		$count = M($fun_stock->name."流水")->where(array('编号'=>$this->userinfo['编号']))->count();
		$Page  = new Page($count,10);
		$list = $list->where(array('编号'=>$this->userinfo['编号'],))->order('addtime')->limit($Page->firstRow.','.$Page->listRows)->select();
		$show       = $Page->show();
		$this->assign('page',$show);
		$this->assign('lists',$list);
		$this->assign('name',$fun_stock->name);
		$this->display();
	}
	//交易明细
	public function deal_detail(fun_stock $fun_stock)
	{
		$list = M($fun_stock->name."交易");
		import("ORG.Util.Page");
		$count = $list->where(array('编号'=>$this->userinfo['编号']))->count();
		$Page  = new Page($count,10);
		$list = $list->where(array('编号'=>$this->userinfo['编号'],))->limit($Page->firstRow.','.$Page->listRows)->select();
		$show       = $Page->show();
		
		$this->assign('data',$show);
		$this->assign('lists',$list);
		$this->assign('name',$fun_stock->name);
		$this->display();
	}

	
	
	
   //股票市场中的交易记录查看
	public function tradedetail(fun_stock $fun_stock)
	{
		
	    if(!isset($_REQUEST['id']) || $_REQUEST['id']=='' || $fun_stock===false){
			$this->error(L('参数错误'));
		}
		$info=M($fun_stock->parent()->name."_".$fun_stock->name."市场")->where(array('id'=>$_REQUEST['id']))->find();
		$this->assign('infos',unserialize($info['tradeinfo']));
		$this->assign("name",L($fun_stock->parent()->name).' '.$info['编号'].L($fun_stock->name).L('交易记录'));
		$this->display();
	}
	public function getxml(fun_stock $fun_stock)
	{
	   	$model = M($fun_stock->name.'走势');
		$num = $model->count();
		$num2 = $num;
		$list = array();
		if($num && $num<=30){
			$list=$model->limit($num)->order("id asc")->field('计算日期,价格')->select();
			$dtime = $list[$num-1]['计算日期'];
			asort($list);					//倒序
			for($i=0;$i<(30-$num2);$i++){
				//补足不够的天数
				$list[$num]['价格'] = '0.10';
				$list[$num]['计算日期'] = $dtime+($i+1)*24*3600;
				$num++;	
			}
		}else if($num && $num>30){
			$list = $model->limit(30)->order('id desc')->field('计算日期,价格')->select();
			asort($list);					//倒序
		}else{
			for($i=0;$i<(30-$num2);$i++){
				$list[$num]['价格'] = '0.10';
				$list[$num]['计算日期'] = time()+$i*24*3600;
				$num++;	
			}
		}
		
		return $list; 
	}
}
?>