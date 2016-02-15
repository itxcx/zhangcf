<?php
// 会员模块
class UserAction extends CommonAction 
{
	function _filter(&$map)
	{
        if( isset($_REQUEST['account']) && $_REQUEST['account'] !='' )
        {
            $map['account'] =  array('like',"%{$_REQUEST['account']}%");    
        }
	}

	/*
	* 设置会员权限
	*/
	public function autho1rize ()
	{
		$Admin			= D('Admin');
		$AdminAccess	= M('AdminAccess');
		if( isset($_POST['submit']) )
		{
			//获取选中的节点
			$admin_id	= $_POST['admin_id'];
			$nodeList	= array();
			
			foreach( $_POST['node'] as $_node )
			{
				$_node_array		= explode('_',$_node);
				$node['admin_id']	= $admin_id;
				$node['node_id']	= $_node_array[0];
				$node['pid']		= $_node_array[1];
				$node['level']		= $_node_array[2];
				$nodeList[]			= $node;
			}
			M()->startTrans();

			//清空之前的授权
			$AdminAccess->where("admin_id='{$admin_id}'")->delete();

			//插入新的授权
			foreach($nodeList as $node)
			{
				$AdminAccess->add($node);
			}
			$this->saveAdminLog('','',"授权成功");
			M()->commit();
			$this->ajaxReturn('','授权成功',1);
		}
		else
		{
			//同步所有应用的节点数据
			$Sync	= D('Sync');
			$Sync->syncAppNodeList();

			//获取管理员信息
			$admin_id			= intval($_REQUEST['id']);
			$where['id']		= $admin_id;
			$adminInfo			= $Admin->where($where)->find();


			//获取节点树
			$Node				= D('Node');
			$treeList			= $Node->getNodeTree();

			
			//获取当前管理员 【管理员授权节点】
			$adminAccessArray	= $Admin->getAdminAccess($admin_id);

			//获取当前管理员 【角色授权节点】
			$roleAccessArray	= $Admin->getRoleAccess($admin_id);
	
	
			$this->assign('adminAccessArray',$adminAccessArray);
			$this->assign('roleAccessArray',$roleAccessArray);
			$this->assign('treeList',$treeList);
			$this->assign('adminInfo',$adminInfo);
			//dump($adminInfo);
			$this->display();
		}
	}

	/*
	* 设置会员角色
	*/
	public function role()
	{
		$User		= M('User');
		$Role		= M('Role');
		$RoleUser	= M('RoleUser');
		if( isset($_POST['submit']) )
		{
			//获取选中的节点
			$user_id	= $_POST['user_id'];
			$roleList	= array();
			
			foreach( $_POST['role'] as $_role )
			{
				$role['role_id']	= $_role;
				$role['user_id']	= $user_id;
				$roleList[]			= $role;
			}
			M()->startTrans();
			//清空之前的角色关联
			$RoleUser->where("user_id='{$user_id}'")->delete();

			//插入新的角色关联
			foreach($roleList as $role)
			{
				$RoleUser->add($role);
			}
			$this->saveAdminLog('','',"权限组设置");
			M()->commit();
			$this->ajaxReturn('','权限组设置成功',1);
		}
		else
		{
			$user_id			= intval($_REQUEST['id']);
			$where['id']		= $user_id;
			$userInfo			= $User->where($where)->find();

			//获取所有会员角色列表
			$roleList			= $Role->where("type=1 and status=1")->select();

			//获取帐号对应的所有角色
			$adminRoleList		= $RoleUser->field('role_id')->where("user_id='{$user_id}'")->select();

			//转成索引数组
			$adminRoleIndexList	= array();

			foreach( $adminRoleList as $adminRole )
			{
				$adminRoleIndexList[] = $adminRole['role_id'];
			}

			$this->assign('adminRoleIndexList',$adminRoleIndexList);
			$this->assign('roleList',$roleList);
			$this->assign('userInfo',$userInfo);
			$this->display();
		}
	}


	public function _filter_insert_after()
	{
		//保存操作日志
		$logData['帐号']	= $_POST['account'];
		$logData['密码']	= $_POST['password'];

		$this->saveAdminLog('','','添加会员');
	}

	public function _filter_update_before()
	{
		//获取之前的数据
		$Model				= M('User');

		$result				= $Model->find($_REQUEST['id']);

		$oldData['帐号']	= $result['account'];

		//保存操作日志
		$logData['帐号']	= $_POST['account'];
		$logData['密码']	= $_POST['password'];

		$this->saveAdminLog('','','修改会员');
	}

	public function _filter_delete_before()
	{
		//获取之前的数据
		$Model				= M('User');
		$result				= $Model->find($_REQUEST['id']);
		$oldData['帐号']	= $result['account'];
		$this->saveAdminLog('','','删除会员');
	}
}
?>