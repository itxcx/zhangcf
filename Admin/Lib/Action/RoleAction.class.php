<?php
// 角色模块
class RoleAction extends CommonAction 
{
	
	public function index()
	{
		$list=new TableListAction("Role",null);
        $setButton=array(
			'添加权限组'=>array("class"=>"add"   ,"href"=>__URL__."/add"              ,"target"=>"navTab"),
			'修改权限组'=>array("class"=>"edit"  ,"href"=>__URL__."/edit/id/{tl_id}"  ,"target"=>"navTab"),
            '删除权限组'=>array("class"=>"delete","href"=>__URL__."/delete/id/{tl_id}","target"=>"ajaxTodo" ,"title"=>"确定要删除吗?"),
        );
        $list->setButton = $setButton;     // 定义按钮显示
        $list->addshow("ID",array("row"=>'[id]'));
        $list->addshow("名称",array("row"=>'[name]',"searchRow"=>'name',"searchMode"=>"text"));
        $list->addshow("状态",array("row"=>array(array($this,'getStatus'),'[status]')));
        
		echo $list->getHtml();
	}
	public function getStatus($status)
	{
		if($status == '0')
		{
			return '待审';
		}
		if($status == '1')
		{
			return '正常';
		}
		if($status == '2')
		{
			return '禁用';
		}
	}
	//显示修改角色页面之前
	public function _before_edit()
	{
		if(strpos(I("get.id/s"),',') !== false){
			$this->error('参数错误!');
		}
		$role_id		= I("get.id/s");

		//获取当前角色的所有授权
		$Access			= M('RoleAccess');
		$accessList		= $Access->field('node_id')->where(array("role_id"=>$role_id))->select();
		
		//转换成索引数组
		$accessIndexArray = array();
		if($accessList)
		foreach( $accessList as $_access)
		{
			$accessIndexArray[] = $_access['node_id'];
		}
		//获取节点树
		M()->startTrans();
		$newtreeList		= $this->getNewTreeList($accessIndexArray);
		M()->commit();
		$this->assign('newtreeList',$newtreeList);
		$this->assign('accessList',$accessIndexArray);
		//$this->assign('treeList',$treeList);
	}

	//显示添加角色页面之前
	public function _before_add()
	{
		//获取节点树
		M()->startTrans();
		$newtreeList		= $this->getNewTreeList(array());
		M()->commit();
		//dump($newtreeList);
		$this->assign('accessList',array());
		//dump($newtreeList);
		$this->assign('newtreeList',$newtreeList);
	}
	
	//成功添加角色成功之后
	public function _filter_insert_after(&$model,$role_id)
	{
		$Access			= M('RoleAccess');
		//获取选中的节点
		$nodeList	= array();
		
		$moduleArr = array();
		foreach( I("post.node/a") as $_node )
		{
			$_node_array		= explode('_',$_node);
			$node['role_id']	= $role_id;
			$node['node_id']	= $_node_array[0];
			$node['pid']		= $_node_array[1];
			$node['level']		= $_node_array[2];
			$nodeList[]			= $node;
			$moduleArr[$node['pid']] =1;
		}

		$Node		= M('Node');
		$appArr = array();
		foreach($moduleArr as $module=>$val){
			$moduleRe = $Node->find($module);
			$node['role_id']	= $role_id;
			$node['node_id']	= $moduleRe['id'];
			$node['pid']		= $moduleRe['pid'];
			$node['level']		= $moduleRe['level'];
			$nodeList[]			= $node;
			$appArr[$node['pid']] = 1;
			
		}
		foreach($appArr as $app=>$val){
			$appRe = $Node->find($app);
			$node['role_id']	= $role_id;
			$node['node_id']	= $appRe['id'];
			$node['pid']		= $appRe['pid'];
			$node['level']		= $appRe['level'];
			$nodeList[]			= $node;
		}

		//插入新的授权
		foreach($nodeList as $node)
		{
			$Access->add($node);
		}
	}
	//成功修改角色之后
	public function _filter_update_after(&$model,$role_id)
	{
		$Access			= M('RoleAccess');
		//获取选中的节点
		$nodeList	= array();
		$moduleArr = array();
		foreach( I("post.node/a") as $_node )
		{
			$_node_array		= explode('_',$_node);
			$node['role_id']	= $role_id;
			$node['node_id']	= $_node_array[0];
			$node['pid']		= $_node_array[1];
			$node['level']		= $_node_array[2];
			$nodeList[]			= $node;
			$moduleArr[$node['pid']] =1;
		}


		$Node		= M('Node');
		$appArr = array();
		foreach($moduleArr as $module=>$val){
			$moduleRe = $Node->find($module);
			$node['role_id']	= $role_id;
			$node['node_id']	= $moduleRe['id'];
			$node['pid']		= $moduleRe['pid'];
			$node['level']		= $moduleRe['level'];
			$nodeList[]			= $node;
			$appArr[$node['pid']] = 1;
			
		}
		foreach($appArr as $app=>$val){
			$appRe = $Node->find($app);
			$node['role_id']	= $role_id;
			$node['node_id']	= $appRe['id'];
			$node['pid']		= $appRe['pid'];
			$node['level']		= $appRe['level'];
			$nodeList[]			= $node;
		}
		M()->startTrans();
		//清空之前的授权
		$Access->where("role_id='{$role_id}'")->delete();

		//插入新的授权
		foreach($nodeList as $node)
		{
			$Access->add($node);
		}
		M()->commit();
	}

	public function getNewTreeList($accessIndexArray=array()){
		
		//同步所有应用的节点数据
		$Sync	= D('Sync');
		$Sync->syncAppNodeList();

		$Node				= D('Node');
		$ftreeList			= $Node->getNodeTree();

		foreach($ftreeList as &$treeList){
		
		  foreach($treeList as $fk=>$firstList){
			
			$roleAccess = 0;
			$count = 0;
			foreach($firstList as $sk=>$secondList){
				
				if(!isset($secondList['title'])){
					
					foreach($secondList as $tk=>$thirdList){
						if(in_array($thirdList['id'],$accessIndexArray)){
							$roleAccess++;
						}
						$count++;
					}
				}else{
					if(in_array($secondList['id'],$accessIndexArray)){
						$roleAccess++;
					}
					$count++;
				}
			}
			if($roleAccess == 0){
				$treeList[$fk]['roleAccess'] = 0;
			}elseif($roleAccess < $count){
				$treeList[$fk]['roleAccess'] = 1;
			}else{
				$treeList[$fk]['roleAccess'] = 2;
			}
			
		  }
		}
		return $ftreeList;
	}
}
?>