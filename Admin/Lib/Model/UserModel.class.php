<?php
// 前台会员模型
class UserModel extends CommonModel {
	public $_validate	=	array(
		array('account','/^[a-z]\w{3,}$/i','帐号格式错误'),
		array('password','require','密码必须'),
		array('account','','帐号已经存在',self::EXISTS_VAILIDATE,'unique',self::MODEL_INSERT),
	);

	public $_auto		=	array(
		array('password','pwdHash',self::MODEL_BOTH,'callback'),
		array('reg_time','time',self::MODEL_INSERT,'function'),
		array('reg_ip','get_client_ip',self::MODEL_INSERT,'function'),
	);

	protected function pwdHash() 
	{
		if(isset($_POST['password'])) {
			return md100($_POST['password']);
		}else{
			return false;
		}
	}
}
?>