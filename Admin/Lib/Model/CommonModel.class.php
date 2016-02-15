<?php
/*
* 公共模型
*/
class CommonModel extends Model 
{
    // 记录模型日志
    public function recordSystemLog()
    {
        $AdminLog = D('AdminLog');
    }

	// 获取当前用户的ID
    public function getMemberId() {
        return isset($_SESSION[C('USER_AUTH_KEY')])?$_SESSION[C('USER_AUTH_KEY')]:0;
    }


    /**
    +----------------------------------------------------------
    * 根据条件 审核不通过 表数据
    +----------------------------------------------------------
    */
    public function forbid($options,$field='status')
    {
        if(FALSE === $this->where($options)->setField($field,2)){
            $this->error =  '审核不通过失败';
            return false;
        }else {
            return True;
        }
    }

	/**
    +----------------------------------------------------------
    * 根据条件 审核通过 表数据
    +----------------------------------------------------------
    */
    public function checkPass($options,$field='status',$val=1)
    {
        if(FALSE === $this->where($options)->setField($field,$val)){
            $this->error =  '审核通过失败';
            return false;
        }else {
            return True;
        }
    }


    /**
    +----------------------------------------------------------
    * 根据条件恢复表数据
    +----------------------------------------------------------
    */
    public function resume($options,$field='status')
	{
        if(FALSE === $this->where($options)->setField($field,1)){
            $this->error =  '恢复失败';
            return false;
        }else {
            return True;
        }
    }

    /**
    +----------------------------------------------------------
    * 根据条件 还原待审 表数据
    +----------------------------------------------------------
    */
    public function recycle($options,$field='status')
	{
        if(FALSE === $this->where($options)->setField($field,0)){
            $this->error =  '还原失败';
            return false;
        }else {
            return True;
        }
    }

}
?>