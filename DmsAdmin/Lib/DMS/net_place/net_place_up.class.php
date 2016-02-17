<?php
/*
    找到上边（包含自己）第一个N层排满的人，作为下一步要 平均公排 的顶点
    本类由net_place的autoset方法进行调用，用于处理特定的排网算法
	
	配置示例：
	up 2 4,fillavg 或 up 2 4,fill

	up 2 4,fillavg 表示前四层大公排平均排列，后面的规则是：找 推荐上级或者推荐上级在该网的上级，若有符合在该网中下两层排满的会员，则作为顶点执行后面的平均排列fillavg。后面的操作不可没有。

	netplace中方法getdownNum供调用，查询符合条件层数会员的数量
*/
class net_place_up
{
	static function run($net,$user,$downLayerNum=1,$headLayerNum = 0)
	{
		//如果顶点没有完成N层排满，要以顶点作为第二个排列方式的顶点
		//$headLayerNum 表示前面几层直接进行后面的操作,可以不设置，如果设置，不应小于2
		//$downLayerNum  表示需要判断层满的层数
		
		//获取区的数量
		$Branchs = $net->getBranch();
		$regionNum = count($Branchs);
		if($headLayerNum != 0 && $headLayerNum < $downLayerNum)
		{
			throw_exception($net->name.'在automode中使用up时，初期排满属性不能小于顶点判定层数');
		}
		if($headLayerNum == 1)
		{
			throw_exception($net->name.'在automode中使用up时,至少要为2');
		}
		//执行大公排跳排
		if($headLayerNum > 0)
		{
			$fourthNum = M('会员')->where(array($net->name.'_层数'=>$headLayerNum))->count();
			if($fourthNum<pow($regionNum,$headLayerNum - 1))
			{
				$user = M('会员')->where(array($net->name.'_层数'=>1))->find();
				//返回原始点编号
				return array($user['编号']);
			}
		}
		//判断如果这个会员不是由其他的排列模块处理过的，则需要从其推荐人开始找起
		if(!isset($user['this']) || !$user['this'])
		{
			if($net->fromNet == '')
			{
				throw_exception($net->name.'在automode中使用min时，需要指定fromNet属性，以便确定对应的推荐网络');
			}
			
			$userTj = M('会员')->where(array('编号'=>$user[$net->fromNet.'_上级编号']))->find();
			if(!$userTj)
			{
				throw_exception($net->name.'在automode中使用up时没有找到对应的'.$net->fromNet.'上级');
			}else
			{
				//如果推荐人是顶点或者满足排满数量
				if($userTj[$net->name.'_层数'] == 1 || $net->getdownNum($userTj,$downLayerNum,$downLayerNum)==pow($regionNum,$downLayerNum))
				{
					return array($userTj['编号']);
				}else
				{
					$users = $net->getups($userTj);
					foreach($users as $u)
					{
						if($u[$net->name.'_层数'] == 1 || $net->getdownNum($u,$downLayerNum,$downLayerNum)==pow($regionNum,$downLayerNum))
						{
							return array($u['编号']);
						}
					}
				}
				throw_exception($net->name.'在automode中使用up时没有找到符合条件的上级');
			}
		}
		else
		{
			throw_exception($net->name.'在automode中使用up时在其之前不能有其他处理算法,还需要考法');
		}
	}
}
?>