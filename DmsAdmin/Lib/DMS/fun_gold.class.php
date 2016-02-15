<?php
	//货币交易模块
	class fun_gold extends stru
	{
		//此模块不考虑也不支持货币求购挂单的情况，在已付款状态下不可以撤销买单，必须完成购买或者公司仲裁
		
		//交易出售的货币名称，如果不是一个fun_bank则报错
		public $bankName='';
		//如果没有设置，交易完成的货币，进入到$bankname，如果设置，则进入到toBank钱包
		public $toBank='';
		//信誉值同步，如果设置了其他的fun_gold的名称，则本模块将不再有信誉值设置，信誉值图片的显示，以及数值变更，全部由所设定的fun_gold模块的设置为准。
		//被设置的fun_gold模块的creditname不能再做设置否则报错。如果填写了，但是不是一个fun_gold模块。需要报错
		public $creditName='';
		//下列属性都由后台设置不需要再XML配置
		//大盘开关
		public $open = 1;
		//关闭提示语
		public $closeMsg = "";
		//出售开关
		public $sellOpen = 1;
		//股买开关
		public $buyOpen  = 1;
		//会员可以设置的每货币最小出售金额
		public $rmbMin=0;
		//会员可以设置的每货币最大出售金额
		public $rmbMax=0;
		//手续费比例
		public $tax=0;
		//
		public $buyAll=1;
		//未成交购买上限
		public $buyMax=0;
		//未成交下出售挂单上限
		public $sellMax=0;
		//日购买挂单总额
		public $buyDayNum=0;
		//日出售挂单总额
		public $sellDayNum=0;
		//信誉级别图片样式
		public $creditStyle='';
		//默认信誉级别
		public $creditNum=5;
		//是否可以购买部分
		public $buyPart=false;
		//出售挂单强制数量选择，比如100,200,500的选择,如果没有表示数量限制
		public $numSelect='';
		//会员一次性出售数量最小值
		public $numMin=0;
		//会员一次性出售数量最大值
		public $numMax=0;
		//整数设置
		public $intNum=0;
		//买家付款倒计时长
		public $payTime=0;
		//卖家确认倒计时长
		public $confirmTime=0;
		//卖家出售时可以填写的内容
		public $sellInput='开户银行,银行卡号,开户名,联系电话';
		public $epsellInput='';
		//买家汇款时可以填写的内容
		public $buyInput='开户银行,银行卡号,开户名,联系电话';
		public $epbuyInput='';
		/*
		* 设置默认的信誉值
		*/
		public function event_user_verify($user)
		{
			M("会员")->where(array("编号"=>$user['编号']))->save(array($this->name."信誉"=>$this->creditNum));
		}
		public function event_sysclear()
		{
			M()->execute("TRUNCATE TABLE " . 'dms_'.$this->name."购买;");
			M()->execute("TRUNCATE TABLE " . 'dms_'.$this->name.'挂单;');
		}
		/*
		name    开户名
		cardnum 卡号
		tel     电话
    	显示所有的交易记录和各种查询
		仲裁管理：会员交易时遇到问题，可以选择是否需要公司协助处理，处理之后可以选择是否扣特定数量信誉评级或直接封权限。
		杂项
		会员有额外成交数量和成交总额的累计。用于在信誉评级之外作为参考
		管理员可以设置  信誉会员 以及授权额信誉会员在购买其他会员出售的货币时，货币可以直接到账并使用，然后在由信誉会员付款，一般服务中心可以设置为信誉会员。信誉会员的未确认购买交易总额不能超过其授权额。
		*/
		public function checkbank($data,$checkary){
			$error="";
			if(in_array('开户银行',$checkary)){
				if($data['bank']==""){
					$error.="开户银行不能为空</br>";
				}
			}
			if(in_array('银行卡号',$checkary)){
				if($data['zhanghao']==""){
					$error.="银行卡号不能为空</br>";
				}else{
					$repregz=preg_match('/[0-9]$/',$data['zhanghao']);
					if($repregz==0){
						$error.="银行卡号须为数字</br>";
					}
				}
			}
			if(in_array('开户名',$checkary)){
				if($data['huzhu']==""){
					$error.="开户名不能为空</br>";
				}
			}
			if(in_array('联系电话',$checkary)){
				if($data['mobile']==""){
					$error.="联系电话不能为空</br>";
				}
			}
			return $error;
		}
		/*
		* 出售挂单 
		* 出售会员  出售数量 出售价格 出售手续费
		*/
		public function setcompany($user,$selldata){
			//判断设定的bankname是否存在
			if(!X("@".$this->bankName))
			{
				return "未找到".$this->bankName."的fun_bank节点";
			}
			///获取货币余额
			$huobi=M('货币')->where(array('userid'=>$user['id']))->find();
			if($selldata['num']+$selldata['tax']>$huobi[$this->bankName]){
				return "需要扣除".$this->bankName."(".($selldata['num']+$selldata['tax']).")";
			}
			/*
			**卖出挂单市场 等待购买
			*/
			$order_m=M($this->name."挂单");
			$data=array();
			$data['编号']=$user['编号'];
			$data['时间']=systemTime();
			$data['数量'] =$selldata['num'];
			$data['未成交数量'] =$selldata['num'];
			$data['手续费']=$selldata['tax'];
			$data['单价']=$selldata['price'];
			$data['状态']="有效";
			$data['开户银行']=$selldata['bank'];
			$data['银行卡号']=$selldata['zhanghao'];
			$data['购买数据']='';
			//$data['开户地址']=$selldata['bankadddress'];
			$data['开户名']=$selldata['huzhu'];
			$data['电话']=$selldata['mobile'];
			$id=$order_m->add($data);
			//扣减货币数据
			bankset($this->bankName,$user['编号'],-$selldata['num']-$selldata['tax'],$this->name."挂单",$this->name."挂单".$selldata['num']);
		}
		/*
		** 购买
		** 购买会员 购买数量 挂单id
		*/ 
		public function buyComOrder($user,$num,$sellid,$type){
			/*
			** 找到卖出的挂单
			*/
			$sellinfo=M($this->name."挂单")->lock(true)->where(array("id"=>$sellid))->find();
			if($sellinfo["未成交数量"]>=$num){
				/*
				* 记录购买记录
				*/
				$data=array(
					"pid"=>$sellinfo['id'],
					"编号"=>$user['编号'],
					"卖家编号"=>$sellinfo['编号'],
					"数量"=>$num,
					"状态"=>"待付",
					"单价"=>$sellinfo['单价'],
					"购买时间"=>systemTime(),
					"卖家申请说明"=>''
				);
				$buyid=M($this->name."购买")->add($data);
				/*
				* 更新买家的挂单信息 购买的数据
				*/
				$selldata=array(
					"购买数据"=>$sellinfo['购买数据']==""?''.$buyid:$sellinfo['购买数据'].",".$buyid,
					"未成交数量"=>$sellinfo['未成交数量']-$num,
					"成交中数量"=>$sellinfo['成交中数量']+$num
				);
				M($this->name."挂单")->where(array("id"=>$sellinfo['id']))->save($selldata);
				return true;
			}else{
				return "挂单出售量不足";
			}
		}
		/*
		** 汇款 
		** 购买记录的id 
		*/
		public function givebank($user,$data){
			//更新购买信息
			$buyinfo=M($this->name."购买")->lock(true)->where(array("id"=>$data['id']))->find();
			if($buyinfo){
				$buyinfo['汇款金额']=$data['money'];
				$buyinfo['开户银行']=$data['bank'];
				$buyinfo['银行卡号']=$data['zhanghao'];
				$buyinfo['开户名']=$data['huzhu'];
				$buyinfo['电话']=$data['mobile'];
				$buyinfo['状态']="已付";
				$buyinfo['汇款时间']=$data['remtime'];
				$buyinfo['付款时间']=systemTime();
				M($this->name."购买")->save($buyinfo);
				return true;
			}else{
				return "未找到购买记录";
			}
		}
		/*
		** 买家审核
		** 购买记录的id 
		*/
		public function accokTrad($user,$buyinfo){
			$buyinfo=M($this->name."购买")->lock(true)->where(array("id"=>$buyinfo['id']))->find();
			//更新购买记录信息
			$buydata=array(
				"确认时间"=>systemTime(),
				"状态"=>"完成"
			);
			if($buyinfo['状态']=="完成"){
				return "订单已完成";
			}else if($buyinfo['状态']!="已付"){
				return "订单未支付";
			}
			M($this->name."购买")->where(array("id"=>$buyinfo['id']))->save($buydata);
			//更新会员的信息
			$bankName=$this->toBank!=""?$this->toBank:$this->bankName;
			bankset($bankName,$buyinfo['编号'],$buyinfo['数量'],$this->name."卖家审核",$this->name."卖家审核得到".$buyinfo['数量'],$user['编号']);
			M("会员")->where(array("编号"=>$buyinfo['编号']))->setInc($this->name."累计购买",$buyinfo['数量']);
			$sellinfo=M($this->name."挂单")->lock(true)->where(array("id"=>$buyinfo['pid']))->find();
			//更新挂单数据
			$selldata=array(
				"成交中数量"=>$sellinfo['成交中数量']-$buyinfo['数量'],
				"已成交数量"=>$sellinfo['已成交数量']+$buyinfo['数量'],
			);
			if($selldata["成交中数量"]==0 && $sellinfo['未成交数量']==0){
				$selldata["状态"]="完成";
			}
			M($this->name."挂单")->where(array("id"=>$sellinfo['id']))->save($selldata);
			M("会员")->where(array("编号"=>$sellinfo['编号']))->setInc($this->name."累计出售",$buyinfo['数量']);
			return true;
		}
		/*
		** 撤销挂单
		** 将未交易的挂单货币撤回推给卖家
		** 挂单id 
		*/
		public function cancelSell($sellid){
			$sellinfo=M($this->name."挂单")->lock(true)->where(array("id"=>$sellid))->find();
			if($sellinfo['未成交数量']<=0){
				return "挂单已全部出售";
			}
			if($sellinfo['成交中数量']>0){
				return "有".$sellinfo['成交中数量']."正在成交中，请成交完成后撤销";
			}
			//退回挂单的货币
			$backdata=array(
				"撤销数量"=>$sellinfo['未成交数量'],
				"未成交数量"=>0,
				"状态"=>"撤销"
			);
			//退回手续费
			$backtax=($sellinfo['手续费']/$sellinfo['数量'])*$sellinfo['未成交数量'];
			bankset($this->bankName,$sellinfo['编号'],$sellinfo['未成交数量'],"卖家撤销".$this->name,"卖家撤销".$this->name."返回未出售".$sellinfo['未成交数量'],$sellinfo['编号']);
			bankset($this->bankName,$sellinfo['编号'],$backtax,$this->name."卖家撤销","卖家撤销".$this->name."返回未出售的手续费".$backtax,$sellinfo['编号']);
			M($this->name."挂单")->where(array("id"=>$sellid))->save($backdata);
			return true;
		}
		/*
		** 购买取消
		** 所购买的货币退回到挂单中
		*/
		public function cancelBuy($buyid){
			$buyinfo=M($this->name."购买")->lock(true)->where(array("id"=>$buyid))->find();
			if($buyinfo["状态"]!="待付"){
				return "已汇款支付，不允许取消";
			}
			$sellinfo=M($this->name."挂单")->lock(true)->where(array("id"=>$buyinfo['pid']))->find();
			if(!$sellinfo){
				return "未找到出售信息";
			}
			$repstr=$buyinfo['id']."";
			if(count(explode(",",$sellinfo['购买数据']))>1){
				$repstr.=",";
			}
			$sellinfo['购买数据']=str_replace($repstr,"",$sellinfo['购买数据']);
			//增加出售的未成交数量，减少成交中数量
			$sellinfo['未成交数量']=$sellinfo['未成交数量']+$buyinfo['数量'];
			$sellinfo['成交中数量']=$sellinfo['成交中数量']-$buyinfo['数量'];
			M($this->name."挂单")->save($sellinfo);
			M($this->name."购买")->where(array("id"=>$buyid))->save(array("状态"=>"取消"));
			return true;
		}
		/*
		** 仲裁操作  仲裁买家：收回卖出放置于挂单；仲裁卖家：确认买入订单，充值到买家手中。
		*/
		public function arbitrate($username,$buyid,$num,$zccontent){
			//仲裁的会员
			if($username!=""){
				$user=M("会员")->lock(true)->where(array("编号"=>$username))->find();
				if(!$user){
					return "未找到仲裁会员";
				}
				$user[$this->name."信誉"]-=$num;
				//仲裁的购买信息
				$buyinfo=M($this->name."购买")->lock(true)->where(array("id"=>$buyid))->find();
				if($buyinfo['编号']==$username){
					$buyinfo['状态']="仲裁买家";
				}else if($buyinfo['卖家编号']==$username){
					$buyinfo['状态']="仲裁卖家";
				}else{
					return "非法仲裁";
				}
				M("会员")->save($user);
				$buyinfo['仲裁扣分']=$num;
				$buyinfo['仲裁说明']=$zccontent;
				M($this->name."购买")->save($buyinfo);
				//如果是仲裁买家需要把买家购买的撤销到挂单中
				if($buyinfo['状态']=="仲裁买家"){
					$sellinfo=M($this->name."挂单")->lock(true)->where(array("id"=>$buyinfo['pid']))->find();
					$sellinfo['未成交数量']=$sellinfo['未成交数量']+$buyinfo['数量'];
					$sellinfo['成交中数量']=$sellinfo['成交中数量']-$buyinfo['数量'];
					M($this->name."挂单")->save($sellinfo);
				}
				//如果是仲裁卖家需要审核充值到买家手中
				if($buyinfo['状态']=="仲裁卖家"){
					//更新会员的信息
					$bankName=$this->toBank!=""?$this->toBank:$this->bankName;
					bankset($bankName,$buyinfo['编号'],$buyinfo['数量'],$this->name."卖家违规",$this->name."卖家违规,后台审核得到".$buyinfo['数量'],$user['编号']);
					M("会员")->where(array("编号"=>$buyinfo['编号']))->setInc($this->name."累计购买",$buyinfo['数量']);
					$sellinfo=M($this->name."挂单")->lock(true)->where(array("id"=>$buyinfo['pid']))->find();
					//更新挂单数据
					$selldata=array(
						"成交中数量"=>$sellinfo['成交中数量']-$buyinfo['数量'],
						"已成交数量"=>$sellinfo['已成交数量']+$buyinfo['数量']
					);
					if($selldata["成交中数量"]==0 && $sellinfo['未成交数量']==0){
						$selldata["状态"]="完成";
					}
					M($this->name."挂单")->where(array("id"=>$sellinfo['id']))->save($selldata);
					M("会员")->where(array("编号"=>$sellinfo['编号']))->setInc($this->name."累计出售",$buyinfo['数量']);
				}
			}else{
				return "仲裁会员编号不能为空";
			}
		}
		/*
		* 充值会员信誉
		*/
		function recharge($username,$chargenum){
			$user=M("会员")->lock(true)->where(array("编号"=>$username))->find();
			if(!$user){
				return "无效会员";
			}
			M("会员")->where(array("编号"=>$username))->setInc($this->name."信誉",$chargenum);
			return true;
		}
	}
?>