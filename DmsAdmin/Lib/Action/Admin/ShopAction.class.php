<?php
/**
+----------------------------------------------------------
* 服务中心模块
+----------------------------------------------------------
*	内部调用查询 无权限限制的方法请使用'_'开头
*/
defined('APP_NAME') || die('不要非法操作哦!');
class ShopAction extends CommonAction 
{
	//服务中心会员单独列出来  控制在系统维护
    public function index()
	{
        $list=new TableListAction('会员');
        $list->table('dms_会员 user inner join dms_货币 b on user.id=b.userid');//货币分离 
        $list->order("user.id desc");
        $list->extraSearch='
        	<li style="margin-top:-2px;height: 23px;">
        	<script type="text/javascript">
        	$.area_default_show = true; //显示默认区域
        	$.area_default_country="中国";
			$.area_select_bind( "country_id" , "province_id" , "city_id" , "county_id", "town_id" );
			</script>
			<select name="country" style="padding:1px;width:100px;display:none;" id="country_id">
			<option value="">请选择</option>
			</select>
			<label>省份：
			<select name="province" style="padding:1px;width:100px" id="province_id" class="select">
			<option value="">请选择</option>
			</select></label>
			<label>城市：
			<select name="city" style="padding:1px;width:100px" id="city_id">
			<option value="">请选择</option>
			</select></label>
			<label>区县：
			<select name="county" style="padding:1px;width:100px" id="county_id">
			<option value="">请选择</option>
			</select></label>
			<label>乡镇：
			<select name="town" style="padding:1px;width:100px" id="town_id">
			<option value="">请选择</option>
			</select></label>
			<input type="hidden" name="extra"/>
			</li>
		';
		$extrawhere='';
		if($list->extraSearch!='' && I("post.extra/s",'null')!="null"){
			if(I("post.province/s")!=""){
				$extrawhere.="user.国家='中国' and user.省份='".I("post.province/s")."' and ";
			}
			if(I("post.city/s")!=""){
				$extrawhere.="user.城市='".I("post.city/s")."' and ";
			}
			if(I("post.county/s")!=""){
				$extrawhere.="user.地区='".I("post.county/s")."' and ";
			}
			if(I("post.town/s")!=""){
				$extrawhere.="user.街道='".I("post.town/s")."' and ";
			}
		}
		$list->where($extrawhere." user.状态='有效' and user.服务中心=1");
		
        $button=array(
			"查看"=>array("class"=>"edit","href"=>__APP__."/Admin/User/view/id/{tl_id}","target"=>"navTab","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_magnify.png'),
            "修改"=>array("class"=>"edit","href"=>__APP__."/Admin/User/edit/id/{tl_id}","target"=>"navTab","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_edit.png'),
       	);
        $list->setButton = $button;
        $list->addshow("ID",array("row"=>'[id]'));
        $list->addshow($this->userobj->byname."编号",array("row"=>array(array(&$this,"_dispUserId"),'[编号]','[状态]','[空点]','[登陆锁定]'),"searchRow"=>"[编号]","searchMode"=>"text","searchRow"=>'user.编号',"searchGet"=>"userid","excelMode"=>"text","searchPosition"=>"top"));
        $list->addshow("姓名",array("row"=>array(array(&$this,"_printName"),"[姓名]"),"searchRow"=>'user.姓名',"searchMode"=>"text"));
        $list->addshow("昵称",array("row"=>"[昵称]","searchMode"=>"text","searchRow"=>'user.昵称'));
        $list->addshow("审核日期",array("row"=>"[审核日期]","format"=>"time","css"=>"width:120px","url"=>__APP__."/Admin/User/userForm/id/[id]/","target"=>"dialog","urlAttr"=>'mask="true" width="960" height="480" title="会员明细"',"searchMode"=>"date",'order'=>'[user.审核日期]','searchRow'=>'user.审核日期'));   
		$list->addshow("空点",array("row"=>array(array(&$this,"_printNull"),"[空点]"),"searchMode"=>"text","searchSelect"=>array("是"=>"1","否"=>"0"),'searchRow'=>'user.空点',"hide"=>true));
		$list->addshow("移动电话",array("row"=>"[移动电话]","searchMode"=>"text",'searchRow'=>'user.移动电话',"hide"=>true));
   		$list->addshow("上级中心",array("row"=>"[服务中心编号]","searchMode"=>"text",'searchRow'=>'user.服务中心编号'));
   		$list->addshow("上级中心",array("row"=>"[服务中心编号]","searchMode"=>"text",'searchRow'=>'user.服务中心编号'));
		foreach(X('fun_bank') as $banks){
			$list->addshow($banks->byname,array("row"=>array(array(&$this,"_base64User"),'[编号]',$banks->objPath(),"[".$banks->name."]"),"css"=>"width:70px","searchRow"=>"b.".$banks->name,"searchMode"=>"num","order"=>'b.'.$banks->name));
		}
		foreach(X('fun_stock') as $fun_stock)
        {
        	$list->addshow($fun_stock->byname,array("row"=>"[$fun_stock->name]","searchMode"=>"num","order"=>$fun_stock->name,"sum"=>'user.'.$fun_stock->name));
        	//$list->addshow($fun_stock->byname."托管",array("row"=>"[".$fun_stock->name."托管]","searchMode"=>"num","order"=>$fun_stock->name."托管"));
        }
        foreach(X('fun_stock2') as $fun_stock)
        {
        	$list->addshow($fun_stock->name,array("row"=>"[$fun_stock->name]","searchMode"=>"num","order"=>$fun_stock->name,"sum"=>'user.'.$fun_stock->name));
        }
		$firstTle=X('tle');
        $list->addshow("累计收入",array("row"=>'<a href="'.__APP__.'/Admin/Tle/index:'.$firstTle[0]->objPath().'/userid/[编号]" target="navTab" title="'.X('user')->byname.$firstTle[0]->byname.'查询" rel="'.md5(__APP__.'/Admin/Tle/index/'.$firstTle[0]->objPath()).'">[累计收入]</a>',"searchMode"=>"num",'searchRow'=>'user.累计收入',"order"=>"user.累计收入"));
        echo $list->getHtml();
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
	/*
	* 打印名称
	*/
	public function _printName($name)
	{
		return empty($name)?'[暂无]':$name;
	}
	//内部使用函数 生成链接到货币明细的链接
	public function _base64User($userid,$xpath,$name){
		return '<a href="'.__APP__.'/FunBank/index:'.$xpath.'/userid/'.$userid.'" target="navTab" mask="true">'.$name.'</a>';
	}
}
?>