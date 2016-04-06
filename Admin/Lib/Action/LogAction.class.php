<?php
// 系统日志模块
class LogAction extends CommonAction 
{
	/*public function _filter(&$map)
	{
		//读取所有的应用列表
		$Model			= M('Node');
		$appList		= $Model->field('id,name,title')->where("level=1 and status=1")->select();
		$this->assign('appList',$appList);
		//读取模块列表
		$moduleList		= array();
		$actionList		= array();
		if( isset($_REQUEST['application']) && $_REQUEST['application']!='' )
		{
			$app		= $_REQUEST['application'];
			$moduleList	= $Model->query("select * from node where pid=(select id from node where name='{$app}' and level=1 ) and level=2");
			//增加公共模块
			if( $app == 'Admin' ) $moduleList[] = array('name'=>'Public','title'=>'公共模块');
			//读取方法列表
			if( isset($_REQUEST['module']) && $_REQUEST['module']!='' )
			{
				$module		= $_REQUEST['module'];
				$actionList	= $Model->query("select * from node where pid=(select id from node where name='{$module}' and pid=(select id from node where name='{$app}' and level=1 ) and level=2) and level=3");
				//增加公共模块方法
				if( $app == 'Admin' )
				{
					$actionList[] = array('name'=>'logout','title'=>'登出');
					$actionList[] = array('name'=>'checkLogin','title'=>'登入');
				}
			}
		}
		$this->assign('moduleList',$moduleList);
		$this->assign('actionList',$actionList);
	}*/
	public function index(){ 
        $list=new TableListAction("log"); // 实例化Model 传表名称 
		$list->table('log a');
		$list->hint = "非电信以及联通带宽线路可能会出现异地IP,如发现IP异常,请先<a href='http://www.ip138.com/' target='_break'>确认当前登入IP</a>是否正常。";
		$list->join('left join admin b on a.admin_id=b.id');
		$list->field('a.*,b.account');
		$list->order("a.create_time desc,a.id desc");  //定义查询条件
        $list->addshow("id",array("row"=>"[id]"));      // 增加列表显示字段
        $list->addshow("应用",array("row"=>"[application]","searchMode"=>"text","excelMode"=>"text",'hide'=>true)); 
        $list->addshow("分组",array("row"=>"[group]","searchMode"=>"text","excelMode"=>"text",'hide'=>true));
        $list->addshow("模块",array("row"=>"[module]","searchMode"=>"text",'hide'=>true));
        $list->addshow("方法",array("row"=>"[action]","searchMode"=>"text",'hide'=>true)); 
		$list->addshow("操作人",array("row"=>'[account]',"searchMode"=>"text",'searchPosition'=>'top','searchRow'=>'account'));
		$list->addshow("操作内容",array("row"=>'[content]',"searchMode"=>"text",'searchPosition'=>'top'));
        $list->addshow("操作时间",array("row"=>"[create_time]",'searchMode'=>'date','searchPosition'=>'top',"format"=>"time",'searchRow'=>'a.create_time'));
		$list->addshow("IP",array("row"=>"[ip]","searchMode"=>"text","excelMode"=>"text"));
		$list->addshow("IP地址",array("row"=>"[address]","excelMode"=>"text",'searchMode'=>'text'));
		$list->addshow("备注",array("row"=>"[memo]",'searchMode'=>'text'));
        $list->addshow("操作",array("row"=>'<a target="dialog" mask="true" width="620" height="550" href="'.__APP__.'/Log/view/id/[id]">查看</a>&nbsp;','css'=>'width:100px',"excel"=>false)); 
		$list ->listLayoutH = 110;
		$this->assign('list',$list->getHtml());
        $this->display();
	}
	//删除日志
    public  function del_log(){
	    $id=I("get.id/d");
	    M()->startTrans();
	    $res = M('log','')->where(array('id'=>$id))->save(array('status_view'=>2));
	    if($res){
	    	M()->commit();
	      $this->success("操作成功");
	    }else{
	    	M()->rollback();
	      $this->error("操作失败");
	    }
	}
	//查看日志详情
	public function view()
	{
		$Model			= M('Log');
		$id				= I("get.id/d");
		$info			= $Model->table('log')->find($id);
		$oldData		= $info['old_data']!=''?(array)json_decode($info['old_data']):'';
		$newData		= $info['new_data']!=''?(array)json_decode($info['new_data']):'';
		$getData		= $info['get_data']!=''?(array)json_decode($info['get_data']):'';
		$postData		= $info['post_data']!=''?(array)json_decode($info['post_data']):'';
		$this->assign('oldData',$oldData);
		$this->assign('newData',$newData);
		$this->assign('postData',$postData);
		$this->assign('getData',$getData);
		$this->display();
	}
	//打印会员名称
	public function printUserName($userId,$userType)
	{
		if( !$userId ) return '无';
	}
	//打印管理员名称
	public function printAdminName($adminId)
	{
		if( !$adminId ) return '无';
		$Model					= M('管理员');
		$where['id']			= $adminId;
		$name					= $Model->where($where)->getField('姓名');
		return $name?$name:'未知';
	}
	//打印应用名称
	public function printApplicationName($app)
	{
		switch($app)
		{
			case 'admin':
				return '管理员后台';
				break;
			case 'user':
				return '会员后台';
				break;
			default:
				return '未知';
		}
	}
	//打印模块名称
	public function printModuleName($module)
	{
		$Model					= M('Node');
		$where['model']			= $module;
		$name					= $Model->where($where)->getField('title');
		return $name?$name:'未知';
	}
	//打印方法名称
	public function printActionName($module,$action)
	{
		$Model					= M('Node');
		$where['model']			= $module;
		$where['action']		= $action;
		$name					= $Model->where($where)->getField('title');
		return $name?$name:'未知';
	}
	//删除邮件
    public function del(){
		if(I("request.id/d")){
            $model=D("Log");
			$map['id']=I("request.id/d");
			M()->startTrans();
			$result1=$model->where($map)->delete();
		    if($result1){
				$this->saveAdminLog('','',"删除系统日志");
				M()->commit();
			    $this->success("删除成功",__URL__."/");
		    }else{
		    	M()->rollback();
			    $this->error("操作失败",__URL__."/");
		    }
		}
		$this->error("参数错误");
    }
}
?>