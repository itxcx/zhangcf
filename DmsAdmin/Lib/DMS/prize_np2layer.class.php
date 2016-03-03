<?php
class prize_np2layer extends prize
{
	//产生奖金模式
	public $prizeMode = 1;
	//来源表条件
	public $where = '';
	public $netName ='';
	public $conFilter=array('con'=>array("minlayer","maxlayer","minlv","maxlv","val","where"));
	function scal($sale)
	{
		$this->cal();
	}
	function cal()
	{
		if(!$this->ifrun()) return;
		$net = X('*@'.$this->netName);
		if($net === NULL)
		{
			throw_exception($this->name."计算时网络体系获取失败,请检查其netName设置是否正确");
		}
		$maxlayer = 0;
		$minlayer = 9999999;
		$cons = $this->getcon('con',array("minlayer"=>1,"maxlayer"=>1,"minlv"=>1,"maxlv"=>1,"val"=>"","where"=>""));
		foreach($cons as $con)
		{
			//用于优化,得到最大会获取多少层的会员
			if($maxlayer <= $con['maxlayer'])
			$maxlayer = $con['maxlayer'];
			if($minlayer >= $con['minlayer'])
			$minlayer = $con['minlayer'];
		}
		$nodes = M($this->netName,'dms_')->where(array('计算'=>0))->select();
		$layer=0;
		$Branch =$net->getBranch();
		foreach($nodes as $node)
		{
			$upnodes=$net->getups($node,$minlayer,$maxlayer,'',true);
			foreach($upnodes as $upnode)
			{
				//得到层数
				$layer = $node["层数"] - $upnode["层数"];
				$cengset = $upnode[$this->name.'数据'] == NULL ? array():unserialize($upnode[$this->name.'数据']);
				//如果当前层计算过。则不需要在计算
				if(isset($cengset[$layer])) continue;
				{
					$layer_have=true;
					//$downwhere="状态='有效' and 审核日期<".($this->_caltime+86400);
					$downnum=count($net->getdown($upnode,$layer,$layer,'',true));
					if($layer==1) $realnum=count($Branch);
					else $realnum=pow(count($Branch),$layer);
					if($downnum != $realnum) $layer_have = false;
				}
				//产生奖金
				if($layer_have)
				{
					//对产生缓存重新赋值
					$upuser = M('user','dms_')->find($upnode['userid']);
					$cengset[$layer]=1;
					//判断走完所有配置，一共当次得到了多少奖金
					$sumPrize=0;
					foreach($cons as $con)
					{
						$up_rs_lv = $upuser[$this->lvName];
						//取双方最小级别,则做降级操作
						$wheredata = array('M'=>&$upuser,'N'=>&$upnode);
						if($con['minlv'] <= $up_rs_lv && $con['maxlv'] >= $up_rs_lv && 
						   $con['minlayer'] <= $layer && $con['maxlayer'] >= $layer && transform($con['where'],array(),$wheredata))
						{
							//得到最终数字
							$prizenum = getnum(1,$con['val'],$this->decimalLen,$upuser[$this->name.'比例']);
							$sumPrize += $this->addprize($upuser,$prizenum,$upuser,'',$layer);
						}
					}
					$datas[$this->name.'数据'] = serialize($cengset);
					$datas[$this->name.'累计'] = array('exp',$this->name.'累计+'.$sumPrize);
					M($this->netName)->where(array("id"=>$upnode["id"]))->save($datas);
				}
			}
		}
		unset($cons);
		$this->prizeUpdate();
	}
}
?>