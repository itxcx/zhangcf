<?php
// 网络图
defined('APP_NAME') || die('不要非法操作哦');
class NetAction extends CommonAction 
{	
	/*
	* 网络图显示
	*/
	public function disp($netNode)
	{
		//级别数组
		$levelsArr = array();
		foreach(X('levels') as $level){
			$levelsArr[$level->name] = array();
			$cons=$level->getcon("con",array("name"=>'','lv'=>''));
			foreach($cons as $con){
				$levelsArr[$level->name][$con['lv']] = $con['name'];
			}
		}
		$netPlaceName = array();
		foreach(X('net_place') as $netPlace){
			$regions=$netPlace->getcon("region",array('name'=>''));
			foreach($regions as $region){
				$netPlaceName[$netPlace->name][]=$region['name'];
			}
		}
		
		//增加注册用args
		foreach(X('sale_reg') as $sale)
		{
			if($sale->user != 'admin' && $sale->use && $netNode->useBySale($sale))
			{
				$this->assign('regXpath',$sale->objPath());
			}
		}
		
		//谁可以报单
		$bdreg=true;
		if($this->userobj->shopWhere != '' && CONFIG('USER_SHOP_SALEONLY') && !transform(X('user')->shopWhere,$this->userinfo)){
			$bdreg=false;
		}
		$this->assign('bdreg',$bdreg);
		$treenumArr = array();
		foreach(X('fun_treenum') as $treenum)
		{
			if($treenum->netName==$netNode->name && in_array($treenum->name,$netNode->treeDisp)){
				$treenumArr[$treenum->netName] = $treenum->name;
			}
		}
		$this->assign('treenumArr',$treenumArr);
		$this->assign('levelsArr',$levelsArr);
		$this->assign('netPlaceName',$netPlaceName);
		$style	= I("REQUEST.nettype/s")=="" ? (!$netNode->userauto?'dir':'') : I("REQUEST.nettype/s");
		if( $style == 'dir'){	//目录树
			$this->assign('style','dir');
			$this->showDirTree($netNode,$netPlaceName,$levelsArr);
		}else{	//分支图
			$this->assign('style','ramus');
			if( get_class($netNode) == 'net_place' ){	//安置关系
				$this->showPositionRamusTree($netNode,$netPlaceName,$levelsArr);
			}else{		//推荐关系
				$this->showRamusTree($netNode,$netPlaceName,$levelsArr);
			}
		}
	}
	
	public function lineList()
	{
		//级别数组
		$levelsArr = array();
		foreach(X('levels') as $level){
			$levelsArr[$level->name] = array();
			$cons=$level->getcon("con",array("name"=>'','lv'=>''));
			foreach($cons as $con){
				$levelsArr[$level->name][$con['lv']] = $con['name'];
			}
		}
		$this->assign('levelsArr',$levelsArr);
		$this->showLineTree($levelsArr);
		
	}

