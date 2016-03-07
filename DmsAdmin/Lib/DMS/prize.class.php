<?php
//奖金父类
/*
	函数概要
	_initialize  用于确定奖金的实际计算周期（本身设置用本身，tle设置用tle）
	gettop       处理会员奖金封顶_top配置项
	isrowfrom    判定奖金的来源类型
	ifrun        判断奖金是否可以运行
	             对lockDate属性的特定日期不能执行奖金的功能做实现
	             判定奖金是否符合tlemode的计算周期
	             根据tlemode定义订单表，会员表的额外查询条件。供getuser,getsale使用
	             输出计算提示信息
	             奖金计算前升级处理
	getsale      取得符合计算周期范围内的订单
	getuser      取得符合计算周期范围内的会员
	addprize     产生奖金，并加入缓存，再其内部会调用gettop处理封顶
	prizeUpdate   更新奖金更新会员表，并处理奖金K值
	getSelRow    得到各类配置可能调用到的会员字段
*/
	class prize extends stru
	{
		//用户部分是否允许查看
		public $userDisp = true;
		//后台部分是否允许查看
		public $adminDisp = true;
		//奖金产生类型0为不产生,1为产生,2为扣除
		public $prizeMode = 1;
		/*
			结算周期,如果为空，则以tle的tleMode属性为准
			d 日结
			s 秒结
			w 周结
			m  月结
			ms 月多次结，如1号、15号、月末各结一次
			y  年结
			ys 年多次结，如3、6、9、12月的月做季度结算
			all 日/秒结，一般是考虑存在秒日混合结算的所得税或者重复消费，使用此设定，能够同时相应秒结和日结
			r  根据审核日期周期结
		*/
		public $tleMode = '';
		/*
			结算日期，根据tleMode的设置不同，会有不同含义
			日结     无作用
			秒结     无作用
			周结     设置为周几结算，可以设置到1-7，表示周一到星期天
			月结     设置为每月几号结算，如果设置为0，则表示月末结算
			月多次结 设置多个结算日期如"1,15,0"日期需要从小到大填写，如果存在月末则需要在最后设置0
			年结     无作用
			年多次结 设置结算的月份,如"3,6,9,12"，分别在这几个月份的月末进行计算
			日/秒结  无作用
			审核结   设置格式为"步长[,起始周期][,终止周期]"，比如审核后每十天那一次奖金，可以拿5次，则设置"10,1,5"
		*/
		public $tleDay = "";
		/*
			结算天数,此设置只能用于 结算之前的，在结算当前奖金时，将结算周期往前移动？？？？
		*/
		public $tleStep = 0;
		//数值长度以及小数位
		public $decimalLon = 14;
		public $decimalLen = 2;
		//级别名称
		public $lvName='';
		//结算周期额外条件
		public $_saleWhere ="";
		public $_userWhere ="";
		public $_extentWhere ="";//拓展表查询
		//禁止执行周期,对应fun_dateset模块
		public $lockDate="";
		public $prize_cache=array();
		//奖金开始之前是否要从新进行升级操作
		public $startuplv=false;
		//当执行ifrun以后，取得可以查找订单的最早时间
		public $_salestarttime=null;
		/*
			奖金K值
			此奖金的奖金总额当超过总业绩的K%的时候，则所有人会按照计算好的比例减少实际发放，
			比如正常发放100，可能只发放了90，以满足这个奖金的拨出比控制在K%
		*/
		public $K = 0;
		/*
			K值条件，符合条件的人才会被降权发放，那么超过K值的这些奖金，都从符合条件的这些人当中平均扣
		*/
		public $Kwhere = '';
		public $Kprize = '';
		//开关设置
		public $use = true;
		//启动时间
		public $startDate = 0;
		//关闭时间
		public $endDate = 0;
		//奖金是否可见
		public $isSee = true;
		//拓展查询的表
		public $table = "";//查询的表名
		public $timecheck = "";//时间名称
		//转入其他奖金项 将某个奖金的所有数据转移到另一个奖金上
		public $to = '';
		//是否生成构成信息
		public $memo = true;
		//是否显示反向构成
		public $unmemo = false;
		public $_caltime;
		//public static $sumcache=array();
        //在rowfrom=0的时候.只取得之前产生过奖金的人
        public $incaluser=false;
		public function _initialize(){
			//结算周期
			$this->tleMode  = ($this->tleMode == '') ? $this->parent()->tleMode : $this->tleMode;
			//结算日期
			$this->tleDay   = ($this->tleDay  == '') ? $this->parent()->tleDay  : $this->tleDay;
		}
		public function gettop($user,$prizenum)
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
								$statrnum+=$user[$with.'本日'];
							break;
							case 'week':
								$statrnum+=$user[$with.'本周'];
							break;
							case 'month':
								$statrnum+=$user[$with.'本月'];
							break;
							case 'all':
								$statrnum+=$user[$with.'累计'];
							break;
						}
						$_tprize = X('*@'.$with,$this->parent());
						if(isset($_tprize->prize_cache[$user["id"]]))
						{
							$statrnum += $_tprize->prize_cache[$user["id"]];
						}
					}
					$statrnum += $prizenum;
					$ifval = transform($con['val'],$user);
					if($statrnum > $ifval )
					{
						$t_num= $prizenum - ($statrnum - $ifval);
						if($ret>$t_num)
						$ret = $t_num;
					}
				}
			}
			if($ret<0)
			$ret=0;
			return $ret;
		}
		//判断当前对象是一种奖金,并且存在rowmode设定,判断是否符某一种类型的rowmode属性
		private function isrowfrom($rowfrom)
		{
			if(!isset($this->rowFrom))
			return false;
			return ($this->rowFrom==$rowfrom);
		}
		//结算方式
		public function getTleMode()
		{
			return ($this->tleMode == '') ? $this->parent()->tleMode : $this->tleMode;
		}
		//结算日期
		public function getTleDay()
		{
			return ($this->tleDay  == '') ? $this->parent()->tleDay  : $this->tleDay;
		}
		/**
			判断结算日期是否符合当前奖金的周期  如果符合进行组合相关的数据
			根据tlemode生成来源表的查询日期条件
		*/
		public function ifrun()
		{
			//计算时间
			$_caltime = $this->parent()->_caltime;
			//是否前移时间
			$_caltime -=$this->tleStep * 86400;
			//是否有开启奖金计算
			if(!$this->use)
				return false;
			//计算起始终止日的判断
			if($this->startDate>0 && $this->startDate > $_caltime)
				return false;
			if($this->endDate>0 && $this->endDate < $_caltime)
				return false;
			//假期的判断
			if($this->lockDate != '') {
				$dateset = X('fun_dateset@'.$this->lockDate);
				if (!$dateset instanceof fun_dateset){
					throw_exception($this->name.'获取lockDate失败,未找到指定fun_dateset模块');
				}
				if($dateset->getDateBool($_caltime))
				{
					return false;
				}
			}
			$ret=false;
			//结算周期
			$tlemode  = $this->getTleMode();
			//结算日期
			$tleday   = $this->getTleDay();
			//重置条件
			$this->_where='';
			//对当前运行条件以及TLDMODE周期进行判定,并返回是否应该执行此奖金
			switch ($tlemode) 
			{
				case 'all':
				case 's':
				case 'd':
				case 'w':
				case 'm':
				case 'ms':
				case 'y':
				case 'ys':
					$rdata=prize::chkTleMode($_caltime,$tlemode,$tleday);
					if($rdata)
					{
						$this->_saleWhere = " and 到款日期>=".$rdata['sdate']." and 到款日期<=".$rdata['edate'];
						$this->_userWhere = " and 审核日期<=".$rdata['edate'];
						$this->_extentWhere= " and `".$this->timecheck."`>=".$rdata['sdate']." and `".$this->timecheck."`<=".$rdata['edate'];
						$this->_salestarttime = $_caltime;
						$ret=true;
					}
					break;
				//审核日期间隔
				case 'r':
					if($this->isrowfrom(1))
					{
						throw_exception($this->name.'在设置了tlemode为r的情况下rowfrom属性必须为0)');
					}
					$ret=true;
					$_set=explode(',',$tleday);
					$_day=(int)$_set[0];
					$this->_userWhere =" and MOD(DATEDIFF(from_unixtime($_caltime),from_unixtime(审核日期)),$_day)=0";
					$this->_userWhere.=" and DATEDIFF(from_unixtime($_caltime),from_unixtime(审核日期))>=0";
					//起始期数
					if(count($_set)>=2)
					{
						$this->_userWhere.=" and floor(DATEDIFF(from_unixtime($_caltime),from_unixtime(审核日期))/".abs($_day).")>=".$_set[1];
					}
					//结束期数
					if(count($_set)>=3)
					{
						$this->_userWhere.=" and floor(DATEDIFF(from_unixtime($_caltime),from_unixtime(审核日期))/".abs($_day).")<=".$_set[2];
					}
					break;
			}
			//处理获取字段中的{uprow}转义
			if(isset($this->rowName) && in_array($this->rowName,array('{uprow}','{uprow+}','{uprow-}')))
			{
				if($this->isrowfrom(1))
				{
					throw_exception($this->name.'的rowMode属性为1的时候不能在rowName属性中使用{uprow}关键字');
				}
				$rowName="0";
				$rowNameInc="0";
				//判断奖金的增加
				foreach(X('prize_*',$this->parent()) as $prize)
				{
					if($prize->prizeMode>=1)
					{
						$rowName.=($prize->prizeMode==1?"+":"-").$prize->name;
					}
					if($prize->prizeMode==1)
					{
						$rowNameInc.='+'.$prize->name;
					}
				}
				$this->rowName=str_replace(array("{uprow}","{uprow+}"),array("($rowName)","($rowNameInc)"),$this->rowName);
			}
			if(get_class($this)=='prize_sql')
			{
				calmsg('执行prize_sql模块','/Public/Images/ExtJSicons/database_lightning.png');
			}
			else
			{
				switch($this->prizeMode)
				{
					case 0:
						calmsg('计算'.$this->byname.','.get_class($this),'/Public/Images/ExtJSicons/money.png');
					break;
					case 1:
						calmsg('计算'.$this->byname.','.get_class($this),'/Public/Images/ExtJSicons/money_add.png');
					break;
					case 2:
						calmsg('计算'.$this->byname.','.get_class($this),'/Public/Images/ExtJSicons/money_delete.png');
					break;
				}
			}
			//在奖金执行前要执行升级
			$this->_caltime = $_caltime;
			if($ret && $this->startuplv)
			{
				//在奖金执行前要执行升级
				foreach(X('levels') as $levels)
				{
					//自动升级处理操作//传入结算日最后一秒
					$levels->uplv('prizeStart',$_caltime+86400-1);
				}
			}
			return $ret;
		}
		//条件替换函数
		public static function calReplace($where,$name,$_caltime)
		{
			if(strpos($where,'{d,')!==false)
			{
				//日期匹配:{d,y,m,d,h,i,s}---可精确到秒，最终转化为mktime，支持mktime和date的语法，最少写{d,后面的前三位,6个字母参数会根据_caltime转换
				//例：{d,y,m-1,1}上月1号 ，{d,y,m,1}本月1号，{d,y,m,0}上月月末 ,{d,y-1,m-1,1}上年结算月的上个月1号，{d,2013,5,15}2013年5月15......			
				preg_match_all('/\{d,([^}]+)\}/',$where,$truevals,PREG_SET_ORDER);
				//匹配{d,内容}
				$mkmode=array(5,3,4,0,1,2);//字符串中的年月日在mktime中的位置
				foreach($truevals as $dset)
				{
					$argstr=date($dset[1],$_caltime);
					$argstr=explode(",",$argstr);
					//用于mktime的参数
					$mkargs=array('0','0','0','0','0','0');
					foreach($argstr as $key=>$val)
					{
						$mkargs[$mkmode[$key]]=$val;
					}
					$mkeval="return mktime(".join(',',$mkargs).");";
					$where=str_replace($dset[0],eval($mkeval),$where);
				}
			}
			return $where;
		}
		//对奖金期限计算的静态方法
		public static function chkTleMode($_caltime,$tleMode,$tleDay)
		{
			switch($tleMode)
			{
				case 'all':
				case 's':
				case 'd':
					return array('sdate'=>$_caltime,'edate'=>$_caltime+86400-1);
				break;
				case 'w':
					if((int)$tleDay < 1||(int)$tleDay > 7)
					{
						throw_exception('周结算时的tleday属性有问题，只能设置为1-7，表示周一到周日，周日为7');
					}
					if(date('N',$_caltime) == (int)$tleDay)
					{
						return array('sdate'=>$_caltime-86400*6,'edate'=>$_caltime+86400-1);
					}
				break;
				case 'm':
					$tleDay=(int)$tleDay;
					if($tleDay<=0 && date('j',$_caltime)==date('t',$_caltime))
					{
						return array('sdate'=>(strtotime(date("Y",$_caltime)."-".date("n",$_caltime)."-1")),'edate'=>(strtotime(date("Y",$_caltime)."-".date("n",$_caltime)."-".date("t",$_caltime)))+86400-1);
					}
					if($tleDay>0 && date('j',$_caltime)==$tleDay)
					{
						//得到上一个月的结算日的下一天
						$sdate = mktime(0,0,0,date("n",$_caltime)-1,date("j",$_caltime)+1,date("Y",$_caltime));
						$edate = mktime(0,0,0,date("n",$_caltime),date("j",$_caltime)+1 ,date("Y",$_caltime));
						return array('sdate'=>$sdate,'edate'=>$edate-1);
					}
					return array();
				break;
				case 'ms':
					//当前结算时间。整点
					$msdata=explode(',',$tleDay);
					foreach($msdata as $key=>$ifday)
					{
						if($key==0 && $ifday==0){
							throw_exception('月末的0不能作为tleDay的开头，请放到最后');
						}
						//月末判定成功(月末的0不能作为tleDay的开头，否则会出现错误)
						if($ifday == '0' && date('j',$_caltime)== date('t',$_caltime))
						{
							$sdate = strtotime(date("Y",$_caltime)."-".date("n",$_caltime)."-".$msdata[$key-1])+86400;
							return array('sdate'=>$sdate,'edate'=>$_caltime+86399);
						}
						//当前结算日是在ms设定中的某一天
						elseif(date('j',$_caltime)==$ifday)
						{
							//如果是tleDay的第一个设定，则需要取得最后一位设定作为终止日
							if($key==0)
							{
								//如果设定最后一天是月末,那么结算起始日就是本月月初
								if($msdata[count($msdata)-1] == '0')
								{
									$sdate = strtotime(date("Y",$_caltime)."-".date("n",$_caltime)."-1");
								}
								else
								{
									//得到上一个月的年和月
									$y=date("Y",$_caltime);
									$m=date("n",$_caltime);
									$m--;
									if($m==0)
									{
										$y-=1;
										$m=12;
									}
									$sdate = strtotime($y."-".$m."-".$msdata[count($msdata)-1]) + 86400;
								}
							}
							else
							{
								$sdate = strtotime(date("Y",$_caltime)."-".date("n",$_caltime)."-".$msdata[$key-1])+86400;
							}
							return array('sdate'=>$sdate,'edate'=>$_caltime+86399);
						}
					}
				case 'y':
				if(date('j',$_caltime) == date('t',$_caltime) && date('n',$_caltime) == 12)
				{
					$sdate = strtotime(date('Y',$_caltime).'-1-1');
					$edate = $_caltime + 86400;
					return array('sdate'=>$sdate,'edate'=>$edate);
				}
				case 'ys':
					if($tleDay=='')
					{
						throw_exception($this->name.'的tleDay设置有错误,未填写信息)');
					}
					$monthAry=explode(',',$tleDay);
					$curMonth=date('n',$_caltime);//当前月
					if(date('j',$_caltime) == date('t',$_caltime) && in_array($curMonth,$monthAry))
					{
						//月份位置
						$keyAry=array_keys($monthAry,$curMonth);
						$mkey=$keyAry[0];//键值
						$mc=count($monthAry);//几个月

						//得到准确时间
						if($mkey==0){//设置的第一个月份
							$sdate = mktime(0,0,0,$monthAry[$mc-1]+1,1,date("Y",$_caltime)-1);
						}else{
							$sdate = mktime(0,0,0,$monthAry[$mkey-1]+1,1,date("Y",$_caltime));
						}						
						$edate = mktime(0,0,0,date("n",$_caltime),date("j",$_caltime)+1 ,date("Y",$_caltime));
						return array('sdate'=>$sdate,'edate'=>$edate-1);
					}
			}
			return array();
		}
		//取得奖金计算时订单查询条件,并输出订单记录
		public function getsale($where='',$rows='*')
		{
			$where = delsign($where);
			$m_sale=M('报单');
			$calwhere="1=1";
			if($where!=""){
				$where=str_replace('{caltime}',$this->_caltime,$where);
				$where=self::calReplace($where,$this->name,$this->_caltime);
				$calwhere.=" and (".$where.")";
			}
			$calwhere.=$this->_saleWhere;
			$ret =  $m_sale->where($calwhere)->field($rows)->select();
			if($ret === false)
			{
				throw_exception($this->name.'获取订单失败,错误信息('.htmlentities($m_sale->getDbError(),ENT_COMPAT ,'UTF-8').")");
			}
			return $ret;
		}
		//获取拓展表数据结算
		public function getextent($where='',$rows='*',$table="")
		{
			$where = delsign($where);
			if($table=="")
			{
				throw_exception($this->name.'table不能为空,设置查询的数据表');
			}
			$m_table=M($table);
			$calwhere="1=1";
			if($where!="")
				$calwhere.=" and (".$where.")";
			if($this->timecheck!=""){
				//是否需要时间判断
				$calwhere.=$this->_extentWhere;
			}
			$ret =  $m_table->where($calwhere)->select();
			if($ret === false)
			{
				throw_exception($this->name.'获取数据失败,sql信息('.htmlentities($m_table->getDbError(),ENT_COMPAT ,'UTF-8').")");
			}
			return $ret;
		}
		//取得USER对象
		public function getuser($where='',$rows='*')
		{
			
			$where = delsign($where);
			$m_user=M();
			$caltime = $this->_caltime;
			//替换结算日期
			$where=str_replace('{caltime}',$caltime,$where);
			$calwhere="审核日期<({$caltime}+86400)";
            $sqlstr="select $rows from dms_会员 where ";
            //存在自定义where条件
			if($where!=""){
				$where=self::calReplace($where,$this->name,$this->_caltime);
				$calwhere.=" and (".$where.")";
			}
            //合成带有时间条件的where语句
			$calwhere.=$this->_userWhere;
            if($this->incaluser)
        	{
        		$calwhere=$this->parent()->caluserwhere($calwhere);
        	}
            $sqlstr.=$calwhere;
        	$ret = $m_user->query($sqlstr);
			if($ret === false)
			{
				throw_exception($this->name.'获取user失败,sql信息('.htmlentities($m_user->getDbError(),ENT_COMPAT ,'UTF-8').")");
			}
			return $ret;
		}
		//增加奖金记录
		public function addprize($user,$num,$fromuser=null,$memo=null,$layer=0)
		{
			if($num  == 0 || $user['奖金锁']==1)
			{
				return 0;
			}
            $tle=$this->parent();
        	$tle->addcaluser($user['id']);
			//处理封顶
			if(get_class($this)=='prize_pile'){
				$newnum=$num;
			}else{
				$newnum=$this->gettop($user,$num);
			}
			//增加构成信息
			if($memo !== null && $this->memo)
			{
				//如果没有设定来源,则默认用当前产生奖金人的信息
				if($fromuser==null)
				{
					$fromuser=&$user;
				}
				$name  =$this->name;
				$byname=$this->byname;
				if($this->to != '')
				{
					$p=X('@'.$this->to);
					$name  =$p->name;
					$byname=$p->byname;
				}
	            /*$comdata =array();
	            $comdata["name"]     =$name;            //奖金的xml位置
	            $comdata["prizename"]=$byname;          //奖金名
	            $comdata["userid"]   =$user["id"];      //得到奖金的会员的ID
	            $comdata["编号"]     =$user["编号"];    //得到奖金的会员的编号
	            $comdata["fromid"]   =$fromuser["id"];  //从哪个会员身上获得奖金的会员ID
	            $comdata["val"]      =$num;             //奖金金额
	            $comdata["trueval"]  =$newnum;          //封顶金额
	            $comdata["memo"]     =$memo;            //备注
	            $comdata["layer"]    =$layer;           //层数
	            $comdata["tighten"]  =0;         		//紧缩层数*/
	            $tle=$this->parent();
	            //对数组项中的数组赋值
	            //$tle->fromdata[]=$comdata;
	            //加载奖金构成处理类
				import('DmsAdmin.DMS.SYS.PrizeData');
	            PrizeData::add($tle->name,$name,$user["id"],$byname,$fromuser["id"],$num,$newnum,$memo,$layer,$this->lvName,($this->lvName!='')?$user[$this->lvName]:'');
			}
			
			if($newnum == 0)
				return 0;
			$newnum=round((float)$newnum,$this->decimalLen);
			/*if($user['状态']=='有效'){
				
			}*/
			if(isset($this->prize_cache[$user["id"]])){
				$this->prize_cache[$user["id"]] += $newnum;
			}else{
				$this->prize_cache[$user["id"]] = $newnum;
			}
			return $newnum;
		}
		//对产生的奖金信息增加到缓存
		public function prizeUpdate()
		{
			//奖金计算完成清除net节点的会员缓存
			if(isset($this->netName) && $this->netName!=""){
				X("net_*@".$this->netName)->clearup('',true);
			}
			//奖金缓存
			$dataarr=$this->prize_cache;
			//对奖金K值进行处理
			if($this->K != 0)
			{
				//取得结算起始时间
				$_caltime = $this->parent()->_caltime;
				$_caltime -=$this->tleStep * 86400;
				//得到本奖金总数
				$allprize = array_sum($dataarr);
				//统计总业绩
                $Achievement=0;
                //扫描所有订单
                foreach(X('sale_*') as $sale)
                {
                	//统计总账
                	if($sale->ledger != '')
                	{
                		//统计总账
                		$Achievement += M('报单')->where(array('到款日期'=> array(array('egt',$this->_salestarttime),array('lt',$_caltime+86400)),'报单类别'=>$sale->name))->sum($sale->ledger);
                	}
                }
                //得到业绩拨出比
                $krate_temp = 0;
                if($Achievement>0)$krate_temp = $allprize/$Achievement*100;
                //当超过播出比的时候进行处理
                if($this->K != 0 && $this->K < $krate_temp && $krate_temp > 0 )
                {
                	if($this->Kprize)
                	{
                		$kprize = X('@'.$this->Kprize);
                	}
                	calmsg($this->byname.'奖金达到了'.$krate_temp."%.将强制降为".$this->K."%",'/Public/Images/ExtJSicons/arrow/arrow_branch.png');
                	$nowkrate = 1-(($krate_temp - $this->K)/$krate_temp);
					
					if($this->Kwhere!=''){
						
						$tmpuser = array_intersect_key($dataarr,M('会员')->where($this->Kwhere)->getfield('id idkey,id'));
						//找到可以K的会员
						if($tmpuser)
						{
							$whereprize = array_sum($tmpuser);
							//比例等于 (符合条件奖金-多出来比例的总奖金)/符合条件奖金
							$nowkrate = ($whereprize-(($krate_temp - $this->K)/100*$allprize))/$whereprize;

							if($nowkrate <0)
							{
								$nowkrate = 0;
							}
							foreach($tmpuser as $key=>$val)
							{
								//如果有kprize的情况
								if($this->Kprize)
                				{
									$kprize->addprize(array('id'=>$key),$dataarr[$key] * (1-$nowkrate));
								}
								else
								{
									$dataarr[$key]=$dataarr[$key] * $nowkrate;
								}
							}
							//奖金构成文件中添加K值信息
							import('DmsAdmin.DMS.SYS.PrizeData');
				            PrizeData::Kadd($this->parent()->name,'KW',$this->name,$this->byname,$this->K,implode("|",array_keys($tmpuser)));
						}
					}
					else
					{
						//如果没有kwhere则全部正常比例K
	                	foreach($dataarr as $k => $arrval)
	                	{
	                		//如果有kprize的情况
							if($this->Kprize)
                			{	                		
	                			$kprize->addprize(array('id'=>$key),$dataarr[$key] * (1-$nowkrate));
	                		}
	                		else
	                		{
	                			$dataarr[$k] = $arrval * $nowkrate;
	                		}
						}
						//奖金构成文件中添加K值信息
						import('DmsAdmin.DMS.SYS.PrizeData');
			            PrizeData::Kadd($this->parent()->name,'PK',$this->name,$this->byname,$this->K);
					}
                }
			}
			$m_user=M('会员');
			$where="";
			$pname=($this->to=='') ? $this->name : $this->to;
			foreach($dataarr as $userid=>$val)
			{
				$m_user->where($where)->bSave(array(
					'id'=>$userid,
					$pname."+"    =>$val,
					$pname."本日+"=>$val,
					$pname."本周+"=>$val,
					$pname."本月+"=>$val,
					$pname."累计+"=>$val,
				));
				//处理net_place的缓存问题
				foreach(X("net_place") as $netplace){
					//判断缓存数据中是否存在相应的数据
					if(isset($netplace->cache[$userid])){
						if(isset($netplace->cache[$userid][$pname])){
							$netplace->cache[$userid][$pname]+=$val;
						}
						if(isset($netplace->cache[$userid][$pname."本日"])){
							$netplace->cache[$userid][$pname."本日"]+=$val;
						}
						if(isset($netplace->cache[$userid][$pname."本周"])){
							$netplace->cache[$userid][$pname."本周"]+=$val;
						}
						if(isset($netplace->cache[$userid][$pname."本月"])){
							$netplace->cache[$userid][$pname."本月"]+=$val;
						}
						if(isset($netplace->cache[$userid][$pname."累计"])){
							$netplace->cache[$userid][$pname."累计"]+=$val;
						}
					}
				}
			}
			$m_user->where($where)->bUpdate();
			//查询是否存在要计算后立即进行addval的操作
			$addcons=$this->getcon('addval',array('to'=>'','now'=>0,'val'=>'100%','cache'=>true),true);
			//循环addval配置
			foreach($addcons as $addcon)
			{
				//OH..FIND.
		 		if($addcon['now']==1)
		 		{
		 			//找到会员
					foreach($dataarr as $key=>$val)
					{
                        //判断如果没有bankmemo设定时则设置
                        if(!array_key_exists("bankmemo",$addcon)) $addcon["bankmemo"] =  "产生" . $this->byname.'转入$val';
                        //判断没有类型时设置
                        if(!array_key_exists("bankmode",$addcon)) $addcon["bankmode"] = $this->byname;
						$user = M('会员')->find($key);
		 				runadd($user,getnum(abs($val),$addcon["val"]),$addcon["to"],$addcon);
		 			}
		 			//找到节点
		 			$node = X('*@'.$addcon['to']);
		 			if(method_exists($node,'update'))
		 			{
		 				//如果存在缓存更新功能,则更新
		 				$node->update();
			 		}
			 	}
			}
			//清除缓存
			$this->prize_cache = array();
		}
		//获取奖金相关会员表字段
		public function getSelRow($ret=array())
		{
			$ret['奖金锁'] = '奖金锁';
			if($this->lvName!='')
			{
				$ret[$this->lvName]=$this->lvName;
			}
			$ret = array_merge($ret,parent::getSelRow());
			$tops=$this->getcon("top",array('mode'=>'','with'=>''));
			foreach($tops as $con)
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
							$ret[$with.'本日']=$with.'本日';
						break;
						case 'week':
							$ret[$with.'本周']=$with.'本周';
						break;
						case 'month':
							$ret[$with.'本月']=$with.'本月';
						break;
						case 'all':
							$ret[$with.'累计']=$with.'累计';
						break;
					}
				}
			}
			if(property_exists($this,'netName'))
			{
				$net = X('@'.$this->netName);
				$netclass = get_class();
				if($netclass == 'net_rec' ||$netclass == 'net_place')
				{
					$ret[$this->netName.'_上级编号'] = $this->netName.'_上级编号';
					$ret[$this->netName.'_网体数据'] = $this->netName.'_网体数据';
					$ret[$this->netName.'_层数'] = $this->netName.'_层数';
					$ret[$this->netName.'_深度'] = $this->netName.'_深度';
				}
				if($netclass == 'net_place')
				{
					$ret[$this->netName.'_位置'] = $this->netName.'_位置';
				}
			}
			return $ret;
		}
	}
?>