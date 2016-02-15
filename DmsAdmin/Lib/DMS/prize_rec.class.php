<?php
	/*
		推荐奖模块
		功能迭代历史
		1拿推荐的人的订单的10%(已作废)
		<_con val='10%' />
		2增加可以拿推荐的人的奖金的10%,增加了rowname和rowmode属性使用
		3增加了不同级别不同比例,考虑到考虑到如1-2级比例一致,所以使用minlv和maxlv
		<_con val='10%' minlv='1' maxlv='2' />(已作废)
		4增加了可以拿多代的情况,考虑到扩展性,设计为可以使用
		<_con val='10%' minlv='1' maxlv='2' minlayer='1' maxlayer='2'/>
		5要求级别按照推荐人和被推荐人的最小级别来做级别判定,增加了getmin属性
		6因为节点属性写错,导致售后,增加了conFilter属性,
		 限定con属性只能定义含有的这些.如果定义不含有的这些则报错
		7产生奖金时,对上边第一个级别大于3的,算第一代奖金,在找到一个算第二代,也就是紧缩
		 增加了tightenwhere属性tightenwhere="[会员级别]>3"来实现功能
		8要求订单,产生业绩的人,产生奖金的人,要能符合特定条件,
		 甚至有,产生业绩的人,必须是自己的安置人.要混合判断
		 增加了<_con where="S[订单字段]='xxx' and M[拿奖人字段]='xxx' and U[报单人字段]='xxx'" ..../>
		10要求产生业绩的人只能是自己的右区和其左区一条线,如图
		         A
		                  B
		               C
		             D
		 即A可以拿到BCD的奖金
		 为了实现这一点,在判断A和D之间奖金时,合成了"右左左"的字符.表示D在A的右区的左区的左区
		 (具体字符内容取决于net_place的_region的name设定)
		 然后可以用正则判断,增加了判断表达式
		 <_con where="{region,^右左*$}" ..../>
		11三个区,要拿C区的AB无线层(大写字母可以产生奖金)
		         我
		      a  b   c
		           A  B
		          AB AB
		         .. ...
		 <_con where="{region,^C[AB]+$}" ..../>
		 12要求实现产生业绩的人与我之间.只有一个人和我平级.并且没有级别超过我的
		 <_con where="{lv=}=1 and {lv>}=0" ..../>
		 为了这类需求创建了一组关键字
		 {lv=}    产生业绩的人与我之间有几个人级别等于我(不含产生业绩的人的本身)的人的数量
		 {lv>}    根据第一句话的解释...其下的内容请自行脑补.
		 {lv<}
		 {lv<=}
		 {lv>=}
		13我所推荐的第三个人以及以后,所推荐的第一第二个人无限代,和11号需求类似
		 <prize_rec regRow='推荐_被推荐数'>
		 <_con where="{regrow,^([3-9]|[1-9][0-9]+)(,[1-2])*$}"/>
		 即可以根据一个特定字段使用","作为分隔,组成一个新字符串,用regrow来验证.
		 regrow为正则列的意思,跟注册无关
		14客户提出了,要单数层才能拿奖金,如1,3,5,7,一直到19
		 增加了{layer}标签,表示了相对代数
		 <_con where="{layer} % 2 = 1" minlayer='1' maxlayer='19' .../>
		15计算日期当前计算日期要参与到判断当中,具体忘了需求是什么样子了
		 <_con where="{caltime} " minlayer='1' maxlayer='19' .../>
	*/
	class prize_rec extends prize
	{
		//产生类型
		public $prizeMode=1;
		//网络体系名称
		public $netName = '';
		//奖金来源表达式
		public $rowName = '';
		//奖金来源类型
		public $rowFrom = 0;
		//来源表条件
		public $where = '';
		//紧缩条件
		public $tightenwhere = '';
		//订单来源状态下的订单类别
		public $saleState = '已结算,已确认';
		//取得上下级最小级别,进行级别判定
		public $getMin=false;
		//小数精度
		public $decimalLen = 2;
		//判断是否显示奖金构成
		public $isSee = true;
		//判断获取上级时是否获取自身 默认为false
		public $haveme = false;
		//con过滤器
		public $conFilter=array('con'=>array("minLayer","maxLayer","minlv","maxlv","val","where",'isSee'));
		//字符替换标注
		public $regRow = '';
		//字符替换标注
		private $conStrP = array();
		//是否紧缩向上
		public $nlayer=false;
		//对上紧缩的条件
		public $nwhere="";
		//在秒结情况下当前订单在安置网产生的新业绩,服务于region_num标签
		public $_placeNewVal=null;
		//秒结算驱动
		function scal($sale)
		{
			//如果是通过会员表中产生奖金,可以直接走CAL流程
			if($this->rowFrom==0)
			{
				$this->cal();
			}
			else
			{
				if($this->where=="")
				{
					//如果判定没有任何条件,则将条件设置为id等于当前订单
					//这样此奖金只计算当前审核的订单
					$this->where = "id=" . $sale["id"];
					//执行结算
					$this->cal();
					//结算后还原条件
					$this->where = "";
				}
				else
				{
					//如果存在where设定,则使用临时变量存储.
					//并且在原条件中增加对ID的判断,结算完成后,在还原到回原始的where条件
					$otherwhere = $this->where;
					$this->where = '('.$otherwhere . ") and id=".$sale["id"];
					$this->cal();
					$this->where = $otherwhere;
				}
			}
		}
		//结算执行
		function cal()
		{
			//使用prize基类的方法,判定是否允许指定本奖金,并根据计算周期进行条件初始化操作
			if(!$this->ifrun()) return;
			//得到网络关系
			$net = X('@'.$this->netName);
			if($net === NULL)
			{
				throw_exception($this->name."计算时网络体系获取失败,请检查其netName设置是否正确");
			}
			if($this->rowName == '')
			{
				throw_exception($this->name.'奖金模块的$rowName没有设置');
			}
			//如果是根据订单产生奖金,同时系统中包含sale_buy类型.并且没有设置订单获取条件,则会抛异常
			if($this->rowFrom==1 && $this->where=='' && count(X('sale_buy'))>0)
			{
				throw_exception($this->name.'没有写where条件,但是订单除了注册还存在其他类型,可能会引发问题,请写明where条件,如果是全部订单都要获取,请设置为where=\'true\'');
			}
			$num_ratio = false;
			//得到可能出现的最大层数
			$rec_maxlayer = 0;
			//获得奖金计算设置
			$cons = $this->getcon('con',array("minLayer"=>'-1',"maxLayer"=>'-1',"minlv"=>0,"maxlv"=>0,"val"=>'',"where"=>''));
			//循环设置
			foreach($cons as $con)
			{
				//用于优化,如果VAL全部带有%,而rowname的结果为0,则可以忽略当次计算
				if(substr($con['val'],-1,1) != '%')
					$num_ratio=true;
				foreach(array('lvreg','qureg','layer','caltime') as $val)
				{
					if(strpos($con['where'],'{'.$val.'}')!==false)
					$this->conStrP[$val]=$val;
				}
				//判断查询网体上级时可以获取到的最大层数
				if($rec_maxlayer<$con['maxLayer'])
					$rec_maxlayer=$con['maxLayer'];
			}
			//进行条件自动优化,如果全部为比例模式.并且条件为空.则设置额外条件
			//从订单获取奖金来源
			if($this->rowFrom == 1)
			{
				if($this->table){
					//根据设置的表名获取相应的值
					$sales=$this->getextent($this->where,"*,$this->rowName as t_recnum",$this->table);
				}else{
					//根据当前条件.获得订单,在此方法中自动会考虑结算周期的时间范围判定,第二个参数表示设置要查询的字段信息
					$sales=$this->getsale($this->where,"*,$this->rowName as t_recnum");
				}
				if(isset($sales)){
					//循环所有订单
					foreach($sales as $sale)
					{
						//计算操作
						$this->calculate($net,$sale,$sale['userid'],$sale,$rec_maxlayer,$cons,$num_ratio);
					}
				}
				$this->prizeUpdate();
				unset($sales);
			}
			//根据会员表信息产生奖金
			if($this->rowFrom == 0)
			{
				//判断如果没有额外条件,并且参数全部是以百分比计算,则自动增加rowName属性不等于0的设置.进行优化处理
				if(!$num_ratio && $this->where=="")
				{
					$this->where="($this->rowName)<>0";
				}
				//取得符合条件的会员信息
				$users=$this->getuser($this->where,"*,$this->rowName as t_recnum");
				//会员信息
				if($users)
				foreach($users as $user)
				{
					//计算操作
					$this->calculate($net,$user,$user['id'],null,$rec_maxlayer,$cons,$num_ratio);
				}
				$this->prizeUpdate();
				unset($users);
			}
			//------------------------------------
			unset($cons);
		}
	    /**
	     +----------------------------------------------------------
	     *	计算操作
		 *	为什么要创建calculate函数
		 *	calculate函数并不是模块标准,而是在模块内部随意命名的函数.
		 *	因为通过订单来计算奖金,还是通过会员信息(如碰对奖)计算奖金.
		 *	所需要进行的流程基本一致所以把计算部分单独抽离一个函数来进行
	     *+----------------------------------------------------------
	     * @param object $net 网络节点
	     * @param array $from 奖金的来源,如果rowFrom为1则是一条订单的信息
	                    ,如果为0则是一条会员的信息.数组中有固定的t_recnum
	                    项,表示要计算的金额.即rowName表达式的值
	     * @param int $userid 产生业绩这个人的编号,用于通过其寻找上级
	     * @param array $sale 如果rowFrom=1则传入当前产生业绩订单的记录信息
	     * @param int $rec_maxlayer 传入最大层数,表示con可以计算的最大层数,实现优化
	     * @param array $cons 奖金设置数组
	     * @param bool $num_ratio 表示是否存在非百分比的奖金设置,如果存在.
	                   当$from['t_recnum']=0的时候.还需要计算计算,否则可以忽略当次计算
	     +----------------------------------------------------------
	     * @access public
	     +----------------------------------------------------------
	     */
		public function calculate($net,&$from,$userid,$sale=null,$rec_maxlayer,&$cons,$num_ratio)
		{
			$caltime=$this->_caltime;
			//取得产生业绩的会员
			if($this->rowFrom == 0)
			{
				$user=$from;
			}
			else
			{
				$user =M("会员")->where(array("id"=>$userid))->lock(true)->find();
			}
			//过滤缓存数据
			$user=X("user")->filt(array($this->lvName),$user);
			//紧缩条件
			$tightenwhere = $this->tightenwhere;
			$tightenwhere = str_replace('{caltime}',$caltime,$tightenwhere);
			//取得网络上级
			$upusers=$net->getups($user,1,$rec_maxlayer,$tightenwhere);
            //合成级别信息,格式类似于'3,1,2,1'
            //表示,表示产生奖金的人的1234代的级别的数字
			$lvreg=$user[$this->lvName];
			//合成位置信息,在参照网络为安置网络的情况下,则会合成'左右左左'此类字符
			$qureg = '';
			if(get_class($net) == 'net_place')$qureg = $user[$net->name.'_位置'];
			$regstr='';
			if($this->regRow!='')
			{
				$regstr=$user[$this->regRow];
			}
			//设置当前层数
			$layer=1;
			//循环所寻找到的上级
			foreach($upusers as $upuser)
			{
				//过滤缓存数据
				$upuser=X("user")->filt(array($this->lvName),$upuser);
				//循环配置
				foreach($cons as $conkey=>$con)
				{
					$minLayer = is_numeric($con['minLayer']) ? $con['minLayer']:transform($con['minLayer'],$upuser);
					$maxLayer = is_numeric($con['maxLayer']) ? $con['maxLayer']:transform($con['maxLayer'],$upuser);
					//取得当前上级的级别
					$up_rs_lv = $upuser[$this->lvName];
					//考虑到getmin函数等givelv还不算完善暂时停用
					//if($this->useGiveLv && isset($upuser['赠送'.$this->lvName]) && $upuser[$this->lvName]< $upuser['赠送'.$this->lvName])
					//	$up_rs_lv = $upuser['赠送'.$this->lvName];
					if($this->getMin && $up_rs_lv>$user[$this->lvName])
						$up_rs_lv=$user[$this->lvName];
					
					if($con['minlv'] > $up_rs_lv || $con['maxlv'] < $up_rs_lv)
					{
						 //编号
						 if(defined('DEBUG_USER') && DEBUG_USER==$upuser['编号'])
						 {
						 	dump($this->name.':因级别不符忽略'.($conkey+1).'个con,来源为'.$user['编号']);
						 }
						//如果判断层数或者级别不符合条件则直接退出
						continue;
					}
					if( $minLayer > $layer || ($maxLayer >= 0 && $maxLayer < $layer))
					{
						 if(defined('DEBUG_USER') && DEBUG_USER==$upuser['编号'])
						 {
						 	dump($this->name.':因层数不符忽略'.($conkey+1).'个con,来源为'.$user['编号']);
						 }
						 continue;
					}
					$where=$con['where'];
					//reglv替换,使用{reglv,正则查询表达式}格式,可以直接对reglv进行正则判定
					if(strpos($where,'{reglv')!==false)
					$where  = preg_replace('/{reglv,([^}]+)}/','preg_match("/$1/","'.$lvreg.'") = 1',$where);
					//region替换,使用{region,正则查询表达式}格式,可以直接对region进行正则判定
					if(strpos($where,'{region')!==false)
					$where  = preg_replace('/{region,([^}]+)}/','preg_match("/$1/u","'.$qureg.'") = 1',$where);
					//自定义正则表达式
					if(strpos($where,'{regrow')!==false)
					$where  = preg_replace('/{regrow,([^}]+)}/','preg_match("/$1/u","'.$regstr.'") = 1',$where);
					if(strpos($where,'{regrow')!==false)
					$where  = preg_replace('/{regrow,([^}]+)}/','preg_match("/$1/u","'.$regstr.'") = 1',$where);
					//判断自己的区域排列
					if(strpos($where,'{region_num')!==false)
					{
						$caltype=$this->parent()->_caltype;
						
						//
						//{region_num,是否并列[,是否包含秒结本单业绩]
        				preg_match('/\{region_num,(true|false)(?:,(true|false))?\}/',$where,$region_result); 
        				if($caltype==0 && count($region_result)==2)
        				{
        				     throw_exception('在秒结情况下region_num,必须带有第二参数来确定判定业绩是否包含激发秒结订单所产生的业绩');
        				}
						//得到产生奖金的会员是自己下属那个区
						$thisregion=mb_substr($qureg,0,1,'utf-8');
						//这个区对应了$net->getBranch()中的key
						$thisregion_id=-1;
						//取得累计业绩会存入这个数组
						$sumnum=array();
						//得到所有累计业绩
						foreach($net->getBranch() as $branthKey=>$region)
						{
							//设置产生业绩的人是产生奖金的人那个区域的key
							if($region==$thisregion)
							{
								$thisregion_id=$branthKey;
							}
							//秒结处理
							if($caltype==0)
							{
								//秒结情况下的
								$sumnum[$branthKey]=$upuser[$net->name.'_'.$region.'区累计业绩'];
								//如果不包含新订单所产生的业绩
								if($region_result[2]=='false')
								{
									if($this->_placeNewVal===null)
									{
										$this->_placeNewVal=M($net->name.'_业绩')->lock(true)->where('pid=0 and saleid='.$sale['id'])->getfield('val');
									}
									//当前循环的区域,和新会员在产生奖金的人的区域一致,那么这个区的业绩,要减去订单产生的新业绩 
									if($region==$thisregion)
									{
										$sumnum[$branthKey]-=$this->_placeNewVal;
									}
								}
							}
							else
							{
								$sumnum[$branthKey]=$net->cache[$upuser['id']][$net->name.'_'.$region.'区累计业绩'];
							}
						}
						
						//根据值,在根据键做排序
						$sumtemp = $sumnum;
						uksort($sumnum, function ($a,$b) use ($sumtemp) {
						    if ($sumtemp[$a] === $sumtemp[$b]) {
						        return $a - $b;
						    }
						    return $sumtemp[$b] - $sumtemp[$a];
						});
						unset($sumtemp);
						//得到排列位置
						$region_num=1;
						//得到排列位置(并列)
						$region_num_Parallel=1;
						//得到判断业绩变化的值
						$region_diffval=null;
						foreach($sumnum as $region_id=>$region_val)
						{
							if($region_diffval===null)
							{
								//第一个单元的值
								$region_diffval=$region_val;
							}
							else 
							{
								//如果当前区和上一个区的业绩不一样大
								if($region_diffval!=$region_val)
								{
									$region_num_Parallel=$region_num;
								}
							}
							//如果循环到的区,是产生业绩那个人所在的区
							if($region_id==$thisregion_id)
							{
								$where  = str_replace($region_result[0],($region_result[1]=="true" ? $region_num_Parallel:$region_num),$where);
								break;
							}
							//循环到第二个人
							$region_num++;
						}
					}
					//对{lv的判定做处理
					if(strpos($where,'{lv')!==false)
					{
						/*
						   这一段表示的是 当前的$upuser 和 $upuser 下面的会员 这些会员也是属于$user的上级会员 
						   例如 $user的上级会员 如图
						   1
						   2
						   3
						   $user 为新会员 1,2,3 都是$user的上级会员 就是判断上级会员与上级会员之间的级别关系 进行判断
						*/
						$downlvary = explode(',',$lvreg);
						$lv_gt=0;//大于数量 来统计当前$upuser的级别比($upuser会员下面的$user的上级会员)小的数量
						$lv_eq=0;//等于数量 来统计当前$upuser的级别比($upuser会员下面的$user的上级会员)一样的数量
						$lv_lt=0;//小于数量 来统计当前$upuser的级别比($upuser会员下面的$user的上级会员)大的数量
						foreach($downlvary as $key=>$lvr){
							//循环到产生业绩的那个点位，不在纳入计数
							if($key==count($downlvary)-1)
							{
								break;
							}
							if($lvr>$up_rs_lv)
							{
								$lv_gt++;//大于
							}elseif($lvr==$up_rs_lv)
							{
								$lv_eq++;//等于
							}else{
								$lv_lt++;//小于
							}
						}
						if(strpos($where,'{lv>}')!==false)
						$where = str_replace('{lv>}',$lv_gt,$where);
						if(strpos($where,'{lv<}')!==false)
						$where = str_replace('{lv<}',$lv_lt,$where);
						if(strpos($where,'{lv=}')!==false)
						$where = str_replace('{lv=}',$lv_eq,$where);
						if(strpos($where,'{lv>=}')!==false)
						$where = str_replace('{lv>=}',$lv_gt+$lv_eq,$where);
						if(strpos($where,'{lv<=}')!==false)
						$where = str_replace('{lv<=}',$lv_lt+$lv_eq,$where);
					}
					
					foreach($this->conStrP as $val)
					{
						$where=str_replace('{'.$val.'}',$$val,$where);
					}
					//如果设置了getMin则表示计算的级别标准,按照双方最小级别来计算
					//U的意思为User,表示用户，也就是产生业绩的人 M的意思表示为Me,也就是产生奖金的人 S的意思为Sale,表示订单。如果当前奖金的rowform为1则此项目表示当前产生业绩的订单
					$wheredata=array('U'=>&$user,'M'=>&$upuser,'S'=>&$sale);
					if(transform($where,array(),$wheredata))
					{
						//得到最终的奖金额
						$prizenum=getnum($from['t_recnum'],$con['val'],$this->decimalLen,$upuser[$this->name.'比例']);
						//增加奖金
						$this->addprize($upuser,$prizenum,$user,substr($con['val'],-1,1) == '%'?$from['t_recnum'].'*'.$con['val']:'',$layer);
					}
					else
					{
						 if(defined('DEBUG_USER') && DEBUG_USER==$upuser['编号'])
						 {
						 	echo $this->name.':因where条件不符忽略'.($conkey+1).'个con,来源为'.$user['编号'].'<br>';
						 }
					}
				}
				if(!$this->nlayer || ($this->nlayer && transform($this->nwhere,array(),$wheredata)))
					//层数加1
					$layer++;
				$lvreg=$upuser[$this->lvName].",".$lvreg;
				//合成安置信息
				if(get_class($net) == 'net_place')$qureg = $upuser[$net->name.'_位置'].$qureg;
				if($this->regRow!='')
				{
					$regstr=$upuser[$this->regRow].','.$regstr;
				}
			}
			//注销上级数组
			unset($upusers);
			//注销产生业绩用户
			unset($user);
		}
	}
?>