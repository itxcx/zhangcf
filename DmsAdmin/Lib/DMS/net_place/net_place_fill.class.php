<?php
/*在自己小区下边排列
    本类由net_place的autoset方法进行调用，用于处理特定的排网算法
	automode="fill"         表示小公排
	automode="fill true"    表示大公排
*/
class net_place_fill
{
	static function run($net,$user,$big='')
	{
		//如果是大公排,直接查顶点会员并返回结果
		if($big != '')
		{
			$user = M('会员')->where(array($net->name.'_层数'=>1))->find();
			return self::findEmptyPostionByChilds($net,$user);
		}
		//判断如果这个会员不是由其他的排列模块处理过的，则需要从其推荐人开始找起
		if(!isset($user['this']) || !$user['this'])
		{
			if($net->fromNet == '')
			{
				throw_exception($net->name.'在automode中使用min时，需要指定fromNet属性，以便确定对应的推荐网络');
			}
			$user = M('会员')->where(array('编号'=>$user[$net->fromNet.'_上级编号']))->find();
			if(!$user)
			{
				throw_exception($net->name.'在automode中使用fill时没有找到对应的'.$net->fromNet.'上级');
			}
		}
		return self::findEmptyPostionByChilds($net,$user);
	}
	static function findEmptyPostionByChilds($net,$user)
	{
		$Model			= M();
		//获取分支名称
		$locationList	= array();
		foreach($net->getcon("region",array("name"=>"")) as $val)
		{
			$locationList[] = $val['name'];
		}
		$locationCount		= count($locationList);

		//****** 处理管理区位 ******

		foreach( $locationList as $key=>$location )
		{
			$net->locationList[ $location ] = $key;
		}

		//*******************************************
		$sql				= "select {$net->name}_层数 as 所在层数 from dms_会员 where {$net->name}_层数>=1 and (";
		$sql2				= '';
		foreach( $locationList as $key=>$location )
		{
			if( $key >=1 )
			{
				$sql .= " or ";
				$sql2 .= " or ";
			}
			$sql		.= "find_in_set( '{$user['id']}-{$location}',{$net->name}_网体数据 )";
			$sql2		.= "{$net->name}_{$location}区=''";
		}

		$sql		.= " or id={$user['id']})";
		$sql		.= " and ({$sql2})";
		$sql		.= " order by {$net->name}_层数 asc limit 1 for update";

		//获取空位置所在的层数
		$result		= $Model->query($sql);
		$layer		= intval($result[0]['所在层数']);

		//获取找出该层的所有空位
		$field_str	= "id,编号,{$net->name}_网体数据";
		foreach( $locationList as $key=>$location )
		{
			$field_str	.= ",{$net->name}_{$location}区";
		}
		$sql		= "select {$field_str} from dms_会员 where {$net->name}_层数={$layer} and (";

		foreach( $locationList as $key=>$location )
		{
			if( $key >=1 ) $sql .= " or ";
			$sql		.= "find_in_set( '{$user['id']}-{$location}',{$net->name}_网体数据 )";
		}
		$sql		.= " or id={$user['id']} ) and ( ";

		foreach( $locationList as $key=>$location )
		{
			if( $key >=1 ) $sql .= " or ";
			$sql		.= "{$net->name}_{$location}区=''";
		}
		$sql		.= ') for update';
		//这种查询，首先要排除 空位置直接在下属子节点的情况
		$result		= $Model->query($sql);
		//空位排序, 按从左到右的顺序
		$key_order		= array();
		$result_order	= array();
		foreach($result as $key=>$vo)
		{
			$netdata = $vo["{$net->name}_网体数据"] ;
			$netdata = preg_replace( "/[0-9]+-/",'',$netdata);
			$netdata = str_replace( ',','',$netdata);
			$key_sign					= str_replace( $locationList , $net->locationList , $netdata);
			$key_order[]				= $key_sign;
			$result_order[ $key_sign ]	= $vo;
			if( $vo['id'] == $user['id']  )
			{
				unset($result_order);
				$result_order[ $key_sign ]	= $vo;
				break;
			}
		}
		unset($result);

		//以order做为key重新创建数组
		uksort($result_order,'strnatcmp');
		$result_order		= current($result_order);
		$finally_result[]	= (string)$result_order['编号'];
		//确定区位
		foreach( $locationList as $key=>$location )
		{
			if( $result_order["{$net->name}_{$location}区"] == '' )
			{
				$finally_result[]	= $location;
				break;
			}
		}
		return $finally_result;
	}
}
?>