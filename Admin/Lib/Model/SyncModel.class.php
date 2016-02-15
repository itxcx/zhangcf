<?php
// 同步模块,功能封装
class SyncModel
{
	public $autoCheckFields=false;
    /**
    +----------------------------------------------------------
    * 同步获取应用的节点数据
    +----------------------------------------------------------
    */
	public function syncAppNodeList()
	{
		$Node			= M('Node');
		$appList		= $Node->field('id,name,is_sync_node,group,level')->where("level=1 and status=1")->select();
		
		foreach( $appList as $key1=>$app )
		{
			//如果开启了同步节点
			if( $app['is_sync_node'] )
			{
				$methodUrl	= $app['group']==''?
					"{$app['name']}://Sync{$app['name']}/returnNodeList"
					:
					"{$app['name']}://{$app['group']}/Sync{$app['name']}{$app['group']}/returnNodeList";
				$list = R($methodUrl);
				if( $list && count($list) > 0  )
				{
					//遍历模块
					foreach( $list as $module_name=>$module )
					{
						//如果模块存在,判断下面每一个方法是否存在
						$module_id = $this->moduleIsExists($Node,$app,$module);
						if( $module_id )
						{
							$module_idss[] = $module_id;
							//遍历方法
							foreach( $module['childs'] as $action_name=>$action )
							{
								$functions[] = $this->actionIsExists1($Node,$module_id,$action);
								//判断不存在方法时
								if( !$this->actionIsExists($Node,$module_id,$action) )
								{
									//不存在直接插入
								    $ress =	$this->actionAdd($Node,$action,$action_name,$module_id);
								    $functions[] = $ress;
								}
							}
						}
						else
						{
							//如果模块不存在,全部插入
							$modes_ids = $this->createModuleNode($Node,$module,$module_name,$app['id']);
							$module_idss[]=$modes_ids['module_id'];
							foreach($modes_ids['childs_id'] as $childs_id){
								$functions[]=$childs_id;
							}
						}
					}
				}
			}
		}
		if(isset($module_idss) && $module_idss){
			//删除数据表中多余的模块
			$this->moduledelete($module_idss);
		}
		if(isset($functions) && $functions){
			$this->deleteactions($functions);
		}
	}
	/*
	* 创建模块及模块下的方法
	*/
	private function createModuleNode(&$Node,$module,$module_name,$pid)
	{
		$addidary=array();
		$data['name']		= $module['module'];
		$data['title']		= $module_name;
		$data['sort']		= isset($module['sort'])?$module['sort']:0;
		$data['pid']		= $pid;
		$data['level']		= 2;
		$data['status']		= 1;
		$module_id			= $Node->add($data);
		$addidary["module_id"]=$module_id;
		if( count($module['childs']) > 0 )
		{
			//遍历插入方法
			foreach( $module['childs'] as $action_name=>$action )
			{
				$fangfass = $this->actionAdd($Node,$action,$action_name,$module_id);
				$addidary["childs_id"][]=$fangfass;
			}
		}
		return $addidary;
	}

