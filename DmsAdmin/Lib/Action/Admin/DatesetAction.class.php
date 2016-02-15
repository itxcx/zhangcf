<?php
//设置结算发放周期所使用的保存函数
class DatesetAction extends CommonAction{
	public function index(fun_dateset $dateset){
        //dump($dateset->getDateBool(time()));
        $dateValue = $dateset ->getatt("dateRange");
        
        $dateValue1 = explode('|',$dateValue);
        $data = array();
        foreach($dateValue1 as $val){
            if($val != ""){
                $v = explode(';',$val);
                if($v[0]!="" || $v[1]!=""){
                    $vd = $v[0] .'至'. $v[1];                    
                    $vdw = $vd.'   '.$v[2];                    
                }else{
                    $vdw = $v[2];
                }
                $data[] = array($val,$vdw);
            }
        }
        $this->assign('data',$data);
        $this->assign('dateValue',$dateValue);
	    $this->assign("xpath",$dateset->objPath());
		$this->display();
	}
    public function saveSet(fun_dateset $dateset){
    	M()->startTrans();
        $dateset ->setatt("dateRange",trim(I("post.dateValue/s"),'|'));
        M()->commit();
        $this->success("保存成功！");
    }
}
?>