<?php
 class fun_scratch extends stru
{
     // 是否允许插入编号空缺
    public $insert = false;
     // 进入排号的条件
    public $where = "";
     // 降级条件
    public $downwhere = "";
     // 当这个人被删除.要对后续人前移
    public $deleteMove = false;
     // 最多排号
    public $maxNum = 0;
     public $timeMode = 'm';
     // 会员奖金结算的事件入口
    public function event_cal($tle, $caltime)
    {
         import('ORG.Util.Date');
         $caltime = $data['caltime'];
         $Date = new Date($caltime);
         // 判定每月只记录一次
        $cons = $this -> getcon('con', array('name' => '', 'where' => ''));
        
         $where = delsign($this -> where);
         // $where='';
        $users = M('会员', 'dms_') -> where($where) -> select();
         if($users)
        {
             foreach($users as $userinfo)
            {
                 // 找到符合条件的人插入记录
                if(!$this -> existscratch($userinfo['编号'], $caltime))
                    {
                     $data = array();
                     $data['编号'] = $userinfo['编号'];
                     $data['时间'] = $caltime;
                     $data["团队业绩"] = $userinfo['团队业绩本月'];
                     $data["个人业绩"] = $userinfo['个人业绩本月'];
                     $data["连贯"] = 0;
                     // 如果上个月此人存在记录。则连续字段跟上一期加一
                    $upmonthendtime = $Date -> firstDayOfMonth() -> date;
                     $upmonthdate = new Date($upmonthendtime-3600 * 24);
                     $upmonthbegintime = $upmonthdate -> firstDayOfMonth() -> date;
                     $upwhere = array();
                     $upwhere['编号'] = $userinfo['编号'];
                     $upwhere['时间'] = array(array('egt', $upmonthbegintime), array('lt', $upmonthendtime));
                     $rsup = M($this -> name, 'dms_') -> where($upwhere) -> find();
                     if($rsup){
                         $data["连贯"] = $rsup["连贯"] + 1;
                         }
                     $this -> adduser($data);
                     }
                 }
             }
        
        
         }
     // xxxx
    public function existscratch($userid, $caltime)
    {
         import('ORG.Util.Date');
         $Date = new Date($caltime);
         $where = array();
         $where['编号'] = $userid;
         // 当月开始时间
        $monthbegintime = $Date -> firstDayOfMonth() -> date;
         $monthendtime = ($Date -> lastDayOfMonth() -> date) + 3600 * 24;
         $where['时间'] = array(array('egt', $monthbegintime), array('lt', $monthendtime));
         $rs = M($this -> name, 'dms_') -> where($where) -> find();
         if($rs){
             return true;
             }else{
             return false;
             }
         }
     public function adduser($data)
    {
         M($this -> name, 'dms_') -> add($data);
         }
     function delsign($where){
         if(strpos($where, '[') !== false){
             $where = str_replace('[', '', $where);
             }
         if(strpos($where, ']') !== false){
             $where = str_replace(']', '', $where);
             }
         return $where;
         }
     public function event_modifyId($oldbh, $newbh)
    {
         M() -> execute("update dms_" . $this -> name . " set 编号='{$newbh}' where 编号='{$oldbh}'");
         }
     }
?>