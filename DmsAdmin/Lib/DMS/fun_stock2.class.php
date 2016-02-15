<?php
	/*
	股票基本模块
	此模块适用的需求案例
	第一期发布200万股,起始价格0.1,每认购20万股涨0.01(也可以每2万股涨0.001)
	涨到0.2后自动拆分,价格变为0.1然后再发行400万股.购买40万股涨0.01(或4万涨0.01)
	在此涨到0.2时在拆分,最早购买的20万拆分到80万全部抛出.
	系统购买或者挂单出售.交易80万后涨0.01,第二批20万会继续抛出.在购买完后涨价
	此模块特性.股票认购是根据金额而非数量,当交易量跨越涨价段的时候.会截至涨价临界点生成一次交易,
	剩余金额在按照新价格继续交易,保证不同价格之间的交易量一致,维持稳定性
	
	因为股票可能会存在高并发特性.
	所以不适用于getatt或者setatt方法因为此方法是通过缓存形式存储.不具备实时性
	模块内置了getset和setcon两个方法.用于读取和保存配置信息.
	在此期间对数据表加锁保持每次只会有一个fun_stock2实例在运行
	
	系统中支持这种设定
	<_addval to='电子货币'	val='50%' tax='10'/>
	目前的addval只能到货币,不支持其他模块.tax表示总额的税率
	如目前是收入50%进入,扣除10%,实际为40%.如有不同需要可以自己修改
	
	目前股票模块的限制,不支持主动提前出售.不支持手动拆分.
	系统强制性设置,拆分投资达到四倍以后开始进行回购.
	
	可以实现的功能,价格,涨价的幅度可以在后台可以随时调控.
	
	*/
	class fun_stock2 extends stru
	{
		public $StartPrice = 0.1;
		public $upSkip = 0.01;//每达到一个交易量度的涨价幅度
		public $tradeBank = "";//购买股票所用货币
		public $upNum = 10000;//涨价交易量度
		public $upNumSplit = true;//拆分后涨价交易量是否一起翻倍
		public $backnum = 50;
		public $shouxufei = 0;
		public $splitPrice = 0.2;//达到拆分的值
		public $splitMultiple = 2;//拆分倍数
		public $tax = 0;
		public $Minint = 10;   //股票交易的最小整数倍
		public $priceLen = 3;  //价格小数位
		private $set = null;	 //内部数据缓存
		public $selltype = 1;//卖出操作时，如果为0则进行公司回购，如果为1则表示挂单卖出处理
		public $buy=true;//是否出现会员主动购买的菜单
		public function setCon($setdata)
		{
			//清除缓存
			$this->set=NULL;
			M($this->name.'设置')->where("1=1")->save($setdata);
		}
		public function getSet()
		{
			if($this->set)
			{
				return $this->set;
			}
			$set=M($this->name.'设置')->lock(true)->find();
			if(!$set)
			{
				M($this->name.'设置')->add(array('交易量'=>0));
				$set=M($this->name.'设置')->lock(true)->find();
			}
			$this->set = $set;
			return $set;
		}
		public function getSets(){
			if($this->set)
			{
				return $this->set;
			}
			$set = $this->getSet();
			return $set;
		}
		
		//卖出指定持有记录
		/*
		private function sell($userid)
		{
			//根据ID到持有表中，找到记录，如果未找到，抛异常
			//如果isSell=1;则抛异常
			$jilu = M($this->name.'持有')->field('sum(nownum) as num,nownum,isSell,price')->where(array('isSell'=>0,'编号'=>$userid,'stocknum'=>2))->find();
			if($jilu['num'] != '' && $jilu['isSell'] == 0)
			{
				//保存当前记录isSell=1
				M($this->name.'持有')->where(array('编号'=>$userid,'stocknum'=>2))->save(array('isSell'=>1));
				//进行卖出处理操作
				if($this->selltype==1)
				{
					//在交易表中增加记录
					$sellstock = M($this->name.'持有')->field('sum(nownum) as num')->where(array('isSell'=>0,'编号'=>$userid,'stocknum'=>1))->find();
					M('会员')->where(array('编号'=>$userid))->save(array($this->name=>$sellstock['num'],'出局'=>1));
					M($this->name.'交易')->add(array('价格'=>$jilu['price'],'挂单量'=>$jilu['num'],'剩余量'=>$jilu['num'],'用户编号'=>$userid));
				}	
			}
		}
		*/
		
		
		//$name 用户编号 $money 购买金额 $num购买数量
		public function buy($name,$money,$backout=true)
		{
			//如果$backout=true则表示扣除货币，否则认为是配股进入
			//此程序内只处理购买或者配送，并传出剩余金额，不做扣款处理
			//根据NAME取得用户
			//得到目前价格可以累积的成交价
			$price=$this->getPrice();
			//当前是否产生过交易
			$isbuy=true;
			//存在挂单流程则先尝试购买挂单
			while($isbuy && $money > $this->getPrice())
			{
				$isbuy=false;
				$sell =M($this->name.'交易')->where("剩余量>0")->order('价格 asc')->find();
				if($sell && $money>=$sell['价格'])
				{
					$price   = $this->getPrice();
					//获得最大购买数量
					$buynum  = ((floatval($money)*100) % ($price*100)) ===0 ? ($money/$sell['价格']):intval($money/$sell['价格']);
					$buynum  = (int)($buynum.'');
					//交易量不能超过当前记录的剩余数量
					if($buynum>$sell['剩余量'])
					{
						$buynum=$sell['剩余量'];
					}
					//数量不能超过涨价临界点
					$buynum = $this->getmaxnum($buynum);
					M()->execute("update `dms_".$this->name.'交易` set 剩余量=剩余量-'.$buynum.',交易量=交易量+'.$buynum.' where id='.$sell['id']);
					$logdata=array('pid'=>$sell['id'],'数量'=>$buynum,'编号'=>$sell['编号'],'时间'=>systemTime());
					M($this->name.'交易明细')->add($logdata);
					//得到成交金额
					$prize =$buynum * $sell['价格'];
					//对买家的余额进行扣除
					if($prize>0)
					{
						if($backout)
						bankset($this->tradeBank,$name,-$prize,'以'.$sell['价格'].'的价格购买'.$buynum.'挂单股');
						$isbuy=true;
						$money-=$prize;
					}
					
					//增加买家的持有记录
					$data=array(
						'编号'=>$name,
						//原始数量
						'num'=>$buynum,
						//当前数量
						'nownum'=>$buynum,
						//价格
						'price'=>$sell['价格'],
						//时间
						'addtime'=>systemTime(),
						//备注
						'memo'=>'挂单买入',
						//拆分次数
						'splitnum'=>0,
						//是否回购
						'isSell'=>0
					);
					M($this->name.'持有')->add($data);
					$this->setrecord($name,$sell['价格'],$buynum,'购买ID-'.$sell['id'].'挂单出售');
					//对卖家产生奖金
					$addvals=$this->getcon('addval',array('to'=>'','val'=>"100%",'tax'=>0));
					foreach($addvals as $addval){
						//处理奖金到货币
						$t_prize=$this->getnum($prize,$addval['val'],'all');
						bankset($addval['to'],$sell['编号'],$t_prize,$this->name.'出售',$name.'以'.$sell['价格'].'购买了您挂出的'.$buynum.'股');
						//处理手续费
						if($addval['tax'] != 0){
							bankset($addval['to'],$sell['编号'],-$prize/100*$addval['tax'],$this->name.'出售',$name.'扣除'.$addval['tax'].'%的手续费');
						}
					}
					

					$this->upPriceSplit($buynum);
					continue;
				}
				$price   = $this->getPrice();
				//得到可以购买的总数量
				$buynum  = ((floatval($money)*100) % ($price*100)) ===0 ? ($money/$price):intval($money/$price);
				$buynum  = (int)($buynum.'');
				//封顶到涨价之前的交易量度
				$buynum  = $this->getmaxnum($buynum);
				//生成相关数组
				$data=array(
					'编号'=>$name,
					//原始数量
					'num'=>$buynum,
					//当前数量
					'nownum'=>$buynum,
					//价格
					'price'=>$price,
					//时间
					'addtime'=>systemTime(),
					//备注
					'memo'=>'购买公司发行',
					//拆分次数
					'splitnum'=>0,
					//是否回购
					'isSell'=>0
				);
				
				M($this->name.'持有')->add($data);
				$this->setrecord($name,$price,$buynum,'购买公司股');
				//从剩余交易金额中减少
				if($buynum * $price>0)
				{
					$isbuy=true;
					if($backout)
					bankset($this->tradeBank,$name,-($buynum * $price),'以'.$price.'的价格购买'.$buynum.'发行股');
				}
				$money-=$buynum * $price;
				$this->upPriceSplit($buynum);
			}
			//如果剩余的金额无法购买1股，也需要把钱数返回交给外部处理，
			//也有考虑无法发行不足没有成交完全的可能
			//$this->sellgive($name,$money,2);	//扣钱
			return $money;
		}
		
		//对系统增加业绩，涨价，拆分综合处理
		public function upPriceSplit($num)
		{
			$num=(int)$num;
			//判断本次价格是否产生过变动
			$priceup = false;
			//$num增加新交易业绩的数量
			$set=$this->getSet();
			$set['交易量']    +=$num;
			$set['交易量结转']+=$num;
			if($set['交易量结转']>$set['涨价额'])
			{
				echo "出现错误！交易量结转不应该超过涨价额";
				die();
			}
			if($set['交易量结转']==$set['涨价额'])
			{
				$set['交易量结转']=0;
				$set['当前价格']  += $set['涨价幅度'];
				$priceup=true;
			}
			//处理拆分
			if($set['当前价格']>=$this->splitPrice)
			{
				//处理拆分
				$haveM=M($this->name.'持有');
				$haves=$haveM->where(array('isSell'=>0))->select();
				//统计拆分时增加的股票数量
				$addsum=0;
				foreach($haves as $have)
				{
					//对原有持有记录进行翻倍
					$haveM->where(array('id'=>$have['id']))->save(array('nownum'=>$have['nownum']*$this->splitMultiple));
					$this->setrecord($have['编号'],$set['当前价格'],$have['nownum']*($this->splitMultiple-1),'ID为['.$have['id'].']持有'.$this->name.'拆分获得');
					$addsum+=$have['nownum']*($this->splitMultiple-1);
				}
				$splitdata=array(
					'addtime'=>systemTime(),
					'拆分增加'=>$addsum,
				);
				//增加拆股记录
				M($this->name.'拆股')->add($splitdata);
				//价格回归
				$set['当前价格'] = ($set['当前价格']/$this->splitMultiple).'';
				if($this->upNumSplit)
				$set['涨价额'] = $set['涨价额'] * $this->splitMultiple;
			}

			//进行股票挂单出售处理
			if($priceup)
			{
				$haveM=M($this->name.'持有');
				//最早购买数量*购买价格*4(相当于翻两倍之后的金额) 等于拆分后的数量 * 当前价格
				$haves=$haveM->where("isSell = 0 and num * price * 4 =nownum * ".$set['当前价格'])->select();
				$sellsum=0;
				foreach($haves as $have)
				{
					$haveM->where(array('id'=>$have['id']))->save(array('isSell'=>1));
					//对原有持有记录进行翻倍
					$this->setrecord($have['编号'],$set['当前价格'],-$have['nownum'],'挂单出售持有的'.$this->name.'ID-'.$have['id']);
					//当存在会员之间交易时才会在交易表中加入记录
					if($this->selltype == 1)
					{
						$jydata=array(
							'价格'=>$set['当前价格'],
							'编号'=>$have['编号'],
							'挂单量'=>$have['nownum'],
							'剩余量'=>$have['nownum'],
							'类型'  =>'卖出',
							'时间'  =>systemTime(),
						);
						$sellsum+=$have['nownum'];
						M($this->name.'交易')->add($jydata);
					}
					else
					{
						$prize=$set['当前价格']*$have['nownum'];
						//如果是会员之间不存在交易，那么挂单卖出以后。需要公司进行回购处理
						$addvals=$this->getcon('addval',array('to'=>'','val'=>"100%"));
						foreach($addvals as $addval){
							$t_prize=$this->getnum($prize,$addval['val'],'all');
							bankset($addval['to'],$have['编号'],$t_prize,$this->name.'出售','公司以'.$set['当前价格'].'回购了您'.$have['nownum'].$this->name);
						}
					}
				}
				//dump('当前挂出'.$sellsum);
				//当产生价格变动时，对走势图进行变更
					$today=strtotime(date("Y-m-d",systemTime()));
					$model=M($this->name."走势",'dms_');
					$rs=$model->where(array("计算日期"=>$today))->find();//得到当天的股票走势
					if(!$rs){
						//若不存在
					   	$count=$model->count();
					   	$price=$this->getPrice();
						if($count>0){
							$last=$model->where(array("计算日期"=>array("lt",$today)))->order("计算日期 desc")->limit("1")->find();
							$lasttime=$last['计算日期']+86400;
							//今天所在的月份
							$todaymonth=strtotime(date("Y-m",$today));
							//最后一次记录的月份
							$lastmonth=strtotime(date("Y-m",$last['计算日期']));
							while($lasttime<$today){
								$updata=array();
								$updata=array( 
									'计算日期'=>$lasttime,
									'价格'=>$price,
									'认购量'=>$num,
								);
								$model->add($updata);
								$lasttime+=86400;
							}
						}
						$data=array( 
						   	'计算日期'	=>	$today,
						   	'价格'		=>	$price,
							'认购量'	=>	$num
						);
						//插入当天的
						$model->add($data);
					   	//$this->trendxml();
					}else{
						$today2=strtotime(date("Y-m-d",systemTime()));
						$where=array();
						$where['计算日期']=$today2;
						$todayinfo=M($this->name."走势",'dms_')->where($where)->find();
						$todayinfo['认购量']+=$num;
						$todayinfo['价格']= $this->getPrice();
						$todayinfo['成交金额']+=$todayinfo['成交金额'];
						$a=M($this->name."走势")->where($where)->save($todayinfo);
					}
			}
			//dump('保存:'.$set['当前价格']);
					$this->setCon($set);
		}
		//更新交易明细
		private function updataSellDetail($pid,$name,$num){
			M($this->name.'交易明细')->add(array('pid'=>$pid,'数量'=>$num,'编号'=>$name,'时间'=>time()));
			
		}
		
		//更新交易					用户ID 交易量  剩余量
		private function updataSell($userid,$s_num,$t_num){
			//更新挂单用户交易，剩余
		
			$sellnum = M($this->name.'交易')->where(array('id'=>$userid))->field('交易量')->find();						
			M($this->name.'交易')->where(array('id'=>$userid))->save(array('交易量'=>$sellnum['交易量']+$s_num,'剩余量'=>$t_num));
												
		}
		
		//获得股票价格
		public function getPrice($time=NULL)
		{
			$set = $this->getSet();
			return $set['当前价格'];
		}
		public function event_sysclear()
		{
            $model=M();
            //流水表示个人股票总数量的增减情况的明细
			$model->execute('truncate table `dms_'.$this->name.'流水'.'`');
			//持有的是，个人持有总股票数量的具体不同时间段购买的记录，持有记录中的股票总数，应该等于会员实际持股数量
			$model->execute('truncate table `dms_'.$this->name.'持有'.'`');
			//每次拆分的记录
			$model->execute('truncate table `dms_'.$this->name.'拆股'.'`');
			//走势图信息
			$model->execute('truncate table `dms_'.$this->name.'走势'.'`');
			//设置
			$model->execute('truncate table `dms_'.$this->name.'设置'.'`');
			//也算是设置
		//	$model->execute('truncate table `dms_'.$this->name.'公司'.'`');
			//挂单挂出记录
			$model->execute('truncate table `dms_'.$this->name.'交易'.'`');
			//交易成交时产生的明细
			$model->execute('truncate table `dms_'.$this->name.'交易明细'.'`');
			//"涨价额"=>$this->upNum,
			M($this->name.'设置','dms_')->add(array('交易量'=>'0',
			'交易量'=>0,
			"涨价额"=>$this->upNum,
			"涨价幅度"=>$this->upSkip,
			'当前价格'=>$this->StartPrice,
			'交易量结转'=>0,
				)
			);
		}

		//得到要到达涨价临界点的最大交易量,传入原始交易量，得到新交易量
		public function getmaxnum($num)
		{
			$set = $this->getSet();
			return ($num > $set['涨价额']-$set['交易量结转']) ? $set['涨价额']-$set['交易量结转'] : $num;
		}
		//$dealType  交易类型  0为默认。1为挂单
		public function event_valadd($user,$val,$option=array("memo"=>"购买股票"),$dealType=0)	
		{
			$this->buy($user['编号'],$val,null);
		}
		//公司回购
		public function autoBack($userid){
			//获取当前价格
			$price = $this->getPrice();
			//找出当前价格之前的所有会员持有量记录
			$holdlist=M($this->name.'持有')->where("isSell=0 and stocknum=1 and 编号='{$userid}'")->select();
			foreach($holdlist as $holdinfo){
				$bei=($price-$this->StartPrice)/$this->StartPrice;
				if($bei*($holdinfo['num']*$holdinfo['price'])<=$holdinfo['nownum']*$price){
					//进行回购
					$money=($holdinfo["nownum"]*$this->backnum/100)*$price;
					//设置税
					$holdinfo["isSell"]=1;
					//持有回购
					M($this->name.'持有','dms_')->save($holdinfo);
					//更新购买记录
					$this->setrecord($holdinfo['编号'],$price,$holdinfo["nownum"]*$this->backnum/100,"公司回购".$this->backnum."%");
					//回购返钱
					$addvals=$this->getcon('addval',array('to'=>'','val'=>"100%",'tax'=>0));
					foreach($addvals as $addval){
						$funbank=X('fun_bank@'.$addval['to']);
						$bankmoney=$this->getnum($money,$addval['val'],"all");
						$funbank->set($holdinfo['编号'],$holdinfo['编号'],$bankmoney,"公司回购","公司回购持有".$this->name);
						if($addval['tax'] != 0){
							bankset($addval['to'],$sell['编号'],-$money/100*$addval['tax'],$this->name.'出售',$name.'扣除'.$addval['tax'].'%的手续费');
						}
					}
					//生成新的持有记录
					$newhold=array(
						"编号"=>$holdinfo["编号"],
						"num"=>$holdinfo["nownum"]*(1-$this->backnum/100),
						"nownum"=>$holdinfo["nownum"]*(1-$this->backnum/100),
						"price"=>$holdinfo["price"],
						"addtime"=>systemTime(),
						"memo"=>"回购后持有"
					);
					M($this->name.'持有','dms_')->add($newhold);
					return true;
				}else{
				  return  false;
				}
			}
		}
		//更新走势图 array('价格'=>,'认购量'=>,'成交量'=>,'成交金额'=>)
        public function uptrend($data)
		{
			$today=strtotime(date("Y-m-d",systemTime()));
			if(!empty($data)){
				$where=array();
				$where['计算日期']=$today;
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
				M($this->name."走势")->where($where)->save($data);
			}
		}
        //增加股票交易流水        会员编号 价格  数量  备注  
		public function setrecord($userid,$price,$num,$memo)
		{
			
			$data=array();
			$data['编号']   =$userid;
			$data['price']  =$price;
			$data['num']    =$num;
			$data['type']	=$type;
			$data['addtime']=systemTime();
			$data['memo']   =$memo;
			$data['tleid']  =$option['tleid'];
			$data['dataid'] =$option['dataid'];
			M($this->name."流水",'dms_')->add($data);
		}
		//对xml中的$val转换成对应金额
	    public function getnum($allval,$xmlval,$from)
	  	{
			if($from=="all") $val=$allval;
			if(strstr($xmlval,'%')){
				$num = $val * substr($xmlval,0,-1) * 0.01; 
			}elseif($xmlval==''){
				$num = $val; 
			}else{
				$num = $xmlval;
			}
			return $num;
		}
		public function event_modifyId($oldbh,$newbh)
		{ 
			M()->execute("update dms_" . $this->name . "流水 set 编号='{$newbh}' where 编号='{$oldbh}'");
			M()->execute("update dms_" . $this->name . "持有 set 编号='{$newbh}' where 编号='{$oldbh}'");
			M()->execute("update dms_" . $this->name . "交易 set 编号='{$newbh}' where 编号='{$oldbh}'");
			M()->execute("update dms_" . $this->name . "交易明细 set 编号='{$newbh}' where 编号='{$oldbh}'");
			M()->execute("update dms_" . $this->name . "价格变更明细 set 编号='{$newbh}' where 编号='{$oldbh}'");
		}
	}
?>