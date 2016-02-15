<?php
	/*
	  剩余时间模块.用于计算客户的到期时间
	  <_con day='10' where="[会员级别]=1"/>
	  如以上设置则表示会员在审核后10天内计数
	  如以上设置表示会员累计收入大于1000时在原基础上在增加15天
	  如果使用sale的addval的话则有如下效果
	  <_addval from='12' type='month' now='1'/>
	  表示设置12个月,并且从当前时间计算
	*/
	class fun_outday extends stru
	{
		//注册时是否显示
		public $dispWhere ='';
		public function event_valadd($user,$val,$option)
		{
			if(! isset($option['type']))
				$option['type']='day';
			if($option['type'] != 'day' && $option['type'] != 'month')
			{
				//throw_exception($this->name."计算时网络体系获取失败,请检查其netName设置是否正确");
			}
			if($user[$this->name]<systemTime())
			{
				$user[$this->name] = systemTime();
			}
			$user[$this->name]+=$val * 86400;
			$syday = round(($user[$this->name]-systemTime())/86400);
			if($syday<0)
				$syday = 0;
			M('会员')->where(array('id'=>$user['id']))->save(array($this->name=>$user[$this->name],$this->name.'剩余'=>$syday));
		}
		function event_cal($tle,$caltime)
		{
			/*
			$cons = $this->getcon("con",array("day"=>10,"where"=>"","add"=>false));
			foreach($cons as $con)
			{
				if($con['day'] != -1)
				{
					$where='1=1';
					if($con['where']!='')
						$where=$con['where'];
					if(!$con['add'])
					{
						if(M($this->parent()->name,'dms_')->where($where)->save(array($this->name=>$con["day"])) === false)
						{
							throw_exception('fun_outday模块设置'.$this->parent()->name.$this->name.'失败,条件信息('.htmlentities($where,ENT_COMPAT ,'UTF-8')."),日期量(".$con["day"].")");
						};
					}
					else
					{
						if(M($this->parent()->name,'dms_')->where($where)->setInc($this->name,$con['day']) === false)
						{
							throw_exception('fun_outday模块设置'.$this->parent()->name.$this->name.'失败,条件信息('.htmlentities($where,ENT_COMPAT ,'UTF-8')."),日期量(".$con["day"].")");
						};
					}
				}
			}
			*/
			M()->execute("update dms_会员 set ".$this->name."剩余 = DATEDIFF(from_unixtime($this->name),from_unixtime(".$caltime.")) where 状态='有效'");
			//对剩余天数小于0的归零
			M()->execute("update dms_会员 set ".$this->name."剩余 = 0 where ".$this->name."剩余 < 0");
			/*
			//如果剩余天数为-1则表示不限,所以需要在清零以后在进行不限的判定
			foreach($cons as $con)
			{
				if($con['day'] == -1)
				{
					$where='1=1';
					if($con['where']!='')
						$where=$con['where'];
						if(M($this->parent()->name)->where($where)->setInc($this->name.'剩余',$con['day']) === false)
						{
							throw_exception('fun_outday模块设置'.$this->parent()->name.$this->name.'失败,条件信息('.htmlentities($where,ENT_COMPAT ,'UTF-8')."),日期量(".$con["day"].")");
						};
				}
			}
			*/
			M()->execute("update dms_会员 set ".$this->name."剩余 = 0,".$this->name." = 0 where 状态<>'有效'");
		}
	}
?>