<?php
defined('APP_NAME') || die('不要非法操作哦!');
class Fun_stock2Action extends CommonAction
{
	//股票设置
	public function config($fun_stock)
	{
		$this->assign('price',$fun_stock->getPrice());
		$set=$fun_stock->getSet();
		$this->assign('decimalLen',$fun_stock->getatt('decimalLen'));
		$this->assign('stockStartMoney',$fun_stock->getatt('stockStartMoney'));//起始单价
		$this->assign('startComGrail',$fun_stock->getatt('startComGrail'));//公司大盘
		$this->assign('stockClose',$fun_stock->getatt('stockClose'));//股票休市
		$this->assign('stockCloseMsg',$fun_stock->getatt('stockCloseMsg'));
		//$allnums=M($fun_stock->name."设置")->where(array("id"=>1))->getField("stockAllNum");
		$this->assign('allnums',$set['stockAllNum']);
	    $this->assign('upSkip' ,$set['涨价幅度']);//公司发行量
		$this->assign('upNum'  ,$set['涨价额']);//托管账户限卖
		$this->assign('splitPrice',$fun_stock->getatt('splitPrice'));//拆分
		//小数位
		$this->assign('priceLen',$fun_stock->getatt('priceLen'));
		//手续费
		$this->assign('shouxufei'   ,$fun_stock->getatt('shouxufei'));
		$this->assign('StockOpening',$fun_stock->getatt('StockOpening'));//股票开盘

		/*
		if(!CONFIG("","stockTrade:".$fun_stock->Path())){
			$fun_stock->setatt('stockTrade',0);
		}
		$this->assign('stockTrade',$fun_stock->getatt('stockTrade'));//交易总数
		if(!CONFIG("","StockSplit:".$fun_stock->Path())){
			$fun_stock->setatt('StockSplit',1);
		}
		$this->assign('StockSplit',$fun_stock->getatt('StockSplit'));//股票拆分
		if(!CONFIG("","stockNowPrice:".$fun_stock->Path())){
			$fun_stock->setatt('stockNowPrice',$fun_stock->getPrice());
		}
		if(!CONFIG("","StockOpening:".$fun_stock->Path())){
			$fun_stock->setatt('StockOpening',0.1);
		}
		*/
		$this->display();
	}
	
