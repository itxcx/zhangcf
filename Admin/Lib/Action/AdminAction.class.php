<?php
// 管理员模块
class AdminAction extends CommonAction 
{
	/*管理员列表*/
	public function index(){
		$setButton=array(
			'添加管理员'=>array("class"=>"add","href"=>"__APP__/Admin/add","target"=>"navTab"),
			'修改管理员'=>array("class"=>"edit","href"=>"__APP__/Admin/edit/id/{tl_id}","target"=>"navTab"),
			'删除管理员'=>array("class"=>"delete","href"=>"__APP__/Admin/delete/id/{tl_id}","target"=>"ajaxTodo","title"=>"确定要删除吗?"),
			'重置权限列表'=>array("class"=>"delete","href"=>"__APP__/Admin/updateNode","target"=>"ajaxTodo","title"=>"重置权限后需取消除超管外的权限?"),
			'后台登陆域名绑定'=>array("class"=>"edit","href"=>"__APP__/Admin/binddomain","target"=>"dialog","title"=>"确定要绑定该域名吗?"),
			'扫码登录绑定'=>array("class"=>"add","href"=>"__APP__/Admin/yangcong_bind/id/{tl_id}","target"=>"dialog","height"=>360,"width"=>560),
        );
        if(!adminshow('admin_scode')){unset($setButton['扫码登录绑定']);}
        $list=new TableListAction("admin"); // 实例化Model 传表名称
        $list->table("admin as a")->join("left join (select c.admin_id,d.name from role_admin c inner join role d on c.role_id=d.id ) as b on b.admin_id=a.id");
        $list->setButton = $setButton;       // 定义按钮显示
		$list->order("last_login_time desc");  //定义查询条件
		$list->field("a.*,b.name");
		$list->addshow("编号",array("row"=>'[id]'));
		$list->addshow("登录帐号",array("row"=>"[account]","searchMode"=>"text","excelMode"=>"text",'searchPosition'=>'top',"searchRow"=>'a.account')); 
		$list->addshow("管理员名称",array("row"=>"[nickname]","searchMode"=>"text","excelMode"=>"text",'searchPosition'=>'top',"searchRow"=>'a.nickname')); 
		$list->addshow("所属权限组",array("row"=>"[name]","searchMode"=>"text","excelMode"=>"text",'searchPosition'=>'top',"searchRow"=>'b.name')); 
		$list->addshow("登录次数",array("row"=>"[login_count]","searchMode"=>"text","excelMode"=>"text",'searchPosition'=>'top',"searchRow"=>'a.login_count')); 
		$list->addshow("最近登录时间",array("row"=>"[last_login_time]","searchMode"=>"date","format"=>"time","excelMode"=>"date",'searchPosition'=>'top',"searchRow"=>'a.last_login_time')); 
		$list->addshow("最近登录IP",array("row"=>"[last_login_ip]","searchMode"=>"text","excelMode"=>"text",'searchPosition'=>'top',"searchRow"=>'a.last_login_ip')); 
		$list->addshow("状态",array("row"=>array(array(&$this,"_showstatus"),"[status]"),"searchMode"=>"text","searchSelect"=>array("待审"=>"0","正常"=>"1","禁用"=>"1"),'searchRow'=>'a.status'));
		$this->assign('list',$list->getHtml());
        $this->display();
	}
	// 绑定请求页
	public function yangcong_bind(){
		$admin_id = I("get.id/s");
		$admin = M('admin',null)->where(array('id'=>$admin_id))->find();
		if(!$admin){
			throw_exception(L('_OPERATION_WRONG_'));
		}

		// 查询绑定次数
		$bind = M('dms_mapping',null)->where(array('admin_uid'=>$admin_id,'status'=>1))->select();
		if($bind===false){
			throw_exception(L('_OPERATION_WRONG_'));
		}

		$count = 0;
		if(is_array($bind)){
			$count = count($bind);
		}
		if($count<1) $count = 0;
		$this->assign('count',$count);
		$this->assign('account',$admin['account']);
		$this->display();

	}

