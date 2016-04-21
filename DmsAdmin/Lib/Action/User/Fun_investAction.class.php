<?php
defined('APP_NAME') || die('不要非法操作哦');
/*
方法表
put              添加收款
putSave          添加付款完成
get              添加收款
getSave          添加收款完成
rem              查看我作为付款方的匹配明细(全部或者针对某一条付款记录的明细),要改为全能明细表
add_rem_two      进行付款方的打款确认操作页面
rem_save_two     进行打款确认执行
putList          付款记录查看
putNotStatusList 付款记录查看(显示未匹配的记录)
putRevoke        付款记录撤销
getList          收款列表
getRevoke        收款记录撤销
*/
class Fun_investAction extends CommonAction {
    private function chkpass2($inputname)
    {
        if($_SESSION['__super_admin'])
        {
            return;
        }
        $pwdtwo = I("post.".$inputname."/s");//二级密码
        if(empty($pwdtwo) || md100($pwdtwo)!=$this->userinfo['pass2'])
        {
        	$this->error(L("二级密码错误！"));
        }
    }
	//mmm付款页面
    public function put(){
        $fun = X('fun_invest@');
        $this->assign('putFeeName','');
        if($fun->putFeeBank != '')
        $this->assign('putFeeName',X('fun_bank@'.$fun->putFeeBank)->byname);//赋值扣的货币的别名
        
        $this->assign('putFee'     ,$fun->putFee);     //报单手续费
		$this->assign('putMultiple',$fun->putMultiple);//整数额
		$this->assign('putMax'     ,$fun->putMax);     //最大额
        $this->display();
    }
    //mmm付款执行
    public function putSave()
    {
    	B('XSS');
        $fun = X('fun_invest@');
        $money  = I("post.tzmoney/d");//资助金额
        $this->chkpass2('oldpwd2');
        //未删除的,
        $map = array(
            '编号'=>$this->userinfo['编号'],
            '状态'=>array('in',array(REMIT_NOT,REMIT_GIVE,REMIT_CONFIRM)),
        );
        M()->startTrans();
        $get = M('mmm收款')->where($map)->find();
    	$put = M('mmm付款')->where($map)->find();
        //如果已经交易中付款,则要提示
        if($put && !$fun->putingDoPut)
        {
        	$this->error(L("存在未完成匹配申请记录，暂不能申请！"));
        }
        //如果有收款交易,同时不允许在收款交易时挂付款,则提示
        if($get && !$fun->getingDoPut)
        {
            $this->error(L("存在未完成匹配申请记录，暂不能申请！"));
        }
        
        if( empty($money) || !is_numeric($money) || 
            $money<=0     || $money > $fun->putMax ||  
            $money % $fun->putMultiple !=0)
        {
            $this->error(L("投资金额错误！"));
        }
        //判断本次投资是否必须大于上次投资的指定倍数
        if($fun->putGtLast)
        {
            $lastMoney = M('mmm付款')->where(array('编号'=>$this->userinfo['编号']))->order('id desc')->getField('总金额');
            //比例
            if($money < $lastMoney * $fun->putGtLast * 0.01)
            {
                $this->error(L("投资金额不能小于上次投资金额的".$fun->putGtLast."%！"));
            }
        }
        //存在报单货币处理
        if($fun->putFee>0)
        {
            if(bankget($fun->putFeeBank,$this->userinfo['编号']) < $fun->putFee)
            {
            	$this->error(L($fun->putFeeBank).L("余额不足"));
            }
            $bank = X('fun_bank@'.$fun->putFeeBank);
            //===========================================
            bankset($fun->putFeeBank,$this->userinfo['编号'],- $fun->putFee,'提供资助扣除',date('Y-m-d h:i:s',systemTime()).' '.$this->userinfo['编号'].'初始排队扣除'.$bank->byname);
        }
		//进入mmm付款表
        $xh=M('mmm付款')->where(array('编号'=>$this->userinfo['编号'],'状态'=>array('neq',MATCH_REVOKE)))->count()+1;
        $data = array(
        	'userid'    =>$this->userinfo['id'],
        	'编号'      =>$this->userinfo['编号'],
        	'总金额'    =>$money,
            '待匹配金额'=>$money,
        	'添加时间'  =>systemTime(),
        	'状态'      =>0,
        	'钱包编号'  =>$this->getWalletNumber('mmm付款'),
            '信誉度'    =>$this->userinfo['信誉度'],
            '序号'      =>$xh
        );
        $res = M('mmm付款')->add($data);
        $put = M('mmm付款')->find($res);
        X('fun_invest@')->runevent('put_add',array('P'=>$put),'会员增加初始排队');
        //执行一次自动匹配--此处取消，放到commonaction中
        //写入会员操作日志
        $this->userlog('初始排队'.$money);
        M()->commit();
        $this->success(L('资助成功'));
    }

