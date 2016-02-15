<?php
	/*个人业绩*/
	class fun_standard extends stru{
		//会员达标条件
		public $where   ='';
		//是否统计本月的业绩
		public $timeMode='m';
		//提前判定合格
		public $advance = true;
		public function event_cal($tle,$caltime)
		{
			if($this->where=='')
			{
				throw_exception('fun_standard模块必须要设有条件');
			}
			//即便是统计每月业绩，但是有存在提前合格的情况，进行提前升级
			$isrun = false;
			switch($this->timeMode)
			{
				case 'm':
				if(date('j',$caltime)==date('t',$caltime))
				{
					$isrun=true;
					$beforeTime=$caltime-(date('t',$caltime)*86400);
				}
				break;
				default:
				throw_exception('fun_standard模块时间格式设置异常');
			}
			if($isrun || $this->advance)
			{
				$m_user=M('会员');
				$stime=strtotime(date('Y-m-1',$caltime));
				switch($this->timeMode)
				{
					case 'm':
						$where='编号 not in (select 编号 from dms_'.$this->name.' where 日期>=' . $stime . ') and ('.delsign($this->where).')';
						//当月的第一天 减1,即得到了上一个月的最后一秒
						$etime=strtotime(date('Y-m-1',$caltime))-1;
						//得到了上个月的1号0点
						$stime=strtotime(date('Y-m-1',$etime));
						$beforeLogs=M($this->name)->where("日期>=".$stime.' and 日期<='.$etime)->getField('编号,连续');
					break;
				}
				$users=$m_user->where($where)->field('id,编号')->select();
				
				if($users)
				foreach($users as $user)
				{
					$continuous=1;
					if(isset($beforeLogs[$user['编号']]))
					{
						$continuous=$beforeLogs[$user['编号']]+1;
					}
					$data=array(
						'编号'=>$user['编号'],
						'日期'=>$caltime,
						'连续'=>$continuous,
						);
					M($this->name)->badd($data);
				}
				M($this->name)->bupdate();
			}
		}
		public function event_sysclear()
		{
			M()->execute('truncate table `dms_'.$this->name.'`');
		}
	}
?>