	//股票拆骨
	public function stockSplit()
	{
		$this->display();
	}
	public function intwp()
	{
		$this->assign("splitnum",I("request.splitnum/f"));
		$this->display();
	}
	//股票拆骨
	public function splitSave(fun_stock $fun_stock)
	{
		$repwd=I("request.repwd/s");
		if($repwd=='') $this->error("请填写二级密码");
		$where['id'] = $_SESSION[C('RBAC_ADMIN_AUTH_KEY')];
		M()->startTrans();
        $result=M("admin")->table("admin")->where($where)->field("password")->find();
        if(!chkpass($repwd,$result['password'])){
            $this->error("管理员密码错误");
        }
		$fun_stock->stockPrice();
		$num=I("request.splitnum/f");
		if($num=="" || $num<=0 || $num==1){
			M()->rollback();
		  	$this->error("拆分倍数不合法");
		}
		$fun_stock->cancelall();
		$fun_stock->splitstock($num);
		$fun_stock->upconf($num);
		M()->commit();
		$this->success('拆分完成');
	}
	public function configSave(fun_stock $fun_stock)
	{
		M()->startTrans();
		 if(I("post.stockClose/s")=='true')
			{
			  $fun_stock->setatt('stockClose',true);
			}else{
			 $fun_stock->setatt('stockClose',false);
			}
		$fun_stock->setatt('stockCloseMsg',I("post.stockCloseMsg/s"));
		//开盘价格
		$fun_stock->setatt('StockOpening',I("post.StockOpening/f"));
		//拆分价格
		$fun_stock->setatt('splitPrice',I("post.splitPrice/f"));
		//小数位
		//$fun_stock->setatt('decimalLen',I("post.decimalLen/d"));
		//交易的小数位
		$fun_stock->setatt('priceLen',I("post.priceLen/d"));
		//涨幅
		//$fun_stock->setatt('upSkip',I("post.upSkip/f"));
		//交易总数
		//$fun_stock->setatt('stockTrade',I("post.stockTrade/f"));
		//手续费
		$fun_stock->setatt('shouxufei',I("post.shouxufei/f"));
			
		M($fun_stock->name."设置")->where(array("id"=>1))->save(array(
			'stockAllNum'=>I("post.allnums/f"),
			'涨价额'=>I("post.upNum/f"),
			'涨价幅度'=>I("post.upSkip/f")
			));
		M()->commit();
		$this->success('完成');
	}
	//股票列表
	public function index($fun_stock)
	{
		$setButton=array(                 // 底部操作按钮显示定义
				'股票充值'=>array("class"=>"add","href"=>__APP__."Admin/Fun_stock/addin:__XPATH__"           ,"target"=>"dialog","mask"=>"true","width"=>"520","height"=>"260"),
				/*'编辑'=>array("class"=>"edit","href"=>__APP__."Admin/Fun_stock/edit:__XPATH__/id/{tl_id}"  ,"target"=>"dialog","mask"=>"true","width"=>"520","height"=>"260"),
				'删除'=>array("class"=>"delete","href"=>__APP__."Admin/Fun_stock/delete:__XPATH__/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要删除该数据吗？"),*/
        );  
        $list=new TableListAction('会员');
        $list->setButton = $setButton; 
		$list->where(array($fun_stock->name=>array('gt',0)))->order($fun_stock->name." desc");
        $list->addshow($this->userobj->byname."编号",array("row"=>"[编号]","searchMode"=>"text","excelMode"=>"text","searchPosition"=>"top"));  
        $list->addShow($fun_stock->byname."数量",array("row"=>"[".$fun_stock->name."]","searchMode"=>"text","excelMode"=>"text"));
        $this->assign('list',$list->getHtml());          // 分配到模板
        $this->display();
	}
	//股票交易
	public function trade($fun_stock)
	{
        $list=new TableListAction($fun_stock->name.'交易');
        $list->setButton = $setButton;
		$list->order("addtime desc");
        $list->addshow("时间",array("row"=>"[addtime]","searchMode"=>"date","format"=>"time","order"=>"时间"));
        $list->addshow("买入编号",array("row"=>"[买入编号]","searchMode"=>"text","excelMode"=>"text","order"=>"编号","searchPosition"=>"top"));  
        $list->addShow("交易股份",array("row"=>"[num]","searchMode"=>"text","excelMode"=>"text"));
        $list->addshow("交易价格",array("row"=>"[price]","excelMode"=>"text","searchMode"=>"num"));      
        $list->addshow("卖出编号",array("row"=>"[卖出编号]","excelMode"=>"text","searchMode"=>"text","searchPosition"=>"top")); 
        $this->assign('list',$list->getHtml()); 
        $this->display();
	}
	//股票挂单
	public function saleList($fun_stock)
	{
        $list=new TableListAction($fun_stock->name.'交易');
        $list->setButton = $setButton;
		$list->order("时间 desc");
        $list->addshow("时间",array("row"=>"[时间]","searchMode"=>"date","format"=>"time","order"=>"时间"));
        $list->addshow("会员编号",array("row"=>"[编号]","searchMode"=>"text","excelMode"=>"text","order"=>"编号","searchPosition"=>"top"));  
        $list->addShow("挂单量",array("row"=>"[挂单量]","searchMode"=>"text","excelMode"=>"text"));
        $list->addshow("剩余量",array("row"=>"[剩余量]","excelMode"=>"text","searchMode"=>"num"));      
        $list->addshow("类型",array("row"=>"[类型]","excelMode"=>"text")); 
        $this->assign('list',$list->getHtml()); 
        $this->display();
	}
	//股票拆分记录
	public function splitList($fun_stock)
	{
        $list=new TableListAction($fun_stock->name.'拆股');
        $list->setButton = $setButton;
		$list->order("addtime desc");
        $list->addshow("时间",array("row"=>"[addtime]","searchMode"=>"date","format"=>"time","order"=>"addtime"));
        $list->addshow("拆股价格",array("row"=>"[price1]","searchMode"=>"text","excelMode"=>"text"));  
        $list->addShow("拆股后价格",array("row"=>"[price]","searchMode"=>"text","excelMode"=>"text"));
        $list->addshow("增加数量",array("row"=>"[拆分增加]","excelMode"=>"text","searchMode"=>"num"));      
        $list->addshow("说明",array("row"=>"[memo]","excelMode"=>"text")); 
        $this->assign('list',$list->getHtml()); 
        $this->display();
	}
	//股票持有记录
	public function stockHave($fun_stock)
	{
        $list=new TableListAction($fun_stock->name.'持有');
        $list->setButton = $setButton;
		$list->order("addtime desc");
        $list->addshow("时间",array("row"=>"[addtime]","searchMode"=>"date","format"=>"time","order"=>"addtime"));
        $list->addshow("会员编号",array("row"=>"[编号]","searchMode"=>"text","excelMode"=>"text","order"=>"编号","searchPosition"=>"top"));  
        $list->addShow("购买数量",array("row"=>"[num]","searchMode"=>"num","excelMode"=>"text"));
        $list->addshow("持有数量",array("row"=>"[nownum]","excelMode"=>"text","searchMode"=>"num"));   
        $list->addshow("价格",array("row"=>"[price]","excelMode"=>"text","searchMode"=>"num"));   
        $list->addshow("说明",array("row"=>"[memo]","excelMode"=>"text"));   
        $list->addshow("类型",array("row"=>array(array(&$this,'haveType'),"[isSell]"),"excelMode"=>"text")); 
        $this->assign('list',$list->getHtml()); 
        $this->display();
	}
	public function haveType($type){
		return $type==0?'未卖出':"已卖出";
	}
   //股票明细
	public function record($fun_stock)
	{
        $list=new TableListAction($fun_stock->name.'流水');
        $list->setButton = $setButton;
		$list->order("id asc");
        $list->addshow("时间",array("row"=>"[addtime]","searchMode"=>"date","format"=>"time","order"=>"时间"));
		$list->addshow("类型",array("row"=>array(array(&$this,"tradetype"),"[type]"),"searchMode"=>"text","searchPosition"=>"top",'searchRow'=>'[type]',"searchSelect"=>array('增加'=>2,"减少"=>1))); 
        $list->addshow("编号",array("row"=>"[编号]","searchMode"=>"text","excelMode"=>"text","order"=>"编号","searchPosition"=>"top"));  
        $list->addShow("交易股份",array("row"=>"[num]","searchMode"=>"num","excelMode"=>"text"));
	//	$list->addShow("余额",array("row"=>"[余额]","searchMode"=>"num","excelMode"=>"text"));
        $list->addshow("交易价格",array("row"=>"[price]","excelMode"=>"num","searchMode"=>"num")); 
	   // $list->addShow($fun_stock->byname."账户",array("row"=>"[账户]","searchMode"=>"text","searchPosition"=>"top","searchSelect"=>array($fun_stock->stockBank=>$fun_stock->stockBank,$fun_stock->byname."托管"=>$fun_stock->name."托管")));
		$list->addshow("备注",array("row"=>"[memo]","excelMode"=>"text"));
        $this->assign('list',$list->getHtml());              // 分配到模板
        $this->display();
	}
	public function tradetype($type){
		if($type==1) return "减少";
		if($type==2) return "增加";
	}
	//股票市场
	public function shop($fun_stock)
	{
		$setButton=array(                 // 底部操作按钮显示定义
				'添加交易'=>array("class"=>"add","href"=>__APP__."Admin/Fun_stock/add:__XPATH__","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"260"),
				/*'编辑'=>array("class"=>"edit","href"=>__APP__."Admin/Fun_stock/edit:__XPATH__/id/{tl_id}","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"260"),
				'删除'=>array("class"=>"delete","href"=>__APP__."Admin/Fun_stock/delete:__XPATH__/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要删除该数据吗？"),*/
        );  
        $list=new TableListAction($fun_stock->name.'市场');
        $list->setButton = $setButton; 
		$list->order("addtime desc");
		$list->addshow("挂单时间",array("row"=>"[addtime]","format"=>"date","searchMode"=>"text","excelMode"=>"text","searchPosition"=>"top")); 
        $list->addshow($this->userobj->byname."编号",array("row"=>"[编号]","searchMode"=>"text","excelMode"=>"text","searchPosition"=>"top"));  
        $list->addShow($fun_stock->byname."原始数量",array("row"=>"[num1]","searchMode"=>"text","excelMode"=>"text"));
		$list->addShow($fun_stock->byname."已成交量",array("row"=>"[num2]"));
		$list->addShow($fun_stock->byname."剩余数量",array("row"=>"[num]"));
		$list->addShow($fun_stock->byname."价格",array("row"=>"[price]","searchMode"=>"text","excelMode"=>"num"));
		$list->addShow($fun_stock->byname."总价",array("row"=>array(array(&$this,"stockAllprice"),"[price]","[num1]")));
		$list->addShow("账户类型",array("row"=>"[账户]","searchMode"=>"text","searchPosition"=>"top","searchSelect"=>array($fun_stock->stockBank=>$fun_stock->stockBank,$fun_stock->byname."托管"=>$fun_stock->name."托管")));
		$list->addShow("类型",array("row"=>array(array(&$this,"stocktype"),"[type]",$fun_stock->Path()),"searchMode"=>"text","searchPosition"=>"top",'searchRow'=>'[type]',"searchSelect"=>array('买入'=>2,"卖出"=>1)));
		$list->addShow("交易信息",array("row"=>array(array(&$this,"tradeInfo"),"[id]","[tradeinfo]")));
        $this->assign('list',$list->getHtml());          // 分配到模板
        $this->display();
	}
	public function tradedetail($fun_stock)
	{
		if(I("request.id/d")=='' || I("request.id/d")<=0 || $fun_stock==false){
		   $this->error("参数错误");
		}
		$tradeinfo=M($fun_stock->name."市场")->where(array('id'=>I("request.id/d")))->getField('tradeinfo');
		$this->assign('info',unserialize($tradeinfo));
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
	public function stocktype($type,$path)
	{
		if($type==1) return '卖出';
		if($type==2) return '买入';
		//return $fun->type[$type];
	}
	public function stockAllprice($price,$num)
	{
         return $price*$num;
	}
	//股票充值
	public function addin($fun_stock)
	{
        $this->assign('name',$fun_stock->byname); 
		$this->assign('username',$this->userobj->byname."编号"); 
		$this->assign('stocknum',$fun_stock->byname."数量"); 
        $this->display();
	}
	public function savein($fun_stock)
	{
		$user_model=M('会员');
		$data=array();
		$data['编号']=I("post.编号/s");
		$data['num']=I("post.num/d");
		if($data['num']=='' || $data['num']<=0)  $this->error('数量不合法');
		$user=$user_model->where('编号='.$data['编号'])->find();
		if($user){
			   M()->startTrans();
			   $rs=$user_model->where('编号='.$data['编号'])->save(array($fun_stock->name=>array('exp',$fun_stock->name.'+'.$data['num'])));
			   if($rs){
				   $data['addtime']=systemTime();
					  $fun_stock->setrecord($data['编号'],$fun_stock->stockPrice(),$data['num'],$this->userobj->byname.$data['编号']."后台充值".$data['num']."股",3);
					  M()->commit();
		              $this->success("添加".$fun_stock->byname.'成功');
	            	}else{
					  M()->rollback();
			          $this->error("添加".$fun_stock->byname.'失败');
		           }
			   }else{
				   $this->error($this->userobj->byname.$data['编号']."不存在");
			   }
	}
	//股票增加
	public function add($fun_stock)
	{
		$this->assign('type',$fun_stock->type);
        $this->assign('name',$fun_stock->byname); 
		$this->assign('username',$this->userobj->byname."编号"); 
		$this->assign('stocknumName',$fun_stock->byname."数量"); 
		$this->assign('stockprizeName',$fun_stock->byname."价格"); 
		$this->assign('stockprize',$fun_stock->stockPrice());
        $this->display();
	}
	public function save($fun_stock)
	{
		$modle=M($fun_stock->name."市场");
		$data=$modle->create();
		if(!$data) $this->error('获取数据失败');
		$user_model=M('会员');
		M()->startTrans();
		$user=$user_model->lock(true)->where("编号='".$data['编号']."'")->find();
		if($user){
           if($user[$fun_stock->stockBank]<$data['num']){
           	   M()->rollback();
		     $this->error('该'.$this->userobj->byname.'的'.$fun_stock->stockBank.'数量不足'.$data['num']);
		   }else{
		     $data['addtime']=systemTime();
			 $data['num1']=$data['num'];
			 $data['tradeinfo']=$fun_stock->encode();
		      $rsadd=$modle->add($data);
		        if($rsadd){
					$fun_stock->setrecord1($data['编号'],$data['price'],$data['num'],$data['type']);
					M()->commit();
		            $this->success("添加".$fun_stock->byname.'成功');
	            }else{
	            		M()->rollback();
			          $this->error("添加".$fun_stock->byname.'失败');
		           }
		   }
		}else{
			M()->rollback();
			$this->error($this->userobj->byname.$data['编号']."不存在");
		}
		
	}
	public function edit($fun_stock)
	{
        $this->assign('name',$fun_stock->byname); 
        $this->display();
	}
	public function delete($fun_stock)
	{
		$id=I("request.id/d");
		if(M($fun_stock->name)->where(array("id"=>$id))->delete()){
			$this->success('删除成功');
		}else{
		    $this->error('删除失败');
		}
	}
	public function cancelall($fun_stock)
	{
	   $fun_stock->cancelall();
	}
	//股票走势
	public function stockTrend($fun_stock)
	{
        $list=new TableListAction($fun_stock->name.'走势');
        $setButton=array(                 // 底部操作按钮显示定义
				'编辑'=>array("class"=>"edit","href"=>__APP__."Admin/Fun_stock/stockTrendedit:__XPATH__/id/{tl_id}","target"=>"dialog","mask"=>"true","width"=>"350","height"=>"220","title"=>"编辑走势"),
				'删除'=>array("class"=>"delete","href"=>__APP__."Admin/Fun_stock/stockTrenddelete:__XPATH__/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要删除该数据吗？"),
        );  
		$list->setButton = $setButton;
		$list->order("计算日期 desc");
        $list->addshow("时间",array("row"=>"[计算日期]","searchMode"=>"date","format"=>"time","order"=>"[计算日期]","searchMode"=>"date","searchPosition"=>"top"));
        $list->addshow("价格",array("row"=>"[价格]"));  
        $list->addShow("成交量",array("row"=>"[成交量]"));
        $list->addshow("认购量",array("row"=>"[认购量]"));      
        $list->addshow("成交金额",array("row"=>"[成交金额]")); 
        $this->assign('list',$list->getHtml()); 
        $this->display();
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
		if(M($fun_stock->name."走势")->where(array("id"=>$id))->delete()){
			$this->success('删除成功');
		}else{
		    $this->error('删除失败');
		}
	}
	public function stockTrendsave($fun_stock)
	{
		$id=I("request.id/d");
		$modle=M($fun_stock->name."走势");
		$data=$modle->create();
		if(!$data) $this->error('获取数据失败');
		$rsadd=$modle->where(array("id"=>$id))->save($data);
		 if($rsadd){
		
		    $this->success('成功');
	     }else{
			$this->error('失败');
		 }
	}
	
	//
	public function stockAnalysis()
	{
		$m_user = M('会员');
		$m_sc = M('股票市场');
		//持有交易股
		$tradeHave = $m_user->sum('股票账户');
		//持有托管股
		$trustHave = $m_user->sum('股票托管');
		//普通股卖出
		$tradeSell = $m_sc->where("type=1 and num>0 and 账户='股票账户'")->sum('num');
		//普通股买入
		$tradeBuy  = $m_sc->where("type=2 and num>0 and 账户='股票账户'")->sum('num');
		//托管股卖出
		$trustSell = $m_sc->where("type=1 and num>0 and 账户='股票托管'")->sum('num');
		//总持有量
		$allHave   = $tradeHave + $trustHave + $tradeSell + $trustSell;
		$this->assign('data',array('allHave'=>$allHave,'tradeHave'=>$tradeHave,'trustHave'=>$trustHave,'tradeSell'=>$tradeSell,'trustSell'=>$trustSell));
		$this->display();
	}
	public function stockAdd()
	{
		$this->display();
	}
	public function stockAddSave($fun_stock)
	{
		$m_user = M('会员');
		M()->startTrans();
		$user  = $m_user->where(array('编号'=>I("post.userid")))->find();
		if(!$user)
		{
			$this->error($this->userobj->byname.'未查到');
		}
		$num=I("post.num/d");
		if($num =='' || $num == 0)
		{
			$this->error('数量不合法');
		}
		
		$fun_stock->setrecord($user['编号'],0,$num,I("post.account/s"),I("post.memo/s"),2,true,false);
		M()->commit();
		$this->success('成功');
	}	
}
?>