    //  推荐列表
    function listDisp($rec){
        if(!$rec->userListDisp){
            $this->error(L($rec->byname).L("列表未开启"));
        }
		if(isset($_GET['userid']) && $_GET['userid']){
		$userid=$_GET['userid'];
		}else{
		$userid=$this->userinfo["编号"];
		}
        $list = new TableListAction('会员');
        $list ->where(array($rec->name.'_上级编号'=>$userid))->order("id desc");
        $list ->setShow = array(
            L('编号') => array("row"=>'<a href="'.__URL__.'/listDisp:__XPATH__/userid/[编号]/style/lists">[编号]</a>'),
			L('姓名')=> array("row"=>"[姓名]"),
            L('注册日期') => array("row"=>"[注册日期]","format"=>"time"),
			L('状态') => array("row"=>"[状态]"),
        );
        $data = $list ->getData();
        //dump($data);
        $this->assign("name",$rec->byname);
        $this->assign("data",$data);
        $this->display();
    }
	
	
	//  推荐列表
    function listDisps_down(net_rec $net_rec){
		
		$first_userid	= I("REQUEST.first_userid/s");
		if(empty($first_userid)) $first_userid=$this->userinfo['编号'];
        if(!$net_rec->userListDisp){
            $this->error(L($net_rec->byname).L("列表未开启"));
        }
        $userLevelArray = array();

		foreach(X('levels') as $levels)
		{	
			foreach($levels->getcon("con",array("name"=>"","lv"=>"")) as $lvconf)
			{
				$_temp[ $lvconf['lv'] ] = L($lvconf['name']);
 			}
			$userLevelArray	= $_temp;
		}
		
		$this->userLevelArray = $userLevelArray;
        $list = new TableListAction('会员');
        $list ->where(array($net_rec->name.'_上级编号'=>$first_userid))->order("id desc");
        $list ->setShow = array(
			L('编号') => array("row"=>"<a href='__URL__/listDisps_down:__XPATH__/first_userid/[编号]'>[编号]</a>"),
            L('姓名')=> array("row"=>"[姓名]"),
            L('注册日期') => array("row"=>"[注册日期]","format"=>"time"),
            L('状态') => array("row"=>"[状态]"),
            L('会员级别') => array("row"=>array(array(&$this,"printUserLevel"),"[会员级别]")),
            
        );
        $data = $list ->getData();
        //dump($data);
        $this->assign("name",$net_rec->byname);
        $this->assign("data",$data);
		$this->assign('recommend_id',$first_userid);
        $this->display();
    }
	
	
	/*
	* 获取会员的子节点
	*/
	public function getChild($netNode)
	{
		$uid				= I("REQUEST.id/s");
		$userNode           =$netNode->parent();
		$netName			= $netNode->name;			//网体名称
		$userLookLayer  	= $netNode->userLookLayer; // 查看深度 
		
		//会员级别数组
		$userLevelArray = array();

		foreach(X('levels') as $levels)
		{
			$_temp=array();
			foreach($levels->getcon("con",array("name"=>"","lv"=>"")) as $lvconf)
			{
				$_temp[ $lvconf['lv'] ] = $lvconf['name'];
 			}
			$userLevelArray[$levels->name]	= $_temp;
		}

		$this->userLevelArray = $userLevelArray;

		//获取下级
		$userModel			= M('会员');
		$recommend_list		= $userModel->where(array($netName."_上级编号"=>$uid))->select();
		$c					= array();
		$recommend_total	= count($recommend_list)-1;
		$jsstr="";
		foreach($recommend_list as $i=>$recommend)
		{
			if(($recommend[$netName.'_层数']-$this->userinfo[$netName.'_层数'])>$userLookLayer && $userLookLayer>0){
				$jsstr.="{ id:'0', pId:'', name:'',  open:false,isParent:false}";
				break;
			}
			if($jsstr!='')
			{
				$jsstr.=',';
			}
			$name=$recommend['编号'];
			foreach(X('levels') as $level)
			{
				$name .= '['.$this->print_user_level($recommend[$level->name],$level->name).']';
			}

			
			if($recommend["审核日期"])
			{
				$name.='[审核日期:' . date("Y-m-d",$recommend["审核日期"]) . ']';
			}
			else
			{
				$name.='[注册日期:' . date("Y-m-d",$recommend["注册日期"]) . ']';
			}
			$isParent='true';
			$down=$userModel->where(array($netName."_上级编号"=>$recommend['编号']))->field('id')->find();
			if(!$down) $isParent='false';
			$jsstr.="{ id:'{$recommend['编号']}', pId:'".$uid."', name:'".$name."',  open:true,isParent:".$isParent."}";

		}
		if($jsstr!='')
		{
				echo "[".$jsstr."]";
		}
	
	}


