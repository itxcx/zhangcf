<?php
defined('APP_NAME') || die('不要非法操作哦!');
class RepairAction extends CommonAction {
	//在最上边插入一个新的原始点
	public function newtop()
	{
		if(I("post.newtopbh/s")==""){
			$this->error('请输入编号');
		}
		M()->startTrans();
		$newtop=I("post.newtopbh/s");
		$m_user=M('会员');
		$user = $m_user->where(array('编号'=>$newtop))->lock(true)->find();
		if(!$user)
		{
			$this->error('编号不正确');
		}
		if($user['空点']==0)
		{
			$this->error('要插入的编号必须是一个空点');
		}
		//校验环节
		foreach(X('net_rec,net_place') as $net)
		{
			if($m_user->where(array($net->name.'_上级编号'=>$user['编号']))->find())
			{
				$this->error('此编号作为'.$net->name.'网络的一个上级.不能用于插入原始点');
			}
		}
		//处理环节
		foreach(X('net_rec') as $net)
		{
			//清除本点位对上级的影响
			$net->event_userdelete($user);
			//清空本点位网络数据
			$m_user->where(array('编号'=>$user['编号']))->save(array($net->name.'_上级编号'=>'',$net->name.'_层数'=>1,$net->name.'_网体数据'=>''));
			//对原先原始点设置上级
			M()->execute("update `dms_会员` set ".$net->name."_上级编号='".$user['编号']."' where  ".$net->name."_层数=1 and id<>'".$user['id']."'");
			//对其他人网体数据做批量更新
			M()->execute("update `dms_会员` set ".$net->name."_层数=".$net->name."_层数+1,".$net->name."_网体数据=concat('".$user['id'].",',".$net->name."_网体数据) where ".$net->name."_层数>1 and id<>'".$user['id']."'");
			M()->execute("update `dms_会员` set ".$net->name."_层数=".$net->name."_层数+1,".$net->name."_网体数据='".$user['id']."' where ".$net->name."_层数=1 and id<>'".$user['id']."'");
		}
		foreach(X('net_place') as $net)
		{
			$Branch = $net->getBranch();
			$qu=$Branch[0];
			$net->event_userdelete($user);
			$oldtop=$m_user->where(array($net->name.'_层数'=>1))->find();
			$m_user->where(array('编号'=>$user['编号']))->save(
				array($net->name.'_上级编号'=>'',
				$net->name.'_层数'=>1,
				$net->name.'_网体数据'=>'',
				$net->name.'_位置'=>'',
				$net->name.'_'.$qu.'区'=>$oldtop['编号'])
			);
			M()->execute("update `dms_会员` set ".$net->name."_上级编号='".$user['编号']."',".$net->name."_位置='".$qu."' where  ".$net->name."_层数=1 and id<>'".$user['id']."'");
			M()->execute("update `dms_会员` set ".$net->name."_层数=".$net->name."_层数+1,".$net->name."_网体数据=concat('".$user['id']."-".$qu.",',".$net->name."_网体数据) where ".$net->name."_层数>1 and id<>'".$user['id']."'");
			M()->execute("update `dms_会员` set ".$net->name."_层数=".$net->name."_层数+1,".$net->name."_网体数据='".$user['id']."-".$qu."' where ".$net->name."_层数=1 and id<>'".$user['id']."'");
		}
		M()->commit();
		$this->success('操作完成！');
	}
    public function index()	
    { 
    	$nets=array();
    	$banks=array();
    	$tles=array();
    	$us=array();
    	
    		foreach(X('net_rec,net_place') as $net)
    		{
    			$nets[$net->name] = $net->objPath();
    		}
    		foreach(X('fun_bank') as $bank)
    		{
    			$banks[$bank->name] = $bank->objPath();
    		}
    		foreach(X('tle') as $tle)
    		{
    			$tles[] = array('name'=>$tle->name,'xpath'=>$tle->objPath());
    		}
    		
    	$this->assign('nets' ,$nets );
		$this->assign('banks',$banks);
		$this->assign('tles' ,$tles );
		$this->display();
    }
    //重新计算订单的addval
    public function saleReAddVal()
    {
    	$sales=M('报单')->where(array('id'=>I("get.saleid/d")))->select();
    	if(!$sales)
    	{
    		$this->error('未查到任何订单');
    	}
    	//订单
    	foreach($sales as $sale)
    	{
    		$saleNode = X('sale_*',$sale['报单类别']);
    	}
    	$this->success('操作完成！');
    }
    public function saleReAddVal_ajax()
    {
    	$addset = array();
    	$sales=M('报单')->where(array('id'=>I("get.saleid/d")))->select();
    	foreach($sales as $sale)
    	{
    		$saleNode = X('sale_*',$sale['报单类别']);
    		if(!isset($addset[$sale['saleid']]))
    		{
    			$addset[$saleNode->getPos()] = array('name'=>$saleNode->name,'data'=>$saleNode->getcon('addval',array('form'=>'','to'=>''),true));
    		}
    	}
    	echo json_encode($addset);
	}
    //修复网络关系数据
    public function RepairNetnum()
    {
    	$net   = X('>');
    	$muser = M('会员')->select();
    }
    //修复网络关系人数
    public function CheckNetnum()
    {
    	//总人数
    	$netallnum=array();
    	//团队人数
    	$netnum   =array();
    	$net=X('>');
		M()->startTrans();
    	$m_users = M('会员')->select();
		if(get_class($net)=='net_rec')
		{
			foreach($m_users as $m_user)
			{
				if($m_user[$net->name.'_网体数据']!='')
				{
					$netarr = explode(',',$m_user[$net->name.'_网体数据']);
					foreach($netarr as $uid)
					{
						$uid=(int)$uid;
						$netallnum[$uid]+=1;
						if($m_user['状态']=='有效')
						{
							$netnum[$uid]+=1;
						}
					}
				}
			}
			foreach($m_users as $m_user)
			{
				if(!isset($netallnum[$m_user['id']]))
					$netallnum[$m_user['id']]=0;
				if(!isset($netnum[$m_user['id']]))
					$netnum[$m_user['id']]=0;
				
				if($m_user[$net->name.'_团队人数'] != $netnum[$m_user['id']])
				{
					dump($m_user['编号'].'团队人数异常目前为'.$m_user[$net->name.'_团队人数'].'检测为'.$netnum[$m_user['id']]);
					M('会员')->where(array("编号"=>$m_user['编号']))->save(array($net->name.'_团队人数'=>$netnum[$m_user['id']]));
				}
				if($m_user[$net->name.'_团队总人数'] != $netallnum[$m_user['id']])
				{
					dump($m_user['编号'].'团队总人数异常目前为'.$m_user[$net->name.'_团队总人数'].'检测为'.$netallnum[$m_user['id']]);
					M('会员')->where(array("编号"=>$m_user['编号']))->save(array($net->name.'_团队总人数'=>$netallnum[$m_user['id']]));
				}
				//修改推荐_推荐人数
				$userCount = M('会员')->where(array($net->name."_上级编号"=>$m_user['编号']))->count(); 
				if($m_user[$net->name.'_推荐人数'] != $userCount)
				{
					dump($m_user['编号'].'推荐人数异常目前为'.$m_user[$net->name.'_推荐人数'].'检测为'.$userCount);
					M('会员')->where(array("编号"=>$m_user['编号']))->save(array($net->name.'_推荐人数'=>$userCount));
				}
			}
		}
		M()->commit();
    }
    function renovate()
    {
    	$net=X('>');
    	$net->renovate();
    }
    //修复总账数据
    function ledgerSum()
    {
    	$tle = X('>tle');
    	$startdate = I("get.startdate/s");
    	if($startdate != '')
    	{
    		M()->startTrans();
    		$diffday=floor((systemTime()-strtotime($startdate))/(24*3600));
	    	for($i=1;$i<=$diffday;$i++)
	    	{
	    		$tle->makeLedger(strtotime($startdate)+($i-1)*24*3600);
	    	}
	    	M()->commit();
    	}
    	$this->success('从新处理总账完成！');
    }
}
?>