<?php

	class prize_stage extends prize
	{
		public $prizeMode=2;
		//奖金来源表达式
		public $rowName = '';
		//奖金来源类型
		public $rowFrom = '';
		//来源表条件
		public $where = '';
		//起征字段
		public $startRow='';
		//起征点
		public $startNum=0;
		//是否要把自己的奖金,扣除下级产生此奖金之和
		public $section=false;
		
		public $deductTree='';
		//小数精度
		public $decimalLen = 2;
			//判断是否显示奖金构成
		public $isSee = true;
		public $conFilter=array('con'=>array("minlv","maxlv","lv","isover","val");
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
					$this->where='('.$otherwhere.") and id=".$sale["id"];
					$this->cal();
					$this->where=$otherwhere;
				}
			}
		}		

		//日结驱动
		function cal()
		{
			
			if(!$this->ifrun()) return;
			
			if(!X('levels@'.$this->lvName) instanceof levels)
			{
				throw_exception($this->name.'计算失败,因其lvName属性未找到对应的级别模块');
			}
			$num_ratio = false;
			$rec_maxlayer = 0;
			$cons = $this->getcon('con',array("minlv"=>1,"maxlv"=>1,"lv"=>0,"isover"=>"false","val"=>""));
			foreach($cons as $con)
			{
				//用于优化,如果VAL全部带有%,而rowname的结果为0,则可以忽略当次计算
				if(substr($con['val'],-1,1) != '%')
				$num_ratio=true;
			}
			if($this->rowFrom == 1)
			{

				$sales=$this->getsale($this->where,"*,$this->rowName as t_recnum");
				foreach($sales as $sale)
				{
					$this->calculate($sale,$sale['userid'],$sale,$cons,$num_ratio);
				}
				unset($sales);
			}
			if($this->rowFrom == 0)
			{
				if(!$num_ratio&&$this->where=="")
				{
					$this->where="($this->rowName)<>0";
				}
				$users=$this->getuser(str_replace('>>','<',$this->where),"*,$this->rowName as tempnum,$this->startRow as startRow,$this->startNum as startNum");
				
				foreach($users as $user)
				{
					$this->calculate($user,$user,null,$cons,$num_ratio);
				}
				unset($users);
			}
			//------------------------------------
			unset($cons);
			$this->prizeUpdate();
		}
		
		public function calculate(&$from,$user,$sale=null,&$cons,$num_ratio)
		{			
				   //dump("计算".$userid);
				   //dump($cons);
					//$form=来源表
					//$userid=产生业绩会员ID
					//$sale=如果来源为订单则传入订单数据
					if($from['t_regnum'] = 0 && !$num_ratio)
					continue;

						//本次奖金
						$tempnum=$user["tempnum"];

						//总累计
						$startRow=$user["startRow"];
						//之前累计
						$startNum=$startRow-$tempnum;
						$oldlv=0;$prizenum=0;$calculateType = '';
						foreach($cons as $con)
						{
						//起征额处理
							if($con["isover"] =='true' && $startRow>$con["lv"] && $tempnum>0){
								if($startNum<$con["lv"]){
									$nownum=$startRow-$con["lv"];
									$tempnum=$con["lv"]-$startNum;
									$startRow=$con["lv"];
								}else{
									$nownum=$tempnum;$tempnum=0;
								}
								$prizenum=getnum($nownum,$con['val'],$this->decimalLen,$user[$this->name.'比例']);
								$calculateType = $nownum .(strstr($con['val'],'%')? '*' : '').$con['val'].'*'.($user[$this->name.'比例']/100).'+';
								$this->addprize($user,$prizenum,$user,trim($calculateType,'+'));
							}elseif($startRow>$con["lv"] && $startRow<=$oldlv && $con["isover"] !='true' && $tempnum>0)
							{	
								if($startNum>=$con["lv"]){
									$nownum=$tempnum;$tempnum=0;
								}else{
									$nownum=$startRow-$con["lv"];
									$tempnum=$con["lv"];
									$startRow=$con["lv"];
								}
								$prizenum=getnum($nownum,$con['val'],$this->decimalLen,$user[$this->name.'比例']);
								$calculateType = $nownum .(strstr($con['val'],'%')? '*' : '').$con['val'].'*'.($user[$this->name.'比例']/100).'+';
								$this->addprize($user,$prizenum,$user,trim($calculateType,'+'));
							}
							$oldlv=$con["lv"];
						}

		}
	}
?>