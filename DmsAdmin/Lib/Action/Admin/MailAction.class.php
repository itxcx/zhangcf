<?php
// 本类由系统自动生成，仅供测试用途
defined('APP_NAME') || die('不要非法操作哦!');
class MailAction extends CommonAction {
	
	//邮件列表
	public function index(){
        $setButton=array(
			'删除'=>array("class"=>"delete","href"=>"__APP__/Admin/Mail/del/id/{tl_id}","target"=>"ajaxTodo" ,"title"=>"确定要删除吗?"),
        );  
        $list=new TableListAction("邮件"); // 实例化Model 传表名称 
        $list->setButton = $setButton;       // 定义按钮显示
		$list->order("发送时间 desc");  //定义查询条件
        $list->addshow("发件人",array("row"=>"[发件人]","searchMode"=>"text","excelMode"=>"text",'searchPosition'=>'top')); 
		$list->addshow("发件人类型",array("row"=>"[发件人类型]","searchMode"=>"text","excelMode"=>"text")); 
        $list->addshow("收件人",array("row"=>"[收件人]","searchMode"=>"text","excelMode"=>"text",'searchPosition'=>'top'));
        $list->addshow("收件人类型",array("row"=>"[收件人类型]","searchMode"=>"text","excelMode"=>"text"));
        $list->addshow("邮件标题",array("row"=>"[标题]","searchMode"=>"text"));
        $list->addshow("邮件内容",array("row"=>"[内容]","searchMode"=>"text",'searchRow'=>'内容',"hide"=>true));
        $list->addshow("发送时间",array("row"=>"[发送时间]","searchMode"=>"date","format"=>"time")); 
        //$list->addshow("收件时间",array("row"=>"[receive_time]","format"=>"time"));
        $list->addshow("状态",array("row"=>array(array(&$this,"getStatus"),"[状态]")));
        $list->addshow("操作",array("row"=>array(array(&$this,"getDofun"),"[状态]",'[id]','[发件人类型]'),"excel"=>false)); 
		$this->assign('list',$list->getHtml());    
        $this->display();
	}
	//邮件列表操作
	public function getDofun($status,$id,$senderType){
		$str = '<a target="dialog" mask="true" href="'.__APP__.'/Admin/Mail/view/id/'.$id.'" width="700" height="450">详情</a>&nbsp;&nbsp;<a target="ajaxTodo" title="确定要删除吗?" href="'.__APP__.'/Admin/Mail/del/id/'.$id.'">删除</a>';
		if($status !=2 && $senderType!='管理员'){
			$str .= '&nbsp;&nbsp;<a target="navTab" title="回复邮件" href="'.__APP__.'/Admin/Mail/answer/id/'.$id.'">回复</a>';
		}
		return $str;
	}
	//邮件状态
	function getStatus($status){
		if($status==0){
			return "未查看";
		}elseif($status==1){
			return "已查看";
		}elseif($status==2){
			return "已回复";
		}
	}
	//邮件详细信息
	public function view(){
		if(I("get.id/d")<=0){
			$this->error("请选择查看的邮件",__URL__."/");
		}
		M()->startTrans();
		$model=M('邮件');
		$result=$model->find(I("get.id/d"));
		$this->assign('list',$result);
		$data=array();
		if($result['状态'] < 1 && $result['发件人类型']!='管理员'){
			$data['状态']=1;
			$model->where(array('id'=>I("get.id/d")))->save($data);
		}
		M()->commit();
		$this->display();
	}
	//回复邮件
	public function answer(){
		$list = M('邮件')->find(I("get.id/d"));
		if(!$list || $list['状态'] == 2){
			$this->error('参数错误!');
		}
		$this->assign('list',$list);
		$this->display();
	}