	//mmm收款页面
	public function get(){
        $fun = X('fun_invest@');
        $bank= X('fun_bank@'.$fun->getBank);
        $this->assign('getBankVal' ,bankget($fun->getBank,$this->userinfo['编号']));//用于收款的代币余额
		$this->assign('getBankName',$bank->byname);    //货币名称
        $this->assign('getMultiple',$fun->getMultiple);//整数倍
        $this->display();
    }
    //mmm收款执行
    public function getSave()
    {
    	B('XSS');
        $fun=X('fun_invest@');
        $money = I("post.money/d");//提现金额
        $this->chkpass2('oldpwd2');
        if( empty($money) || !is_numeric($money) || $money<=0 ||  $money % $fun->getMultiple != 0){
            $this->error(L("提现金额错误！"));
        }
        //提现上限设置
        if($fun->getMax >0 && $money>$fun->getMax)
        {
            $this->error(L("单次提现金额上限为：").$fun->getMax);
        }
        $map = array(
            '编号'=>$this->userinfo['编号'],
            '状态'=>array('in',array(REMIT_NOT,REMIT_GIVE,REMIT_CONFIRM)),
        );
        //修改，存在未完成的 提供资助 或者 mmm收款 记录就不可以再进行提供资助或mmm收款
        M()->startTrans();
        $get = M('mmm收款')->where($map)->find();
    	$put = M('mmm付款')->where($map)->find();
        if($put || $get)
        {
        	$this->error(L("存在未完成匹配申请记录，暂不能申请！"));
        }
        //取得出局钱包货币
        $bank = X('fun_bank@'.$fun->getBank);
        if(bankget($fun->getBank,$this->userinfo['编号']) < $money)
        {
        	$this->error(L($fun->getBank).L("余额不足"));
        }
        //序号设置为未撤销记录的数量+1,由于部分交易不能撤销.所以序号不会有什么大问题.
        $xh = M('mmm收款')->where(array('编号'=>$this->userinfo['编号'],'状态'=>array('neq',MATCH_REVOKE)))->count()+1;
		$data = array(
        	'编号'      =>$this->userinfo['编号'],
        	'总金额'    =>$money,
            '待匹配金额'=>$money,
        	'添加时间'  =>systemTime(),
			'解冻时间'  =>systemTime() + (3600 * $fun->getFrozenTime),
			'状态'      =>0,
            '信誉度'    =>$this->userinfo['信誉度'],
            '序号'      =>$xh,
        );
        $res = M('mmm收款')->add($data);
        bankset($fun->getBank,$this->userinfo['编号'],-$money,L('接受资助扣款'),L('接受资助扣款').'id:'.$res);
        $get = M('mmm收款')->find($res);
        X('fun_invest@')->runevent('get_add',array('G'=>$get),'会员接受资助');
        $this->userlog('接受资助'.$money);
         M()->commit();
        //有冻结期，此处不执行匹配
        //写入会员操作日志
        $this->success(L('接受资助排队成功'));
	}
	//汇款通知列表
	public function rem()
	{
        $fun=X('fun_invest@');
        $list = new TableListAction('mmm匹配');
        //显示类型
        $type = I("get.type/s");
        $where = array();
        if($type == 'put')
        {
        	$where['a.付款会员'] =  $this->userinfo['编号'];
            if(I("get.id/d"))
           	$where['a.付款id']   =  I("get.id/d");
        }
        if($type == 'get')
        {
        	$where['a.收款会员'] =  $this->userinfo['编号'];
            if(I("get.id/d"))
           	$where['a.收款id']   =  I("get.id/d");
        }
        if(!$where)
        {
        	$where['a.付款会员'] =  $this->userinfo['编号'];
        	$where['c.删除'] =  0;
        	$where['d.删除'] =  0;
        }
        
        $list->join(" a inner join dms_mmm付款 c on a.付款id = c.id inner join dms_mmm收款 d on a.收款id = d.id inner join dms_会员 b on a.收款会员=b.编号 ")
        ->field('a.*,b.微信账号,b.移动电话,b.支付宝账号')->where($where)->order("a.id asc");
        $data = $list->getData();
        //添加申诉情况的显示
        if(isset($data['list']) && count($data['list'])>0)
        {
        	foreach($data['list'] as $k=>&$d)
        	{
        		$str = $d['申诉']!=''?L('提供资助方申诉中'):L('提供资助方未申诉');
        		$str .= $d['提现申诉']!=''?L(' | 接受资助方申诉中'):L(' | 接受资助方未申诉');
        		$d['申诉情况'] = $str;
        	    //操作项设计
                $dolink=array();
                //付款链接
                if($this->userinfo['编号'] == $d['付款会员'])
                {
            	    if($d['状态'] == REMIT_NOT)
                        $dolink[] = '<a href="__URL__/add_rem_two/id/'.$d['id'].'">'.L('汇款').'</a>';
                    //查看收款人信息,以及申诉.
                    if($d['状态'] != REMIT_CONFIRM && $d['状态'] != REMIT_REVOKE)
                    {
                        $dolink[] = '<a href="__URL__/showGetUser/id/'.$d['id'].'">'.L('收款人信息').'</a>';
                        $dolink[] = '<a href="__URL__/putAppeal/id/'.$d['id'].'">'.L('申诉').'</a>';
                    }
                }
                //收款确认链接
        	    if($this->userinfo['编号'] == $d['收款会员'])
                {
                    if($d['状态'] != REMIT_CONFIRM && $d['状态'] != REMIT_REVOKE)
                    {
                        $dolink[] = '<a href="__URL__/showPutUser/id/'.$d['id'].'">'.L('付款人信息').'</a>';
                    }
                    //确认收款按钮
                    if($d['状态'] == REMIT_GIVE)
                    {
                        $dolink[] = '<a href="__URL__/rem_accok/id/'.$d['id'].'" onclick="return confirm(\'"'.L('确认收款吗?').'"\')">'.L('确认收款').'</a>';
                        //在确认收款的情况下直接可以申诉
                        $dolink[] = '<a href="__URL__/getAppeal/id/'.$d['id'].'">'.L('申诉').'</a>';
                    }
                    //在打款方未打款的情况下申诉
                    if($d['状态'] == REMIT_NOT)
                    {
                        if($d['延时'] == 0)
                        {
                            $dolink[] = '<a href="__URL__/addTime/id/'.$d['id'].'">'.L('延时').'</a>';
                        }
                        //匹配到目前为止的时间
                        $timeval = (systemTime()-$d['匹配时间']);
                        if($timeval>$fun->remitNotGetUserAppealHour * 3600)
                        {
                            
                            //可以申诉
                            $dolink[] = '<a href="__URL__/getAppeal/id/'.$d['id'].'">'.L('申诉').'</a>';
                        }
                        else
                        {
                            //得到倒计时信息
                            $timeval = (systemTime()-$d['匹配时间']);
                            $timeval = $fun->remitNotGetUserAppealHour * 3600 - $timeval;
                            //计算剩余的小时
                            $hour    = intval($timeval/3600);
                            $timeval-=($hour*3600);
                            //计算剩余的分钟
                            $min =intval($timeval/60);
                            $dolink[] = $hour.'小时'.$min.'分钟后可申诉';
                        }
                    }
                }
                $d['操作']=join(' | ',$dolink);
        	}
        }
        $this->assign('data',$data);
		$this->display();
	}
	//添加汇款
	function add_rem_two(){
        X('fun_invest@');
		$id = I("get.id/d");
		$remit = M('mmm匹配')->where(array('id'=>$id))->find();
        if(!$remit || $remit['付款会员'] != $this->userinfo['编号'] || $remit['状态'] != REMIT_NOT)
        {
            $this->error(L('该打款记录不存在'));
        }
		$this->assign('remit',$remit);
		$this->display();
	}
	function rem_save_two(){
        //防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
        $fun_invest = X('fun_invest@');
		B('XSS');
        M()->startTrans();
	  	$m = M('mmm匹配');
        $remit = $m->where(array('id'=>I("post.id/d"),'付款会员'=>$this->userinfo['编号']))->find();
        if(!$remit)$this->error(L('该汇款记录不存在'));
        if($remit['状态'] != MATCH_NOT)$this->error(('该汇款记录已经打款'));

		if(trim(I("post.开户名/s"))==''){
			$this->error(L('汇款开户名不能为空'));
		}
		if(trim(I("post.银行流水/s"))==''){
			$this->error(L('银行流水不能为空'));
		}
		$data	= $m->create();
		if($data===false){
			$this->error();
		}
        $remit['汇款时间'] = systemTime();
        //重新设置收款方超时
        $remit['超时时间'] = systemTime()+ ($fun_invest->remitGiveTimeout * 3600);
        $remit['状态']     = REMIT_GIVE;
		$m->save($remit);
        M()->commit();
		$this->success(L('操作成功'),__URL__."/rem/id/".$remit['id']);
	}
    
    
    
