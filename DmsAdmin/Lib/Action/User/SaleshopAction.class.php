<?php
defined('APP_NAME') || die(L('not_allow'));
class SaleshopAction extends CommonAction {
 	/*
    +----------------------------------------------------------
    * 购物车产品消费
    +----------------------------------------------------------
    */
	public function buy_shop(sale_shop $sale_shop){
		if(!$sale_shop->use)
		{
			echo "<script>alert('没有权限');</script>";die;
		}
		$probj = X("product@".$sale_shop->productName);
	    if(!$probj){
	       $this->error("产品不存在");
	    }
	    $list = new TableListAction($probj->name);
		$list->pagenum = 8;
		//判断是否设置了产品功能
        $where=array();
        $wherestring='';
        $where['状态'] = '使用';
        $where[$sale_shop->productMoney][] = array('gt',0);
		if($probj->productnumCheck || adminshow('prostock')){
            $where['可订购数量'] = array('gt',0);
		}
		$num = M('产品_功能')->where(array('状态'=>'使用'))->select();
		if($num){
			//查询属于$sale的产品节点
			$i=0;
			foreach($num as $v){
				//将每一个商城产品功能的节点名称进行切割
				$objname = explode(",",$v['节点名称']);
				if(in_array($sale_shop->name,$objname)){
					$i++;
                    if($i==1)
						$wherestring .= "FIND_IN_SET(".$v['id'].",所属功能)";
                    else
                    	$wherestring .= " or FIND_IN_SET(".$v['id'].",所属功能)  ";
				}
			}
			if($wherestring!=""){
				$where['_string'] = $wherestring;
			}
		}
        
		//判断是否选择了search
		if(I("post.search/s")!=""){
			//判断是否了分类
			if(I("post.fenlei/s")!=""){
                //查询分类id的名称
                $fenname = M($probj->name.'_分类')->where(array('名称'=>I("post.fenlei/s")))->find();
                if($fenname)
					$where['分类'] = $fenname['名称'];
			}
			//判断是否填写了商品名称
			if(I("post.pro_name/s")!=""){
		    	$where['名称'] =array('like',"%".I("post.pro_name/s")."%");
			}
			//判断是否价格的范围
			if(I("post.lingshou_start/f")>=0){
				$where[$sale_shop->productMoney][] = array('egt',I("post.lingshou_start/f"));
			}
			if(I("post.lingshou_end/f")>0){
				$where[$sale_shop->productMoney][] = array('elt',I("post.lingshou_end/f"));
			}
		}
		$this->assign("fenlei",I("post.fenlei/s"));
		$this->assign("pro_name",I("post.pro_name/s"));
		$this->assign("lingshou_start",I("post.lingshou_start/f"));
		$this->assign("lingshou_end",I("post.lingshou_end/f"));
	    $list->where($where);
	    $list ->order("顺序 asc");
        $data = $list->getData();
        $this->assign('data',$data);
        //查询全国标准的收费
        $areafei = M('产品物流管理')->where(array('是否全国标准'=>1))->find();
        $this->assign('areafei',$areafei);
        $fenlei = M($probj->name.'_分类')->where(array('状态'=>'使用'))->select();
		$this->assign('fenlei',$fenlei);
		$this->assign('sale',$sale_shop);
		$accbanks=X('accbank@'.$sale_shop->accBank)->getcon("bank",array("name"=>""));
		$banks=array();
		foreach($accbanks as $accbank){
			$banks[] = X('fun_bank@'.$accbank['name']);
		}
		$this->assign('banks',$banks);
		$discount = $sale_shop->getDiscount($this->userinfo);
		$this->assign('discount',$discount);
		$this->display($sale_shop->template);
	}
  
