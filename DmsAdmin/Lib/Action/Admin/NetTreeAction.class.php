<?php
// 网络图
defined('APP_NAME') || die('不要非法操作哦!');
class NetTreeAction extends CommonAction 
{
	public $levelsArr = array();
	public $net;
	//业绩信息是否要在点位内部做显示,如果关闭则在浮动框里边显示
	public $placePvNodeDisp=true;
	//ThinkPHP构造函数
	public function _initialize() {
		parent::_initialize();
		//生成级别信息缓存
		$levelsArr = array();
		foreach(X('levels') as $level){
			$levelsArr[$level->name] = array();
			$cons=$level->getcon("con",array("name"=>'','lv'=>''));
			foreach($cons as $con){
				$levelsArr[$level->name][$con['lv']] = $con['name'];
			}
		}
		//dump($levelsArr);
		
		$this->levelsArr = $levelsArr;
		$this->assign('levelsArr',$levelsArr);
		//会员标题名称
		$this->assign('usertitle',X('user')->name);
		$this->assign('is_treeimg',adminshow('is_treeimg'));
		$this->assign('placePvNodeDisp',$this->placePvNodeDisp);
	}
	public function setAssign($net)
	{
		$this->net = $net;
		$this->assign('showLayer',$this->net->adminNetLayer);
		$this->assign('netNode'  ,$this->net);
		$this->assign('netName'  ,$this->net->name);
		$treenumArr=array();
		foreach(X('fun_treenum') as $treenum)
		{
			if($treenum->netName==$this->net->name){
				$treenumArr[$treenum->netName] = $treenum->name;
			}
		}
		$this->assign('treenumArr'  ,$treenumArr);
	}
	/*
	* 网络图显示
	*/
	public function index($net)
	{
		$this->setAssign($net);
		//获取显示类型
		$regPath='';
		
		
		foreach(X('sale_reg') as $sale_reg)
		{
			if($sale_reg->user == 'admin' && $net->useBySale($sale_reg))
			{
				$regPath = $sale_reg->objPath();
				break;	
			}
		}
		$this->assign('netName',$net->byname);
		$this->assign('netTrueName',$net->name);
		$this->assign('regXpath',$regPath);
		$style	= I("request.style/s")!="" ? I("request.style/s") : 'ramus';
		$style	= ($style=='ramus') ? get_class($this->net) : $style;
		$this->assign('style',$style);
	
		switch($style)
		{
			case 'dir':
				$this->showDirTree($net);
			break;
			case 'net_place':
				$this->showNet('Place');
			break;
			case 'net_rec':
				$this->showNet('Rec');
			break;
			case 'lists':
				$this->showListsTree($net);
			break;
		}
	}

