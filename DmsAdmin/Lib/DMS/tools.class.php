<?php
	class tools{
	/*根据构成信息转移奖金
	@tlename   tle名称
	@prizename 奖金名称
	@frombh    业绩来源编号
	@tobh      要把奖金转移给那个编号
	@caltime   奖金日期，如果不设置则全部转移
	*/
	public static function movetle($tlename,$prizename,$frombh,$tobh,$caltime=null)
	{
		$prize=X('@'.$prizename);
		if(!$prize)
		{
			die('奖金不存在');
		}
		if($prize->prizeMode==2)
		{
			die('扣除类型暂不支持');
		}
		//来源ID
		$fromid=M('会员')->where(array('编号'=>$frombh))->getfield('id');
		if(!$fromid)
		{
			die('来源会员未找到');
		}
		//目标id,用于筛选不符合的记录
		$toid  =M('会员')->where(array('编号'=>$tobh))->getfield('id');
		if(!$toid)
		{
			die('目标会员未找到');
		}
		//查找要转移的构成记录
		$map=array('name'=>$prizename,'fromid'=>$fromid,'userid'=>array('neq',$toid));
		if($caltime!=null)
		{
			$map['dataid'] = array('in',"(select id from dms_".$tlename." where 计算日期=".$caltime.")");
		}  
		//查找要替换的构成信息
		$memos=M($tlename.'构成')->where($map)->select();
		//循环要处理的构成信息
		foreach($memos as $memo)
		{
			//要对现有上级做扣减
			$val = $memo['trueval'];
			//找到要扣除的会员
			$kuser = M('会员')->where(array('id'=>$memo['userid']))->find();
			//对原奖金记录对应的奖金扣减
			M($tlename)->where(array('id'=>$memo['dataid']))->setDec($prizename,$val);
			//修复老会员奖金数据
			self::repairtle($tlename,array('id'=>$memo['dataid']));
			//根据奖金表记录得到结算日期
			$caltime = M($tlename)->where(array('id'=>$memo['dataid']))->getfield('计算日期');
			//判断新会员编号是否需要创建奖金记录
			if(!$caltime)
			{
				die('时间出错');
			}
			$newid = self::maketle($tlename,$tobh,$caltime);
			//对新会员记录奖金额增加
			M($tlename)->where(array('id'=>$newid))->setInc($prizename,$val);
			//修复新奖金记录
			self::repairtle($tlename,array('id'=>$newid));
			M($tlename.'构成')->where(array('id'=>$memo['id']))->save(array('dataid'=>$newid,'userid'=>$toid));
			$caltime = M($tlename)->where(array('id'=>$memo['dataid']))->getfield('计算日期');
		}
	}
	//创建某人在某一个时间的奖金记录，并返回ID，如果存在记录，则会传回现有记录的ID
	public static function maketle($tlename,$bh,$caltime)
	{
		
		//如果没有找到奖金记录
		$data=M($tlename)->where(array('编号'=>$bh,'计算日期'=>$caltime))->find();
		if(!$data)
		{
			$user = M('会员')->where(array('编号'=>$bh))->find();
			$data=array();
			$data['计算日期']=$caltime;
			$data['编号']    =$user['编号'];
			//未完待续
			return M($tlename)->add($data);
		}
		else
		{
			return $data['id'];
		}
	}
	/*根据奖金构成信息，重新修正奖金表奖金
	$tlename  奖金表名称
	$prizename奖金的名称public  function index($tlename,$prizename,$tlewhere)
	$tlewhere 奖金表记录的条件
	*/ 
	public static function getTle($tlename,$prizename,$tlewhere="1=1")
	{
		if(!X("@".$prizename)){
			die('奖金不存在');
		}
		
		//$data=M()->query('select a.*,b.* from dms_'.$tlename.' a left join (select  dataid, sum(trueval) val from dms_'.$tlename.'构成 where name="'.$prizename.'" group by userid ) b 
		//			on a.id=b.dataid where '.$tlewhere.'');
		//查询语句
		$data=M()->query(
			"select a.*,sum(b.trueval) val from  dms_".$tlename." a left join  dms_".$tlename."构成 b on a.id=b.dataid where b.name='".$prizename."' and ".$tlewhere." group by b.dataid"
		);
		foreach($data as $tle)
		{
			if($tle[$prizename]!=$tle['val']){
				//根据构成表修正某个奖金
				M($tlename)->where(array('id'=>$tle['id']))->save(array($prizename=>$tle['val']));
				//修正奖金货币
				self::repairtle($tlename,'id="'.$tle['id'].'"');
			}
		}
	}
	/*
		修复奖金收入和关联的财务数据,如果未发放的奖金会导致发放
		
	*/
	public static function repairTle($tlename,$where)
	{
		$tle = X('@'.$tlename);
        //奖金字段
        $jjrow='';
        //收入字段
        $srrow='';
        foreach(X('prize_*',$tle) as $prize)
        {
        	if($prize->prizeMode > 0){
        		$srrow.='+'.$prize->name;
        		if($prize->prizeMode==1)
        		{
        			$jjrow.='+'.$prize->name;
        		}
        	}
        	//去加号处理
        	//从新处理收入
        }
       	$jjrow=trim($jjrow,'+');
       	$srrow=trim($srrow,'+');
        M($tlename)->where($where)->save(array('奖金'=>array('exp',$jjrow),'收入'=>array('exp',$srrow)));
        $tle->givePrice(M($tlename)->where($where)->select());
	}
	/*日期遍历,在两个日期期间进行循环
	使用方式
	tools::dateeach('2014-1-1','2015-1-1',function ($time){
		dump($time);
	})
	*/
	public static function dateeach($sdate,$edate,$callback)
	{
		$stime=strtotime($sdate);
		$etime=strtotime($edate);
		for($time=$stime;$time<=$etime;$time+=86400)
		{
			call_user_func_array($callback,array($time));
		}
	}	
}
?>