 	/**
    +----------------------------------------------------------
    * 购物车产品详情
    +----------------------------------------------------------
    */
	public function chanpinxiangxi(sale_shop $sale_shop){
		$bank = X('fun_bank@'.$sale_shop->accBank);
	  	//查询出产品
	  	$proid = I("get.id/d");
		$product = M($sale_shop->productName)->where(array('id'=>$proid))->find();
		$this->assign('sale',$sale_shop);
		$this->assign('bank',$bank);
		$this->assign('product',$product);
		$this->assign('proobj',X("product@".$sale_shop->productName));
		$this->display();
	}
	/**
    +----------------------------------------------------------
    *加入购物车
    +----------------------------------------------------------
    */
	function buygouwu(sale_shop $sale_shop){
		if(!$sale_shop || I("post.proid/d")<=0){
			$this->error("操作失败");
		}
		if(I("post.buynum/d")<=0){
			$this->error('请输入购买数量');
		}
		//产品
		$proid=I("post.proid/d");
		$buynum=intval(abs(I("post.buynum/d")));
		$product=M($sale_shop->productName)->where(array("id"=>$proid))->find();
		if(!$product){
			$this->error("产品不存在，请重新购买");
		}
		//判断产此产品在兑换购物车中是否已经存在
		$buycarmodel = M($sale_shop->name.'购物车');
		$have = $buycarmodel->where(array('产品id'=>$proid,'编号'=>$this->userinfo['编号']))->find();
		//判断库存是否不足
		if($buynum>$product['可订购数量']){
			$this->error('库存不足，请减少一些试试哦');
		}elseif($have){
			$cha=$product['可订购数量']-$have['数量'];
			if($cha<0) $cha=0;
			if($buynum>$cha){
				$this->error("购物车中已有此产品，您只能再购买{$cha}件");
			}
		}
		
		M()->startTrans();
		//已有
		if($have){
			$result =  $buycarmodel->where(array('id'=>$have['id']))->setInc('数量',$buynum);
		}else{
			//盛放接收到的POST的值 
			$data = array();
			$data = array(
				'产品id'=>$proid,
				'数量'=>$buynum,
				'编号'=>$this->userinfo['编号'],
				'操作时间'=>systemTime()
			);
			//添加新的记录到购物车当中
			$result = $buycarmodel->data($data)->add();
		}
		if($result){
			M()->commit();
			$this->success('添加购物车成功',__URL__."/chongxiao_gouwuche:".__XPATH__);
		}else{
			M()->rollback();
			$this->error('添加失败');
		}
	}
 /**
    +----------------------------------------------------------
    * 购物车列表提交
    +----------------------------------------------------------
    */
	function chongxiao_gouwuche(sale_shop $sale_shop){
		import('ORG.Util.Page');// 导入分页类
		$buycarmodel = M($sale_shop->name.'购物车');
		$count      = $buycarmodel->where(array('编号'=>$this->userinfo['编号']))->count();
		$Page       = new Page($count,6);
		$show       = $Page->show();// 分页显示输出
		$list = M()->query("select a.*,b.数量 as buynum,b.操作时间,b.id as wuliuid from dms_".$sale_shop->productName." as a right join dms_".$sale_shop->name."购物车 as b on a.id=b.产品id where b.编号='{$this->userinfo['编号']}' and a.状态='使用' order by b.操作时间 asc   limit {$Page->firstRow},{$Page->listRows}");
		foreach($list as $key=>$v){
			//每个产品的总价
			$list[$key]['sum_price'] = $v['buynum']*$v[$sale_shop->productMoney]; 
		} 
		$this->assign('list',$list);
		$this->assign('page',$show);
		$this->assign('sale',$sale_shop);
		$this->assign('userinfo',$this->userinfo);
		$this->assign('buycar',$sale_shop->name.'购物车');
		$this->display();
	}
 /**
    +----------------------------------------------------------
    * 移除购物车
    +----------------------------------------------------------
    */
	function buygouwuchongxiao_del(sale_shop $sale_shop){
		//获取购物车某一个产品的所在的记录的id
		$id = I("get.id/d");
		//获取购物车
		if(!$sale_shop){
			$this->error("此购物车不存在");
		}
		M()->startTrans();
		$res = M($sale_shop->name.'购物车')->where(array('id'=>$id))->delete();
		if($res){
			M()->commit();
			$this->success('操作成功');
		}else{
			M()->rollback();
			$this->error('操作失败');
		}
	}
 /**
    +----------------------------------------------------------
    * 移除购物车
    +----------------------------------------------------------
    */
	function gouwuchechongxiao_del(sale_shop $sale_shop){
		if(!$sale_shop){
			$this->error("此购物车不存在");
		}
		M()->startTrans();
		$res = M($sale_shop->name.'购物车')->where(array('编号'=>$this->userinfo['编号']))->delete();
		if($res){
			M()->commit();
			$this->success('清空购物车成功');
		}else{
			M()->rollback();
			$this->error('清空失败');
		}
	}
 /**
    +----------------------------------------------------------
    * 购物车下一步进行发货地址填写 和物流费的计算
    +----------------------------------------------------------
    */
    function buygouwu_chongxiao(sale_shop $sale_shop){
    	$res = M($sale_shop->name.'购物车')->where(array('编号'=>$this->userinfo['编号']))->getField("id,产品id");
    	if(!$res){
    		$this->error("购物车中没有产品",__URL__."/buy_shop:".__XPATH__);
    	}
    	//总价
		$all_price = 0;
		//总pv
		$all_pv = 0;
		//总重量
		$all_zongliang = 0;
		M()->startTrans();
		foreach(I("post.buynum/a") as $key=>$v){
			$num=abs(intval($v));
			if($num==0){
				//删除
				M($sale_shop->name.'购物车')->delete($key);
				continue;
			}
			//查询此产品
			$pro_info = M($sale_shop->productName)->where(array('id'=>$res[$key]))->find();
			if($num>$pro_info['可订购数量']){
				$this->error("您选择的产品名称为 {$pro_info['名称']} 的库存数量只有{$pro_info['可订购数量']},请修改购物车的数量");
			}
			//查询购物车记录
			$shopinfo = M($sale_shop->name.'购物车')->where(array('id'=>$key))->find();
			//更新购物车产品数量
			if($shopinfo['数量']!=$num)
			{
				M($sale_shop->name.'购物车')->where(array('id'=>$shopinfo['id']))->save(array('数量'=>$num));
			}
			$all_price+=$pro_info[$sale_shop->productMoney]*$num;
			$all_pv+=$pro_info['PV']*$num;
			$all_zongliang+=$pro_info['重量']*$num;
		}
		M()->commit();
		$buymoneys = $all_price;
		//判断是否有折扣
		$discount=$sale_shop->getDiscount($this->userinfo);
		$all_price = $all_price*$discount;
		$this->assign('discount',$discount);
		//购物金额
		$this->assign("buymoneys",$buymoneys);
		//折后价格
		$this->assign('all_price',$all_price);

		$this->assign("logistic",$sale_shop->logistic);
		$this->assign('sale',$sale_shop);
		$this->assign('userinfo',$this->userinfo);
		$this->assign('all_pv',$all_pv);
		$this->assign('all_zongliang',$all_zongliang);
		$this->assign('shop',$sale_shop->fromNoName);
		$accbanks=X('accbank@'.$sale_shop->accBank)->getcon("bank",array("name"=>""));
		$banks=array();
		foreach($accbanks as $accbank){
			$banks[] = X('fun_bank@'.$accbank['name']);
		}
		$this->assign('banks',$banks);
		$this->display();
    }
    /**
    +----------------------------------------------------------
    * 购物车产品消费保存
    +----------------------------------------------------------
    */
	public function buySave(sale_shop $sale_shop)
	{
	//防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
		B('XSS');
		set_time_limit(1800);
		ini_set('memory_limit','500M');
		M()->startTrans();
		if(!$sale_shop->use){
			echo "<script>alert('没有权限');</script>";
			die;
		}
		
		if($sale_shop->extra && (I("post.country/s")=='' || I("post.province/s")=='' || I("post.city/s")=='' || I("post.county/s")=='' || I("post.town/s")=='' || I("post.reciver/s")=='' || I("post.address/s")=='' || I("post.mobile/s")=='')){
			$this->error("请完善收货信息");
		}
		$res = M($sale_shop->name.'购物车')->where(array('编号'=>$this->userinfo['编号']))->select();
    	if(!$res){
    		$this->error("购物车中没有产品",__URL__."/buy_shop:".__XPATH__);
    	}
		//判断库存，生成产品数组
		$productNum=array();
		foreach($res as $v){
			$num=abs(intval($v['数量']));
			
			//查询此产品的名称和编码
			$pro_info = M($sale_shop->productName)->where(array('id'=>$v['产品id']))->find();
			if($num>$pro_info['可订购数量']){
				$this->error("您选择的产品名称为 {$pro_info['名称']} 的库存数量只有{$pro_info['可订购数量']},请修改购物车的数量");
			}
			$productNum[$v['产品id']]=$num;
		}
		$_POST['productNum']=$productNum;  	
		$checkResult = $sale_shop->getValidate(I("post."));   //自动验证
		if($checkResult['error']){
			$errorStr = '';
			foreach($checkResult['error'] as $error){
				$errorStr .= $error . '<br/>';
			}
			$this->error($errorStr);
		}else{   
			$rswhere=$sale_shop->iswhere($this->userinfo);
			if($rswhere !== true){
				$this->error($rswhere);
			}
			$_POST['userid']=$this->userinfo['编号'];
			$return = $sale_shop->buy(I("post."));
			
			if(gettype($return)=='string')
			{
				$this->error($return);
			}
			M()->commit();
		    $this->success('订购成功',__GROUP__.'/Sale/productmysale');
		}
	}
}
?>