	public function answerSave(){
		if(I("post.answerContent/s",'','')==""){
			$this->error("请输入要回复的内容");
		}
		$model=M("邮件");
		//邮件信息
		M()->startTrans();
		$map2['id']=I("request.id/d");
		$rs=$model->where($map2)->select();
		$this->assign('rs',$rs);
		//回复内容
		$data=array();
		$data['id'] = I("post.id/d");
		$data['状态'] = 2;
		$data['回复人'] = $_SESSION['loginAdminName'];
		$data['回复时间']=systemTime();
		$data['回复内容']=get_magic_quotes_gpc() ? stripslashes(I("post.answerContent/s",'','')) : I("post.answerContent/s",'','');
		$result=$model->save($data);
		$this->saveAdminLog('','',"回复邮件");
		if($result){
			M()->commit();
			$this->success("回复成功");
		}else{
			M()->rollback();
			$this->error("回复失败");
		}
	}
    //邮件发送
    public function send(){
		$sendto=array();
		//遍历USER节点
		$sendto[]=array("name"=>$this->userobj->byname."编号","path"=>'');
			//判断如果是豪华版可以发布团队公告
		if(C('VERSION_SWITCH') == '0'){
			//遍历所有下级节点
			foreach(X('net_rec,net_place') as $net)
			{
				$sendto[]=array("name"=>$this->userobj->byname.$net->byname."网络","path"=>$net->objPath());
			}
		}
		$this->assign('sendto',$sendto);
		$this->display();
    }
	//发送邮件
	public function send_email(){
		$where['编号']	= I("post.receiver/s");
		if(I("post.type/s")=="")
		{
			$list	= M('会员')->where($where)->getField('id');
			if($list){
				if(I("post.title/s")==""){
					$this->error("标题不能为空");
				}
				if(I("post.content/s",'','')==""){
					$this->error("内容不能为空");
				}
				$model=M('邮件');
				$data=array();
				$data['收件人'] = I("post.receiver/s");
				$data['收件人类型'] = $this->userobj->name;
				$data['标题'] = I("post.title/s");
				$data['内容'] = get_magic_quotes_gpc() ? stripslashes(I("post.content/s",'','')) : I("post.content/s",'','');
				$data['发送时间']=systemTime();
				$data['发件人']=$_SESSION['loginAdminName'];
				$data['回复人']='';
				$data['回复内容']='';
				$result=$model->add($data);
			}else{
				$this->error("该{$this->userobj->byname}不存在");
			}
		}
		else
		{
			$obj=X(">",I("post.type/s"));
			$user=M('会员')->where($where)->find();
			//查询会员是否存在
			if($user==""){
				$this->error("该".$this->userobj->byname."不存在");
			}else{
			//获得含自己在内的所有下级
				$users=$obj->getdown($user,0,'',true);
				if($users)
				foreach($users as $user)
				{
					$model=M('邮件');
					$data=array();
					$data['收件人']=$user['编号'];
					$data['收件人类型'] = $this->userobj->name;
					$data['标题'] = I("post.title/s");
					$data['内容'] = get_magic_quotes_gpc() ? stripslashes(I("post.content/s",'','')) : I("post.content/s",'','');
					$data['发送时间']=systemTime();
					$data['发件人']=$_SESSION['loginAdminName'];
					$result=$model->add($data);
				}
			}
		}
		if($result){
			$this->saveAdminLog('','',"发送邮件");
			$this->success("发送成功");
		}else{
			$this->error("发送失败");
		}

	}
	//删除邮件
    public function del(){
		$succNum = 0;
		$errNum = 0; 
		$errMsg = '';
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
			$model=M("邮件");
			$map['id']=$id;
			M()->startTrans();
			$result=$model->where($map)->delete();
			if($result){
				$this->saveAdminLog('','',"删除邮件");
				$succNum++;
				M()->commit();
			}else{
				$errNum++;
				M()->rollback();
			}
		}
		if($errNum !=0){
			$this->error("删除成功：".$succNum .'条记录；删除失败：'.$errNum .'条记录；');
		}else{
			$this->success("删除成功：".$succNum .'条记录；');
		}
    }
    //站外邮件列表显示
    public function zwemail(){
        $setButton=array(
			'删除'=>array("class"=>"delete","href"=>"__APP__/Admin/Mail/zwdel/id/{tl_id}","target"=>"ajaxTodo" ,"title"=>"确定要删除吗?"),
        );  
        $list=new TableListAction("站外邮件"); // 实例化Model 传表名称 
        $list->setButton = $setButton;       // 定义按钮显示
		$list->order("发送时间 desc");  //定义查询条件
        $list->addshow("发件人",array("row"=>"[发件人]",'css'=>'width:10%',"searchMode"=>"text","excelMode"=>"text",'searchPosition'=>'top')); 
		$list->addshow("标题",array("row"=>"[标题]",'css'=>'width:10%',"searchMode"=>"text","excelMode"=>"text")); 
        $list->addshow("收件人",array("row"=>"[收件人]",'css'=>'width:10%',"searchMode"=>"text","excelMode"=>"text",'searchPosition'=>'top'));
        $list->addshow("内容",array("row"=>"[内容]",'css'=>'width:10%',"searchMode"=>"text","excelMode"=>"text"));
        $list->addshow("发送时间",array("row"=>"[发送时间]",'css'=>'width:15%',"searchMode"=>"date","format"=>"time")); 
		$this->assign('list',$list->getHtml());    
        $this->display();
	}
	//删除站外邮件
    public function zwdel(){
		$succNum = 0;
		$errNum = 0; 
		$errMsg = '';
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
			$model=M("站外邮件");
			$map['id']=$id;
			M()->startTrans();
			$result=$model->where($map)->delete();
			if($result){
				$this->saveAdminLog('','',"删除邮件");
				$succNum++;
				M()->commit();
			}else{
				$errNum++;
				M()->rollback();
			}
		}
		if($errNum !=0){
			$this->error("删除成功：".$succNum .'条记录；删除失败：'.$errNum .'条记录；');
		}else{
			$this->success("删除成功：".$succNum .'条记录；');
		}
    }
}
?>