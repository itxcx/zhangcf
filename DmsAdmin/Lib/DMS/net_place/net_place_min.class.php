<?php
/*在自己小区下边排列
    本类由net_place的autoset方法进行调用，用于处理特定的排网算法
	automode="min"        表示一直找自己的小区的小区的小区...
	automode="min 1"      表示找自己小区一层（这个是一个非终止的寻找）
	automode="min 1 左"   表示找到自己的一个小区，然后按照固区一直找到末尾
*/
class net_place_min
{
	static function run($net,$user,$layer=0,$whileRegion='')
	{
		//$layer  表示要向下找的指定层数
		//$whileRegion 表示小区寻找完毕之后，要固定找的区
		//找到所有的区的设定
		$Branch = $net->getBranch();
		//对$whileRegion参数做一个校验
		if($whileRegion != '' && !in_array($whileRegion,$Branch)) 
		{
			throw_exception($net->name.'的automode参数设置不正确，min后的第二个参数并不是一个有效的区域名称，请和_region设置做比对');
		}
		if($net->fromNet == '')
		{
			throw_exception($net->name.'在automode中使用min时，需要指定fromNet属性，以便确定对应的推荐网络');
		}
		$user = M('会员')->where(array('编号'=>$user[$net->fromNet.'_上级编号']))->find();
		if(!$user)
		{
			throw_exception($net->name.'在automode中使用min时没有找到对应的'.$net->fromNet.'上级');
		}
		//存储递归找到下级的编号，如果发现有重复，则表示此处理程序进入了一个死循环
		$haves = array($user['编号']);
		//如果layer为0要一直循环，否则是for循环
		for($iLayer=0;$layer ==0 || $iLayer< $layer ; $iLayer++){
			
			//设定小区默认为第一个区
			$sumarr=array();
			//循环所有区
			foreach($Branch as $region)
			{
				//如果要寻找的人某一个区是空的，则自动安置
				if($user[$net->name.'_'.$region.'区'] == ""){
					//返回编号和区域信息
					return array($user['编号'],$region);
				}
				//统计每个区的累计业绩
				$sumarr[$region] = $user[$net->name.'_'.$region.'区累计业绩'];
			}
			//对业绩数组进行排序
			uasort($sumarr,function ($a,$b){return $a>=$b;});
			//找到了小区重新赋值会员
			$user=M('会员')->where(array('编号'=>$user[$net->name.'_'.key($sumarr).'区']))->find();
			//循环重复判定
			if(in_array($user['编号'],$haves))
			{
				dump($haves);
				throw_exception('在处理小区排列时发现存在循环链，需检查网体数据');
			}
			$haves[]=$user['编号'];
		}
		
		//如果循环到了这里,表示找到指定层数的小区已经结束，但是还没找到最终的节点人
		//如果不在根据某条区一直向下找则需要返回这个编号，由其他算法继续接管
		if($whileRegion == '')
		{
			return array($user['编号']);
		}
		
		//循环直至到底部退出
		while(true)
		{
			if($user[$net->name.'_'.$whileRegion.'区'] == '')
			{
				return array((string)$user['编号'],$whileRegion);
			}
			else
			{
				$user = M('会员')->where(array('编号'=>$user[$net->name.'_'.$whileRegion.'区']))->find();
				//循环重复判定
				if(in_array($user['编号'],$haves))
				{
					dump($haves);
					throw_exception('在处理小区排列时发现存在循环链，需检查网体数据');
				}
				$haves[]=$user['编号'];				
			}
		}
	}
	
}
?>