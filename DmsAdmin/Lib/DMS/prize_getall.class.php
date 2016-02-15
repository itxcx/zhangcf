<?php
	class prize_getall extends prize
	{
		//产生奖金模式
		public $prizeMode = 1;
		//产生体系
		public $username;
		//
		public $rowFrom = 1;
		//奖金来源表达式
		public $rowName = '';
		//来源表条件
		public $where = '';
		//订单来源状态下的订单类别
		public $saleState = '已结算,已确认';
		//判断是否显示奖金构成
		public $isSee = true;
		//奖金池分红功能
		public $pool = false;
		//小数精度
		public $decimalLen = 2;
		//设置参考业绩。如果此值设置为-1则表示不生效。否则将会按照此值作为分红业绩计算
		public $sumprice   = -1;
		public $conFilter=array('con'=>array('minlv','maxlv','val','where',"weighing",'isSee'));
		function scal($sale)
		{
			
			if($this->rowFrom==0)
			{
				
				$this->cal();
			}
			else
			{
				if($this->sumprice>0 && $this->rowName!='' && $sale[$this->rowName]<=0) return false;
				if($this->where=="")
				{
					$this->where="id=".$sale["id"];
					$this->cal();
					$this->where="";
				}
				else
				{
					$otherwhere=$this->where;
					$this->where='('.$otherwhere.") and id=".$sale["id"];
					$this->cal();
					$this->where=$otherwhere;
				}
			}
		}
		
		//结算执行
		function cal()
		{
			if(!$this->ifrun())
				return;
         
			//是否统计奖金池
			if($this->pool){
				//查询本期所要统计的奖金池的业绩
				$poolwhere=array("到款日期"=>array(array("lt",($this->parent('tle')->_caltime-7*86400)),array("gt",($this->parent('tle')->_caltime+86400))));
				$all=M("报单")->where($poolwhere)->sum("报单金额");
				if($this->poolrate){
					$all=getnum($all,$this->poolrate);
				}
				$poollist=M("poollist")->find();
				if($poollist){
					$poollist['val']+=$all;
					M("poollist")->save($poollist);
				}else{
					$poollist['val']=$all;
					M("poollist")->add($poollist);
				}
			}
			$num_ratio = false;
			$rec_maxlayer = 0;
			$cons = $this->getcon('con',array("minlv"=>1,"maxlv"=>1,"val"=>"",'where'=>'',"weighing"=>''));
			//从订单表中获取数据
			if($this->rowFrom == 1)
			{
				if($this->sumprice!=-1){
					$t_num=$this->sumprice;
				}else{
					if($this->rowName==''){
					 throw_exception($this->name.'奖金模块的$rowName没有设置');
					}
					if(isset($poollist['val'])){
						$t_num=$poollist['val'];
					}else{
						//通过基类的getsale方法取得订单,附带订单条件,$this->rowName
						$sales=$this->getsale($this->where,"$this->rowName as t_num");
						//取得总业绩
						$t_num=0;
						foreach((array)$sales as $sale)
						{
							//循环订单累加总业绩
							$t_num+=(float)$sale['t_num'];
						}
					}
				}
				//遍历配置
				foreach($cons as $con)
				{
					//找到符合条件的配置,对配置设定SQL语句适应化
					//替换字符串
					$where=str_replace('M[','[',$con['where']);
					$where=delsign($where);
					if($where =='') $where='1';
					$where = '审核日期<'.$this->parent('tle')->_caltime.'+86400 and ('.$where.')';
					$users=M("会员")->where($where)->select();
					//根据条件查询到所有会员
					$usercount=$con['weighing']=="" ? count($users) : M("会员")->where($where)->sum(delsign($con['weighing']));
					if($users)
					{
						//遍历所有会员.产生奖金
						foreach($users as $user)
						{
							$weighing= $con['weighing']=="" ? 1 : transform($con['weighing'],$user);
							if(!is_numeric($weighing)){
								throw_exception($this->name.'weighing值转换后不为数字');
							}
							if($weighing!=0)
							{
								$prizenum=getnum($t_num/$usercount*$weighing,$con['val'],$this->decimalLen,$user[$this->name.'比例']);
								if(strstr($con["val"],'%')){
									$calculateType =$t_num.'/'.$usercount.'*'.$weighing.' * '.$con["val"];
								}else if(strstr($con["val"],'*')){
									$calculateType = $t_num.'/'.$usercount.'*'.$weighing . $con["val"];
								}else{
									$calculateType = $con["val"];
								}
								if($user[$this->name.'比例']<>100)
								{
									$calculateType .= ',(个人奖金比例'.$user[$this->name.'比例'].'%)';
								}
								$this->addprize($user,$prizenum,null,$calculateType,0);
							}
						}
					}
				}
				if($this->pool){
					//统计本期所使用的奖金池的业绩
					$geiveprice=array_sum($this->prize_cache);
					$poollist['val']-=$geiveprice;
					M("poollist")->save($poollist);
				}
				$this->prizeUpdate();
				unset($sales);
			}
			unset($cons);
			unset($tops);
		}
	}
?>