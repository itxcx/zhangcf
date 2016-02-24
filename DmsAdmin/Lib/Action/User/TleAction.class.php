<?php 
defined('APP_NAME') || die(L('不要非法操作哦'));
class TleAction extends CommonAction
{
	public function index(tle $tle)
	{
        $list=new TableListAction($tle->name);
		$list ->table("dms_{$tle->name} as a");
        //隐藏显示天数不等于0的时候
        $holdWhere='';
		//if($obj->dataHold != 0)
		//{
		//	$holdWhere=' and a.计算日期 >=' . (systemTime() - $obj->dataHold * 86400);
		//}
		//if(!$obj->notgiveshow){
			
		//	$holdWhere .= ' and b.state!=0';
		//}
		//用于显示任何一种奖金大于0或者业绩大于0
		$where    =array();
        $where1   =array();
        foreach(X('prize_*',$tle) as $prize1)
        {
            //判定是一种数值计算形奖金(主要为了去除prize_sql)
            if($prize1->prizeMode >= 0){
            //如果会员没有开启allInTle,则判定必须有奖金项金额大于0的情况下.才会增加奖金记录.
                $where1['a.'.$prize1->name]=array('gt',0);
            }
        }
        foreach(X('net_place') as $net_place)
        {
            foreach($net_place->getcon("region",array("name"=>"")) as $nameconf)
            {
            	$where1['a.' . $net_place->name."_".$nameconf['name']."区本日业绩"]=array('gt',0);
            }
        }
        $where1['_logic']="or";
        //
        $where['a.编号']  =$this->userinfo["编号"];
        if(!$tle->notgiveshow){
        	$list->join(C('DB_PREFIX').$tle->name.'总账 as b on a.计算日期=b.计算日期')->field('a.*');
        	$where['b.state']=array('NEQ',0);
        }
		if(I("post.startTime/s")!=''){
			$where['a.计算日期'][] = array('egt',strtotime(I("post.startTime/s")));
		}
		if(I("post.endTime/s")!=''){
			$where['a.计算日期'][] = array('lt',strtotime(I("post.endTime/s"))+3600*24);
		}
        $where['_complex']=$where1;
        $list ->where($where)->order("a.计算日期 desc,a.id desc");
        $list ->setShow = array(
            L('计算日期')=> array("row"=>array(array(&$this,"getDateUrl"),"[id]","[计算日期]")),
            L('奖金')=> array("row"=>'[奖金]','sum'=>'奖金'),
            L('收入')=> array('row'=>'[收入]','sum'=>'收入'),
        );
		foreach(X('net_place') as $netPlace){
			$region=array();
			$netName = $netPlace->name;
			$addRowArr = array(array(&$this,"getAddRow"));
			$addRowStr = '';
			$remianRowArr = array(array(&$this,"getAddRow"));
			$remianRowStr = '';
			$totalRowArr = array(array(&$this,"getAddRow"));
			$totalRowStr = '';
			foreach($netPlace->getcon("region",array("name"=>"")) as $nameconf)
			{
				$region[] =  $nameconf['name'];
				array_push($addRowArr,'['.$netName.'_'.$nameconf['name'].'区本日业绩]');
				$addRowStr .= $netName.'_'.$nameconf['name'].'区本日业绩+';
				array_push($remianRowArr,'['.$netName.'_'.$nameconf['name'].'区结转业绩]');
				$remianRowStr .= $netName.'_'.$nameconf['name'].'区结转业绩+';
				array_push($totalRowArr,'['.$netName.'_'.$nameconf['name'].'区累计业绩]');
				$totalRowStr .= $netName.'_'.$nameconf['name'].'区累计业绩+';
 			}
			$list ->addshow("新增业绩",array('row'=>$addRowArr));
			if($this->is_BumpPrize()){
				$list ->addshow("结转业绩",array('row'=>$remianRowArr));
			}
			$list ->addshow("累计业绩",array('row'=>$totalRowArr));
		}
		$prizeStr = '';
        foreach(X('prize_*',$tle) as $prize)
        {
        	if($prize->prizeMode>=0 && $prize->userDisp==true)
        	{
				$list ->addShow(L($prize->byname),array('row'=>'['.$prize->name.']','sum'=>'a.'.$prize->name));
            }
        }
        $data = $list->getData();
        $this->assign('data',$data);
		$this->assign('xpath',$tle->objPath());
        $this->display();
        
	}
	public function getAddRow(){
		$args = func_get_args();
		$str = '';
		foreach($args as $val){
			$str .= floatval($val).'/';
		}
		return  trim($str,'/');
	}
    function getDateUrl($str1,$str2){
        $str2 = date("Y-m-d",$str2);
        return  '<a href="__GROUP__/Tle/prizeForm:__XPATH__/id/'.$str1.'">'.$str2.'</a>';
        if(CONFIG('USER_PRIZE_SWITCH')){
        	return  '<a href="__GROUP__/Tle/prizeForm:__XPATH__/id/'.$str1.'">'.$str2.'</a>';
        }else{
        	return  $str2;
        }
    }
	public function prizeForm(tle $tle)
	{
		//加载类
       	import('DmsAdmin.DMS.SYS.PrizeData');
       	$id    = I("request.id/s");
		//取得奖金记录
		$m_tle = M($tle->name);
		$m_tle->where(array("id"=>$id));
		$tlddata=$m_tle->find();
       	$data=array();$undata=array();
        foreach(X('prize_*') as $prize)
        {
        	if($prize->prizeMode>=0  && $prize->userDisp==true  && $prize->isSee==true)
        	{
        		$fromlist = PrizeData::getprizedata($tlddata['计算日期'],$tle->name,$prize->name,$this->userinfo['id'],$prize->byname,$prize->unmemo);
        		/*if(count($fromlist['prize'])>0)
	    		{
	    			$data[] = array("name"=>$prize->byname,"list"=>$fromlist['prize'],"TK"=>isset($fromlist['TK'])?$fromlist['TK']:array(),"PK"=>isset($fromlist['PK'])?$fromlist['PK']:array(),"KW"=>isset($fromlist['KW'])?$fromlist['KW']:array());
	    		}*/
	    		if(is_array($fromlist)){
	      			foreach($fromlist['prize'] as $key=>$val){
	        			$fromlist['prize'][$key]['lv'] = $this->printUserLevel($val['lv'],$val['lvname']);
	        		}
	       			if(count($fromlist['prize'])>0)
	        		{
	        			$data[$prize->name] = array("name"=>$prize->byname,"list"=>$fromlist['prize'],"TK"=>isset($fromlist['TK'])?$fromlist['TK']:array(),"PK"=>isset($fromlist['PK'])?$fromlist['PK']:array(),"KW"=>isset($fromlist['KW'])?$fromlist['KW']:array());
	        		}
					if($prize->unmemo)
					{
		        		if(count($fromlist['prizeup'])>0)
		        		{
		        			$undata[] = array("name"=>$prize->byname,"list"=>$fromlist['prizeup']);
		        		}
	        		}
        		}
        	}
        }
        $this->assign("data",$data);
        $this->display();
	}

	public function fun_fuli(fun_fuli $fun_fuli)
	{
		$data=array();
		$where['编号']=$this->userinfo['编号'];
		$rs=M($fun_fuli->name)->where($where)->select();
		if($rs == null)
		{
			foreach($fun_fuli->getcon('con',array('name'=>'','wheremsg'=>'')) as $con)
			{
            //  $data[$con['name']]=array(
				   //   'msg'=>$con['wheremsg'],
				   //   'state'=>'0',
				  //);
			}
		}else{
			foreach($rs as $fuli)
			{
				$data[$fuli['name']]=array(
					'msg'=>'恭喜您获得该奖励',
					'state'=>$fuli['state'],
				    );
			}
		}
        $this->assign('data',$data);
		$this->display();
	}
}
?>