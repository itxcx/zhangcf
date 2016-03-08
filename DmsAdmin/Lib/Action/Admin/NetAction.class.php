<?php
// 网络图
defined('APP_NAME') || die('不要非法操作哦!');
class NetAction extends CommonAction 
{	
	function _filter()
    {
		parent::_filter();
	}
	/*
	* 网络图修改第一步
	*/
	public function editList()
	{
		$this->display();
	}
	
	/*
	*网络修改记录
	*/
	public function editLog(){
		$list=new TableListAction('log_user');
		$list->table("dms_log_user as a");
		$list->field('a.id,user_id,content,a.create_time,编号,admin_id,c.account')->where("content like '移动%'");
		$list->join('dms_会员 as b on a.user_id=b.id');
		$list->join('admin as c on a.admin_id=c.id');
		$list->order("a.id desc");
		
		$button=Array("修改"=>array("class"=>"edit","href"=>__APP__."/Admin/Net/editList","target"=>"navTab","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_edit.png'));
		$list->setButton = $button;
		$list->showPage=true;
		
		$list->addshow("操作人编号",array("row"=>'[account]'));
		$list->addshow("会员编号",array("row"=>'[编号]'));
		$list->addshow("修改内容",array("row"=>'[content]'));
		$list->addshow("修改时间",array("row"=>"date('Y-m-d H:i:s',[create_time])"));
		$this->assign('list',$list->getHtml());
		$this->display();
	}
	
	/*
	* 网络图修改第二步
	*/
	public function edit()
	{
		if(trim(I("request.uid/s"))==''){
			$this->error($this->userobj->name."编号不能为空!");
		}
		$uid				= trim(I("request.uid/s"));
		$userModel			= M('会员');
		$userInfo			= $userModel->where(array('编号'=>$uid))->find();
		
		if( !$userInfo )
		{
			$this->error($uid."  不存在!");
		}

		$userNetList		= array(); //用户网体列表
		foreach(X('net_rec,net_place') as $net)
		{
			$userNetList[ $net->name ]['tag'] = get_class($net);
			if( get_class($net) == 'net_place' )
			{
				$poses=$net->getBranch();
				$userNetList[ $net->name ]['ramus']= $poses;
			}
		}
		$this->assign('name',$this->userobj->name);
		$this->assign('userNetList',$userNetList);
		$this->assign('userInfo',$userInfo);
		$this->display();
	}

	/*
	* 网络图修改第三步:保存
	*/
	public function editSave()
	{
		//获取会员资料
		$user	= M('会员')->where(array('编号'=>trim(I("post.uid/s"))))->find();
		if( !$user ) $this->error("资料不存在!");
		set_time_limit(0);
		ini_set('memory_limit','2000M');
		M()->startTrans();
		M('会员')->lock(true)->count();
		//循环安置网
		foreach(X('net_place,net_rec') as $net)
		{
			$newup=M('会员')->where(array('编号'=>trim(I("post.".$net->name."_上级编号/s")),"状态"=>'有效'))->find();
			$region=I("post.".$net->name."_位置/s");
			$ret=$net->move($user,$newup,$region);
			if($ret !== true)
			{
				$this->error($ret);
			}
			/**** 保存日志 - 结束 ****/
		}
		M()->commit();
		$this->assign('jumpUrl',__URL__.'/edit/uid/'.trim(I("post.uid/s")));
		$this->saveAdminLog('','',"网络修改");
		$this->success("网络修改成功");
	}
	/*
	*删除网体下所有会员
	*/
	public function delNetDown()
	{
		$this->display();
	}
	//判断会员是否可以删除
	public function delNetDowncfm()
	{
		if(trim(I("request.uid/s"))==''){
			$this->error($this->userobj->name."编号不能为空!");
		}
		//获取会员的信息是否存在
		$uid				= trim(I("request.uid/s"));
		$userModel			= M('会员');
		$userInfo			= $userModel->where(array('编号'=>$uid))->find();
		if( !$userInfo )
		{
			$this->error(" $uid 不存在!");
		}
		$admin=M()->table("admin");
        $result=$admin->where(array('id'=>$_SESSION[C('RBAC_ADMIN_AUTH_KEY')]))->field("password")->find();
        if(!chkpass(I("request.password/s"),$result['password'])){
           $this->error("管理员密码错误");
        }
		/************************************************/
		//到user.class.php中执行删除操作down_delete  得到返回的值
		M()->startTrans();
		$result=$this->userobj->down_delete($userInfo['id']);
		//将返回的所删除的会员编号数组转换成字符串
		$delstr=implode(",",$result);
		//统计一共删除了多少人
		$succNum=count($result);
		//记录系统日志
		$this->saveAdminLog('',$delstr,'删除会员网络','删除'.$this->userobj->byname.'['.$uid.']的网络');
		/************************************************/
		//提示删除完成  删除了多少人
		if($succNum >0){
			M()->commit();
			$this->success("删除成功：".$succNum);
		}else{
			M()->rollback();
			$this->error("删除失败");
		}
	}
	//管理网络业绩分析
	public function achieve(net_place $net_place){
		$branch=$net_place->getBranch();
		if(trim(I("post.uid/s"))!=""){
			$where = "报单状态!='未确认'";
			if(I("post.startTime/s") != ''){
				$startTime = strtotime(I("post.startTime/s"));
				$where .= ' and 到款日期>='.$startTime;
			}
			if(I("post.endTime/s") != ''){
				$endTime = strtotime(I("post.endTime/s"));
				$where .= ' and 到款日期<'.($endTime+24*3600);
			}
			if(trim(I("post.uid/s")) == ''){
				$this->ajaxReturn('',$this->userobj->byname.'编号不能为空!',0);
			}
			$userInfo = M("会员")->where(array("编号"=>trim(I("post.uid/s"))))->find();
			if(!$userInfo){
				$this->ajaxReturn('',$this->userobj->byname.'编号不存在!',0);
			}
			$addSet = array();
			foreach(X('sale_*') as $sale){
				$addvals = $sale->getcon('addval',array('from'=>'','to'=>'','set'=>''));
				foreach($addvals as $addval){
					if($addval['set']=='1' || $addval['to'] != $net_place->name) continue;
					$addSet[] = array('from'=>$addval['from'],'name'=>$sale->name);
				}
			}
			$downUser = array();
			$achieve = array();
			foreach($branch as $v){
				$achieve[$v] = 0;
				$downUsers = M('会员')->field('id,编号')->where("find_in_set('".$userInfo['id']."-{$v}',{$net_place->name}_网体数据)")->select();
				if(isset($downUsers))
				foreach($downUsers as $downUser){
					foreach($addSet as $add){
						$downsales = M('报单')->where($where." and 编号='{$downUser['编号']}' and 报单类别='{$add['name']}'")->select();
						if(isset($downsales))
						foreach($downsales as $downsale){
							$achieve[$v] += transform($add['from'],$downsale);
						}
					}
				}
			}
			$this->ajaxReturn($achieve,'',1);
		}else{
			$this->assign('branch',$branch);
			$this->assign('net_place',$net_place);
			$this->assign('name',$this->userobj->byname);
			$this->display();
		}
	}
	/*
	* fun_treenum网络业绩分析
	* 根据订单数据组成数组 除未确认订单外的所有订单 根据xml配置中的addval对上返业绩
	*/
	public function funachieve(fun_treenum $fun_treenum){
		if(trim(I("post.userid/s"))){
			//条件判断
			$where['报单状态'] = array("neq","未确认");
			if(I("post.startTime/s") != ''){
				$startTime = strtotime(I("post.startTime/s"));
				$where['到款日期'][]=array("egt",$startTime);
			}
			if(I("post.endTime/s") != ''){
				$endTime = strtotime(I("post.endTime/s"));
                $where['到款日期'][]=array("lt",$endTime+24*3600);
			}
			if(trim(I("post.userid/s")) == ''){
				$this->ajaxReturn('',$this->userobj->byname.'编号不能为空!',0);
			}
			//会员信息
			$userInfo = M("会员")->where(array("编号"=>trim(I("post.userid/s"))))->find();
			if(!$userInfo){
				$this->ajaxReturn('',$this->userobj->byname.'编号不存在!',0);
			}
			//业绩来源订单
			$addSet = array();
			foreach(X('sale_*') as $sale){
				$addvals = $sale->getcon('addval',array('from'=>'','to'=>'','set'=>''));
				foreach($addvals as $addval){
					if($addval['to'] != $fun_treenum->name)
						continue;
					$addSet[] = array('from'=>$addval['from'],'name'=>$sale->name);
				}
			}
			$achieve = array();$downUsers=array();
			//推荐的人
			$tjusers = M('会员')->field('id,编号,'.$fun_treenum->netName.'_网体数据')->where(array($fun_treenum->netName."_上级编号"=>$userInfo['编号']))->select();
			if(isset($tjusers)){
				foreach($tjusers as $tjuser){
					//下级信息
					$downUsers[$tjuser['编号']] = M('会员')->field('id,编号')->where(array($fun_treenum->netName."_网体数据"=>array("like",trim($tjuser[$fun_treenum->netName.'_网体数据'].','.$tjuser['id'].",%",',')),$fun_treenum->netName."_上级编号"=>$tjuser['编号'],"_logic"=>"or"))->select();
					$downUsers[$tjuser['编号']][]=array("id"=>$tjuser['id'],"编号"=>$tjuser['编号']);
				}
			}
			if(isset($downUsers)){
                $sum=0;
				//循环下级找出对上返的业绩
				foreach($downUsers as $tjkey=>$downUser1s){
					$achieve[$tjkey]=0;
					foreach($downUser1s as $downUser){
                        $where['编号']=$downUser['编号'];
						foreach($addSet as $add){
                            $where['报单类别']=$add['name'];
							$downsales = M('报单')->where($where)->select();
							if(isset($downsales)){
								foreach($downsales as $downsale){
									$achieve[$tjkey] += transform($add['from'],$downsale);
								}
							}
						}
					}
                    $sum+=$achieve[$tjkey]; 
				}
			}
			//汇总
            $achieve['汇总']=$sum;
            //输出到页面上
			$this->ajaxReturn($achieve,'',1);
		}else{
			$this->assign('fun_treenum',$fun_treenum);
			$this->assign('name',$this->userobj->byname);
			$this->display();
		}
	}
    // 网络设置
	function netSet(){
      
		$net = array();$treenumArr=array();
		foreach(X('net_rec,net_place') as $netobj){
			foreach(X('fun_treenum') as $treenum)
			{
				if($treenum->netName==$netobj->name){
					$treenumArr[$treenum->netName] = $treenum->name;
				}
			}
			$net[$netobj->name] = $netobj;
		}
		//$netobj->treenum[]=$treenum->name;
		$this->assign("net",$net);
		$this->assign("treenumArr",$treenumArr);
		$this->assign("shop",X('user')->shopWhere!='');
		$this->display();
	}
    // 保存网络设置
    function saveNetSet(){
    	M()->startTrans();
		foreach(X('net_rec') as $nr){
			$nr->setatt("treeDisp",array(""));
			$this->autoSet($nr);
		}
		foreach(X('net_place') as $np){
			$this->autoSet($np);
		}
		M()->commit();
		$this->saveAdminLog('','',"网络设置");
		$this->success("设置完成！");
    }
    protected function autoSet($obj,$option=array()){
		foreach($obj as $k=>$v){
			if(I('data.'.$obj->name,'','',I("post.".$k."/a"))!=""){
				$newval=I('data.'.$obj->name,'','',I("post.".$k."/a"));
				if(($k=='adminNetLayer' || $k=='userNetLayer') && $newval>10){
					$newval = 10;
				}
				//数组？？？
				if(is_array($newval)){
					foreach($newval as $nettree=>$postval){
						if($postval=='true'){
							$obj->treeDisp[]=$nettree;
							$obj->setatt($k,$obj->treeDisp);
						}
					}
				}
				if(gettype($v)=='string' || ((gettype($v)=='integer' || gettype($v)=='double') && is_numeric($newval))){
			   		settype($newval,gettype($v));
			   		$obj->setatt($k,$newval);
			   	}
			   	if(gettype($v)=='boolean' && (strtolower($newval)=='true' || strtolower($newval) == 'false')){
			   		if($newval=='true'){
			   			$newval=true;
			   		}else{
			   			$newval=false;
                    }
			   		$obj->setatt($k,$newval);
			   	}
			}
		}
	}
	public function dispUp($net)
	{
		$thisuser=M('会员','dms_')->where(array('编号'=>I("get.id/s")))->find();
		$useraction=A('Admin/User');
		$upids=$net->getupids($thisuser,0,0,array(),true);
        $list=new TableListAction('会员');
		$list->table('dms_会员 user');
        $list->showPage=false;
        $list->where(array('user.id'=>array('in',$upids)));
        $list->order("user.id desc");
        $list->addshow($this->userobj->byname."编号",array("row"=>array(array(&$useraction,"_dispUserId"),'[编号]','[状态]','[空点]','[登陆锁定]'),"searchRow"=>"[编号]","searchMode"=>"text","searchRow"=>'user.编号',"searchGet"=>"userid","excelMode"=>"text","order"=>"user.编号","searchPosition"=>"top"));
        if($this->userobj->shopWhere == '[服务中心]=1')
        {
        	$list->addshow("店",array("row"=>"[服务中心]","searchMode"=>"text",'searchRow'=>'服务中心','format'=>'bool','order'=>'user.服务中心'));
        }        
        foreach(X('levels') as $levels)
        {
        	$_temp=array();
			foreach($levels->getcon("con",array("name"=>"","lv"=>"")) as $lvconf)
			{
				$_temp[ $lvconf['name'] ] = $lvconf['lv'];
 			}
        	$list->addshow($levels->byname,array("row"=>array(array(&$this,"_printUserLevel"),"[".$levels->name."]",$levels->name),"searchMode"=>"num","searchSelect"=>$_temp,"searchRow"=>"user.".$levels->name."","order"=>'user.'.$levels->name));
        }
        $netnamerow='';
        if(get_class($net)=='net_rec'||get_class($net)=='net_place')
        {
		$searchSql = "FIND_IN_SET((SELECT id FROM dms_会员 where `编号`='[*]'),user.`{$net->name}_网体数据`)";
		//$list->addshow($net->name."上级",array("row"=>"[".$net->name."_上级编号]","searchMode"=>"text","excelMode"=>"text"));
	    $list->join('dms_会员 as '.$net->name.' on user.'.$net->name.'_上级编号='.$net->name.'.编号');
	    $list->addshow("姓名",array("row"=>"[姓名]","searchRow"=>'user.姓名',"searchMode"=>"text","searchPosition"=>"top"));
        //$netnamerow.=",{$net->name}.姓名 as netname".$net->getPos();
        //$list->addshow($net->name."姓名",array("row"=>"[netname".$net->getPos()."]","searchMode"=>"text","excelMode"=>"text"));
        if(get_class($net)=='net_place')
        {
			$bras = $net->getBranch();
			$time = strtotime(date('Y-m-d',systemTime()));
			$dayField = '';$leaveField = '';$totalField= '';
        	foreach($bras as $bar){
				$dayField   .=$net->name."_".$bar."区本期业绩,";
				$leaveField .=$net->name."_".$bar."区结转业绩,";
				$totalField .=$net->name."_".$bar."区累计业绩,";
        	}
			$searchSql = "";
			foreach($net->getcon("region",array("name"=>"")) as $nameconf){
				$regionName = $nameconf['name'];
				$searchSql .= " FIND_IN_SET((select concat((SELECT id FROM dms_会员 where 编号='[*]'),'-{$regionName}')),{$net->name}_网体数据) or";
			}
			$searchSql = trim($searchSql,'or');
	       	$list->addshow($net->byname."新增业绩",array("row"=>array(array($this,'getAchieve'),$dayField  ,'[编号]')));
			$list->addshow($net->byname."结转业绩",array("row"=>array(array($this,'getAchieve'),$leaveField,'[编号]')));
			$list->addshow($net->byname."累计业绩",array("row"=>array(array($this,'getAchieve'),$totalField,'[编号]')));
	       
	       	$list->addshow($net->byname."所属区域",array("row"=>array(array($net,"showregion"),"[".$net->name."_位置]"),"searchMode"=>"text","excelMode"=>"text"));
        }
       	$list->addshow($net->byname."层数",array("row"=>"[".$net->name."_层数]","searchMode"=>"num"));
		$list->addshow($net->byname."网",array("row"=>'','hide'=>true,'searchShow'=>false,"searchMode"=>"text",'searchRow'=>'','searchSql'=>$searchSql));
       	}
       	$list->field('user.*'.$netnamerow);
        $list->order('user.'.$net->name.'_层数 asc');
        $this->assign('list',$list->getHtml());
        $this->display();
	}

	public function getAchieve($field,$userid){
		$result = M('会员')->field(trim($field,','))->where(array('编号'=>$userid))->find();
		$str = '';
		foreach($result as $re){
			$str .= $re.'/';
		}
		return trim($str,'/');
	}
   /*
	*
	*网络图打印设置
	*
	*/
	
	function  netSet_print(){
	   //查询出所有的网体
	   $nets = X("net_place,net_rec");
	   $netname = array();
	   foreach($nets as $net){
	     $netname[] = $net->name;
	   }
	   $this->assign('net',$netname);
	   $this->display();
	}
	
	//打印预览
	
	function myPrintPreview(){
	  //获取$_POST['wangluo']所在的节点	  
	    $net = X("@".I("post.wangluo/s"));
		$res_max = M()->query("select max({$net->name}_层数) as biggest_ceng from dms_会员");

	  //获取第一个会员
	  if(I("post.userid/s")){
	  	  
	     $user = M('会员')->where(array('编号'=>I("post.userid/s")))->find();
	     if(!$user){
	        die('该会员不存在,请返回重新填写');
	     }
	     
	  }else{
	     $user = M('会员')->where(array($net->name.'_层数'=>1))->find();   
	  }
	  //设置最小层数 最大层数
	  if(I("post.netstart/d")!=null){
	  	 $minlayer = I("post.netstart/d");
	  	 if($minlayer<1){
	  	   $minlayer=1;
	  	 }
	   
	  }else{
	     $minlayer = 1;
	  }
	  if(I("post.netend/d")!=null){
	    $maxlayer = I("post.netend/d");
	    if($maxlayer<1){
	  	   	$maxlayer=1;
	  	}
	  }else{
	    //获取表中最大的$net."_层数"
	    $maxlayer = $res_max[0]['biggest_ceng'];
	  }
	    $where = "1";
	    //获取开始时间 和结束时间
	    if(I("post.startTime/s")!="")
    		$starttime = strtotime(I("post.startTime/s"));
    	if(I("post.endTime/s")!="")
    		$endtime = strtotime(I("post.endTime/s"));
	    if(I("post.startTime/s")!=""){
	        $where.=" and 注册日期>={$starttime} ";
	    }
	     if(I("post.endTime/s")!=""){
	        $where.=" and 注册日期<={$endtime} ";
	    }
	    $allusers = array();
	     for($i=$minlayer;$i<=$maxlayer;$i++){
	           $downusers = array();
	           $downusers = $net->getdown($user,$i,$i,$where);
	           $allusers[$i] = $downusers;
	     }
	     $this->assign('user',$user);
       //查询所有的下级会员
        $this->assign("downusers",$allusers);
        //查询出打印员
        $this->assign("account",$_SESSION['loginAdminAccount']);
        //查询打印时间
        $this->assign('systemTime',date('Y-m-d H:i:s',systemTime()));
        //映射会员的级别
        $i=0;
        foreach(X('levels') as $node)
		{
			$i++;
			$level[$node->name]['con'][0]='无级别';
			$level[$node->name]['giveEdit']=$node->giveEdit;
			$level[$node->name]['regEdit']=$node->regEdit;
			$level[$node->name]['byname']=$node->byname;
			foreach($node->getcon('con',array('lv'=>0,'name'=>'')) as $con)
			{
				$level[$node->name]['con'][$con['lv']]=$con['name'];
			}
		}
		$i = $i*2+4;
		$this->assign('ii',$i);
		//映射会员的钱包信息
		$this->assign('level',$level);
		if(I("post.act/s")!=""){
			$this->assign("printtype",I("post.act/s"));
		}
	    $this->display();
	}
}
?>