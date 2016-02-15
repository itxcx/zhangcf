<?php
defined('APP_NAME') || die('不要非法操作哦!');
class SecretAction extends CommonAction {
	//密保管理
    function index(){
        $setButton=array(
			'添加'=>array("class"=>"add","href"=>"__URL__/addsecret","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"240"),
			'修改'=>array("class"=>"edit","href"=>"__URL__/editsecret/id/{tl_id}","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"240"),
            '删除'=>array("class"=>"delete","href"=>"__URL__/delsecret/id/{tl_id}","target"=>"ajaxTodo" ,"title"=>"确定要删除吗?"),
        );
        $setShow = array(
			'编号'=>array('row'=>'[id]'),
            '密保问题'=>array('row'=>'[密保问题]'),
            '管理员'=>array('row'=>'[管理员]'),
            '添加时间'=>array('row'=>'[添加时间]',"format"=>"time"),
        );
        $list=new TableListAction("密保");
        $list->setShow = $setShow;         // 定义列表显示
        $list->setButton = $setButton;     // 定义按钮显示
        $list->title="密保问题列表";       // 列表标题
        $list->order("id desc"); 
        $this->assign('list',$list->getHtml()); 
        $this->display();
    }
	
    function addsecret(){
        $this->display();
    }
	
    function savesecret(){
        $secret = M('密保');
        if(trim(I("post.密保问题/s"))==""){
        	$this->error("请输入密保问题");
        }else{
        	$rs=$secret->where(array("密保问题"=>trim(I("post.密保问题/s"))))->find();
        	if($rs){
        		$this->error("密保问题已存在");
        	}
        }
        $data['密保问题'] = trim(I("post.密保问题/s"));
        $data['管理员']=$_SESSION['loginAdminAccount'];
        $data['添加时间']=systemTime();
        if($secret ->add($data)){
            $this->success("添加密保问题成功",'__URL__/index');
        }else{
            $this->error("添加失败");
        }  
    }
	
	function editsecret(){
		$secretinfo = M('密保')->find(I("request.id/d"));
		$this->assign('secretinfo',$secretinfo);
		$this->display();
	}
	function saveEditsecret(){
		$secret = M('密保');
		if(trim(I("post.密保问题/s"))==""){
        	$this->error("请输入密保问题");
        }else{
        	$rs=$secret->where(array("密保问题"=>trim(I("post.密保问题/s")),"id"=>I("post.id/d")))->find();
        	if($rs){
        		$this->error("密保问题已存在");
        	}
        }
		$data['id'] = I("post.id/d");
        $data['密保问题'] = trim(I("post.密保问题/s"));
        $data['管理员']=$_SESSION['loginAdminAccount'];
        $data['添加时间']=systemTime();
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