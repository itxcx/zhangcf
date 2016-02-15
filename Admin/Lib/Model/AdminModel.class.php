<?php
// 后台用户模型
class AdminModel extends CommonModel {
	public $_validate	=	array(
		array('account','/^[a-z]\w{3,}$/i','帐号格式错误'),
		array('password','require','密码必须'),
		//array('password','checkPwd','密码必须为大于8为的字母数字组合',0,'callback'),
		array('password','checkPwd','密码必须大于8位',0,'callback'),
		array('nickname','require','昵称必须'),
		array('repassword','require','确认密码必须'),
		array('repassword','password','确认密码不一致',self::EXISTS_VAILIDATE,'confirm'),
		array('account','','帐号已经存在',self::EXISTS_VAILIDATE,'unique',self::MODEL_INSERT),
		);

	public $_auto		=	array(
		array('password','pwdHash',self::MODEL_BOTH,'callback'),
		array('create_time','time',self::MODEL_INSERT,'function'),
		array('update_time','time',self::MODEL_UPDATE,'function'),
		);

	protected function pwdHash() 
	{
		if(isset($_POST['password']) and $_POST['password']!="noeditpass") {
			return md100($_POST['password']);
		}else{
			return false;
		}
	}

	protected function checkPwd($pwd){
		//if(strlen($pwd)>=8 && preg_match('/[a-zA-Z]/',$pwd)){
		if(strlen($pwd)>=8){
			return true;
		}else{
			return false;
		}
	}

    /*
    * 插入后置操作
    */
    protected function _after_insert($data, $options)
    {
        $this->make_cache();
    }

    /*
    * 修改后置操作
    */
    public function _after_update($data, $options)
    {
        $this->make_cache();
    }

    /*
    * 删除后置操作
    */
    public function _after_delete($data, $options)
    {
        $this->make_cache();
    }

	/*
	* 获取指定管理员当前的【管理员权限】
	*/
	public function getAdminAccess($adminId)
	{
		$Access				= M('AdminAccess');

		//记录管理员的【管理员权限】
		$accessIndexArray = array();
		$where2['admin_id']	= $adminId;
		$accessList			= $Access->field('node_id')->where($where2)->select();
		//转换成索引数组
		if(isset($accessList))
		foreach( $accessList as $_access)
		{
			//去除重复
			if( !in_array( $_access['node_id'] , $accessIndexArray ) )
			{
				$accessIndexArray[] = $_access['node_id'];
			}		
		}
		return $accessIndexArray;
	}


	/*
	* 获取指定管理员当前的所有角色权限(合并)
	*/
	public function getRoleAccess($adminId)
	{
		//第一步:获取该管理员的所有角色
		$RoleAdmin			= M("RoleAdmin");
		$Access				= M('RoleAccess');
		$where1['admin_id']	= $adminId;
		$roleList			= $RoleAdmin->where($where1)->select();

		//记录管理员的所有角色权限数组
		$accessIndexArray = array();
		
		if( !$roleList ) return $accessIndexArray;

		//转换为 xx,xx 格式
		$roleIds			= '';
		foreach( $roleList as $role )
		{
			if( $roleIds !='' ) $roleIds .= ',';
			$roleIds .= $role['role_id'];
		}

		//第二步:获取每个角色的权限,然后合并
		$where2['role_id']	= array('in',$roleIds);
		$accessList			= $Access->field('node_id')->where($where2)->select();
		
		//转换成索引数组
		if($accessList)
		foreach( $accessList as $_access)
		{
			//去除重复
			if( !in_array( $_access['node_id'] , $accessIndexArray ) )
			{
				$accessIndexArray[] = $_access['node_id'];
			}		
		}
		return $accessIndexArray;
	}

    /*
    * 生成后台用户缓存
    */
    public function make_cache()
    {
        $list   = $this->field('id,account,nickname')->select();

        $newList    = array();
        //转换数组主键
        foreach($list as $key=>$val)
        {
            $newList[$val['id']] = $val;
        }
        F('adminList',$newList);
    }
}
?>