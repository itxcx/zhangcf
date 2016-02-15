<?php
// 管理员修改密码模块
class UpdateUserAction extends CommonAction 
{
	//管理员密码修改
	public function index(){
		$adminid = $_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ];
		$admin = M('admin');
		$str = array();
		$str1 = array();
		$str = $admin->where(array('id'=>$adminid))->find();
		//添加
		$yubiprefixs = M('yubicloud',null)->where(array('account_id'=>$adminid))->select();
		$this->assign('yubiprefixs',$yubiprefixs);
		$this->assign('vo',$str);
		$this->display();
	}
	public function update(){	
		$id = I("post.id/d");
		$account = I("post.account/s");
		$passwordyz = I("post.password1/s");	
		$pattern = "/^(?![^a-zA-Z]+$)(?!\D+$).{7,15}$/";
		if(!preg_match($pattern,$passwordyz)){
			$this->error("密码必须有字母和数字且字符长度在7-15之间");	
		}	
		$oldpassword = md100(I("post.oldpassword/s"));
		$password1 = md100(I("post.password1/s"));
		$password2 = md100(I("post.password2/s"));
		if(isset($password1) && $password1 != ""){
			if($password1 != $password2){
				$this->error("两次输入的密码不一样");	
			}
		}else{
			$this->error("密码不能为空");	
		}
		$str2 = array();
		M()->startTrans();
		$admin = M('admin');
		$str = $admin->where(array('id'=>$id))->find();
		if(isset($oldpassword) && $str['password'] == $oldpassword){
			$str2['password'] = $password1;
			$cont = $admin->where(array('id'=>$id))->data($str2)->save();
			if($cont){
				$_POST['password']='*******';
                $_POST['password1']='*******';
                $_POST['password2']='*******';
                $this->saveAdminLog('','','修改密码');
				$this->success("修改成功");
			}else{
				$this->error("修改失败");
			}
		}else{
	    	$this->error("旧密码错误");
		}
		M()->commit();
	}
}
?>