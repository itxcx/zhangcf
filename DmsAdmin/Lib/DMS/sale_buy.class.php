<?php
	class sale_buy extends sale{
	    public $money=0;		//当setmoney为false时的订单金额
		public $minMoney=0;		//最小报单金额
		public $maxMoney=0;		//最大报单金额
		public $extra = false;  //是否填写物流信息
		public $lockMe = true;  //是否锁定报单人编号为自己,不允许填写报单人编号
		public $setMoney = false;//是否允许设置报单金额
		public $number = 1;		//单数
		//此订单扣谁的钱
		public $accstr='编号';
		public $accview='';//审核人（谁能看到此订单）
		public function iswhere($user){
			$cons=$this->getcon("where",array("val"=>"","msg"=>""));
			foreach($cons as $con)
			{
				if(transform($con['val'],$user)){
					if($con['msg']!=""){
					  return $con['msg'];
					}else{
					  return "条件不符和";
					}
				}else{
					continue;
				}
			}
			return true;
		}
		public function buy($data = array()){
			//扣款的设置
			if($this->showRatio){
				$data['paycons']=array();
				$accbankObj=X("accbank@".$this->accBank);
				$accRatioary=$accbankObj->getcon("bank",array("name"=>"","minval"=>"0%","maxval"=>'100%',"extra"=>false),true);
				foreach($accRatioary as $acckey=>$accRatio){
					if($accRatio['extra']){
						$val=$accRatio['maxval'];
					}else{
						$val=$data['accval'][$acckey];
					}
					if(strstr($val,"%"))
					{
						$data['paycons'][$accRatio['name']]=$val."%";
					}
					else
					{
						$data['paycons'][$accRatio['name']]=$val;
					}
				}
			}
			$buydata = $this ->getBuyData($data);
			$udata = $buydata['udata'];
			$sdata = $buydata['sdata'];
			M('会员')->where('id<0')->lock(true)->count();
			/*付款审核*/
			$accret=$this->accbank($sdata,$data,$udata);
			if($accret!==true)
			{
				return $accret;
			}
			/*付款审核完成*/
			$rs=M('报单')->add($sdata);
			if(!$rs)
			{
				M()->rollback();
				return '订单插入失败';
			}
			//更新会员资料
			if($this->extra){
				$zlary=array();
				if($sdata['收货国家']!='' && $udata['国家']=='') { $zlary['国家']=$sdata['收货国家'];}
				if($sdata['收货省份']!='' && $udata['省份']=='') { $zlary['省份']=$sdata['收货省份'];}
				if($sdata['收货城市']!='' && $udata['城市']=='') { $zlary['城市']=$sdata['收货城市'];}
				if($sdata['收货地区']!='' && $udata['地区']=='') { $zlary['地区']=$sdata['收货地区'];}
				if($sdata['收货街道']!='' && $udata['街道']=='') { $zlary['街道']=$sdata['收货街道'];}
				if($sdata['收货地址']!='' && $udata['地址']=='') $zlary['地址']=$sdata['收货地址'];
				if($sdata['收货人']!='' && $udata['收货人']=='') $zlary['收货人']=$sdata['收货人'];
				if($sdata['联系电话']!='' && $udata['移动电话']=='') $zlary['移动电话']=$sdata['联系电话'];
				if(count($zlary)>0){
					M('会员')->where(array("id"=>$udata['id']))->save($zlary);
				}
			}
			$sdata['id']=$rs;
			$product = $buydata['productdata'];
			if($product){
				foreach($product as $k=>$productdata){
					$productdata['报单id'] = $sdata["id"];
					M('产品订单')->add($productdata);
				}
			}
			if($sdata['报单状态'] == '已确认'){
				$udata = M('会员')->find($udata['id']);
				$this->runconfirm($udata,$sdata,$product);
			}
			return true;
		}

		//获得重复消费用户信息 订单信息
		public function getBuyData($data){
			if($this->lockMe && $this->user !='admin'){
				$udata = M("会员")->where(array("编号"=>$_SESSION[C('USER_AUTH_NUM')]))->find();
			}else{
				$udata = M('会员')->where(array('编号'=>trim($data['userid'])))->find();
			}
			$sdata=array();
			$this->getFromInfo($sdata,$data,$udata);
			$confirm         =isset($data['confirm']) ? $data['confirm'] : $this->confirm;
			$sdata['userid']  =$udata['id'];
			$sdata['编号']    =$udata['编号'];
			
			$sdata['购买日期']=systemTime();
			$sdata['到款日期']=$confirm ? systemTime():0;
			$sdata['报单状态']=$confirm ? "已确认":"未确认";
			$sdata['报单类别']=$this->name;
			$sdata["byname"]  =$this->byname;
			if($this->extra == true){
				$sdata['收货国家']=$data['country'];
				$sdata['收货省份']=$data['province'];
				$sdata['收货城市']=$data['city'];
				$sdata['收货地区']=$data['county'];
				$sdata['收货街道']=$data['town'];
				$sdata['收货人']  =$data['reciver'];
				$sdata['收货地址']=$data['address'];
				$sdata['联系电话']=$data['mobile'];
				if(adminshow('sale_sendtype'))$sdata['发货方式']=$data['sendtype'];
			}else{
				$sdata['收货国家']=$udata['国家'];
				$sdata['收货省份']=$udata['省份'];
				$sdata['收货城市']=$udata['城市'];
				$sdata['收货地区']=$udata['地区'];
				$sdata['收货街道']=$udata['街道'];
				$sdata['收货人']  =$udata['收货人'];
				$sdata['收货地址']=$udata['地址'];
				$sdata['联系电话']=$udata['移动电话'];
			}
			if(isset($data['setMoney'])){
				$sdata['报单金额']=$data['setMoney'];
			}else{
				$saleMoneys = $this->getSaleMoneys($data);
				$sdata['报单金额']=$saleMoneys['money'];
			}
			$sdata['实付款']=$sdata['报单金额'];
			
			//产品
			if($this->productName){
				$data['productNum'] = isset($data['productNum'])?$data['productNum']:array();
				$productObj = X('product@'.$this->productName);
				if($productObj){
					$productdata = $productObj -> setField($sdata,$data['productNum'],$this);
				}else{
					//根据提交的产品数据形成订单
					$productdata = $data['product'];
					$sdata['购物金额']=$data['productCountMoney'];
					$sdata['产品'] =1;
				}
			}
			$sdata['实付款'] = $this->getPayMoney($data,$sdata);
			$sdata["accbank"]="";$sdata["accokstr"]="";
			//生成支付的数据
			if(isset($data['paycons'])){
				$accbankObj = X("accbank@".$this->accBank);
				if(isset($accbankObj)){
					$sdata["accbank"]=$accbankObj->makejson($data['paycons']);
				}
			}
			$productdata = isset($productdata) ? $productdata : null;
			
			return array('udata'=>$udata,'sdata'=>$sdata,'productdata'=>$productdata);
		}
		public function getValidate($data_post=array(),$ajax=false){
			$ret = array();
			if($this->setMoney)
			{
				$ret[] = array('setMoney','require','未填写报单金额！',1);
				$ret[] = array('setMoney','double','报单金额为数字！',1);
				if($this->maxMoney!=0){
					$ret[] = array('setMoney',$this->minMoney.','.$this->maxMoney,'您输入的报单金额必须大于等于'.$this->minMoney.'并小于等于'.$this->maxMoney.'！',1,'between');
				}else{
					$ret[] = array('setMoney',$this->minMoney.',9999999','您输入的报单金额必须大于等于'.$this->minMoney.'！',1,'between');
				}
			}
			if($this->setNumber){
				$ret[] = array('setNumber','require',L('单数不能为空'),1);
				$ret[] = array('setNumber',array($this,'validateInt'),L('单数必须为大于0的整数'),1,'function');
			}
			//产品
			if($this->productName){
				$productObj = X('product@'.$this->productName);
				$productObj -> formVerify($ret,$this);
			}
			if($this->fromNoName!=''){
				$ret[] = array('shop','require',L($this->fromNoName.'不能为空'),1);
			}
			$buydata = $this->getBuyData($data_post);
			if(!$this->lockMe){
				//$ret[] = array('userid','require',L($this->parent()->name.'编号不能为空'),1);
				if(!isset($buydata['udata'])){
					$ret[] = array('userid',array($this,'returnFalse'),L($this->parent()->name.'编号不存在'),1,'function');
				}
			}
			$this->fromVerify($ret,$data_post);		//服务中心验证
			//lock配置中的验证条件
			$this->lockconVerify($ret,$buydata);
			$ajax=true;
			if($ajax && isset($data_post['postname']))
			{
				foreach($ret as $key=>&$r)
				{
					if($r[0]!==$data_post['postname'])
					{
						unset($ret[$key]);
					}
				}
			}
			
			
			$m=M();
			$m->setProperty("patchValidate",true);
			$m->setProperty("_validate",$ret);
			$error = array();
			if($m->create() === false){
				$error = $m->getError();  //错误信息
			}else{
					if(isset($data_post['postname']) && strpos($data_post["postname"],'shop') !== false && !isset($errs['shop']))
					{
						$where['编号']	= $data_post['shop'];
						$error['shop']=M('会员')->where($where)->getfield('姓名');
					}
			}
			return array('error'=>$error);
		}
		
		public function returnFalse(){
		  	return false;	
		}

		//获得订单金额
		public function getSaleMoneys($data=array()){
			//设定报单单数
			if($this->setNumber){
				$this->number = $data['setNumber'];
			}
			//设定的报单金额
			if($this->setMoney){
				$returnMoney = (float)$data['setMoney']*$this->number;
			}else{
				$returnMoney = $this->money*$this->number;
			}
            if($returnMoney<0){
				$returnMoney=0;
			}
			//报单pv的设定？？？？
			
			return array('money'=>$returnMoney,'pvmoney'=>0);
		}
		public function accPayMoney($attlv,$sale){
			$ret=0;
			$ret=$sale['购物金额'];
			return $ret;
		}
	}
?>