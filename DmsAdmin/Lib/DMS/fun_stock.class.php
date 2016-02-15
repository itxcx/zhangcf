<?php
	class fun_stock extends stru
	{
		public $use=true;   	//该标签是否可用
		/*
		发行相关
		*/
		public $stockLimit=true;//由公司发行总量，false表示不限制会员认购量
		public $stockPrice=0.1;//公司默认发行价
		public $buyfComStock=true;
		public $tradeBank='';  //购买股票所需要的货币
		public $buyBack=false;//公司回收
		//会员购买数量限制
		public $stockMinint=100;  //股票交易的最小整数倍
		
		public $stockInputPrice=false;  //会员定价
		
		public $splitStart=true;//是否开启拆分功能（显示拆分列表）
		public $adminSell=false;//是否开启管理员一键挂单功能（显示一键挂单操作）
		//交易操作的开关
		public $stockClose=false;  //股票市场关闭
		public $buyDisp=false;
		public $stockBuybutton=true; //买入开关
		public $stockSellbutton=true; //卖出开关
		public $sellDisp=false;
		public $stockBuycancel=true; //买入撤销开关
		public $stockSellcancel=true; //卖出撤销开关
		
		public $stockMax=array();//封顶值
		
		public $stockAutosell=array();//自动挂单封顶值
		
		public $stockSec=0;
		//股票起始默认价格
		public $sellauto=false;
		public $stockStartPrice=0.1;
		
		//默认拆分倍数
		public $SplitNum=0.5;
		//是否允许手动修改拆分的倍数
		public $SplitEdit=false;
		/*
			股票价格计算公式
			写法说明：股价自增公式 已知数量 以及 数量跨度  价格增长幅度
									取最小整数 乘以 增长幅度
					  股价自增使用量公式 已知 价格上涨跨度（abs(拆分的倍数*发行价-增长价)） 价格增长幅度  数量跨度
					  				获取到价格增长的使用量
		*/
		
		
		public $PriceFromuUser="floor([成交量]/1000)*0.001";//会员之间的交易的股价自增公式
		public $PriceFromCompany="floor([认购总量]/10000)*0.001";//会员购买公司的股价自增公式
		//
		public $NumFromUser="([增长价]*0.001)*1000";//会员之间的交易的股价自增使用量公式
		public $NumFromCompany="([增长价]*0.001)*10000";//会员购买公司的股价自增使用量公式
		
		public $priceLen=4;
		public $decimalLen=4;
		const TRADE_SELL = "卖出";
		const TRADE_BUY = "买入";
		private $logstr='';
		
		//自动购买股票
		public function event_valadd($user,$val,$option){
			//判断可购买的股票数量
			$num=floor($val/$this->getPrice());
			if($num>0){
				$this->setcompany($user['编号'],$this->getPrice(),$num,'买入');
			}
		}
		//获得股票价格
		public function getPrice()
		{
			$info=M($this->name."发行")->order("日期 desc")->find();
			//计算当前的价格
			if($info){
				//会员间交易产生的价格增长
				if($this->PriceFromuUser!=""){
					$riseprice1=transForm($this->PriceFromuUser,$info);
				}else{
					$riseprice1=0;
				}
				//会员认购公司交易产生的价格增长
				if($this->PriceFromCompany!=""){
					$riseprice2=transForm($this->PriceFromCompany,$info);
				}else{
					$riseprice2=0;
				}
				$nowprice=$info['发行价']+$riseprice1+$riseprice2;
			}else{
				$nowprice=$this->stockStartPrice;
				//默认发行股票
				$issuedata=array(
					"发行量"=>0,
					"发行价"=>$this->stockStartPrice,
					"余量"=>0,
					"发行总量"=>0,
					"认购总量"=>0,
					"回购总量"=>0,
					"日期"=>systemTime()
				);
				M($this->name."发行")->add($issuedata);
			}
			return $nowprice;
		}
		//判断交易账户余额
		public function tradeMoney($money,$user)
		{
			if($user[$this->tradeBank]<$money){
		 		return false;
			}
			return true;
		}
		//挂单记录
		public function setcompany($username,$price,$num,$type)
		{
			//增加了挂单记录
			$order_m=M($this->name."市场");
			$data=array(
				"编号"=>$username,
				"挂单价"=>$price,
				"挂单总量"=>$num,
				"剩余量"=>$num,
				"成交量"=>0,
				"挂单时间"=>systemTime(),
				"类型"=>$type,
				"状态"=>"挂单中",
				"tradeinfo"=>serialize(array())
			);
			//返回挂单的id
			$id=$order_m->add($data);
			//剩余购买量
			$surplus = $num;
			//如果优先购买公司发售股,下面有段一样的代码，是后购买
			$uparray=array();
			if($type == self::TRADE_BUY && $this->getPrice()<=$price){
				//购买公司原始股
				$comnum=$this->isbuyComStock($username,$surplus,$price,$uparray);
				//剩余交易量
				$surplus-=$comnum;
				//交易记录
				$this->setdetail("公司原始股",$username,0,$id,$comnum,$price,"购买公司发行股".$comnum."股，".$price."/股");
			}
			if($surplus>0){
				//得到匹配交易信息
				$where=array(
					'挂单价'=>array(($type=='卖出' ? 'egt' :'elt') ,$price),
					'类型'=>$type=='卖出'? '买入' : '卖出',
					'编号'=>array('neq',$data['编号']),
					'剩余量'=>array('gt',0),
					'状态'=>'挂单中',
					);
				$lists=$order_m->order(($type==1 ? "挂单价 desc," : "挂单价 asc,") . "挂单时间 asc")
							   ->where($where)
							   ->select();
				//定义交易期间的剩余成交量
				foreach((array)$lists as $list)
				{
					$diffmoney=0;  //购买股票时有可能购买比定价低的股票 故应该返还
					if($surplus<=0) break;
					//得到当前会员的股券交易最大量
					$thisnum = $list['剩余量']<$surplus ? $list['剩余量'] : $surplus;
					//当前股券每股价格基准
					$thisprice=$type == self::TRADE_BUY ? $list['挂单价'] : $price;
					//当前会员的交易额
					$thismoney=$thisprice * $thisnum;
					//卖出得到现金金额   当前循环会员的买入，得到股票数量
					if($type == self::TRADE_SELL)
					{
						//定义卖出会员 与 买入会员
						$selluser=$username;//卖出
						$buyuser=$list['编号'];//买入
						$sellid=$id;//卖出市场id
						$buyid=$list['id'];//买入市场id
						//所查找的订单为买入单  订单价格-交易价 为差价  需返款
						if($thisprice<$list['挂单价']){
							$diffmoney=($list['挂单价']-$thisprice)*$thisnum;
						}
					} 
					//买入得到股券数量   当前循环会员的卖出 得到现金金额
					if($type == self::TRADE_BUY)
					{
						$selluser=$list['编号'];//卖出
						$buyuser=$username;//买入
						$sellid=$list['id'];//卖出市场id
						$buyid=$id;//买入市场id
						//所查找的订单为卖出单  提交价格-交易价 为差价  需返款
						if($thisprice<$price){
							$diffmoney=($price-$thisprice)*$thisnum;
						}
					}
					//获取交易的信息数组
					$tradeinfo=unserialize($list['tradeinfo']);
					$tradeinfo[]=array(
						'name'=>$username,
						'price'=>$thisprice,
						'num'=>$thisnum,
						'time'=>systemTime(),
						'money'=>$thismoney
					);
					$updata=array(
						'剩余量'=>$list['剩余量']-$thisnum,
						'成交量'=>$list['成交量']+$thisnum,
						'tradeinfo'=>serialize($tradeinfo)
					);
					if($updata['剩余量']==0){
						$updata['状态']="已成交";
					}
					$order_m->where(array('id'=>$list['id']))->save($updata);
					unset($updata);
				   	//返款  买入便宜的
				   	if($diffmoney>0){
					  bankset($this->tradeBank,$buyuser,$diffmoney,L('买入返款'),"买入ID".$buyid.",卖出ID".$sellid);
					}
					//会员表中$username的 现金 $this->cashBank  账户 + $thismoney并增加一个现金账户交易明细
					$memo=$this->parent()->name.$buyuser."花费买入".$thismoney."元".$selluser."卖出股票的".$thisnum."股,每股".$thisprice;
					//交易记录
					$this->setdetail($selluser,$buyuser,$sellid,$buyid,$thisnum,$thisprice,$selluser."卖出".$thisnum."股，每股".$thisprice);
					//卖出所得货币到账
					$this->selldo($selluser,$buyuser,$thismoney,$sellid);
					// 并增加一个股票账户交易明细
					$this->setrecord($buyuser,$thisprice,$thisnum,$memo,"买入");
					//更新走势图
					$this->uptrend(array("价格"=>$thisprice,"认购量"=>$num,'成交量'=>$thisnum,'成交金额'=>$thismoney));
					$surplus-=$thisnum;
					//明细
					$uparray[]=array(
						'name'=>$list['编号'],
						'price'=>$thisprice,
						'num'=>$thisnum,
						'time'=>systemTime(),
						'money'=>$thismoney
					);
				}
			}
			//如果还有没购买的则购买公司发售股,公司股不优先
			if($surplus>0){
				if($type == self::TRADE_BUY && $this->getPrice()<=$price){
					//购买公司原始股
					$comnum=$this->isbuyComStock($username,$surplus,$price,$uparray,false);
					$surplus-=$comnum;
					//交易记录
					$this->setdetail("公司原始股",$username,0,$id,$comnum,$price,"购买公司发行股".$comnum."股，".$price."/股");
				}
			}
			$updata=array(
				"tradeinfo"=>serialize($uparray),
				"剩余量"=>$surplus,
				"成交量"=>$num-$surplus
			);
			if($surplus==0){
				$updata['状态']="已成交";
			}
			$order_m->where(array('id'=>$id))->save($updata);
		}
		//购买公司发行
		public function isbuyComStock($userid,$surplus,$price,&$uparray,$buy=true){
			$comnum=0;
			if($this->getatt('buyfComStock')==$buy){
				//实际购买公司发行股数量
				$comprice=$price;
				$comnum=$this->buyComStock($surplus,$comprice);
				if($comnum>0){
					$commoney=$comnum*$comprice;
					$this->setrecord($userid,$comprice,$comnum,"购买公司发行股".$comnum."股，".$comprice."/股","买入");
					//更新走势图
					$this->uptrend(array("价格"=>$comprice,"认购量"=>$comnum,'成交量'=>$comnum,'成交金额'=>$commoney));
					//更新价格
					$this->getPrice();
					//返款
					if($price>$comprice){
						$trmoney=($price-$comprice)*$comnum;
						bankset($this->tradeBank,$userid,$trmoney,L('买入返款'),"自动买入比预期价格便宜的公司发行股返款".$trmoney);
					}
					$uparray[]=array(
						'name'=>'公司',
						'price'=>$comprice,
						'num'=>$comnum,
						'time'=>systemTime(),
						'money'=>$commoney
					);
				}
			}
			return $comnum;
		}
		//可以购买的公司发行股
		public function buyComStock($num,$comprice)
		{
            //取得总发行量
            $allinfo=M($this->name."发行")->order("日期 desc")->find();
			//当总发行量不受限
			if($this->stockLimit==false){
				$allinfo['余量']=($allinfo['余量']-$num)>0?($allinfo['余量']-$num):0;
			}else{
				//判断发行量是否满足
				if($allinfo['余量']<=0){
					return 0;
				}
				if($allinfo['余量']<=$num){
					$num=$allinfo['余量'];
				}
				$allinfo['余量']=$allinfo['余量']-$num;
			}
			M($this->name."发行")->save($allinfo);
			return $num;
		}
		//增加股票交易明细
		public function setrecord($userid,$price,$num,$memo,$type,$usersave=true)
		{
			$userinfo=M($this->name)->where("编号='".$userid."'")->find();
			$data=array();
			$data['编号']   =$userid;
			$data['价格']  	=$price;
			$data['数量']   =$num;
			$data['时间']	=systemTime();
			$data['备注']   =$memo;
			$data['类型']   =$type;
			if($usersave){
				$data['余量']   =$userinfo['数量']+$data['数量'];
				$userinfo['数量']+=$data['数量'];
				M($this->name)->save($userinfo);
			}
			if($data['数量']<>0){
				M($this->name."明细")->add($data);
			}
		}
		//会员股票达到一定量后自动挂单
		public function sellauto(){
			//判断当前
		}
		//会员股票卖出后的操作
		/*
		$money   为卖出股票获得的金额
		*/
		public function selldo($selluser,$buyuser,$allmoney,$sellid='')
		{
			//卖出金额
			if($allmoney<=0)
				return;
			//股票的卖出后货币操作节点 val 发到货币的比例 tax 扣除的手续费 实际金额=发到货币的比例金额-手续费
			$cons=$this->getcon("selldo",array('val'=>'100%','tax'=>'0%','bank'=>''),true);
			if(empty($cons))  return;
			//卖出会员信息
			$user=M('会员')->where(array('编号'=>$selluser))->find();
			//循环
			foreach($cons as $con)
			{
				$thistax=getnum($allmoney,$con['val']);
				$thismoney=getnum($allmoney,$con['tax']);
				if($thistax<$thismoney){
					$memo=$this->byname."卖出所得,交易单号".$sellid.",购买人".$buyuser;
					if($thistax>0)
					{
						$memo.="：手续费".$thistax;
					}
					bankset($con['bank'],$selluser,$thismoney-$thistax,$this->byname."卖出所得",$memo);
				}
			}
 		}
		//更新走势图 array('价格'=>,'认购量'=>,'成交量'=>,'成交金额'=>)
        public function uptrend($data)
		{
			$today=strtotime(date("Y-m-d",systemTime()));
			if(!empty($data)){
				$where=array();
				$where['日期']=$today;
				$todayinfo=M($this->name."走势")->where($where)->find();
				if(isset($data['认购量'])){
					$data['认购量']+=$todayinfo['认购量'];
				}
				if(isset($data['成交量'])){
					$data['成交量']+=$todayinfo['成交量'];
				}
				if(isset($data['成交金额'])){
					$data['成交金额']+=$todayinfo['成交金额'];
				}
				if($todayinfo){
					if($todayinfo['价格']>$data['价格']){
						$data['价格']=$todayinfo['价格'];
					}
					M($this->name."走势")->where($where)->save($data);
				}else{
					$data['日期']=$today;
					M($this->name."走势")->add($data);
				}
			}
		}
		//交易明细
		public function setdetail($selluser,$buyuser,$sellid,$buyid,$num,$price,$memo)
		{
			if($num>0){
				$data=array(
					'买入ID'=>$buyid,
					'买入编号'=>$buyuser,
					'卖出ID'=>$sellid,
					'卖出编号'=>$selluser,
					'交易量'=>$num,
					'交易价'=>$price,
					'交易额'=>$num*$price,
					'成交时间'=>systemTime(),
					'备注'=>$memo
				);
				$rs=M($this->name."交易")->add($data);
				//增加股票交易量与交易额
				$this->upStock($num,$price,false);
			}
		}
		//更新股票价格相关
		public function upStock($num,$price,$comStock){
			//取得总发行信息
            $allinfo=M($this->name."发行")->order("日期 desc")->find();
			//更新公司原始股的认购
			if($comStock){
				$data['认购总量']=$allinfo['认购总量']+$num;
				$data['认购金额']=$allinfo['认购金额']+($num*$price);
			}else{
				$data['成交量']=$allinfo['成交量']+$num;
				$data['成交金额']=$allinfo['成交金额']+($num*$price);
			}
			M($this->name."发行")->where(array("id"=>$allinfo['id']))->save($data);
			//更新股价
			$this->upStockPrice($comStock);
		}
		//公司回购会员卖出
		public function buyback($where=array()){
			//查找挂出记录
			if(!isset($where['类型']))
				$where['类型']="卖出";
			if(!isset($where['剩余量']))
				$where['剩余量']=array('gt',0);
			if(!isset($where['状态']))
				$where['状态']='挂单中';
			$alllists=M($this->name."市场")->where($where)->select();
			if(empty($alllists))
				return;
			//执行回购操作
			foreach($alllists as $list){
				//获取交易的信息数组
				$tradeinfo=unserialize($list['tradeinfo']);
				$tradeinfo[]=array(
					'name'=>"公司回购",
					'price'=>$thisprice,
					'num'=>$list['剩余量'],
					'time'=>systemTime(),
					'money'=>$thismoney
				);
				$updata=array(
					'剩余量'=>0,
					'成交量'=>$list['成交量']+$list['剩余量'],
					'tradeinfo'=>serialize($tradeinfo)
				);
				if($updata['剩余量']==0){
					$updata['状态']="已成交";
				}
				M($this->name."市场")->where(array('id'=>$list['id']))->save($updata);
				unset($updata);
				//会员表中$username的 现金 $this->cashBank  账户 + $thismoney并增加一个现金账户交易明细
				$memo="回购".$list['编号']."卖出股票的".$list['剩余量']."股,每股".$thisprice;
				//交易记录
				$this->setdetail($list['编号'],'公司回购',$list['id'],0,$list['剩余量'],$thisprice,$list['编号']."卖出".$list['剩余量']."股，每股".$thisprice);
				//卖出所得货币到账
				$this->selldo($list['编号'],'公司回购',$thismoney,$list['id']);
			}
		}
		//撤销交易市场中的未完成的所有单
		public function cancelall($where=array())
		{
			//查询挂单记录
			$where['剩余量']=array('gt',0);
			$where['状态']=array('eq','挂单中');
			$all=M($this->name."市场")->where($where)->select();
			if(empty($all))
				return;
			//循环挂单记录
			foreach($all as $list)
			{
				$memo="公司撤销挂单";
				if($list['类型']=='卖出'){
					$userinfo=M($this->name)->where(array("编号"=>$list['编号']))->find();
		            //增加股票变动明细
					$data=array();
					$data['编号']   = $list['编号'];
					$data['价格']  	= $list['挂单价'];
					$data['数量']   = $list['剩余量'];
					$data['余量']   = $userinfo['数量']+$list['剩余量'];
					$data['时间']	= systemTime();
					$data['备注']   = $memo;
					$data['类型']   = "撤销挂单";
					M($this->name."明细")->bAdd($data);
					//更新账户的余额
					$userdata['id']=$userinfo['id'];
					$userdata['数量']=$userinfo['数量']+$list['剩余量'];
					M($this->name)->bSave($userdata);
				}
				if($list['类型']=='买入'){
					//剩余支付金额
					$money=$list['挂单价']*$list['剩余量'];
					if($money>0){
						//返回预支付金额
						bankset($this->tradeBank,$list['编号'],$money,$this->name."撤单",$memo);
					}
				}
				//更新状态
				$update['id']=$list['id'];
				$update['状态']='已撤销';
				M($this->name."市场")->bSave($update);
			}
			M($this->name."明细")->bUpdate();
			M($this->name)->bUpdate();
			M($this->name."市场")->bUpdate();
		}
		//更新股票价格
		public function upstockprice($comStock)
		{
			//股价公式无自增长公式 直接返回
		   	if($this->PriceFromuUser=="" && $this->PriceFromCompany==""){
			   return ;
			}
			$info=M($this->name."发行")->order("日期 desc")->find();
			//计算当前的价格
			$nowprice=$this->getPrice();
			//判断拆分 达到拆分倍数 并且自动拆分
			if((($this->SplitNum>1 && ($nowprice/$info['发行价'])>=$this->SplitNum) || ($this->SplitNum<1 && ($nowprice/$info['发行价'])<=$this->SplitNum)) && $this->autoSplit){
				$this->splitstock($this->SplitNum,$info['发行价']*$this->SplitNum,true,$comStock);
			}
		    return;
		}
		//股票拆骨
		public function splitstock($num,$splitPrice,$autoSqlit=false,$comStock=false)
		{
			$splitdata=array();
			//相同价格不能拆分
			if($num==1)
				return;
			//查询会员所有的持有股
			$list=M($this->name)->where(array("数量"=>array('gt',0)))->select();
			if(empty($list))
				return;
			//发行记录
			$info=M($this->name."发行")->order("日期 desc")->find();
			//备注
			$memo="股票拆分,";
			if($num>1){
				$type="股票拆分";
				$memo.="持有增长".($num-1)."倍";
			}else{
				$type="股票反向拆分";
				$memo.="持有缩减".$num."倍，股价上涨";
			}
			$splitdata['拆股前']=$splitPrice;
			$splitdata['拆股后']=$splitPrice/$num;
			$splitdata['拆分倍数']=$num;
			/*
				会员持有数量因为拆分变动
			*/
			foreach($list as $userinfo){
				/*变更数量 倍数小于1时 由于价格上涨 根据价值不变 所以数量需要减少*/
				$addnum=$num>1?$userinfo['数量']*($num-1):$userinfo['数量']*($num-1);
				//增加股票变动明细
				$data=array();
				$data['编号']   = $userinfo['编号'];
				$data['价格']  	= $splitPrice/$num;
				$data['数量']   = $addnum;
				$data['余量']   = $userinfo['数量']+$addnum;
				$data['时间']	= systemTime();
				$data['备注']   = $memo;
				$data['类型']   = $type;
				M($this->name."明细")->bAdd($data);
				//更新账户的余额
				$userdata['id']=$userinfo['id'];
				$userdata['数量']=$userinfo['数量']+$addnum;
				M($this->name)->bSave($userdata);
			}
			M($this->name."明细")->bUpdate();
			M($this->name)->bUpdate();
			//自动拆分
			if($autoSqlit){
				$splitdata['拆分方式']="自动拆分";
				//判断拆股操作是由认购公司还是会员交易
				if($comStock){
					//获取会员交易的价格增量 并清除会员交易量或交易额
					preg_match_all('/\[(.*)\]/U',$this->PriceFromuUser,$truevalsUser,PREG_SET_ORDER);
					$risepriceUser=transForm($this->PriceFromuUser,$info);
					//记录拆股所用数字
					$splitdata[$truevalsUser[1]]=$info[$truevalsUser[1]];
					$info[$truevalsUser[1]]=0;
					//获取认购公司达到拆股价格的数量
					preg_match_all('/\[(.*)\]/U',$this->PriceFromCompany,$truevalsCompany,PREG_SET_ORDER);
					$risedata['增长价']=$splitPrice-$risepriceUser;
					$rengounum=transForm($this->NumFromCompany,$risedata);
					//记录拆股所用数字
					foreach($truevalsCompany as $truevalCompany){
						if(isset($info[$truevalCompany[1]])){
							if(isset($info[$truevalCompany[1]])){
								//记录拆股所用数字
								$splitdata[$truevalCompany[1]]=$rengounum;
								$info[$truevalCompany[1]]-=$rengounum;
								$rengounum=0;
							}else{
								//记录拆股所用数字
								$splitdata[$truevalCompany[1]]=$info[$truevalCompany[1]];
								$info[$truevalCompany[1]]=0;
								$rengounum-=$info[$truevalCompany[1]];
							}
						}
					}
				}else{
					//获取认购公司的价格增量 并清除认购公司量或交易额
					preg_match_all('/\[(.*)\]/U',$this->PriceFromCompany,$truevalsCompany,PREG_SET_ORDER);
					$risepriceCompany=transForm($this->PriceFromCompany,$info);
					foreach($truevalsCompany as $truevalCompany){
						if(isset($info[$truevalCompany[1]])){
							//记录拆股所用数字
							$splitdata[$truevalCompany[1]]=$info[$truevalCompany[1]];
							$info[$truevalCompany[1]]=0;
						}
					}
					//获取会员交易达到拆股价格的数量
					preg_match_all('/\[(.*)\]/U',$this->PriceFromuUser,$truevalsUser,PREG_SET_ORDER);
					$risedata['增长价']=$splitPrice-$risepriceCompany;
					$chjnum=transForm($this->NumFromUser,$risedata);
					foreach($truevalsUser as $truevalUser){
						if(isset($info[$truevalUser[1]])){
							if($info[$truevalUser[1]]>=$chjnum){
								//记录拆股所用数字
								$splitdata[$truevalUser[1]]=$chjnum;
								$info[$truevalUser[1]]-=$chjnum;
								$chjnum=0;
							}else{
								//记录拆股所用数字
								$splitdata[$truevalUser[1]]=$info[$truevalUser[1]];
								$info[$truevalUser[1]]=0;
								$chjnum-=$info[$truevalUser[1]];
							}
						}
					}
				}
			}else{
				$splitdata['拆分方式']="手动拆分";
				//手动拆分操作
				//获取会员交易价格自增数据 将数据清除
				preg_match_all('/\[(.*)\]/U',$this->PriceFromuUser,$truevalsUser,PREG_SET_ORDER);
				foreach($truevalsUser as $truevalUser){
					if(isset($info[$truevalUser[1]])){
						$splitdata[$truevalUser[1]]=$info[$truevalUser[1]];
						$info[$truevalUser[1]]=0;
					}
				}
				//获取认购公司价格自增数据 将数据清除
				preg_match_all('/\[(.*)\]/U',$this->PriceFromCompany,$truevalsCompany,PREG_SET_ORDER);
				foreach($truevalsCompany as $truevalCompany){
					if(isset($info[$truevalCompany[1]])){
						$splitdata[$truevalCompany[1]]=$info[$truevalCompany[1]];
						$info[$truevalCompany[1]]=0;
					}
				}
			}
			//修改拆分后的发行价格
			$info['发行价']=$splitdata['拆股后'];
			//拆股记录
			$splitdata['时间']=systemTime();
			$splitid=M($this->name."拆股")->add($splitdata);
			$info['拆股变动']=$splitid;
			//保存数据
			M($this->name."发行")->save($info);
		}
		//清空数据库
		public function event_sysclear()
		{
	        $model=M();
	        $model->execute('truncate table `dms_'.$this->name.'`');
	        $model->execute('truncate table `dms_'.$this->name.'发行`');
			$model->execute('truncate table `dms_'.$this->name.'市场`');
			$model->execute('truncate table `dms_'.$this->name.'明细'.'`');
			$model->execute('truncate table `dms_'.$this->name.'交易'.'`');
			$model->execute('truncate table `dms_'.$this->name.'拆股'.'`');
			$model->execute('truncate table `dms_'.$this->name.'走势'.'`');
		}
	}
?>