	//自己的mmm付款列表(显示所有状态的)
    public function putList()
    {
        $fun = X('fun_invest@');
		$list = new TableListAction('mmm付款');
		$list->table('dms_mmm付款');
		$list->join(" a inner join dms_会员 as b on b.编号=a.编号");
		$list->where("a.编号='".$this->userinfo['编号']."' and a.删除=0");
        $list->order("a.id asc");
        $list->field("a.*,b.姓名");
        $list->title="申请资助列表";            // 列表标题
        $list->pagenum=15;                   // 每页显示数量  默认20
        $list->order  ="a.id desc";
        $list->addshow(L('参与者')    ,array("row"=>"[编号]")); 
        $list->addshow(L('类型')      ,array("row"=>L("申请资助"))); 
        $list->addshow(L('申请金额')  ,array("row"=>"[总金额]")); 
		$list->addshow(L('已匹配金额'),array("row"=>"[已匹配金额]"));
		$list->addshow(L('已成交金额'),array("row"=>"[已完成金额]"));
		$list->addshow(L('状态')      ,array("row"=>array(array(&$fun,"matchStatus"),"[状态]")));
        $list->addshow(L('创建时间')  ,array("row"=>"[添加时间]","format"=>"time"));
		$list->addshow(L('操作')    ,array("row"=>array(array(&$this,"doLink")     ,"[状态]","[id]",'put')));
		$data = $list->getData(); 
		$this->assign('data',$data);
		$this->display();
    }
	//自己的mmm付款列表(显示未匹配的)
    public function putNotStatusList()
    {
        $fun = X('fun_invest@');
		$list = new TableListAction('mmm付款');
		$list->table('dms_mmm付款');
		$list->join(" a inner join dms_会员 as b on b.编号=a.编号");
		$list->where(" a.状态=0 and a.删除=0 and a.编号='".$this->userinfo['编号']."'");
        $list->order("a.id asc");
        $list->field("a.*,b.姓名");
        $list->title="爱心提供列表";            // 列表标题
        $list->pagenum=15;                   // 每页显示数量  默认20
        $list->order  ="a.id desc";
        $list->addshow(L('钱包编号'),array("row"=>"[钱包编号]")); 
        $list->addshow(L('投资日期'),array("row"=>"[添加时间]","format"=>"time"));
		$list->addshow(L('状态')    ,array("row"=>array(array(&$fun ,"matchStatus"),"[状态]")));
		$list->addshow(L('操作')    ,array("row"=>array(array(&$this,"doLink")     ,"[状态]","[id]",'put')));
		$data = $list->getData(); 
		$this->assign('data',$data);
		//前面的等待人数：1000为一人
		$paiduiNum = M('mmm付款')->where(array('状态'=>0,'删除'=>0))->sum('总金额');
        $paiduiNum = floor($paiduiNum/$fun->listMoney);
        $paiduiNum += $fun->listNumAdd;
		$this->assign('paiduiNum',$paiduiNum);
		$this->display();
    }
    //操作
    public function doLink($state,$id,$type){
                $fun=X('fun_invest@');
                $lookRemit=false;
                //局部或者完全匹配时.可以显示匹配信息
                if($state == MATCH_PART_OK || MATCH_ALL_OK)
                {
                    $lookRemit = true;
                }
                if($state == MATCH_COMPLETE && $fun->dispMatchComplete)
                {
                    $lookRemit = true;
                }
                if($state == MATCH_REVOKE && $fun->dispMatchCancel)
                {
                    $lookRemit = true;
                }
                $ret = "";
                if($lookRemit)
                    $ret = "<a href='__URL__/rem/type/".$type."/id/{$id}'>".L('查看匹配')."</a>";
                if($state == MATCH_NOT && $fun->putUserRevoke)
                {
                    $ret.="&nbsp;&nbsp;<a href='__URL__/".$type."Revoke/id/{$id}'>".L('撤销')."</a>";
                }
                return $ret;
            }
    //投资撤销 保单币不退 
    public function putRevoke(){
        //防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
        $fun=X('fun_invest@');
        $id=I("get.id/d");//会员编号
        M()->startTrans();
        $put=M("mmm付款")->where(array('id'=>$id,'编号'=>$this->userinfo['编号']))->find();
        if(!$put){
            $this->error(L("记录不存在"));
        }
        if($put['状态'] != MATCH_NOT){
            $this->error(L("此记录不可撤销"));
        }
        if(!$fun->putUserRevoke)
        {
            $this->error(L("未开启撤销功能"));
        }
        $fun->event_revoke_put($put,'put_user_revoke','会员手动撤销');
        $this->userlog('撤销投资'.$put['id']);
        M()->commit();
        $this->success(L("撤销投资成功！"),__URL__.'/putList');
    }
    
