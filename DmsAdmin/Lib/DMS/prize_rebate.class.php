<?php

	class prize_rebate extends prize
	{
		public $prizeMode=1;
		//奖金来源表达式
		public $rowName = '';
		//奖金来源类型
		public $rowFrom = '';
		//来源表条件
		public $where = '';
		//起征字段
		public $startRow='';
		//是否要把自己的奖金,扣除下级产生此奖金之和
		public $section=false;
		//判断是否显示奖金构成
		public $isSee = true;
		public $deductTree='';
		//小数精度
		public $decimalLen = 2;
		//是否执行sql方式执行
		public $usesql = false;
		
		public $conFilter=array('con'=>array("minlv","maxlv","val","where",'isSee'));
		function scal($sale)
		{
			if($this->rowFrom==0)
			{
				$this->cal();
			}
			else
			{
				if($this->where=="")
				{
					$this->where="id=".$sale["id"];
					$this->cal();
					$this->where="";
				}
				else
				{
					$otherwhere=$this->where;
					$this->where='('.$otherwhere.") and id=".$sale["id"];
					$this->cal();
					$this->where=$otherwhere;
				}
			}
		}
		//日结驱动
		function cal()
		{
			
			if(!$this->ifrun()) return;
			
			if(!X('levels@'.$this->lvName) instanceof levels)
			{
				throw_exception($this->name.'计算失败,因其lvName属性未找到对应的级别模块');
			}
			$num_ratio = false;
			$rec_maxlayer = 0;
			$cons = $this->getcon('con',array("minlv"=>1,"maxlv"=>1,"val"=>"","where"=>""));
			$topcons = $this->getcon("top",array('val'=>'','mode'=>'','where'=>'','with'=>''));
			foreach($cons as $con)
			{
				//用于优化,如果VAL全部带有%,而rowname的结果为0,则可以忽略当次计算
				if(substr($con['val'],-1,1) != '%')
				$num_ratio=true;
			}
			if($this->rowFrom == 1)
			{
				$sales=$this->getsale($this->where,"*,$this->rowName as t_recnum");
				if(isset($sales)){
					foreach($sales as $sale)
					{
						$this->calculate($sale,$sale['userid'],$sale,$cons,$num_ratio);
					}
				}
				unset($sales);
			}
			if($this->rowFrom == 0)
			{
				if(!$num_ratio&&$this->where=="")
				{
					$this->where="($this->rowName)<>0";
				}
				$users=$this->getuser(str_replace('>>','<',$this->where),"*,$this->rowName as t_recnum");
				if($users){
					//在rowfrom=0且不进行K值的情况下.使用SQL批量的形式执行奖金计算.而不是单个会员循环执行addprize
					//where条件中不要用M
					/*************************************************************/
					if($this->usesql && $this->K==0){
						foreach($cons as $con)
						{
							$wherestr  = " and ".$this->lvName." between ".$con['minlv']." and ". $con['maxlv'];
							$wherestr .= " and ".delsign($this->where);
							$wherestr .= " and 状态='有效' and 奖金锁=0";
							$val = $con['val'];
							//如果含有百分号
							if(strstr($val,'%')){
								//替换百分号为*0.01
								$jisuan=str_replace("%","*0.01",$val);
								$val = "(".$this->rowName.")*". eval("return $jisuan;");
							}else if(substr($val,0,1)=='*'){
								//字符第一位有乘号
								$jisuan=substr($val,1,99999);
								$val = "(".$this->rowName .")*". eval("return $jisuan;");
							}else{
								$val = transform($val);
							}
							if(empty($topcons)){
								$sqlstr = "update dms_会员 set ".$this->name."=".$val.",".$this->name."本日=".$this->name."本日+".$val.",".$this->name."本周=".$this->name."本周+".$val.",".$this->name."本月=".$this->name."本月+".$val.",".$this->name."累计=".$this->name."累计+".$val." where 1";
								M()->execute($sqlstr.$wherestr);
							}else{
								//封顶
								foreach($topcons as $topcon){
									$with = $topcon['with'];
									if($with == '')
									{
										$with=$this->name;
									}
									$withs = explode(',',$with);
									$withprize = '';
									foreach($withs as $with)
									{
										switch($topcon['mode'])
										{
											case 'day':
												$withprize .="+".$with.'本日';
											break;
											case 'week':
												$withprize .="+".$with.'本周';
											break;
											case 'month':
												$withprize .="+".$with.'本月';
											break;
											case 'all':
												$withprize .="+".$with.'累计';
											break;
										}
									}
								}
								//有where条件限制封顶
								if($topcon['where']!=''){
									//符合where条件
									$sqlstr = "update dms_会员 set ".$this->name."=if(".$val.$withprize.">".$topcon['val'].",if((".$topcon['val']."-".$withprize.")<0,0,(".$topcon['val']."-".$withprize.")),".$val."),".$this->name."本日=".$this->name."本日+if(".$val.$withprize.">".$topcon['val'].",if((".$topcon['val']."-".$withprize.")<0,0,(".$topcon['val']."-".$withprize.")),".$val."),".$this->name."本周=".$this->name."本周+if(".$val.$withprize.">".$topcon['val'].",if((".$topcon['val']."-".$withprize.")<0,0,(".$topcon['val']."-".$withprize.")),".$val."),".$this->name."本月=".$this->name."本月+if(".$val.$withprize.">".$topcon['val'].",if((".$topcon['val']."-".$withprize.")<0,0,(".$topcon['val']."-".$withprize.")),".$val."),".$this->name."累计=".$this->name."累计+if(".$val.$withprize.">".$topcon['val'].",if((".$topcon['val']."-".$withprize.")<0,0,(".$topcon['val']."-".$withprize.")),".$val.") where 1";
									M()->execute($sqlstr.$wherestr." and ".delsign($topcon['where']));
									//不符合where条件
									$sqlstr = "update dms_会员 set ".$this->name."=".$val.",".$this->name."本日=".$this->name."本日+".$val.",".$this->name."本周=".$this->name."本周+".$val.",".$this->name."本月=".$this->name."本月+".$val.",".$this->name."累计=".$this->name."累计+".$val." where 1";
									M()->execute($sqlstr.$wherestr." and not(".delsign($topcon['where']).")");
								}else{
									$sqlstr = "update dms_会员 set ".$this->name."=if(".$val.$withprize.">".$topcon['val'].",if((".$topcon['val']."-".$withprize.")<0,0,(".$topcon['val']."-".$withprize.")),".$val."),".$this->name."本日=".$this->name."本日+if(".$val.$withprize.">".$topcon['val'].",if((".$topcon['val']."-".$withprize.")<0,0,(".$topcon['val']."-".$withprize.")),".$val."),".$this->name."本周=".$this->name."本周+if(".$val.$withprize.">".$topcon['val'].",if((".$topcon['val']."-".$withprize.")<0,0,(".$topcon['val']."-".$withprize.")),".$val."),".$this->name."本月=".$this->name."本月+if(".$val.$withprize.">".$topcon['val'].",if((".$topcon['val']."-".$withprize.")<0,0,(".$topcon['val']."-".$withprize.")),".$val."),".$this->name."累计=".$this->name."累计+if(".$val.$withprize.">".$topcon['val'].",if((".$topcon['val']."-".$withprize.")<0,0,(".$topcon['val']."-".$withprize.")),".$val.") where 1";
									M()->execute($sqlstr.$wherestr);
								}
							}
							unset($users);
							return;
						}
					}else{
						foreach($users as $user)
						{
							$this->calculate($user,$user['id'],null,$cons,$num_ratio);
						}
					}
					/*************************************************************/
				}
				unset($users);
			}
			//------------------------------------
			unset($cons);
			$this->prizeUpdate();
		}
		public function calculate(&$from,$userid,$sale=null,&$cons,$num_ratio)
		{
			//$form=来源表
			//$userid=产生业绩会员ID
			//$sale=如果来源为订单则传入订单数据
			//级别正则判定数据
			if($from['t_recnum'] == 0 && !$num_ratio)
				return;
			if($this->rowFrom == 0)
			{
				$user=$from;
			}
			else
			{
				$user =M('会员')->where(array("id"=>$userid))->lock(true)->find();
			}
			//过滤缓存数据
			$user=X("user")->filt(array($this->lvName),$user);
			foreach($cons as $con)
			{
				$wheredata=array('M'=>&$user,'S'=>&$sale);
				//取双方最小级别,则做降级操作
				if($con['minlv'] <= $user[$this->lvName] && $con['maxlv'] >= $user[$this->lvName] && 
				    transform($con['where'],array(),$wheredata))
				{
					//得到最终数字
					$prizenum = getnum($from['t_recnum'],$con['val'],$this->decimalLen,$user[$this->name.'比例']);
					//增加奖金
					$this->addprize($user,$prizenum,$user,substr($con['val'],-1,1) == '%'?$from['t_recnum'].'*'.$con['val']:'');
				}
			}
			unset($user);
		}
	}
?>