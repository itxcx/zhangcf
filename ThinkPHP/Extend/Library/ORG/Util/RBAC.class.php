<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: RBAC.class.php 2504 2011-12-28 07:35:29Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 基于角色的数据库方式验证类
 +------------------------------------------------------------------------------
 * @category   ORG
 * @package  ORG
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: RBAC.class.php 2504 2011-12-28 07:35:29Z liu21st $
 +------------------------------------------------------------------------------
 */

class RBAC {
    // 认证方法
    static public function authenticate($map,$model='')
    {
        if(empty($model)) $model =  C('RBAC_ADMIN_AUTH_TABLE');
        //使用给定的Map进行认证
        return M($model)->where($map)->find();
    }

    //用于检测用户权限的方法,并保存到Session中
    static function saveAccessList($authId=null)
    {
        if(null===$authId)   $authId = $_SESSION[C('RBAC_ADMIN_AUTH_KEY')];
        // 如果使用普通权限模式，保存当前用户的访问权限列表
        // 对超级管理员开放所有权限
        if( C('RBAC_ADMIN_AUTH_TYPE') !=2 && (!isset($_SESSION[C('RBAC_SUPER_ADMIN_KEY')]) || !$_SESSION[C('RBAC_SUPER_ADMIN_KEY')]) )
        {
            $_SESSION['_ACCESS_LIST']	=	RBAC::getAccessList($authId);
        }
        return ;
    }


    //检查当前操作是否需要认证
    static function checkAccess()
    {
		//这个方法在一般会返回false
        //如果项目要求认证，并且当前模块需要认证，则进行权限认证
		//RBAC_ADMIN_AUTH_ON 为false的时候之间 返回 false
        if( C('RBAC_ADMIN_AUTH_ON') ){
			$_module	=	array();
			$_action	=	array();
            if("" != C('RBAC_REQUIRE_AUTH_MODULE')) {
                //需要认证的模块
                $_module['yes'] = explode(',',strtoupper(C('RBAC_REQUIRE_AUTH_MODULE')));
            }else {
                //无需认证的模块
                $_module['no'] = explode(',',strtoupper(C('RBAC_NOT_AUTH_MODULE')));
            }

            //检查当前模块是否需要认证
			/*
             *若当前的ACTION在认证RBAC_REQUIRE_AUTH_MODULE里且RBAC_REQUIRE_AUTH_MODULE不为空

			 *或者不在不认证RBAC_NOT_AUTH_MODULE里且RBAC_NOT_AUTH_MODULE不为空面 
			 *则执行里面的 
			 
			 否则返回false 有一个为空也返回false


			 **一般在RBAC_NOT_AUTH_MODULE里面有一个Public 这样只要不是Public的Action都要进行验证 执行里面
			*/
            if((!empty($_module['no']) && !in_array(strtoupper(MODULE_NAME),$_module['no'])) || (!empty($_module['yes']) && in_array(strtoupper(MODULE_NAME),$_module['yes']))) 
			{
				if("" != C('RBAC_REQUIRE_AUTH_ACTION')) 
				{
					//需要认证的操作
					$_action['yes'] = explode(',',strtoupper(C('RBAC_REQUIRE_AUTH_ACTION')));
				}
				else 
				{
					//无需认证的操作
					$_action['no'] = explode(',',strtoupper(C('RBAC_NOT_AUTH_ACTION')));
				}

				//检查当前操作是否需要认证

				/*
				* 若当前的操作在认证RBAC_REQUIRE_AUTH_ACTION里且RBAC_REQUIRE_AUTH_ACTION不为空
				* 或者不在不认证RBAC_NOT_AUTH_ACTION里且RBAC_NOT_AUTH_ACTION不为空面 
				* 则执行里面的 返回true  否则返回false
				* 一般这两个都为空 所以会返回false
				*/

				if( (!empty($_action['no']) && !in_array(strtoupper(ACTION_NAME),$_action['no'])) || (!empty($_action['yes']) && in_array(strtoupper(ACTION_NAME),$_action['yes'])) ) 
				{
					return true;
				}
				else 
				{
					return false;
				}
            }
			else 
			{
                return false;
            }
        }
        return false;
    }

