<?php
    //各种状态常量
    //匹配状态  0未匹配 5部分匹配 10匹配成功 20 投资成功 30投资撤销
    define("MATCH_NOT"     ,0);
    define("MATCH_PART_OK" ,1);
    define("MATCH_ALL_OK"  ,2);
    define("MATCH_COMPLETE",3);
    define("MATCH_REVOKE"  ,4);
    //匹配状态状态
    define("REMIT_NOT"    ,0);//0未打款
    define("REMIT_GIVE"   ,1);//1已打款
    define("REMIT_CONFIRM",2);//2已收款
    define("REMIT_REVOKE" ,3);//3已撤销
    
    define("APPEAL_NOT"    ,0);//未处理的申诉
    define("APPEAL_PUT"    ,1);//申述支持打款方
    define("APPEAL_GET"    ,2);//申述支持收款方
    //注意:系统存在付款或者收款记录处于撤销状态,但是匹配打款还处于已打款状态,但一定是处于仲裁
	//3M投资模块
	class fun_invest extends stru
	{	
        public $name='mmm';
        //会员活动冻结时限
        public $userTimeout=48;
        //打款前审核时间
        public $remitNotTimeout=24;
        //打款后审核时间
        public $remitGiveTimeout=24;
        //接受资助冻结时间
        public $getFrozenTime=72;
        //报单手续费0为不扣除
        public $putFee=100;
        //报单手续费的货币钱包
        public $putFeeBank="报单币";
        //提供资助金额倍数
        public $putMultiple=500;
        //单次提供资助最大金额
        public $putMax = 50000;
        //提供资助是否必须大于上一次投资的指定倍数，0为不限
        public $putGtLast  =0;
        //接受资助倍数
        public $getMultiple=500;
        //接受资助倍数
        public $getMax=50000;
        //注册以后默认的信誉值
        public $regCredit=12;
        //控制调整部分--------------------------------------
        //每日匹配总额(单位万)
        public $dayRemitMax=0;
        //每次自动执行匹配次数
        public $tickRemitNum=10;
        //资助完成是否显示匹配明细
        public $dispMatchComplete = true;
        //资助完成是否显示匹配明细
        public $dispMatchCancel   = true;
        //在资助期间可以继续提供资助
        public $putingDoPut       = false;
        //在接受资助期间可以提供资助
        public $getingDoPut       = true;
        //接受资助次数必须小于提供资助次数
        public $getEltPut         = true;
		//接受资助的金额是上次提供资助金额的N倍,如果为0则关闭限制
        public $getvalPutvalRatio = 0;
        //在付款方没有付款的情况下,收款人可以进行申诉的时限,单位为小时
        public $remitNotGetUserAppealHour = 22;
        //会员是否允许撤销提供资助
        public $putUserRevoke     = true;
        //会员是否允许撤销接受资助
        public $getUserRevoke     = true;        
        //货币名称，如果不是一个fun_bank则报错
		public $getBank='出局钱包';
        //排队人数显示分母
        public $listMoney  = 1000;
        public $listNumAdd = 0;
        //投资查上级对应的网络名称
        public $netName = '';
        //开启自动匹配
        public $autoMatch = true;
        public $matchMode = 'many2many';
		public function event_sysclear()
		{
			M()->execute("TRUNCATE TABLE " . "dms_mmm匹配;");
			M()->execute("TRUNCATE TABLE " . "dms_mmm付款;");
			M()->execute("TRUNCATE TABLE " . "dms_mmm收款;");
			M()->execute("TRUNCATE TABLE " . "dms_正式排队;");
			M()->execute("TRUNCATE TABLE " . "dms_信誉记录;");
			M()->execute("TRUNCATE TABLE " . "dms_见点奖金来源;");
			//M()->execute("TRUNCATE TABLE " ."dms_动态奖金转出暂存表;");
		}
        //定时执行代码，一般为一分钟执行一次
        public function event_tick()
        {
            //对超时记录进行超时处理------------------------------------------------------
            //最多一次性处理100个冻结
            $matchs = M('mmm匹配')->where(
            array(
              '超时时间'=>array('elt',systemTime()),
              '状态'=>array('in',array(REMIT_NOT,REMIT_GIVE)),
              '申诉'=>'',
              '提现申诉图片'=>'',
              '申诉图片'=>'',
              '提现申诉'=>''))->limit(100)->select();
            if($matchs)
            foreach($matchs as $match)
            {
                //如果要是没有付款,则冻结付款人
                if($match['状态'] == REMIT_NOT)
                {
                    $this->event_frozenuser($match['付款会员'],$match['id'].'付款超时');
                }
                //如果是已经打款,则冻结收款人
                if($match['状态'] == REMIT_GIVE)
                {
                    $this->event_frozenuser($match['收款会员'],$match['id'].'收款超时');
                }
            }
            //会员超时处理,最多一次处理50人
            if($this->userTimeout > 0)
            {
                $users = M('会员')->where("mmm超时>0 and mmm超时<".systemTime())->limit(50)->field('编号')->select();
                foreach($users as $user)
                {
                    dump('处理超时会员'.$user['编号']);
                    $this->event_frozenuser($user['编号'],'会员超时'); 
                }
            }
            //开启自动匹配---------------------------------------------------------------
            if($this->tickRemitNum>0)
                $this->runAutoMatch();
        }
        //冻结某一个用户
        public function event_frozenuser($userbh,$memo='')
        {
            M('会员')->where(array('编号'=>$userbh))->save(array('登陆锁定'=>1,'备注'=>$memo)); 
            //清空信用度
            $this->event_creditChange($userbh,0);
            //付款方冻结流程
            //所有待匹配金额都变为撤销金额
            //所有匹配了并没有打款的用户,被起诉方算撤销,支持放算待匹配
            //已经匹配,并打款的,自动进行申诉处理
            //撤销冻结用户付款记录
            //撤销时包含了撤销状态，因为如果倍冻结人有其他的付款但未确认的匹配
            //会导致付款被撤销，但是匹配处于有效并申诉的阶段，如果申诉完成并在处理
            //重新调用这里时，就需要考虑被撤销的付款
            $puts = M('mmm付款')->where(array('编号'=>$userbh,'状态'=>array('in',MATCH_NOT.','.MATCH_PART_OK.','.MATCH_ALL_OK.','.MATCH_REVOKE)))->select();
            foreach($puts as $put)
            {
                
                $this->event_revoke_put($put,'froze');
            }
            //撤销冻结用户的收款记录
            $gets = M('mmm收款')->where(array('编号'=>$userbh,'状态'=>array('in',MATCH_NOT.','.MATCH_PART_OK.','.MATCH_ALL_OK.','.MATCH_REVOKE)))->select();
            foreach($gets as $get)
            {
                $this->event_revoke_get($get,'froze'); 
            }
        }
        //信用值产生变化
        public function event_creditChange($userbh,$val)
        {
            M('会员')   ->where(array('编号'=>$userbh))->save(array('信誉度'=>$val));
            M('mmm付款')->where(array('编号'=>$userbh))->save(array('信誉度'=>$val));
            M('mmm收款')->where(array('编号'=>$userbh))->save(array('信誉度'=>$val));
        }
        //撤销付款记录
        public function event_revoke_put($put,$type)
        {
            $updata=array('id'=>$put['id']);
            //如果存在未匹配的金额,则全部进行撤销
            if($put['待匹配金额']>0)
            {
                $put['已撤销金额'] += $put['待匹配金额'];
                $put['待匹配金额'] = 0;
            }
            //撤销已匹配记录
            $matchs=M('mmm匹配')->where(array('付款id'=>$put['id']))->select();
            if($matchs)
            foreach($matchs as $match)
            {
                //如果撤销的付款的某个匹配还没有打款,则可以撤销
                if($match['状态']==REMIT_NOT)
                {
                    $put['已匹配金额']-=$match['金额'];
                    $put['已撤销金额']+=$match['金额'];
                    //设置状态为撤销
                    M('mmm匹配')->where(array('id'=>$match['id']))->save(array('状态'=>REMIT_REVOKE));
                    //对收款方的处理
                    $get=M('mmm收款')->find($match['收款id']);
                    $getupdata=array(
                        '已匹配金额'=>$get['已匹配金额']-$match['金额'],
                        '待匹配金额'=>$get['待匹配金额']+$match['金额'],
                    );
                    if($get['状态'] == MATCH_ALL_OK)
                    {
                        $getupdata['状态'] = MATCH_PART_OK;
                    }
                    M('mmm收款')->where(array('id'=>$match['收款id']))->save($getupdata);
                }
                //如果要撤销的付款方已经打款，则自动按照仲裁处理
                if($match['状态']==REMIT_GIVE)
                {
                    //预先设定备注,如果没有找到有效类型,则已类型传参作为备注
                    $memo=$type.'类型处理';
                    //设置状态为撤销
                    if($type == 'froze')
                    {
                        $memo='收款方被冻结，需进行仲裁';
                    }
                    M('mmm匹配')->where(array('id'=>$match['id']))->save(array('申诉'=>'付款方被冻结，需进行仲裁'));
                }
                //如果没有其他匹配，则设置为状态撤销
            }
            //设置为撤销状态
            $put['状态'] = MATCH_REVOKE;
            //保存状态
            M('mmm付款')->save($put);
            $this->runevent($type,array('P'=>$put));
        }
        //撤销收款记录
        public function event_revoke_get($get,$type,$memo='')
        {
            $updata=array('id'=>$get['id']);
            //如果存在未匹配的金额,则全部进行撤销
            if($get['待匹配金额']>0)
            {
                $get['已撤销金额'] += $get['待匹配金额'];
                $get['待匹配金额'] = 0;
            }
            //撤销已匹配记录
            $matchs=M('mmm匹配')->where(array('收款id'=>$get['id']))->select();
            if($matchs)
            foreach($matchs as $match)
            {
                //如果撤销的付款的某个匹配还没有打款,则可以撤销
                if($match['状态']==REMIT_NOT)
                {
                    $get['已匹配金额']-=$match['金额'];
                    $get['已撤销金额']+=$match['金额'];
                    //设置状态为撤销
                    M('mmm匹配')->where(array('id'=>$match['id']))->save(array('状态'=>REMIT_REVOKE));
                    //对收款方的处理
                    $put=M('mmm付款')->find($match['付款id']);
                    $putupdata=array(
                        '已匹配金额'=>$put['已匹配金额']-$match['金额'],
                        '待匹配金额'=>$put['待匹配金额']+$match['金额'],
                    );
                    if($put['状态'] == MATCH_ALL_OK)
                    {
                        $putupdata['状态'] = MATCH_PART_OK;
                    }
                    M('mmm付款')->where(array('id'=>$match['付款id']))->save($putupdata);

                }
                //如果要撤销的付款方已经打款，则自动按照仲裁处理
                if($match['状态']==REMIT_GIVE)
                {
                    //预先设定备注,如果没有找到有效类型,则已类型传参作为备注
                    $memo=$type.'类型处理';
                    //设置状态为撤销
                    if($type == 'froze')
                    {
                        $memo='付款方被冻结，自动进行申诉';
                    }
                    M('mmm匹配')->where(array('id'=>$match['id']))->save(array('申诉'=>'收款方被冻结，需进行申诉处理'));
                }
                //如果没有其他匹配，则设置为状态撤销
            }
            //设置为撤销状态
            $get['状态'] = MATCH_REVOKE;
            //保存状态
            M('mmm收款')->save($get);
            $this->runevent($type,array('G'=>$get));
        }
        //执行手动匹配
        public  function runManualMatch($putids,$getids)
        {
            //付款方
            $puts = M('mmm付款')->where(array(
                      '状态'=>array('in',array(MATCH_NOT,MATCH_PART_OK)),
                      '删除'=>0,
                      'id'=>array('in',$putids)
                    ))
                    ->order('添加时间 asc,信誉度 desc,待匹配金额 desc')
                    ->getField('id idkey,id,编号,待匹配金额,状态');
            //收款方
            //自动匹配有解冻时间,手动匹配不做时间验证
            $gets = M('mmm收款')->where(array(
                       '状态'=>array('in',array(MATCH_NOT,MATCH_PART_OK)),
                       '删除'=>0,
                       'id'=>array('in',$getids),
                       '待匹配金额'=>array('gt',0)
                    ))
                    ->order('添加时间,信誉度 desc,待匹配金额 desc')
                    ->getField('id idkey,id,编号,待匹配金额,状态');
			$className = 'fun_invest_' . $this->matchMode;
			//对参数增加当前网体和会员信息,
            $args = array(&$puts,&$gets);
			//应用匹配算法类
			import('DmsAdmin.DMS.fun_invest.'.$className);
			$matchs = call_user_func_array(array($className,'match'),$args);
            //得到匹配结果,进行匹配更新处理
            foreach($matchs as $match)
            {
                $this->evenet_makematch($match[0],$match[1],$match[2],'手动匹配');
            }
            //集中更新
            $this->evenet_makematch();
        }
        //自动匹配
        private function runAutoMatch()
        {
            //付款方
            $puts = M('mmm付款')->where("(状态=" .MATCH_NOT . " or 状态=" .MATCH_PART_OK . " or 状态=" .MATCH_ALL_OK . ") and 删除=0 and 待匹配金额>0 ")
                    ->order('添加时间 asc,信誉度 desc,待匹配金额 desc')
                    ->limit($this->tickRemitNum)->getField('id idkey,id,编号,待匹配金额,状态');
            //收款方
            $gets = M('mmm收款')->where("(状态=". MATCH_NOT ." or 状态=". MATCH_PART_OK . " or 状态=" .MATCH_ALL_OK . ") and 删除=0 and 待匹配金额>0 and 解冻时间<=".systemTime())
                    ->order('添加时间,信誉度 desc,待匹配金额 desc')
                    ->limit($this->tickRemitNum)->getField('id idkey,id,编号,待匹配金额,状态');
            dump('付款记录:'.count($puts));
            dump('收款记录:'.count($gets));
            //对日匹配上限的判定
            //匹配总量封顶,-1为不限
            $maxVal=-1;
            //如果有设置了封顶
            if($this->dayRemitMax)
            {
                //统计本日匹配总额
                $stime=strtotime(date('Y-m-d'         ,systemTime()));
                $etime=strtotime(date('Y-m-d 23:59:59',systemTime()));
                $map=array(
                    '匹配时间'=>array('between',array($stime,$etime)),
                );
                $dayRemit = M('mmm匹配')->where($map)->sum('金额');
                !$dayRemit && $dayRemit = 0;
                //计算分钟化的当前可匹配金额
                //设置值乘以1万,除以一天总分钟数,乘以目前本日已经过分钟数
                $maxVal = $this->dayRemitMax * 10000 / 1440 * (date('H',systemTime())*60+date('i',systemTime()));
                //当天匹配额，减去已匹配额
                $maxVal -= $dayRemit;
                //如果为负数,则默认为0
                $maxVal < 0 && $maxVal = 0;
            }
            //如果为0就表示不用再匹配了
            if($maxVal == 0)
                return;

			$className = 'fun_invest_' . $this->matchMode;
			//对参数增加当前网体和会员信息
            $args = array(&$puts,&$gets,$maxVal);
			//应用匹配算法类
			import('DmsAdmin.DMS.fun_invest.'.$className);
			$matchs = call_user_func_array(array($className,'match'),$args);
            //得到匹配结果,进行匹配更新处理
            foreach($matchs as $match)
            {
                $this->evenet_makematch($match[0],$match[1],$match[2],'自动匹配');
            }
            //集中更新
            $this->evenet_makematch();
        }
        //进行具体的匹配数据处理
        //自动匹配手动匹配都走这个环节
        //传入的直接为读取好的付款和收款记录信息
        private function evenet_makematch($putid=null,$getid=null,$val=0,$memo='')
        {

            //由于匹配期间,要使用bsave减少数据库操作,而同时又需要根据ID读取记录,
            //所以读取的记录要缓存起来,以便在入库之前可以获得最新的数据
            //缓存的key是id
            static $putcache = array();
            static $getcache = array();
            //集中更新
            if($putid === null)
            {
                M('mmm付款')->bupdate();
                M('mmm收款')->bupdate();
                M('mmm匹配')->bupdate();
                $putcache=array();
                $getcache=array();
                return;
            }
            if(!isset($putcache[$putid]))
                $putcache[$putid]=M('mmm付款')->find($putid);
            if(!isset($getcache[$getid]))
                $getcache[$getid]=M('mmm收款')->find($getid);
            //对双方的待匹配额度更新
            $put = &$putcache[$putid];
            $get = &$getcache[$getid];
            if($val==0)
            {
                dump($put);
                dump($get);
                throw_exception("匹配金额不应为0");
            }
            if(!$put || !$get)
                die('匹配处理未找到数据');
            if($put['状态'] != MATCH_NOT && $put['状态'] != MATCH_PART_OK)
            {
                dump($put);
                die('付款方数据异常');
            }
            if($get['状态'] != MATCH_NOT && $get['状态'] != MATCH_PART_OK)
            {
                dump($get);
                die('收款方数据异常');
            }
            //额度处理
            $put['待匹配金额'] -= $val;$put['已匹配金额'] += $val;
            $get['待匹配金额'] -= $val;$get['已匹配金额'] += $val;
            //状态处理
            $put['状态'] = $put['已匹配金额']==$put['总金额'] ? MATCH_ALL_OK : MATCH_PART_OK;
            $get['状态'] = $get['已匹配金额']==$get['总金额'] ? MATCH_ALL_OK : MATCH_PART_OK;
            //保存数据
            M('mmm付款')
            ->bsave(array(
              'id'        =>$putid,
              '待匹配金额'=>$put['待匹配金额'],
              '已匹配金额'=>$put['已匹配金额'],
              '状态'      =>$put['状态'],
            ));
            M('mmm收款')
            ->bsave(array(
              'id'        =>$getid,
              '待匹配金额'=>$get['待匹配金额'],
              '已匹配金额'=>$get['已匹配金额'],
              '状态'      =>$get['状态'],
            ));
            $getuser = M('会员')->where(array('编号'=>$get['编号']))->find();
            //创建匹配记录
            $data=array(
                '付款id'        =>$put['id'],
                '收款id'        =>$get['id'],
                '付款会员'      =>$put['编号'],
                '收款会员'      =>$get['编号'],
		        '汇入账户卡号'  =>$getuser['银行卡号'],
		        '汇入账户开户行'=>$getuser['开户银行'],
		        '汇入账户开户名'=>$getuser['开户名'],
		    	'匹配时间'      =>systemTime(),
		    	'超时时间'      =>systemTime() + $this->remitNotTimeout * 3600,
                '金额'          =>$val,
                '备注'          =>$memo,
                '状态'=>0
            );
            //插入匹配记录
            M('mmm匹配')->badd($data);
        }
        public function confirm($matchid)
        {
            $match = M('mmm匹配')->where(array('id'=>$matchid))->find();
            if($match['状态'] == REMIT_NOT)
                return '汇款人未汇款';
            if($match['状态'] == REMIT_CONFIRM)
                return '汇款已审核过，不可重新审核';
            if($match['状态'] == REMIT_CONFIRM)
                return '汇款已撤销';
            M('mmm匹配')->where(array('id'=>$matchid))->save(array('状态'=>2,'确认时间'=>systemTime()));
            //计算匹配到付款期间的小时数
            $givehour = ($match['汇款时间'] - $match['匹配时间'])/3600;
            //读取信誉度设置
            $credits = $this->getcon('credit',array('minhour'=>0,'maxhour'=>0,'val'=>0));
            foreach($credits as $credit)
            {
                //如果付款时间在判断范围之内,则进行信誉值处理
                if($credit['minhour']<=$givehour && ($credit['maxhour']>$givehour || $credit['maxhour'] == 0))
                {
                    //信誉值设定是绝对值,所以需要先取得当前会员信誉
                    $putuser = M('会员')->where(array('编号'=>$match['付款会员']))->find();
                    $this->event_creditChange($match['付款会员'],$credit['val']);
                }
            }
            //对双方记录的处理
            $put = M('mmm付款')->find($match['付款id']);
            $get = M('mmm收款')->find($match['收款id']);
            $put['已匹配金额']-=$match['金额'];
            $get['已匹配金额']-=$match['金额'];
            $put['已完成金额']+=$match['金额'];
            $get['已完成金额']+=$match['金额'];
            M('mmm付款')->where(array('id'=>$put['id']))->save(array('已匹配金额'=>$put['已匹配金额'],'已完成金额'=>$put['已完成金额']));
            M('mmm收款')->where(array('id'=>$get['id']))->save(array('已匹配金额'=>$get['已匹配金额'],'已完成金额'=>$get['已完成金额']));
            //付款完成状态确认
            if($put['已匹配金额']==0 && $put['待匹配金额']==0)
            {
                M('mmm付款')->where(array('id'=>$put['id']))->save(array('状态'=>MATCH_COMPLETE));
                $this->runevent('put_confirm',array('M'=>$match,'P'=>$put,'G'=>$get));
            }
            //收款完成状态确认
            if($get['已匹配金额']==0 && $get['待匹配金额']==0)
            {
                M('mmm收款')->where(array('id'=>$get['id']))->save(array('状态'=>MATCH_COMPLETE));
                $this->runevent('get_confirm',array('M'=>$match,'P'=>$put,'G'=>$get));
            }
            //单条匹配确认
            $this->runevent('confirm',array('M'=>$match,'P'=>$put,'G'=>$get));
            return '';
        }
    //执行事件处理
    //类型,数据,备注
    public function runevent($type,$data,$memo)
    {
        /*触发事件,type类型
           confirm      某一个匹配被审核
           put_confirm  某一个付款记录整体完成
           get_confirm  某一个收款记录整体完成、
        */
        $addcons=$this->getcon('addval',array('type'=>'','tofrom'=>'','to'=>'','where'=>'','val'=>''),true);
        foreach($addcons as $con)
        {
            //类型不匹配
            if($con['type'] != $type)
                continue;
            //判断不匹配
            if(!transform($con['where'],array(),$data))
                continue;
            //计算值
            $val = transform($con['val'],array(),$data);
            if($con['user'] === 'put')
                $user=M('会员')->where(array('编号'=>$data['P']['编号']))->find();
            if($con['user'] === 'get')
                $user=M('会员')->where(array('编号'=>$data['G']['编号']))->find();

            if(!$user)
                throw_exception('进行addval操作时未找到对应会员数据');
            
            
            if(isset($con['bankmemo']))
            {
                $con['bankmemo']=transform($con['bankmemo'],array(),$data,false);
            }
            runadd($user,$val,$con['to'],$con);
        }
        if($type == 'put_add' || $type == 'put_confirm' || $type=='put_user_revoke')
        {
            $user=M('会员')->where(array('编号'=>$data['P']['编号']))->find();
            $this->runutime($user,$type,$memo);
        }
        if($type == 'get_add' || $type == 'get_confirm' || $type=='get_user_revoke')
        {
            $user=M('会员')->where(array('编号'=>$data['G']['编号']))->find();
            $this->runutime($user,$type,$memo);
        }
    }
    //倒计时处理,会员,类型,备注
    function runutime($user,$type,$memo)
    {
        if($this->userTimeout==0)
            return ;
        
        
        
        //进行时间处理
        //一个时间处理固定模型,原本是在config配置里边实现,后来发现意义不大
        $utimes = array(
            'userverify'     =>'start '.$this->userTimeout,//会员审核
            'put_add'        =>'pause',
            'get_add'        =>'pause',
            'put_user_revoke'=>'unpause',
            'get_user_revoke'=>'unpause',
            'put_confirm'    =>'stop',
            'get_confirm'    =>'start '.$this->userTimeout,
        );
        foreach($utimes as $settype => $setdata)
        {
            //类型不匹配
            if($settype != $type)
                continue;
            $set = explode(' ',$setdata);
            //初始化定义日志表数据
            if($memo==null)
            {
                $memo="";
            }
            $log=array(
                '编号'    =>$user['编号'],
                '类型'    =>$type,
                '备注'    =>$memo,
                '添加时间'=>systemTime(),
            );
            
            //开启倒计时
            if($set[0] == 'start')
            {

                //得到超时终止时间
                $time = systemTime()+$set[1]*3600;
                $log['设定时间']=$time;
                $setdata=array('mmm超时'=>$time,'mmm超时记录'=>$time);
                //如果有收款或付款交易中的记录.都不能算超时
                if(M('mmm收款')->where(array('编号'=>$user['编号'],'状态'=>array('lt',MATCH_COMPLETE)))->find() ||
                   M('mmm付款')->where(array('编号'=>$user['编号'],'状态'=>array('lt',MATCH_COMPLETE)))->find()
                   )
                {
                    $setdata['mmm超时']=0;
                    $log['备注'].=',因有其他进行匹配记录,只更新暂停缓存';
                }
                M('会员')->where(array('id'=>$user['id']))->save($setdata);
            }
            //停止倒计时
            if($set[0] == 'stop')
            {
                M('会员')->where(array('id'=>$user['id']))->save(array('mmm超时'=>0,'mmm超时记录'=>0));
            }
            if($set[0] == 'pause')
            {
                M('会员')->where(array('id'=>$user['id']))->save(array('mmm超时'=>0));
            }
            if($set[0] == 'unpause')
            {
                M('会员')->where(array('id'=>$user['id']))->save(array('mmm超时'=>array('exp','`mmm超时记录`')));
            }
            M('mmm倒计时')->add($log);
        }
    }
        //用户激活事件,用于处理类型为userverify格式的倒计时
    function event_user_verify($user)
    {
        
        $this->runutime($user,'userverify','会员注册');
    }
    //取得订单状态
    function matchStatus($state)
    {
        $data=array(
        MATCH_NOT     =>'等待匹配',
        MATCH_PART_OK =>'部分交易',
        MATCH_ALL_OK  =>'交易中'  ,
        MATCH_COMPLETE=>'已成交'  ,
        MATCH_REVOKE  =>'投资撤销',
        );
        return $data[$state];
    }
    function remitStatic($state)
    {
        $data=array(
        REMIT_NOT     =>'未打款',
        REMIT_GIVE    =>'已打款',
        REMIT_CONFIRM =>'已收款',
        REMIT_REVOKE  =>'已撤销',
        );
        return $data[$state];
    }
    function timeStatic($state)
    {
        $data=array(
        0     =>'未延时',
        1     =>'已延时',
        );
        return $data[$state];
    }
}
?>