    //待收款列表
    public function getList()
    {
        $fun = X('fun_invest@');
        $list = new TableListAction('mmm收款');
		$list->table('dms_mmm收款');
        $list->join(" a  inner join dms_会员 c on a.编号=c.编号");
		$list->where(" a.编号='".$this->userinfo['编号']."' and a.删除=0");
        $list->field("a.*,c.姓名");
        $list->title="接受资助列表";            // 列表标题
        $list->pagenum=15;                   // 每页显示数量  默认20
        $list->order  ="a.id desc";
        $list->addshow(L('申请者')    ,array("row"=>"[编号]"));
		$list->addshow(L('类型')      ,array("row"=>L("接受资助")));
        $list->addshow(L('申请金额')  ,array("row"=>"[总金额]"));
		$list->addshow(L('已匹配金额'),array("row"=>"[已匹配金额]"));
		$list->addshow(L('已获金额')  ,array("row"=>"[已完成金额]"));
		$list->addshow(L('交易状态')  ,array("row"=>array(array(&$fun,"matchStatus"),"[状态]")));
        $list->addshow(L('添加时间')  ,array("row"=>"[添加时间]","format"=>"time"));  
        $list->addshow(L('解冻时间')  ,array("row"=>"[解冻时间]","format"=>"time"));  
        $list->addshow(L('操作')      ,array("row"=>array(array(&$this,"doLink"),"[状态]","[id]","get")));
		$data = $list->getData(); 
		$this->assign('data',$data);
		$this->display();
    }
    //提现撤销 金额退回出局钱包 
    public function getRevoke(){
        $id=I("get.id/d");//会员编号
        M()->startTrans();
        $get = M('mmm收款')->where(array('id'=>$id,'编号'=>$this->userinfo['编号']))->find();
        if(!$get){
            $this->error(L("记录不存在"));
        }
        if($get['状态'] != MATCH_NOT){
            $this->error(L("此记录不可撤销"));
        }
        $fun_invest = X('fun_invest@');
        $fun_invest->event_revoke_get($get,'get_user_revoke','会员手动撤销');
        $this->userlog('撤销提现'.$txinfo['id']);
        M()->commit();
        $this->success(L("撤销提现成功！"),__URL__.'/getList');
    }
	//正式排队列表   目前显示所有自己的记录
    public function zhengshipaiduiList(fun_invest $fun_invest)
    {
		$list = new TableListAction('正式排队');
		$list->table('dms_正式排队');
		$list->join(" a  inner join dms_会员 c on a.所属编号=c.编号");
		//查询自己最老的未出具的点位id
	//0127修改，显示自己的   
		$list->where(" a.所属编号='".$this->userinfo['编号']."'");
        $list->field("a.*,c.姓名");
        $list->title="正式排队列表";            // 列表标题
        $list->pagenum=15;                   // 每页显示数量  默认20
        $list->order  ="a.id desc";
		//$list->addshow("ID",array("row"=>"[id]")); 
        $list->addshow(L('钱包编号'),array("row"=>"[钱包编号]"));         
      //  $list->addshow(L('姓名')    ,array("row"=>"[姓名]"));
        $list->addshow(L('序号')  ,array("row"=>"[序号]"));     
		$list->addshow(L('上级序号')  ,array("row"=>"[上级序号]"));  
		$list->addshow(L('本金')  ,array("row"=>"[本金]"));  
		$list->addshow(L('利息')  ,array("row"=>"[利息]"));   
        $list->addshow(L('添加时间'),array("row"=>"[添加时间]","format"=>"time"));  
        $list->addshow(L('出局时间'),array("row"=>"[出局时间]","format"=>"time"));  
        $list->addshow(L('状态')    ,array("row"=>array(array(&$this,"showstatus"),"[状态]")));  
		$data = $list->getData(); 
	//dump($data);die;
		$this->assign('data',$data);
		$this->display();
    }
	public function showstatus($status)
	{
		if($status==0)return L("正常");
		if($status==1)return L("封顶出局");
		if($status==2)return L("超时出局");
        if($status==3)return L("锁定");
	}
	//
	//提交打款,这里主要更新状态, 暂时不进入模块