    //权限认证的过滤器方法
    static public function AccessDecision($appName=APP_NAME)
    {
        //检查是否需要认证
		//APP_NAME 当前的项目名  admin 文件夹名
		//MODEULE_NAME  当前模块名 应该是Action名
		//ACTION_NAME  当前的操作名 Action 里面的方法名
		
		// RBAC::checkAccess()在填写不验证的PUblic 不填写必须验证和不验证的操作时基本返回false 
        if( RBAC::checkAccess() ) 
		{
			//这里要判断一下因为没开分组的情况下GROUP_NAME 会 == 'GROUP_NAME'
			$group_name		= defined('GROUP_NAME')?GROUP_NAME:'';
            //认证标识名称
            $accessGuid		= md5( $appName . $group_name . MODULE_NAME . ACTION_NAME  );
			//不是超级管理员
            if( empty($_SESSION[C('RBAC_SUPER_ADMIN_KEY')]) ) 
			{
				//实时认证,每次都到数据库查询权限
                if( C('RBAC_ADMIN_AUTH_TYPE')==2 ) 
				{
                    $accessList = RBAC::getAccessList( $_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ] );	
                }
				//SESSION认证,权限只有登录的时候获取并存入SESSION一次 
				else
				{
                    // 如果认证标识已经通过
                    if(isset($_SESSION['_VALIDATED'][$accessGuid]) && $_SESSION['_VALIDATED'][$accessGuid] ) 
					{
                        return true;
                    }
                    if(isset($_SESSION[C('RBAC_ADMIN_AUTH_KEY')])){
                    	//登录验证模式，比较登录后保存的权限访问列表
                    	$adminapp=self::readAccessList($_SESSION[C('RBAC_ADMIN_AUTH_KEY')]);
                    	$accessList = $adminapp;
                    }else{
                    	return false;
                    }
                }
                //判断是否为组件化模式，如果是，验证其全模块名
                $module = defined('P_MODULE_NAME')?  P_MODULE_NAME   :   MODULE_NAME;
                if( !isset( $accessList[ strtoupper($appName.'_'.$group_name) ][ strtoupper($module) ][ strtoupper(ACTION_NAME) ] ) ) 
				{
                    $_SESSION['_VALIDATED'][$accessGuid]  =   false;
                    return false;
                }
                else 
				{
                    $_SESSION['_VALIDATED'][$accessGuid]	=	true;
                }
            }
			else
			{
                //超级管理员无需认证
				return true;
			}
        }
        return true;
    }
    /**
    写入或者读取缓存文件的内容
    */
    static public function rbacF($name,$value=''){
    	static $_cache = array();
	    $filename = ROOT_PATH.'Admin/Conf/'.$name.'.php';
	    if ('' !== $value) {
	        if (is_null($value)) {
	            //删除缓存
	            return unlink($filename);
	        } else {
	            // 缓存数据
	            $dir = dirname($filename);
	            // 目录不存在则创建
	            if (!is_dir($dir))
	                mkdir($dir);
	            $_cache[$name] =   $value;
	            return file_put_contents($filename, strip_whitespace("<?php\nreturn " . var_export($value, true) . ";\n?>"));
	        }
	    }
	    if (isset($_cache[$name]))
	        return $_cache[$name];
	    // 获取缓存数据
	    if (is_file($filename)) {
	        $value = include $filename;
	        $_cache[$name] = $value;
	    } else {
	        $value = false;
	    }
	    return $value;
    }
    /**
    +-----------------------------------------------------------
    写入当前认证号的所有权限列表进入缓存
    */
    static public function writeAccessList($authId,$accname='adminapplist'){
    	//获取当前会员的
    	$applist=self::getAccessList($authId);
    	$adminapplist=self::rbacF($accname);
    	if($adminapplist){
    		$adminapplist[$authId]=$applist;
    	}else{
    		$adminapplist=array($authId=>$applist);
    	}
    	self::rbacF('adminapplist',$adminapplist);
    }
    /**
    +-----------------------------------------------------------
    读取权限的缓存文件内容
    */
    static public function readAccessList($authId){
    	//获取当前管理员的
    	$adminapplist=self::rbacF('adminapplist');
    	if($adminapplist && isset($adminapplist[$authId])){
    		$applist=$adminapplist[$authId];
    	}else{
    		$applist="";
    	}
    	return $applist;
    }
    /**
     +----------------------------------------------------------
     * 取得当前认证号的所有权限列表
     +----------------------------------------------------------
     * @param integer $authId 用户ID
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    static public function getAccessList($authId)
    {
		$db     =   Db::getInstance(C('RBAC_DB_DSN'));
     
        $table = array(
			'role'			=> C('RBAC_ROLE_TABLE'),		//role
			'user'			=> C('RBAC_ROLE_ADMIN_TABLE'),  //role_admin
			'access'		=> C('RBAC_ROLE_ACCESS_TABLE'), //role_access
			'node'			=> C('RBAC_NODE_TABLE'),		//node
			'admin'			=> C('RBAC_ADMIN_AUTH_TABLE'),	//admin
			'admin_access'  => C('RBAC_ADMIN_ACCESS_TABLE'),//admin_access
		);

		
		//获取应用授权
		$apps		= RBAC::getAppAccessList($db,$table,$authId);
		//var_dump($db,$table,$authId);
		//echo 11111;die;
		
		$access		= array();
		
		//遍历应用
        foreach($apps as $key=>$app) 
		{
            $appId								= $app['id'];
			$groupName							= $app['group'];
            $appName							= $app['name'];
            $access[ strtoupper($appName.'_'.$groupName) ]		= array();

			//获取模块授权
			$modules							= RBAC::getModuleAccessList($db,$table,$authId,$appId);

            //遍历模块
            foreach($modules as $key=>$module) 
			{
                $moduleId		= $module['id'];
                $moduleName		= $module['name'];
                $actions		= RBAC::getActionAccessList($db,$table,$authId,$moduleId);
                $action_list	= array();
                foreach ($actions as $action)
				{
					if(strpos($action['name'],',') !== false){
						$actionNameArray = explode(',',trim($action['name'],','));
						foreach($actionNameArray as $actionName){
							$action_list[$actionName]	 = $action['id'];
						}
					}else{
						//读取模块的操作权限
						$action_list[ $action['name'] ]	 = $action['id'];
					}
                }

				//保存操作权限到其对应的模块上
                $access[ strtoupper($appName.'_'.$groupName) ][ strtoupper($moduleName) ]   =  array_change_key_case($action_list,CASE_UPPER);
            }
        }
        return $access;       
    }


	/*
	* 取得当前认证号的所有应用权限
	*/
	static public function getAppAccessList(&$db,&$table,$authId)
	{
		// 查询已授权的【角色应用节点】
        $sql		= "select node.id,node.name,node.group from ".
                    $table['role']." as role,".
                    $table['user']." as user,".
                    $table['access']." as access ,".
                    $table['node']." as node ".
                    "where user.admin_id='{$authId}' and user.role_id=role.id and access.role_id=role.id and role.status=1 and access.node_id=node.id and node.level=1 and node.status=1 order by node.sort asc";

        $role_apps	= $db->query($sql);


		// 查询已授权的【管理员应用节点】
		$sql		= "select node.id,node.name,node.group from ".
					$table['admin']." as admin,".
					$table['admin_access']." as access,".
					$table['node']." as node ".
					"where admin.id='{$authId}' and access.admin_id=admin.id and admin.status=1 and access.node_id=node.id and node.level=1 and node.status=1 order by node.sort asc";
		//echo $sql;
		$_SESSION['sql'] = $sql;
		$admin_app	= $db->query($sql);
		

		//【角色应用节点授权】 和 【管理员应用节点授权】合并
		$apps		= $role_apps;
		
		$added_app = array();
		foreach( $admin_app as $key1=>$app1 )
		{
			$finded = false;
			foreach( $apps as $key2=>$app2 )
			{
				if( $app1['name'] == $app2['name'] )
				{
					$finded = true;
					break;
				}
			}
			if( !$finded )
			{
				$added_app[] = $app1;
			}
		}

		$apps = array_merge($apps,$added_app);
		return $apps;
	}

	/*
	* 取得当前认证号的指定应用的所有模块权限
	*/
	static public function getModuleAccessList(&$db,&$table,$authId,$appId)
	{
		 // 查询 已授权的【角色模块节点】
		$sql    =   "select node.id,node.name,node.args from ".
				$table['role']." as role,".
				$table['user']." as user,".
				$table['access']." as access ,".
				$table['node']." as node ".
				"where user.admin_id='{$authId}' and user.role_id=role.id and access.role_id=role.id and role.status=1 and access.node_id=node.id and node.level=2 and node.pid={$appId} and node.status=1";
		$role_modules =   $db->query($sql);


		 // 查询已授权的【管理员模块节点】
		$sql		= "select node.id,node.name,node.args from ".
				$table['admin']." as admin,".
				$table['admin_access']." as access,".
				$table['node']." as node ".
				"where admin.id='{$authId}' and access.admin_id=admin.id and admin.status=1 and access.node_id=node.id and node.level=2 and node.pid={$appId} and node.status=1";
		$admin_modules	= $db->query($sql);

		//【角色模块节点授权】 和 【管理员模块节点授权】合并
		$modules		= $role_modules;
		
		$added_modules	= array();
		foreach( $admin_modules as $key1=>$module1 )
		{
			$finded = false;
			foreach( $modules as $key2=>$module2 )
			{
				if( $module1['name'] == $module2['name'] && $module1['args'] == $module2['args'] )
				{
					$finded = true;
					break;
				}
			}
			if( !$finded )
			{
				$added_modules[] = $module1;
			}
		}

		$modules = array_merge($modules,$added_modules);
		return $modules;
	}

	/*
	* 取得当前认证号的指定模块下的所有操作权限
	*/
	static public function getActionAccessList(&$db,&$table,$authId,$moduleId)
	{
		// 查询 已授权的【角色操作节点】
		$sql			=   "select node.id,node.name from ".
			$table['role']." as role,".
			$table['user']." as user,".
			$table['access']." as access ,".
			$table['node']." as node ".
			"where user.admin_id='{$authId}' and user.role_id=role.id and access.role_id=role.id and role.status=1 and access.node_id=node.id and node.level=3 and node.pid={$moduleId} and node.status=1";
		$role_actions =   $db->query($sql);

		// 查询 已授权的【管理员操作节点】
		$sql		= "select node.id,node.name,node.args from ".
			$table['admin']." as admin,".
			$table['admin_access']." as access,".
			$table['node']." as node ".
			"where admin.id='{$authId}' and access.admin_id=admin.id and admin.status=1 and access.node_id=node.id and node.level=3 and node.pid={$moduleId} and node.status=1";
		$admin_actions =   $db->query($sql);
		//【角色模块节点授权】 和 【管理员模块节点授权】合并
		$actions		= $role_actions;
		
		$added_actions	= array();
		foreach( $admin_actions as $key1=>$action1 )
		{
			$finded = false;
			foreach( $actions as $key2=>$action2 )
			{
				if( $action1['name'] == $action2['name'] )
				{
					$finded = true;
					break;
				}
			}
			if( !$finded )
			{
				$added_actions[] = $action1;
			}
		}

		$actions = array_merge($actions,$added_actions);
		return $actions;
	}
}
?>