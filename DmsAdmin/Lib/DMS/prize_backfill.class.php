<?php
	class prize_backfill extends prize{
		public $prizeMode=2;
		//奖金来源表达式
		public $rowName = '';
		//奖金来源类型
		public $rowFrom = '';
		//来源表条件
		public $where = '';
		//判断是否显示奖金构成
		public $isSee = true;
		//小数精度
		public $decimalLen = 2;
		//需要发放才回填，true代表结算完就回填
		public $auto=false;
		
		public $conFilter=array('con'=>array("minlv","maxlv","val","where"));
		function scal($sale){
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
		function cal(){
			if(!$this->ifrun()) return;
			
			if(!X('levels@'.$this->lvName) instanceof levels)
			{
				throw_exception($this->name.'计算失败,因其lvName属性未找到对应的级别模块');
			}
			$num_ratio = false;
			$cons = $this->getcon('con',array("minlv"=>1,"maxlv"=>1,"val"=>"","where"=>""));
			foreach($cons as $con)
			{
				//用于优化
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
				$users=$this->getuser(str_replace('>>','<',$this->where),"*,$this->rowName as t_recnum");
				
				foreach($users as $user)
				{
					$this->calculate($user,$user['id'],null,$cons,$num_ratio);
				}
				unset($users);
			}
			//------------------------------------
			unset($cons);
			$this->prizeUpdate();
		}
		
		public function calculate(&$from,$userid,$sale=null,&$cons,$num_ratio){		
			//$form=来源表
			//$userid=产生业绩会员ID
			//$sale=如果来源为订单则传入订单数据
			if($this->rowFrom == 0){
				$user   =$from;
			}else{
				$user   =M('会员')->find((int)$userid);
			}
			$t_prize=0;
			//级别正则判定数据
			if($from['t_recnum'] == 0 && !$num_ratio)
				return;
			foreach($cons as $con)
			{
				//取双方最小级别,则做降级操作
				if($con['minlv'] <= $user[$this->lvName] && $con['maxlv'] >= $user[$this->lvName] && transform($con['where'],array(),array('M'=>$user,'S'=>$sale)))
				{
					//得到最终数字
					$prizenum=getnum($from['t_recnum'],$con['val'],$this->decimalLen,$user[$this->name.'比例']);
					$t_prize+=$this->addprize($user,$prizenum,$user,substr($con['val'],-1,1) == '%'?$from['t_recnum'].'*'.$con['val']:'',0);
				}
			}
			//增加结转用于回填的扣除
			M("会员")->where(array("编号"=>$user['编号']))->setInc($this->name."结转",$t_prize);
			unset($user);
		}
		//计算回填封顶金额
		public function gettop($user,$prizenum){
			//	回填金额  已回金额  奖金累计  本期需要回填
			//	  1000     500		 600		 400
			$m_sale = M('报单');
			//剩余回填的金额
			$back = $m_sale->where(array("编号"=>$user['编号'],"报单状态"=>"回填"))->sum('回填金额');
			if(($user[$this->name.'累计']+$prizenum)>$back){
				$ret = $back-$user[$this->name.'累计'];
			}else{
				$ret = $prizenum;
			}
			return $ret>0 ? $ret : 0;
		}
	}
?>