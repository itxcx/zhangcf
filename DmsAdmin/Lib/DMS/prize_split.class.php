<?php
	class prize_split extends prize
	{
		//SELECT 创业组_盘号,创业组_序号,创业组_直推,创业组_间推,创业组_总推 FROM `dms_会员`;
		//产生奖金模式
		public $prizeMode = -1;
		//来源表条件
		public $where = '';
		public $modAdd = true;
		//排序推荐权最大数量
		public $recMax = 4;
		//排序规则
		public $order  ='';
		//分盘数
		public $splitNum = 15;
		public $rowmode = 1;
		//关联网络体系
		public $netName='';
		//通过订单进入的
		public $next='';
		//盘主资格
		public $outWhere='';
		//判断是否显示奖金构成
		public $isSee = false;
		//盘面历史记录ID，为0表示未生成
		public $logid=0;
		function scal($sale)
		{
			//退出
			if($this->rowmode!=1)return;
			$m_user=M('会员');
			$user = $m_user->where(array('编号'=>$sale['编号']))->lock(true)->find();
			$net = X('*@'.$this->netName);
			if($net === NULL)
			{
				throw_exception($this->name."计算时网络体系获取失败,请检查其netName设置是否正确");
			}
			//查询在当前盘或者已经出当前盘的人
			$upusers = $net->getups($user,0,0,$this->name . '_盘号>0 or '.$this->name.'_出盘>0');
			//如果不存在这类人，则肯定需要自己建盘
			if(count($upusers)==0)
			{
				//创建新盘
				$newid=$this->make();
				//进入新盘
				$this->in($user,$newid);
			}
			else
			{
				$upuser = array_shift($upusers);
				//当找到的人不在当前盘面上的时候
				if($this->rowmode == 1)
				{
					//找到推荐下级在此盘面的人
					if($upuser[$this->name.'_盘号'] == 0)
					{
						//当自己的推荐上级已经出局不在当前盘面，需要根据推荐人的下级寻找在盘点位，需要确定排序规则
						$downusers=$net->getdown($user,0,0,$this->name . '_盘号>0');
						if(count($downusers)=='0')
						{
							throw_exception($this->name."计算时发现一个出局的人居然没有在出局之前的下级。似乎不太可能");
						}
						$upuser = array_shift($downusers);
					}					
					//推荐权下移操作
					$this->givedownrec($user,$upuser,$net);
				}
				//开始进行进盘操作
				$panid = $upuser[$this->name . '_盘号'];
				$this->in($user,$panid);
			}
		}
		//推荐权下移
		function givedownrec($user,$upuser,$net)
		{
			$m_user = M('会员');
			//是否已经给予了推荐权
			$reggive=false;
			//进行推荐权下移操作
			$reccons=$this->getcon('reccon',array('where'=>'','val'=>1,'isme'=>true));
			//循环推荐权配置
			foreach($reccons as $reccon)
			{
				//判定是否为自己推荐
				$isme = ($user[$this->netName.'_上级编号'] == $upuser['编号']);
				//当没有给予推荐权，并且上级推荐权没达到封顶的时候，可以给予其推荐权
				if($reggive === false && $reccon['isme'] == $isme && $upuser[$this->name.'_总推'] < $this->recMax)
				{
					//增加推荐权
					$this->giverec($upuser,$user,$reccon['val']);
					$reggive=true;
				}
			}
			//给予下级推荐权
			if(!$reggive)
			{
				$gwu = array_shift($net->getdown($upuser,0,0,"{$this->name}_盘号>0 and {$this->name}_总推<{$this->recMax}"));
				foreach($reccons as $reccon)
				{
					//当没有给予推荐权，并且上级推荐权没达到封顶的时候，可以给予其推荐权
					if($reccon['isme'] == false)
					{
						//增加推荐权
						$this->giverec($gwu,$user,$reccon['val']);
					}
				}
			}
		}		
		//通过其他盘面进盘
		function prizein($user)
		{
			$m_user=M('会员');
			$net = X('*@'.$this->netName);
			if($net === NULL)
			{
				throw_exception($this->name."计算时网络体系获取失败,请检查其netName设置是否正确");
			}
			$upusers = $net->getups($user,0,0,$this->name . '_盘号>0');
			//进行推荐权上移操作
			if(count($upusers)==0)
			{
				//创建新盘
				$newid=$this->make();
				//进入新盘
				$this->in($user,$newid);
			}
			else
			{
				$reggive=false;
				$upuser = array_shift($upusers);
				//判断是否给推荐权
				$reccons=$this->getcon('reccon',array('where'=>'','val'=>1,'isme'=>true,'minlayer'=>0,'maxlayer'=>0));
				foreach($reccons as $reccon)
				{
					//判定是否为自己推荐
					$isme = ($user[$this->netName.'_上级编号'] == $upuser['编号']);
					//当没有给予推荐权，并且上级推荐权没达到封顶的时候，可以给予其推荐权
					if($reggive === false && $reccon['isme'] == $isme && $upuser[$this->name.'_总推']<$this->recMax)
					{
						$this->giverec($upuser,$user,$reccon['val']);
						$reggive=true;
					}
				}
				//给予上级推荐权
				if(!$reggive)
				{
					//寻找上级可以拿到推荐权的人
					$gwus = $net->getups($upuser,0,0,"{$this->name}_盘号>0 and {$this->name}_总推<{$this->recMax}");
					//如果存在人
					if(count($gwus) > 0)
					{
						//得到第一个点
						$gwu=array_shift($gwus);
						//循环配置
						foreach($reccons as $reccon)
						{
							//得到当前层数
							$thislayer = $user[$net->name.'_层数'] - $gwu[$net->name.'_层数'];
							//当没有给予推荐权，并且上级推荐权没达到封顶的时候，可以给予其推荐权
							if($reccon['isme'] == false && $reccon['minlayer'] <= $thislayer && $reccon['maxlayer'] >= $thislayer)
							{
								//可以增加的推荐权数
								$val = $reccon['val'];
								//可以增加的推荐权数
								if($gwu[$this->name.'_总推'] + $val >$this->recMax)
								{
									$val -= ($gwu[$this->name.'_总推'] - $this->recMax);
								}
								//可以增加的推荐权数
								$this->giverec($gwu,$user,$val);
							}
						}
					}
				}
				//处理完推荐权上级编号
				$panid = $upuser[$this->name . '_盘号'];
				$this->in($user,$panid);
			}
		}

		//得到一个没有占用的新盘号
		function make()
		{
			$panid = M('会员')->where($this->name . '_盘号>0')->Max($this->name . '_盘号');
			if($panid == null)
				$panid=0;
			return $panid +1;
		}
		//进盘操作
		function in($user,$panid)
		{
			//清空历史盘面记录ID
			$this->logid=0;
			//打开会员表
			$m_user = M('会员');
			//得到当前盘的人数，从后向前排列
			$panuser = $m_user->where(array($this->name.'_盘号'=>$panid))->order($this->name.'_序号 desc')->select();
			//得到新进入点位的序号
			if(count($panuser)==0)
				$newxu = 1;
			else
				$newxu = $panuser[0][$this->name.'_序号'] + 1;
			//更新数据
			$user[$this->name.'_盘号']=$panid;
			$user[$this->name.'_序号']=$newxu;
			$user[$this->name.'_进盘时间']=time();
			$user[$this->name.'_循环']=$user[$this->name.'_循环']+1;
			//进行更新
			
			$m_user->where(array('id'=>$user['id']))->save(array($this->name.'_盘号'=>$panid,$this->name.'_序号'=>$newxu,
			$this->name.'_循环'=>array('exp',$this->name.'_循环+1'))
			);
			//从新读取当前盘所有人
			$panusers = $m_user->where(array($this->name.'_盘号'=>$panid))->order($this->name.'_序号 asc')->select();
			//循环所有人进行比对
			$cons = $this->getcon('con',array('name'=>'','type'=>'','val'=>0,'where'=>''));
			foreach($panusers as $panuser)
			{
				foreach($cons as $con)
				{
					//判定是否应该产生奖金
					if(transform($con['where'],array(),array('M'=>$panuser,'U'=>$user)) && $con['type']=='in')
					{
						$prizedata=array(
							$con['name']=>array('exp',$con['name']+$con['val']),
							$con['name'].'本月'=>array('exp',$con['name'].'本月'+$con['val']),
							$con['name'].'累计'=>array('exp',$con['name'].'累计'+$con['val']),
						);
						//$m_user->where(array('id'=>$panuser['id']))->save($prizedata);
						$this->makecom($con['name'],$panuser,$user,$con['val'],$panusers);
					}
				}
			}
			//盘也进了，奖金也发了，，接下来么。。。分盘吧
			if(count($panusers) == $this->splitNum)
			{
				$this->logid=0;
				//设置盘主资格
				$m_user->where($this->name.'_盘号='.$panid)->save(array($this->name.'_盘主资格'=>0));
				$m_user->where($this->name.'_盘号='.$panid.' and ('.delsign($this->outWhere).')')->save(array($this->name.'_盘主资格'=>1));
				//读取默认排序规则
				$order=$this->order;
				//设置默认排序规则
				if($order == '')
					$order = "{$this->name}_盘主资格 desc,{$this->name}_总推 desc,{$this->name}_入盘时间 asc,id asc";
				$panusers = $m_user->where(array($this->name.'_盘号'=>$panid))->order($order)->select();
				//开始分盘,得到AB盘ID
				$apanid = $this->make();
				//开始分盘,得到AB盘ID
				$bpanid = $apanid+1;
				foreach($panusers as $k=>$panuser)
				{
					//处理原始点出局奖金
					if($k==0)
					{
						foreach($cons as $con)
						{
							if($con['type'] == 'out')
							{
								$prizedata=array(
									$con['name']=>array('exp',$con['name']+$con['val']),
									$con['name'].'本月'=>array('exp',$con['name'].'本月'+$con['val']),
									$con['name'].'累计'=>array('exp',$con['name'].'累计'+$con['val']),
								);
								
								$m_user->where(array('id'=>$panuser['id']))->save($prizedata);
								$this->makecom($con['name'],$panuser,$user,$con['val'],$panusers);
							}
						}
					}
					$panuser[$this->name.'_盘号']=0;
					$panuser[$this->name.'_序号']=0;
					//$panuser[$this->name.'_直推']=0;
					//$panuser[$this->name.'_间推']=0;
					//$panuser[$this->name.'_总推']=0;
					//删除推荐权表
					//M(X('user')->name.'_'.$this->name.'rec','dms_')->where(array('编号'=>$panuser['编号']))->delete();
					//数据更新
					$updata = array($this->name.'_盘号'=>0,$this->name.'_序号'=>0,$this->name.'_直推'=>0,$this->name.'_间推'=>0,$this->name.'_总推'=>0);
					$m_user->where(array('id'=>$panuser['id']))->save($updata);
					//进盘操作,排除盘主
					if($k>0)
						$this->in($panuser,$k % 2 ? $apanid:$bpanid);
				}
				//对盘主进行处理
				if($this->next != '')
				{
					$n_prize = X('prize_split@'.$this->next);
					$n_prize->prizein($panusers[0]);
				}
			}
		}
		//给予会员推荐权，并进行记录
		public function giverec($user,$fromuser,$val)
		{
			//增加推荐资格
			$uparr=array($this->name.'_总推'=>array('exp',$this->name.'_总推+'.$val));
			if($user['编号'] == $fromuser[$this->netName.'_上级编号'])
			{
				$uparr[$this->name.'_直推']=array('exp',$this->name.'_直推+'.$val);
				$thisrec=1;
			}
			else
			{
				$uparr[$this->name.'_间推']=array('exp',$this->name.'_间推+'.$val);
				$thisrec=0;
			}
			M('会员','dms_')->where(array('id'=>$user['id']))->save($uparr);
			

		    $adddata=array('编号' => $user['编号'],
	                       '来源' => $fromuser['编号'],
	                       '数量' => $val,
	                       '直推' => $thisrec,
	                       '代数' => $fromuser[$this->netName.'_层数']-$user[$this->netName.'_层数']
							);
			
			M($this->name.'rec','dms_')->add($adddata);
		}
		//生成构成信息
		public function makecom($name,$user,$formuser,$val,$alluser)
		{
			$prize = X('*@'.$name);
			if($this->logid==0)
			{
				$adata=array();
				$k=0;
				foreach($alluser as $auser)
				{
					$k+=1;
					$adata[(int)$k] = array(
						'id'=>$auser['编号'],
						'tj' =>$auser[$this->name.'_直推'],
						'tj2'=>$auser[$this->name.'_间推']
						);
				}
				$recdata = M($this->name.'rec')->where("编号 in (select 编号 from dms_会员 where ".$this->name."_盘号=".$user[$this->name.'_盘号'].")")->select();
				$logdata=array('盘号'=>$user[$this->name.'_盘号'],
							   '时间'=>systemTime(),
							   '进盘编号'=>$user['编号'],
							   '数据'=>serialize($adata),
						       '推荐数据'=>serialize($recdata),
								);
				$this->logid = M($this->name.'记录')->add($logdata);
			}
			$prize->addprize($user,$val,$formuser,'url:D_PrizeSplit/index:'.$this->objPath().'/id/'.$this->logid);
		}
        public function event_sysclear()
		{
			M()->execute('truncate table `dms_'.$this->name.'rec`');
			M()->execute('truncate table `dms_'.$this->name.'记录`');
		}
		public function event_modifyId($oldbh,$newbh)
		{ 
			M()->execute("update dms_{$this->name}记录 set 进盘编号='{$newbh}' where 进盘编号='{$oldbh}'");
			M()->execute("update dms_{$this->name}rec set 编号='{$newbh}' where 编号='{$oldbh}'");
			M()->execute("update dms_{$this->name}rec set 来源='{$newbh}' where 来源='{$oldbh}'");
		}
	}
?>