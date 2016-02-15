<?php
	class prize_consume extends prize
	{
		private $_saleData=array();
		//
		public $prizeMode=2;
		//来源表条件
		public $where = '';
		//来源表达式
		public $rowName = '';
		//起征数量
		public $startNum='0';
		//起征字段
		public $startRow='累计收入';
		//
		public $saleMoney ='';
		//
		public $rowFrom = 0;
		public $saleName='';
		//判断是否显示奖金构成
		public $isSee = true;
		function scal()
		{
			$this->cal();
		}
		function cal()
		{
			if(!$this->ifrun()) return;
			//进行条件优化
			if($this->where=="")
			{
				$this->where="($this->rowName)<>0";
			}
			$users=$this->getuser($this->where,"*,$this->rowName as tempnum,$this->startRow as startRow,$this->startNum as startNum");
			$cons=$this->getcon("con",array("minlv"=>1,"maxlv"=>1,"val"=>"","where"=>""));
			$itemadds=$this->getcon("itemaddval",array("to"=>"",'val'=>'100%'),true);
			foreach((array)$users as $user)
			{
				$tempnum=$user["tempnum"];
				//起征额处理
				if($user["startNum"]!=0 && $user["startRow"]<=$user["startNum"])
				{	
					//超过起征点
					if($user["startRow"]+$tempnum>$user["startNum"])
					{	
						$tempnum=($user["startRow"]+$tempnum-$user["startNum"]);
					}
					else
					{
						continue;
					}
				}
				$prizenum=0;
				$calculateType = '';
				foreach($cons as $con)
				{
					if($con['minlv'] <= $user[$this->lvName] && $con['maxlv'] >= $user[$this->lvName] && transform($con['where'],array(),array('M'=>$user)))
					{
						$calculateType .= $tempnum .(strstr($con['val'],'%')? '*' : '').$con['val'].'*'.($user[$this->name.'比例']/100).'+';
						//得到临时奖金
						$t_prize = getnum($tempnum,$con['val'],$this->decimalLen,$user[$this->name.'比例']);
						$prizenum += $this->addprize($user,$t_prize,$user,trim($calculateType,'+'),0);
					}
				}
				//得到奖金额
				$user[$this->name."结转"]+=$prizenum;
				if($user[$this->name."结转"]>=$this->saleMoney&&$this->saleMoney>0)
				{
					$itemnum=floor($user[$this->name."结转"]/$this->saleMoney);
					foreach($itemadds as $itemadd)
					{
						runadd($user,$this->saleMoney*$itemnum,$itemadd["to"],$itemadd);
					}
					$user[$this->name."结转"]-=$this->saleMoney*$itemnum;
					if($this->saleName!="")
					{
						$sale_buy=X('sale_buy@'.$this->saleName);
						if($sale_buy==''||empty($sale_buy))
						{
                             throw_exception($this->name.'获取sale标签失败');
						}
						$sale_buy->setMoney=true;
						$sale_buy->lockMe=false;
						$data=array();
						$data=array(
							'userid'=>$user['编号'],
							'confirm'=>true,
							'setMoney'=>$this->saleMoney
							);
						for($i=1;$i<=$itemnum;$i++){
							$this->_saleData[]=$data;
                    	}	
						//弹入到缓存数组
					}
					$user[$this->name."本日单数"]+=$itemnum;
					$user[$this->name."本月单数"]+=$itemnum;
					$user[$this->name."累计单数"]+=$itemnum;
					//更新结转和单数
					$data=array();
					$data= array(
						$this->name."结转"	 => $user[$this->name."结转"],
						$this->name."本日单数" => $user[$this->name."本日单数"],
						$this->name."本月单数" => $user[$this->name."本月单数"],
						$this->name."累计单数" => $user[$this->name."累计单数"],
					);
					$data['id']=$user['id'];
					M('会员')->bSave($data);
				}
			}
			M('会员')->bUpdate();
			$this->prizeUpdate();
		}
		//会导致秒结出现问题,要改为commit事件响应
		//public function event_calover($tle,$caltime,$type)
		//{
			//读取订单缓存，如果存在则循环执行

		//	while($data = array_shift($this->_saleData))
		//	{
		//		$sale_buy=X('sale_buy@'.$this->saleName);
		//		$sale_buy->user='admin';
		//		$sale_buy->accbank = '';
		//		$sale_buy->buy($data);
		//	}
		//}
		//对本月单数清理提取出来
		public function event_caldayover($caltime)
		{
			$data=array();
			if(date('j',$caltime)==date('t',$caltime))
			{
				$data[$this->name.'本月单数']=0;
				M('会员')->where('1')->save($data);
			}
		}
	}
?>