	//删除汇款通知----不提供删除操作
	//mmm收款记录查看匹配
	public function getremFromJieshou(fun_invest $fun_invest)
	{
        $list = new TableListAction('mmm匹配');
        $where = array();
        if(I("get.jieshouid/d")){
        	$where['a.收款会员'] =  $this->userinfo['编号'];
           	$where['a.收款id']   =  I("get.jieshouid/d");
        }else
		{
			$this->error(L('不存在匹配记录'));
		}
        $list ->where($where)->order("a.id asc");
        $list->join(" a left join dms_会员 b on a.付款会员=b.编号");
        $list->field("a.*,b.移动电话");
        $data = $list->getData();
        //添加申诉情况的显示
        if(isset($data['list']) && count($data['list'])>0)
        {
        	foreach($data['list'] as $k=>$d)
        	{
        		$str = $d['申诉']!=''?L('提供资助方申诉中'):L('提供资助方未申诉');
        		$str .= $d['提现申诉']!=''?L(' | 接受资助方申诉中'):L(' | 接受资助方未申诉');
        		$data['list'][$k]['申诉情况'] = $str;
        	}
        }
        $this->assign('data',$data);
        $this->assign('nowTime',systemTime());
		$this->display();
	}
	
    //确认收款
	public function rem_accok()
	{
        $fun_invest=X('fun_invest@');
        $m = M('mmm匹配');
        $where['id'] = $_GET['id'];
        M()->startTrans();
        $remlist = $m -> where($where) -> find();
        if($remlist['收款会员'] != $this->userinfo['编号']){
            $this -> error(L('您不是该记录的收款人，系统自动重新加载收款列表！'),__URL__.'/rem/type/get');
        }
        if($remlist['状态'] == REMIT_NOT){
            $this -> error(L('汇款人尚未汇款！'));
        }
        if($remlist['状态'] == REMIT_CONFIRM){
            $this -> error(L('汇款已审核过，不可重新审核！'));
        }
        if($remlist['状态'] == REMIT_REVOKE){
            $this -> error(L('汇款已撤销！'));
        }
        
        $fun_invest->confirm($remlist['id']);
        $this->userlog('确认收款，打款人:'.$remlist['编号']);
		M()->commit();
        $this -> success(L("确认成功！"));
    }
    //延时操作页面
	public function addTime()
	{
	 	$bh = I('get.bh/s');
	 	$id = I('get.id/d');
		$this->assign('bh',$bh);
		$this->assign('id',$id);
		$this->display();
    }
    //延时操作保存
    public function addTimeSave()
    {
    	//判断未汇款，未延时----》执行延时，超时时间增加，延时更新为1
    	$m = M('mmm匹配');
        
        $where['id'] = I('post.id/d');
        $addhours    = I('post.addhours/d');
        if($addhours<0)
        {
            $this->error(L('延期时间至少大于0'));
        }
        M()->startTrans();
        $remlist = $m -> where($where) -> find();
        if($remlist['收款会员'] != $this->userinfo['编号']){
            $this -> error(L('您不是该记录的收款人，系统自动重新加载收款列表！'),__URL__.'/rem/type/get');
        }
        if($remlist['状态'] != 0){
            $this -> error(L('未汇款才可以延时操作！'));
        }
        if($remlist['延时'] == 1){
            $this -> error(L('已经延时过，不可再延时！'));
        }
        $m->where($where)->save(array('延时'=>1,'超时时间'=>array('exp','超时时间+'.$addhours.'*3600')));
        M()->commit();
        $this->success(L("延时成功！"),__URL__.'/rem/type/get');
    }
    //生成随机不重复编号
    public function getWalletNumber($m_name)
    {
    	$codeSet = '2345678abcdefhijkmnpqrstuvwxyz';
    	$ret = '';
		for($i = 0; $i < 8; $i++)
		{
			$ret .= $codeSet[mt_rand(0, 29)];
		}	
		$result = M($m_name)->lock(true)->where(array('钱包编号'=>$ret))->find();
		if($result){
			return $this->getWalletNumber($m_name);
		}
		return $ret;
    }
    //显示提供资助者信息
    public function showPutUser()
    {
        $id    = I("get.id/d");
        $remit = M('mmm匹配')->where(array('id'=>$id,'收款会员'=>$this->userinfo['编号']))->find();
        if(!$remit)
        {
            $this->error('记录不存在');
        }
        //如果记录已经撤销。则没有必要在查看收款人信息
        if($remit['状态'] == REMIT_CONFIRM || $remit['状态'] == REMIT_REVOKE)
        {
            $this->error('此匹配已完成');
        }
        $res = M('会员')->join("a left join dms_会员 b on a.推荐_上级编号=b.编号")->where(array('a.编号'=>$remit['付款会员']))->Field("a.*,b.昵称 tjnc,b.移动电话 tjphone")->find();
    	$this->assign('data',$res);
    	$this->display();
    }
    //提供资助记录  查询匹配 显示 收款人信息
    public function showGetUser()
    {
        $id    = I("get.id/d");
        $remit = M('mmm匹配')->where(array('id'=>$id,'付款会员'=>$this->userinfo['编号']))->find();
        if(!$remit)
        {
            $this->error('记录不存在');
        }
        //如果记录已经撤销。则没有必要在查看收款人信息
        if($remit['状态'] == REMIT_CONFIRM || $remit['状态'] == REMIT_REVOKE)
        {
            $this->error('此匹配已完成');
        }
        $res = M('会员')->join("a left join dms_会员 b on a.推荐_上级编号=b.编号")->where(array('a.编号'=>$remit['收款会员']))->Field("a.*,b.昵称 tjnc,b.移动电话 tjphone")->find();
    	$this->assign('data',$res);
    	$this->display();
    }
    //提供资助  申诉
    public function putAppeal()
    {
    	$this->assign('cid',I("get.id/d"));
    	$this->display();
    }
    public function putAppealSave()
    {
    	if(I("post.shensu/s")==""){
			$this->error(L('申诉不能为空'));
		}
    	$data['申诉'] = I("post.shensu/s");
    	M()->startTrans();
        $remit = M('mmm匹配')->where(array('id'=>I("post.cid/s"),'付款会员'=>$this->userinfo['编号']))->find();
        if(!$remit)
        {
            $this->error(L('未找到记录'));
        }
        if($remit['状态']!=REMIT_NOT && $remit['状态']!=REMIT_GIVE)
        {
            $this->error(L('状态不正确。'));
        }
    	$result = $remit = M('mmm匹配')->where(array('id'=>I("post.cid/s")))->save($data);
		if($result){
			M()->commit();
			$this->success(L('提交成功'));
		}else{
			M()->rollback();
			$this->error(L('提交失败'));
		}
    }
    //提供资助  申诉
    public function getAppeal()
    {
    	$this->assign('cid',I("get.id/d"));
    	$this->display();
    }
    public function getAppealSave()
    {
        $fun = X('fun_invest@');
    	if(I("post.shensu/s")==""){
			$this->error(L('申诉不能为空'));
		}
        
    	//if(count($_FILES)>0){
		//	$res_pro1=$this->upload();
		//	if(isset($res_pro1["error"]) && $res_pro1["error"]==1){
		//     	$this->error($res_pro1['message']);
		//     }
		//}
    	//$data['提现申诉图片'] =	isset($res_pro1['c_file']) ? $res_pro1['c_file']:'';	
    	$data['提现申诉'] = I("post.shensu/s");	
    	M()->startTrans();
        $remit = M('mmm匹配')->where(array('id'=>I("post.cid/s"),'收款会员'=>$this->userinfo['编号']))->find();
        if(!$remit)
        {
            $this->error(L('未找到记录'));
        }
        if($remit['状态'] != REMIT_NOT && $remit['状态'] != REMIT_GIVE)
        {
            $this->error(L('状态不正确。'));
        }
        //在未付款的情况下的时间限制
        if($remit['状态'] == REMIT_NOT)
        {
            $timeval = (systemTime() - $remit['匹配时间']);
            if($timeval < $fun->remitNotGetUserAppealHour * 3600)
            {
                $this->error(L('未到申诉时间'));
            }
        }
    	$result = M('mmm匹配')->where(array('id'=>I("post.cid/s")))->save($data);
		if($result){
			M()->commit();
			$this->success(L('提交成功'));
		}else{
			M()->rollback();
			$this->error(L('提交失败'));
		}
    }
    	//图片保存
	public function upload($allowExts=array('jpg', 'gif', 'png', 'jpeg'))
	{
		
        import("ORG.Util.UploadFile");
        $upload						= new UploadFile();                         // 实例化上传类
        $upload->maxSize			= 3145728;                                   // 默认允许上传的附件大小(3M)
        $upload->allowExts			= $allowExts;								// 默认允许上传的附件类型
        $upload->thumb				= true;                                     // 是否对图片进行缩略处理
        $upload->thumbPrefix        = 't_';										// 默认缩略图前缀
        $upload->thumbRemoveOrigin  = true;										// 默认缩略图片并删除原图
        $upload->thumbMaxWidth      = '600';									// 默认缩略图的最大宽度
        $upload->thumbMaxHeight     = '600';									// 默认缩略图的最大高度
        $upload->savePath           = "./Public/Uploads/".date('Ym/d/');


        if(!file_exists_case($upload->savePath)) 
        {
            mk_dir($upload->savePath);  //如果目录不存在自动创建目录
        }

         
        if(!$upload->upload()) 
        { 
            // 上传错误提示错误信息
    
			return  json_encode(array('error' => 1, 'message' => $upload->getErrorMsg()));
			exit;
        }
        else 
        {
            // 上传成功获取上传文件信息
            $info		= $upload->getUploadFileInfo();
        
            foreach($info as $key=>$val){
            	$info[$info[$key]['key']]=$val['thumbfile'];
            }
            return $info;

        }
	}



