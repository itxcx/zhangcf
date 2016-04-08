<?php
defined('APP_NAME') || die('不要非法操作哦!');
// 本类由系统自动生成，仅供测试用途
class PrizeLockAction extends CommonAction {
	public function index()
	{
		$net=X('net_place,net_rec');
		$butset=array(                 // 底部操作按钮显示定义
			'根据编号修改'=>array("class"=>"edit","href"=>__URL__."/editByName","target"=>"dialog","title"=>"添加名单","mask"=>"true","width"=>"500","height"=>"480"),
			);
		//如果有网体节点,可以显示根据网体关系修改
		if($net)
		{
			$butset['根据网络关系修改']=array("class"=>"edit","href"=>__URL__."/editByNet","target"=>"dialog","title"=>"添加名单","mask"=>"true","width"=>"500","height"=>"200");
		}
        $list=new TableListAction('会员');
        $list->table('dms_会员 user inner join dms_货币 b on user.id=b.userid');
		$list->setButton=$butset;
        $where   =array('user.奖金锁'=>1);
        $list->where($where);
        $list->addshow("编号",array("row"=>"[编号]","css"=>"width:60px","searchMode"=>"text",'searchGet'=>'userid',"searchPosition"=>"top","excelMode"=>"text",'searchRow'=>'user.编号'));  
		$list->addshow('姓名',array('row'=>'[姓名]',"searchMode"=>"text",'searchRow'=>'user.姓名'));
        $list->addshow("状态",array("row"=>array(array(&$this,"_zhuangtai"),"[状态]"),"css"=>'width:50px'));
        $list->addshow("注册日期",array("row"=>"[注册日期]","format"=>"date","css"=>"width:60px","url"=>__APP__."/Admin/User/userForm/id/[id]/","target"=>"dialog","urlAttr"=>'mask="true" width="960" height="480" title="会员明细"',"searchMode"=>"date","order"=>"[user.注册日期]",'searchRow'=>'user.注册日期'));
        $list->addshow("审核日期",array("row"=>"[审核日期]","format"=>"date","css"=>"width:60px","url"=>__APP__."/Admin/User/userForm/id/[id]/","target"=>"dialog","urlAttr"=>'mask="true" width="960" height="480" title="会员明细"',"searchMode"=>"date",'order'=>'[user.审核日期]','searchRow'=>'user.审核日期'));
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
	       	$list->join('left join dms_会员 as '.$net->name.' on user.'.$net->name.'_上级编号='.$net->name.'.编号');
        	$netnamerow.=",{$net->name}.姓名 as netname".$net->getPos();
        	//$list->addshow($net->byname."人姓名",array("row"=>"[netname".$net->getPos()."]","searchMode"=>"text","excelMode"=>"text",'searchRow'=>"{$net->name}.姓名"));
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
		echo $list->getHtml();
	}
    public function _zhuangtai($status){
       	if($status=='无效'){
       		return "<span style='background-color:#FF0000;font-weight:bold;color:#316EDA;display:block;padding:5px 0px'>{$status}</span>";
       	}else{
        	return "<span style='background-color:#316EDA;font-weight:bold;color:#FFFFFF;display:block;padding:5px 0px'>{$status}</span>";
       	}
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
	//名单处理
	public function editByName()
	{
		$this->display();
	}
	//处理添加或者移除
	public function editByNameRun()
	{
		$data   = I('post.name/s');
		$value = I('post.value/d');
		if($data=='')
		{
			$this->error('请输入编号信息');
		}
		$dataarr=explode("\r\n",$data);
		M()->startTrans();
		foreach($dataarr as $name)
		{
			if($name!='')
			{
				$user=M('会员')->where(array('编号'=>$name))->find();
				if(!$user)
				{
					$this->error('编号:'.$name.'未找到');
				}
				else
				{
					M('会员')->where(array('编号'=>$name))->save(array('奖金锁'=>$value));
				}
			}
		}
		M()->commit();
		$this->success('处理完成');
	}
	public function editByNet()
	{
		$nets = X('net_rec,net_place');
		$this->assign('nets',$nets);
		$this->display();
	}
	public function editByNetRun()
	{
		$netname = I('post.netname/s');
		$name    = I('post.name/s');
		$value   = I('post.value/d');
		$net=X('@'.$netname);
		if(get_class($net) != 'net_rec' && get_class($net) != 'net_place')
		{
			$this->error('网体类型错误');
		}
		M()->startTrans();
		$user = M('会员')->where(array('编号'=>$name))->find();
		if(!$user)
		{
			$this->error('输入的编号未查到');
		}
		$users=$net->getdown($user,0,0,'',false,true);
		$users[] = $user;
		foreach($users as $user)
		{
			M('会员')->where(array('id'=>$user['id']))->save(array('奖金锁'=>$value));
		}
		M()->commit();
		$this->success('处理完成');
	}
}
?>