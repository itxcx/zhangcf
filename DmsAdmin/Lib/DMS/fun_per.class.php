<?php
	/*个人业绩*/
	class fun_per extends stru{
		//是否统计本年的业绩
		public $year=false;
		//是否统计本月的业绩
		public $month=false;
		//是否统计本周的业绩
		public $week=false;
		public function event_valadd($user,$val,$option)
		{
			$this->add($user,$val,$option);
		}
		public function add($user,$val,$option){
			//获取订单的ID
			$saleid = isset($option['saleid'])?$option['saleid']:0;
			//自己产生的业绩
			$indata = array(
				'userid'=>$user['id'],
				'val'=>$val,
				'saleid'=>$saleid,
				'time'=>systemTime(),
			);
			//插入自己的业绩
			
			M($this->name.'_业绩')->add($indata);
			$data=array();
			$data[$this->name.'本日']=array('exp','`'.$this->name.'本日`+'.$val);
			$this->week  && $data[$this->name.'本周']=array('exp','`'.$this->name.'本周`+'.$val);
			$this->month && $data[$this->name.'本月']=array('exp','`'.$this->name.'本月`+'.$val);
			$this->year  && $data[$this->name.'本年']=array('exp','`'.$this->name.'本年`+'.$val);
			M('会员')->where(array('id'=>$user['id']))->save($data);
		}
		//30  31  1 2
		//计{$this->name}累计算时统计
		public function event_cal($tle,$caltime)
		{
			$this->update($caltime);
		}
		//查询更新会员的业绩
		function update($caltime){
			if($caltime==0)
		    	$caltime=strtotime(date("Y-m-d",systemTime()));
		      //更新本日业绩
		      M()->execute("update dms_会员 a left join (select  userid,sum(val) val from dms_".$this->name."_业绩 where  time>=" . ($caltime) . " and time<".($caltime+86400)."  group by userid) b 
					on a.id=b.userid set a.".$this->name."本日 =ifnull(b.val,0)");
		   //统计本周的
		   if($this->week){
		   	  $firstweek=$caltime-3600*24*(date("N",$caltime)-1);
		      //统计本周的业绩
		      M()->execute("update dms_会员 a left join (select  userid,sum(val) val from dms_".$this->name."_业绩 where  time>=".($firstweek)." and time<".($caltime+86400)."  group by userid) b 
					on a.id=b.userid set a.".$this->name."本周 =ifnull(b.val,0)");
		   }
		   //如果开启了月结的开关
		   if($this->month){
		     M()->execute("update dms_会员 a left join (select  userid,sum(val) val from dms_".$this->name."_业绩 where  FROM_UNIXTIME(time,'%Y%m')='" . date('Ym',$caltime) . "'  group by userid) b 
					on a.id=b.userid set a.".$this->name."本月 =ifnull(b.val,0)");
		   }
		   //更新本年的
		   if($this->year){
		      $firstyear=strtotime(date("Y",$caltime)."-01-01")-3600*24;
		      //统计本周的业绩
		      M()->execute("update dms_会员 a left join (select  userid,sum(val) val from dms_".$this->name."_业绩 where   time>=".($firstyear)." and time<".($caltime+86400)."  group by userid) b 
					on a.id=b.userid set a.".$this->name."本年 =ifnull(b.val,0)");
		   }
		   //如果开启了累计的开关
		    M()->execute("update dms_会员 a left join (select  userid,sum(val) vals from dms_".$this->name."_业绩 group by userid) b 
					on a.id=b.userid set a.".$this->name."累计 =ifnull(b.vals,0)");
		}
		public function event_sysclear()
		{
			M()->execute('truncate table `dms_'.$this->name.'_业绩`');
		}
	}
?>