	 /************************   net_place2网络图   *********************/
    public function place2(net_place2 $net)
    {
   		//获得net_place2的节点的区域
   	   	$model = M($net->name,'dms_');
   	   	if(I("REQUEST.tid/s") == 'go'){
   	   		$userz = $model->where(array('编号'=>I("REQUEST.uid/s")))->find();
   	   		if(!$userz){
				$this->error(L('会员不存在'));
			}
   	   		if(I("REQUEST.uid/s") != $this->userinfo['编号']){
   	   			$upuser = $model->where(array('编号'=>$this->userinfo['编号']))->find();
   	   			$where1 = '';
   	   			foreach($net->getcon("region",array("name"=>"")) as $key=>$val)
				{ 
					if($key==0){
					   $where1 = "find_in_set('{$upuser['id']}-{$val['name']}',网体数据)";
					}else{
					   $where1.=" or find_in_set('{$upuser['id']}-{$val['name']}',网体数据)";
					}
				}
				$firstUser = $model->where("id='".$userz['id']."' and (".$where1.")")->find();
				if(!$firstUser){
					$this->error(L('该会员不在公排网体下'));
				}
			}
   	   		$_REQUEST['uid'] = $userz['id'];
   	   	}
   	   	
		if(I("REQUEST.uid/s")!=''){
			$upnode = $model->where(array('id'=>I("REQUEST.uid/s")))->find();
		}else{
			$upnode = $model->where(array('编号'=>$this->userinfo['编号']))->find();
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
		for($i=0 ; $i < $net->userNetLayer -1 ; $i++){
			$downUsers=$net->getdown($upnode,$i+1,$i+1);
			if(!$downUsers){
				break;
			}
				$result[$i] = $downUsers;
		
		//	$result[$i] = $downUsers;
		}
		$this->assign('thisUser',$this->userinfo);
		$this->assign('showLayer',$net->userNetLayer);
		$this->assign('firstUserInfo',$upnode);
		//$this->assign('userNode',$userNode);
		$this->assign('netNode',$net);
		$this->assign('netName',$net->name);
		$this->assign('netTree',$result);
		$this->display('place2');
   }

	/*
	* 显示 网络目录树
	*/
	private function showDirTree($netNode,$netPlaceName,$levelsArr)
	{
		
		$userModel = M('会员');
		$userNode = X('user');
		$userLookLayer  = $netNode->userLookLayer; // 查看深度 
		
		$netName = $netNode->name;
		$thisuser=$this->userinfo;
		
		//获取树
		if(I("REQUEST.uid/s")!=''){
			if( preg_match('/\'|\"|;|select|truncate|drop|insert|update|delete|join|union|into|load_file|outfile/i', I("REQUEST.uid/s"), $matches) ){
				$this->error(L('非法表单数据'));
			}
			if(I("REQUEST.uid/s") != $this->userinfo['编号']){
				$firstUser = $userModel->where(array("编号"=>trim(I("REQUEST.uid/s"))))->find();
				if($firstUser){
					$firstUserNetInfos = explode(',',$firstUser[$netName.'_网体数据']);
					$firstUserNetArray=array();
					foreach($firstUserNetInfos as $firstUserNetInfo){
						$firstUserNetInfoArr = explode('-',$firstUserNetInfo);
						$firstUserNetArray[] = $firstUserNetInfoArr[0];
					}
					
					if(!in_array($this->userinfo['id'],$firstUserNetArray)){
						$this->error(L('该'.$this->userobj->byname.'不在'.$netName.'网体下'));
					}
					//超出深度
					if($userLookLayer>0 && ($firstUser[$netName.'_层数']-$this->userinfo[$netName.'_层数'])>$userLookLayer){
						$this->error(L("只允许查看").$userLookLayer.L("层内的会员"));
					}
				}else{
					$this->error(L('该'.$this->userobj->byname.'不存在'));
				}
				$firstUserInfo=$firstUser;
			}
		}else{
			$result = M('会员')->where(array("编号"=>$_SESSION[C('USER_AUTH_NUM')]))->select();
			$firstUserInfo = $result[0];
		}
		if(!$firstUserInfo){
			echo "未找到对应{$this->userobj->byname}.<a href='javascript:navTab.reload();'>返回</a>";
			exit;
		}
		
		$firstUserid = $firstUserInfo['编号'];
		$downUsers = M('会员')->where(array($netName.'_上级编号'=>$firstUserid))->select();
		if($downUsers)
		foreach($downUsers as $key=>$downUser)
		{
			//检查是否存在下级
			$info	= $userModel->where("{$netName}_上级编号='{$downUser['编号']}'")->find();
			if($info){
				$downUsers[$key]['haveChild'] = true;
			}else{
				$downUsers[$key]['haveChild'] = false;
			}
			//$downUsers[$key]['floatStr'] = $this->getFloatJson($userNode,$downUsers[$key],$levelsArr);
		}
		
		//$firstUserInfo['floatStr'] = $this->getFloatJson($userNode,$firstUserInfo,$levelsArr);
		
		$this->assign('firstUserInfo',$firstUserInfo);
		$this->assign('downUsers',$downUsers);
		$this->assign('userNode',$this->userobj);
		$this->assign('netNode',$netNode);
		$this->display('dir_tree');

	}


	/*
	* 显示 树状 网络分支图(安置关系)
	*
	* $userType		 : 当前会员节点的数据名
	* $netName		 : 网络名称
	* $userNode		 : 当前会员节点
	* $position_list : 区位列表
	*/
	private function showPositionRamusTree($netNode,$netPlaceName,$levelsArr)
	{
		//判断是否是ion模版
    	if(CONFIG('DEFAULT_THEME')=='ion'){
			//判断如果  开启只有服务中心才能看网络图地
			if($netNode->shopNetDisp ==1 and $this->userinfo['服务中心'] == 0){
				$showLayer		= $netNode->shopNetLayer; //显示几层
			}else{
				$showLayer		= $netNode->userNetLayer; //显示几层
			}
			$userLookLayer  = $netNode->userLookLayer; // 查看深度 
			$userModel		= M('会员');
			$netName = $netNode->name;
			
			//判断是否在自己网体下,不在自己网体下不能查看
			if( I("REQUEST.uid/s")!='')
			{
				if( preg_match('/\'|\"|;|select|truncate|drop|insert|update|delete|join|union|into|load_file|outfile/i', I("REQUEST.uid/s"), $matches) ){
					$this->error(L('非法表单数据'));
				}
				if(I("REQUEST.uid/s") != $this->userinfo['编号']){
					$firstUser = $userModel->where(array("编号"=>trim(I("REQUEST.uid/s"))))->find();
					if($firstUser){
						$firstUserNetInfos = explode(',',$firstUser[$netName.'_网体数据']);
						$firstUserNetArray=array();
						foreach($firstUserNetInfos as $firstUserNetInfo){
							$firstUserNetInfoArr = explode('-',$firstUserNetInfo);
							$firstUserNetArray[] = $firstUserNetInfoArr[0];
						}
						
						if(!in_array($this->userinfo['id'],$firstUserNetArray)){
							$this->error(L('该'.$this->userobj->byname.'不在'.$netName.'网体下'));
						}
					}else{
						$this->error(L('该'.$this->userobj->byname.'不存在'));
					}
				}
			}
			$levelStr = '';
			foreach($levelsArr as $levelName=>$level){
				$levelStr .= ',a.'.$levelName;
			}
			
			//获取树
			if(I("REQUEST.uid/s")!=''){
				//需要加层数>0的条件
				$where = array(
					'编号'=>array('eq',I("REQUEST.uid/s")),
					$netName.'_层数'=>array('gt','0')
				);
				$result = M('会员')->where($where)->select();
			}else{
				$where = array(
					'编号'=>array('eq',$this->userinfo['编号']),
					$netName.'_层数'=>array('gt','0')
				);
				$result = M('会员')->where($where)->select();
			}
			$firstUserInfo = $result[0];
			$result = array();
			if($firstUserInfo){
				
				$upid = $firstUserInfo['id'];
				$upUserLayer = $firstUserInfo[$netName.'_层数'];
				
				// 循环获得下面几层数据
				for($i=0;$i<$showLayer-1;$i++){
					$where = ''.$netName.'_层数='.($upUserLayer+$i+1).' and (';
					foreach($netPlaceName[$netName] as $region){
						$where .= " find_in_set('{$upid}-{$region}',{$netName}_网体数据) or";
					}
					$where = trim($where,'or') .')';
					//$downUsers = $this->userobj->getUserAchieve("a.id,a.编号,a.姓名,a.状态,a.注册日期,a.审核日期{$levelStr},a.{$netName}_层数,a.{$netName}_团队总人数,a.{$netName}_团队人数,a.{$netName}_上级编号,a.{$netName}_位置",$where);
					$downUsers = M('会员')->where($where)->select();
					if(!$downUsers || ($userLookLayer !=0 && ($upUserLayer+$i+1-$this->userinfo[$netName.'_层数'])>=$userLookLayer)){
						break;
					}
					$result[$i] = $downUsers;
				}
			}
			$this->assign('thisUser',$this->userinfo);
			$this->assign('showLayer',$showLayer);
			$this->assign('firstUserInfo',$firstUserInfo);
			$this->assign('userNode',$this->userobj);
			$this->assign('netNode',$netNode);
			$this->assign('netName',$netName);
			$this->assign('netTree',$result);
			$this->display('net_place_tree2');
		}else{
			$showLayer		= $netNode->userNetLayer; //显示几层
			$userLookLayer  = $netNode->userLookLayer; // 查看深度 
			$userModel		= M('会员');
			$netName = $netNode->name;
			//店铺查看深度替换
			if(X('user')->shopWhere != '' && transform(X('user')->shopWhere,$this->userinfo)){
				$showLayer     = $netNode->shopNetLayer;
				$userLookLayer = $netNode->shopLookLayer;
			}
			//判断是否在自己网体下,不在自己网体下不能查看
			if( I("REQUEST.uid/s")!='')
			{
				if( preg_match('/\'|\"|;|select|truncate|drop|insert|update|delete|join|union|into|load_file|outfile/i', I("REQUEST.uid/s"), $matches) ){
					$this->error(L('非法表单数据'));
				}
				if(I("REQUEST.uid/s") != $this->userinfo['编号']){
					$firstUser = $userModel->where(array("编号"=>trim(I("REQUEST.uid/s"))))->find();
					if($firstUser){
						$firstUserNetInfos = explode(',',$firstUser[$netName.'_网体数据']);
						$firstUserNetArray=array();
						foreach($firstUserNetInfos as $firstUserNetInfo){
							$firstUserNetInfoArr = explode('-',$firstUserNetInfo);
							$firstUserNetArray[] = $firstUserNetInfoArr[0];
						}
						
						if(!in_array($this->userinfo['id'],$firstUserNetArray)){
							$this->error(L('该'.$this->userobj->byname.'不在'.$netName.'网体下'));
						}
						//超出深度
						if($userLookLayer>0 && ($firstUser[$netName.'_层数']-$this->userinfo[$netName.'_层数'])>$userLookLayer){
							$this->error(L("只允许查看".$userLookLayer."层内的会员"));
						}
					}else{
						$this->error(L('该'.$this->userobj->byname.'不存在'));
					}
				}
			}
			$levelStr = '';
			foreach($levelsArr as $levelName=>$level){
				$levelStr .= ',a.'.$levelName;
			}
			//获取树
			if(I("REQUEST.uid/s")!=''){
				//需要加层数>0的条件
				$where = array(
					'编号'=>array('eq',I("REQUEST.uid/s")),
					$netName.'_层数'=>array('gt','0')
				);
				$result = M('会员')->where($where)->select();
			}else{
				$where = array(
					'编号'=>array('eq',$this->userinfo['编号']),
					$netName.'_层数'=>array('gt','0')
				);
				$result = M('会员')->where($where)->select();
			}
			$firstUserInfo = $result[0];
			$result = array();
			if($firstUserInfo){
				$upid = $firstUserInfo['id'];
				$upUserLayer = $firstUserInfo[$netName.'_层数'];
				// 循环获得下面几层数据
				for($i=0;$i<$showLayer-1;$i++){
					$where = ''.$netName.'_层数='.($upUserLayer+$i+1).' and (';
					foreach($netPlaceName[$netName] as $region){
						$where .= " find_in_set('{$upid}-{$region}',{$netName}_网体数据) or";
					}
					$where = trim($where,'or') .')';
					$downUsers = M('会员')->where($where)->select();
					if(!$downUsers || ($userLookLayer !=0 && ($upUserLayer+$i+1-$this->userinfo[$netName.'_层数'])>$userLookLayer)){
						break;
					}
					$result[$i] = $downUsers;
				}
			}
			$this->assign('thisUser',$this->userinfo);
			$this->assign('showLayer',$showLayer);
			$this->assign('firstUserInfo',$firstUserInfo);
			$this->assign('userNode',$this->userobj);
			$this->assign('netNode',$netNode);
			$this->assign('netName',$netName);
			$this->assign('netTree',$result);
			$this->display('net_place_tree');
		}
	}
	/*
	* 显示 树状 网络分支图(推荐关系)
	*/
	private function showRamusTree($netNode,$netPlaceName,$levelsArr)
	{
		if(CONFIG('DEFAULT_THEME')=='ion'){
			if($netNode->shopNetDisp ==1 and $this->userinfo['服务中心'] == 0){
				$showLayer		= $netNode->shopNetLayer; //显示几层
			}else{
				$showLayer		= $netNode->userNetLayer; //显示几层
			}
			$userLookLayer  = $netNode->userLookLayer; // 查看深度 
			$userModel		= M('会员');
			
			$netName = $netNode->name;
			if( I('REQUEST.uid/s')!='')
			{
				if( preg_match('/\'|\"|;|select|truncate|drop|insert|update|delete|join|union|into|load_file|outfile/i', I("REQUEST.uid/s"), $matches) ){
					$this->error(L('非法表单数据'));
				}
				if(I('REQUEST.uid/s') != $this->userinfo['编号']){
					$firstUser = $userModel->where(array("编号"=>trim(I('REQUEST.uid/s')),"_string"=>"find_in_set('{$this->userinfo['id']}',{$netName}_网体数据)"))->find();
					if(!$firstUser){
						$this->error(L('该'.$this->userobj->byname.'不在'.$netName.'网体下'));
					}
				}
			}
			//获取树
			if(I("REQUEST.uid/s")!=''){
				$result = M('会员')->where(array('编号'=>I("REQUEST.uid/s")))->select();
			}else{
				$result = M('会员')->where(array('编号'=>$_SESSION[C('USER_AUTH_NUM')]))->select();
			}
			$firstUserInfo = $result[0];
			$result = array();
			if($firstUserInfo){
				$upid = $firstUserInfo['id'];
				$upUserLayer = $firstUserInfo[$netName.'_层数'];
				// 循环获得下面几层数据
				for($i=0;$i<$showLayer-1;$i++){
					$where = "{$netName}_层数=".($upUserLayer+$i+1)." and ";
					$where .= "find_in_set('{$upid}',{$netName}_网体数据)";
					$downUsers = M('会员')->where($where)->select();
					if(!$downUsers  || ($userLookLayer !=0 && ($upUserLayer+$i+1-$this->userinfo[$netName.'_层数'])>$userLookLayer)){
						break;
					}
					$result[$i] = $downUsers;
				}
			}
			$this->assign("disname",$netNode->userNameDisp);
			$this->assign("disnickname",$netNode->userAnotherNameDisp);
			$this->assign('thisUser',$this->userinfo);
			$this->assign('firstUserInfo',$firstUserInfo);
			$this->assign('userNode',$this->userobj);
			$this->assign('netNode',$netNode);
			$this->assign('netName',$netName);
			$this->assign('netTree',$result);
			$this->display('net_rec_tree2');
		}else{
			$showLayer		= $netNode->userNetLayer;  //显示几层
			$userLookLayer  = $netNode->userLookLayer; // 查看深度 
			$userModel		= M('会员');
			if($this->userinfo['服务中心']==1){
				$showLayer=$netNode->shopNetLayer;
				$userLookLayer  = $netNode->shopLookLayer;
			}
			$netName = $netNode->name;
			if( I("REQUEST.uid/s")!='')
			{
				if( preg_match('/\'|\"|;|select|truncate|drop|insert|update|delete|join|union|into|load_file|outfile/i', I("REQUEST.uid/s"), $matches) ){
					$this->error(L('非法表单数据'));
				}
				if(I("REQUEST.uid/s") != $this->userinfo['编号']){
					$firstUser = $userModel->where(array("编号"=>trim(I("REQUEST.uid/s")),"_string"=>"find_in_set('{$this->userinfo['id']}',{$netName}_网体数据)"))->find();
					if(!$firstUser){
						$this->error(L('该'.$this->userobj->byname.'不在'.$netName.'网体下'));
					}
					//超出深度
					if($userLookLayer>0 && ($firstUser[$netName.'_层数']-$this->userinfo[$netName.'_层数'])>$userLookLayer){
						$this->error(L("只允许查看".$userLookLayer."层内的会员"));
					}
				}
				
			}
			//获取树
			if(I("REQUEST.uid/s")!=''){
				$result = M('会员')->where(array('编号'=>I("REQUEST.uid/s")))->select();
			}else{
				$result = M('会员')->where(array('编号'=>$_SESSION[C('USER_AUTH_NUM')]))->select();
			}
			$firstUserInfo = $result[0];
			$result = array();
			if($firstUserInfo){
				$upid = $firstUserInfo['id'];
				$upUserLayer = $firstUserInfo[$netName.'_层数'];
				// 循环获得下面几层数据
				for($i=0;$i<$showLayer-1;$i++){
					$where = "{$netName}_层数=".($upUserLayer+$i+1)." and ";
					$where .= "find_in_set('{$upid}',{$netName}_网体数据)";
					$downUsers = M('会员')->where($where)->select();
					if(!$downUsers  || ($userLookLayer !=0 && ($upUserLayer+$i+1-$this->userinfo[$netName.'_层数'])>$userLookLayer)){
						break;
					}
					$result[$i] = $downUsers;
				}
			}
			$this->assign("disname",$netNode->userNameDisp);
			$this->assign("disnickname",$netNode->userAnotherNameDisp);
			$this->assign('thisUser',$this->userinfo);
			$this->assign('firstUserInfo',$firstUserInfo);
			$this->assign('userNode',$this->userobj);
			$this->assign('netNode',$netNode);
			$this->assign('netName',$netName);
			$this->assign('netTree',$result);
			$this->display('net_rec_tree');
		}
	}
	/**
	*幸运网
	*
	*/
	public function showLineTree($levelsArr,$net)
	{
		$fieldstr="";
		foreach(X("levels") as $level){
			$fieldstr.=",".$level->name;
		}
		$showLayer		= 10;
		$netName = $net->byname;
		//获得当前会员的点位
		$firstUser=array();
		$firstUser=M($net->name)->where(array("状态"=>0,"编号"=>$this->userinfo['编号']))->order("time asc")->find();
		$result=array();
		if($firstUser){
			$upuser=array();
			if(I("REQUEST.uid/s") != "" and I("REQUEST.uid/s") != $this->userinfo['编号']){
				$where=array("编号"=>I("request.uid/s"),"状态"=>0,"网体"=>$firstUser['网体'],"排序"=>array("gt",$firstUser['排序']));
				if(I("request.u_num/d")>0){
					$where['排序']=I("request.u_num/d");
				}
				$topUser=M($net->name)->where($where)->find();
				if($topUser!=""){
					$firstUser=$topUser;
				}
				$upuser=M($net->name)->where(array("id"=>$firstUser['上级'],"状态"=>0,"网体"=>$firstUser["网体"]))->order("排序 asc")->find();
			}
			$this->assign("upuser",$upuser);
			$Userinfo=M("会员")->where(array("id"=>$firstUser['userid']))->Field("姓名,状态,审核日期".$fieldstr)->find();
			$firstUser=array_merge($firstUser,$Userinfo);
			
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
		$this->assign('firstUserInfo',$firstUser);
		$this->assign('netTree',$result);
		$this->assign('netName',$netName);
		$this->display('net_line_tree');
	}
	/*
	* 打印会员级别
	*/
	public function print_user_level($level,$levelname)
	{
		if( isset( $this->userLevelArray[$levelname][$level] ) )
		{
			return $this->userLevelArray[$levelname][$level];
		}
		else
		{
			return $level;
		}
	}
	//网络图打印功能
	function printset(){
	  $this->display();
	}

}
?>