	/*
	* 显示 树状 网络分支图(安置关系)
	*/
	private function showNet($type)
	{
		$userModel = M('会员');
		$netName = $this->net->name;
		//获取树
		if(I("request.uid/s")!=''){
			$userModel->where(array('编号'=>I("request.uid/s")));
		}else{
			$userModel->where(array($netName.'_层数'=>'1'));
		}
		$firstUser = $userModel->find();
		$pvtime = I("request.pvtime/s");
	    $this->assign("pvtime",$pvtime);
		if($pvtime != ''){
			$recache=$this->getDayYeji($firstUser,$pvtime);
		    if($recache[$firstUser['id']]){
		    	foreach($recache[$firstUser['id']] as $regkey=>$val){
		    		$firstUser[$regkey]=$val!=NULL?$val:0;
		    	}
		    }
		}
		$this->assign("firstUserInfo",$firstUser);
		$result = array();
		$newusers = null;
		if($firstUser){
			$users=$this->net->getDown($firstUser,1,$this->net->adminNetLayer-1);
			if(!$users)$users=array();
			array_unshift($users,$firstUser);
			//从新生成新数组,以编号为K.带有下级编号数组
			$child=array();
			foreach($users as $user)
			{
				if($pvtime){
					$recache=$this->getDayYeji($user,$pvtime);
				    if($recache[$user['id']]){
				    	foreach($recache[$user['id']] as $regkey=>$val){
				    		$user[$regkey]=$val!=NULL?$val:0;
				    	}
				    }
			    }
				$newusers[$user['编号']]=$user;
				$region=array();
				//对当前USER做判定，标记可注册区域有哪些
				foreach($this->net->getcon("region",array("name"=>"","regDisp"=>"true",'where'=>''),true) as $con)
				{
					//如果某一个区符合注册条件，同时这个区还没人
					$where = $con['where'];
					$where = str_replace('{myrec}','true',$where);
					if(transform($where,$user) && $user[$netName.'_'.$con['name'].'区']=='')
					{
						$region[]=$con['name'];
					}
				}
				$newusers[$user['编号']]['region'] = $region;
			}
		}
		
		$this->assign('users',$newusers);
		$this->assign('userNode',$this->userobj);
		$this->assign('rel',md5(__GROUP__.'/Admin/NetTree/index:'.__XPATH__));
		$this->display('net'.$type.'Tree');
	}
	public function getDayYeji($user,$pvtime){
		$pvtime =strtotime($pvtime);
		$user_m = M('会员');
		foreach($this->net->getBranch() as $key => $Branch)
	    {
	    	$lstr=$this->net->name.'_'.$Branch.'区';
	    	//本期业绩连表
        	$user_m->join('(select userid,sum(val) '.$lstr.'本日业绩 from dms_'.$this->net->name.'_业绩 where time>='.$pvtime.' and time<'.$pvtime.'+86400 and region='.($key+1).' and pid>0 group by userid) new'.$key.' on dms_会员.id = new'.$key.'.userid');
        	//结转业绩连表
			$user_m->join('(select userid,sum(val) '.$lstr.'结转业绩 from dms_'.$this->net->name.'_业绩 where time<'.$pvtime.' and region='.($key+1).' and pid<>0 group by userid) jie'.$key.' on dms_会员.id = jie'.$key.'.userid');
			//累计业绩连表
			$user_m->join('(select userid,sum(val) '.$lstr.'累计业绩 from dms_'.$this->net->name.'_业绩 where time<'.$pvtime.'+86400 and region='.($key+1).' and pid>0 group by userid) sum'.$key.' on dms_会员.id = sum'.$key.'.userid');
        	$rows['new'.$key.'.'.$lstr.'本日业绩'] = 1;
        	$rows['jie'.$key.'.'.$lstr.'结转业绩'] = 1;
        	$rows['sum'.$key.'.'.$lstr.'累计业绩'] = 1;
	    }
	    $cache = $user_m->where(array("id"=>$user['id']))->getField('id keyid,'.implode(array_keys($rows),','));
	    return $cache;
	}
	/*
	* 显示 树状 网络分支图(推荐关系)
	$userNode 当前user节点
	$netNode 当前net节点
	$date 日期
	*/
	private function showRamusTree($netNode,$netPlaceName)
	{
		
		$userModel = M('会员');
		$netName = $netNode->name;
		//获取树
		if(I("request.uid/s")!=''){
			$firstUserInfo = M('会员')->where(array('编号'=>I("request.uid/s")))->find();
		}else{
			$firstUserInfo = M('会员')->where(array($netName.'_层数'=>'1'))->find();
		}
		$result = array();
		if($firstUserInfo){
			$upid = $firstUserInfo['id'];
			$upUserLayer = $firstUserInfo[$netName.'_层数'];
			
			// 循环获得下面几层数据
			for($i=0;$i<$this->net->adminNetLayer-1;$i++){
				$where = "{$netName}_层数=".($upUserLayer+$i+1)." and ";
				$where .= "find_in_set('{$upid}',{$netName}_网体数据)";
				//$where .= " order by id";
				$downUsers = M('会员')->where($where)->select();
				if(!$downUsers){
					break;
				}
				$result[$i] = $downUsers;
			}
		}
		$this->assign('firstUserInfo',$firstUserInfo);
		$this->assign('netTree',$result);
		$this->assign('userNode',$this->userobj);
		$this->display('netRecTree');
	}

