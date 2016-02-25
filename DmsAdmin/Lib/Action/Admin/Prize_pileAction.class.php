<?php
defined('APP_NAME') || die('不要非法操作哦!');
// 本类由系统自动生成，仅供测试用途
class Prize_pileAction extends CommonAction {
	public function view(prize_pile $prize_pile)
	{
		//奖金表显示
        $list=new TableListAction($prize_pile->name);
        $list->order  ="开始时间 desc";
		$list->setButton = array(
			'修改'=>array("class"=>"edit","href"=>"__URL__/edit:__XPATH__/id/{tl_id}","target"=>"dialog","mask"=>"true"),
		);
        $list->addshow($this->userobj->byname."编号",array("row"=>"[编号]","searchMode"=>"text","excelMode"=>"text","searchGet"=>"userid")); 
        $list->addshow('累计奖金',array("row"=>"[累计奖金]"));
		$list->addshow("产生次数",array("row"=>"[产生次数]")); 
		$list->addshow("投资金额",array("row"=>"[金额]")); 
	    $list->addshow("比例%",array("row"=>"[比例]"));
		$list->addshow("开始时间",array("row"=>"[开始时间]","format"=>"date","searchMode"=>"date")); 
        $list->addshow("截止时间",array("row"=>"[截止时间]","format"=>"date"));
        $this->assign('list',$list->getHtml());
        $this->display();
	}
    public function edit(prize_pile $prize_pile){
		$id=I("request.id/d");
		if(I("request.id/d")<=0)
		{
			$this->error('参数错误');
		}
		$info=M($prize_pile->name)->where(array('id'=>$id))->find();
        $name=$prize_pile->byname."明细修改"; 
		$this->assign('name',$name);
		$this->assign('info',$info);
		$this->display();
       
    }
    public function editSave(prize_pile $prize_pile){
		$datatrans=array(
			'id'=>'id',
			'userid'=>'编号',
			'starttime'=>'开始时间',
			'endtime'=>'截止时间',
			'prize'=>'金额',
			'allmoney'=>'累计奖金',
			'tax'=>'比例',
			'num'=>'产生次数',
			);
		$data=array();
		foreach(I("post.") as $key=>$val)
		{
			if(array_key_exists($key,$datatrans))
			{
              $data[$datatrans[$key]]=$val;
			}
		}
		$data['开始时间']=strtotime($data['开始时间']);
		$data['截止时间']=strtotime($data['截止时间']);
		M()->startTrans();
		$rs=M($prize_pile->name)->save($data);
		if($rs)
		{
			M()->commit();
			$this->success('修改成功',__URL__.'/view:__XPATH__');
		}else{
			M()->rollback();
            $this->Error('修改失败或内容未修改');
			}
    }
}
?>