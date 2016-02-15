<?php
	class fun_ifnum extends stru
	{
		//使用于多个网体
		public $fromNet=""; 
		//进入排号的条件
		public $where = "1=1";
		//当这个人后期不符合条件，是否要移除网络
		public $noWhereMove = false;
		//是否每个会员排几个
		public $onlyNum = 0;
		//后续进入导致出局的人数 
		public $outNum = 0;
		//最多排号
		public $maxNum = 0;
		public function event_sysclear()
		{
			M()->execute('truncate table `dms_'.$this->name.'`');
		}
		//addval 执行进网的操作
		public function event_valadd($user,$val,$option)
		{
			$user=M("会员")->where(array("编号"=>$user['编号']))->find();
			if(!transform($this->where,$user))
			{
				return;
			}
			//查询网体的条件
			$where=array();
			$where['状态']=0;
			$where['网体']=0;
			//判断进网时是否需跟着某网体上级
			if($this->fromNet!=""){
				//上级会员 及所在的网体
				if(isset($user[$this->fromNet."_上级编号"])){
					$upname=$user[$this->fromNet."_上级编号"];
				}else{
					$upname=M('会员','dms_')->where(array("编号"=>$user['编号']))->getField($this->fromNet."_上级编号");
				}
				if(isset($upname) && $upname!=""){
					$netNum=M($this->name,'dms_')->where(array("编号"=>$upname))->getField("网体");
				}else{
					$netNum=$user['id'];
				}
				$where['网体']=$netNum;
			}
			$this->intoNet($user,$where,systemTime());
		}
		//结算完成执行
		public function event_calover($tle,$caltime,$type){
			//获取的需要进网的会员
			$where=delsign($this->where);
			$where.=" and 审核日期<=".$caltime;
			$users=M("会员")->where($where)->select();
			if(count($users)<=0)
				return ;
			//循环处理进网
			foreach($users as $user){
				//查询网体的条件
				$where=array();
				$where['状态']=0;
				$where['网体']=0;
				//判断进网时是否需跟着某网体上级
				if($this->fromNet!=""){
					//上级会员 及所在的网体
					$upname=$user[$this->fromNet."_上级编号"];
					if(isset($upname) && $upname!=""){
						$netNum=M($this->name,'dms_')->where(array("编号"=>$upname))->getField("网体");
					}else{
						$netNum=$user['id'];
					}
					$where['网体']=$netNum;
				}
				$this->intoNet($user,$where,$caltime);
			}
		}
		//会员删除后的点位空缺
		public function event_userdelete($user){
			$delinfo=M($this->name)->where(array("编号"=>$user['编号'],"排序"=>array("gt",0)))->order("排序 asc")->find();
			$delnum=M($this->name)->where(array("编号"=>$user['编号']))->delete();
			if($delinfo){
				$netusers=M($this->name)->where(array("排序"=>array("gt",$delinfo['排序'])))->order("排序 asc")->getField('id kid,id,网体数据');
				//更新网体的排序
				M()->execute("update `dms_".$this->name ."` inner join (SELECT @rowid:=@rowid+1 as rowid ,id FROM `dms_".$this->name ."`, (SELECT @rowid:=0) as init where dms_".$this->name .".排序>0 ORDER BY dms_".$this->name .".排序 asc) b on `dms_".$this->name ."`.id=b.id set `dms_".$this->name ."`.排序=b.rowid");
				//更新网体数据
				$netstr=$delinfo['网体数据'];
				foreach($netusers as $netuser){
					//网体数据有变化时更新
					if($netuser['网体数据']!=$netstr){
						$netuser['网体数据']=$netstr;
						M($this->name)->bsave($netuser);
					}
					$netstr.=$netstr==""?$netuser['id']:','.$netuser['id'];
				}
				M($this->name)->bUpdate();
			}
		}
		//插入网络
		public function intoNet($user,$where=array(),$intotime){
			//当前会员已进几个点位
			$mewhere=$where;
			$mewhere['编号']=$user['编号'];
			$xunum=M($this->name,'dms_')->where($mewhere)->Max('序号');
			$xunum++;
			if($this->onlyNum>0 && $xunum>$this->onlyNum){
				return ;
			}
			//存在所在的网络
			if(!$where){
				$maxnum=M($this->name,'dms_')->where("1=1")->Max('排序');
			}else{
				$maxnum=M($this->name,'dms_')->where($where)->Max('排序');
			}
			if($maxnum>0){
				$where['排序']=$maxnum;
				$upinfo=M($this->name,'dms_')->where($where)->find();
			}
			$maxnum++;
			if($this->maxNum > 0 && $maxnum > $this->maxNum){
				return ;
			}
			if(isset($upinfo)){
				$netstr=trim($upinfo['网体数据'].",".$upinfo['id'],',');
			}else{
				$netstr="";
			}
			$data=array(
				"编号"=>$user['编号'],
				"userid"=>$user['id'],
				"time"=>$intotime,
				"上级"=>isset($upinfo)?$upinfo['id']:0,
				"网体数据"=>$netstr,
				"网体"=>$where['网体'],
				"排序"=>$maxnum,
				"序号"=>$xunum,
				"状态"=>0
			);
			M($this->name,'dms_')->add($data);
			//判断是否有出局会员
			$this->outnet($maxnum,$where['网体']);
			//处理addval
			$addcons=$this->getcon("addval",array("val"=>'','to'=>""));
			foreach($addcons as $addcon){
				X("@".$addcon['to'])->event_valadd($user,$addcon['val'],$addcon);
			}
		}
		//出局条件判断
		public function outnet($maxnum,$netnum){
			if($this->outNum > 0){
				//可能导致出局的点位 进几出一规则
				$outnum=($maxnum-1)/$this->outNum;
			}else{
				if($this->noWhereMove){
					//判断是否不符合排列会员 正常条件进网 不符合出局规则//更新出局会员状态
					$upnum=M()->execute("update dms_{$this->name} f inner join (select id,编号 from dms_会员 where not (".delsign($this->where).")) u on f.userid=u.id set f.排序=0,f.状态=1,f.出局时间=".systemTime()." where f.排序>=1");
					//修改网体数据
					if($upnum>0)
					{
						//更新网体的排序
						M()->execute("update `dms_".$this->name ."` inner join (SELECT @rowid:=@rowid+1 as rowid ,id FROM `dms_".$this->name ."`, (SELECT @rowid:=0) as init where dms_".$this->name .".排序>0 ORDER BY dms_".$this->name .".排序 asc) b on `dms_".$this->name ."`.id=b.id set `dms_".$this->name ."`.排序=b.rowid");
						//更新网体数据
						$netusers=M($this->name)->where(array("排序"=>array("gt",0),"状态"=>0))->order("排序 asc")->getField('id kid,id,网体数据');
						$netstr="";
						foreach((array)$netusers as $netuser){
							//网体数据有变化时更新
							if($netuser['网体数据']!=$netstr){
								$netuser['网体数据']=$netstr;
								M($this->name)->bsave($netuser);
							}
							$netstr.=$netstr==""?$netuser['id']:','.$netuser['id'];
						}
						M($this->name)->bUpdate();
					}
				}
				return ;
			}
			//获得坐前面的未出局的人
			$outinfo=M($this->name)->where(array("网体"=>$netnum,"状态"=>0,"排序"=>array("elt",$outnum)))->order("排序 asc")->select();
			if(!isset($outinfo)){
				return;
			}
			//会员出局
			$outdata['状态']=1;
			$outdata['出局时间']=systemTime();
			M($this->name)->where(array("网体"=>$netnum,"状态"=>0,"排序"=>array("elt",$outnum)))->save($outdata);
		}
	}
?>