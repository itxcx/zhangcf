<?php
/**
+----------------------------------------------------------
* 会员模块
+----------------------------------------------------------
*	内部调用查询 无权限限制的方法请使用'_'开头
*/
defined('APP_NAME') || die('不要非法操作哦!');
class UserAction extends CommonAction 
{
	//会员查询列表页
    public function index()
	{
		
		//加载tablelist 实例化联合查询表
        $list=new TableListAction('会员');
        //货币分离
        $table='dms_会员 user inner join dms_货币 b on user.id=b.userid';
        //股票表
        foreach(X('fun_stock') as $stkey=>$fun_stock)
        {
        	$table.=" inner join dms_".$fun_stock->name." fs".$stkey." on user.id=fs".$stkey.".userid";
        }
        $list->table($table);
        //判断查询的会员状态 在系统维护中的系统设置中设置
        if(adminshow('user_noacc'))
        	$list->where("user.状态='有效'");
        //排序字段
        $list->order("user.id desc");
        $REQUEST_URI=$_SERVER['REQUEST_URI'];
        //显示操作按钮
        $button=array(
			"查看"=>array("class"=>"edit","href"=>__APP__."/Admin/User/view/id/{tl_id}".strstr($REQUEST_URI,'&'),"target"=>"navTab","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_magnify.png'),
	        "修改"=>array("class"=>"edit","href"=>__APP__."/Admin/User/edit/id/{tl_id}".strstr($REQUEST_URI,'&'),"target"=>"navTab","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_edit.png'),
			"删除"=>array("class"=>"delete","href"=>__APP__."/Admin/User/pre_delete/id/{tl_id}".strstr($REQUEST_URI,'&'),"target"=>"dialog","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_delete.png')
	    );
	    $button['登陆锁定'] = array("class"=>"delete","href"=>__APP__."/Admin/User/suoding/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要锁定此会员吗？",'icon'=>'/Public/Images/ExtJSicons/application/application_link.png');
	    $button['解锁']     = array("class"=>"edit","href"=>__APP__."/Admin/User/jiesuo/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要解除此会员的登陆锁定状态吗？");
        if(adminshow('piliangshengji')){
        	$button['批量升级'] = array("class"=>"delete","href"=>__APP__."/Admin/User/bulkUpLevel","target"=>"navTab","mask"=>"true","title"=>"",'icon'=>'/Public/Images/ExtJSicons/award-start/award_star_add.png');
        }
        $list->setButton = $button;
        /*显示字段的内容*/
        $list->addshow("ID",array("row"=>'[id]',"hide"=>true));
        $list->addshow($this->userobj->byname."编号",array("row"=>array(array(&$this,"_dispUserId"),'[编号]','[状态]','[空点]','[登陆锁定]'),"css"=>'width:100px',"searchMode"=>"text","searchRow"=>'user.编号',"searchGet"=>"userid","excelMode"=>"text","searchPosition"=>"top"));
        $list->addshow("姓名",array("row"=>array(array(&$this,"_printName"),"[姓名]"),"css"=>'width:100px',"searchRow"=>'user.姓名',"searchMode"=>"text","searchPosition"=>"top"));
        $list->addshow("状态",array("row"=>array(array(&$this,"_zhuangtai"),"[状态]"),"css"=>'width:50px'));
        $list->addshow("昵称",array("row"=>"[昵称]","searchMode"=>"text","searchRow"=>'user.昵称'));
        $list->addshow("注册日期",array("row"=>"[注册日期]","format"=>"time","css"=>"width:120px","url"=>__APP__."/Admin/User/userForm/id/[id]/","target"=>"dialog","urlAttr"=>'mask="true" width="960" height="480" title="会员明细"',"searchMode"=>"date","order"=>"[user.注册日期]",'searchRow'=>'user.注册日期'));
        $list->addshow("审核日期",array("row"=>"[审核日期]","format"=>"time","css"=>"width:120px","url"=>__APP__."/Admin/User/userForm/id/[id]/","target"=>"dialog","urlAttr"=>'mask="true" width="960" height="480" title="会员明细"',"searchMode"=>"date",'order'=>'[user.审核日期]','searchRow'=>'user.审核日期'));
		$list->addshow("锁定",array("row"=>"[登陆锁定]","searchMode"=>"text",'searchRow'=>'user.登陆锁定',"searchSelect"=>array("是"=>"1","否"=>"0"),"hide"=>true));
		$list->addshow("空点",array("row"=>array(array(&$this,"_printNull"),"[空点]"),"searchMode"=>"text","searchSelect"=>array("是"=>"1","否"=>"0"),'searchRow'=>'user.空点',"hide"=>true));
		$list->addshow("省份",array("row"=>"[省份]","searchMode"=>"text",'searchRow'=>'user.省份',"hide"=>true));
		$list->addshow("城市",array("row"=>"[城市]","searchMode"=>"text",'searchRow'=>'user.城市',"hide"=>true));
		$list->addshow("地区",array("row"=>"[地区]","searchMode"=>"text",'searchRow'=>'user.地区',"hide"=>true));
		$list->addshow("街道",array("row"=>"[街道]","searchMode"=>"text",'searchRow'=>'user.街道',"hide"=>true));
		$list->addshow("地址",array("row"=>"[地址]","searchMode"=>"text",'searchRow'=>'user.地址',"hide"=>true));
		$list->addshow("移动电话",array("row"=>"[移动电话]","searchMode"=>"text",'searchRow'=>'user.移动电话',"hide"=>true));
        //级别信息
        foreach(X('levels') as $levels)
        {
        	$_temp=array();
			foreach($levels->getcon("con",array("name"=>"","lv"=>"")) as $lvconf)
			{
				$_temp[ $lvconf['name'] ] = $lvconf['lv'];
 			}
        	$list->addshow($levels->byname,array("row"=>array(array(&$this,"_printUserLevel"),"[".$levels->name."]",$levels->name),"searchMode"=>"num","searchSelect"=>$_temp,"searchRow"=>"user.".$levels->name."","order"=>'user.'.$levels->name));
        }
        //是否显示服务中心开关字段,如果使用其他条件.则此开关字段没显示必要
        if(X('user')->shopWhere=='[服务中心]=1')
        	$list->addshow("店",array("row"=>"[服务中心]","searchMode"=>"text",'searchRow'=>'user.服务中心','format'=>'bool','order'=>'user.服务中心',"searchSelect"=>array("是"=>"1","否"=>"0"),));
        if(X('user')->shopWhere!='')
        	$list->addshow("所属店铺",array("row"=>"[服务中心编号]","searchMode"=>"text",'searchRow'=>'user.服务中心编号'));
		foreach(X('fun_bank') as $banks){
			//货币分离
			$list->addshow($banks->byname,array("row"=>array(array(&$this,"_base64User"),'[编号]',$banks->objPath(),"[".$banks->name."]"),"css"=>"width:90px","searchRow"=>"b.".$banks->name,"searchMode"=>"num","order"=>'b.'.$banks->name,"sum"=>'b.'.$banks->name));
		}
		foreach(X('fun_stock') as $fskey=>$fun_stock)
        {
        	$list->addshow($fun_stock->byname,array("row"=>array(array($this,"_shownum"),"[fs".$fskey."num]","[编号]",$fun_stock->objPath()),"searchRow"=>"fs".$fskey.".数量","searchMode"=>"num","order"=>"fs".$fskey.".数量","sum"=>"fs".$fskey.".数量"));
        }
        //显示网络上级姓名的额外字段
        $netnamerow='';
        //网络信息
        foreach(X('net_rec,net_place') as $net)
        {
			$searchSql = "FIND_IN_SET((SELECT id FROM dms_会员 where `编号`='[*]'),user.`{$net->name}_网体数据`)";
        	if(get_class($net)=='net_place' && $net->pvFun)
        	{
        		//$bras = explode(',',$net->Branch);
				$bras = $net->getBranch();
        		foreach($bras as &$bar)
        		{
        			$bar="[".$net->name."_".$bar."区@]";
        		}
				$searchSql = "(";
				foreach($net->getcon("region",array("name"=>"")) as $nameconf){
					$regionName = $nameconf['name'];
					$searchSql .= " FIND_IN_SET((select concat((SELECT id FROM dms_会员 where 编号='[*]'),'-{$regionName}')),user.{$net->name}_网体数据) or";
				}
				$searchSql = trim($searchSql,'or');
				$searchSql .= ")";
        		$bras = implode("/",$bras);
	        	$list->addshow($net->byname."新增业绩",array("row"=>str_replace("@","本日业绩",$bras),"hide"=>true));
	        	$list->addshow($net->byname."结转业绩",array("row"=>str_replace("@","结转业绩",$bras),"hide"=>true));
	        	$list->addshow($net->byname."累计业绩",array("row"=>str_replace("@","累计业绩",$bras),"hide"=>true));
        	}
        	$list->addshow($net->byname."上级",array("row"=>array(array(&$this,"_dispNetUp"),'[编号]',"[".$net->name."_上级编号]",$net->name,$net->objPath()),"searchMode"=>"text","searchPosition"=>"top","excelMode"=>"text",'searchRow'=>"[user.".$net->name."_上级编号]"));
	       	$list->join('dms_会员 as '.$net->name.' on user.'.$net->name.'_上级编号='.$net->name.'.编号');
        	$netnamerow.=",{$net->name}.姓名 as netname".$net->getPos();
        	$list->addshow($net->byname."人姓名",array("row"=>"[netname".$net->getPos()."]","searchMode"=>"text","excelMode"=>"text",'searchRow'=>"{$net->name}.姓名"));
        	$list->addshow($net->byname."层数",array("row"=>"[".$net->name."_层数]","searchMode"=>"num",'searchRow'=>"user.".$net->name."_层数","order"=>"user.".$net->name."_层数"));
        }
		foreach(X('fun_outday') as $fun_outday)
        {
        	$list->addshow($fun_outday->byname,array("row"=>"[$fun_outday->name]天",'format'=>'date',"searchMode"=>"num"));
        	$list->addshow('剩余'.$fun_outday->byname,array("row"=>"[".$fun_outday->name."剩余]天","searchMode"=>"num"));
        }
        foreach(X('fun_treenum') as $fun_treenum)
        {
        	$list->addshow($fun_treenum->byname.'累计',array("row"=>"[".$fun_treenum->name."累计]","searchMode"=>"num","order"=>"user.".$fun_treenum->name."累计",'searchRow'=>"user.".$fun_treenum->name."累计"));
        }
        foreach(X("tle") as $tle)
        {
			foreach(X('prize_*',$tle) as $prize)
			{
				if($prize->prizeMode >= 0)
				{
					$list->addshow($prize->byname."累计",array("row"=>"[".$prize->name."累计]","searchMode"=>"num",'searchRow'=>'user.'.$prize->name."累计","hide"=>true));// 增加列表显示字段
				}
			}
		}
		$firstTle=X('tle');
        $list->addshow("最后登入IP",array("row"=>"[最后登入IP]","searchMode"=>"text","searchRow"=>'user.最后登入IP',"hide"=>true));
		$list->addshow("注册人",array("row"=>"[注册人编号]","searchMode"=>"text","searchRow"=>'user.注册人编号'));
		//定义查询的数据表中的字段

		$filestr='user.*'.$netnamerow;
		foreach(X('fun_bank') as $fun_bank){
			$filestr.=",b.".$fun_bank->name;
		}
		foreach(X('fun_stock') as $fskey=>$fun_stock)
        {
        	$filestr.=",fs".$fskey.".数量 as fs".$fskey."num";
        }
		$list->field($filestr);//货币分离
		//显示到页面中
        $this->assign('list',$list->getHtml());
        $this->display();
    }
    //会员的状态
    public function _zhuangtai($status){
       	if($status=='无效'){
       		return "<span style='background-color:#FF0000;font-weight:bold;color:#316EDA;display:block;padding:5px 0px'>{$status}</span>";
       	}else{
        	return "<span style='background-color:#316EDA;font-weight:bold;color:#FFFFFF;display:block;padding:5px 0px'>{$status}</span>";
       	}
    }
    function _shownum($num,$userid,$xpath){
		return "<a href='__APP__/Fun_stock/index:{$xpath}/userid/{$userid}' target='navTab' mask='true'>".$num."</a>";
	}
    //由于最后访问时间消耗大量资源,此功能需要放到内存缓存中来实现
    //public function userOnline()
    /*
	{
        $list=new TableListAction('会员');
        $list->table('dms_会员 user');
		$list->where("user.sessionid!='' and 最后访问时间>'".(systemTime()-15*60*60)."'");
        $list->order("user.最后访问时间 desc");
		$button=array(
			"强制下线"=>array("class"=>"delete","href"=>__APP__."/Admin/User/Onlinebreak/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要将此会员踢下线吗？",'icon'=>'/Public/Images/ExtJSicons/application/application_form_delete.png'),
		);
        $list->setButton = $button;
        $list->addshow("ID",array("row"=>'[id]'));
        $list->addshow($this->userobj->byname."编号",array("row"=>'[编号]',"searchRow"=>"[编号]","searchMode"=>"text","searchRow"=>'user.编号',"searchGet"=>"userid","excelMode"=>"text","searchPosition"=>"top"));
        $list->addshow("姓名",array("row"=>array(array(&$this,"_printName"),"[姓名]"),"searchRow"=>'user.姓名',"searchMode"=>"text"));
        $list->addshow("状态",array("row"=>array(array(&$this,"_zhuangtai"),"[状态]"),"css"=>'width:50px'));
        $list->addshow("昵称",array("row"=>"[别名]","searchMode"=>"text","searchRow"=>'user.别名'));
        $list->addshow("注册日期",array("row"=>"[注册日期]","format"=>"date","css"=>"width:70px","url"=>__APP__."/Admin/User/userForm/id/[id]/","target"=>"dialog","urlAttr"=>'mask="true" width="960" height="480" title="会员明细"',"searchMode"=>"date","order"=>"[user.注册日期]",'searchRow'=>'user.注册日期'));
        $list->addshow("审核日期",array("row"=>"[审核日期]","format"=>"date","css"=>"width:70px","url"=>__APP__."/Admin/User/userForm/id/[id]/","target"=>"dialog","urlAttr"=>'mask="true" width="960" height="480" title="会员明细"',"searchMode"=>"date",'order'=>'[user.审核日期]','searchRow'=>'user.审核日期'));
 		$list->addshow("锁定",array("row"=>"[登陆锁定]","searchMode"=>"text",'searchRow'=>'user.登陆锁定',"searchSelect"=>array("是"=>"1","否"=>"0"),"hide"=>true));
        $list->addshow("最后登入IP",array("row"=>"[最后登入IP]","searchMode"=>"text"));
		$list->addshow("最后访问时间",array("row"=>"[最后访问时间]","format"=>"time"));
		$list->field('user.*');
        $this->assign('list',$list->getHtml());
        $this->display();
    }
    */
    //强制下线处理
    public function onlineBreak(){
    	$id=I("get.id/d");
		M('会员')->where(array("id"=>$id))->save(array("sessionid"=>'',"最后访问时间"=>0));
		$this->success("操作成功");
    }
    //未激活会员
	public function noConfirm()
	{
        $list=new TableListAction('会员');
        $list->table('dms_会员 user');
		$list->where("user.状态='无效'");
        $list->order("user.id desc");
        $button=array(
			"查看"=>array("class"=>"edit","href"=>__APP__."/Admin/User/view/id/{tl_id}","target"=>"navTab","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_magnify.png'),
            "修改"=>array("class"=>"edit","href"=>__APP__."/Admin/User/edit/id/{tl_id}","target"=>"navTab","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_edit.png'),	
           	"删除"=>array("class"=>"delete","href"=>__APP__."/Admin/User/delete/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要删除该数据吗？",'icon'=>'/Public/Images/ExtJSicons/application/application_form_delete.png'),
			'全部删除'=>array("class"=>"delete","href"=>__APP__."/Admin/User/deleteAllInvalidUser","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要删除所有无效会员吗？",'icon'=>'/Public/Images/ExtJSicons/status_busy.png')
        );
		if($this->userobj->unaccLog){
			$button["登陆锁定"]=array("class"=>"delete","href"=>__APP__."/Admin/User/suoding/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要锁定此会员吗？",'icon'=>'/Public/Images/ExtJSicons/application/application_link.png');
			$button["解锁"]=array("class"=>"delete","href"=>__APP__."/Admin/User/jiesuo/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要解除此会员的登陆锁定状态吗？",'icon'=>'/Public/Images/ExtJSicons/application/application_error.png');
		}
        //会员只有注册订单，没有产品，需要审核，则在会员列表中增加审核功能
        if($this->userobj->regOnly() && !$this->userobj->haveProduct() &&  $this->userobj->haveConfirm())
        {
        	$button['审核'] = array("class"=>"delete","href"=>__APP__."/Admin/User/accok/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要审核吗？",'icon'=>'/Public/Images/ExtJSicons/table/table_go.png');
        }
        $list->setButton = $button;
        $list->addshow("ID",array("row"=>'[id]'));
        $list->addshow($this->userobj->byname."编号",array("row"=>array(array(&$this,"_dispUserId"),'[编号]','[状态]','[空点]','[登陆锁定]'),"searchRow"=>"[编号]","searchMode"=>"text","searchRow"=>'user.编号',"searchGet"=>"userid","excelMode"=>"text","order"=>"user.编号","searchPosition"=>"top"));
        $list->addshow("姓名",array("row"=>array(array(&$this,"_printName"),"[姓名]"),"searchRow"=>'user.姓名',"searchMode"=>"text","searchPosition"=>"top"));
        $list->addshow("昵称",array("row"=>"[昵称]","searchMode"=>"text","searchRow"=>'user.昵称'));
		if($this->userobj->unaccLog){
			$list->addshow("锁定",array("row"=>"[登陆锁定]","searchMode"=>"text","searchPosition"=>"top",'searchRow'=>'user.登陆锁定',"searchSelect"=>array("是"=>"1","否"=>"0"),"hide"=>true));
		}
        //级别信息
        foreach(X('levels') as $levels)
        {
        	$_temp=array();
        	//循环所有的con节点
			foreach($levels->getcon("con",array("name"=>"","lv"=>"")) as $lvconf)
			{
				$_temp[ $lvconf['name'] ] = $lvconf['lv'];
 			}
        	$list->addshow($levels->byname,array("row"=>array(array(&$this,"_printUserLevel"),"[".$levels->name."]",$levels->name),"searchMode"=>"num","searchSelect"=>$_temp,"searchRow"=>"user.".$levels->name."","order"=>'user.'.$levels->name));
        }
		foreach(X('fun_bank') as $banks){
			$list->addshow($banks->byname,array("row"=>array(array(&$this,"_base64User"),'[编号]',$banks->objPath(),"[".$banks->name."]"),"searchRow"=>"user.".$banks->name,"searchMode"=>"num","order"=>'user.'.$banks->name));
		}
        //显示网络上级姓名的额外字段
        $netnamerow='';
        //网络信息
        foreach(X('net_rec,net_place') as $net)
        {
			$searchSql = "FIND_IN_SET((SELECT id FROM dms_会员 where `编号`='[*]'),user.`{$net->name}_网体数据`)";
        	$list->addshow($net->byname."上级",array("row"=>array(array(&$this,"_dispNetUp"),'[编号]',"[".$net->name."_上级编号]",$net->name,$net->objPath()),"searchMode"=>"text","searchPosition"=>"top","excelMode"=>"text",'searchRow'=>"[user.".$net->name."_上级编号]"));
        }
		$list->addshow("注册人",array("row"=>"[注册人编号]","searchMode"=>"text","searchPosition"=>"top","searchRow"=>'user.注册人编号'));
		$list->addshow("注册日期",array("row"=>"[注册日期]","format"=>"time","searchMode"=>"date","searchRow"=>'user.注册日期',"order"=>"[user.注册日期]"));
		$list->field('user.*'.$netnamerow);
        $this->assign('list',$list->getHtml());
        $this->display();
    }
    //内部使用函数 生成链接到货币明细的链接
	public function _base64User($userid,$xpath,$name){
		return '<a href="'.__APP__.'/FunBank/index:'.$xpath.'/userid/'.$userid.'" target="navTab" mask="true">'.$name.'</a>';
	}
	//内部使用函数 生成链接到网络的的链接
    public function _dispNetUp($userid,$upid,$netname,$xpath)
    {
    	$userlink = '<a href="'.__APP__.'/Admin/Net/dispUp:'.$xpath.'/id/'.$userid.'" target="navTab" mask="true" title="'.$netname.'明细">'.$upid.'</a>';
    	return $userlink;
    }
    //内部使用函数 生成链接登陆前台的链接并显示编号的样式
	public function _dispUserId($userid,$state,$nullstate,$loginlock)
	{
		//有改动 之前不知道问什么屏蔽了  把编号的无效与有效样式区分开了 2015-02-10 16:30
		$idstr = $userid;
		if($state=='无效')
		{
			$idstr="<font color='#939393'>".$idstr.'</font>';
		}
		$userlink = '<a href="'.__APP__.'/Admin/User/loginToUser/id/'.$userid.'" target="_blank" rSelect="true">'.$idstr.'</a>';
		//显示空点的状态
		if($nullstate=='1')
			$userlink .= '(空)';
		//显示当前会员是否被锁定登陆
		if($loginlock)
			$userlink.="<br><font color='red'>锁定</font>";
		return $userlink;
	}
	//空点的显示
	public function _printNull($null){
		if($null==0){
			return '否';
		}else{
			return '是';
		}
	}

	//代理列表  地区代理的列表显示  有设置<levels area="true"></levels>的情况下使用  默认必须只有一个
	public function areaIndex()
	{
		$user=X('user');
        $list=new TableListAction('会员');
        $list->table('dms_会员 user inner join dms_货币 b on user.id=b.userid');
        //找到设置地区级别的levels节点
        $levels = X('levels');
		foreach($levels as $level){
			if($level->area) {
				$lvname=$level->name;
				break;
			}
		}
		//级别名称数组
		$_temp=array();$area=array();
		foreach($level->getcon("con",array("name"=>"","lv"=>"","area"=>"")) as $lvconf)
		{
			if($lvconf['lv']==1) continue;
			$_temp[$lvconf['name']] = $lvconf['lv'];
			if($lvconf['area']!='')$area[$lvconf['area']]=$lvconf['name'];
		}
		//查询条件 第一个con级别lv='1' 表示的是 无级别 
		$where=array();
		$where['状态']='有效';
		$where[$lvname]=array('gt',1);
		
        $list->where($where);
        $list->order("审核日期 desc");
        
		$list->addshow("ID",array("row"=>"[id]","searchMode"=>"text","order"=>"id"));
        $list->addshow($this->userobj->byname."编号",array("row"=>array(array(&$this,"_dispUserId"),'[编号]','[状态]','[空点]','[登陆锁定]'),"searchRow"=>"[编号]","searchMode"=>"text","searchRow"=>'user.编号',"searchGet"=>"userid","excelMode"=>"text","searchPosition"=>"top"));
		
        $list->addshow("姓名",array("row"=>array(array(&$this,"_printName"),"[姓名]"),"searchRow"=>'user.姓名',"searchMode"=>"text"));
        $list->addshow("审核日期",array("row"=>"[审核日期]","format"=>"time","css"=>"width:120px","url"=>__APP__."/Admin/User/userForm/id/[id]/","target"=>"dialog","urlAttr"=>'mask="true" width="960" height="480" title="会员明细"',"searchMode"=>"date",'order'=>'[user.审核日期]','searchRow'=>'user.审核日期'));
		//代理级别的名称
		$list->addshow($level->byname,array("row"=>array(array(&$this,"_printUserLevel"),"[".$level->name."]",$level->name),"searchMode"=>"num","searchSelect"=>$_temp,"searchRow"=>"user.".$level->name."","order"=>'user.'.$level->name,"searchPosition"=>"top"));
		if(isset($area['country'])){
			$list->addshow("代理国家",array("row"=>"[代理国家]","searchMode"=>"text",'searchRow'=>'user.代理国家'));
		}
		if(isset($area['province'])){
			$list->addshow("代理省份",array("row"=>"[代理省份]","searchMode"=>"text",'searchRow'=>'user.代理省份'));
		}
		if(isset($area['city'])){
			$list->addshow("代理城市",array("row"=>"[代理城市]","searchMode"=>"text",'searchRow'=>'user.代理城市'));
		}
		if(isset($area['county'])){
			$list->addshow("代理地区",array("row"=>"[代理地区]","searchMode"=>"text",'searchRow'=>'user.代理地区'));
		}
		if(isset($area['town'])){
			$list->addshow("代理乡镇街道",array("row"=>"[代理街道]","searchMode"=>"text",'searchRow'=>'user.代理街道'));
		}
		foreach(X('fun_bank') as $banks){
			$list->addshow($banks->byname,array("row"=>array(array(&$this,"_base64User"),'[编号]',$banks->objPath(),"[".$banks->name."]"),"css"=>"width:70px","searchRow"=>"b.".$banks->name,"searchMode"=>"num","order"=>'b.'.$banks->name,"sum"=>'b.'.$banks->name));
		}
        $list->addshow("累计收入",array("row"=>"[累计收入]","searchMode"=>"num","order"=>"累计收入"));
        $this->assign('list',$list->getHtml());     
        $this->display();		
    }
	/**
    +----------------------------------------------------------
	* 修改页面
    +----------------------------------------------------------
	*/
	public function edit() 
    {
		if(strpos(I("get.id/s"),',') !== false){
			$this->error('参数错误!');
		}
	    $require=explode(',',CONFIG('USER_REG_REQUIRED'));
		$show=explode(',',CONFIG('USER_REG_SHOW'));
	    $edit=explode(',',CONFIG('USER_EDIT_SHOW'));
		//获取xml中 奖励金额比例 项
		$rewardMoney	= array();
		//读取货币模块
		$funbank = array();
		foreach(X('fun_bank') as $node)
		{
			$funbank[$node->byname] =  $node->name;
		}
		//读取奖金节点  循环  显示每个prize奖金节点的 拿奖比例
		foreach(X('tle') as $tle)
		{
			foreach(X('prize_*',$tle) as $node)
			{
				if($node->prizeMode>=0)	
				{
					$rewardMoney[$node->byname . "比例"] =  $node->name . "比例";
				}
			}
		}
		//fun_lock 添加的是与否的显示字段内容 0 1
		$funlock=array();
       	foreach(X('fun_lock') as $fun_lock)
		{
			$funlock[] =  $fun_lock->name;
		}
		//添加的fun_val字段控制是否显示与修改
		$funval = array();
		$admineditary=array();
		foreach(X('fun_val') as $node)
		{
			//可显示的fun_val内容
			if($node->adminView){
				//是否可修改
				$funval[$node->byname] =  $node->name;
				$admineditary[$node->byname]=$node->adminEdit;
			}
		}
		$this->assign("admineditary",$admineditary);
		//每个级别
        foreach(X('levels') as $node)
		{
			$level[$node->name]['con'][0]='无级别';
			$level[$node->name]['giveEdit']=$node->giveEdit;
			$level[$node->name]['regEdit']=$node->regEdit;
			$level[$node->name]['byname']=$node->byname;
			foreach($node->getcon('con',array('lv'=>0,'name'=>'')) as $con)
			{
				$level[$node->name]['con'][$con['lv']]=$con['name'];
			}
		}
		//信用额度 货币交易的
		if($this->userobj->tradeMoney!=""){
			$this->assign('credit',"true");
		}
		//获取会员信息
		$model		= M('会员');
		$id			= I("request.id/d");
		$vo			= $model->table('dms_会员 user inner join dms_货币 b on user.id=b.userid')->where(array("user.id"=>$id))->find();
		$olduserary = $vo;
		/*//安置网体业绩修改
		$netPlaceName = array();
		foreach(X('net_place') as $netPlace){
			$regions=$netPlace->getcon("region",array('name'=>''));
			foreach($regions as $region){
				$netPlaceName[$netPlace->byname][]=$region['name'];
			}
		}*/
		//查询所有的银行卡
		$Bank	= M('银行卡');
		$banklist	= $Bank->order('id asc')->select();
		$this->assign('banklist',$banklist);
		//网络图显示修改
		$netname=array();
		foreach(X('net_rec,net_place') as $net){
			$netname[] = $net->name;
		}
		$this->assign('id',$id);
		$this->assign('olduserary',$olduserary);
		$this->assign('pwd3Switch',adminshow('pwd3Switch'));
		$this->assign('netname',$netname);
		//$this->assign('netPlaceName',$netPlaceName);
		$this->assign('funlock',$funlock);
		$this->assign('funbank',$funbank);
		$this->assign('name',$this->userobj->byname);
        $this->assign('edit',$edit);
		$this->assign('level',$level);
		import("COM.Mobile.NumCheck");
		$this->assign('NumCheck',NumCheck::$data);
	    $this->assign('require',$require);
		$this->assign('show',$show);
		$this->assign ( 'rewardMoneys', $rewardMoney );
		$this->assign ( 'funval', $funval );
		$this->assign ( 'vo', $vo );
		$this->assign ( 'shop', $this->userobj->shopWhere =='[服务中心]=1');
		//$lists=M()->table('dms_修改日志 a')->join('admin b on a.修改人=b.id')->where(array("userid"=>$id))->field('a.*,b.account')->order('a.id desc')->select();
		//$this->assign('lists',$lists);
		$this->display (); 
	}
	/*
	***查看页面
	*/
    public function view() 
    {
        if(strpos(I("get.id/s"),',') !== false){
            $this->error('参数错误!');
        }
        $require=explode(',',CONFIG('USER_REG_REQUIRED'));
        $show=explode(',',CONFIG('USER_REG_SHOW'));
        $edit=explode(',',CONFIG('USER_EDIT_SHOW'));
        $model     = M('会员');
        $id         = I("request.id/d");
        $where['id']= $id;
        $vo         = $model->where($where)->find();
        //获取xml中 奖励金额比例 项
        $rewardMoney    = array();
        foreach(X('tle') as $tle)
        {
            foreach(X('prize_*',$tle) as $node)
            {
                if($node->prizeMode>=0) 
                {
                    $rewardMoney[$node->byname . "比例"] =  $node->name . "比例";
                }
            }
        }
        $funval = array();
        foreach(X('fun_val') as $node)
        {
            if($node->adminView) $funval[$node->byname] =  $node->name;
        }
        //显示级别信息  直接找出会员表中的数据
        foreach(X('levels') as $node)
        {
            $level[$node->byname]['name']=$node->name;
            if($vo[$node->name]){
                foreach($node->getcon('con',array('lv'=>0,'name'=>'')) as $con)
                {
                    if($vo[$node->name]==$con['lv'])
                        $level[$node->byname]['lv']=$con['name'];
                    if($vo[$node->name]==$con['lv'])
                        $level[$node->byname]['applylv']=$con['name'];
                    if($vo[$node->name]==$con['lv'])
                        $level[$node->byname]['givelv']=$con['name'];
                }
            }
            if(!isset($level[$node->byname]['lv']))
                $level[$node->byname]['lv']="无级别";
            if(!isset($level[$node->byname]['applylv']))
                $level[$node->byname]['applylv']="无级别";
            if(!isset($level[$node->byname]['givelv']))
                $level[$node->byname]['givelv']="无级别";
        }
        $netPlaceName = array();
        foreach(X('net_place') as $netPlace){
            $regions=$netPlace->getcon("region",array('name'=>''));
            foreach($regions as $region){
                $netPlaceName[$netPlace->name][]=$region['name'];
            }
        }
        //把后台会员资料查看里面显示的0、1替换成男、女
        if($vo['性别']==0){
           $vo['性别']='男';
        }elseif($vo['性别']==1){
           $vo['性别']='女';
        }
        $this->assign('pwd3Switch',adminshow('pwd3Switch'));
        $this->assign('name',$this->userobj->byname);
        $this->assign('netPlaceName',$netPlaceName);
        $this->assign('edit',$edit);
        $this->assign('level',$level);
        $this->assign('require',$require);
        $this->assign('show',$show);
        $this->assign ( 'rewardMoneys', $rewardMoney );
        $this->assign('funval',$funval);
        $this->assign ( 'vo', $vo );
        $this->display ();
    }
	/**
    +----------------------------------------------------------
	* 保存修改
    +----------------------------------------------------------
	*/
	public function update()
    {
		$model		= M('会员');
		$model_h	= M('货币');//货币分离
		$fieldList_h=array();//货币分离
		$data_h=array();//货币分离
		M()->startTrans();
		$updateuser = $model->lock(true)->find(I("post.id/d"));
		if(adminshow('user_id')==true){
			if(!$updateuser)
			{
				M()->rollback();
				$this->error('要修改的会员不存在');
			}
			//会员编号判定
			if(C('VERSION_SWITCH') == '0'){
				$userbh = I("post.userbh/s");
			    if(!preg_match('/^[a-zA-Z0-9_]+$/',$userbh)){ //匹配
			    	M()->rollback();
			        $this->error('新会员编号必须为英文数字下划线组合');
			    }
			    //存在同编号不同ID的会员
			    if(M('会员')->where(array('编号'=>$userbh,'id'=>array('neq',I("post.id/d"))))->find())
			    {
			    	M()->rollback();
			    	$this->error('新会员编号已被使用');
			    }
			}
		}
		$data		= array(); //待修改的数据
		$fieldList	= array(
			"name"				=>'姓名',
			"alias"				=>'昵称',
			"reciver"			=>'收货人',
			"id_card"			=>'证件号码',
			"bank_apply_name"	=>'开户银行',
			"bank_card"			=>'银行卡号',
			"bank_name"			=>'开户名',
			"bank_apply_addr"	=>'开户地址',
			"country"			=>'国家',
			"province"			=>'省份',
			"city"				=>'城市',
			"county"			=>'地区',
		    "town"			    =>'街道',
			"sex"				=>'性别',
			"country_code"		=>'国家代码',
			"mobile"			=>'移动电话',
			"address"			=>'地址',
			"email"				=>'email',
			"qq"				=>'QQ',
			"pass1"				=>'pass1',
			"pass2"				=>'pass2',
			"userStatus"		=>'状态',
			"nullStatus"		=>'空点',
			"weixin"		    =>'微信账号',
			"secretsafe_name"	=>'密保问题',
			"secretanswer"		=>'密保答案',
			"memo"		        =>'备注',
		);
		if(adminshow('pwd3Switch')){
			$fieldList['pass3'] = 'pass3';
		}
		foreach(X('net_rec,net_place') as $net){
			$fieldList[$net->name.'网络显示'] = $net->name.'网络显示';
		}
		//判断是否为必填
        $edit=explode(',',CONFIG('USER_EDIT_SHOW'));
		$requirearr=explode(',',CONFIG('USER_REG_REQUIRED'));
		if(in_array('area',$edit))
		{
			foreach($edit as $key=>$val)
			{
				if($val=='area')
				{
					unset($edit[$key]);
				}
			}
			$edit[]='country';
			$edit[]='province';
			$edit[]='city';
			$edit[]='county';
			$edit[]='town';
		}
		if(in_array('area',$requirearr))
		{
			foreach($requirearr as $key=>$val)
			{
				if($val=='area')
				{
					unset($requirearr[$key]);
				}
			}
			$requirearr[]='country';
			$requirearr[]='province';
			$requirearr[]='city';
			$requirearr[]='county';
			$requirearr[]='town';
		}		
		//遍历fun_val得到必填的fun_val
		foreach(X('fun_val') as $funval){
			if($funval->required == "true"){
				$requirearr[] = $funval->name;
				$edit[] = $funval->name;
			}
			$fieldList[$funval->name] = $funval->name;
		}
		foreach($requirearr as $requireinfo)
		{
			if(in_array($requireinfo,$edit) && I("post.".$requireinfo."/s")=='')
			{
				M()->rollback();
				$this->error('请填写'.$fieldList[$requireinfo].'信息');
			}
		}
		
		/******************** 获取xml中 奖励金额比例 项  - 开始 ********************/
		$rewardMoney	= array();
		foreach(X('tle') as $tle)
		foreach(X('prize_*',$tle) as $node)
		{
			if($node->prizeMode>=0)	
			{
				$fieldList[$node->name . "比例"] =  $node->name . "比例";
			}
		}
		foreach(X('fun_lock') as $fun_lock)
		{
			$fieldList[$fun_lock->name] =$fun_lock->name;
		}
		foreach(X('fun_bank') as $fun_bank)
		{
			//$fieldList[$fun_bank->name."锁定"] = $fun_bank->name . "锁定";
			$fieldList_h[$fun_bank->name."锁定"] = $fun_bank->name . "锁定";//货币分离
		}
		//修改级别
		foreach(X('levels') as $node)
		{
			$fieldList[$node->name] =  $node->name;
			if($node->regEdit)
				$fieldList['申请'.$node->name] =  '申请'.$node->name;
			if($node->giveEdit)
				$fieldList['赠送'.$node->name] =  '赠送'.$node->name;
		}
		//修改店铺状态
		if($this->userobj->shopWhere=='[服务中心]=1')
		{
			$fieldList['isshop'] = "服务中心";
		}
		/******************** 获取xml中 奖励金额比例 项  - 结束  ********************/
		foreach(I("post.") as $key => $val )
		{
			foreach( $fieldList as $fkey=> $filed)
			{			
				if( $fkey == $key and $val!="!!noeditpass!!")
				{
					$data[ $filed ] = $this->safe_replace($val);
				}
			}
			foreach( $fieldList_h as $fkey=> $filed)//货币分离
			{	
				if( $fkey == $key and $val!="!!noeditpass!!")
				{	
					$data_h[ $filed ] = $this->safe_replace($val);
				}
			}
		}
		//处理密码加密
		$where['id']	= I("post.id/d");
		if(I("post.pass1/s")!="!!noeditpass!!"){
			$data['pass1']	= md100($data['pass1']);
		}
		if(I("post.pass2/s")!="!!noeditpass!!"){
			$data['pass2']	= md100($data['pass2']);
		}
		if(adminshow('pwd3Switch')){
			if(I("post.pass3/s")!="!!noeditpass!!"){
				$data['pass3'] = md100( $data['pass3']);
			}
		}
		$model_h->where(array('userid'=>I("post.id/d")))->save($data_h);//货币分离
		$ret=$model->where($where)->save($data);
		if( $ret !== false){
			if($ret==1)
			{
				//修改日志和操作日志 
				$updateuser['修改人']   = $_SESSION[C ('RBAC_ADMIN_AUTH_KEY')];
				$updateuser['修改时间'] = systemTime();
				$updateuser['ip']   = get_client_ip();
				$updateuser['userid']   = $updateuser['id'];
				//dump($fieldList['name']);
				//exit();
				unset($updateuser['id']);
				//如果修改内容有变更，则需要插入修改日志
				$logid=$this->saveAdminLog('','',$this->userobj->byname.'资料修改',$this->userobj->name.'['.$updateuser['编号']."]资料修改");
				$updateuser['logid']   = $logid;
				M('修改日志')->add($updateuser);
				//写入会员操作日志
				//$authInfo['姓名']=$updateuser['姓名'];
				$authInfo['编号']=$updateuser['编号'];
				$authInfo['id']=$updateuser['userid'];
				$data = array();
				$datalog['user_id']=$authInfo['id'];
				//$datalog['user_name']=$authInfo['姓名'];
				$datalog['user_bh']=$authInfo['编号'];
				$datalog['ip'] = get_client_ip();
				$datalog['content']= '管理员修改资料';
				$datalog['create_time']=time();
				//获取会员的IP地址
				import("ORG.Net.IpLocation");
				$IpLocation				= new IpLocation("qqwry.dat");
				$loc					= $IpLocation->getlocation();
				$country				= mb_convert_encoding ($loc['country'] , 'UTF-8','GBK' );
				$area					= mb_convert_encoding ($loc['area'] , 'UTF-8','GBK' );
				$datalog['address']		= $country.$area;
				M('log_user')->add($datalog);
				//写入会员操作日志结束
			}
			if(adminshow('user_id')==true){
				//查看是否有必要更新会员编号
				if(C('VERSION_SWITCH') == '0'){
					if($userbh != $updateuser['编号'])
					{
						X('user')->callevent('modifyId',array('oldbh'=>$updateuser['编号'],'newbh'=>$userbh));
						//如果修改内容有变更，则需要插入修改日志
						$updateuser['修改人']   = $_SESSION[C ('RBAC_ADMIN_AUTH_KEY')];
						$updateuser['修改时间'] = systemTime();
						$updateuser['ip']   = get_client_ip();
						$updateuser['userid'] = $updateuser['id']==''?$updateuser['userid']:$updateuser['id'];
						unset($updateuser['id']);
						//如果修改内容有变更，则需要插入修改日志
						$logid=$this->saveAdminLog('','','修改'.$this->userobj->byname.'编号',$this->userobj->name.'['.$updateuser['编号']."]修改编号[".$userbh."]");
						$updateuser['logid']   = $logid;
						$updateuser['编号']=$userbh;
						M('修改日志')->add($updateuser);
						
						//写入会员操作日志
						//$authInfo['姓名']=$updateuser['姓名'];
						$authInfo['编号']=$userbh;
						$authInfo['id']=$updateuser['userid'];
						$data = array();
						$datalog['user_id']=$authInfo['id'];
						//$datalog['user_name']=$authInfo['姓名'];
						$datalog['user_bh']=$authInfo['编号'];
						$datalog['ip'] = get_client_ip();
						$datalog['content']= '管理员修改编号';
						$datalog['create_time']=time();
						//获取会员的IP地址
						import("ORG.Net.IpLocation");
						$IpLocation				= new IpLocation("qqwry.dat");
						$loc					= $IpLocation->getlocation();
						$country				= mb_convert_encoding ($loc['country'] , 'UTF-8','GBK' );
						$area					= mb_convert_encoding ($loc['area'] , 'UTF-8','GBK' );
						$datalog['address']		= $country.$area;
						M('log_user')->add($datalog);
					}
				}
			}			
			M()->commit();
			$this->success("修改成功");
		}else{
			M()->rollback();
			$this->error("修改失败");
		}
	}
	/**
    +----------------------------------------------------------
	* 授权登入
    +----------------------------------------------------------
	*/
	public function loginToUser()
	{
		//跳转到前台框架页
        $authInfo = M('会员')->where(array("编号"=>I("request.id/s")))->find();
        if(!$authInfo){
        	echo '<script>alert("该用户不存在");window.close();</script>';die;
        }else{
            $_SESSION[C('USER_AUTH_KEY')]	=  $authInfo['id'];
		    $_SESSION[C('USER_AUTH_NUM')]	=  $authInfo['编号'];
		    $_SESSION['username']		    =  $authInfo['姓名'];
			$_SESSION['logintype'] = "admin";
			$_SESSION['ip'] = get_client_ip();
			foreach($this->userobj->getcon('session',array('name'=>'','rename'=>'')) as $session)
			{
				if($session['rename'] !=''){
				   $_SESSION[$session['rename']]=$authInfo[$session['name']];
				}else{
					$_SESSION[$session['name']]=$authInfo[$session['name']];
				}
			}
			//1正常 2维护 3无法访问
			$SYSTEM_STATE=CONFIG('SYSTEM_STATE');
            //不能登入提示内容
			$SYSTEM_CLOSE_TITLE=CONFIG('SYSTEM_CLOSE_TITLE');
			$startOpenTime = CONFIG('startOpenTime');
            $endOpenTime = CONFIG('endOpenTime');

            $week = date('w',time());
            $hour = date('G',time());

            $startHour = $startOpenTime ? $startOpenTime[$week] : 0;
            $endHour = $startOpenTime ? $endOpenTime[$week] : 24;
			if($hour < $startHour || $hour > $endHour){
				$sysStatus = "非开放时间";
			}else if($SYSTEM_STATE==2){
				$sysStatus=$SYSTEM_CLOSE_TITLE==""?'维护':$SYSTEM_CLOSE_TITLE;
			}else if($SYSTEM_STATE==3){
				$sysStatus=$SYSTEM_CLOSE_TITLE==""?'关闭':$SYSTEM_CLOSE_TITLE;
			}
			$this->saveAdminLog('','','登陆会员前台','登陆会员['.$authInfo['编号']."]的前台");
			if($sysStatus!=''){
				echo '<script>alert("当前系统处于'.$sysStatus.'状态")</script>';
				redirect(__APP__.'/User/Index/index',1,'<span style="font-weight:bold;color:red">系统处于'.$sysStatus.'状态</span> 将在1秒后跳转到前台～');
			}else{
				redirect(__APP__.'/User/Index/index');
			}
        }
	}
	/*
	* 打印名称
	*/
	public function _printName($name)
	{
		return empty($name)?'[暂无]':$name;
	}
	//审核确认 审核会员  相当于审核注册订单
    public function accok()
	{
		set_time_limit(1800);
		ini_set('memory_limit','500M');
		$errMsg = '';
		$succNum = 0;
		$errNum = 0;
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
			M()->startTrans();
			M('会员')->lock(true)->where('id<0')->find();
			$sdata = M("报单")->lock(true)->where(array('userid'=>$id))->find();
			
			$salename=$sdata['报单类别'];
			$userid=$sdata['编号'];
			$sale=X('sale_*@'.$salename);
			if($userid=='' || $sale===false){
				$errNum++;
				$errMsg .= $userid.'：参数错误！<br/>';
				continue;
			}
			//审核 扣款
			$return = $sale->accok($sdata,true);
			if($return !== true){
				$errNum++;
				$errMsg .= $userid.'：'.$return.'<br/>';
				M()->rollback();
				continue;
			}
			$this->saveAdminLog($sdata,'','审核会员','审核会员['.$userid."]");
			M()->commit();
			$succNum++;
		}
		if($errNum !=0){
			$this->error("审核成功：".$succNum .'条记录；审核失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("审核成功：".$succNum .'条记录；');
		}
	}
	/**
    +----------------------------------------------------------
	* 锁定会员
    +----------------------------------------------------------
	*/
	public function suoding(){
		$errMsg = '';
		$succNum = 0;
		$errNum = 0; 
		foreach(explode(',',I("get.id/s")) as $id){
			$message = '';
			if($id == '') continue;
			M()->startTrans();
			$list	 = M('会员')->where(array("id"=>$id))->find();
			if($list['登陆锁定']==1){
				$message='已经被锁定';
			}else{
				$save	 = array("登陆锁定"=>1);
				M('会员')->where(array("id"=>$id))->save($save);
			}
			if($message !== ''){
				//$this->error($message);
				$errNum++;
				$errMsg .= $list['编号'].'：'.$message.'<br/>';
				M()->rollback();
			}else{
				$succNum++;
				$this->saveAdminLog($list,'',$this->userobj->byname.'锁定','锁定'.$this->userobj->byname.'['.$list['编号'].']');
				M()->commit();
				//$this->success("删除成功！");
			}
		}
		if($errNum !=0){
			$this->error("操作成功：".$succNum .'条记录；操作失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("操作成功：".$succNum .'条记录；');
		}
		
	}
	/**
    +----------------------------------------------------------
	* 解锁会员
    +----------------------------------------------------------
	*/
	public function jiesuo(){
		$errMsg = '';
		$succNum = 0;
		$errNum = 0; 
		foreach(explode(',',I("get.id/s")) as $id){
			$message = '';
			if($id == '') continue;
			M()->startTrans();
			$list	 = M('会员')->where(array("id"=>$id))->find();
			if($list['登陆锁定']==0){
				$message='已经被解锁';
			}else{
				$save	 = array("登陆锁定"=>0);
				M('会员')->where(array("id"=>$id))->save($save);
			}
			if($message !== ''){
				//$this->error($message);
				$errNum++;
				$errMsg .= $list['编号'].'：'.$message.'<br/>';
				M()->rollback();
			}else{
				$succNum++;
				$this->saveAdminLog($list,'',$this->userobj->byname.'解锁','解锁'.$this->userobj->byname.'['.$list['编号'].']');
				M()->commit();
				//$this->success("删除成功！");
			}
		}
		if($errNum !=0){
			$this->error("操作成功：".$succNum .'条记录；操作失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("操作成功：".$succNum .'条记录；');
		}
		
	}
	
	//删除会员前
    public function pre_delete()
	{
		$sdata = array();
		if(I("get.id/s")){
			$sdata = M("会员")->where(array("id"=>array("in",I("get.id/s"))))->getField("id idkey,编号,审核日期,注册日期,状态");
			$this->assign('ids',I("get.id/s"));
			//获取所有钱包
			foreach(X('fun_bank') as $bank)
			{
				$banks[]=$bank->name;
			}
			foreach($sdata as $userdata)
			{
				//计算被删除会员的所有钱包之和
				$sumMoney = M('货币')->where(array('编号'=>$userdata['编号']))->sum(implode("+",$banks));
				//判断是否开启转账给未激活(状态=无效)会员
				if(adminshow(zhuanzhang) && $sumMoney>0)
				{
					$result = true;
					$this->assign('result',$result);
				}
			}
		}
		$this->assign('sdata',$sdata);
		$this->display();
	}
	
	/**
    +----------------------------------------------------------
	* 删除会员
    +----------------------------------------------------------
	*/
	public function delete(){
		$errMsg = array();//'';
		$succNum = 0;
		$errNum = 0; 
		foreach(explode(',',I("post.ids/s")) as $id){
			if($id == '') continue;
			M()->startTrans();
			$list	 = M('会员')->where(array("id"=>$id))->find();
			$message = $this->userobj->delete($id);
			if($message !== true){
				//$this->error($message);
				$errNum++;
				//$errMsg .= $list['编号'].'：'.$message.'<br/>';
				$errMsg[$id]= array('msg'=>$message);
				M()->rollback();
			}else{
				$succNum++;
				$errMsg[$id]= array('msg'=>'删除成功');
				$this->saveAdminLog($list,'',$this->userobj->byname.'删除','删除'.$this->userobj->byname.'['.$list['编号'].']');
				M()->commit();
				//$this->success("删除成功！");
			}
		}
		echo json_encode($errMsg);
		/*if($errNum !=0){
			$this->error("删除成功：".$succNum .'条记录；删除失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("删除成功：".$succNum .'条记录；');
		}*/
		
	}
	/**
    +----------------------------------------------------------
	* 注册协议
    +----------------------------------------------------------
	*/
	public function agreement(){
		$this->assign('regAgreement',F('regAgreement'));
		$this->display();
	}
	//注册协议设置保存
	public function saveAgreement(){
		F('regAgreement',get_magic_quotes_gpc() ? stripslashes(I("post.agreementContent/s")) : I("post.agreementContent/s"));
		$this->saveAdminLog("",'','注册协议保存','注册协议保存数据');
		$this->success('设置完成!');
	}
	//删除所有无效会员
	public function deleteAllInvalidUser(){
		$num = $this->userobj->deleteAllInvalidUser();
		$this->saveAdminLog('','','删除无效'.$this->userobj->byname,"删除无效".$this->userobj->byname.$num."个");
		$this->success("删除无效".$this->userobj->byname.$num."个！");
	}
	//批量升级
	public function bulkUpLevel()
	{
        foreach(X('levels') as $node)
		{
			$level[$node->name]['giveEdit']=$node->giveEdit;
			$level[$node->name]['regEdit']=$node->regEdit;
			$level[$node->name]['con'] = array();
			foreach($node->getcon('con',array('lv'=>0,'name'=>'')) as $key=>$con)
			{
				if($key)
				{
					$level[$node->name]['con'][$con['lv']]=$con['name'];
				}
			}
		}
		$this->assign('level',$level);
		$this->display();
	}
	//批量升级操作
	public function bulkUpLevelExecute()
	{
		if(I("post.iddata/s") == '')
		{
			$this->error('未填写任何编号信息!');
		}
		$idarr=explode("\n",I("post.iddata/s"));
		foreach($idarr as $key=>$id)
		{
			$id=str_replace("\r","",$id);
			$id=trim($id);
			$idarr[$key] = $id;
			if($id=='')
			{
				unset($idarr[$key]);
			}
		}
		
		$finduser = M('会员','dms_')->where(array('编号'=>array('IN',$idarr)))->select();
		//dump($finduser);
		//$idarr用于后期判定没有找到的人，$idarr2用于在不存在未找到人的情况下。进行更新
		$idarr2=$idarr;
		//如果查询到的人数和数组人数不符
		foreach($finduser as $u)
		{
			unset($idarr[array_search($u['编号'],$idarr)]);
		}
		if(count($idarr)>=1)
		{
			$errstr='';
			foreach($idarr as $id2)
			{
				$errstr.='编号['.$id2.']未找到<br>';
			}
			$this->error($errstr);
		}
		$levels = X('levels@'.I("post.levels/s"));
		if($levels==null)
		{
			$this->error('传入级别名称错误');
		}
		M()->startTrans();
		M('会员','dms_')->where(array('编号'=>array('IN',$idarr2)))->save(array($levels->name=>I("post.lval/d")));
		M()->commit();
		$this->success('升级完成!');
	}
	//会员的相关操作日志
	public function userForm(){
		$id   = I("request.id/d");
		$this->assign('id',$id);
        $list=new TableListAction("log");
        $list->editList = false;
        $list->excel = false;
       	$list ->setShow = array(
        	"操作时间"=>array("row"=>"[create_time]","format"=>"time","searchRow"=>"create_time","searchMode"=>"date","searchPosition"=>"top"),
        	"操作内容"=>array("row"=>"[content]","searchMode"=>"text","searchPosition"=>"top"),
        	"登陆地址"=> array("row"=>"[address]","css"=>"width:200px","searchMode"=>"text","excelMode"=>"text","searchPosition"=>"top"),
        	"登陆IP"=> array("row"=>"[ip]","css"=>"width:200px","searchMode"=>"text","excelMode"=>"text","searchPosition"=>"top"),
		);
        $list->table("dms_log_user as a");
        $list->where(array('user_id'=>$id));
        $list->showPage=true;  // 是否显示分页 默认显示
        $list->numPerPage = '14';
        $list->order("create_time desc");
		$list->autoLoad = false;
        $this->assign('list',$list->getHtml());
	    $this->display();
	}
	/*字符过滤url*/
	public function safe_replace($string) {
		$string = str_replace('%20','',$string);
		$string = str_replace('%27','',$string);
		$string = str_replace('%2527','',$string);
		$string = str_replace('*','',$string);
		$string = str_replace('"','&quot;',$string);
		$string = str_replace("'",'',$string);
		$string = str_replace('"','',$string);
		$string = str_replace(';','',$string);
		$string = str_replace('<','&lt;',$string);
		$string = str_replace('>','&gt;',$string);
		$string = str_replace("{",'',$string);
		$string = str_replace('}','',$string);
		$string = str_replace('\\','',$string);
		return $string;
	}
}
?>