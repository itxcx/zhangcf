<?php
	class fun_placenum extends stru
	{
		//统计网络业绩的网络关系名
		public $netName = "";
		//包含字段
		//public $row = "day,week,month,year,all";
		//是否包含自己
		public $haveMe = false;
		//产生个人条件
		//public $where = "";
		//计算最小层
		public $MinLayer = -1;
		//计算最大层
		public $MaxLayer = -1;
		//计算统计本周
		public $nowWeek=false;
		//计算统计本月
		public $nowMonth=false;
		//计算统计本年
		public $nowYear=false;
		//
		public $firstinto=false;
		public $cache    =array();
		public $tleMode="";
		/*系统清空事件*/
		public function event_sysclear()
		{
			M()->execute("TRUNCATE TABLE " . 'dms_'.$this->name.'_业绩;');
		}
		public function getTleMode()
		{
			if($this->tleMode!='')
				return $this->tleMode;
			//找到碰对奖模块
			if(X('prize_bump'))
			{
				foreach(X('prize_bump') as $bump){
					if($bump->netName==$this->name){$
						//如果存在碰对奖模块,同时没有设置结算周期,则取奖金表周期
						$this->tleMode = ($bump->tleMode == '' ? $bump->parent()->tleMode : $bump->tleMode);
						$this->tleDay   = ($bump->tleDay == '' ? $bump->parent()->tleDay  : $bump->tleDay);
					}
				}
			}
			else
			{
				$this->tleMode = X('tle@')->tleMode;
				$this->tleDay  = X('tle@')->tleDay;
			}
			return $this->tleMode;
		}
		public function event_scal()
		{
			$rows=$this->getSelRow();
			foreach(X('prize_bump') as $prize_bump)
			{
				if($prize_bump->netName === $this->name)
				{
					$rows=$prize_bump->getSelRow($rows);
				}
			}
	        //定义要查询的字段
	        foreach($this -> getBranch() as $key => $Branch)
	        {
	        	$lstr=$this->name.'_'.$Branch.'区';
	        	$rows[$lstr.'本期业绩'] = 1;
	        	$rows[$lstr.'结转业绩'] = 1;
	        	$rows[$lstr.'累计业绩'] = 1;
	        }
	        $this->cache = M('会员')->lock(true)->getField('id keyid,id,编号,'.implode(array_keys($rows),','));
		}
		public function event_cal($tle,$caltime)
		{
	        $rows=$this->getSelRow();
			foreach(X('prize_bumpcpj') as $prize_bump)
			{
				if($prize_bump->netName === $this->name)
				{
					$rows=$prize_bump->getSelRow($rows);
				}
			}
			if($this->getTleMode() == 's')
			{
				$user_m=M('会员');
				//在秒日混合计算情况下，在日结时取得当前业绩缓存
		        foreach($this -> getBranch() as $key => $Branch)
		        {
		        	$lstr=$this->name.'_'.$Branch.'区';
		        	//本期业绩连表
		        	$user_m->join('(select userid,sum(val) '.$lstr.'本期业绩 from dms_'.$this->name.'_业绩 where time>='.$caltime.' and time<'.$caltime.'+86400 and region='.($key+1).' and pid>0 group by userid) new'.$key.' on dms_会员.id = new'.$key.'.userid');
		        	//结转业绩连表
					$user_m->join('(select userid,sum(val) '.$lstr.'结转业绩 from dms_'.$this->name.'_业绩 where time<'.$caltime.' and region='.($key+1).' and pid<>0 group by userid) jie'.$key.' on dms_会员.id = jie'.$key.'.userid');
					//累计业绩连表
					$user_m->join('(select userid,sum(val) '.$lstr.'累计业绩 from dms_'.$this->name.'_业绩 where time<'.$caltime.'+86400 and region='.($key+1).' and pid>0 group by userid) sum'.$key.' on dms_会员.id = sum'.$key.'.userid');
		        	$rows['new'.$key.'.'.$lstr.'本期业绩'] = 1;
		        	$rows['jie'.$key.'.'.$lstr.'结转业绩'] = 1;
		        	$rows['sum'.$key.'.'.$lstr.'累计业绩'] = 1;
		        }
				$this->cache = $user_m->lock(true)->getField('id keyid,id,编号,'.implode(array_keys($rows),','));
			}
	        //日结或者周结,把结算日之后的
	        if($this->getTleMode() == 'd' || ($this->getTleMode() == 'w' && date('N', $caltime) == (int)$this -> tleDay)){
		        foreach($this -> getBranch() as $key => $Branch)
		        {
		        	$lstr=$this->name.'_'.$Branch.'区';
		        	$rows[$lstr.'本期业绩'] = 1;
		        	$rows[$lstr.'结转业绩'] = 1;
		        	$rows[$lstr.'累计业绩'] = 1;
		        }
	        	$this->cache = M('会员')->lock(true)->getField('id keyid,id,编号,'.implode(array_keys($rows),','));
	        	foreach($this -> getBranch() as $key => $Branch)
	        	{
	        		$vals = M($this->name.'_业绩')->lock(true)->where('pid<>0 and time>='.($caltime+86400).' and region='.($key+1))->group('userid')->field('userid,sum(val) val')->select();
	        		if($vals)
	        		foreach($vals as $val)
	        		{
	        			$this->cache[$val['userid']][$this->name.'_'.$Branch.'区本期业绩'] -= $val['val'];
	        			$this->cache[$val['userid']][$this->name.'_'.$Branch.'区累计业绩'] -= $val['val'];
	        		}
	        	}
	        }
	        elseif($this->getTleMode() == 'w')
	        {
			    foreach($this -> getBranch() as $key => $Branch)
			    {
			    	$lstr = '0 '.$this->name.'_'.$Branch.'区';
			    	$rows[$lstr.'本期业绩'] = 1;
			    	$rows[$lstr.'结转业绩'] = 1;
			    	$rows[$lstr.'累计业绩'] = 1;
			    }
			    $this->cache = M('会员')->lock(true)->getField('id keyid,id,编号,'.implode(array_keys($rows),','));
	        }
		}
		//执行addval
		public function event_valadd($user,$val,$option)
		{
			$this->add($user,$val,$option);
		}
		//增加记录
		public function add($user,$val,$option)
		{
			//取得订单ID
			$saleid = isset($option['saleid']) ? $option['saleid'] : 0 ;
			//产生自身业绩记录
			$indata = array('time'=>systemTime(),
				  'userid'=>$user['id'],
				  'fromid'=>$user['id'],
				  'val'   =>$val,
				  'saleid'=>$saleid,
				  'pid'   =>0,
				);
			//插入原始记录得到Pid
			$pid=M($this->name.'_业绩')->add($indata);
			//对上返业绩
			$this->addUpPv($pid,$val,$user[$this->netName.'_网体数据'],$user['id'],$saleid,systemTime(),$user[$this->netName.'_层数']);
		}
		//原始记录ID,业绩数值,网体数据,业绩来源会员id,订单id,时间,当前层数 
		//更新上级业绩
		private function addUpPv($pid,$val,$netdata,$fromid,$saleid,$time,$cengnum){
			//如果没有网体数据.就表示不需要做任何处理.直接返回
			if(!$netdata) return;
			//网体数据转换
			$t_arrs=array_reverse(explode(',',$netdata));
			$sql=array();
			//区域
			$region2id = array();
			$net = X('*@'.$this->netName);
			if($net->getcon("region",array("name"=>""))){
				foreach($net->getcon("region",array("name"=>"")) as $key=>$Region)
				{
					$region2id[$Region["name"]]=$key+1;
				}
			}
			//业绩缓存
			//$adddata=array();
			//网体数据
			$netstrary=$t_arrs;
			//循环进业绩
			foreach($t_arrs as $key=>$t_arr)
			{
				//对业绩层数增的判定
				if(($this->MinLayer == -1 || $key+1 >= $this->MinLayer) && ($this->MaxLayer == -1 || $key+1 <= $this->MaxLayer)) {
					$data = explode('-',$t_arr);
					//默认累计
				    $inval=false;
				    //第一个点位进业绩
				    if($this->firstinto){
					    //网体数据
					    $netstr=implode(',',array_reverse($netstrary));
					    unset($netstrary[$key]);
					    //判断区域第一个点位
	   					$where=$net->name."_层数=".$cengnum."  and  {$net->name}_网体数据 like '{$netstr}%' and 状态='有效'";
	   					$firstid=M('会员')->where($where)->order("审核日期 asc")->getField('id');
					    //如果是第一个点位
					    if($fromid==$firstid){
					    	$inval=true;
					    }
				    }
				    //判断是否可以进业绩
				    if(($this->firstinto===false && $inval===false) || ($this->firstinto===true && $inval===true)){
						//$adddata[$data[1]][]=$data[0];
					    //$region = $region2id[$data[1]];
					    //插入的语句
				    	$sql[]  = "($time,$data[0],$fromid,$val,$saleid,$pid,$region)";
				    	//对会员的本日本月累计进行增加
				    	$udata=array(
				    		"id"=>$data[0],
				    		$this->name."_{$data[1]}区本期业绩+"=>$val,
				    		$this->name."_{$data[1]}区本日业绩+"=>$val,
				    		$this->name."_{$data[1]}区累计业绩+"=>$val
				    		);
				    	//本周数据
				    	if($this->nowWeek)
					    {
					    	$udata[$this->name."_{$data[1]}区本周业绩+"]=$val;
					    }
					    //本月数据
					    if($this->nowMonth)
					    {
					    	$udata[$this->name."_{$data[1]}区本月业绩+"]=$val;
					    }
					    //本年数据
					    if($this->nowYear)
					    {
					    	$udata[$this->name."_{$data[1]}区本年业绩+"]=$val;
					    }
				    	M("会员")->bSave($udata);
				    }
				}
			}
			//如果SQL数组为空.就退出
			if(!$sql) 
				return;
			$sqlstr = implode($sql,',');
			$sqlstr = 'INSERT INTO dms_'.$this->name.'_业绩 (`time`,`userid`,`fromid`,`val`,`saleid`,`pid`,`region`) VALUES '.$sqlstr;
			M()->execute($sqlstr);
			M("会员")->bUpdate();
		}
		public function event_caldayover($caltime)
		{
			//根据最后一天的结算日期
			if($this->getTleMode() != 's')
			{
		        foreach($this -> getBranch() as $key => $Branch){
		        	//结转等于结转+当天业绩加当天扣除
		            M() -> execute('update dms_会员 a inner join (select  userid,sum(val) val from dms_' . $this -> name . '_业绩 where pid<>0 and time>=' . ($caltime ) . ' and time<' . ($caltime + 86400) .' and region=' . ($key + 1) . ' group by userid) b
						on a.id=b.userid set a.' . $this -> name . '_' . $Branch . '区结转业绩=a.' . $this -> name . '_' . $Branch . '区结转业绩+ifnull(b.val,0) where ifnull(b.val,0)<>0');
					//更新本期业绩
		            M() -> execute("update dms_会员 a left join (select  userid,sum(val) val from dms_" . $this -> name . "_业绩 where pid<>0 and time>=" . ($caltime + 86400) . " and region=" . ($key + 1) . " group by userid) b
						on a.id=b.userid set a." . $this -> name . '_' . $Branch . "区本期业绩=ifnull(b.val,0) where a." . $this -> name . '_' . $Branch . "区本期业绩>0 and a." . $this -> name . '_' . $Branch . "区本期业绩<>ifnull(b.val,0) ");
	            }
	        }
	    }
	    //跨日结算
		public function event_diffTime($time)
		{
			$this->update($time);
		}
		//获取区域
		public function getBranch(){
			return X("net_place@".$this->netName)->getBranch();
		}
		//更新数据
		public function update($caltime=0){
			if($caltime==0)
				$caltime=strtotime(date("Y-m-d",systemTime()));
			//更新本日业绩
			foreach(X("net_place@".$this->netName)->getBranch() as $rkey=>$region){
				M()->execute("update dms_会员 set ".$this->name."_{$region}区本日业绩=0 where ".$this->name."_{$region}区本日业绩>0");
				M()->execute("update dms_会员 a inner join (select  userid,sum(val) val from dms_".$this->name."_业绩 where region='".($rkey+1)."' and pid<>0 and time>=".($caltime)." and time<".($caltime+86400)." group by userid) b 
				on a.id=b.userid set a.".$this->name."_{$region}区本日业绩=ifnull(b.val,0) where a.".$this->name."_{$region}区本日业绩!=ifnull(b.val,0)");
				//更新本周业绩
				if($this->nowWeek){
					$firstweek=$caltime-3600*24*(date("N",$caltime)-1);
					M()->execute("update dms_会员 set ".$this->name."_{$region}区本周业绩=0 where ".$this->name."_{$region}区本周业绩>0");
					M()->execute("update dms_会员 a inner join (select  userid,sum(val) val from dms_".$this->name."_业绩 where region='".($rkey+1)."' and pid<>0 and time>=".($firstweek)." and time<".($caltime+86400)." group by userid) b 
					on a.id=b.userid set a.".$this->name."_{$region}区本周业绩=ifnull(b.val,0) where a.".$this->name."_{$region}区本周业绩!=ifnull(b.val,0)");
				}
				//更新本月业绩
				if($this->nowMonth){
					$firstmonth=$caltime-3600*24*(date("d",$caltime)-1);
					M()->execute("update dms_会员 set ".$this->name."_{$region}区本月业绩=0 where ".$this->name."_{$region}区本月业绩>0");
					M()->execute("update dms_会员 a inner join (select  userid,sum(val) val from dms_".$this->name."_业绩 where region='".($rkey+1)."' and pid<>0 and time>=".($firstmonth)." and time<".($caltime+86400)." group by userid) b 
					on a.id=b.userid set a.".$this->name."_{$region}区本月业绩=ifnull(b.val,0) where a.".$this->name."_{$region}区本月业绩!=ifnull(b.val,0)");
				}
				//更新本年业绩
				if($this->nowYear){
					$firstyear=strtotime(date("Y",$caltime)."-01-01");
					M()->execute("update dms_会员 set ".$this->name."_{$region}区本年业绩=0 where ".$this->name."_{$region}区本年业绩>0");
					M()->execute("update dms_会员 a inner join (select  userid,sum(val) val from dms_".$this->name."_业绩 where region='".($rkey+1)."' and pid<>0 and time>=".($firstyear)." and time<".($caltime+86400)." group by userid) b 
					on a.id=b.userid set a.".$this->name."_{$region}区本年业绩=ifnull(b.val,0) where a.".$this->name."_{$region}区本年业绩!=ifnull(b.val,0)");
				}
				//更新累计业绩
				M()->execute("update dms_会员 set ".$this->name."_{$region}区累计业绩=0 where ".$this->name."_{$region}区累计业绩>0");
				M()->execute("update dms_会员 a inner join (select  userid,sum(val) val from dms_".$this->name."_业绩 where region='".($rkey+1)."' and pid<>0 and time<".($caltime+86400)." group by userid) b 
				on a.id=b.userid set a.".$this->name."_{$region}区累计业绩=ifnull(b.val,0) where a.".$this->name."_{$region}区累计业绩!=ifnull(b.val,0)");
			}
		}
		//当网络进行过移动时
		public function event_netmove($net,$user)
		{
			$uidsql = $users = M('会员')->where($net->name."_网体数据 like '".($user[$net->name.'_网体数据'].','.$user['id'])."%'")->Field('id')->select(false);
			$ids = M($this->name.'_业绩')->where('pid=0 and userid in '.$uidsql)->Field('id')->getField('id,id id2');
			if($ids)
			{
				M()->execute('delete from dms_'.$this->name.'业绩 where pid in ('.implode(",",$ids).')');
			}
			$adds = M()->table('dms_'.$this->name.'_业绩 a')->join('dms_会员 b on b.id=a.userid')->field('b.'.$net->name.'_网体数据 netdata,b.id uid,a.val,a.id,a.saleid,a.time')->where('pid=0 and `time` >='.$movetime)->select();
			foreach($adds as $add)
			{
				$this->addUpPv($add['id'],$add['val'],$add['netdata'],$add['uid'],$add['saleid'],$add['time']);
			}
		}
	}
?>