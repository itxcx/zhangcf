<?php
defined('APP_NAME') || die('不要非法操作哦!');
// 本类由系统自动生成，仅供测试用途
class PrizeSplitAction extends CommonAction {
	public function index($prize)
	{
		$tle=$prize->parent();
		$data=M($prize->name.'记录')->where(array('id'=>I("get.id")))->find();
		if($data)
		{
			$pandata = unserialize($data['数据']);
			$recdata = unserialize($data['推荐数据']);
			foreach($pandata as &$pan)
			{
				//$pan[]
				$thisrec=array();
				
				foreach($recdata as $rec)
				{
					if($rec['编号'] == $pan['id'])
					{
						$thisrec[]=$rec;
					}
				}
				$pan['recdata']=$thisrec;
			}
		}
		$this->assign('name'       ,$prize->name);
		$this->assign('panid'      ,$data['盘号']);
		$this->assign('create_time',$data['时间']);
		$this->assign('data'       ,$pandata);
		$this->display();
	}
}
?>