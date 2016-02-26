<?php

	class prize_givedown extends prize
	{
		//奖金产生类型0为不产生,1为产生,2为扣除
		public $prizeMode = 1;
		//网络名称
		public $netName = '';
		//来源表条件
		public $where = '';
		//会员条件,不限制可设置1
		public $getwhere="状态='有效'";
		//来源表达式
		public $rowName = '';
		//来源表达式
		public $rowFrom = 0;
		//起征数量
		public $startNum=0;
		//起征字段
		public $startRow='';
		//判断是否显示奖金构成
		public $isSee = true;
		//构成信息
		public $conFilter=array('con'=>array("minlayer","maxlayer","minlv","maxlv","val","where","weighing",'isSee'));
		function scal($sale)
		{
			//如果是通过会员表中产生奖金,可以直接走CAL流程
			if($this->rowFrom==0)
			{
				$this->cal();
			}
			else
			{
				if($this->where=="")
				{
					//如果判定没有任何条件,则将条件设置为id等于当前订单
					//这样此奖金只计算当前审核的订单
					$this->where = "id=" . $sale["id"];
					//执行结算
					$this->cal();
					//结算后还原条件
					$this->where = "";
				}
				else
				{
					//如果存在where设定,则使用临时变量存储.
					//并且在原条件中增加对ID的判断,结算完成后,在还原到回原始的where条件
					$otherwhere = $this->where;
					$this->where = '('.$otherwhere . ") and id=".$sale["id"];
					$this->cal();
					$this->where = $otherwhere;
				}
			}
		}
		function cal()
		{
			
			if(!$this->ifrun()) return;
			$net = X('*@'.$this->netName);
			if($net === NULL)
			{
				throw_exception($this->name."计算时网络体系获取失败,请检查其netName设置是否正确");
			}			
			$num_ratio = false;
			$rec_maxlayer = 0;
			$cons = $this->getcon('con',array("minlayer"=>1,"maxlayer"=>1,"minlv"=>1,"maxlv"=>1,"val"=>"","where"=>"","weighing"=>''));
			//进行条件自动优化,如果全部为比例模式.并且条件为空.则设置额外条件
			//echo "[".$rec_maxlayer."]";
			//echo $rec_maxlayer;
			//从订单获取奖金来源
			
			if($this->rowFrom == 1)
			{
				$sales=$this->getsale($this->where,"*,$this->rowName as t_num");
				foreach($sales as $sale)
				{
					$this->calculate($net,$sale,$sale['userid'],$sale,$rec_maxlayer,$cons,$num_ratio);
				}
				unset($sales);
			}
			if($this->rowFrom == 0)
			{
				if(!$num_ratio&&$this->where=="")
				{
					$this->where="($this->rowName)<>0";
				}
				$users=$this->getuser($this->where,"*,$this->rowName as t_num");
				if($users)
				foreach($users as $user)
				{
					$this->calculate($net,$user,$user['id'],null,$rec_maxlayer,$cons,$num_ratio);
				}
				unset($users);
			}
			//------------------------------------
			unset($cons);
			$this->prizeUpdate();
		}
		//计算处理
		public function calculate($net,&$from,$userid,$sale=null,$rec_maxlayer,&$cons,$num_ratio)
		{
				$user   =M("会员")->where(array("id"=>$userid))->lock(true)->find();
				foreach($cons as $con)
				{
					//此处判断推荐人的条件
					$prizenum  = getnum($from['t_num'],$con['val']);
					//默认计算时间24:00之前推荐的有效会员
					//取得在审核日期内的所有下级
					$downwhere = $this->getwhere." and 审核日期<".($this->parent('tle')->_caltime+86400);
					$ndownusers = $net->getdown($user,$con['minlayer'],$con['maxlayer'],$downwhere,true);
					//对不符合条件的记录进行过滤
					$downusers=array();
					if($ndownusers)
					foreach($ndownusers as $downuser)
					{
						$wheredata=array('U'=>&$user,'M'=>&$downuser,'S'=>&$sale);
						//对会员级别的判断
						if(!transform($con['where'],array(),$wheredata)
						 || $downuser[$this->lvName]<$con['minlv']
						 || $downuser[$this->lvName]>$con['maxlv']
						 )
						{
							//unset($downusers[array_search($downuser , $downusers)]);
						}else{
							$downusers[]=$downuser;
						}
					}
					
					if(count($downusers)==0)
					{
						continue;
					}
                    
					if($con['weighing']!=''){
                        $usercount=0;
						foreach($downusers as $downuser){
							$usercount += transform($con['weighing'],$downuser);
						}
					}else{
						$usercount =  count($downusers);
					}
					if($downusers)
					foreach($downusers as $downuser)
					{
						$weighing= $con['weighing']=="" ? 1 : transform($con['weighing'],$downuser);
						$prize = $prizenum/$usercount*$weighing;
						//生成构成信息
						$calculateType = $from['t_num']."*".$con['val'].'/'.$usercount.'人';
						if($con['weighing']!='')
							$calculateType .= '*' . $weighing . '加权';
						
						$this->addprize($downuser,$prize,$user,$calculateType,$downuser[$this->netName.'_层数'] - $user[$this->netName.'_层数']);
					}
				}
				
				unset($downusers);
				unset($user);
		}
	}
?>