	/*
	* 判断是否已存在该模块
	*/
	private function moduleIsExists(&$Node,$app,$module)
	{
		$where['pid']	= $app['id'];
		$where['name']	= $module['module'];
		$where['level']	= 2;
		$result			= $Node->where($where)->getField('id');
		return $result;
	}
    	private function moduleIsExists1(&$Node,$app,$module)
	{
		$where['pid']	= $app['id'];
		$where['name']	= $module['module'];
		$where['level']	= 2;
		$result			= $Node->where($where)->getField('id');
		return $result;
	}
	//删除数据表中不存在的模块
	private function moduledelete($module_idss)
	{
		//查询所有的模块
		$where['level']	= '2';
		$result1			= M('node')->where($where)->select();
		foreach($result1 as $v){
		  $ids[] = $v['id'];
		}
	   //数据库中的所有模块是$ids 文件中的所有模块是$module_idss 判断数据库中多余的
	   foreach($ids as $id1){
	     if(!in_array($id1,$module_idss)){
	        //删除id=$id1的模块 和他所对应的Action
	        M('node')->where(array('id'=>$id1))->delete();
	     }
	   }
	}
		//删除数据表中不存在的action
	private function deleteactions($functions)
	{
		//查询所有的模块
		$where['level']	= '3';
		//$where['setParent']	= '信息管理s';
		$result1			= M('node')->where($where)->select();
		foreach($result1 as $v){
		  $ids[] = $v['id'];
		}
		//数据库中的所有方法是$ids 文件中的所有方法是$functions 判断数据库中多余的
		foreach($ids as $id1){
			if(!in_array($id1,$functions)){
				//删除id=$id1的方法 
				M('node')->where(array('id'=>$id1))->delete();
			}
		}
	}
	/*
	* 判断是否已存在该方法
	*/
	private function actionIsExists(&$Node,$module_id,$action)
	{
		if( is_string($action) )
		{
			$where['name']	= $action;
		}
		else if( is_array($action) )
		{
			$where['name']	= $action['action'];
		}
		$where['level']	= 3;
		$where['pid']	= $module_id;
		$result			= $Node->where($where)->getField('id');
		if( $result )
		{
			return true;
		}
		return false;
	}
	private function actionIsExists1(&$Node,$module_id,$action)
	{
		if( is_string($action) )
		{
			$where['name']	= $action;
		}
		else if( is_array($action) )
		{
			$where['name']	= $action['action'];
		}
		$where['level']	= 3;
		$where['pid']	= $module_id;
		$result			= $Node->where($where)->getField('id');

		if( $result )
		{
			return $result;
		}
	}

	/*
	* 增加方法节点
	*/
	private function actionAdd(&$Node,$action,$action_name,$module_id)
	{
		if( is_string($action) )
		{
			$data['name']		= $action;
		}
	    if( is_array($action) )
		{
			$data['name']		= $action['action'];
			$data['sort']		= isset($action['sort'])?$action['sort']:0;
			$data['remark']		= isset($action['remark'])?$action['remark']:'';
			$data['setParent']  = isset($action['setParent'])?$action['setParent']:'';
			$data['parent']		= isset($action['parent'])?$action['parent']:'';
		}
		$result2=0;
		if($data['parent'] && $data['setParent']){
			//查询权限中是否有存在title
		   $wheres['parent']	= $data['parent'];
		   $wheres['setParent']	= $data['setParent'];
		   $wheres['title']	= $action_name;
		   $result2			= $Node->where($wheres)->getField('id');
		}
	    if($result2){
	    	//修改
	       $upd['name'] = $action['action'];
	       $upd['title'] = $action_name;
	       $Node->where(array('id'=>$result2))->save($upd);
	       return $result2;
	    }else{
			$data['title']		= $action_name;
			$data['pid']		= $module_id;
			$data['level']		= 3;
			$data['status']		= 1;
			$rdss = $Node->add($data);
			return $rdss;
		}
	}
    /**
    +----------------------------------------------------------
    * 同步获取应用的菜单数据(仅获取一级菜单)
    +----------------------------------------------------------
    */
	public function syncAppMenuList1( $appId )
	{
		$Node			= M('Node');
		$app			= $Node->where("id='{$appId}'")->find();
		$menuList		= array();
		//如果开启了同步菜单
		if( $app['is_sync_menu'] )
		{
			$methodUrl	= $app['group']==''?
				"{$app['name']}://Sync{$app['name']}/returnMenuList"
				:
				"{$app['name']}://{$app['group']}/Sync{$app['name']}{$app['group']}/returnMenuList";
			
			$list = R($methodUrl);
			if( $list && count($list) > 0  )
			{
				//遍历模块组
				foreach( $list as $group_name=>$group  )
				{
					//开始遍历模块列表
					foreach( $group['childs'] as $menu_name=>$menu )
					{
						$this->searchMenuItem1($app,$menu_name,$menu,$menuList);
					}
				}
			}
		}
		foreach($menuList as  $key=>&$menu)
		{
			if($menu['name']=='系统设置')
			{
				unset($menuList[$key]);
			};
		}
		return $menuList;
	}

