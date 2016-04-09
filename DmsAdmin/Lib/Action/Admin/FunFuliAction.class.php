<?php
defined('APP_NAME') || die('不要非法操作哦!');
class FunFuliAction extends CommonAction
{
	public function index($fun_fuli)
	{
		$setButton=array(
			'发放奖励'=>array("class"=>"edit","href"=>__APP__."/Admin/FunFuli/fafang:__XPATH__/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要发放该奖励？"),
        );
        $list=new TableListAction($fun_fuli->name);
		$list->setButton = $setButton;   
        $list->order("获得时间 desc");
        $list->addshow("奖励名称",array("row"=>"[name]"));
        $list->addshow($this->userobj->byname."编号",array("row"=>"[编号]","searchMode"=>"text","searchPosition"=>"top","excelMode"=>"text"));
		$list->addshow("获得时间",array("row"=>"[获得时间]","format"=>"date")); 
		$list->addshow("发放时间",array("row"=>"[发放时间]","format"=>"date")); 
		$list->addshow("发放状态",array("row"=>array(array(&$this,"myfun"),"[state]"),"searchMode"=>"text","searchPosition"=>"top","excelMode"=>"text",'searchSelect'=>array('未发放'=>0,'已发放'=>1),'searchRow'=>'state'));  
        $this->assign('list',$list->getHtml());
        $this->display();
	}
	public function myfun($state)
	{
		if($state==0)
		 {
			return '未发放';
		 }else{
			 return '已发放';
		 }

	}

	public function fafang($fun_fuli)
	{
		 $id=I("request.id/d");
		 $where=array();
		 $where['id']=$id;
		 $update=array();
		 $update['state']=1;
		 $update['发放时间']=systemTime();
		 M()->startTrans();
		 $user=M($fun_fuli->name)->where($where)->find();
		 if($user){
			 if($user['state']=='1'){
				 $this->error('该'.$this->userobj->byname.'已领取该奖励');
			 }
		 }else{
			 $this->error('不存在该记录');
		 }
		 $rs=M($fun_fuli->name)->where($where)->save($update);
		 if($rs)
		 {
		 	 M()->commit();
			 $this->success('成功');
		 }else{
		 	 M()->rollback();
			 $this->error('失败');
		 }
	 }
 }
?>