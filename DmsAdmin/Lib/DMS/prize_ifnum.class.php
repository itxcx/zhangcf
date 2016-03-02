<?php
	class prize_ifnum extends prize
	{
		//产生类型
		public $prizeMode=1;
		//网络体系名称
		public $rowName = '';
		//奖金来源类型
		public $rowFrom = 1;
		//来源表条件
		public $where = '';
		//订单来源状态下的订单类别
		public $saleState = '已结算,已确认';
		//小数精度
		public $decimalLen = 2;
		//一条线模块名称
		public $numName = '' ;
		public $netName = '';
		public $conFilter=array('numcon'=>array("minNum","maxNum","minlv","maxlv","direction","val",'where'),'netcon'=>array("maxlayer","minlayer","minNum","maxNum","minlv","maxlv","direction","val",'where'));
		static $_userCache=array();
		//public $direction = 'up';
		//秒结算驱动
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
		//结算执行
		function cal()
		{
			if(!$this->ifrun()) return;
			if($this->rowFrom == 1)
			{
				$sales=$this->getsale($this->where,"*,$this->rowName as t_recnum");
				foreach($sales as $sale)
				{
					
					$this->calculate($sale,$sale['userid'],$sale);
				}
				unset($sales);
			}
			if($this->rowFrom == 0)
			{   //$num_ratio并没有定义，如果默认开启此代码，可能会导致固定值奖金无法产生
				//if(!$num_ratio&&$this->where=="")
				//{
				//	$this->where="($this->rowName)<>0";
				//}
				$users=$this->getuser($this->where,"*,$this->rowName as t_recnum");
				foreach($users as $user)
				{
					$this->calculate($user,$user['id'],null);
				}
				unset($users);
			}
			//------------------------------------
			$this->prizeUpdate();
		}
		//计算处理,来源表，产生奖金的会员ID，订单表记录，设置数组
		public function calculate(&$from,$userid,$sale=null)
		{			
			$user   =M('会员')->where(array("id"=>$userid))->lock(true)->find();
			$numcons =$this->getcon('numcon',array("minNum"=>1,"maxNum"=>1,"minlv"=>1,"maxlv"=>1,"direction"=>'up',"val"=>"",'where'=>''));
			if($numcons){
				//循环配置
				foreach($numcons as $con)
				{
					if($con['direction']=='up'){
						$map=array(
							$this->numName=>array(array('egt',$user[$this->numName]-$con['maxNum']),array('elt',$user[$this->numName]-$con['minNum'])),
						);
						$upusers=D("user")->where($map)->select();
						foreach($upusers as $upuser)
						{
							$up_rs_lv=$upuser[$this->lvName];
							$wheredata=array('U'=>&$user,'M'=>&$upuser,'S'=>&$sale);
							if($con['minlv'] <= $up_rs_lv && $con['maxlv'] >= $up_rs_lv && transform($con['where'],array(),$wheredata))
							{
								if(!in_array($upuser['编号'],$this->_userCache)){
									//得到最终数字
									$prizenum=getnum($from['t_recnum'],$con['val'],$this->decimalLen,$upuser[$this->name.'比例']);
									//增加奖金
									$this->addprize($upuser,$prizenum,$user,substr($con['val'],-1,1) == '%'?$from['t_recnum'].'*'.$con['val']:'',$user[$this->numName]-$upuser[$this->numName]);
									$this->_userCache[]=$upuser['编号'];
								}
							}
						}
					}else{
						$map=array(
							$this->numName=>array(array('egt',$user[$this->numName]+$con['minNum']),array('elt',$user[$this->numName]+$con['maxNum']))
						);
						$upusers=D("user")->where($map)->select();
						foreach($upusers as $upuser)
						{
						  	$up_rs_lv=$user[$this->lvName];
							$wheredata=array('U'=>&$upuser,'M'=>&$user,'S'=>&$sale);
							if($con['minlv'] <= $up_rs_lv && $con['maxlv'] >= $up_rs_lv && transform($con['where'],array(),$wheredata))
							{
								if(!in_array($user['编号'],$this->_userCache)){
									//得到最终数字
									$prizenum=getnum($from['t_recnum'],$con['val'],$this->decimalLen,$user[$this->name.'比例']);
									//增加奖金
									$this->addprize($user,$prizenum,$upuser,substr($con['val'],-1,1) == '%'?$from['t_recnum'].'*'.$con['val']:'',$user[$this->numName]-$upuser[$this->numName]);
									$this->_userCache[]=$user['编号'];
								}
							}
						}
					}
				}
			}
			$netcons =$this->getcon('netcon',array("minNum"=>1,"maxNum"=>1,"minlayer"=>-1,"maxlayer"=>-1,"minlv"=>1,"maxlv"=>1,"direction"=>'up',"val"=>"",'where'=>''));
			if($netcons){
				//上级
				$net = X('*@'.$this->netName);
				//取得网络上级
				$upusers=$net->getups($user);
				$lvreg=$user[$this->lvName];
				$thislayer=0;
				foreach($upusers as $upuser)
				{
					//循环配置
					$thislayer++;
					foreach($netcons as $con)
					{
						$minLayer = transform($con['minlayer'],$upuser);
						$maxLayer = transform($con['maxlayer'],$upuser);
						$wheredata=array('U'=>&$user,'M'=>&$upuser,'S'=>&$sale);
						if($minLayer <= $thislayer && ($maxLayer==-1 || $maxLayer >= $thislayer))
						{
							//得到最终的奖金额
							$prizenum=getnum($from['t_recnum'],$con['val'],$this->decimalLen,$upuser[$this->name.'比例']);
							//获得一条线上
							$map=array(
								$this->numName=>array(array('egt',$upuser[$this->numName]-$con['maxNum']),array('elt',$upuser[$this->numName]-$con['minNum'])),
							);
							//获得能拿钱的会员
							$upuserss=M('会员')->where($map)->select();
							foreach($upuserss as $upuse)
							{
								$up_rs_lv=$upuse[$this->lvName];
								$wheredata=array('U'=>&$upuser,'M'=>&$upuse,'S'=>&$sale);
								if($con['minlv'] <= $up_rs_lv && $con['maxlv'] >= $up_rs_lv && transform($con['where'],array(),$wheredata))
								{
									if(!in_array($upuse['编号'],$this->_userCache)){
										//增加奖金
										$this->addprize($upuse,$prizenum,$user,substr($con['val'],-1,1) == '%'?$from['t_recnum'].'*'.$con['val']:'',$thislayer);
										$this->_userCache[]=$upuse['编号'];
									}
								}
							}
						}
					}
				}
			}
			unset($upusers);
			unset($user);
			unset($this->_userCache);
		}
	}
?>