    /**
    +----------------------------------------------------------
    * 同步获取应用的菜单数据
	*
	* $appId		: 应用ID
	*
	* $find_menu	: 指定要获取的应用的菜单项[可选]
	*
    +----------------------------------------------------------
    */
	public function syncAppMenuList( $appId ,$find_menu='')
	{
		$Node			= M('Node');
		$app			= $Node->find($appId);
		$appName		= $app['name'];
		$str = '';
		//如果开启了同步菜单
		if( $app['is_sync_menu'] )
		{
			$methodUrl	= $app['group']==''?
				"{$app['name']}://Sync{$app['name']}/returnMenuList"
				:
				"{$app['name']}://{$app['group']}/Sync{$app['name']}{$app['group']}/returnMenuList";
			$list	= R($methodUrl);
			if($appName=='Admin')
			{
				$conmenu=array();
				$node_datas=$Node->where("Name<>'Admin' and pid=0")->select();
				if($node_datas)
				//目前判定的为后台的系统设置菜单.需要遍历其他项目菜单.并把系统设置中的功能合并
				foreach($node_datas as $Node_data)
				{
					$methodUrl	= $Node_data['group']==''?
					"{$Node_data['name']}://Sync{$Node_data['name']}/returnMenuList"
					:
					"{$Node_data['name']}://{$Node_data['group']}/Sync{$Node_data['name']}{$Node_data['group']}/returnMenuList";
					
					$list2	= R($methodUrl);
					$list2  = array_shift($list2);
					foreach($list2['childs'] as $key=>$list3)
					{
						if($key=='系统设置')
						{
							$conmenu=array_merge($conmenu,$list3['childs']);
						}
					}
				}
				$list['系统设置']['childs']=array_merge($list['系统设置']['childs'],$conmenu);
			}
			if( $list && count($list) > 0  )
			{
				$str .= '<div class="accordion" fillSpace="sideBar">'."\n";
				//遍历模块组
				foreach( $list as $group_name=>$group  )
				{
					$str .= '	<div class="accordionHeader">'."\n";
					$str .= '	<h2><span>system</span>'.$group_name.'</h2>'."\n";
					$str .= '	</div>'."\n";
					$str .= '	<div class="accordionContent">'."\n";
					$str .= '	<ul class="tree treeFolder expand">'."\n";
					//开始遍历模块列表
					foreach( $group['childs'] as $menu_name=>$menu )
					{
						if( $find_menu )
						{
							if( $menu_name == $find_menu ) 
								$this->searchMenuItem($app,$menu_name,$menu,$str);
						}
						else
						{
							$this->searchMenuItem($app,$menu_name,$menu,$str);
						}
					}
					$str .= '	</ul>'."\n";
					$str .= '	</div>'."\n";
				}
				$str .= '</div>'."\n";
			}
		}
		return $str;
	}
	/*
	* 搜索菜单数组(仅获取一级菜单)
	*/
	private function searchMenuItem1(&$app,$name,$menu,&$menuList)
	{
		//直传链接地址的情况
		if( is_string($menu) )
		{
			$data	= $this->resolveUrl($menu);
			//如果是超级管理员 或者 有权限
			if( isset($_SESSION[C('RBAC_SUPER_ADMIN_KEY')]) || $this->havePower($data) )
			{
				$_menu['name']		= $name;
				$_menu['url']		= $menu;
				$_menu['appId']		= $app['id'];
				$_menu['appName']	= $app['name'];
				$_menu['appGroup']	= $app['group'];
				$menuList[]			= $_menu;
				return true;
			}
		}
		else if( is_array($menu) )
		{
			//如果存在下级菜单
			if( isset($menu['childs']) && count($menu['childs']) >0 )
			{
				$finded			= false; //子节点至少有1个以上的权限才显示父节点
				$temp_str		= '';
				$new_array		= array();
				//遍历子节点
				foreach( $menu['childs'] as $_name=>$__menu )
				{
					$result				 = $this->searchMenuItem1($app, $_name, $__menu["url"], $new_array);
					if( $result )
						$finded = true;
				}
				if( $finded )
				{
					$_menu['name']		= $name;
					$_menu['url']		= "__APP__/Menu/show/app/{$app['name']}/item/{$name}";
					$_menu['appId']		= $app['id'];
					$_menu['appName']	= $app['name'];
					$_menu['appGroup']	= $app['group'];
					$menuList[]			= $_menu;
					return true;
				}
			}
			else
			{
				$data			= $this->resolveUrl($menu['url']);
				$actionUrl		= $menu['url'];
				//echo($actionUrl)."\r\n";
				//如果是超级管理员 或者 有权限
				if(isset($_SESSION[C('RBAC_SUPER_ADMIN_KEY')]) || $this->havePower($data) )
				{
					$_menu['name']		= $name;
					$_menu['url']		= $actionUrl;
					$_menu['appId']		= $app['id'];
					$_menu['appName']	= $app['name'];
					$_menu['appGroup']	= $app['group'];
					$menuList[]			= $_menu;
					return true;
				}
			}
		}
		return false;
	}
	
