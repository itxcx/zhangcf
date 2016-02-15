<?php
/*
* 数据库备份与恢复
*/
class LoginVerify
{
	//构造函数
	public function __construct()
    {
    }
    /* -
     * +------------------------------------------------------------------------
     * * 密码输入超三次跳出验证码
     * +------------------------------------------------------------------------
     */
 
    public static function checkloginrs($ip){
    	$res=M('dms_loginrs',null)->where(array('ip'=>array('eq',$ip)))->find();
		if($res){
			if($res['errornum']>=C('VERIFY_NUM'))
			{
				if($res['endtime']>systemTime())
				{
					return true;
				}
			}
		}
		return false;
    }
    //更新用户登陆失败的次数
	public static function uploginrs($ip)
	{
		$log_model=M('dms_loginrs',null);
        $where['ip']=array('eq',$ip);
		$rs=$log_model->where($where)->find();
		$data['starttime']=systemTime();
		$data['endtime']=systemTime()+C('VERIFY_TIME');
		if($rs)
		{
			M()->startTrans();
			$rs1=$log_model->where($where)->save($data);
			$rs2=$log_model->where($where)->setInc('errornum');
			M()->commit();
		}else{
			$data['errornum']=1;
			$data['ip']=$ip;
			$rs3=$log_model->where($where)->add($data);
		}
	}
}
?>