	// 表单验证
	public function yangcong_check(){

		// 验证选择的管理员密码
		$status = I("post.status/s");
		$account = I("post.account/s");
		$password = I("post.password/s");

		if($account=="")
        {
			$this->error('帐号错误！');
		}
        elseif ($password=="")
        {
			$this->error('密码必须！');
		}

		$admin = M('admin',null)->where(array('account'=>$account,'password'=>md100($password)))->find();
		if(!$admin){
			$this->error('密码错误');
		}

		// 解除绑定
		if($status=='2'){
			M()->startTrans();
			$map = M('dms_mapping',null)->where(array('admin_uid'=>$admin['id']))->save(array('status'=>0));
			
			if($map===false){
				$this->error('解绑出错！');
				die;
			}

			if($map===0){
				$this->error('尚未绑定！');
				die;
			}

			M()->commit();

			$this->success('解绑成功! ','','2');
		}

		$_SESSION['bind_admin_id'] = $admin['id'];

		$this->success('');

	}

	// 请求二维码 及事件读取
	public function yangcong_ac()
	{
		// 引入接口类
		vendor('yangcong.secken');

		$admin_scode=explode(',',CONFIG('ADMIN_SCODE'));
		list($app_id, $app_key, $auth_id) = $admin_scode;

		//填写洋葱网给您申请的app_id
		$app_id = $app_id ?: '';

		//填写您在洋葱网申请的app_key
		$app_key = $app_key ?: '';

		//填写您在洋葱网申请的auth_id
		$auth_id = $auth_id ?: '';

		//实例化洋葱认证类
		$secken_api = new secken($app_id,$app_key,$auth_id);


		$ac = isset($_GET['ac']) ? $_GET['ac'] : 'none';

		//发起验证请求
		if($ac == 'qrcode_for_auth'){
		    $auth_type = isset($_GET['auth_type']) ? $_GET['auth_type'] : 1;
		    $resp = $secken_api -> getAuth($auth_type);
		    echo json_encode($resp);
		    
		}

		//获取事件结果
		if($ac == 'event_result'){
		    $event_id = isset($_GET['event_id']) ? $_GET['event_id'] : '';
		    $resp = $secken_api -> getResult($event_id);

		    if(is_array($resp)){
		        $resp['description'] = $secken_api -> getMessage();
		    }

		    echo json_encode($resp);
		}

	}

	// 扫码成功后的绑定方法
	public function yangcong_check_bind()
	{
	    $yangcong_uid = isset($_POST['yangcong_uid']) ? I("post.yangcong_uid/s") : '';
	    $admin_id = $_SESSION['bind_admin_id'];
	    unset($_SESSION['bind_admin_id']);

	    /**
	     * 请先确保您已经执行了table.sql中的语句
	     */

	    $map = M('dms_mapping',null)->where(array('yangcong_uid'=>$yangcong_uid,'status'=>1))->select();
	    if($map===false){
	    	$ret = array('status'=>0,'info'=>'连接数据库失败!');
	        echo json_encode($ret);
	    }
	    elseif(is_array($map)){
	    	$ret = array('status'=>0,'info'=>'该APP账号已绑定！');
	        echo json_encode($ret);
	    }else{
	    	$bind = M('dms_mapping',null)->add(array('admin_uid'=>$admin_id,'yangcong_uid'=>$yangcong_uid,'bind_time'=>systemTime(),'status'=>1));
	        if($bind){
	            $ret = array('status'=>1,'info'=>'绑定成功！');
	            echo json_encode($ret);
	        }else{
	            $ret = array('status'=>0,'info'=>'数据插入失败!');
	            echo json_encode($ret);
	        }
	    }
	}