    //正式排队页面
	public function zhengshipaidui(){
        $this->display();
    }
    //正式排队执行
    public function zhengshipaiduiSave()
    {
    	B('XSS');
        $dianwei = I("post.dianwei/d");//点位
        $this->chkpass2('oldpwd2');
        if( empty($dianwei) || !is_integer($dianwei) || $dianwei<=0 ){
            $this->error(L("点位数错误！"));
        }
        //开启事务
        M()->startTrans();
		$totalmoney = $dianwei * 1000;
		if(bankget('交易钱包',$this->userinfo['编号'])<$totalmoney)
		{
			M()->rollback();
			$this->error(L("交易钱包余额不足！"));
		}
		//扣除交易币
		X('fun_bank@交易钱包')->set($this->userinfo['编号'],$this->userinfo['编号'],-$totalmoney,'正式排队扣除',date('Y-m-d h:i:s',systemTime()).' '.$this->userinfo['编号'].'正式排队扣除交易钱包'.$totalmoney);
		//进点位
		$zhengshipaiduiObj = M('正式排队');
		$lastData = $zhengshipaiduiObj->where('true')->order('序号 desc')->limit(1)->find();
		$maxXuhao = $lastData['序号'];
		$maxCengshu = $lastData['层数'];
	//	$zspdcjts = CONFIG('zspdcjts');20160111取消超时出局
		for($i=1;$i<=$dianwei;$i++)
		{
			$maxXuhao++;
			$maxCengshu++;
			$zhengshipaiduiData = array(
				'userid'=>$this->userinfo['id'],
				'所属编号'=>$this->userinfo['编号'],
				'序号'=>$maxXuhao,
				'上级序号'=>$maxXuhao-1,
				'层数'=>$maxCengshu,
				'本金'=>1000,
				'利息'=>0,
				'添加时间'=>systemTime(),
	//			'出局时间'=>systemTime()+3600*24*$zspdcjts,         20160111取消超时出局
				'状态'=>0,//1表示封顶出局，2表示超时出局
				'钱包编号'=>$this->getWalletNumber('正式排队'),
			);
			$addres = $zhengshipaiduiObj->add($zhengshipaiduiData);
			if(!$addres)
			{
				M()->rollback();
				$this->error(L("点位插入数据库失败"));
			}
			//触发秒结秒发（见点，封顶，超时出局，发放）
			$this->jiandianScal($addres);
		}
        $this->userlog('正式排队点位数'.$dianwei);
		M()->commit();
        $this->success(L('正式排队成功'));
	}
	//正式排队见点奖金计算
	public function jiandianScal($newid)
	{
		$m_zhengshipaidui = M('正式排队');
		$bankobj = X('fun_bank@出局钱包');
		$newRecord = $m_zhengshipaidui->where(array('id'=>$newid))->find();
		//计算见点奖，存入利息，记录见点奖金来源表，封顶记录本金利息进入出局钱包，更新状态
		$idarray = $m_zhengshipaidui->where("状态=0 and id!=".$newid)->field('序号')->select();
		if($idarray)
		{
			//获取配置参数
			$dttjjje = CONFIG('dttjjje');
			$dttjjfd = CONFIG('dttjjfd');
			//利息增加
			$m_zhengshipaidui->where("状态=0 and id!=".$newid)->setInc('利息',$dttjjje);
			//封顶执行
			$fengdingNum = $m_zhengshipaidui->where(array('利息'=>array('gt',$dttjjfd),'状态'=>0))->save(array('利息'=>$dttjjfd));
			//封顶出局
			$topOutDatas = $m_zhengshipaidui->where(array('利息'=>$dttjjfd,'状态'=>'0'))->select();
			if($topOutDatas)
			{
				foreach($topOutDatas as $topOutData)
				{
                    $user = M('会员')->where(array('编号'=>$topOutData['所属编号']))->find();
                    X('fun_bankdelay@')->event_valadd($user,$topOutData['本金'],array('bankmode'=>'本金','bankmemo'=>'封顶出局本金'));
                    X('fun_bankdelay@')->event_valadd($user,$topOutData['利息'],array('bankmode'=>'利息','bankmemo'=>'封顶出局利息'));
				}
			}
			//更新超时出局状态
			$m_zhengshipaidui->where(array('利息'=>$dttjjfd,'状态'=>'0'))->save(array('状态'=>1,'出局时间'=>systemTime()));
			//存入见点未封顶记录
			$idstr = '';
			foreach($idarray as $v)
			{
				$idstr .= $v['序号'].',';
			}
			$idstr = trim($idstr,',');
			$detailData = array(
				'序号'=>$newRecord['序号'],
				'拿见点序号'=>$idstr,
				'见点金额'=>$dttjjje,
				'封顶数'=>$fengdingNum
			);
			$resend = M('见点奖金来源')->add($detailData);
		}
	}
}
?>