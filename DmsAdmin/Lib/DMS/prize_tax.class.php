<?php

	class prize_tax extends prize
	{
		public $prizeMode=2;
		//税点
		public $taxNum =0;
		//来源表条件
		public $where = '1=1';
		//来源表达式
		public $rowName = '';
		//起征数量
		public $startNum=0;
		//起征字段
		public $startRow='';
		//判断是否显示奖金构成
		public $isSee = true;
		function scal($sale)
		{
			$this->cal();
		}
		function cal()
		{
			if(!$this->ifrun()) return;
			$this->where = delsign($this->where);
			$m_user=M();
			if($this->startNum == 0)
			{
				$m_user->execute("update dms_会员 set $this->name=($this->rowName)*".$this->name."比例/100*($this->taxNum/100) where $this->where");
				$m_user->execute("update dms_会员 set $this->name=ROUND($this->name,$this->decimalLen) where $this->where");
				$m_user->execute("update dms_会员 set ".$this->name."本日=".$this->name."本日+$this->name,".$this->name."本周=".$this->name."本周+$this->name,".$this->name."本月=".$this->name."本月+$this->name,".$this->name."累计=".$this->name."累计+$this->name where $this->where");
		   	}
		   else
		   {
		   	   //起征字段未达到起征数量,本次奖金累计后，多出的部分
			    $m_user->execute("update dms_会员 set $this->name=($this->rowName+$this->startRow-$this->startNum)*".$this->name."比例/100*($this->taxNum/100) where ($this->startRow)<$this->startNum and ($this->startRow+$this->rowName)>$this->startNum and $this->where");
				//起征字段满足起征数量
				$m_user->execute("update dms_会员 set $this->name=($this->rowName)*".$this->name."比例/100*($this->taxNum/100) where $this->startRow>=$this->startNum and $this->where");
				//更新小数位数
			    $m_user->execute("update dms_会员 set $this->name=ROUND($this->name,$this->decimalLen) where $this->where");
				//更新奖金
			    $m_user->execute("update dms_会员 set ".$this->name."本日=".$this->name."本日+$this->name,".$this->name."本周=".$this->name."本周+$this->name,".$this->name."本月=".$this->name."本月+$this->name,".$this->name."累计=".$this->name."累计+$this->name where $this->where");
			}
		}
	}
?>