	function _showstatus($status){
		if($status==0){
			return "待审";
		}else if($status==1){
			return "正常";
		}else{
			return "禁用";
		}
	}
	//添加管理员页面
	public function add(){
		//修复所有权限节点的表
		M()->startTrans();
		$Sync	= D('Sync');
		$Sync->syncAppNodeList();
		//获取节点树
		$newtreeList = $this->getNewTreeList(array());
		$roleList = M('Role')->select();
		if(isset($roleList))
		foreach($roleList as $k=>$val){
			$roleAccessList = M('RoleAccess')->where(array('role_id'=>$val['id']))->select();
			$roleAccessStr = '';
			if($roleAccessList)
			foreach($roleAccessList as $roleAccess){
				$roleAccessStr .= $roleAccess['node_id'].'-';
			}
			$roleList[$k]['access'] = trim($roleAccessStr,'-');
		}
		M()->commit();
		$this->assign('roleList',$roleList);
		$this->assign('newtreeList',$newtreeList);
		$this->assign('adminAccessArray',array());
		$this->assign('roleAccessArray',array());
		$this->display();
	}
	//修改管理员页面
	public function edit(){
		if(strpos(I("get.id/s"),',') !== false){
			$this->error('参数错误!');
		}
		//当前管理员
		$Admin		= D('Admin');
		$admin_id	= I("request.id/d");
		$Sync	= D('Sync');
		M()->startTrans();
		//修复所有权限节点的表
		$Sync->syncAppNodeList();
		//失效key值
		M('yubicloud',null)->where(array('account_id'=>$admin_id,"state"=>1,"endtime"=>array(array("gt",0),array('lt',strtotime(date('Y-m-d',systemTime()))))))->save(array('state'=>2));
		M()->commit();
		//获取当前管理员 【管理员授权节点】
		$adminAccessArray	= $Admin->getAdminAccess($admin_id);
		//获取当前管理员 【角色授权节点】
		$roleAccessArray	= $Admin->getRoleAccess($admin_id);
		//获取节点树
		$newtreeList = $this->getNewTreeList($adminAccessArray,$roleAccessArray);
		$roleList = M('Role')->select();
		$roleAdminResult = M('RoleAdmin')->field('role_id')->where(array('admin_id'=>$admin_id))->select();
		$roleAdmin = array();
		if(isset($roleAdminResult))
		foreach($roleAdminResult as $val){
			$roleAdmin[] = $val['role_id'];
		}
		if(isset($roleList))
		foreach($roleList as $k=>$val){
			$roleAccessList = M('RoleAccess')->where(array('role_id'=>$val['id']))->select();
			$roleAccessStr = '';
			if($roleAccessList)
			foreach($roleAccessList as $roleAccess){
				$roleAccessStr .= $roleAccess['node_id'].'-';
			}
			$roleList[$k]['access'] = trim($roleAccessStr,'-');
		}
		$this->assign('roleList',$roleList);
		$this->assign('roleAdmin',$roleAdmin);
		$this->assign('roleList',$roleList);
		$this->assign('newtreeList',$newtreeList);
		$this->assign('adminAccessArray',$adminAccessArray);
		$this->assign('roleAccessArray',$roleAccessArray);
		$name   = $this->getActionName();
		$model  = M('admin');
		$vo     = $model->getById ( $admin_id );
		//当前登录的会员
		$admins = M('admin')->where(array('id'=>$_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ]))->find();
		//判断是否可以修改超管权限
		$show=false;
		if($admins['admin_status']==1 && $vo['id']!=$admins['id']){
			//判断有几个超管了
			$counts = M("admin")->where(array('admin_status'=>1))->count();
			if($counts<2){
				$show=true;
			}
		}
		if($vo['admin_status']==1 && $vo['id']!=$admins['id']){
			$show=true;
		}
		$this->assign("show",$show);
		//yubikey列表
		$yubiprefixs = M('yubicloud',null)->where(array('account_id'=>$admin_id))->select();
		$this->assign('yubiprefixs',$yubiprefixs);
		//临时密码
		if(!F('passrand'.md5($_SERVER["SERVER_NAME"])))
		{
			F('passrand'.md5($_SERVER["SERVER_NAME"]),$this->getguid());
		}
		$rndpass = (F('passrand'.md5($_SERVER["SERVER_NAME"])).$vo['password'].date('Ymd',time()));
		$rndpass = md5($rndpass);
		$this->assign('rndpass',$rndpass);
		$this->assign('admins',$admins);
		$this->assign ( 'vo', $vo );
		$this->display();
	}
	//添加保存
	public function insert()
	{
		M()->startTrans();
		$model  = D("Admin");
		if (false === $model->create ()) {
			$this->error($model->getError ());
		}
		//添加之前检查有无定义过滤器方法
		if (method_exists ( $this, '_filter_insert_before' )) {
			$this->_filter_insert_before ( $model );
		}
		$result		= $model->add ();
		if ($result!==false)  //保存成功
        {
        	//设置权限
			$this->addAccess($result,I("post."));
			//保存操作日志
			$logData['帐号']	= I("post.account/s");
			$logData['昵称']	= I("post.nickname/s");
			$this->saveAdminLog($logData,'','添加管理员');
			//添加之后检查有无定义过滤器方法
			if (method_exists ( $this, '_filter_insert_after' )) {
				$this->_filter_insert_after ( $model,$result );
			}
			M()->commit();
			$this->success("添加完成");
        }else{
        	//失败提示
			$this->error ('新增失败!');
        }
	}
	//修改保存
	public function update()
	{
		M()->startTrans();
		$Model=D('Admin');
		// 查出修改之前的数据
		$oldData		= $Model->find(I("post.id/d"));
		if (false === $Model->create ()) {
			$this->error ( $Model->getError () );
		}
		//修改之前检查有无定义过滤器方法
		if (method_exists ( $this, '_filter_update_before' )) {
			$this->_filter_update_before ( $Model );
		}
		//判断修改权限 不能自己修改自己的权限
		$admins = M('admin')->where(array('id'=>$_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ]))->find();
		if($admins['admin_status']==1 && I("post.id/d")!=$admins['id'] && $oldData['admin_status']==I("post.admin_status/d"))
		{
			//设置权限
		  	$this->addAccess(I("post.id/d"),I("post."));
		}
		//判断超管条件
		if($oldData['admin_status']!=I("post.admin_status/d"))
		{
			if(I("post.admin_status/d")==1){
				$counts = M("admin")->where(array('admin_status'=>1))->count();
				if($counts>=2){
					$this->error("超管权限管理员存在两个以上请取消后在设置");
				}
			}
			if(I("post.admin_status")==1)
			{
				$str="管理员".I("post.account/s")."设置为超管权限";
			}
			else
			{
				$str="管理员".I("post.account/s")."取消超管权限";
			}
		}
		// 更新数据
		$result		= $Model->save();
		//修改之后检查有无定义过滤器方法
		if (method_exists ( $this, '_filter_update_after' )) {
			$this->_filter_update_after ( $Model,I("request.id/d"));
		}
		//查出修改之后的数据
		$newData	= M("admin")->find(I("request.id/d"));
		$oldData['帐号']	= $result['account'];
		$oldData['昵称']	= $result['nickname'];
		$this->saveAdminLog($newData,$oldData,'修改管理员');
		M()->commit();
		if(isset($str))
			$this->success($str);
		$this->success("修改完成");
	}
	//删除管理员
	public function delete()
	{
		//获取之前的数据
		$Model				= M('Admin');
		$errMsg = "";
		$succNum = 0;
		$errNum = 0; 
		foreach(explode(',',I("request.id/s")) as $id){
			$admin				= $Model->find($id);
			//判断超管权限
			if($admin['admin_status']==1){
				$admin_result=$Model->where(array("admin_status"=>1,"id"=>array("NOTIN",I("request.id/s"))))->find();
				if(!isset($admin_result) || !$admin_result){
					$errNum++;
					$errMsg .= $admin['account'].'为超管暂不能删除<br/>';
					continue;
				}
			}
			$oldData['帐号']	= $admin['account'];
			$oldData['昵称']	= $admin['nickname'];
			M()->startTrans();
			$result	= M("admin")->where(array('id'=>$id))->delete();
			if($result){
				M('admin_access')->where(array('admin_id'=>$id))->delete();
				$this->saveAdminLog($oldData,'','删除管理员');
				M()->commit();
				$succNum++;
			}else{
				$errNum++;
				$errMsg .= $admin['account'].'：删除失败<br/>';
				M()->rollback();
			}
		}
		if($errNum !=0){
			$this->error("删除成功：".$succNum .'条记录；删除失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("删除成功：".$succNum .'条记录；');
		}
	}
	//重置权限列表
	public function updateNode(){
		$admins = M('admin')->where(array('id'=>$_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ]))->find();
    	if(!$admins['admin_status']){
    	   $this->error('无权限操作');
    	}
    	M()->startTrans();
		M('node')->where('level>1')->delete();
	
		M()->execute("truncate table admin_access");
		M()->execute("truncate table role_access");
		//重新保存所有权限节点
		$Sync	= D('Sync');
		$Sync->syncAppNodeList();
        M()->commit();
		$this->success('重置完成');
	}
	//绑定后台登陆域名
	public function bind(){
		if(I("post.status/d")==1){
			if(I("post.domain/s")==""){
				$_POST['domain']=$_SERVER['HTTP_HOST'];
			}
			M()->startTrans();
			CONFIG('binddomain',I("post.domain/s"));
			M()->commit();
			$this->success('绑定成功');
		}else{
			//取消绑定
			M()->startTrans();
			CONFIG('binddomain',"");
			M()->commit();
			$this->success('取消绑定成功');
		}
	}
	//添加 yubicloud
	public function addyubicloudprefix(){
		$this->assign('managerid',I("get.aid/d"));
		$this->display();
	}
	//添加保存yubicloud
	public function addyubicloudprefix_save(){
		file_put_contents('record.txt',strtolower(I("post.newyubiopt/s")));
		if(I("post.newyubiopt/s")!=""){
			require(ROOT_PATH."/Public/yubicloud.class.php");
			$yubicloudobj = new Yubicloud();
			$res = $yubicloudobj->checkOnYubiCloud(strtolower(I("post.newyubiopt/s")));
			if($res=='OK')
			{
				$yubicloudprefix = substr(strtolower(I("post.newyubiopt/s")),0,12);
				$res2 = M('yubicloud')->where(array('yubi_prefix'=>$yubicloudprefix,'account_id'=>I("post.id/d")))->find();
				if($res2){
					$this->success('已经绑定过');
				}
				$days=0;
				if(I("post.yubidays/d")>0){
					$days=I("post.yubidays/d");
				}
				$admininfo=M("admin")->where(array("id"=>I("request.id/d")))->find();
				$data = array(
					'account_id'=>I("request.id/d"),
					'yubi_prefix'=>$yubicloudprefix,
					'yubi_prefix_name'=>I("post.yubiname/s"),
					'addtime'=>systemTime(),
					'endtime'=>($days==0)?0:(strtotime(date("Y-m-d",systemTime()))+86400*$days),
					'state'=>1,
				);
				M()->startTrans();
				M('yubicloud',null)->add($data);
				M()->commit();
				$this->saveAdminLog('','',$yubicloudprefix."绑定".$admininfo['account']."成功");
				$this->success('绑定成功');
			}
		}
		$this->error('验证失败！');
	}
	//添加保存yubicloud
	public function delyubicloudprefix(){
		//获取删除的yubikey信息
		$yubiinfo=M('yubicloud',null)->where(array("id"=>I("request.kid/d")))->find();
		if(!isset($yubiinfo)){
			$this->error("未获取到yubi信息");
		}
		//当前管理员
		$admininfo=M("admin")->where(array("id"=>I("request.aid/d")))->find();
		if(!isset($admininfo)){
			$this->error("未获取到管理员信息");
		}
		$this->assign("yubiprefix",$yubiinfo);
		$this->assign("admininfo",$admininfo);
		$this->display();
	}
	//取消 yubicloud
	public function cancelyubiprefix(){
		if(I("post.ayubiopt/s")!=""){
			$admininfo=M("admin")->where(array("id"=>I("request.adid/d")))->find();
			if(!isset($admininfo)){
				$this->error("无效信息");
			}
			require(ROOT_PATH."/Public/yubicloud.class.php");
			$yubicloudobj = new Yubicloud();
			$res = $yubicloudobj->checkOnYubiCloud(strtolower(I("post.ayubiopt/s")));
			if($res!='OK'){
				$this->error("无效信息");
			}
			//验证key
			$yubicloudprefix = substr(strtolower(I("post.ayubiopt/s")),0,12);
			$yubicloud=M('yubicloud',null)->where(array("yubi_prefix"=>$yubicloudprefix,"account_id"=>array(array("eq",$admininfo['id']),array("eq",$_SESSION[C('RBAC_ADMIN_AUTH_KEY')]),"or")))->find();
			if(!isset($yubicloud)){
				$this->error("验证失败");
			}
			if(I("request.id/d")>0){
				M()->startTrans();
				M('yubicloud',null)->delete(I("request.id/d"));
				M()->commit();
				$this->saveAdminLog('','',"删除".$admininfo['account']."的yubikey值".$yubicloudprefix);
				$this->success('已删除');
			}else{
				$this->error("无效信息");
			}
		}else{
			$this->error("请输入yubikey值");
		}
	}
	/*
	* 奖权限保存到数据库中，然后再写入到静态文件中，以便于在管理员登陆时直接读取下次时直接读取
	*/
	public function addAccess($admin_id,$post){
		//权限设置
		$Admin			= D('Admin');
		$AdminAccess	= M('AdminAccess');
		//获取选中的节点
		$nodeList	= array();
		$moduleArr = array();
		$Node		= M('Node');
		if(isset($post['node'])){
			foreach( $post['node'] as $_node )
			{
				$_node_array		= explode('_',$_node);
				$node['admin_id']	= $admin_id;
				$node['node_id']	= $_node_array[0];
				$node['pid']		= $_node_array[1];
				$node['level']		= $_node_array[2];
				$nodeList[]			= $node;
				$moduleArr[$node['pid']] =1;
			}
		}
		$appArr = array();
		foreach($moduleArr as $module=>$val){
			$moduleRe = $Node->find($module);
			$node['admin_id']	= $admin_id;
			$node['node_id']	= $moduleRe['id'];
			$node['pid']		= $moduleRe['pid'];
			$node['level']		= $moduleRe['level'];
			$nodeList[]			= $node;
			$appArr[$node['pid']] = 1;
		}
		foreach($appArr as $app=>$val){
			$appRe = $Node->find($app);
			$node['admin_id']	= $admin_id;
			$node['node_id']	= $appRe['id'];
			$node['pid']		= $appRe['pid'];
			$node['level']		= $appRe['level'];
			$nodeList[]			= $node;
		}
		//清空之前的授权
		$AdminAccess->where("admin_id='{$admin_id}'")->delete();
		//插入新的授权
		foreach($nodeList as $node)
		{
			$AdminAccess->add($node);
		}
		//权限组设置
		$Role		= M('Role');
		$RoleAdmin	= M('RoleAdmin');
		$roleList	= array();
		if(isset($post['role'])){
			foreach($post['role'] as $_role)
			{
				$role['role_id']	= $_role;
				$role['admin_id']	= $admin_id;
				$roleList[]			= $role;
			}
		}
		//清空之前的角色关联
		$RoleAdmin->where("admin_id='{$admin_id}'")->delete();
		//插入新的角色关联
		foreach($roleList as $role)
		{
			$RoleAdmin->add($role);
		}
		//将管理员的权限写入静态文件
		import ( 'ORG.Util.RBAC' );
		RBAC::writeAccessList($admin_id);
	}
	//获取权限节点树
	public function getNewTreeList($adminAccessArray=array(),$roleAccessArray=array()){
		//同步所有应用的节点数据
		$Node				= D('Node');
		$ftreeList			= $Node->getNodeTree();
		if($ftreeList)
		foreach($ftreeList as &$treeList){
		  foreach($treeList as $fk=>$firstList){
			$firstAccess = 0;		//0无权限  1部分权限  2全部权限
			$firstAccessNum = 0;
			$roleAccess = 0;
			$count = 0;
			foreach($firstList as $sk=>$secondList){
				
				if(!isset($secondList['title'])){
					
					foreach($secondList as $tk=>$thirdList){
						if(in_array($thirdList['id'],$adminAccessArray)){
							$firstAccessNum++;
						}
						if(in_array($thirdList['id'],$roleAccessArray)){
							$roleAccess = 1;
						}
						$count++;
					}
				}else{
					if(in_array($secondList['id'],$adminAccessArray)){
						$firstAccessNum++;
					}
					if(in_array($secondList['id'],$roleAccessArray)){
						$roleAccess = 1;
					}
					$count++;
				}
			}
			if($firstAccessNum == 0){
				$treeList[$fk]['adminAccess'] = 0;
			}elseif($firstAccessNum < $count){
				$treeList[$fk]['adminAccess'] = 1;
			}else{
				$treeList[$fk]['adminAccess'] = 2;
			}
			$treeList[$fk]['roleAccess'] = $roleAccess;
		  }
		}
		return $ftreeList;
	}
	//生成随机临时密码
	function getguid(){
	    if (function_exists('com_create_guid')){
	        return com_create_guid();
	    }else{
	        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
	        $charid = strtoupper(md5(uniqid(rand(), true)));
	        $hyphen = chr(45);// "-"
	        $uuid = chr(123)// "{"
	                .substr($charid, 0, 8).$hyphen
	                .substr($charid, 8, 4).$hyphen
	                .substr($charid,12, 4).$hyphen
	                .substr($charid,16, 4).$hyphen
	                .substr($charid,20,12)
	                .chr(125);// "}"
	        return $uuid;
	    }
	}
}
?>