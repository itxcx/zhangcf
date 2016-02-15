<?php
// 角色模型
class RoleModel extends CommonModel 
{
	public $_validate = array(
		array('name','require','名称必须'),
	);

	public $_auto		=	array(
		array('create_time','time',self::MODEL_INSERT,'function'),
		array('update_time','time',self::MODEL_UPDATE,'function'),
	);



	/*
	* 更新缓存
	*/
	public function updateCache()
	{
		$list		= $this->field("id,name,type")->select();
		$objectList	= array();
		foreach($list as $role)
		{
			$objectList[$role['id']] = $role;
		}
		F('roleList',$objectList);
	}
}
?>