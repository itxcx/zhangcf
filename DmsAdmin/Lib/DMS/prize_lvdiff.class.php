<?php
	class prize_lvdiff extends prize
	{
		//产生类型
		public $prizeMode=1;
		//网络体系名称
		public $netName = '';
		//奖金来源
		public $rowMode = '';
		//奖金来源表达式
		public $rowName = '';
		//奖金来源类型
		public $rowFrom = '';
		//来源表条件
		public $where = '';
		//紧缩条件
		public $tightenWhere = '';
		//订单来源状态下的订单类别
		public $saleState = '已结算,已确认';
		//级差计算时的最大实际相对层
		public $maxCeng=0;
		//仅计算差额
		public $differenceOnly = false;
		//根据分割列进行计算
		public $splitRow='';
		//分隔计算前代码
		public $splitStartExe='';
		//分隔计算后代码
		public $splitEndExe='';
		//最小层
		public $minLayer = -1;
		//最大层
		public $maxLayer = -1;
		//判断是否显示奖金构成
		public $isSee = true;
		//小数精度
		public $decimalLen = 2;
		//级差奖是否包含自身
		public $haveMe = false;
		//初始比例是否要从订单产生人比例开始
		public $differenceMe = false;
		//产生几次差额奖金之后。就停止产生0-x%也算作一次
		public $diffStop = 0;
		//启动差额计算，如果关闭，则实际计算时，按照0%到当前比例足额计算
		public $diff=true;
		function scal($sale)
		{
			if($this->rowFrom==0)
			{
				
				$this->cal();
			}
			else
			{
				if($this->where=="")
				{
					$this->where="id=".$sale["id"];
					$this->cal();
					$this->where="";
				}
				else
				{
					$otherwhere=$this->where;
					$this->where= '('.$otherwhere.") and id=".$sale["id"];
					$this->cal();
					$this->where=$otherwhere;
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
			$cons = $this->getcon('con',array("lv"=>1,"val"=>'',"where"=>'','equals'=>'','minequal'=>1,'maxequal'=>1,'deduct'=>false));
			//从订单获取奖金来源
			if($this->rowFrom == 1)
			{
				$sales=$this->getsale($this->where,"*,$this->rowName as t_num");
				if(isset($sales)){
					foreach($sales as $sale)
					{
						$this->calculate($net,$sale,$sale['userid'],$sale,$cons);
					}
				}
				unset($sales);
			}
			if($this->rowFrom == 0)
			{
				$users=$this->getuser($this->where,"*,$this->rowName as t_num");
				foreach($users as $user)
				{
					$this->calculate($net,$user,$user['id'],null,$cons);
				}
				unset($users);
			}
			//------------------------------------
			unset($cons);
			$this->prizeUpdate();
		}
		public function calculate($net,&$from,$userid,$sale=null,$cons)
		{
			$temp_getnum=0;
			$user   =M('会员')->where(array("id"=>$userid))->lock(true)->find();
            //过滤缓存数据
			$user=X("user")->filt(array($this->lvName),$user);
			//取得包括自己的上级
			$upusers=$net->getups($user,0,0,$this->tightenWhere,true,$this->haveMe);
			$oldcon = '0%';
			//是否要去掉自己的差额
			if($this->differenceMe)
			{
				foreach($cons as $con)
				{
					if((getnum($from['t_num'],$con['val']) <= $temp_getnum and $con['equals'] == '') || $this->getlv($user,$cons)!= $con['lv'])continue;
					$temp_getnum = getnum($from['t_num'],$con['val']);
					$oldcon=$con['val'];
				}
			}
			//平级判定上一个人的级别
			$equalsLv = 0;
			//获取上级时，循环到的平级数量
			$equals = 0;
			//平级扣除明细
			$deduct = array();
			//产生差额奖金的次数
			$diffNum=0;
			foreach($upusers as $upuser)
			{
                //过滤缓存数据
				$upuser=X("user")->filt(array($this->lvName),$upuser);
				//如果层数超过最大层数则跳过当条线
				if($this->maxLayer != -1 && $user[$net->name.'_层数'] - $upuser[$net->name.'_层数'] > $this->maxLayer)
					break;
				//如果层数量小于最小层数，则跳过当前人
				if($this->minLayer != -1 && $user[$net->name.'_层数'] - $upuser[$net->name.'_层数'] < $this->minLayer)
					continue;
				//计算级别平级
				if($equalsLv == $this->getlv($upuser,$cons))
				{
					$equals += 1;
				}
				//计算级别超越
				if($equalsLv<$this->getlv($upuser,$cons))
				{
					$equals=0;
					$equalsLv=$this->getlv($upuser,$cons);
				}
				//如果当前级别小于之前的级别，则不能算平级
				if($equalsLv>$this->getlv($upuser,$cons))
				{
					$thisequals = 0;
				}
				else
				{
					$thisequals = $equals;
				}
				foreach($cons as $con)
				{
					//如果当前配置金额小于已分配,或者判定不属于当前con,则跳出本次循环
					if((getnum($from['t_num'],$con['val']) <= $temp_getnum and $con['equals'] == '') || $this->getlv($upuser,$cons)!= $con['lv'])continue;

					//如果只产生差额.第一次产生额度将不纳入计算
					if($this->differenceOnly && $temp_getnum == 0)
					{
						$temp_getnum = getnum($from['t_num'],$con['val']);
						$oldcon=$con['val'];
						continue;
					}
					//平级奖
					if($con['equals']!='' && $equals >= $con['minequal'] && $equals <= $con['maxequal'])
					{
						$prizenum = getnum($from['t_num'],$con['equals']);
						$comstr =  '产生平级'.$from['t_num']."*".$con['equals'];
						$this->addprize($upuser,$prizenum,$user,$comstr,$user[$net->name.'_层数'] - $upuser[$net->name.'_层数']);
						if($con['deduct'])
						{
							$deduct[]=array('id'=>$upuser['id'],'num'=>$prizenum,'val'=>$con['equals'],'t_num'=>$from['t_num']);
						}
					}
					if($equals == 0 && getnum($from['t_num'],$con['val']) > $temp_getnum)
					{
						if($this->diff)
						{
							$prizenum = getnum($from['t_num'],$con['val']) - $temp_getnum;
							$comstr = $from['t_num'].'*('.$con['val'] . '-' . $oldcon.')';
						}
						else
						{
							$prizenum = getnum($from['t_num'],$con['val']);
							$comstr = $from['t_num'] . '*' . $con['val'];
						}
						if($prizenum>0)
						{
							$diffNum +=1;
						}
						//取得差额
						$temp_getnum = getnum($from['t_num'],$con['val']);
						$this->addprize($upuser,$prizenum,$user,$comstr,$user[$net->name.'_层数'] - $upuser[$net->name.'_层数']);
						if(count($deduct) != 0)
						{
							foreach($deduct as $d)
							{
								ini_set('display_errors','On');
								$this->addprize($upuser,-$d['num'],$user,'拨付'.$from['t_num'].'*'.$d['val'],$user[$net->name.'_层数'] - $upuser[$net->name.'_层数']);

							}
							$deduct=array();
						}
						
						//生成构成
						$oldcon = $con['val'];
					}
					if($this->diffStop>0 && $diffNum>=$this->diffStop)
					{
						break;
					}
					//增加奖金
				}
			}
		}
		//取得当前会员所属的级别值
		private function getlv($user,$cons)
		{
			//定义返回值为假
			$result = -1;
			//得到要判定的数据
			$lv_lv_num=$user[$this->lvName];
			//对所有的CON进行循环
			foreach(array_reverse($cons) as $con)
			{
				//判断当前值大于等于lv的时候，并且条件也符合的情况下。
				if($lv_lv_num>=$con['lv'] && transform($con['where'],array(),array('M'=>$user)))
				{
					//传入的配置相等，则返回TRUE
					return $con['lv'];
				}
			}
			return $result;
		}
	}
?>