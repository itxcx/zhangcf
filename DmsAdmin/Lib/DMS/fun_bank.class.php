<?php
	/*电子货币模块*/
	class fun_bank extends stru
	{
		//是否显示明细列表链接
		public $userListDisp=true;
		public $userTransferDisp=true;
		public $userdisp=true;//会员首页是否显示
		public $use		=true;
		//显示条件
		public $dispWhere='';
		//是否允许提现
		public $getMoney   =true;
		//提现是否允许撤销
		public $allowBack   =true;
		//增加是否允许撤销的申请
		public $allowBack_apply   =false;
		public $getMoneyWhere = "";
		public $getMoneyMsg   = "";
		//提现手续费点位
		public $getMoneyTax="0";
		//提现手续费下限
		public $getMoneyTaxMin=0;
		//提现手续费上限
		public $getMoneyTaxMax=0;
		//提现受限费来源 1是提现额  0 是额外扣除
		public $getTaxFrom=1;
		//最小提款额
		public $getMoneyMin=0;
		//最大提款额
		public $getMoneyMax=0;
		//提现是否单独输入银行账号信息
		public $getMoneyBank=false;
		//提现确认以后是否清除银行信息
		public $getMoneyBankClear=false;
		//整数支持，为0则不支持，大于零，则必须是其整数倍才可以
		public $getMoneyInt=0;
		//在未审核状态下不允许提现
		public $getOnly=false;
		//货币换算比率
		public $getMoneyRatio=1.0;
		public $isShowRadio=false;
		//周几不能提现
		public $getMoneyWeek=array();
		//每月几号不能提现
		public $getMoneyMday='';
		//是否支持自动提现
		//public getmoneyauto
		//public getmoneyautonewup'是否支持自动提现
		//提现是否需要二级密码
		public $getMoneyPass2=false;
		//提现是否需要三级密码
		public $getMoneyPass3=false;
		//提现是否需要短信验证码
		public $getMoneySmsSwitch=false;
		//提现短信验证码内容
		public $getMoneySmsContent='尊敬的会员[编号],您的提现操作短信验证码是[验证码]';
		//需要密保问题
		public $getSecretSafe=false;
		//转账是否需要短信验证码
		public $giveMoneySmsSwitch=false;
		//转账短信验证码内容
		public $giveMoneySmsContent='尊敬的会员[编号],您的转账操作短信验证码是[验证码]';
		//public dispgetsum
		//public disp_banknum
		//public disp_index
		//是否可以转账
		public $giveMoney=false;
		public $giveMoneyWhere = "";
		public $giveMoneyMsg   = "";		
		//每次可以转账最小额度
		public $giveMoneyMin=0;
		//转账手续费
		public $giveMoneyTax=0;
		//转账手续费下线
		public $giveMoneyTaxMax=0;
		//转账设置信息
		public $giveCon=array();
		//转账是否需要输入二级密码
		public $giveMoneyPass2=false;
		//转账是否需要输入三级密码
		public $giveMoneyPass3=false;
		//货币addval时的备注提示模板
		public $inMoneyStr="";
		//是否开启汇款登记
		public $sysBankIn=true;
		//是否开启汇款登记到账比例(如人民币汇款进美元)
		public $sysBankInx=0;
		//是否开启汇款登记最少填写金额数
		public $sysBankInMin=0;
		//是否开启在线支付
		public $bankIn=false;
		//是否在线支付配置
		public $bankInConStr="";
		//提现过程回调
		//public resetgetmoneyfun
		//转账过程回调
		//public resetgivemoneyfun
		//每日提现次数
		public $getMoneyInDayNum=0;
		//冻结模式.不打算启用和开发此功能
		public $freezeMode;
		//货币单位多语言标签
		public $money_l_name="";
		//冻结功能
		public $frozen=false;
		//支付转换比例
		public $issave=0;
		//所有会员原始缓存
		public $userCache=array();
		//余额更新缓存
		public $updateCache=array();
		//记录更新缓存
		public $logCache=array();
		public $isLock=false;
		//在线支付转换比率
		public $bank_scale=1;
		public function event_valadd($user,$val,$option)
		{
			//如果货币处于关闭状态,则不会增加
			if($this->use == false)
			return ;
			isset($option["bankmode"])  || $option["bankmode"]='';
			isset($option["bankmemo"])  || $option["bankmemo"]='';
			isset($option["tlename"])   || $option["tlename"]='';
			isset($option["prizename"]) || $option["prizename"]='';
			isset($option["dataid"])    || $option["dataid"]=0;
			$option["bankmemo"]  = str_replace('$val',$val,$option["bankmemo"]);
			$this->set($user['编号'],$user['编号'],$val,$option["bankmode"],$option["bankmemo"],$option["tlename"],$option["prizename"],$option["dataid"]);
		}
		public function lock()
		{
			$this->isLock = true;
			$this->userCache = M('货币','dms_')->lock(true)->getField('编号,'.$this->name);//货币分离
		}
		public function update()
		{
			//更新金额
			$update=$this->updateCache;
			asort($update);
			$data1=array();
			$i=1;
			foreach($update as $key=>$val)
			{
				if($i>1 && $val==$data1[$i-1]['val']){
			    	$data1[$i-1]['id'][]=$key;
				}else{
				    $data1[$i]['val']=$val;
				    $data1[$i]['id'][]=$key;
					$i++;
				}
			}
			foreach($data1 as $data)
			{
				//M('会员')->where(array('编号'=>array('in',$data['id'])))->setInc($this->name,$data['val']);
				M('货币')->where(array('编号'=>array('in',$data['id'])))->setInc($this->name,$data['val']);//货币分离
			}
			//增加记录
			$dsql='';
			$ii=0;
			foreach($this->logCache as $key=>$logs)
			{
				foreach($logs as $log)
				{
					if($dsql!='') $dsql.=',';
					$dsql.="('{$log['编号']}','{$log['来源']}','{$log['类型']}','{$log['备注']}',{$log['金额']},{$log['余额']},{$log['时间']},'{$log['tlename']}','{$log['prizename']}',{$log['dataid']})";
					$ii++;
					if($ii>=5000) {
						$dsql="INSERT INTO dms_".$this->name."明细 (编号,来源,类型,备注,金额,余额,时间,tlename,prizename,dataid) values ".$dsql;
						M()->execute($dsql);
						$ii=0;
						$dsql='';
					}
				}
			}
			if($dsql!='')
			{
				$dsql="INSERT INTO dms_".$this->name."明细 (编号,来源,类型,备注,金额,余额,时间,tlename,prizename,dataid) values ".$dsql;
				M()->execute($dsql);
			}
		}
		//添加会员编号,来源编号,值,类型,备注
		public function set($username,$fromname,$val,$mode,$memo,$tlename='',$prizename='',$dataid=-1)
		{
			if(is_null($tlename))  $tlename  ='';
			if(is_null($prizename))$prizename='';
			if(is_null($dataid)) $dataid =-1;
			//$m_user=M('会员','dms_');
			$m_user=M('货币','dms_');//货币分离
			$data=array(
				"编号"=>$username,
				"来源"=>$fromname,
				"类型"=>$mode,
				"备注"=>$memo,
				"金额"=>$val,
				"时间"=>systemTime(),
				"tlename"  =>$tlename,
				"prizename"=>$prizename,
				"dataid" =>$dataid
			);
			$userupdata=array();
			$m_bank=M($this->name."明细",'dms_');
			//$m_bank->lock(true)->where(array('编号'=>$username))->select();
			if($tlename != '' )
			{
				$finddata=$m_bank->where(array("编号"=>$username,"tlename"=>$tlename,"prizename"=>$prizename,"dataid" =>$dataid))->find();
				if($finddata!=NULL)
				{
					//得到差额
					$diffval = $val-$finddata["金额"];
					if($this->isLock  && false)
					{
						//对总缓存更新
						$userCache[$username]+=$val;
						//判断更新缓存是否存在
						if(!isset($this->updateCache[$username]))
						{
							$this->updateCache[$username]=0;
						}
						//更新更新缓存
						$this->updateCache[$username]+=$val;
						if(isset($this->logCache[$username]))
						{
							foreach($this->logCache[$username] as &$logCache)
							{
								$logCache['余额']+=$diffval;
							}
						}
					}
					else
					{
						$user=$m_user->lock(true)->where("编号='{$username}'")->find();
						//更新会员余额
						$user[$this->name]+=$diffval;
						$userupdata[$this->name]=$user[$this->name];
						//更新会员信息
						$m_user->where("编号='{$username}'")->save($userupdata);
						//更新记录中的ID
						$data["id"]=$finddata["id"];
						unset($data["时间"]);
						$m_bank->save($data);
					}
					$m_bank->where("id>=".$data["id"]." and 编号='".$username."'")->setInc('余额',$diffval);
					return $data["id"];
				}
			}
			if($val==0)
			{
				return;
			}
			if($this->isLock)
			{
				//如果为缓存更新模式
				$userCache[$username]+=$val;
				$data['余额'] = $userCache[$username];
				if(!isset($this->logCache[$username]))
				{
					$this->logCache[$username]=array();
				}
				$this->logCache[$username][]=$data;
				if(!isset($this->updateCache[$username]))
				{
					$this->updateCache[$username]=0;
				}
				$this->updateCache[$username]+=$val;
			}
			else
			{
				$user=$m_user->lock(true)->where("编号='{$username}'")->find();
				$user[$this->name]+=$val;
				$data["余额"]=$user[$this->name];
				$userupdata[$this->name]=$user[$this->name];
				$userup=$m_user->where("编号='".$username."'")->save($userupdata);
				return $m_bank->add($data);
			}
		}
		
		/*系统清空事件*/
		public function event_sysclear()
		{
			M()->execute("TRUNCATE TABLE " . 'dms_'.$this->name . "明细;");
			M()->execute("TRUNCATE TABLE " . 'dms_提现;');
		}
		
		public function event_userdelete($user)
		{
			M($this->name."明细",'dms_')->where(array("编号"=>$user["编号"]))->delete();
		}
		//删除特定的货币记录。并更新记录所属的上级余额以及实际余额,参数为货币记录的条件
		public function delete($where='')
		{
			//$m_user = M('会员','dms_');
			$m_user = M('货币','dms_');//货币分离
			$m_bank = M($this->name.'明细','dms_');
			foreach($m_bank->where($where)->select() as $bank)
			{
				$m_bank->where(array('编号'=>$bank['编号'],'id'=>array('gt',$bank['id'])))->save(array('余额'=>array('exp','余额-('.$bank['金额'].')')));
				$m_user->where(array('编号'=>$bank['编号']))->save(array($this->name >= array('exp',$this->name.'-('.$bank['金额'].')')));
				$m_bank->where(array('id'=>$bank['id']))->delete();
			}
		}
		//根据记录进行货币余额的修正性更新
		public function revise($userid='')
		{
			M()->execute("set @sumnum=0"); 
			M()->execute("set @pdept=''");
			M()->execute("update `dms_".$this->name."明细` a right join 
			(select if(@pdept=编号,@sumnum:=@sumnum+金额,@sumnum:=金额) sumnum,@pdept:=编号,id from (SELECT @sumnum:=0,id,金额,编号 FROM `dms_".$this->name."明细` order by 编号 desc,时间 asc,id asc)b)c
			 on a.id=c.id set a.余额=c.sumnum");
			//M()->execute("update dms_会员 a left join (select 编号,sum(金额) as sumnum from  dms_".$this->name."明细  group by 编号)b on a.编号=b.编号 set a.".$this->name."=IFNULL(b.sumnum,0)");
			M()->execute("update dms_货币 a left join (select 编号,sum(金额) as sumnum from  dms_".$this->name."明细  group by 编号)b on a.编号=b.编号 set a.".$this->name."=IFNULL(b.sumnum,0)");//货币分离
		} 
		public function event_modifyId($oldbh,$newbh)
		{ 
			M()->execute("update dms_{$this->name}明细 set 编号='{$newbh}' where 编号='{$oldbh}'");
		}
		//货币日分红
		public function event_caldayover($caltime)
		{
			
			$cons = $this->getcon('rebate',array('val'=>'','where'=>'1=1','bankmode'=>'','bankmemo'=>'','lockdate'=>''));
			
			foreach($cons as $con)
			{
				//如果有节假日锁定
				if($con['lockdate'] != '')
				{
					$dateset = X('fun_dateset@'.$con['lockdate']);
					if (!$dateset instanceof fun_dateset){
						throw_exception($this->name.'获取lockDate失败,未找到指定fun_dateset模块');
					}
					if($dateset->getDateBool($caltime))
					{
						continue;
					}
				}
				
				$where = delsign($con['where']);
				//$users=M('会员')->where($where)->field('id,编号,'.$this->name)->select();
				$users=M('货币')->where($where)->field('id,编号,'.$this->name)->select();//货币分离
				foreach($users as $user)
				{
					if($user[$this->name]<=0)
					{
						continue;
					}
					//对会员余额进行增加
					$price = getnum($user[$this->name],$con['val']);
					//M('会员')->bSave(array('id'=>$user['id'],$this->name.'+'=>$price));
					M('货币')->bSave(array('id'=>$user['id'],$this->name.'+'=>$price));//货币分离
					$memo=$con["bankmemo"];
					$memo=str_replace('{date}',date('Y-m-d',$caltime),$memo);
					$memo=str_replace('{val}',$price,$memo);
			      	M($this->name.'明细')->bAdd(array(
			      		'编号'=>$user['编号'],
			      		'来源'=>'',
			      		'类型'=>$con['bankmode'],
			      		'备注'=>$memo,
			      		'金额'=>$price,
			      		'余额'=>$user[$this->name] + $price,
			      		'时间'=>systemTime(),
			      		));
				}
				//M('会员')->bupdate();
				M('货币')->bupdate();//货币分离
				M($this->name.'明细')->bupdate();
			}
		}
	}
?>