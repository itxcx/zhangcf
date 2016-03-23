<?php
    /*
    *此文件为奖金表模块,奖金表内部会含有若干prize_xxx的奖金模块
    *
    *
    */
    class tle extends stru
    {
        /*结算周期
        s:秒结
        d:日结
        w:周结
        m:月结
        y:年结
        r:审核日期间隔
        */
        public $tleMode = 'd';
        /*发放日
        此属性配合tleMode使用
        如果为周结.则$tleDay设置为1-7,分别表示周一到周日
        如果设置月结,$tleDay为0表示月末,如果为指定日期,则表示每月n号结算
        如果设置审核日期,则为x,[y],[z],x为间隔周期,比如10天一返,y为最小周期,当天返为0下期返为1,z为最大周期,返到多少期为止
        */
        public $tleDay  = '0';
        //内部使用的结算类型,0为秒结,1为手工结()
        public $_caltype =0;
        //内部使用的当前结算日期.
        public $_caltime;
        //K值,还没起到作用
        public $k = 0;
        //在结算期间统计日销售额
        private $salesVolume=0;
        //在结算期间统计实际发放
        private $prizeVolume=0;
        //奖金构成信息缓存,存储计算过程中的构成数据后期统一入库
        public $fromdata=array();
        //结算完成后是否立即发放电子货币,否则会在总账表中出现发放按钮
        public $autoGive=false;  //是否自动发放奖金
        public $autoGiveDelay = 0;//如果是自动发放.是否延迟.单位为天数.如设置为1在结算当期奖金时发放上日奖金
        public $secAutoGive=false;//秒结状态下是否发放奖金
        //日结周发
        public $weekAutoGive=false;
        //日结周发是什么时候发放 设置1-7就是周一到周日
        public $weekGiveDay=1;
        //保留数据天数,0为不限(暂时未开发此功能)
        public $dataHold=0;
        // 未发放奖金的时候.前台不可见.
        public $notgiveshow = false;
        //是否进行合并发放
        public $sumAdd      =false;
        //参与结算和奖金变更的人员的ID数组.如果数组只有一个单元.并是一个all.表示所有人都需要做后续处理
        //此参数用于效率调优
        public $caluser=array();
        function caluserwhere($where)
        {
        	//如果是日结，则不执行条件过滤
        	if($this->_caltype==1)
        	{
        		return $where;
        	}
        	if(is_string($where))
        	{
        		//如果是全部会员,则按照原有条件
        		if($this->caluser=='all')
        			return $where;
        		//如果没有会员则不做任何处理
	        	if(!$this->caluser)
	        	{
	        		return 'false';
	        	}
	        	return 'id in ('.join(',',array_keys($this->caluser)).') and ('.$where.')';
        	}
        	if(is_array($where))
        	{
        		//如果是全部会员,则按照原有条件
        		if($this->caluser=='all')
        		{
        			return $where;
        		}
        		//如果没有会员则不做任何处理
	        	if(!$this->caluser)
	        	{
	        		return 'false';
	        	}
	        	$map = array('_complex'=>$where);
	        	$map['id'] = array('in',array_keys($this->caluser));
	        	return $map;
        	}
        }
        function clearcaluser()
        {
        	$this->caluser=array();
        }
        function addcaluser($id)
        {
        	if($id=='all')
        	{
        		$this->caluser='all';
        		return;
        	}
        	//如果已经是all.可以直接返回
        	if($this->caluser=='all')
        	{
        		return;
        	}
        	if(!isset($this->caluser[$id]))
        	{
        		$this->caluser[$id]=1;
        	}
        }
        //系统数据初始化事件
        function event_sysclear()
        {
            //(举例) 删除 会员_销售奖金表,会员_销售奖金构成表,会员_销售奖金总账表
            M()->execute("TRUNCATE TABLE dms_".$this->name.";");
            M()->execute("TRUNCATE TABLE dms_".$this->name."构成;");
            M()->execute("TRUNCATE TABLE dms_".$this->name."总账;");
        }
        //
        function event_rollback($time)
        {
        	//在删除之前应该要做发放回退
        	$delsql = M($this->name)->where(array('计算日期'=>array('egt',$time)))->field('id')->select(false);
        	//做发放回退
        	//需要从新做货币修正的货币
        	//统计所有的添加目标
        	$alladd=array();
        	//统计tle自身addval目标
            foreach($this->getcon("addval",array('to'=>'')) as $con)
            {
            	$alladd[$con['to']] = '1';
            }
            //统计奖金addval目标
            foreach(X('prize_*',$this) as $prize)
            {
            	foreach($prize->getcon("addval",array('to'=>'')) as $con)
            	{
            		$alladd[$con['to']] = '1';
            	}
        	}
        	foreach($alladd as $bankname=>$val)
        	{
        		//目标必须是一个货币
        		$bank = X('fun_bank@'.$bankname);
				if($bank)
				{
					//删除跟这个奖金表有关的所有货币添加
					$ret=M($bankname.'明细')
						->where(array('tlename'=>$this->name,'dataid'=>array('exp','in '.$delsql)))
						->delete();
					if($ret)
					{
						//如果有记录被删除，则整个货币需要从新做修正处理
						$bank->revise();
					}
				}
        	}
        	//减去每个奖金的累计
        	$upstr='u.累计收入=u.累计收入-p.收入';
        	foreach(X('prize_*',$this) as $prize){
        		if($prize->prizeMode==1){
        			$upstr.=',u.'.$prize->name.'累计=u.'.$prize->name.'累计-p.'.$prize->name;
        		}
        		if($prize->prizeMode==2){
        			$upstr.=',u.'.$prize->name.'累计=u.'.$prize->name.'累计+p.'.$prize->name;
        		}
        	}
        	
        	$sql="update dms_会员 u inner join (select * from dms_".$this->name." where 计算日期>=".$time.") p on u.编号=p.编号 set ".$upstr;
        	if($upstr!=""){
        		M()->execute($sql);
        	}
        	//删除销售奖金
        	M($this->name.'构成')->where(array('dataid'=>array('exp','in '.$delsql)))->delete();
        	M($this->name)->where(array('计算日期'=>array('egt',$time)))->delete();
        	M($this->name.'总账')->where(array('计算日期'=>array('egt',$time)))->delete();
        }
        //订单审核后调用此方法进行计算
        function scal($sale)
        {
        	//清空caluser缓存
        	$this->clearcaluser();
            //如果没有秒结的奖金项,则直接return
            $calmode = true;
            if($this->tleMode!='s'){
                foreach(X('prize_*',$this) as $prize){
                    if($prize->tleMode =='s'){
                        $calmode = false;
                        break;
                    }
                }
                if($calmode) return;
            }
            M()->execute("SET @SCAL='秒结开始于.".date('H:i:s',time())."';");
            //设置当前结算状态为秒结形式
            $this->_caltype=0;
            //设置结算日为当日0点
            $this->_caltime=strtotime(date("Y-m-d",systemTime()));
            //激发scal事件,让其他模块可以相应event_scal方法
            X('user')->callevent("scal",array());
            //循环奖金表上级的所有levels节点.并执行自动升级操作
            foreach(X('levels') as $levels)
            {
                //自动升级处理操作
                $levels->uplv('scalStart',$this->_caltime+86400-1);
            }
            //遍历奖金表下级所有节点
            foreach(X('prize_*',$this) as $prize)
            {
                /*
                当下级节点对象存在scal方法(表示支持秒结算处理)的时候,判定当前奖金为秒结或者当前奖金表为秒结,就执行秒结操作
                假设奖金表为日结,某一个奖金项为秒结.是允许的.但是奖金表为秒结,奖金项设置为日结.是不被接受的
                */
                if(method_exists($prize,"scal"))
                {
                    if($prize->tleMode=='all' || $prize->tleMode=='s'||$this->tleMode=='s')
                    {
                        //执行秒结算处理,并把当前订单信息传入
                        $prize->scal($sale);
                    }
                }
            }
            //奖金结算结果保存到奖金表
            $this->save();
            //更新报单为有效状态
            $this->saleeffect($this->_caltime,$sale);
            //更新总账信息
            $this->makeLedger();
            //触发calover事件
            X('user')->callevent('calover',array('tle'=>$this,'caltime'=>$this->_caltime,'type'=>'scal'));
            //循环奖金表上级的所有levels节点.并执行自动升级操作
            foreach(X('levels') as $levels)
            {
                //自动升级处理操作
                $levels->uplv('scalEnd',$this->_caltime+86400-1);
            }
            M()->execute("SET @SCAL='秒结结束于".date('H:i:s',time())."';");
            /***完成奖金构成信息***/
			import('DmsAdmin.DMS.SYS.PrizeData');
	        PrizeData::commit($this->_caltime,true);
	        /*********************/
        }
        //把某日的"已确认"订单变为"已结算"状态
        public function saleeffect($date,$sale=array()){
            $startdate=$date;
            $enddate=$date+24*3600;
            $where="到款日期 >= ".$startdate." AND 到款日期 < ".$enddate." AND 报单状态 = '已确认'";
            if(isset($sale['id']))
            	$where.=" AND id=".$sale['id'];
            $update=array();
            $update['报单状态']="已结算";
            M("报单")->where($where)->save($update);
        }
        /*
            进行手动结算操作的处理入口
            此方法会被批量会员注册调用.也会被/Admin/TleAction.php调用
        */
        function cal($caltime,$recal=false)
        {
            //如果奖金表处于秒结算模式,则直接退回
            if($this->tleMode=='s')
            {
                return ;
            }
            $this->clearcaluser();
            //设置结算状态为手工结算
            $this->_caltype=1;
            //设置结算时间
            $this->_caltime = $caltime;
            //执行cal事件
            calmsg('执行cal事件','/Public/Images/ExtJSicons/lightning.png');
            X('user')->callevent("cal",array('tle'=>$this,'caltime'=>$caltime));
            //处理自动升级
            foreach(X('levels') as $levels)
            {
                //处理自动升级
                $levels->uplv("calStart",$this->_caltime+86400-1);
            }
            //取得所有下级节点
            foreach(X('prize_*',$this) as $prize)
            {
                //判断下级节点是否存在cal函数,如果存在则调用
                if(method_exists($prize,"cal"))
                {
                    //如果下级节点设置结算周期为秒结,则不调用
                    if(($prize->tleMode!='s' || $recal)){
                       $prize->cal();
                    }
                }
            }
            foreach(X('levels') as $levels)
            {
                //处理自动升级
                $levels->uplv("calEnd",$this->_caltime+(2*86400)-1);
            }
            //保存奖金表信息
            $this->save();
            //更新报单为有效状态
            $this->saleeffect($this->_caltime);
            //更新总账信息
            calmsg('更新总账信息','/Public/Images/ExtJSicons/chart/chart_pie_add.png');
            $this->makeLedger();
            //执行结算完成事件
            calmsg('触发calover事件','/Public/Images/ExtJSicons/lightning.png');
            X('user')->callevent('calover',array('tle'=>$this,'caltime'=>$this->_caltime,'type'=>'cal'));
        }
        //奖金表存储
        public function save()
        {
            //建立会员表Model
            $m_user  =M('会员');
            //建立奖金表Model
            $m_tle   =M($this->name);
            //创建查询条件数组
            $where   =array();
            //创建奖金项数组
            $prizearr=array();
            $uadata  =array();
            $nowkrate=1;
            //奖金表列对应的会员表列
            $tle2user=array();
            foreach(X('levels') as $levels){
            	$tle2user[$levels->name] = $levels->name;
            }
            //遍历推荐人信息,作用为和级别一致
            foreach(X('net_rec') as $net_rec){
            	$tle2user[$net_rec->name.'_上级编号']=$net_rec->name.'_上级编号';
            }
            //安置人信息
            foreach(X('net_place') as $net_place)
            {
				$tle2user[$net_place->name.'_上级编号']=$net_place->name.'_上级编号';
                foreach($net_place->getcon("region",array("name"=>"")) as $nameconf)
                {
                   if(!$this->parent()->allInTle){
                   	   $plstr = $net_place->name."_".$nameconf['name']."区";
                       $where[$plstr."本日业绩"] = array('gt',0);
                   }
               }
            }
            $prizenames='';
            foreach(X('prize_*',$this) as $prize1)
            {
                //判定是一种数值计算形奖金(主要为了去除prize_sql)
                if($prize1->prizeMode >= 0){
                	$prizenames.=','.$prize1->name;
                    //如果会员没有开启allInTle,则判定必须有奖金项金额大于0的情况下.才会增加奖金记录.
                    if(!$this->parent()->allInTle){
                        $where[$prize1->name]=array('gt',0);
                    }
                    //增加奖金项目缓存数组,存储名称以及$prize1->prizeMode
                    $prizearr[]=array('name'=>$prize1->name,'prizeMode'=>$prize1->prizeMode);
                }
            }
            if($this->k >0 && $this->tleMode == 'd'){
                //得到当前的所有奖金拨出
                $prizesumstr='';
                foreach($prizearr as $prize)
                {
                    if($prizesumstr!=='') $prizesumstr.='+';
                    if($prize['prizeMode']==1) $prizesumstr.=$prize['name'];
                    if($prize['prizeMode']==2) $prizesumstr.='(-'.$prize['name'].')';
                }
                $thisAchievement=0;
                foreach(X('sale_*') as $sale)
                {
                	if($sale->ledger!='')
                	{
                		$thisAchievement += M('报单')->where(array('到款日期'=> array(array('egt',$this->_caltime),array('lt',$this->_caltime+86400)),'报单类别'=>$sale->name,'报单状态'=>array('not in','空单,回填')))->sum($sale->ledger);		
                	}
                }
                $krate_temp = 0;
                if($thisAchievement>0)$krate_temp=(M('会员')->sum($prizesumstr)/$thisAchievement)*100;
                //是否超出了K值
                if($this->k >0 && $this->k < $krate_temp && $krate_temp > 0){
	                //在实际奖金增加时会使用此变量
	                $nowkrate = 1-(($krate_temp - $this->k)/$krate_temp);
	                //奖金构成文件中添加K值信息
					import('DmsAdmin.DMS.SYS.PrizeData');
		            PrizeData::Kadd($this->name,'TK','','',$this->k);
                }
            }
            //设置整体判定为or,也就是任意一项奖金大于0,都会激发判定
            $where['_logic']="or";
            $where=$this->caluserwhere($where);
            //生成查询会员表的字段的字符串
            $rows='id,编号,累计收入';
            foreach($tle2user as $tlerow=>$userrow)
            {
            	$rows.=','.$userrow;
            }
            foreach($prizearr as $prize2){
                $pname=$prize2['name'];
                $rows.=','.$pname;
            }
            //查询到符合判定的会员信息
            $users = $m_user->where($where)->select();
            //找到本日奖金中存在的记录
            $hasuserarr=$m_tle->lock(true)->where(array('计算日期'=>$this->_caltime))->getField('编号,id,奖金,收入'.$prizenames); 
            if(!$hasuserarr)
            	$hasuserarr=array();
            //奖金表总封顶
            $topcons=$this->getcon('top',array('where'=>'','val'=>0,'mode'=>'day'));
            //循环查到的用户
            if($users)
            foreach($users as $user)
            {
            	$tledata=array();
                //编号
                $tledata["编号"]=$user["编号"];
                //计算日期等于当前结算时间
                $tledata["计算日期"]=$this->_caltime;
                //设置默认奖金和收入值
                $tledata["奖金"]=0;
                $tledata["收入"]=0;
                //0表示已经完成结算和发放 1表示正在结算中
                $tledata["state"]=1;
                //创建要插入或者合并奖金表的临时数组
                //对多余字段进行增加
                foreach($tle2user as $tlerow=>$userrow)
                {
                	$tledata[$tlerow]=$user[$userrow];
                }
                //从net_place的结算缓存中拉取业绩数据
                foreach(X('net_place') as $net_place){
                	foreach($net_place -> getcon("region", array("name" => "")) as $nameconf){
                		$plstr = $net_place->name."_".$nameconf['name']."区";
                		$tledata[$plstr . "本日业绩"] = isset($net_place->cache[$user['id']][$plstr . "本日业绩"])?$net_place->cache[$user['id']][$plstr . "本日业绩"]:0;
                		$tledata[$plstr . "累计业绩"] = isset($net_place->cache[$user['id']][$plstr . "累计业绩"])?$net_place->cache[$user['id']][$plstr . "累计业绩"]:0;
                		$tledata[$plstr . "结转业绩"] = isset($net_place->cache[$user['id']][$plstr . "结转业绩"])?$net_place->cache[$user['id']][$plstr . "结转业绩"]:0;
                	}
                }
                //遍历所有奖金计算出当期奖金和收入
                foreach($prizearr as $prize2){
                	$pname=$prize2['name'];
                    //奖金不管是否产生实收,都要进入到
                    if($prize2['prizeMode'] >= 0){
                		$tledata[$pname]=$user[$pname]*$nowkrate;
                        //$tledata[$pname.'本周']=$user[$pname.'本周']+$user[$pname]*$nowkrate;
                        //$tledata[$pname.'本月']=$user[$pname.'本月']+$user[$pname]*$nowkrate;
                        //$tledata[$pname.'累计']=$user[$pname.'累计']+$user[$pname]*$nowkrate;
                	}
                    //奖金不管是否产生实收,都要进入到
                    if($prize2['prizeMode'] == 1){
                        $tledata["奖金"]+=$user[$pname]*$nowkrate;
                        $tledata["收入"]+=$user[$pname]*$nowkrate;
                    }
                    
                    if($prize2['prizeMode'] == 2){
                    	$tledata[$pname] =-$user[$pname]*$nowkrate;
                        //$tledata[$pname.'本周']=$user[$pname.'本周']-$user[$pname]*$nowkrate;
                        //$tledata[$pname.'本月']=$user[$pname.'本月']-$user[$pname]*$nowkrate;
                        //$tledata[$pname.'累计']=$user[$pname.'累计']-$user[$pname]*$nowkrate;
                        $tledata["收入"] -=$user[$pname]*$nowkrate;
                    }
                }
                //处理总奖金封顶，目前看样子不支持秒结日封顶
                foreach($topcons as $topcon)
                {
                    if(transform($topcon['where'],$user))
                    {
                        //当日封顶
                        if($topcon['mode']=='day' && $tledata["收入"]>$topcon['val'])
                        {
                            $tledata["收入"]=$topcon['val'];
                        }
                        //累计封顶
                        if($topcon['mode']=='all')
                        {
                            if(($user["累计收入"]+$tledata["收入"])>$topcon['val'])
                            {
								/*(封顶值-也取得的收入)+(封顶值-也取得的收入)的绝对值)/2 这个是两种算法放在一起之后的算法。
								首先先确定，累计加上本期肯定会封顶，
								1、未加上就封顶的情况 比如封顶值1000，已经收入1100（很少出现，修改了参数会出现），
								然后计算本期奖金收入应该是 0；
								2、加上之后才会封顶  比如封顶值1000，已经收入900，
								然后计算本期收入应该是 1000-900
								少写判断  放在一起
									那么计算是：封顶值-已取得的收入  得到还剩下多少拿，
									会有可能产生负数，那么不能是负数，最少是0，
									那么我们将这个负数加上它的绝对值就变成0了，
									然后我们发现还有可能是正数，加上正数的绝对值就变成两倍了，
									所以我们除以2，得到最终的值*/
                                //$tledata["收入"]=($topcon['val']-$user["累计收入"]+abs($topcon['val']-$user["累计收入"]))/2;  //替换成下面代码执行
                                $tledata["收入"]= ($user["累计收入"] >= $topcon['val']) ? 0 : $topcon['val']-$user["累计收入"];
                            }
                        }
                    }
                }
                $tledata['累计收入'] = $user["累计收入"] + $tledata["收入"];
				//$uadata[$user["编号"]]=$tledata;
				//如果当前人没有存在于本日奖金当中，则执行插入流程
				//未存在本日记录的
				if(!array_key_exists($user['编号'],$hasuserarr)){
					
					$m_tle->bAdd($tledata);
				}
				else//已存在本日记录的
				{
                	$data=array();
	                $data['id']   = $hasuserarr[$user['编号']]['id'];
	                $data['奖金'] = $tledata['奖金'] + $hasuserarr[$user['编号']]['奖金'];
	                $data['收入'] = $tledata['收入'] + $hasuserarr[$user['编号']]['收入'];
	                foreach($prizearr as $prize5){
		                $pname = $prize5['name'];
		                $data[$pname] = $hasuserarr[$user['编号']][$pname] + $tledata[$pname];
	                }
	                $data['state']=1;
	                foreach(X('net_place') as $net_place){
	                	foreach($net_place -> getcon("region", array("name" => "")) as $nameconf){
	                		$plstr = $net_place->name."_".$nameconf['name']."区";
	                		$data[$plstr . "本日业绩"] = isset($tledata[$plstr . "本日业绩"])?$tledata[$plstr . "本日业绩"]:0;
	                		$data[$plstr . "累计业绩"] = isset($tledata[$plstr . "累计业绩"])?$tledata[$plstr . "累计业绩"]:0;
	                		$data[$plstr . "结转业绩"] = isset($tledata[$plstr . "结转业绩"])?$tledata[$plstr . "结转业绩"]:0;
	                	}
	                }
	            	$data['累计收入+']=$tledata['收入'];
	                $m_tle->bSave($data);
				}
				/*
				累计收入已在prizelog表实现,使用tle节点做增加*/
				if($tledata["收入"])
				{
		            M("会员")->bSave(array(
		            'id'=>$user['id'],
		            '累计收入+'=>$tledata["收入"],
		            '月收入+'  =>$tledata["收入"])
		            );
                }
                
            }
            //更新对奖金的处理
            $m_tle->bUpdate();
            //更新会员的累计收入
            M("会员")->bUpdate();
            //得到会员编号对应本日奖金表的ID
			$justin=$m_tle->where("state=1 and 计算日期=".$this->_caltime)->getField("编号,id");
            $realjustin = $justin;
            //如果开启了自动发放,则需要对当前奖金记录进行发放操作
            if($this->secAutoGive && $this->_caltype == 0){
                  //取得未处理奖金记录
                 $tlelist=$m_tle->where("计算日期=".$this->_caltime)->select();
                 //进行发放操作
                 $this->givePrice($tlelist);
            }
            if($this->autoGive && $this->_caltype == 1 && false){
                //进行发放操作
                if($this->autoGiveDelay==0)
                {
                    $tlelist=$m_tle->where("计算日期=".$this->_caltime)->select();
                    $this->givePrice($tlelist);
                }
                else
                {
                     //总账查询
                     $oldTime = $this->_caltime-$this->autoGiveDelay*86400;
                     $oldLedger=M($this->name."总账")->lock(true)->where(array('计算日期'=>$oldTime))->find();
                     //找到总账,并且未发放
                     if($oldLedger && $oldLedger['state']==0)
                     {
                         $tlelist=$m_tle->lock(true)->where("计算日期=".$oldTime)->select();
                         $this->givePrice($tlelist);
                         M($this->name."总账")->where(array('计算日期'=>$oldTime))->save(array('state'=>1));
                     }
                }
            }
            //判断日结周发的奖金发放
            if($this->weekAutoGive && false){
               //判断是周几要开始发放
               if(date('N',$this->_caltime)==(int)$this->weekGiveDay){
                  //判断一下是否是日结
                  if($this->tleMode == 'd'){
                     //将之前没有发放的都给发放
                     $oldprizes=M($this->name."总账")->lock(true)->where(array('计算日期'=>array('lt',$this->_caltime)))->select();
                   
	                     foreach($oldprizes as $key=>$oldprize){
	                     //找到总账,并且未发放
		                         if($oldprize && $oldprize['state']==0)
		                         {
		                             $tlelistPrize=$m_tle->lock(true)->where("计算日期=".$oldprize['计算日期'])->select();
		                             $this->givePrice($tlelistPrize);
		                             $res = M($this->name."总账")->where(array('计算日期'=>$oldprize['计算日期']))->save(array('state'=>1));
		                         }
	                     }
                        $tlelist_today=$m_tle->lock(true)->where("state=1 and 计算日期=".$this->_caltime)->select();
                        $this->givePrice($tlelist_today);
                  }
               }
            }
            //设置当日state=1的奖金记录的state为0.表示奖金处理完成
            if($this->_caltype==0)
        	{//秒结只处理有产生奖金的记录
                if(isset($realjustin) && count($realjustin)>0)
                	$m_tle->where("id in (".join(',',$realjustin).")")->save(array("state"=>0));
            }else
            {
                $m_tle->where("state=1 and 计算日期=".$this->_caltime)->save(array("state"=>0));
            }
            //清空构成信息缓存表
            $this->fromdata=array();
            //执行清理过程
            $this->clear($this->_caltime);
        }
        /*
        ** 奖金发放 可以在tle节点中加<_addval from="" to="电子币" val="100%" userwhere="" memo=""/>,也可以在prize节点中增加
        ** 在prize加会直接发放这个奖金；在tle中增加会根据from的值发放哪些奖金，如果没有值，默认发放收入，from的值每个奖金之间用','分开
        ** memo表示显示的内容，包括货币明细中的备注和类型，如果memo值为空，默认等于from的值
        ** val的是负数使用，如果扣除奖金发到某个钱包，那么这个奖金发放的val应该为-100%，因为这个奖金在奖金表中的数值为负数
        ** userwhere表示发放奖金的条件 有会员表中的字段做判断，字段需加上"[]"，比如"[会员级别]>2"，运行将字段的值查出 条件判断发放
        ** 运行流程：查出奖金明细，循环发放的addval节点，然后传值到inBank函数中执行发到钱包，如果发放过一部分会在原货币明细上增加金额
        ** ----有问题 找基础模块人员
        */
        public function givePrice($tlelist)
        {
            if(empty($tlelist)) 
            	return;
            $addcons=$this->getcon("addval",array('from'=>'','to'=>'','userwhere'=>'','val'=>'100%','memo'=>''),true);
            foreach($addcons as $key=>$addcon)
            {
				$bank = X('fun_bank@'.$addcon['to']);
				if($bank)
				{
					$this->inBank($tlelist,$addcon['from'],$addcon,$addcon['memo']);
				}
            }
            foreach(X('prize_*',$this) as $prize)
            {
            	if($prize->prizeMode>=0)
                {
                	$addcons=$prize->getcon("addval",array('to'=>'','userwhere'=>'','val'=>'100%','now'=>0),true);
                    foreach($addcons as $key=>$addcon)
                    {
                        if($addcon['now']==0)
                        {
                        	$prizename=$prize->name;
                        	if($prize->byname!="") 
                        		$prizename=$prize->byname;
                        	if(X('fun_bank@'.$addcon['to'])){
                        		$this->inBank($tlelist,$prize->name,$addcon,$prizename);
                        	}else{
                        		foreach($tlelist as $key=>$val){
                        			$pricenum=getnum($val[$prize->name],$addcon["val"]);
                        			X("@".$addcon['to'])->event_valadd($val,$pricenum,$addcon);
                        		}
                        	}
                        }
                    }
                }
            }
        }
        /*
        ** 奖金记录进入到某一个货币当中，有giveprce传值过来
        */
        public function inBank($tlelist,$prizename,$addcon,$showname)
        {
           	//得到to目标对象
            $bank = X('fun_bank@'.$addcon['to']);
            //如果目标为fun_bank
            $time=$tlelist[0]['计算日期'];
            //$m_user=M('会员');
            $m_user=M('货币');//货币分离
            $m_bank=M($bank->name.'明细');
            //判断是奖金的分发放还是收入发放
            if($prizename)
            {
            	//字段数组 奖金1,奖金2,奖金3
            	$rowary=explode(',',$prizename);
            	//备注  奖金1,奖金2,奖金3
            	$shownameary=$rowary;
            	if($showname!=''){
            		$shownameary=explode(',',$showname);
            	}
	        }
	        else
	        {
	        	//字段数组 奖金1,奖金2,奖金3
            	$rowary=explode(',','收入');
            	//备注  奖金1,奖金2,奖金3
            	$shownameary=explode(',',$this->name);
            	if($showname!='')
            		$shownameary=explode(',',$showname);
	        }
	        //生成的备注以及类型
	        if(!array_key_exists("bankmemo",$addcon)) 
	        	$addcon["bankmemo"] = date('Y-m-d',$time) . "产生[showname]转入$"."val";
	        //if(!array_key_exists("bankmode",$addcon)) $addcon["bankmode"] = $showname;
            //查询会员信息以及userwhere条件中的字段$wherestr
            $wherestr="";
            if($addcon['userwhere']!=""){
            	preg_match_all('/(?<!\$_REQUEST)(?<!\$_POST)(?<!\$_GET)([A-Z]?)\[(.*)\]/Uis',$addcon['userwhere'],$trforms,PREG_SET_ORDER);
            	foreach($trforms as $trform){
            		$wherestr.=",u.".$trform[2];
            	}
            }            
            //合成奖金表id和数组KEY的对应表 奖金表中所有会员的编号数组
            $tleidss=array();$bhs=array();
            $haveSups = array();
            $i=0;
	        $tlelistcnt=count($tlelist);
            foreach($tlelist as $key=>$val)
            {
            	$i++;
            	$tleidss[$val['id']]=$key;
            	$bhs[]=$val['编号'];
            	if(count($bhs)>1000 || $i==$tlelistcnt){
            		//查询会员的信息以及货币
            		$haveSupsub = M("会员")->table("dms_会员 u")->join("dms_货币 f on u.id=f.userid")->where(array('u.编号'=>array('in',$bhs)))->getField("u.编号 keyid,f.id,u.编号,f.".$bank->name." num".$wherestr);
         			$haveSups = $haveSupsub+$haveSups;
         			$bhs=array();
         		}
            }
            //循环奖金名称数组
            foreach($rowary as $rkey=>$row){
            	$tleids=$tleidss;
            	//取得已经产生过的发放记录
            	$havelog=$m_bank->lock(true)->where(array('tlename'=>$this->name,'prizename'=>$row,'dataid'=>array('in',array_keys($tleids))))->getField('dataid,id,金额,编号,时间');
	            if($havelog)
	            {
	            	//对会员的更新缓存
	            	$usernames = array();
	            	//对明细的更新缓存
	            	$logset = array();
	            	//循环货币明细
	            	foreach($havelog as $key=>$log)
	            	{
	            		if($time>$log['时间'] || $log['编号']!=$tlelist[$tleids[$key]]['编号'])
	            		{
	            			throw_exception($bank->name.'货币记录,与发放奖金记录存在数据错误,货币记录中id为'.$log['id'].'的记录,不是会员'.$tlelist[$tleids[$key]]['编号'].'或者货币记录的日期,早于奖金发放日.此问题可能由于数据导入而产生.如果不存在秒结算,可尝试对货币表中的dataid清零处理');
	            		}
	            		if(!transform($addcon['userwhere'],$haveSups[$log['编号']]))
            				continue;
	            		if(abs($tlelist[$tleids[$key]][$row])>0)
	            		{
		            		$price = getnum($tlelist[$tleids[$key]][$row],$addcon["val"]);
		            		if($log['金额'] != $price)
		            		{
		            			$usernames[] = $log['编号'];
		            			$bankmemo=str_replace('$val',$price,$addcon["bankmemo"]);
				      			$bankmemo=str_replace('[showname]',$shownameary[$rkey],$bankmemo);
		            			$logset[$log['id']] = array('编号'=>$log['编号'],'val'=>$price - $log['金额'],'memo'=>$bankmemo);
		            		}
		            		unset($tleids[$key]);
	            		}
	            	}
	            	//如果找到有需要更新的人
	            	if($logset)
	            	{
	            		$maxlog = $m_bank->lock(true)->where(array('编号'=>array('in',$usernames)))->group('编号')->getField('编号,max(id)');
	            		$name2id = $m_user->where(array('编号'=>array('in',$usernames)))->getField('编号,id');
	            		foreach($logset as $key=>$log)
	            		{
	            			$m_bank->bSave(array(
	            				'id'=>$key,
	            				'金额+'=>$log['val'],
	            				'余额+'=>$log['val'],
	            				'备注' =>$log['memo'])
	            				);
	            			$m_user->bSave(array(
	            				'id' => $name2id[$log['编号']],
	            				$bank->name .'+' => $log['val'])
	            				);
	            			$haveSups[$log['编号']]['num']+=$log['val'];
	            			//如果当前会员的货币记录.不等于其拥有的最大记录.则需要额外做一个更新
	            			if($maxlog[$log['编号']]!=$key)
	            			{
	            				$m_bank->where("id>".$key." and 编号='".$log['编号']."'")->setInc('余额',$log['val']);
	            			}
	            		}
	            		$m_user->bUpdate();
	            		$m_bank->bUpdate();
	            	}
	            }
	            //处理余下的
	            if($tleids)
	            {
		         	foreach($tleids as $key=>$val)
			      	{
			      		$data = $tlelist[$val];
			      		$price = getnum($data[$row],$addcon["val"]);
			      		if(abs($data[$row])==0)
			      		{
			      			continue;
			      		}
			      		if(transform($addcon['userwhere'],$haveSups[$data['编号']])){
					      	$m_user->bSave(array('id'=>$haveSups[$data['编号']]['id'],$bank->name.'+'=>$price));
					      	$bankmemo=str_replace('$val',$price,$addcon["bankmemo"]);
					      	$bankmemo=str_replace('[showname]',$shownameary[$rkey],$bankmemo);
					      	$m_bank->bAdd(array(
					      		'编号'=>$data['编号'],
					      		'来源'=>'',
					      		'类型'=>$shownameary[$rkey],
					      		'备注'=>$bankmemo,
					      		'金额'=>$price,
					      		'余额'=>$haveSups[$data['编号']]['num'] + $price,
					      		'时间'=>systemTime(),
					      		'tlename'=>$this->name,
					      		'prizename'=>$row,
					      		'dataid'=>$key,
					      		));
					      	$haveSups[$data['编号']]['num']+=$price;
					    }
			      	}
			      	$m_bank->bUpdate();
			      	$m_user->bUpdate();		      	
	            }
            }
            //清空缓存 奖金id
	        unset($tleids);unset($tleidss);
        }
        /**
         +----------------------------------------------------------
         * 清零本日.以及本月累计数据
         * 会员表中存在每项奖金的本日,和本月累计数据.需要在结算到特定日期以后.进行清零
         +----------------------------------------------------------
         * @param int $caltime 奖金产生的日期时间撮
         * @param bool $scalclear 如果目前结算为秒结,表示是否是本日第一次秒结,秒结会在结算前调用clear
         +----------------------------------------------------------
         * @access public
         +----------------------------------------------------------
         */
        public function clear($caltime)
        {
            //设置要清零的字段数组
            $clearrow=array();
            //取得下级所有奖金
            foreach(X('prize_*',$this) as $prize)
            {
                //表示为奖金计算类型奖金
                if($prize->prizeMode>=0)
                {
                    //首先所有于奖金同名的字段肯定要清零,这个字段仅在计算期间有值,计算完成后应为0
                    $clearrow[$prize->name]=0;
                    if (($prize->getTleMode() != 's' && $prize->getTleMode() != 'all') || ($prize->getTleMode() == 'all' && $this->_caltype==1)){
                        //本日数据清零
                        $clearrow[$prize->name.'本日']=0;
						if (Date('w',$caltime) == 0){
							$clearrow[$prize->name.'本周']=0;
						}
                        //如果当前结算天为月末这一天,则清空本月业绩
                        if (Date('t',$caltime) == Date('d',$caltime)){
                            $clearrow[$prize->name.'本月']=0;
                        }
                    }
                }
            }
            if (Date('t',$caltime) == Date('d',$caltime))
            {
                $clearrow['月收入']=0;
            }
            //对所有会员进行保存
            M('会员')->where("1=1")->save($clearrow);
        }
        //增加总账信息
        public function makeLedger($caltime = null)
        {
        	
        	//统计奖金表计算粒度,最低为日,秒结按日处理
        	$tleMode = $this->tleMode == 's' ? 'd' : $this->tleMode;
        	$tleDay  = $this->tleDay;
        	
        	//如果不是日结
        	if($tleMode != 'd')
        	{
        		foreach(X('prize_*',$this) as $prize)
        		{
        			if($prize->prizeMode >= 0 && $prize->tleMode<>'' && $prize->tleMode<>'' &&  $prize->tleMode != $this->tleMode)
        			{
        				$tleMode = 'd';
        			}
        		}
        	}
        	//$modeData = prize::chkTleMode($caltime,$tleMode,$tleDay);
        	//if(!$modeData)
        	//{
        	//	return;
        	//}
        	//$modeData = '';
        	//$modeData = '';
        	
        	//先注释点 这个地方 不能这么限制 如果这么限制的话 那么统计总业绩有问题 这个以后系统在更新
			/*	$isrun=false;
				$prizes=array();
				foreach(X('prize_*',$this) as $p)
				{
					$prizes[]=$p;
				}
				if(count($prizes)>0)
				{
					foreach($prizes as $prize)
					{
						if($prize->ifrun())
						{
							$isrun=true;
							break;
						}
					}
				}
				if(!$isrun){
					return;
				}
			*/
        	$thisAchievement = 0;
            //获得当前结算时间
            if($caltime == null){
            	$caltime = $this->_caltime;
            	$caltype = 1;//自动秒结
            }else{
            	$caltype = 2;//手动结算
            }
            //dump($caltime);
			//die();
            //打开奖金表总账信息
            $m_Ledger = M($this->name."总账");
            //寻找小于等于当前结算日的最后一次的总账信息.
            $Ledger   = $m_Ledger->lock(true)->where(array('计算日期'=> array('lt',$caltime)))->order('计算日期 desc')->find();
            //如果没有找到,则认为是第一次结算.所有的总账业绩数据为0
            if(!$Ledger)
            {
                //设置总账生成开始时间为当前时间
                $startTime      = $caltime;
                //累计业绩数
                $sumAchievement = 0;
                //累计发放数
                $sumPrize       = 0;
                //累计会员数
                $sumUser        = 0;
            }
            else
            {
                /*
                    设置总账开始时间为记录的次日,假设1号有总账信息,
                    我在计算3号奖金,$startTime要为2号,因为总账如果有缺失日期,需要能够补全
                */
                $startTime      = $Ledger['计算日期']+86400;
                $sumAchievement = $Ledger['总业绩'];
                $sumPrize       = $Ledger['总奖金'];
                $sumUser        = $Ledger['全部会员'];
            }
			$thisPrize = 0;
            //需要最早生成总账的天数,于当前结算日期间隔几天并循环.0天也循环一次,表示当日，秒结执行
			if($this->tleMode=='s'){
				$startnum=0;
			}else{
				$startnum=floor(($caltime-$startTime)/86400);
			}
            for($i=0;$i<=floor(($caltime-$startTime)/86400);$i++)
            {
                //得到要产生总账记录的具体时间戳
                $iftime = $startTime + ($i*86400);
                //判断当期是否已经保存
                $thisledger=$m_Ledger->where(array('计算日期'=>array('eq',$iftime)))->lock(true)->find();
                
                $modeData = prize::chkTleMode($iftime,$tleMode,$tleDay);
                if(!$modeData)
            	{
            		continue;
            	}
                //根据报单金额和时间条件,统计业绩信息(此处还需要改进)
                $sumarr=array();
                foreach(X('sale_*') as $sale)
                {
                	if($sale->ledger!='')
                	{
                		$sumarr[$sale->ledger][]=$sale->name;
                		//$thisAchievement += M('报单')->where(array('到款日期'=> array(array('egt',$modeData['sdate']),array('elt',$modeData['edate'])),'报单类别'=>$sale->name))->sum($sale->ledger);
                	}
                }
                foreach($sumarr as $k=>$val){
                	$thisAchievement += M('报单')->where(array('到款日期'=> array(array('egt',$modeData['sdate']),array('elt',$modeData['edate'])),'报单状态'=>array('not in','空单,回填'),'报单类别'=>array('in',implode(',',$val))))->sum($k);
                }
                //得到当期的奖金收入(得到当日奖金表收入)
                $thisPrize       += M($this->name)->where(array('计算日期'=> array(array('egt',$modeData['sdate']),array('elt',$modeData['edate']))))->sum('收入');
                 //统计当日会员
                $newuser  = M('会员')->where(array('审核日期'=> array(array('egt',$modeData['sdate']),array('lt',$modeData['edate'])),'状态'=>array('eq','有效')))->count('id');
                //全部会员数量增加当日会员
                $sumUser += $newuser;
                //总业绩增加当日业绩
                $sumAchievement  += $thisAchievement;
                //总奖金增加当日奖金
                $sumPrize        += $thisPrize;
                //要存储的数据
                $savedata         =array(
                    '计算日期'=>$iftime,
                    '总业绩'=>$sumAchievement,
                    '总奖金'=>$sumPrize,
                    '本期业绩'=>$thisAchievement,
                    '本期奖金'=>$thisPrize,
                    '新增会员'=>$newuser,
                    '全部会员'=>$sumUser,
                    '结算方式'=>$caltype
                    );
                //得到当期的各个奖金的汇总,此处也应该可以优化
               foreach(X('prize_*',$this) as $price)
               {
	               if($price->prizeMode>=1)
	               {
	                   $savedata[$price->name]=M($this->name)->where(array('计算日期'=> array(array('egt',$modeData['sdate']),array('elt',$modeData['edate']))))->sum($price->name);
	                   $savedata[$price->name] == null && $savedata[$price->name] = 0;
	               }
               }
                //设置当日的奖金的发放状态,默认为未发放
                $savedata['state']=0;
                
                //秒结秒发
                if($this->secAutoGive && $this->_caltype == 0){
                    //判断当前为纯秒结算，则默认处于已发放状态
                    if($this->tleMode == 's')
                    {
                        $savedata['state'] = 1;
                    }
                	//判断是否以保存本期总账信息
                	if(isset($thisledger['state'])){
                		$savedata['state']=$thisledger['state'];
                	}else{
                		//判断其他奖金是否全部是秒结
                		$secAuto=true;
                		foreach(X("prize_*") as $prizeobj){
                			if($prizeobj->getTleMode()!="s"){
                				$secAuto=false;
                				break;
                			}
                		}
                		if($secAuto){
                			$savedata['state']=1;
                		}
                	}
                }
                if($this->autoGive && $this->autoGiveDelay == 0 && false){
                    //如果开启了自动发放功能.则状态自动设置为已发放
                    $savedata['state']=1;
                }
                 if($this->weekAutoGive && (int)$this->weekGiveDay == date('N',$this->_caltime) && $this->tleMode == 'd' && false){
                    //如果开启了自动发放功能.则状态自动设置为已发放
                    $savedata['state']=1;
                }
                
                //如果查询到当前总账(在秒结可能会出现),则保存,否则添加
                if($thisledger)
                {
                    $m_Ledger->where(array('计算日期'=>array('eq',$iftime)))->save($savedata);
                }
                else
                {
                    $m_Ledger->add($savedata);
                }
            }
        }
        //会员删除
        public function event_userdelete($user)
        {
            M($this->name)->where(array("编号"=>$user["编号"]))->delete();
			M($this->name."构成")->where(array("userid"=>$user["id"]))->delete();
		}
		//系统时间跨日,对秒结进行处理
		public function event_diffTime($time)
		{
			//对所有秒结奖金要做更新
			$uprow='';
			$where='';
			foreach(X('prize_*',$this) as $prize){
				if($prize->prizeMode>=0 && $prize->getTleMode()=='s'){
					$uprow.=','.$prize->name.'本日=0';
					$where.=' or '.$prize->name.'本日<>0';
					
					if (Date('w',$time-86400) == 0){
						$uprow.=','.$prize->name.'本周=0';
						$where.=' or '.$prize->name.'本周<>0';
					}
                    //如果当前结算天为月末这一天,则清空本月业绩
                    if (Date('t',$time) == Date('d',$time)){
						$uprow.=','.$prize->name.'本月=0';
						$where.=' or '.$prize->name.'本月<>0';
                    }
				}
			}
			//清零数据
			if($uprow != '')
			{
				M()->execute('update dms_会员 set '.trim($uprow,',').' where '.trim($where,' or '));
			}
		}
		public function make($name,$user,$val,$memo)
		{
            //设置当前结算状态为秒结形式
            $this->_caltype=0;
            //设置结算日为当日0点
            $this->_caltime=strtotime(date("Y-m-d",systemTime()));
            
            foreach(X('prize_*',$this) as $prize)
            {
            	if($prize->name==$name)
            	{
            		$prize->addprize($user,$val,null,$memo);
            		$prize->prizeUpdate();
            		$this->save();
            		$this->makeLedger();
            		X('user')->callevent('calover',array('tle'=>$this,'caltime'=>$this->_caltime,'type'=>'scal'));
            	}
            }
		}
		public function event_modifyId($oldbh,$newbh)
		{ 
			M()->execute("update dms_{$this->name} set 编号='{$newbh}' where 编号='{$oldbh}'");
			foreach(X('net_rec,net_place') as $net)
			{
				M()->execute("update dms_{$this->name} set {$net->name}_上级编号='{$newbh}' where {$net->name}_上级编号='{$oldbh}'");
			}
		}
		
		//是否含有秒结
		public function haveScal(){
			$calmode=false;
			if($this->tleMode=='s'){
				$calmode=true;
			}else{
                foreach(X('prize_*',$this) as $prize){
                    if($prize->tleMode =='s'){
                        $calmode = true;
                        break;
                    }
                }
            }
            return $calmode;
		}
    }
?>