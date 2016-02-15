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
 * 基于会员角色的数据库方式验证类
 +------------------------------------------------------------------------------
 * @category   ORG
 * @package  ORG
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: RBAC.class.php 2504 2011-12-28 07:35:29Z liu21st $
 +------------------------------------------------------------------------------
 */

class RBACUser {
    // 认证方法
    static public function authenticate($map,$model='')
    {
        if(empty($model)) $model =  C('RBAC_USER_AUTH_TABLE');
        //使用给定的Map进行认证
        return M($model)->where($map)->find();
    }

    //用于检测用户权限的方法,并保存到Session中
    static function saveAccessList($authId=null)
    {
        if(null===$authId)   $authId = $_SESSION[C('RBAC_USER_AUTH_KEY')];
        // 如果使用普通权限模式，保存当前用户的访问权限列表
        // 对超级管理员开放所有权限
        if( C('RBAC_USER_AUTH_TYPE') !=2 )
        {
            $_SESSION['_ACCESS_LIST_U']	=	RBACUser::getAccessList($authId);
        }
        return ;
    }


    //检查当前操作是否需要认证
    static function checkAccess()
    {
		//这个方法在一般会返回false
        //如果项目要求认证，并且当前模块需要认证，则进行权限认证
		//RBAC_ADMIN_AUTH_ON 为false的时候之间 返回 false
        if( C('RBAC_USER_AUTH_ON') ){
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
		

		// RBACUser::checkAccess()在填写不验证的PUblic 不填写必须验证和不验证的操作时基本返回false 
        if( RBACUser::checkAccess() ) 
		{

			//参数 args
			$args			= isset($_REQUEST['args'])?$_REQUEST['args']:'';

			//dump( $_SESSION );
			//exit;
			//这里要判断一下因为没开分组的情况下GROUP_NAME 会 == 'GROUP_NAME'
			$group_name		= GROUP_NAME=='GROUP_NAME'?'':GROUP_NAME;

            //认证标识名称
            $accessGuid		= md5( $appName . $group_name . MODULE_NAME . '_' . $args . ACTION_NAME  );

				
			//实时认证,每次都到数据库查询权限
			if( C('RBAC_USER_AUTH_TYPE')==2 ) 
			{
				$accessList = RBACUser::getAccessList( $_SESSION[ C('RBAC_USER_AUTH_KEY') ] );	
			}
			//SESSION认证,权限只有登录的时候获取并存入SESSION一次 
			else
			{
				// 如果认证标识已经通过
				if( $_SESSION['_VALIDATED_U'][$accessGuid] ) 
				{
					return true;
				}
				//登录验证模式，比较登录后保存的权限访问列表
				$accessList = $_SESSION['_ACCESS_LIST_U'];
			}
			//判断是否为组件化模式，如果是，验证其全模块名
			$module = defined('P_MODULE_NAME')?  P_MODULE_NAME   :   MODULE_NAME;

			if( !isset( $accessList[ strtoupper($appName.'_'.$group_name) ][ strtoupper($module.'_'.$args) ][ strtoupper(ACTION_NAME) ] ) ) 
			{
				$_SESSION['_VALIDATED_U'][$accessGuid]  =   false;
				return false;
			}
			else 
			{
				$_SESSION['_VALIDATED_U'][$accessGuid]	=	true;
			}

        }
        return true;
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
			'role'			=> C('RBAC_ROLE_TABLE'),
			'user'			=> C('RBAC_ROLE_USER_TABLE'),
			'access'		=> C('RBAC_ROLE_ACCESS_TABLE'),
			'node'			=> C('RBAC_NODE_TABLE'),
		);

		
		//获取应用授权
		$apps		= RBACUser::getAppAccessList($db,$table,$authId);

		
		
		$access		= array();
		
		//遍历应用
        foreach($apps as $key=>$app) 
		{
            $appId								= $app['id'];
			$groupName							= $app['group'];
            $appName							= $app['name'];
            $access[ strtoupper($appName.'_'.$groupName) ]		= array();

			//获取模块授权
			$modules							= RBACUser::getModuleAccessList($db,$table,$authId,$appId);

            //遍历模块
            foreach($modules as $key=>$module) 
			{
                $moduleId		= $module['id'];
                $moduleName		= $module['name'];
				$moduleArgs		= $module['args'];

                $actions		= RBACUser::getActionAccessList($db,$table,$authId,$moduleId);
                $action_list	= array();
                foreach ($actions as $action)
				{
					//读取模块的操作权限
                    $action_list[ $action['name'] ]	 = $action['id'];
                }

				//保存操作权限到其对应的模块上
                $access[ strtoupper($appName.'_'.$groupName) ][ strtoupper($moduleName.'_'.$moduleArgs) ]   =  array_change_key_case($action_list,CASE_UPPER);
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
                    "where user.user_id='{$authId}' and user.role_id=role.id and access.role_id=role.id and role.status=1 and access.node_id=node.id and node.level=1 and node.status=1 and node.type=1";

        $role_apps	= $db->query($sql);

		return $role_apps;
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
				"where user.user_id='{$authId}' and user.role_id=role.id and access.role_id=role.id and role.status=1 and access.node_id=node.id and node.level=2 and node.pid={$appId} and node.status=1";
		$role_modules =   $db->query($sql);
		return $role_modules;
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
			"where user.user_id='{$authId}' and user.role_id=role.id and access.role_id=role.id and role.status=1 and access.node_id=node.id and node.level=3 and node.pid={$moduleId} and node.status=1";
		$role_actions =   $db->query($sql);
		return $role_actions;
	}
}
?>