	/*
	* 显示 网络目录树
	*/
	private function showDirTree($netNode)
	{
		$userModel = M('会员');
		$netName = $netNode->name;
		if(I("request.uid/s")!=''){
			$firstUserInfo = M('会员')->where(array('编号'=>I("request.uid/s")))->find();
		}else{
			$firstUserInfo = M('会员')->where(array($netName.'_层数'=>'1'))->find();
		}
		if(!$firstUserInfo){
			echo "未找到对应{$this->userobj->byname}.<a href='javascript:navTab.reload();'>返回</a>";
			exit;
		}
		
		$firstUserid = $firstUserInfo['编号'];
		$downUsers = M('会员')->where(array($netName."_上级编号"=>$firstUserid))->select();
		foreach($downUsers as $key=>$downUser)
		{
			//检查是否存在下级
			$info	= $userModel->where("{$netName}_上级编号='".$downUser['编号']."'")->find();
			if($info){
				$downUsers[$key]['haveChild'] = true;
			}else{
				$downUsers[$key]['haveChild'] = false;
			}
			$downUsers[$key]['floatStr'] = $this->getFloatJson($downUsers[$key]);
		}
		$bump = $this->is_BumpPrize();
		if($bump){
		  $bump=1;
		}else{
		  $bump=0;
		}
		$firstUserInfo['floatStr'] = $this->getFloatJson($firstUserInfo);
		$this->assign('firstUserInfo',$firstUserInfo);
		$this->assign('downUsers',$downUsers);
		$this->assign('userNode',$this->userobj);
		$this->assign('netNode',$netNode);
		$this->assign('is_bump',$bump);//判读该系统是否有对碰奖
		$this->assign('jiedian',get_class($netNode));//判断树形是否是推荐还是管理
		$this->display('dirTree');
	}

