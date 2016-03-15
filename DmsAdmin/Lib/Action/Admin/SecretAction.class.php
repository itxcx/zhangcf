<?php
defined('APP_NAME') || die('不要非法操作哦!');
class SecretAction extends CommonAction {
	//密保管理
    function index(){
        $setButton=array(
			'修改'=>array("class"=>"edit","href"=>"__URL__/editsecret/id/{tl_id}","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"240"),
            '删除'=>array("class"=>"delete","href"=>"__URL__/delsecret/id/{tl_id}","target"=>"ajaxTodo" ,"title"=>"确定要删除吗?"),
        );
        $setShow = array(
			'会员ID'=>array('row'=>'[uid]'),
            '密保问题'=>array('row'=>'[密保问题]'),
            '密保答案'=>array('row'=>'[密保答案]'),
        );
        $list=new TableListAction("密保");
        $list->setShow = $setShow;         // 定义列表显示
        $list->setButton = $setButton;     // 定义按钮显示
        $list->title="密保问题列表";       // 列表标题
        $list->order("uid desc"); 
        $this->assign('list',$list->getHtml()); 
        $this->display();
    }
	
	function editsecret(){
		$secretinfo = M('密保')->find(I("request.id/d"));
		$this->assign('secretinfo',$secretinfo);
		$this->display();
	}
    
	function saveEditsecret(){
		$secret = M('密保');
        if(trim(I("post.wt/s"))=="" and trim(I("post.da/s"))=="" and I("post.id/d")==""){
        	$this->error("无法修改");
        }
		$data['id'] = I("post.id/d");
        $data['密保问题'] = trim(I("post.wt/s"));
        $data['密保答案'] = trim(I("post.da/s"));
        M()->startTrans();
        if($secret ->save($data)){
        	M()->commit();
            $this->success("修改成功",'__URL__/index');
        }else{
        	M()->rollback();
            $this->error("修改失败");
        }
	}
	
    function delsecret(){
        $secret   = M('密保');
        M()->startTrans();
		$list	= $secret ->where(array("id"=>I("request.id/d")))->delete();
		if($list){
			$this->saveAdminLog('','',"删除密保问题");
			M()->commit();
			$this->success("删除成功！","__URL__/index");
		}else{
			M()->rollback();
			$this->error("删除失败！");
		}
    }
}
?>