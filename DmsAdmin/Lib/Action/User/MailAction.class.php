<?php
// 本类由系统自动生成，仅供测试用途
defined('APP_NAME') || die('不要非法操作哦');
class MailAction extends CommonAction {
	    //邮件列表
	    public function index(){
		   //$list=M()->query("select id,state,title,send_time from email where receiver='".$_SESSION[C('USER_AUTH_NUM')]."' and type=0 order by send_time desc");
		   //$this->assign('list',$list);
           
            $list = new TableListAction('邮件');
            $list ->where(array('收件人'=>USER_NAME,'收件人类型'=>$this->userobj->name))->order("发送时间 desc");
			$list->pageCon	= 'p1';
			$list->pagenum = 5;
			//dump($list);
            $data = $list->getData();
            $this->assign('data',$data);

			//发件箱
			$list1 = new TableListAction('邮件');
            $list1 ->where(array('发件人'=>USER_NAME,'发件人类型'=>$this->userobj->name))->order('发送时间 desc');
			$list1->pageCon	= 'p2';
			$list1->pagenum = 5;
            $data1 = $list1->getData();
			//dump($data1);
			//dump($list1);
            $this->assign('data1',$data1);
    		$this->display();
		}
       
		//邮件状态
		function dispFunction($state){
			if($state==0){
				return "未查看";
			}
			if($state==1){
				return "已查看";
			}
			if($state==2){
				return "已回复";
			}
		}
		//发件箱
		public function sendbox()
		{
            $list = new TableListAction('邮件');
            $list->where(array('发件人'=>USER_NAME,'发件人类型'=>$this->userobj->name))->order('发送时间 desc');
            $data = $list->getData();
			
            $this->assign('data',$data);
			$this->display();
		}
		//查看邮件详细
		public function view(){
			$model = M('邮件');
			$result = $model->where(array('收件人'=>USER_NAME,'收件人类型'=>$this->userobj->name,'id'=>I("get.id/d")))->find();
			if(!$result){
				$this->error(L('参数错误'));
			}
			if($result['状态'] == 0){
				M()->startTrans();
				$model->where(array('id'=>I("get.id/d")))->setField('状态',1);
				M()->commit();
			}
			$this->assign('list',$result);
			$this->display();
		}
		public function sendview(){
			$model = M('邮件');
			$result = $model->where(array('发件人'=>USER_NAME,'发件人类型'=>$this->userobj->name,'id'=>I("get.id/d")))->find();
			if(!$result){
				$this->error(L('参数错误'));
			}
			$map['id']=I("get.id/d");
			$this->assign('list',$result);
			$this->display();
		}

		public function resend()
		{
			if(I("request.id/d")<=0)
			{
				$this->error(L("请选择查看的邮件"),__URL__."/");
			}
			$model=M('Email');
			$map['id']=I("request.id/d");
			$rf=$model->where($map)->find();
			if($rf['state']==2)
		    {
				$this->error(L('该信件已经回复'));
			}
			$this->assign('rf',$rf);
			$this->display();
		}
		//回复邮件
		public function answer(){
			$where = array();
			$where['收件人'] = USER_NAME;
			$where['收件人类型'] = $this->userobj->name;
			$where['id'] = I("get.id/d");
			$result = M("邮件")->where($where)->find();
			if(!$result){
				$this->error(L('参数错误'));
			}
			$this->assign('list',$result);
			$this->display();
		}
		public function answerSave(){
			$model=M("邮件");
			$where = array();
			$where['收件人'] = USER_NAME;
			$where['收件人类型'] = $this->userobj->name;
			$where['id'] = I("post.id/d");
			$result = $model->where($where)->find();
			if(!$result){
				$this->error(L('参数错误'));
			}
			if(I("post.answerContent/s")==''){
				$this->error(L('内容不能为空'));
			}
			$data=array();
			$data['id']=I("post.id/d");
			$data['回复人']=USER_NAME;
			$data['回复内容']=I("post.answerContent/s");
			$data['回复时间']=systemTime();
			$data['状态']=2;
			M()->startTrans();
			$result=$model->save($data);
			if($result){
				//更新邮件状态
				M()->commit();
				$this->success(L('回复成功'),"__URL__/index");
			}else{
				M()->rollback();
				$this->error(L('回复失败'));
			}
		}
		//发送邮件
		public function send(){
			/*$maps['status']=array('gt',0);
			$sendto=M('role')->where($maps)->select();
			$this->assign('sendto',$sendto);*/
			$this->assign('mailset',adminshow('mailset'));
			$this->display();
				
		}
		//发送邮件
		public function send_email(){
			//防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
		   
			if(I("post.title/s")==""){
				$this->error(L('标题不能为空'));
			}
			if(I("post.center/s")==""){
				$this->error(L('内容不能为空'));
			}
             B('XSS');
            $content=get_magic_quotes_gpc() ? stripslashes(I("post.center/s")) : I("post.center/s");
           
			$model=M('邮件');
			$data=array();
			if(I("post.receiver/s")==''){
				$data['收件人类型']='管理员';
			}else{
				if(trim(I("post.receiver/s"))==USER_NAME){
					$this->error(L('不能发送给自己'));
				}
				if(M('会员')->where(array("编号"=>trim(I("post.receiver/s"))))->find()){
					$data['收件人类型']=$this->userobj->name;
					$data['收件人']=trim(I("post.receiver/s"));
				}else{
					$this->error(L('收件人编号不存在'));
				}
			}
			$data['标题']=I("post.title/s");
			$data['内容'] = $content;
			$data['发送时间']=systemTime();
			//$data['收件人类型']='管理员';
			$data['发件人'] = USER_NAME;
			$data['发件人类型']= $this->userobj->name;
			$data['回复人']= '';
			$data['回复内容']= '';
			$result=$model->add($data);
			if($result){
				$this->success(L('发送成功'),__URL__."/sendbox");
			}else{
				$this->error(L('发送失败'),__URL__."/send");
			}
		}
		//删除邮件
		public function del(){
			//遍历所选
			$model	  = M('邮件');
			$map['id']= I("get.id/d");
			$map['_string'] = "(收件人='".USER_NAME."' AND 收件人类型='".$this->userobj->name."') or (发件人='".USER_NAME."' AND 发件人类型='".$this->userobj->name."')";
			M()->startTrans();
			$result1  = $model->where($map)->delete();
			if($result1){
				M()->commit();
				$this->success(L('删除成功'));
			}else{
				M()->rollback();
				$this->error(L('删除失败'));
			}
		}
 

}
?>