	/*
	* 获取会员的子节点
	*/
	public function getChild($net)
	{
		$this->setAssign($net);
		$depth				= I("request.depth/d");
		$uid				= I("request.uid/s");
		$spanImg			= I("request.spanImg/s");
		$netName			= $net->name;			//网体名称
		//获取下级
		$userModel = M('会员');
		$downUsers = M('会员')->where(array($netName.'_上级编号'=>$uid))->select();
		$c					= array();
		$downUsersTotal	= count($downUsers)-1;
		foreach($downUsers as $i=>$downUser)
		{
			$levelStr = '';
			foreach($this->levelsArr as $levelName=>$levels){
				$levelStr .= '['.$levels[$downUser[$levelName]].']';
			}
			$downUsers[$i]['floatStr'] = $this->getFloatJson($downUser);
			//检查是否存在下级
			$info	= $userModel->where("{$netName}_上级编号='".$downUser['编号']."'")->find();
			$c[]	= '<div style="float:left;clear:both;" id="doverUser_'.$downUser['编号'].'" info='.$downUsers[$i]['floatStr'].'>';
			$spanImgArr = explode(',',trim($spanImg,','));
			$spanImg1 = '';
			foreach($spanImgArr as $val){
				if($val == '0'){
					$spanImg1 .='<span style="padding-left:19px"></span>';
				}else if($val == '1'){
					$spanImg1 .='<img border=0 src="/Public/Images/admin/treeimgs/tree_line.gif"/>';
				}
			}
			$c[]		= $spanImg1;
			if ($info)
			{
				if( $i == $downUsersTotal )
				{
					$c[]	= "<img border=0 id=\"img_{$downUser['编号']}\" disrc=\"plusl\" src=\"/Public/Images/admin/treeimgs/tree_plusl.gif\" onclick=\"get_child('{$downUser['编号']}',".($depth+1).");\" style='cursor:pointer' />";
				}
				else
				{
					$c[]	= "<img border=0 id=\"img_{$downUser['编号']}\" disrc=\"plus\"  src=\"/Public/Images/admin/treeimgs/tree_plus.gif\" onclick=\"get_child('{$downUser['编号']}',".($depth+1).");\" style='cursor:pointer'/>";
				}
			}
			else
			{
				$c[]	= ($i==$downUsersTotal)?'<img border=0 id="img_'.$downUser["编号"].'" src="/Public/Images/admin/treeimgs/tree_blankl.gif" disrc=\"blankl\" />':'<img border=0 id="img_'.$downUser["id"].'" disrc=\"blank\" src="/Public/Images/admin/treeimgs/tree_blank.gif" />';
			}
			if($downUser["审核日期"]){
				$c[]		= $downUser["编号"] . '[第' . ($depth+2) . '层] '.$levelStr.' [审核日期:' . date("Y-m-d",$downUser["审核日期"]) . ']</div>';
			}else{
				$c[]		= $downUser["编号"] . '[第' . ($depth+2) . '层] '.$levelStr.' [注册日期:' . date("Y-m-d",$downUser["注册日期"]) . ']</div>';
			}
			$c[]		= '<div id="d_' . $downUser['编号'] . '" style="display:none"></div>';
			
		}
		
		$this->ajaxReturn(implode('',$c),'ok',1);
	}

	
	//获取浮动层数据
	private function getFloatJson($userinfo){
		$netPlaceName = array();
		foreach(X('net_place') as $netPlace){
			if($netPlace->pvFun)
			{
				$regions=$netPlace->getcon("region",array('name'=>'','byname'=>''));
				foreach($regions as $region){
					$regionname=$region['byname']!=""?$region['byname']:$region['name']."区";
					$netPlaceName[$netPlace->name][$regionname]=array(
						$userinfo[$netPlace->name.'_'.$region['name'].'区本日业绩'],
						$userinfo[$netPlace->name.'_'.$region['name'].'区结转业绩'],
						$userinfo[$netPlace->name.'_'.$region['name'].'区累计业绩']
					);
				}
			}
		}
		$floatStr =array();
		$floatStr['编号'] = $userinfo['编号'];
		$floatStr['姓名'] = $userinfo['姓名'];
		if($userinfo['审核日期']){
			$floatStr['审核日期'] = date('Y-m-d%H$i$s',$userinfo['审核日期']);
		}else{
			$floatStr['注册日期'] = date('Y-m-d%H$i$s',$userinfo['注册日期']);
		}
		$floatStr['levels'] = array();
		foreach($this->levelsArr as $levelName=>$levels){
			$floatStr['levels'][$levelName] = $levels[$userinfo[$levelName]];
		}
		$floatStr['网体业绩'] = $netPlaceName;
		$floatStr = json_encode($floatStr);
		return $floatStr;
	}

	
	/*
	* 显示 列表形式 推荐网络图 (推荐关系)
	*/
	public function showListsTree($net) ///&$userType,&$netName,&$userNode,&$netNode
	{
		$netName				= $net->name;			//网体名称
		$list=new TableListAction('会员');
		$list->editList=false;
		
		//获取顶层
		$uid = '';
		//从table查询中得到上级的ID.并做处理
		if(I("post._search0_other/s")!="")
		{
			//把上级ID赋给userid参数.模拟成点击了某个会员
			$_REQUEST['userid']=I("post._search0_other/s");
			$_GET['userid']=I("post._search0_other/s");
			//注销外参.不让tablelist内部查询生效
			unset($_POST['_search0_other']);
			unset($_REQUEST['_search0_other']);
		}
		if( I("request.userid/s")!='' )
		{
			$hint='目前正在显示['.I("request.userid/s").']的'.$net->byname.'下级';
			$uid = addslashes(I("request.userid/s"));
			$list ->where(array($netName.'_上级编号'=>$uid))->order("id");
			
		}elseif( I("request.pid/s")!=''){
			$upuser = M("会员")->where(array("编号"=>I("request.pid/s")))->find();
			if($upuser[$netName.'_上级编号'] !=''){
				$hint='目前正在显示['.$upuser[$netName.'_上级编号'].']'.$net->byname.'下级';
				$list ->where(array($netName.'_上级编号'=>$upuser[$netName.'_上级编号']));
				$uid = $upuser[$netName.'_上级编号'];
			}else{
				$hint='目前正在显示第一层会员';
				$list ->where(array($netName.'_层数'=>1));
			}
		}else{
			$hint='目前正在显示第一层会员';
			$list ->where(array($netName.'_层数'=>1));
		}
		$list ->hint=$hint;
		$list ->showSearch = true;
		$list ->excel = false;
		//合成DWZ原始连接。定义窗口
		$rel=md5(__GROUP__.'/Admin/NetTree/index:'.__XPATH__);
		$title=$netName."网络";
        $list->setButton = array(
			"上一层"=>array("class"=>"edit","href"=>__APP__."/Admin/NetTree/index:__XPATH__/style/lists/pid/".$uid,"target"=>"navTab",'title'=>$title,"rel"=>$rel),
        );
		$list->addshow("编号",array("row"=>'<a rel="'.$rel.'" title="'.$title.'" target="navTab" href="'.__APP__.'/Admin/NetTree/index:__XPATH__/style/lists/userid/[编号]'.'/style/lists">[编号]</a>',"searchRow"=>'编号',"searchMode"=>"text","searchPosition"=>"top"));
        $list->addshow("姓名",array("row"=>"[姓名]"));
		get_class($net) == 'net_rec' && $list->addshow("推荐人数",array("row"=>"[{$netName}_推荐人数]"));
		$list->addshow("团队人数",array("row"=>"[{$netName}_团队人数]"));
		$list->addshow("团队总人数",array("row"=>"[{$netName}_团队总人数]"));
		$list->addshow("层数",array("row"=>"[{$netName}_层数]"));
        $list->addshow("注册日期",array("row"=>"[注册日期]","format"=>"time"));
		$this->assign('list',$list->getHtml());
		$this->display('listsTree');
	}
   /************************   net_place2网络图   *********************/
   public function place2(net_place2 $net)
   {
   	    $upnode = false;
   	   	$model = M($net->name,'dms_');
		if(I('request.uid/s')!=''){
			$upnode = $model->where(array('id'=>I('request.uid/s')))->find();
		}
		if(!$upnode)
		{
			$upnode = $model->where(array('层数'=>1))->find();
		}
		if(!$upnode)
		{
			die('未找到会员');
		}
		$result=array();
		$upUserLayer = $upnode['层数'];
		for($i=0 ; $i < $net->adminNetLayer -1 ; $i++){
			$downUsers=$net->getdown($upnode,$i+1,$i+1);
			if(!$downUsers){
				break;
			}
			$result[$i] = $downUsers;
		}
		$this->assign('firstUserInfo',$upnode);
		$this->assign('netTree',$result);
		$this->display('place2');
   }
   