	/*
	* 递归搜索菜单数组
	*/
	private function searchMenuItem(&$app,$name,$menu,&$str)
	{
		//直传链接地址的情况
		if( is_string($menu) )
		{
			$data	= $this->resolveUrl($menu);
			//如果是超级管理员 或者 有权限
			if( isset($_SESSION[C('RBAC_SUPER_ADMIN_KEY')]) || $this->havePower($data) )
			{
				$str .= "		<li><a href='{$menu}' target='navTab' rel='".md5($menu)."'>{$name}</a>".'</li>'."\n";
				return true;
			}
		}
		else if( is_array($menu) )
		{
			//如果存在下级菜单
			if( isset($menu['childs']) && count($menu['childs']) >0 )
			{
				$temp_str	= '';
				$finded					 = false; //子节点至少有1个以上的权限才显示父节点
				$temp_str				.= "		<li><a href='#'>{$name}</a>";
				$temp_str				.= '		<ul>'."\n";
				//遍历子节点
				foreach( $menu['childs'] as $_name=>$_menu )
				{
					$result				 = $this->searchMenuItem($app, $_name, $_menu, $temp_str);
					if( $result ) $finded = true;
				}
				$temp_str .= '		</ul>'."\n";
				$temp_str .= '		</li>'."\n";
				if( $finded ) 
				{
					$str .= $temp_str;
					return true;
				}
			}
			else
			{
				$data			= $this->resolveUrl($menu['url']);
				$actionUrl		= $menu['url'];
				//如果是超级管理员 或者 有权限
				if( isset($_SESSION[C('RBAC_SUPER_ADMIN_KEY')]) || $this->havePower($data) )
				{
					$str .= '		<li><a href="'.$actionUrl.'"';
					$str .= isset($menu['target'])?" target=\"{$menu['target']}\"":' target="navTab"';
					//是否设置其他属性
					if( isset($menu['attrs']) && is_array($menu['attrs']) )
					{
						foreach($menu['attrs'] as $key=>$val)
						{
							$str .= " {$key}=\"{$val}\"";
						}
					}
					$str .= ' rel="'.(isset($menu['rel'])?$menu['rel']:md5($actionUrl)).'">';
					$str .= $name.'</a>'.'</li>'."\n";
					return true;
				}
			}
		}
		return false;
	}

	/*
	* 判定是否有权限访问,无权限的不会显示出来
	*/
	private function havePower($data)
	{
		$appName	= $data['app'];
		$module		= $data['module'];
		$action		= $data['action'];
		$group		= $data['group'];
		$adminapp=RBAC::readAccessList($_SESSION[C('RBAC_ADMIN_AUTH_KEY')]);
		if( !isset($adminapp[strtoupper($appName.'_'.$group)][strtoupper($module)][strtoupper($action)]) )
		{
			return false;
		}
		return true;
	}

	/*
	* 解析URL链接,返回其中的应用名称,模块名称和操作名称
	*/
	private function resolveUrl($url)
	{
		$url_data			= explode('/',$url);
		//获取当前项目分组
		if( $url_data[1]=='index.php?s=')
		{
			$app='DmsAdmin';
		}else{
			$app="Admin";
			$url_data[4]=$url_data[2];
			$url_data[3]=$url_data[1];
			$url_data[2]='';
		}
		$data['app']		= $app;
		$data['group']		= isset($url_data[2])?$url_data[2]:"";
		$data['module']		= isset($url_data[3])?$url_data[3]:"";
		$data['action']		= isset($url_data[4])?$url_data[4]:"";
		return $data;
	}
}
?>