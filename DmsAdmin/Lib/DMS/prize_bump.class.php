<?php
	class prize_bump extends prize
	{
		//产生奖金模式
		public $prizeMode = 1;
		//网络体系名称
		public $netName = '';
		//扫单模式,0为平均,1为整数
		public $clear_mode = 1;
		//扫单反转
		public $against = true;
		//来源表条件
		public $modAdd = true;
		//碰对最大次数
		public $bumpMax=0;
		//判断是否显示奖金构成
		public $isSee = true;
		function scal($sale)
		{
			$this->cal();
		}
		function cal()
		{
			if(!$this->ifrun()) return;
			$net   = X('*@'.$this->netName);
			if($net === NULL)
			{
				throw_exception($this->name."计算时网络体系获取失败,请检查其netName设置是否正确");
			}
			$branch= $net->getBranch();
			$cons  = $this->getcon('con',array("bump"=>"","val"=>"","where"=>"",'only'=>false,'top'=>0));
			//要确认当前是否为秒结
			
			if($this->parent()->tleMode == 's' || $this->tleMode == 's')
			{
				$isscal = true;
			}
			else
			{
				$isscal = false;
			}
			//清空和统计结转业绩
			$updata=array();
			//更新业绩表有业绩的会员的结转业绩为 本期+上期结转
			$_caltime = $this->parent('tle')->_caltime;
			//对配置中的bump信息数组拆分
			foreach($cons as $key =>$con)
			{
				$cons[$key]["bump"]=explode(":",$cons[$key]["bump"]);
				if(is_numeric($con['val']))
				{
					throw_exception("prize_bump的val配置不能使用纯数字");
				}
			}
			if($net->cache)
			foreach($net->cache as $user)
			{
				$t_cons=array();
				//得到符合当前会员条件的CONS
				foreach($cons as $con)
				{
					if(transform($con["where"],$user))
					{
						$t_cons[]=$con;
					}
				}
				/*
					对关于业绩处理情况的说明
					如果为秒结算处理
					1.net_place->event_diffTime出发跨日事件,从新统计结转和本日业绩
					2.net_place->event_valadd()触发插业绩事件
					3.net_place->addpv()增加插业绩原始记录pid=0,或者对小区业绩增加
					4.net_place->addUpPv()对上级业绩增加.和会员表本期本日累积增加
					5.*秒结bump对本期业绩碰对
					6.*秒结bump扣减本期业绩字段
					7.入奖金表
					如果为日结算处理
					1.net_place->event_valadd()触发插业绩事件
					2.net_place->addpv()增加插业绩原始记录pid=0,或者对小区业绩增加
					3.net_place->addUpPv()对上级业绩增加.和会员表本期本日累积增加
					4.*net_place->event_cal();统计所有会员本期和结转业绩保存到会员表
					5.*日结bump对本期业绩+结转业绩碰对
					6.入奖金表
					7.*net_place->event_caldayover();本日所有奖金计算完成以后.统计下一天的本日业绩和结转业绩
					秒结的特殊处理,主要是为了避免秒结操作时对业绩的全部统计.
					因为秒结也不会出现业绩回退的情况.所以除了生成奖金表中的本期业绩和结转业绩要准确以外.不需要对业绩进行汇总更新.
					但是网络图显示上会有一些异常.后续还需要做开发
				*/
				$bumpval=array();
				$use    =array();
				foreach($branch as $key)
				{
					if($isscal)
					{
						$bumpval[$key]=$user[$net->name.'_'.$key.'区本期业绩'];
					}
					else
					{
						if($user[$net->name.'_'.$key.'区结转业绩']<0 || $user[$net->name.'_'.$key.'区本期业绩']<0){
							//业绩有负数产生
							throw_exception("结算".$this->name."时会员【".$user['编号']."】的".$key."区业绩错误");
						}
						$bumpval[$key]=$user[$net->name.'_'.$key.'区本期业绩']+$user[$net->name.'_'.$key.'区结转业绩'];
					}
					$use[$key]=0;
				}
				//保留一份原始业绩,用于计算完毕后统计剩余量
				$oldbumpval=$bumpval;
				$this->calculate($bumpval,$t_cons,$branch,$user,$use);
				//增加将近使用记录
				foreach($branch as $key => $name)
				{
					if($oldbumpval[$name]>$bumpval[$name])
					{
						//将记录放在结算日的最后一秒
						$indata = array('time'=>$_caltime + 86400 - 1,
							'userid'=> $user['id'],
							'fromid'=> $user['id'],
							'val'   => -($oldbumpval[$name] - $bumpval[$name]),
							'pid'   => -1,
							'saleid'=> 0,
							'region'=>$key+1
						);
						$pid = M($net->name.'_业绩')->add($indata);
						if($isscal)
						{
							M('会员')->where(array('id'=>$user['id']))->setDec($net->name.'_'.$name.'区本期业绩',($oldbumpval[$name] - $bumpval[$name]));
						}
					}
				}
			}
			$this->prizeUpdate();
		}
		public function calculate(&$bumpval,$cons,$branch,$user)
		{
			$result=0;
			foreach($cons as $con)
			{
				$bumpval2=array();
				$bumpset =array();
				//对碰比进行循环,得到可碰比业绩指针
				foreach($con["bump"] as $key=>$val)
				{
					if($val!="")
					{
						$bumpval2[]=&$bumpval[$branch[$key]];
						$bumpset[] =(float)transform($val);
					}
				}
				//如果系统存在翻转机制,则对碰对之前进行碰比设定的排序
				if($this->against)
				{
					rsort($bumpset);
				}
				//对相关单元从新排序
				while(true)
				{
					if($this->against)
					{
						rsort($bumpval2);
					}
					//定义可能出现最大扫单量
					$bump=($this->clear_mode==0) ? 1 : floor($bumpval2[0]/$bumpset[0]);
					//判定扫单量
					foreach($bumpset as $key=>$val)
					{
						if($bumpset[$key]!=0 && $bump>floor($bumpval2[$key]/$bumpset[$key]))
						{
							$bump=floor($bumpval2[$key]/$bumpset[$key]);
						}
					 }
					 
					 foreach($bumpset as $key=>$val)
					 {
					 	 //设置被使用数量
						$bumpval2[$key] -= $bumpset[$key] * $bump;
					 }
					 $prizenum = getnum($bump,$con["val"]);
					 if($con['top']!=0 && $prizenum > $con['top']){
						$prizenum = $con['top'];
					 }
					 $result += $prizenum;
					if(strstr($con["val"],'%')){
						$calculateType =$bump.' * '.$con["val"]; 
					}else if(strstr($con["val"],'*')){
						$calculateType = $bump . $con["val"]; 
					}else{
						$calculateType = $con["val"];
					}
					$this->addprize($user,$prizenum,$user,$calculateType);
					 if($bump==0 || $con['only'])
					 break;
				}
			}
			return $result;
		}
		//用于模拟得到碰对数据量,结余的函数,以便修正数据库时使用
		/*
			testbump('1:2',array('A'=>10,'B'=>10));
			返回结果为
			array(5) {
			  ["bump"] => float(5)
			  ["A"] => float(5)
			  ["B"] => float(0)
			  ["A-"] => float(-5)
			  ["B-"] => float(-10)
			}
		*/
		public function testbump($exp='1:1',$bumpval = null)
		{
			if(!$bumpval)
			{
				die('参数不正确');
			}
			$oldbumpval=$bumpval;
			$net = X('net_place@'.$this->netName);
			$branch= $net->getBranch();
			$bumpval2=array();
			$bumpset =array();
			$bumpsum=0;
			$exp = explode(':',$exp);
			foreach($exp as $key=>$val)
			{
				$bumpval2[]=&$bumpval[$branch[$key]];
				$bumpset[] =(float)$val;
			}
			//如果系统存在翻转机制,则对碰对之前进行碰比设定的排序
			if($this->against)
			{
				rsort($bumpset);
			}
			//对相关单元从新排序
			while(true)
			{
				if($this->against)
				{
					rsort($bumpval2);
				}
				//定义可能出现最大扫单量
				$bump=($this->clear_mode==0) ? 1 : floor($bumpval2[0]/$bumpset[0]);
				//判定扫单量
				foreach($bumpset as $key=>$val)
				{
					if($bumpset[$key]!=0 && $bump>floor($bumpval2[$key]/$bumpset[$key]))
					{
						$bump=floor($bumpval2[$key]/$bumpset[$key]);
					}
				 }
				 
				 foreach($bumpset as $key=>$val)
				 {
				 	 //设置被使用数量
					$bumpval2[$key] -= $bumpset[$key] * $bump;
				 }
				 $bumpsum += $bump;
				 if($bump==0)
				 break;
			}
			//计算负数量
			//=array()
			foreach($branch as $region)
			{
				$bumpval[$region.'-']=$bumpval[$region]-$oldbumpval[$region];
			}
			return array_merge(array('bump'=>$bumpsum),$bumpval);
		}
		//数组两两组合方式
		private function getCombinationToString($arr,$m=2){
			$result = array();
			if ($m ==1){
				return $arr;
			}
			if ($m >= count($arr) ){
				$result[] = implode(',' , $arr);
				return $result;
			}
			$temp_firstelement = $arr[0];
			unset($arr[0]);
			$arr = array_values($arr);
			$temp_list1 = $this->getCombinationToString($arr, ($m-1));
			foreach ($temp_list1 as $s){
				$s = $temp_firstelement.','.$s;
				$result[] = $s;
			}
			unset($temp_list1);
			$temp_list2 = $this->getCombinationToString($arr, $m);
			foreach ($temp_list2 as $s){
				$result[] = $s;
			}
			unset($temp_list2);
			return $result;
		}
		//奖金删除  所用业绩回退 秒结对碰业绩不处理
		public function event_rollback($time)
		{
			if($this->getTleMode() !== 's')
			{
				M($this->netName.'_业绩')->where('time>='.$time.' and pid=-1')->delete();
				//更新左右区
				foreach(X("@".$this->netName)->getBranch() as $key=>$Branch)
				{
					//更新结转业绩
					M()->execute('update dms_会员 a left join (select  userid,sum(val) val from dms_'.$this->netName.'_业绩 where pid<>0 and time<' . ($time) . ' and region=' . ($key+1) . ' group by userid) b 
					on a.id=b.userid set a.'.$this->netName.'_'.$Branch.'区结转业绩=ifnull(b.val,0)');
					//更新本日业绩
					M()->execute("update dms_会员 a left join (select  userid,sum(val) val from dms_".$this->netName."_业绩 where pid<>0 and time>=" . ($time) . " and region=" . ($key+1) . " group by userid) b 
					on a.id=b.userid set a.".$this->netName.'_'.$Branch."区本期业绩=ifnull(b.val,0), a.".$this->netName.'_'.$Branch."区本日业绩=ifnull(b.val,0)");
				}
			}
		}
	}
?>