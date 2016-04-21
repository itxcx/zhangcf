<?php
	/*货币延期到账模块，有一些货币在发放*/
	class fun_bankdelay extends stru
	{
		//显示条件
		public function event_valadd($user,$val,$option)
		{
            //是否能够找到配置项进行处理
            $find = false;
            $cons = $this->getcon("con",array('to'=>'','bankmode'=>'','bankmemo'=>'','val'=>'100%','delay'=>''));
            foreach($cons as $con)
            {
                if(!$con['delay'])
                {
                    throw_exception("未设定冻结日期");
                }
                if(!$con['to'])
                {
                    throw_exception("未设定目标");
                }
                //如果类型一致
                if($con['bankmode'] == $option['bankmode'])
                {
                    $find = true;
                    $time =  systemTime();
                    $time += ($con['delay']*3600*24);
                    $data=array(
                        '编号'    =>$user['编号'],
                        '创建时间'=>systemTime(),
                        '解冻时间'=>$time,
                        '金额'    =>getnum($val,$con['val']),
                        '发放'    =>0,
                        'to'      =>$con['to'],
                        'bankmode'=>$option['bankmode'],
                        'bankmemo'=>$option['bankmemo'],
                    );
                    M($this->name)->add($data);
                }
            }
            if(!$find)
            {
                throw_exception("对bankdelay操作时没有对应的bankmode");
            }
		}
        public function event_tick()
        {
            $datas=M($this->name)->where(array('解冻时间'=>array('lt',systemTime()),'发放'=>0))->select();
            foreach($datas as $data)
            {
                bankset($data['to'],$data['编号'],$data['金额'],$data['bankmode'],$data['bankmemo']);
                M($this->name)->where(array('id'=>$data['id']))->save(array('发放'=>1));
            }
        }
	}
?>