<?php

	class prize_pile extends prize
	{
		public $prizeMode=1;
		//来源表条件
		public $where = '';
		//小数精度
		public $section=false;
		//是否要把自己的奖金,扣除下级产生此奖金之和
		public $deductTree='';
		public $decimalLen = 2;
			//判断是否显示奖金构成
		public $isSee = true;
		//会员被删除触发的事件
		public function event_userdelete($user)
		{
			M($this->name)->where(array("编号"=>$user["编号"]))->delete();
		}
		
		//系统数据清空事件
		public function event_sysclear()
		{
			M()->execute("TRUNCATE TABLE dms_".strtolower($this->name));
		}		

		public function event_valadd($user,$val,$option)
		{
			//返还天数
			$day  =empty($option['day']) ? -1 : $option['day'];
			//返还执行条件
			$where=empty($option['where']) ? '' : $option['where'];
			//设定比例
			$ratio=$option['ratio'];
			//增加新记录
			$this->set($user['编号'],$val,$day,$ratio,$where);
		}
		//增加定期返利设定
		function set($username,$val,$day,$ratio,$where='')
		{
			//设置起始时间
			$thistime = strtotime(date("Y-m-d",systemTime()));
			//设置终止日期，如果day为-1则表示无结束日期
			$endday   = ($day>0) ? $thistime+($day*24*3600) : $day;
			//查询到指定会员
			$user = M('会员')->where(array('编号'=>$username))->find();
			//增加累计数量
			$user[$this->name . '数量'] += 1;
			M('会员')->save($user);

			$data=array('编号'=>$username,
				'金额'=>$val,
				'开始时间'=>$thistime,
				'截止时间'=>$endday,
				'比例'=>$ratio,
				'条件'=>$where,
				'序号'=>$user[$this->name . '数量'],				
				);
			if(M($this->name)->add($data) === false)
			{
				throw_exception('prize_pile模块增加返利记录失败');
			}
		}
		//由于此类奖金还有一种this封顶类型。即根据当前点位进行封顶

		public function thisgettop($user,$pile,$prizenum)
		{
			$ret = $prizenum;
			$cons=$this->getcon("top",array('val'=>'','mode'=>'','where'=>'','with'=>''));
			foreach($cons as $con)
			{
				//封顶起征点
				$statrnum = 0;
				if(transform($con['where'],$user))
				{
					$with=$con['with'];
					if($with == '')
					{
						$with=$this->name;
					}
					$withs = explode(',',$with);
					foreach($withs as $with)
					{					
						switch($con['mode'])
						{
							case 'day':
								$statrnum += $user[$with . '本日'];
							break;
							case 'month':
								$statrnum += $user[$with . '本月'];
							break;
							case 'all':
								$statrnum += $user[$with . '累计'];
							break;
							case 'this':
								$statrnum += $pile['累计奖金'];
							break;
						}
					}
					$statrnum += $prizenum;
					if($con['mode'] == 'this')
					{
						$ifval = transform($con['val'],$pile);
					}
					else
					{
						$ifval = transform($con['val'],$user);
					}
					if($statrnum > $ifval )
					{
						$ret -= ($statrnum - $ifval);
					}
				}
			}
			if($ret<0)
			$ret=0;
			
			return $ret;
		}

		//日结驱动
		function cal()
		{
			if(!$this->ifrun()) return;
			$where=array(
			"开始时间"=>array('elt',$this->_caltime),
			"截止时间"=>array('egt',$this->_caltime),
			);
			//查询出可能得到的记录
			$piles=M($this->name)->where("开始时间<=" . $this->_caltime . " and (截止时间>=".$this->_caltime ." or 截止时间=-1)")->select();
			if(count($piles)>0)
			{
			foreach($piles as $pile)
			{
				//where='100<3000*1.5 or 1>1'
				$user=M('会员')->where(array("编号"=>$pile['编号']))->lock(true)->find();
				$where=$pile['条件'];
				$where=str_replace('{allnum}',$user[$this->name.'数量'],$where);
				$where=str_replace('{thisnum}',$pile['序号'],$where);
				if(transform($where,$pile))
				{
					//取得会员
					$users=$this->getuser("编号='".$pile['编号']."'");
					$user=$users[0];
					$ratio=$pile['比例'];
					//对比例的字段进行替换，num替换为当前记录的累计产生次数
					$ratio=str_replace('{num}',$pile['产生次数'],$ratio);
					//对$ratio进行动态计算，得到最终的结果
					$prizenum=transform($ratio,$pile);
					$prizenum=getnum($prizenum,'*1',$this->decimalLen,$user[$this->name.'比例']);
					$prizenum=$this->thisgettop($user,$pile,$prizenum);
					//添加奖金
					$this->addprize($user,$prizenum,$pile['金额'].'*'.$ratio.'%','');
					//对返利记录进行更新
					$pile['累计奖金']+=$prizenum;
					$pile['产生次数']+=1;
				    M($this->name)->save($pile);
			    }
			}
			$this->prizeUpdate();
			}
		}
		public function event_modifyId($oldbh,$newbh)
		{ 
			M()->execute("update dms_{$this->name} set 编号='{$newbh}' where 编号='{$oldbh}'");
		}
	}
?>