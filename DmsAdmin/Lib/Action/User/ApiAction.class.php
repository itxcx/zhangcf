<?php
defined('APP_NAME') || die(L('not_allow'));
class ApiAction extends CommonAction {

	//查询会员是否存在  参数 userid  如果存在返回OK 如果不存在返回NO
	public function userExit()
	{		
		$res = M('会员')->where(array('编号'=>$_GET['userid']))->count();
		if($res){
		  echo "OK";
		}else{
		   echo "NO";
		}
	}
    //判断该会员密码是否正确  参数 pass 为用户密码 userid 为用户编号 如果一级密码正确 则返回OK 如果不存在则返回NO
   public function passRight(){
     $res = M('会员')->where(array('编号'=>$_GET['userid'],'pass1'=>$_GET['pass']))->count();
     if($res){
       echo "OK"
     }else{
       echo "NO";
     }
   }
   //获取会员的信息  参数 用户编号 userid  用户密码 pass 如果会员存在则返回会员的信息 会员的编号,会员的密码,会员的省份,市,区,街道,详细地址 如果会员不存在 则返回NO
   public function userInfo(){
     $res = M('会员')->where(array('编号'=>$_GET['userid'],'pass1'=>$_GET['pass']))->find();
     if($res){
       $infoarray = array(
          'userid'=>$res['编号'],
          'pass'=>$res['pass1'],
          'province'=>$res['省份'],
          'city'=>$res['城市'],
          'area'=>$res['地区'],
          'country'=>$res['街道'],
          'address'=>$res['地址'],
       );
       $infostr = implode(',',$infoarray);
       echo $infostr;
     }else{
       echo "NO";
     }
   }
  //会员注册信息
  public function usereg(){
    //获取会员的一些信息 接收会员的信息 一个字符串 是 会员账号,密码,手机号码,推荐人,所在省份,市,区,街道,详细地址,联系人 参数名 userinfo
    $userinfo = $_GET['userinfo'];
    //将字符串转化成数组
    $data_arr = explode(',',$userinfo);
    
    
  }
}
?>