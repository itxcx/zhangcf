<?php
	class prize_np2rec extends prize
	{
		public $prizeMode = 1;
		//奖金来源表达式
		public $rowName = '';
		//奖金来源类型
		public $rowFrom = '';
		//来源表条件
		public $where = '';
		//起征点
		public $startNum = 0;
		//小数精度
		public $decimalLen = 2;
		//对应网络体系
		public $netName = '';
		//con过滤器
		public $conFilter=array('con'=>array("minLayer","maxLayer","minlv","maxlv","val","where"));
		function scal($sale)
		{
			$this->cal();
		}
		//日结驱动
		function cal()
		{
			if(!$this->ifrun()) return;
			
			if(!X('levels@'.$this->lvName) instanceof levels)
			{
				throw_exception($this->name.'计算失败,因其lvName属性未找到对应的级别模块');
			}
			$m_user=M('会员');
			$cons = $this->getcon('con',array("minlv"=>1,"maxlv"=>1,"val"=>"","where"=>'','minLayer'=>0,'maxLayer'=>0));
			//寻找还没有被计算的点位
			$net = X('net_place2@'.$this->netName);
			if($net == null)
			{
				throw_exception($this->name.'的$netName设置有误，未找到对应名称的网体模块');
			}
			$nodes = M($this->netName,'dms_')->where(array('计算'=>0))->select();
			//设置层数
			
			foreach($nodes as $node)
			{
				
				$upNodes = $net->getups($node);
				
				$user = $m_user->find($node['userid']);
				$layer=0;
				foreach($upNodes as $upNode)
				{
					$layer++;
					$upuser = $m_user->find($upNode['userid']);
					$up_rs_lv = $upuser[$this->lvName];
					$ifarr = array('M'=>$upuser,'U'=>$user);
					foreach($cons as $con)
					{
						if($con['minlv'] <= $up_rs_lv && $con['maxlv'] >= $up_rs_lv && $con['minLayer'] <= $layer && $con['maxLayer'] >= $layer && transform($con['where'],array(),$ifarr))
						{
							$prizenum = getnum(1,$con['val'],$this->decimalLen,$upuser[$this->name.'比例']);
							$this->addprize($upuser,$prizenum,$user,$user['编号'].'('.$node['序号'].')=>'.$upuser['编号'].'('.$upNode['序号'].')',$layer);
						}
					}
				}
			}
			unset($cons);
			$this->prizeUpdate();
		}
	}
?>