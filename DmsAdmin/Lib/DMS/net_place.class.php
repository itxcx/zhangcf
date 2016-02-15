<?php
	class net_place extends net
	{
       /*
	       如果注册期间选择的位置有人.是否向下滑落
	       开启此属性，在填写安置人和安置位置时，如果安置人的X区有人，
	       则会自动注册到那个区的第一个区直线最下的位置
	       举例:
	                         A
	                    B         C
	                  D   E     F   G
	       
	       如果安置人填写A,位置选择左,那么注册的的人会放在D的左区
	       如果位置选择的是右，那么注册的人会放在F的左区
       */
       public $backFall=false;
       /*
       显示业绩时的分母
       如业绩是按照1000进入的，但是在网络图中只想显示为1,那么此属性设置为1000即可
       */
       public $addMinLayer=0;
       //增加业绩值的最大层数
       public $addMaxLayer=0;
       //是否在注册时显示注册区域的选择
       public $setRegion=true;
       //在推荐第一个人的时候,是否必须是在为自己最左边的区域.
       public $oneInLeft=false;
       //安置人必须在推荐人网络体系之下的判定.
       public $inOwn=false;
       //参考网网络名称(或对象)
       //用于判定$oneInLeft为真时的参考推荐网络,
       public $fromNet="";
       /*
       	设置自动落点的模式。默认为不使用
       	$this->findUp($user);会根据这个人的推荐人，寻找到特定的安置上级，
       	返回一个数组array(安置人编号,位置)
       	如果想知道某一个会员推荐一个人可能会放在哪个位置，则使用
       	$this->findUp($user,true);
       	如果想不使用现有autoMode属性，使用特定的方式来查询
       	$this->findUp($user,true,'排列条件');
       	运行结构举例
       	"min 1,fill"
       	表示先查询自己的小区
       	min首先会转化为调用/DmsAdmin/Lib/net_place/net_place_min.class.php中的静态的run方法
       	run方法中有固定的两个参数,网络跟用户
       	run($net,$user)
       	也可以自己定义其他参数，通过空格来做参数分隔
       	如net_place_min中如下
       	run($net,$user,$layer=0,$whileRegion='')
       	"min 1,fill"中的1就作为$layer参数传入
       	可以自己在net_place目录下创建模块定义自己的排列算法
       	如果第一个算法只是找到了一个人，但是还无法确定位置，
       	则返回一个数组array(上级编号)，这样第二个fill查找，
       	会在第一个min 1的查找基础之上继续进行.
       */
       public $autoMode="fill";
       //奖金表业绩显示开关
	   public $pvFun = true;
		//用户注册时的处理
        //安置人是否是推荐人的推荐网体下
		public $lockrec=false;
		/*
			安置人是否是推荐人的安置网体下
			默认要求开启
		*/
		public $lockplace=true;
		public $inWhere  ="";
		//此属性表示注册时必须要按照从左到右的顺序进行
		public $Sequence = false;
		public $tleMode ='';
		public $tleDay ='';
		public $_cache=array();//奖金计算时的会员缓存记录的数组
		public $_cachenum=0;//奖金计算时调用getups的次数
		public $_maxnum=10;//默认最多调用十次  启动缓存机制保存所有会员  不在查询数据库
		public $decimal=0;//网络图业绩显示时的小数点位数
		//此属性为netplace专用,表示是否要在会员点位上显示业绩表格
		public $userBgxs= true;
		public function getTleMode()
		{
			if($this->tleMode!='')
				return $this->tleMode;
			//找到碰对奖模块
			$bump = X('prize_bump@');
			//如果存在碰对奖模块,同时没有设置结算周期,则取奖金表周期
			if($bump)
			{
				$this->tleMode = ($bump->tleMode == '' ? $bump->parent()->tleMode : $bump->tleMode);
				$this->tleDay   = ($bump->tleDay == '' ? $bump->parent()->tleDay  : $bump->tleDay);
			}
			else
			{
				$this->tleMode = X('tle@')->tleMode;
				$this->tleDay  = X('tle@')->tleDay;
			}
			
			return $this->tleMode;
		}
		public function event_user_reg($user,$sale_reg)
		{
			//创建索引
			// 如果网体支持这个节点
			if($this->useBySale($sale_reg)){
				$this->set_groupnum($user,1,0);
			}
		}
		//注册订单审核成功入口
		public function event_user_verify($user)
		{
			$this->set_groupnum($user,1,1);
			$this->set_Depth($user);
            //此订单审核完成后,处理fulladdval标签,即判定排满标签
            $this->fulladdval($user);
		}
		public function event_sale_delete($sale)
		{
			//得到结算起始日
			$movetime = 0;
			//日结的处理方式
			if($this->getTleMode() === 'd') 
			{
				//取得结算起始日
				$movetime=CONFIG('CAL_START_TIME');
			}
			if($this->getTleMode() === 'w')
			{
				//得到结算日
				$movetime =  CONFIG('CAL_START_TIME');
				//得到当前所属日期所对应的周结算起始日
				$movetime -= (date('N',$movetime)-$tleDay-1 + ((date('N',$movetime) <= $tleDay) ? 7 :0)) * 86400;
			}
			//到款日期,如果大于结算起始日.则表示这个订单的业绩还没有参与计算.可以进行撤单
			if($movetime > 0 && $sale['到款日期'] >= $movetime )
			{
				//得到要被删除订单的会员ID信息,以便删除完记录以后局部汇总
				$idstr=M($this->name.'_业绩')->where(array('saleid'=>$sale['id']))->group('userid')->getField('userid ,userid id2');
				if(isset($idstr))
				$ids = implode(",",$idstr);
				M($this->name.'_业绩')->where(array('saleid'=>$sale['id']))->delete();
				if(isset($ids))
				{
					foreach($this->getBranch() as $key=>$Branch)
					{
						//更新结转业绩
						M()->execute('update dms_会员 a left join (select  userid,sum(val) val from dms_'.$this->name.'_业绩 where userid in ('.$ids.') and pid<>0 and time<' . ($movetime) . ' and region=' . ($key+1) . ' group by userid) b 
						on a.id=b.userid set a.'.$this->name.'_'.$Branch.'区结转业绩=ifnull(b.val,0) where a.id in ('.$ids.')');
						//更新本日业绩
						M()->execute("update dms_会员 a left join (select  userid,sum(val) val from dms_".$this->name."_业绩 where userid in (".$ids.") and pid<>0 and time>=" . ($movetime) . " and region=" . ($key+1) . " group by userid) b 
						on a.id=b.userid set a.".$this->name.'_'.$Branch."区本期业绩=ifnull(b.val,0), a.".$this->name.'_'.$Branch."区本日业绩=ifnull(b.val,0) where a.id in (".$ids.")");						//更新累计业绩
						//累计业绩
						M()->execute("update dms_会员 a left join (select  userid,sum(val) val from dms_".$this->name."_业绩 where userid in (".$ids.") and pid>0 and val>0 and region=" . ($key+1) . " group by userid) b 
						on a.id=b.userid set a.".$this->name.'_'.$Branch."区累计业绩=ifnull(b.val,0) where a.id in (".$ids.")");
					}
				}
			}
		}
        
        private function fulladdval($user)
        {
            //原点或者不在网络中的人不需要做任何处理,因为不会导致上级触发fulladdval
            if($user[$this->name.'_上级编号']=='' || $user[$this->name.'_层数']<=1)
            {
                return;
            }
            //<_fulladdval layer='2'/>
            $cons = $this->getcon('fulladdval',array('layer'=>1,'val'=>1,'to'=>''),true);
            if(!$cons)
            {
                return;
            }
            $upusers=$this->getups($user);
            $Branch =$this->getBranch();
            foreach($upusers as $upuserkey=>$upuser)
            {
                $layer=$upuserkey+1;
                $layerfind=false;
                foreach($cons as $con)
                {
                    if($con['layer']==$layer)
                    {
                        //如果当前层数未做判断
                        if(!$layerfind)
                        {
                            //计算某层应该有的人数
            				if($layer==1)
            					$realnum=count($Branch);
            				else
            					$realnum=pow(count($Branch),$layer);
                            $downuser=$this->getdown($upuser,$layer,$layer,"状态='有效'");
                            if(count($downuser)==$realnum)
                            {
                                $layerfind=true;
                            }
                            else
                            {
                                return;
                            }
                        }
                        //dump($user['编号'].'判断'.$upuser['编号'].'的第'.$layer.'层排满,人数'.count($downuser));
                        if($layerfind)
                        {
                            runadd($upuser,$con['val'],$con['to'],$con);
                        }
                    }
                }
            }
        }
		/**
		 //返回指定会员是否有空缺的线
		 **/
		public function getNullRegion($name)
		{
			if($name=="")
			{
				return '';
			}
			$user=M('会员','dms_')->where(array('编号'=>$name))->find();
			foreach($this->getRegion() as $Region)
			{
				if($user[$this->name.'_'.$Region['name'].'区']=='')
				{
					return $Region['name'];
				}
			}
			return '';
		}
        //判断该网下是否有人
		public function have()
		{
			$where[$this->name."_层数"]=array("gt",0);
			$rs=M('会员','dms_')->lock(true)->where($where)->find();
			if($rs)
			{
				return true;
			}else{
			    return false;
			}
		}

		//取得位置设置数据
		public function getRegion()
		{
			$ret=$this->getcon("region",array("name"=>"","regDisp"=>"true","byname"=>""),true);
			//处理别名
			foreach($ret as &$region)
			{
				$region['byname']=='' && $region['byname']=$region['name'].'区';
			}
			return $ret;
		}
		
		public function getBranch()
		{
			$ret=array();
			foreach($this->getcon("region",array("name"=>"")) as $Region)
			{
				$ret[] = $Region["name"];
			}
			return $ret;
		}
		//网体的位置显示
		public function showregion($regionname)
		{
			foreach($this->getcon("region",array("name"=>"","byname"=>"")) as $Region)
			{
				if($Region["name"]==$regionname){
					$regionname = $Region["byname"]!=""?$Region["byname"]:$regionname."区";
				}
			}
			return $regionname;
		}
		/*
			根据特定逻辑寻找上级
			$user    传入对应的会员
			$thisuser默认情况下，会根据这个人的上级推荐人为起点，寻找安置人。
			         如传入的$user本身也可能会作为安置人，请设置此属性为true
			$exp     默认情况下自动排网算法会以automode为准
		*/
		public function findUp($ifuser,$thisuser=false,$exp='')
		{
			if(!$ifuser)
			{
				throw_exception('findUp参数有误');
			}
			$ifuser['this']=$thisuser;
			$exp == '' && $exp=$this->autoMode;
			foreach(explode(",",$exp) as $set)
			{
				$args  = explode(" ",$set);
				//取得算法模块名
				$className = array_shift($args);
				//类名增加action前缀
				$className = 'net_place_' . $className;
				//对参数增加当前网体和会员信息
				array_unshift($args,$this,$ifuser);
				//应用排序算法类
				import('DmsAdmin.DMS.net_place.'.$className);
				//回调
				$ret=call_user_func_array(array($className,'run'),$args);
				//如果回调是有明确区域的
				if(count($ret)==2)
				{
					return $ret;
				}
				else
				{
					$ifuser = M('会员')->where(array('编号'=>$ret[0]))->find();
					//设置一个this标志，表示下个判定已这个为起点
					$ifuser['this']=true;
				}
			}
			throw_exception('findUp无法找到一个正确的上级');
		}
		//获得自动排列的新上级编号
		public function autoSetUp(&$user)
		{
			//需要测试引用变量是否会导致排网的问题
			//原始点判定
			if(!M('会员','dms_')->lock(true)->where(array($this->name.'_层数'=>1))->find())
			{
				$this->set_index($user);
				return ;
			}
			//用于做判定的会员
			$ret=$this->findUp($user);
			$user[$this->name."_上级编号"]=$ret[0];
			$user[$this->name."_位置"]    =$ret[1];
			$this->set_index($user);
			
			$this->set_groupnum($user,1,2);
			$userdata=array(
				$this->name."_上级编号"=>$ret[0],
				$this->name."_位置"    =>$ret[1],
			);
			M('会员')->where(array('id'=>$user['id']))->save($userdata);
            //处理上级排满事件
            $this->fulladdval($user);
			return;
		}
		
		public function event_valadd(&$user,$val,$option)
		{
			//如果是要重设节点人位置属性
			if(isset($option["set"]) && $option["set"]==1)
			{
				if($user[$this->name."_层数"]<1)
				{
					$this->autoSetUp($user);
				}
			}
			else
			{
				if($val<0)
				{
					throw_exception($this->name.'进入业绩时不应出现负数');
				}
				$this->addpv($user,$val,$option);
			}
		}
		
		public function addpv($user,$val,$option)
		{
			if($val==0)
			{
				return;
			}
			/*业绩表字段说明
			userid业绩所属会员ID.表示这个业绩记录属于某要一个会员
			
			fromid来源编号ID,表示这个业绩是通过谁产生的
			
			saleid订单ID,如果这个业绩是根据一个订单而产生,那么则记录这个订单的id
			
			pid
			如果业绩是向上产生,则订单产生人会产生一个pid=0的记录.并得到这个新纪录的ID,对上级产生业绩时,则用此记录ID作为PID
			假设删除某一个订单的规则,saleid=订单ID,这样这个订单所产生的所有业绩都会删除.
			删除会员的规则,根据(userid=被删除人id and pid=0)得到这个人的原始记录.在根据这些记录的ID进行其他记录的pid查询
			得到关联产生的业绩记录.并进行删除
			如果业绩是进入自己小区,则只会产生一个pid=100000000的个人记录.
			
			$option['notin']="左,1,2|中,1,2"  表示一二层内的所有左区和中区不对上累计业绩  放于<_addval from='[报单金额]' to='管理' now='1' notin='左,1,2|中,1,2'/>
			条件的涵义：'产生业绩会员所在的位置（相对于第一个上级会员）,对上累计的最小层数,对上累计的最大层数'
			使用限制  与addMinLayer与addMaxLayer的属性相冲  若使用此方法  默认这两个属性值即可
			若必须使用addMinLayer与addMaxLayer，则$option['notin']中的层数限制在addMinLayer与addMaxLayer的值之间
						 1
				2		 3		  4
			5	6	7 8	 9	10 11 12 13
			条件效果  每个人业绩都是100  那么 1是100,100,200  2 是 0,0,100 3是0,0,100
			
			*/
			//如果分到小区
			if(isset($option["min"]) && $option["min"]=='true')
			{
				//获取分支名称
				$small='';
				$smallkey=1;
				foreach($this->getBranch() as $key=>$region)
				{
					if($small == '')
					{
						$small = $region;
					}else if($user[$this->name.'_'.$region.'区累计业绩'] < $user[$this->name.'_'.$small.'区累计业绩']){
						$smallkey = $key+1;
						$small = $region;
					}
					$key++;
				}
				$saleid = isset($option['saleid']) ? $option['saleid'] : 0 ;
				//产生自身业绩记录
				$indata = array('time'=>systemTime(),
					  'userid'=>$user['id'],
					  'fromid'=>$user['id'],
					  'val'   =>$val,
					  'saleid'=>$saleid,
					  'pid'   =>100000000,
					  'region'=>$smallkey,
					);
				//插入原始记录得到Pid
				$pid=M($this->name.'_业绩')->add($indata);
				//对小区业绩进行实际增加
				$lstr=$this->name.'_'.$small.'区';
				M('会员')->where(array('id'=>$user['id']))->save(array(
					$lstr.'本期业绩'=>array('exp',$lstr.'本期业绩+'.$val),
					$lstr.'本日业绩'=>array('exp',$lstr.'本日业绩+'.$val),
					$lstr.'累计业绩'=>array('exp',$lstr.'累计业绩+'.$val)
					));				
			}else{
				//取得订单ID
				$saleid = isset($option['saleid']) ? $option['saleid'] : 0 ;
				//产生自身业绩记录
				$indata = array('time'=>systemTime(),
					  'userid'=>$user['id'],
					  'fromid'=>$user['id'],
					  'val'   =>$val,
					  'saleid'=>$saleid,
					  'pid'   =>0,
					);
				//插入原始记录得到Pid
				$pid=M($this->name.'_业绩')->add($indata);
				//不对上累计业绩的条件  生成数组
				$notinwhere=array();
				if(isset($option['notin'])){
					foreach(explode("|",$option['notin']) as $notin){
						$notinwhere[]=explode(',',$notin);
					}
				}
				/*函数内的参数定义 自身业绩表中的id,业绩数值,对上累计的网体数据,业绩来源会员id,订单id,时间,不对上累计的条件,会员的网体位置*/
				$this->addUpPv($pid,$val,$user[$this->name.'_网体数据'],$user['id'],$saleid,systemTime(),$notinwhere,$user[$this->name.'_位置']);
			}
		}
		
		//根据原始记录ID,额度,以及网体数据.更新上级业绩
		private function addUpPv($pid,$val,$netdata,$fromid,$saleid,$time,$notinwheres,$regin)
		{
			if($val==0)
			{
				return;
			}
			//如果没有网体数据.就表示不需要做任何处理.直接返回
			if(!$netdata) return;
			$t_arrs=array_reverse(explode(',',$netdata));
			$sql=array();
			//区域对应位置号的转换数组,用于吧字符区域名转换为从1开始的数字
			$region2id = array();
			foreach($this->getcon("region",array("name"=>"")) as $key=>$Region)
			{
				$region2id[$Region["name"]]=$key+1;
			}
			//不进业绩条件 生成条件字符串
			$notwhere="";
			foreach($notinwheres as $notinwhere){
				if($notwhere!="")
					$notwhere.=" || ";
				$notwhere.="('$regin'='".$notinwhere[0]."' && ($"."key+1)>=".$notinwhere[1];
				if(isset($notinwhere[2])){
					$notwhere.=" && ($"."key+1)<=".$notinwhere[2];
				}
				$notwhere.=")";
			}
			//对上累计业绩
			$adddata=array();
			foreach($t_arrs as $key=>$t_arr)
			{
				//对业绩层数增的判定
				if(($this->addMinLayer == 0 || $key+1 >= $this->addMinLayer) && ($this->addMaxLayer == 0 || $key+1 <= $this->addMaxLayer)) {
				    $data = explode('-',$t_arr);
				    //默认累计
				    $inval=false;
				    //条件中的数值转换  层数
				    if($notwhere){
				    	eval("\$notaddwhere = \"$notwhere\";");
				    	$inval=transform($notaddwhere,array(),array());
				    }
				    //判断是否累计
				    if($inval===false){
				    	$adddata[$data[1]][]=$data[0];
				    	$region = $region2id[$data[1]];
				    	$sql[]  = "($time,$data[0],$fromid,$val,$region,$saleid,$pid)";
				    }
				}
			}
			//如果SQL数组为空.就退出
			if(!$sql) return;
			$sqlstr = implode($sql,',');
			$sqlstr = 'INSERT INTO dms_'.$this->name.'_业绩 (`time`,`userid`,`fromid`,`val`,`region`,`saleid`,`pid`) VALUES '.$sqlstr;
			M()->execute($sqlstr);
			//更新会员表
			$m_user = M('会员');
			foreach($adddata as $key=>$data)
			{
				$lstr=$this->name.'_'.$key.'区';
				$m_user->where(array('id'=>array('in',$data)))->save(array(
					$lstr.'本期业绩'=>array('exp','`'.$lstr.'本期业绩`+'.$val),
					$lstr.'本日业绩'=>array('exp','`'.$lstr.'本日业绩`+'.$val),
					$lstr.'累计业绩'=>array('exp','`'.$lstr.'累计业绩`+'.$val)
					));
			}
		}
		//查询上级并返回ID数组
		public function getupids($user,$minlayer=1,$maxlayer=0,$where=array(),$haveme = false)
		{
			if($user[$this->name.'_网体数据']=='')
			return array();
			$netdata = $user[$this->name.'_网体数据'];
			foreach($this->getBranch() as $Branch)
			{
				$netdata=str_replace("-".$Branch,"",$netdata);//$Branch
			}

			$ret=array_reverse(explode(',',$netdata));
			if($minlayer<1)$minlayer=1;
			$ret=($maxlayer==0)?array_slice($ret,$minlayer-1):array_slice($ret,$minlayer-1,$maxlayer-$minlayer+1);
			if($haveme)
			array_unshift($ret,$user["id"]);
			return $ret;
		}
		public function getup($user,$fromprize=false)
		{
			if(!$user)
			{
				throw_exception('net_rec执行getup失败，参数无效');
			}
			$m_user=D("会员");
			$ret=$m_user->where(array('编号'=>$user[$this->name.'_上级编号']))->find();
			return $ret;
		}
		public function clearup($m_user,$clear=false){
			if($clear){
				if($this->_cache){
					$this->_cachenum=0;
					$this->_cache=array();
				}
				return ;
			}
			$this->_cachenum+=1;
			$fieldsarr=$m_user->get_Property("fields");
			unset($fieldsarr['_autoinc']);
			unset($fieldsarr['_pk']);
			$fields=join(',',$fieldsarr);
			$this->_cache=$m_user->order($this->name.'_层数 DESC')->getField('id as iskey,'.$fields);
		}
		//查询上级,查询用户,数量(代数),条件,是否包括用户本身
		public function getups($user,$minlayer=0,$maxlayer=0,$where='',$fromprize=false,$haveme = false)
		{
			$m_user=D("user");
			if($this->_cachenum==$this->_maxnum){
				//获取全部会员 拉入缓存
				$this->clearup($m_user);
			}else if($this->_cachenum<$this->_maxnum){
				$this->_cachenum+=1;
			}
			//如果取的层数大于0，则不可能存在haveme -1 0 >0
			if($minlayer>0 ) $haveme=false;
			if($user[$this->name.'_网体数据'] == '' && !$haveme)
				return array();
			$ret=array();
			$limit = ($minlayer > 0)? $minlayer-1 : '0';
			//层数判断
			if($maxlayer > 0){
				//设置取的记录长度
				if($minlayer>0)
					$limitLen = $maxlayer-($minlayer-1);
				else
				{
					if($haveme)
						$limitLen = $maxlayer+1;
					else
						$limitLen = $maxlayer;
				}
				$limit .=",".$limitLen;
			}else{
				$limit .=",9999999999";
			}
			
			$findids=$user[$this->name.'_网体数据'];
			$findids= preg_replace( "/-[^,]*/",'',$findids);
			if($haveme)
				$findids = ( $findids=='') ? $user['id'] : $findids.",".$user['id'];
			//根据cache获取会员
			if($this->_cache){
				//将$findids的会员找出并判断
				$findarr=explode(',',$findids);
				$findarr=array_reverse($findarr);
				$thislayer=1;
				foreach($findarr as $findid){
					$finduser=$this->_cache[$findid];
					if($finduser){
						//判断条件
						if(transform($where,$finduser) && $thislayer>=$minlayer && ($thislayer<=$maxlayer || $maxlayer<=0)){
							$ret[]=$finduser;
							$thislayer++;
						}
						unset($finduser);
					}
				}
				return $ret;
			}
			$where=delsign($where);
			if($where == '')
				$where = "id in ($findids)";
			else
				$where = "id in ($findids) and ($where)";
			
			$ret = $m_user->where($where)->order($this->name.'_层数 DESC')->limit($limit)->select();
			if($ret === false)
			{
				throw_exception('net_place执行查下级点位失败,错误信息('.htmlentities($m_user->getDbError(),ENT_COMPAT ,'UTF-8').")");
			}
			if($ret === null)
			{
				$ret = array();
			}
			return $ret;
		}
		//查询下级
		public function getdown($user, $minlayer = 0, $maxlayer = 0, $where = '',$fromprize=false,$haveme = false){
			$m_user=D("user");
			//判断会员是否是第一个会员 如果是第一个会员的话 应该是'id-%' 如果不是的话应该是'%,id-%'
			$sql = $this->name . "_网体数据 like '".($user[$this->name.'_网体数据'] ? $user[$this->name.'_网体数据'].',' : '').$user['id']."-%' or {$this->name}_上级编号='".$user['编号']."'";
	        $where .= " and ($sql)";
	        if($minlayer >= 0) $where .= " and " . $this -> name . "_层数" .  " >=" . ($user[$this -> name . "_层数"] + $minlayer);
	        if($maxlayer > 0)  $where .= " and " . $this -> name . "_层数" . " <="  . ($user[$this -> name . "_层数"] + $maxlayer);
	        $ret = $m_user -> where(trim($where,' and')) -> order($this -> name . '_层数 ASC') -> select();
	        if($ret === false){
	            throw_exception('net_place执行查下级点位失败,sql信息(' . htmlentities($m_user -> getDbError(), ENT_COMPAT , 'UTF-8') . ")");
	        }
	        return $ret;
        }

		//设置团队人数$verify=0为未审核人数,1为已审核人数,2为两类人数
		public function set_groupnum($user,$num,$verify)
		{
			if(!isset($user[$this->name.'_网体数据']) || $user[$this->name.'_网体数据'] == ''  || defined('BULK_INSERT'))
			return;
			$sqlstr="";
			$ids="";
			$idarrs=explode(',',$user[$this->name.'_网体数据']);
			foreach($idarrs as $idarr)
			{
				preg_match('/(.*)-.*/',$idarr,$idstr);
				$ids.=$idstr[1].",";
			}
			$ids=substr($ids,0,-1);
			if($verify==1||$verify==2){
				$sqlstr="update dms_会员 set ".$this->name."_团队人数  =".$this->name."_团队人数  + ".$num." where id in(".$ids.")";
				M()->execute($sqlstr);
			}
			if($verify==0||$verify==2){
				$sqlstr="update dms_会员 set ".$this->name."_团队总人数=".$this->name."_团队总人数 + ".$num." where id in(".$ids.")";
				M()->execute($sqlstr);
			}
		}
		
		//更新管理层深
		public function set_Depth(&$user){
			$thislayer=$user[$this->name.'_层数'];
			
			if($user[$this->name."_网体数据"] != ''){
				$datas = explode(',',$user[$this->name."_网体数据"]);
				foreach($datas as $data){
					$res = explode('-',$data);
					$sql="update dms_会员 set {$this->name}_{$res[1]}区层深={$thislayer}-{$this->name}_层数 where {$this->name}_{$res[1]}区层深<{$thislayer}-{$this->name}_层数 and id = {$res[0]}";
					M()->execute($sql);
				}
			}
		}

		//创建索引数据
		public function set_index(&$user,$update=true)
		{
			$model=M('会员','dms_');
			if($user[$this->name.'_上级编号']=='')
			{
				$user[$this->name.'_层数'] = 1;
				$user[$this->name.'_网体数据'] = '';
			}
			else
			{
				$upuser = M('会员')->where(array('编号'=>$user[$this->name.'_上级编号']))->find();;
				$user[$this->name.'_网体数据'] = trim($upuser[$this->name.'_网体数据'].",".$upuser["id"].'-'.$user[$this->name.'_位置'],',');
				$user[$this->name.'_层数'] = $upuser[$this->name.'_层数'] + 1;
				//更新上级某区为自己编号
				$data=array();
				$data[$this->name."_".$user[$this->name.'_位置'].'区'] = $user['编号'];
				$model->where("编号='".$user[$this->name.'_上级编号']."'")->save($data);
			}
			$model->where(array('id'=>$user['id']))->save(
				array($this->name.'_网体数据'=>$user[$this->name.'_网体数据'],
				      $this->name.'_层数'=>$user[$this->name.'_层数']
			));
		}
		
		//判断指定区是否存在人,如果指定
		public function nothaveRegion($username,$regionName)
		{
			$user=M('会员','dms_')->lock(true)->where(array("编号"=>$username))->find();
			if(isset($user[$this->name."_".$regionName."区"]) && !$user[$this->name."_".$regionName."区"])
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		//判断这个人所有区是否已经排满
		public function haveAllRegion($userid)
		{
            $user=M('会员','dms_')->lock(true)->where(array('编号'=>$userid))->find();
			$ableregion=array();
			foreach($this->getRegion() as $region)
			{
				if($user[$this->name.'_'.$region['name'].'区'] =='')
				{
					$ableregion[]=$region['name'];
				}
			}
			if(empty($ableregion))
			{
			 return false;
			}else{
			return true;
			}
		}
		//最左区判定
		public function isInLeft($placename,$recname,$regionName)
		{
			$rec=M('会员','dms_')->lock(true)->where("编号='".$recname."'")->find();
			$recid=$rec['id'];
			$Branch=$this->getBranch();
			$recnum=$rec[$this->fromNet . "_推荐人数"];
			if($recnum >0){
			  return true;
			}
			//转换用于输入编号大小写都识别
			if(strtolower($recname)==strtolower($placename)){
			  $recplace=M('会员','dms_')->lock(true)->where($this->name."_上级编号='".$recname."'")->find();
			  if($recplace){
				  return false;
			  }elseif($regionName != $Branch[0]){
			      return false;
			  }else{
				  return true;
				}
			}
			$placenet=M('会员','dms_')->lock(true)->where("编号='".$placename."'")->getField($this->name.'_网体数据');
			if($placenet=="")
			{
				return true;
			}else{
				$branch= $this->getBranch();
				$first=$recid."-".$branch[0];
				$netarr=explode(",",$placenet);
				if(in_array($first,$netarr)){
				    return true;
				}else{
					return false;
					}
			}
		}
		//判定推荐人必须为自己，如果不为自己则返回FALSE
		public function inOwnNet($placename,$recname)
		{
			//得到推荐人编号
			$recuser   = M('会员','dms_')->lock(true)->where("编号='".$recname."'")->find();
			$placeuser = M('会员','dms_')->lock(true)->where("编号='".$placename."'")->find();

			if($recuser['id'] == $placeuser['id'])
			{
				return true;
			}
			$netdata=M('会员','dms_')->lock(true)->where(array('id'=>$placeuser['id']))->getField($this->name.'_网体数据');
			//推荐人和安置人不符，并且安置人为原始点
			if($netdata=='')
			{
				return false;
			}
			foreach(explode(',',$netdata) as $data)
			{
				$data2 = explode('-',$data);
				if( $data2[0]== $recuser['id'])
				{
					return true;
				}
			}
			return false;
		}

		//判断安置人是否必须在推荐人的安置网体下
		public function placeLock($netname,$recname){
			$placenet=M('会员','dms_')->lock(true)->where(array("编号"=>$netname))->getField($this->name."_网体数据");
			$recid=M('会员','dms_')->lock(true)->where(array("编号"=>$recname))->getField("id");
			if(!$placenet) return true;
			$placearr=explode(",",$placenet);
			$newplacearr = array();
			foreach($placearr as $k=>$v){
				$vs = explode('-',$v);
				$newplacearr[] = $vs[0];
			}
			if(in_array($recid,$newplacearr) || $netname==$recname){
				return true;
			}else{
				return false;
			}
		}
		//会员删除事件
		public function event_userdelete($user)
		{
			//删除个人业绩数据
			M($this->name.'_业绩')->where(array('userid'=>$user['id']))->delete();
			//更新上级信息
			if($user[$this->name."_上级编号"]!='' && $user[$this->name."_位置"]!='')
			{
				M('会员','dms_')->where(array("编号"=>$user[$this->name."_上级编号"]))->save(array($this->name."_".$user[$this->name."_位置"]."区"=>''));
				if($user['状态']=='有效')
				{
					//更新总数和已审核
					$this->set_groupnum($user,-1,2);
				}
				else
				{
					//只更新总数
					$this->set_groupnum($user,-1,0);
				}
			}
		}
		public function event_scal()
		{
			$rows=$this->getSelRow();
			foreach(X('prize_bump') as $prize_bump)
			{
				if($prize_bump->netName === $this->name)
				{
					$rows=$prize_bump->getSelRow($rows);
				}
			}
	        //定义要查询的字段
	        foreach($this -> getBranch() as $key => $Branch)
	        {
	        	$lstr=$this->name.'_'.$Branch.'区';
	        	$rows[$lstr.'本期业绩'] = 1;
	        	$rows[$lstr.'本日业绩'] = 1;
	        	$rows[$lstr.'结转业绩'] = 1;
	        	$rows[$lstr.'累计业绩'] = 1;
	        }
	        $this->cache = M('会员')->lock(true)->getField('id keyid,id,编号,'.implode(array_keys($rows),','));
		}
		public function event_cal($tle,$caltime)
		{
	        $rows=$this->getSelRow();
			foreach(X('prize_bump') as $prize_bump)
			{
				if($prize_bump->netName === $this->name)
				{
					$rows=$prize_bump->getSelRow($rows);
				}
			}
			if($this->getTleMode() == 's')
			{
				$user_m=M('会员');
				//在秒日混合计算情况下，在日结时取得当前业绩缓存
		        foreach($this -> getBranch() as $key => $Branch)
		        {
		        	$lstr=$this->name.'_'.$Branch.'区';
		        	//本期业绩连表
		        	$user_m->join('(select userid,sum(val) '.$lstr.'本日业绩 from dms_'.$this->name.'_业绩 where time>='.$caltime.' and time<'.$caltime.'+86400 and region='.($key+1).' and pid>0 group by userid) new'.$key.' on dms_会员.id = new'.$key.'.userid');
		        	//结转业绩连表
					$user_m->join('(select userid,sum(val) '.$lstr.'结转业绩 from dms_'.$this->name.'_业绩 where time<'.$caltime.' and region='.($key+1).' and pid<>0 group by userid) jie'.$key.' on dms_会员.id = jie'.$key.'.userid');
					//累计业绩连表
					$user_m->join('(select userid,sum(val) '.$lstr.'累计业绩 from dms_'.$this->name.'_业绩 where time<'.$caltime.'+86400 and region='.($key+1).' and pid>0 group by userid) sum'.$key.' on dms_会员.id = sum'.$key.'.userid');
		        	$rows['new'.$key.'.'.$lstr.'本日业绩'] = 1;
		        	$rows['jie'.$key.'.'.$lstr.'结转业绩'] = 1;
		        	$rows['sum'.$key.'.'.$lstr.'累计业绩'] = 1;
		        }
				$this->cache = $user_m->lock(true)->getField('id keyid,id,编号,'.implode(array_keys($rows),','));
			}
	        //日结或者周结,把结算日之后的
	        if($this->getTleMode() == 'd' || ($this->getTleMode() == 'w' && date('N', $caltime) == (int)$this -> tleDay)){
		        foreach($this -> getBranch() as $key => $Branch)
		        {
		        	$lstr=$this->name.'_'.$Branch.'区';
		        	$rows[$lstr.'本期业绩'] = 1;
		        	$rows[$lstr.'本期业绩 '.$lstr.'本日业绩'] = 1;
		        	$rows[$lstr.'结转业绩'] = 1;
		        	$rows[$lstr.'累计业绩'] = 1;
		        }
	        	$this->cache = M('会员')->lock(true)->getField('id keyid,id,编号,'.implode(array_keys($rows),','));
	        	foreach($this -> getBranch() as $key => $Branch)
	        	{
	        		$vals = M($this->name.'_业绩')->lock(true)->where('pid<>0 and time>='.($caltime+86400).' and region='.($key+1))->group('userid')->field('userid,sum(val) val')->select();
	        		if($vals)
	        		foreach($vals as $val)
	        		{
	        			$this->cache[$val['userid']][$this->name.'_'.$Branch.'区本期业绩'] -= $val['val'];
	        			$this->cache[$val['userid']][$this->name.'_'.$Branch.'区本日业绩'] -= $val['val'];
	        			$this->cache[$val['userid']][$this->name.'_'.$Branch.'区累计业绩'] -= $val['val'];
	        		}
	        	}
	        }
	        elseif($this->getTleMode() == 'w')
	        {
			    foreach($this -> getBranch() as $key => $Branch)
			    {
			    	$lstr = '0 '.$this->name.'_'.$Branch.'区';
			    	$rows[$lstr.'本期业绩'] = 1;
			    	$rows[$lstr.'本日业绩'] = 1;
			    	$rows[$lstr.'结转业绩'] = 1;
			    	$rows[$lstr.'累计业绩'] = 1;
			    }
			    $this->cache = M('会员')->lock(true)->getField('id keyid,id,编号,'.implode(array_keys($rows),','));
	        }
		}
		//结算完成,清空会员表中的业绩,业绩表业绩处理
		public function event_caldayover($caltime)
		{
			//根据最后一天的结算日期
			if($this->getTleMode() != 's')
			{
		        foreach($this -> getBranch() as $key => $Branch){
		        	//结转等于结转+当天业绩加当天扣除
		            M() -> execute('update dms_会员 a inner join (select  userid,sum(val) val from dms_' . $this -> name . '_业绩 where pid<>0 and time>=' . ($caltime ) . ' and time<' . ($caltime + 86400) .' and region=' . ($key + 1) . ' group by userid) b
						on a.id=b.userid set a.' . $this -> name . '_' . $Branch . '区结转业绩=a.' . $this -> name . '_' . $Branch . '区结转业绩+ifnull(b.val,0) where ifnull(b.val,0)<>0');
					//更新本期业绩
		            M() -> execute("update dms_会员 a left join (select  userid,sum(val) val from dms_" . $this -> name . "_业绩 where pid<>0 and time>=" . ($caltime + 86400) . " and region=" . ($key + 1) . " group by userid) b
						on a.id=b.userid set a." . $this -> name . '_' . $Branch . "区本期业绩=ifnull(b.val,0), a." . $this -> name . '_' . $Branch . "区本日业绩=ifnull(b.val,0) where a." . $this -> name . '_' . $Branch . "区本期业绩>0 and a." . $this -> name . '_' . $Branch . "区本期业绩<>ifnull(b.val,0) ");
	            }
	        }
	        /*if($this->getTleMode() == 'w' && date('N', $caltime) == (int)$this -> tleDay)
	        {
	        	foreach($this -> getBranch() as $key => $Branch){
		        	//结转等于结转+当周新增-当周扣除
		            M() -> execute('update dms_会员 a inner join (select  userid,sum(val) val from dms_' . $this -> name . '_业绩 where pid<>0 and time>=' . ($caltime - 86400*6 ) . ' and time<' . ($caltime + 86400) .' and region=' . ($key + 1) . ' group by userid) b
						on a.id=b.userid set a.' . $this -> name . '_' . $Branch . '区结转业绩=a.' . $this -> name . '_' . $Branch . '区结转业绩+ifnull(b.val,0) where ifnull(b.val,0)<>0');
					//更新本期业绩
		            M() -> execute("update dms_会员 a left join (select  userid,sum(val) val from dms_" . $this -> name . "_业绩 where pid<>0 and time>=" . ($caltime + 86400) . " and region=" . ($key + 1) . " group by userid) b
						on a.id=b.userid set a." . $this -> name . '_' . $Branch . "区本期业绩=ifnull(b.val,0), a." . $this -> name . '_' . $Branch . "区本日业绩=ifnull(b.val,0) where a." . $this -> name . '_' . $Branch . "区本期业绩>0 and a." . $this -> name . '_' . $Branch . "区本期业绩<>ifnull(b.val,0) ");
				}
	        }*/
	    }
	    //秒结算跨日
	    public function event_diffTime($time)
	    {
	    	if($this->getTleMode() === 's')
	    	{
	    		$this->repairPv($time);
	    	}
	    }
		public function repairPv($datetime)
		{
			$rowstr='';
			//预先加载现有缓存
			foreach($this -> getBranch() as $key => $Branch)
			{
				$rowstr.=','.$this->name.'_'.$Branch.'区本期业绩';
				$rowstr.=','.$this->name.'_'.$Branch.'区本日业绩';
				$rowstr.=','.$this->name.'_'.$Branch.'区结转业绩';
				$rowstr.=','.$this->name.'_'.$Branch.'区累计业绩';
			}			
			$yjs = M('会员')->lock(true)->getField('id'.$rowstr);
			foreach($this->getBranch() as $Branchkey=>$Branch)
			{
				//对结转业绩的处理
				$upsql='';$ids='';
				$vals = M($this->name.'_业绩')->lock(true)->where('pid<>0 and time<'.$datetime.' and region='.($Branchkey+1))->group('userid')->getField('userid,sum(val) val');
				if($yjs)
				foreach($yjs as $key=>&$yj)
    			{
		    		if($yj[$this->name.'_'.$Branch.'区结转业绩']!=0 && !isset($vals[$key]))
		    		{
		    			$ids.=','.$key;
		    			$upsql.=' WHEN '.$key.' THEN 0';
		    		}
				}
				if($vals)
		    	foreach($vals as $key=>$val)
		    	{
		    		if(isset($yjs[$key]) && $yjs[$key][$this->name.'_'.$Branch.'区结转业绩']!=$val)
		    		{
		    			$upsql.=' WHEN '.$key.' THEN '.$val;
		    			$ids.=','.$key;
		    		}
		    	}
		    	if($ids)
		    	{
		    		M()->execute('update dms_会员 set '.$this->name.'_'.$Branch.'区结转业绩 = case id '.$upsql.' END where id in ('.trim($ids,',').')');
		    	}
		    	//处理新增业绩===========================
				$upsql='';$ids='';
				$vals = M($this->name.'_业绩')->lock(true)->where("pid<>0 and time>=" . ($datetime) . " and region=" . ($Branchkey+1))->group('userid')->getField('userid,sum(val) val');
				if($yjs)
				foreach($yjs as $key=>&$yj)
    			{
		    		if(($yj[$this->name.'_'.$Branch.'区本期业绩']!=0 || $yj[$this->name.'_'.$Branch.'区本日业绩']!=0) && !isset($vals[$key]))
		    		{
		    			$ids.=','.$key;
		    			$upsql.=' WHEN '.$key.' THEN 0';
		    		}
				}
				if($vals){
			    	foreach($vals as $key=>$val)
			    	{
			    		if(isset($yjs[$key]) && ($yjs[$key][$this->name.'_'.$Branch.'区本期业绩']!=$val || $yjs[$key][$this->name.'_'.$Branch.'区本日业绩']!=$val))
			    		{
			    			$upsql.=' WHEN '.$key.' THEN '.$val;
			    			$ids.=','.$key;
			    		}
			    	}
		    	}
		    	if($ids)
		    	{
		    		
		    		//如果是非秒结算业绩.可以更新到本期,否则只能更新本日业绩
		    		if($this->getTleMode()!='s')
		    		M()->execute('update dms_会员 set '.$this->name.'_'.$Branch.'区本期业绩 = case id '.$upsql.' END where id in ('.trim($ids,',').')');
		    		M()->execute('update dms_会员 set '.$this->name.'_'.$Branch.'区本日业绩 = case id '.$upsql.' END where id in ('.trim($ids,',').')');
		    	}
				//更新累计业绩
				$upsql='';$ids='';
				$vals = M($this->name.'_业绩')->lock(true)->where("pid>0 and val>0 and region=" . ($Branchkey+1))->group('userid')->getField('userid,sum(val) val');
				if($yjs)
				foreach($yjs as $key=>&$yj)
    			{
		    		if(($yj[$this->name.'_'.$Branch.'区累计业绩']!=0 || $yj[$this->name.'_'.$Branch.'区累计业绩']!=0) && !isset($vals[$key]))
		    		{
		    			$ids.=','.$key;
		    			$upsql.=' WHEN '.$key.' THEN 0';
		    		}
				}
				if($vals)
		    	foreach($vals as $key=>$val)
		    	{
		    		if(isset($yjs[$key]) && ($yjs[$key][$this->name.'_'.$Branch.'区累计业绩']!=$val || $yjs[$key][$this->name.'_'.$Branch.'区累计业绩']!=$val))
		    		{
		    			$upsql.=' WHEN '.$key.' THEN '.$val;
		    			$ids.=','.$key;
		    		}
		    	}
		    	if($ids)
		    	{
		    		
		    		M()->execute('update dms_会员 set '.$this->name.'_'.$Branch.'区累计业绩 = case id '.$upsql.' END where id in ('.trim($ids,',').')');
		    	}
			}
		}
		public function event_sysclear()
		{
			M()->execute('truncate table `dms_'.$this->name.'_业绩`');
		}
		public function lockSequence($qu,$userbh)
		{
			//打开$userbh会员，循环所有区，如果指定的这个$qu前边有空位返回false
			$branch  = $this->getBranch();
        	//查询出所有的会员的管理上级编号为$userbh的记录
			$results=M('会员','dms_')->lock(true)->where(array($this->name.'_上级编号'=>$userbh))->select();
			if($results){
				foreach($results as $v){
					$qus[] = $v[$this->name."_位置"];
				}
				foreach($branch as $perqu){
					if(in_array($perqu,$qus)){
						if($perqu==$qu){
							return true;break;
						}
						continue;
					}else{
						//判断返回的是否和此区域一致
						if($perqu==$qu){
							return true;break; 
						}else{
						    return false; break;
						}
					}
				}
			}else{
				//判断返回的是否是第一个区域
				if($branch[0]!=$qu){
			    	return false;
				}else{
			    	return true;
			  	}
			}
		}
		//移动网体
		public function move($user,$newup,$region)
		{
			//选出第一个会员
			$first=M('会员')->where(array("状态"=>'有效'))->order("id asc")->field('id,编号')->find();
			if($first['编号']==$user['编号']){
				return "第一人不能进行移动";
			}
			//如果新要求与目前网络体系一致则直接返回
			if($user[$this->name.'_上级编号']==$newup['编号'] && $user[$this->name.'_位置']==$region)
			{
				return true;
			}
			//不能为自己判断
			if($user['编号']==$newup['编号'])
			{
				return $this->name."网新上级编号不能为自己";
			}
			//对新上级是否存在做判断
			if(!$newup)
			{
				return $this->name."移动网络时,新上级信息不存在";
			}
			//对$region参数的有效性进行验证
			if(!in_array($region,$this->getBranch()))
			{
				return $this->name."位置信息非法";
			}
			//对上级指定的区是否存在做判断
			if($newup[$this->name."_".$region."区"]!='')
			{
				return $newup['编号']."的".$region.'区已经有人';
			}
			//新上级不能在自己网络体系之下
			if(strpos($newup[$this->name.'_网体数据'],$user[$this->name.'_网体数据'])!==false)
			{
				return "新上级不能在其网络体系之下";
			}
			//开始更新团队人数
			$oldids = explode(',',preg_replace( "/-[^,]+/",'', $user[$this->name.'_网体数据']));
			$newids = explode(',',preg_replace( "/-[^,]+/",'',$newup[$this->name.'_网体数据']));
			//因为团队人数变更.也会对新上级本身产生影响,所以需要增加
			$newids[] = $newup['id'];
			$allnum     = $user[$this->name.'_团队总人数'] + 1;
			$comfrimnum = $user[$this->name.'_团队人数'] + ( $user['状态']='有效' ? 1 : 0 );
			//减团队人数
			M('会员')->where(array('id'=>array('in',$oldids)))->setDec($this->name.'_团队总人数',$allnum);
			M('会员')->where(array('id'=>array('in',$oldids)))->setDec($this->name.'_团队人数'  ,$comfrimnum);
			//增加团队人数
			M('会员')->where(array('id'=>array('in',$newids)))->setInc($this->name.'_团队总人数',$allnum);
			M('会员')->where(array('id'=>array('in',$newids)))->setInc($this->name.'_团队人数'  ,$comfrimnum);
			//业绩处理==============================================================
			$movetime=0;
			//日结的处理方式
			if($this->getTleMode() == 'd') 
			{
				//取得结算起始日
				$movetime=CONFIG('CAL_START_TIME');
			}
			if($this->getTleMode() == 'w')
			{
				//得到结算日
				$movetime =  CONFIG('CAL_START_TIME');
				//得到当前所属日期所对应的周结算起始日
				$movetime -= (date('N',$movetime)-$tleDay-1 + ((date('N',$movetime) <= $tleDay) ? 7 :0)) * 86400;
			}
			if($movetime>0)
			{
				$ids = M($this->name.'_业绩')->where('pid=0 and `time` >='.$movetime)->getField('id,id id2');
				if($ids)
				M()->execute('delete from dms_'.$this->name.'_业绩 where pid in ('.implode(",",$ids).')');
			}
			//=======================================================================
			//清空原上级的对应区的编号
			M('会员')->where(array("编号"=>$user[$this->name."_上级编号"]))->save(array($this->name.'_'.$user[$this->name.'_位置'].'区' =>''));
			//更新新上级对应区编号
			M('会员')->where(array("编号"=>$newup['编号']))->save(array($this->name.'_'.$region.'区' =>$user['编号']));
			//对移网会员本身的上级信息更新
			M('会员')->where(array("编号"=>$user['编号']))->save(array( $this->name . '_上级编号' => $newup['编号'],$this->name .'_位置' => $region));
			//生成新网体数据
			$newnet = trim($newup[$this->name . '_网体数据'].','.$newup['id'].'-'.$region,',');
			//得到新老层数差
			$layerdiff = $newup[$this->name.'_层数']+1 - $user[$this->name.'_层数'];
			//对下级和自身层数进行更新
			$where = "(".$this->name . "_网体数据 like '".($user[$this->name.'_网体数据'] ? $user[$this->name.'_网体数据'].',' : '').$user['id']."-%' or {$this->name}_上级编号='".$user['编号']."')";
	        $where .= " or (编号='{$user['编号']}')";
			M('会员')->where($where)->setInc($this->name.'_层数',$layerdiff);
			//对老网体数据进行更新
			$down1=0;//是否移动第一个人的第一层会员
			foreach($this->getRegion() as $Region){
				$Regionname=$first['id'].'-'.$Region['name'];//其中$first['id']直接用的move下面第一行的获取（即得到公司网体的第一个会员）
				if($user[$this->name.'_网体数据']==$Regionname){
					//首先处理这个特殊会员	
					M()->execute("update `dms_会员` set {$this->name}_网体数据='{$newnet}' where 编号='".$user['编号']."'");
					$weizhi=strlen($Regionname)+1;//得到逗号首次出现的位置
					//得到移动的会员网体下数据
					$downusers  = M('会员')->where("find_in_set('".$user[$this->name.'_网体数据']."',".$this->name."_网体数据)")->getField("编号,".$this->name."_网体数据");
					//进行循环
					foreach($downusers as $downbianhao=>$downuser){
						//为合成会员网体做准备,得到后半部分网体数据
						$downuserh=mb_substr($downuser,$weizhi);
						//对移动过的会员合成新网体
						$downusernewnet=$newnet.','.$downuserh;
						//对移动过的会员网体数据进行更新
						M()->execute("update `dms_会员` set {$this->name}_网体数据='{$downusernewnet}' where 编号='".$downbianhao."'");
					}
					$down1=1;//判断有没有执行if里面的内容，如果执行了，则下面的那一句if就不会再执行了，如果始终没有该变量那么下面的就再执行！
					unset($downusers);
					break;
				}
			}
			if($down1==0){
				M()->execute("update `dms_会员` set ".$this->name."_网体数据=replace(".$this->name."_网体数据,'".$user[$this->name.'_网体数据']."','".$newnet."')");
			}
			//对新增业绩在新网体中进行还原
			if($movetime>0 && $ids)
			{
				//找到所有需要增加业绩的记录,并附带网体数据
				$adds = M()->table('dms_'.$this->name.'_业绩 a')->join('dms_会员 b on b.id=a.userid')->field('b.'.$this->name.'_网体数据 netdata,b.'.$this->name.'_位置,b.id uid,a.val,a.id,a.saleid,a.time')->where('pid=0 and `time` >='.$movetime)->select();
				foreach($adds as $add)
				{
					$notinstr='';
					$notinwhere=array();
					//获取不进业绩的条件节点
					$salename=M("报单")->where(array("id"=>$add['saleid']))->getfield("报单类别");
					$cons=X("@".$salename)->getcon("addval",array("from"=>"","to"=>"","now"=>1,'notin'=>''),true);
					foreach($cons as $con){
						if(!isset($con["set"]) && $con["to"]==$this->name && $con['notin']!=''){
							$notinstr=$con['notin'];
						}
					}
					//不对上累计业绩的条件  生成数组
					if($notinstr){
						foreach(explode("|",$notinstr) as $notin){
							$notinwhere[]=explode(',',$notin);
						}
					}
					$this->addUpPv($add['id'],$add['val'],$add['netdata'],$add['uid'],$add['saleid'],$add['time'],$notinwhere,$add[$this->name.'_位置']);
				}
				//对所有人的业绩进行同步更新处理
				//根据最后一天的结算日期
				foreach($this->getBranch() as $key=>$Branch)
				{
					//更新结转业绩
					M()->execute('update dms_会员 a left join (select  userid,sum(val) val from dms_'.$this->name.'_业绩 where pid<>0 and time<' . ($movetime) . ' and region=' . ($key+1) . ' group by userid) b 
					on a.id=b.userid set a.'.$this->name.'_'.$Branch.'区结转业绩=ifnull(b.val,0)');
					//更新本日业绩
					M()->execute("update dms_会员 a left join (select  userid,sum(val) val from dms_".$this->name."_业绩 where pid<>0 and time>=" . ($movetime) . " and region=" . ($key+1) . " group by userid) b 
					on a.id=b.userid set a.".$this->name.'_'.$Branch."区本期业绩=ifnull(b.val,0), a.".$this->name.'_'.$Branch."区本日业绩=ifnull(b.val,0)");
					//更新累计业绩
					M()->execute("update dms_会员 a left join (select  userid,sum(val) val from dms_".$this->name."_业绩 where pid>0 and val>0 and region=" . ($key+1) . " group by userid) b 
					on a.id=b.userid set a.".$this->name.'_'.$Branch."区累计业绩=ifnull(b.val,0)");
				}
			}
			foreach(X("fun_placenum") as $funrec){
				$funrec->event_netmove($this,$user);
			}
			//保存网体修改日志
			$oldupuser=$user[$this->name.'_上级编号'];
			$oldregion=$user[$this->name.'_位置'];
			$data = array();
			$datalog['user_id']   = $user['id'];
			$datalog['user_name'] = $user['姓名'];
			$datalog['user_bh']   = $user['编号'];
			$datalog['admin_id']  = $_SESSION[ C('RBAC_ADMIN_AUTH_KEY')];
			$datalog['ip']        = get_client_ip();
			$datalog['content']   = '移动'.$user['编号'].$this->name.'网从'.$oldupuser.$oldregion.'到'.$newup['编号'].$region;
			$datalog['create_time']=time();			
			import("ORG.Net.IpLocation");
			$IpLocation				= new IpLocation("qqwry.dat");
			$loc					= $IpLocation->getlocation();
			$country				= mb_convert_encoding ($loc['country'] , 'UTF-8','GBK' );
			$area					= mb_convert_encoding ($loc['area'] , 'UTF-8','GBK' );
			$datalog['address']		= $country.$area;
			M('log_user')->add($datalog);
			//全剧终
			return true;
		}
		/*
			管理网业绩逆向修正，根据会员表中的业绩数据，重建业绩表记录
			警告：此操作会清空现有业绩表记录
		*/
		public function repairTable()
		{
			M()->execute('truncate table `dms_'.$this->name.'_业绩`');
			$users  = M('会员')->select();
			$branch = $this->getBranch();
			$model  = M($this->name.'_业绩');
			foreach($branch as $region)
			{
				if(M('会员')->where($this->name.'_'.$region.'区本期业绩>0')->find())
				{
					throw_exception('目前有会员'.$this->name.'_'.$region.'区本期业绩大于0,请将本期业绩更新到结转后在执行，
					参考SQL:update dms_会员 set '.$this->name.'_'.$region.'区结转业绩='.$this->name.'_'.$region.'区结转业绩+'.$this->name.'_'.$region.'区本期业绩,'.$this->name.'_'.$region.'区本期业绩=0');
				}
			}
			foreach($users as $user)
			{
				foreach($branch as $key=>$region)
				{
					if($user[$this->name.'_'.$region.'区累计业绩']>0)
					{
						$indata = array(
						'time'  => systemTime()-86400,
						'userid'=> $user['id'],
						'fromid'=> $user['id'],
						'val'   => $user[$this->name.'_'.$region.'区累计业绩'],
						'pid'   => 1,
						'saleid'=> 0,
						'region'=>$key+1,
						);
						$model->badd($indata);
					}
					if($user[$this->name.'_'.$region.'区累计业绩'] != $user[$this->name.'_'.$region.'区结转业绩'])
					{
						$indata = array(
						'time'  => systemTime()-86400,
						'userid'=> $user['id'],
						'fromid'=> $user['id'],
						'val'   => -($user[$this->name.'_'.$region.'区累计业绩'] - $user[$this->name.'_'.$region.'区结转业绩']),
						'pid'   => -1,
						'saleid'=> 0,
						'region'=>$key+1,
						);
						$model->badd($indata);
					}
				}
			}
			$model->bUpdate();
		}
		//修复网体数据
		public function repair()
		{
	   	    ini_set('memory_limit','5000M');
	   	    set_time_limit(1000);
	   	    //对编号可能存在的大小写不一致的情况做修正
	   	    M()->execute("update dms_会员 a,dms_会员 b set a.".$this->name."_上级编号=b.编号 where a.".$this->name."_上级编号=b.编号");
	   	    //清空现有网体数据信息
	   	    M()->execute("update dms_会员 set {$this->name}_网体数据=''");
	   	    M()->execute("update dms_会员 set {$this->name}_层数=1 where {$this->name}_上级编号=''");
	   	    //取得需要用的信息表
	   	    $userdata=M('会员')->getField("编号,{$this->name}_上级编号 上级编号,id,{$this->name}_位置 位置,状态,0 num,0 allnum");
	   	    //取得要处理会员的信息表
	   	    $upusers  = M('会员')->getField('id,编号');
	   	    //更新网体数据临时SQL
	   	    $netsql   = '';
	   	    //更新层数临时SQL
	   	    $layersql = '';
	   	    foreach($upusers as $id=>$name)
	   	    {
	   	    	$user = $userdata[$name];
	   	   		if($user['上级编号'] != '')
	   	   		{
	   	   			//定义网体数据
	   	   			$netstr = '';
	   	   			//定义层数
	   	   			$layer  = 1;
	   	   			//定义网络
	   	   			$region = $user['位置'];
	   	   			//设置自己的上级
	   	   			$thisup = $user['上级编号'];
	   	   			$userdata[$thisup][$region."区"]=$name;
	   	   			//对上级进行链性表遍历,同时要防止死循环(存在互为上级的情况)
	   	   			while($thisup!='' && $layer<1000)
	   	   			{
	   	   				$layer++;
	   	   				//合成部分网体数据
	   	   				
	   	   				$netstr=$userdata[$thisup]['id'].'-'.$region.','.$netstr;
	   	   				//增加团队总人数
	   	   				$userdata[$thisup]['num']++;
	   	   				//增加有效人数
	   	   				if($user['状态']=='有效')$userdata[$thisup]['allnum']++;
	   	   				//向上追链性表
	   	   				$region = $userdata[$thisup]['位置'];
	   	   				$thisup = $userdata[$thisup]['上级编号'];
	   	   			}
	   	   			$netstr = trim($netstr,',');
	   	   			//判断达到1000层,认为出现死循环情况
	   	   			if($layer >= 1000)
	   	   			{
						throw_exception("出现了互为上级的情况,".$netstr);
	   	   			}
	   	   			
	   	   			M('会员')->bsave(array(
	   	   				'id'=>$user['id'],
	   	   				$this->name.'_网体数据'=>$netstr,
	   	   				$this->name.'_层数'=>$layer,
	   	   				)
	   	   			);
				}
		    }
		    M('会员')->bupdate();
	   	   	//更新每个人的安置人数信息 以及安置人的区位会员
	   	   	foreach($userdata as $user)
	   	   	{
	   	   		$data=array(
	   	   			'id'=>$user['id'],
	   	   			$this->name.'_团队人数'  =>$user['num'],
	   	   			$this->name.'_团队总人数'=>$user['allnum']
	   	   		);
	   	   		foreach($this->getBranch() as $key=>$Branch)
				{
					if(!isset($user[$Branch.'区']))
						$user[$Branch.'区']="";
					$data[$this->name."_".$Branch."区"]=$user[$Branch.'区'];
				}
				M('会员')->bSave($data);
	   	   	}
	   	   	M('会员')->bUpdate();
		}
		
		public function event_modifyId($oldbh,$newbh)
		{ 
			M()->execute("update dms_会员 set {$this->name}_上级编号='{$newbh}' where {$this->name}_上级编号='{$oldbh}'");
			foreach($this->getBranch() as $key=>$Branch)
			{
				M()->execute("update dms_会员 set {$this->name}_{$Branch}区='{$newbh}' where {$this->name}_{$Branch}区='{$oldbh}'");
			}
		}
		/*
		* 根据订单表重新进业绩 删除会员表以及业绩表中的信息
		* 然后根据报单表到款日期先后顺序把所有的报单根据xml中的进业绩关系，执行进业绩操作  生成的业绩都是新增业绩和累计，不进行业绩的结转
		* 以及奖金的结算
		*/
		public function repairupv(){
			//会员表业绩数据清零
			foreach($this->getcon("region",array("name"=>"")) as $region){
				$data[$this->name.'_'.$region['name']."区本期业绩"]=0;
				$data[$this->name.'_'.$region['name']."区本日业绩"]=0;
				$data[$this->name.'_'.$region['name']."区结转业绩"]=0;
				$data[$this->name.'_'.$region['name']."区累计业绩"]=0;
			}
			M("会员")->where("1=1")->save($data);
			//删除所有的业绩表信息
			M($this->name."_业绩")->where("1=1")->delete();
			//找出所有的订单进行addval操作
			$sales=M("报单")->where("到款日期>0")->order("到款日期 asc")->select();
			foreach($sales as $sale){
				systemTime($sale['到款日期']);
				//找出订单的会员
				$user=M("会员")->where(array("编号"=>$sale['编号']))->find();
				if($user){
					//获取sale节点的信息
					$cons=X("@".$sale['报单类别'])->getcon("addval",array("from"=>"","to","now"=>1),false);
					foreach($cons as $con){
						if($con['to']==$this->name && !isset($con['set'])){
							//首先执行特殊定制程序
							$sql="update dms_会员 d inner join(select a.推荐_上级编号 编号,count(1) val  from `dms_会员` a inner join dms_会员 ck on a.推荐_上级编号=ck.编号 where a.审核日期>0 and a.审核日期<".$sale['到款日期']." group by a.推荐_上级编号) b on d.编号=b.编号 set 业绩=1 where d.业绩=0 and ifnull(b.val,0)>=2";
							M()->execute($sql);
							//执行业绩进网方法
							$this->event_valadd($user,transform($con['from'],$sale),$con);
						}
					}
				}
			}
		}
	}
?>