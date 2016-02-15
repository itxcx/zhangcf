<?php
	class fun_treenum extends stru
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
		public $Cache    =array();
		//字段类型
		public $mode = 'numeric(13,2)';

		/*系统清空事件*/
		public function event_sysclear()
		{
			M()->execute("TRUNCATE TABLE " . 'dms_'.$this->name.'_业绩;');
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
			$this->addUpPv($pid,$val,$user[$this->netName.'_网体数据'],$user['id'],$saleid,systemTime());
		}
		//根据原始记录ID,额度,以及网体数据.更新上级业绩
		private function addUpPv($pid,$val,$netdata,$fromid,$saleid,$time){
			//增加数据
			$weeks ='';
		    $mouths='';
		    $years ='';
		    if($this->nowWeek)
		    {
		    	$weeks = $this->name."本周=".$this->name."本周+".$val.",";
		    }
		    if($this->nowMonth)
		    {
		    	$mouths = $this->name."本月=".$this->name."本月+".$val.",";
		    }
		    if($this->nowYear)
		    {
		    	$years = $this->name."本年=".$this->name."本年+".$val.",";
		    }
		    
			//如果对自身产生业绩
			if($this->haveMe)
			{
				M()->execute("update dms_会员 set ".$this->name."本日=".$this->name."本日+".$val.",".$weeks.$mouths.$years.$this->name."累计=".$this->name."累计+".$val." where id=".$fromid);
			}
			//如果没有网体数据.就表示不需要做任何处理.直接返回
			if(!$netdata) return;
			//网体数据转换
			$t_arrs=array_reverse(explode(',',$netdata));
			$sql=array();
			$net = X('*@'.$this->netName);
			
			foreach($t_arrs as $key=>$t_arr)
			{
				//对业绩层数增的判定
				if(($this->MinLayer == -1 || $key+1 >= $this->MinLayer) && ($this->MaxLayer == -1 || $key+1 <= $this->MaxLayer)) {
					if(isset($t_arrs[$key-1])){
						$lineid=$t_arrs[$key-1];
					}else{
						$lineid=$fromid;
					}
				    $sql[]  = "($time,$t_arr,$fromid,$val,$saleid,$pid,$lineid)";
				    //对会员的本日本月累计进行增加
				    M()->execute("update dms_会员 set ".$this->name."本日=".$this->name."本日+".$val.",".$weeks.$mouths.$years.$this->name."累计=".$this->name."累计+".$val." where id=".$t_arr);
				}
			}
			//如果SQL数组为空.就退出
			if(!$sql) return;
			$sqlstr = implode($sql,',');
			//$idusers = implode($userids,',');
			$sqlstr = 'INSERT INTO dms_'.$this->name.'_业绩 (`time`,`userid`,`fromid`,`val`,`saleid`,`pid`,`lineid`) VALUES '.$sqlstr;
			M()->execute($sqlstr);
		}
		public function event_diffTime($time)
		{
			$this->update($time);
		}
		//计算时统计
		public function event_cal($tle,$caltime)
		{
			//统计计算奖金所需的业绩
			//$this->update($caltime);
		}
		//统计当前
		public function event_calover($tle,$caltime,$type){
			//$this->update();
		}
		public function update($caltime=0){
			if($caltime==0)
				$caltime=strtotime(date("Y-m-d",systemTime()));
			$haveme="";
			if(!$this->haveMe){
				$haveme="pid<>0 and ";
			}
			//更新本日业绩
			M()->execute("update dms_会员 set ".$this->name."本日=0 where ".$this->name."本日>0");
			M()->execute("update dms_会员 a inner join (select  userid,sum(val) val from dms_".$this->name."_业绩 where ".$haveme." time>=".($caltime)." and time<".($caltime+86400)." group by userid) b 
			on a.id=b.userid set a.".$this->name."本日=ifnull(b.val,0) where a.".$this->name."本日!=ifnull(b.val,0)");
			//更新本周业绩
			if($this->nowWeek){
				$firstweek=$caltime-3600*24*(date("N",$caltime)-1);
				M()->execute("update dms_会员 set ".$this->name."本周=0 where ".$this->name."本周>0");
				M()->execute("update dms_会员 a inner join (select  userid,sum(val) val from dms_".$this->name."_业绩 where ".$haveme." time>=".($firstweek)." and time<".($caltime+86400)." group by userid) b 
				on a.id=b.userid set a.".$this->name."本周=ifnull(b.val,0) where a.".$this->name."本周!=ifnull(b.val,0)");
			}
			if($this->nowMonth){
				$firstmonth=$caltime-3600*24*(date("d",$caltime)-1);
				M()->execute("update dms_会员 set ".$this->name."本月=0 where ".$this->name."本月>0");
				M()->execute("update dms_会员 a inner join (select  userid,sum(val) val from dms_".$this->name."_业绩 where ".$haveme." time>=".($firstmonth)." and time<".($caltime+86400)." group by userid) b 
				on a.id=b.userid set a.".$this->name."本月=ifnull(b.val,0) where a.".$this->name."本月!=ifnull(b.val,0)");
			}
			if($this->nowYear){
				$firstyear=strtotime(date("Y",$caltime)."-01-01");
				M()->execute("update dms_会员 set ".$this->name."本年=0 where ".$this->name."本年>0");
				M()->execute("update dms_会员 a inner join (select  userid,sum(val) val from dms_".$this->name."_业绩 where ".$haveme." time>=".($firstyear)." and time<".($caltime+86400)." group by userid) b 
				on a.id=b.userid set a.".$this->name."本年=ifnull(b.val,0) where a.".$this->name."本年!=ifnull(b.val,0)");
			}
			//更新累计
			M()->execute("update dms_会员 set ".$this->name."累计=0 where ".$this->name."累计>0");
			M()->execute("update dms_会员 a inner join (select  userid,sum(val) val from dms_".$this->name."_业绩 where ".$haveme." time<".($caltime+86400)." group by userid) b 
			on a.id=b.userid set a.".$this->name."累计=ifnull(b.val,0) where a.".$this->name."累计!=ifnull(b.val,0)");
		}
		//当网络进行过移动时
		public function event_netmove($net,$user)
		{
			$uidsql = $users = M('会员')->where($net->name."_网体数据 like '".($user[$net->name.'_网体数据'].','.$user['id']).",%' or ".$net->name."_上级编号='".$user['编号']."'")->Field('id')->select(false);
			$ids = M($this->name.'_业绩')->where('pid=0 and (userid='.$user['id'].' or userid in '.$uidsql.')')->Field('id')->getField('id,id id2');
			if($ids)
			{
				M()->execute('delete from dms_'.$this->name.'_业绩 where pid in ('.implode(",",$ids).')');
				$adds = M()->table('dms_'.$this->name.'_业绩 a')->join('dms_会员 b on b.id=a.userid')->field('b.'.$net->name.'_网体数据 netdata,b.id uid,a.val,a.id,a.saleid,a.time')->where('a.id in ('.implode(",",$ids).')')->select();
				foreach($adds as $add)
				{
					$this->addUpPv($add['id'],$add['val'],$add['netdata'],$add['uid'],$add['saleid'],$add['time']);
				}
				$this->update();
			}
		}
	}
?>