      	/**
	*幸运图
	*
	*/
	public function showLineTree($net)
	{
		$fieldstr="";
		foreach(X("levels") as $level){
			$fieldstr.=",".$level->name;
		}
		$showLayer		= 10;
		$netName = $net->byname;
		//获得所有的顶点会员
		$topwhere=array("状态"=>0);
		$topids=M($net->name)->where($topwhere)->order("time asc")->group("网体")->select();
		$firstUser=array();
		$result=array();
		if($topids){
			//获得页面显示的顶点
			if( I("request.uid/s")!='')
			{
				$where=array("编号"=>I("request.uid/s"),"状态"=>0);
				if(I("request.u_num/d")>0){
					$where['排序']=I("request.u_num/d");
				}
				$firstUser=M($net->name)->where($where)->order('排序 asc')->find();
				$Userinfo=M("会员")->where(array("id"=>$firstUser['userid']))->Field("姓名,状态,审核日期".$fieldstr)->find();
				if($Userinfo){
					$firstUser=array_merge($firstUser,$Userinfo);
				}else{
					$firstUser=array();
				}
			}else{
				$firstUser=$topids[0];
				$Userinfo=M("会员")->where(array("id"=>$firstUser['userid']))->Field("姓名,状态,审核日期".$fieldstr)->find();
				if($Userinfo){
					$firstUser=array_merge($firstUser,$Userinfo);
				}else{
					$firstUser=array();
				}
			}
			if($firstUser){
				$upuser=M($net->name)->where(array("id"=>$firstUser['上级'],"状态"=>0,"网体"=>$firstUser["网体"]))->order("排序 asc")->find();
				$this->assign("upuser",$upuser);
				$topid=M($net->name)->where(array("状态"=>0,"网体"=>$firstUser["网体"]))->order("排序 asc")->getField('编号');
				$this->assign("topid",$topid);
				$downUsers=M($net->name)->where(array("排序"=>array("gt",$firstUser["排序"]),"状态"=>0,"网体"=>$firstUser["网体"]))->limit($showLayer-1)->select();
				if(count($downUsers)>0){
					$i=1;
					foreach($downUsers as $downUser){
						$Userinfo=M("会员")->where(array("id"=>$downUser['userid']))->Field("姓名,状态,审核日期".$fieldstr)->find();
						$downUser=array_merge($downUser,$Userinfo);
						$result[$i] = $downUser;
						$i++;
					}
				}
			}
		}
		$this->assign('firstUserInfo',$firstUser);
		$this->assign('userNode',$this->userobj);
		$this->assign('topids',$topids);
		$this->assign('netName',$netName);
		$this->assign('netTree',$result);
		$this->display('net_line_tree');
	}

}
?>