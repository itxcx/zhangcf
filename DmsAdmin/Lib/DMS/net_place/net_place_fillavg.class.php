<?php
/*在自己小区下边排列
    本类由net_place的autoset方法进行调用，用于处理特定的排网算法
	automode="fillavg"        表示小公排,平均排列
	automode="fillavg 1"      表示大公排,平均排列
	平均公排规则
	                 顶点
	      1                         2
   3            5            4                 6    
7   11      9   13       8    12        10       14
*/
class net_place_fillavg
{
	static function run($net,$user,$big='')
	{
		//如果是大公排,直接查顶点会员并返回结果
		if($big != '')
		{
			$user = M('会员')->where(array($net->name.'_层数'=>1))->find();
			return self::fillavg($net,$user);
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
		return self::fillavg($net,$user);
	}
	//针对某一个会员作为顶点进行公排处理
	static function fillavg($net,$user)
	{
		/*
		   根据顶部的介绍.我们可以得到一个数字顺序和网体位置的一种相对关系
		   1A
		   2B
		   3AA
		   4BA
		   5AB
		   6BB
		   7AAA
		   8BAA
		   9ABA
		  10BBA
		  11AAB
		  12BAB
		  13ABB
		  14BBB
		  首先我们要实现根据数字得到上边的对应网体结构文字
		  然后把下级网体已相对网体位置作为KEY进行isset()即可得到结论
		*/
		//首先取得会员所有下级,已编号为键,已网体数据为值
		$downs  = array();
		$downs  = M('会员','dms_')->where($net->name. "_网体数据 like '".$user[$net->name."_网体数据"]."%' and ".$net->name."_上级编号!='' and id<>".$user['id'])
		          ->lock(true)->getField('编号,'.$net->name.'_网体数据');
		if(count($downs)>0){
			//循环所有下级,并对单元做引参处理
			foreach($downs as &$down)
			{
				//如果上级本身也有网体数据,则要把下级的网体数据,含有自己的那部分删除,才能够得到相对网体数据
				if($user[$net->name."_网体数据"]!="" && strpos($down,$user[$net->name."_网体数据"])===0)
				{
					$down=substr($down,strlen($user[$net->name."_网体数据"]));
				}
				//替换掉所有的数字和横线,这样网体数据只有A,B,A,A这类内容(如果region设置中本身带数字就可能悲剧)
				$down = preg_replace( "/[0-9]+-/",'',$down);
				//替换掉,这样网体数据只有ABAA这样
				$down = str_replace( ',','',$down);
			}
			//数组键值和Key值做互换处理
			$downs=array_flip($downs);
		}
		//做一个增量处理
		$i=1;
		while($i<100000)
		{
			//根据数字的得到,相对位置数据,具体看算法说明
			$regs = self::getstr($net,$i);
			//如果没有isset命中.说明特定位置没有人
			if(!isset($downs[$regs]))
			{
				//判断生成的字符长度为一位,则表示直属的区没有人
				if(mb_strlen($regs,'utf-8')==1)
				{
					//返回上级为自己,以及生成的区域
					return array($user['编号'],$regs);
				}
				else
				{
					//第一个参数,假设生成的$regs='AB'并且没有命中
					//那么要返回的信息应该是A的B区
					//首先去掉最后一位得到A,并根据downs的数组,得到了A这个位置的编号
					//在通过原来的$regs得到最后一位,表示要放在A下边的位置
					return array((string)$downs[mb_substr($regs,0,-1,'utf-8')],mb_substr($regs,-1,1,'utf-8'));
				}
			}
			$i++;
		}
	}
	/*
	* 层数计算 当前的数字排序 以及区位数 获取层数
	* 17 3
	* $y=1<18 $y=1+3 $l=1
	* $y=4<18 $y=4+9 $l=2
	* $y=13<18 $y=13+27 $l=3
	* $y=40<18 $y=40+27 $l=4 
	*/
	static function getlayer($id,$num)
	{
		$x=1;
		$y=1;
		$l=0;
		while($y<$id+1)
		{
			$x=$x*$num;
			$y+=$x;
			$l++;
		}
		return $l;
	}
	/*
	* 获取当层的最左数字以及最右数字
	* 4 3
	* 最大为81+((1-81)/(1-3))-1=120
	* 1,0+3=3  1 2 3
	* 4,3+9=12 4 5 6 7 8 9 10 11 12
	* 13,12+27=39 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31 32 33 34 35 36 37 38 39
	* 40,39+81=120 40 41 42 43 44 45 46 47 48 49 50 ... 120
	*/
	static function getlrnum($layer,$num)
	{
		$maxnum=pow($num,$layer)+(1-pow($num,$layer))/(1-$num)-1;
		$minnum=$maxnum/$num;
		return array($minnum,$maxnum);
	}
	//根据id取得相对网体数据
	static function getstr($net,$id)
	{
		//我们ID=14得到BBB为例
		//得到net的region设置
		$region = $net->getBranch();
		//取得分支数
		$num=count($region);
		//得到层数,根据ID计算出现在应该属于第几层
		$l=self::getlayer($id,$num);
		/*
		* 得到本层左右数,返回一个数组array(最左边的id,最右边的id)
		* 注:以ID=14为例,返回了array(7,14)
		*/
		$lr = self::getlrnum($l,$num);
		/*
		* id要设为本层左侧用1作为起点的数量
		* id=14-(7-1)
		* id=8
		*/
		$id -= ($lr[0]-1);
		//层数ID位置,也从本层最左边的起点,设置为1-8
		$lr[1]-=($lr[0]-1);
		$lr[0]=1;
		/*
		* 计算每条线占本层数的数量
		* 7-14返回的数据可以看出,最后一位,是前4个都是A结尾,后4个都是B结尾,所以我们根据
		* 本层起点所在的位置,就能够推算出最后一位是多少
		* $langth=8/2
		* $langth=4
		*/
		$langth=$lr[1]/$num;
		/*计算末尾一位的数字
		* $ret=$region[(int)floor(7/4)]
		* $ret=$region[1];
		* $ret='B';即得到了BBB中对应的最后一个B
		*/
		$ret=$region[(int)floor(($id-1)/$langth)];
		/*
		    计算前边的所有位数
		    7-14中生成的文字的前两位可以发现，他们的结构有点类似于逆向的二进制数
		    所以只需要做除法循环就能得到
		    第一次循环
		    {
		        $lr[1]=4;
		        $id     = 8 - (4*1)
		        $id     = 4
		        $langth = 4/2
		        $ret2   =  $region[int(3/2)].'';
		        $ret2   =  'B'.'';
		    }
		    第二次
		    {
		        $lr[1]  = 2;
		        $id     = 4 - 2*int(3/2)
		        $id     = 2
		        $langth = 2/2
		        $ret2   = $region[int(1/1)].'';
		        $ret2   = 'B'.'B';
		    }
		*/
		$ret2='';
		while($lr[1]>$num)
		{
			//设置最新封顶区域
			$lr[1]  =  $langth;
			//设置新ID
			$id    -=  $langth*(int)floor(($id-1)/$langth);
			//设置新边长
			$langth =  $lr[1]/$num;
			$ret2   =  $region[(int)floor(($id-1)/$langth)].$ret2;
		}
		//return 'BB'.'B';
		return $ret2.$ret;
	}
}
?>