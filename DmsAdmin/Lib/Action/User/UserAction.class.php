<?php
defined('APP_NAME') || die(L('不要非法操作哦'));
class UserAction extends CommonAction {

	//修改密码
	public function setPass()
	{		
		$this->assign('changePwdsmsSwitch',CONFIG('changePwdsmsSwitch'));
		$this->assign('changePwdsmsContent',CONFIG('changePwdsmsContent'));
		$this->assign('verificateSwitch',CONFIG('verificatesmsSwitch'));
		$this->assign('verificatesmsContent',CONFIG('verificatesmsContent'));
		$this->assign('changePwdmailSwitchyanzheng',CONFIG('changePwdmailSwitchyanzheng'));
		$this->assign('changePwdmailContentyanzheng',CONFIG('changePwdmailContentyanzheng'));
		$this->assign('pwd3Switch',adminshow('pwd3Switch'));
		$this->display();
	}

	public function passSave()
	{
		//防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
		B('XSS');
		$pass1			= trim(I('post.pwd1/s'));
		$pass2			= trim(I('post.pwd2/s'));
		$pass3			= I('post.pwd3/s')!=""?trim(I('post.pwd3/s')):'';
		$repass1		= trim(I('post.repwd1/s'));
		$repass2		= trim(I('post.repwd2/s'));
		$repass3		= I('post.repwd3/s')!=""?trim(I('post.repwd3/s')):'';
		//一级密码输入验证
		if($pass1 !== $repass1){
			$this->error(L('两次输入的一级密码不一致'));
		}elseif(strlen($pass1)<6){
			$this->error(L('一级密码长度不能小于6位'));
		}else{
			$map['pass1']  =md100($pass1);
		
		}
		$pwd3Switch=adminshow('pwd3Switch');
		//二级密码输入验证
		if($pass2 !== $repass2){
			$this->error(L('两次输入的二级密码不一致'));
		}elseif(strlen($pass2)<6){
			$this->error(L('二级密码长度不能小于6位'));
		}else{
			$map['pass2']  = md100($pass2);
		}
		//三级密码输入验证
		if($pwd3Switch && $pass3 !== $repass3){
			$this->error(L('两次输入的三级密码不一致'));
		}elseif($pwd3Switch && strlen($pass3)<6){
			$this->error(L('三级密码长度不能小于6位'));
		}elseif($pwd3Switch){
			$map['pass3']  = md100($pass3);
		}
		
		$isyanzheng = CONFIG('verificatesmsSwitch');
		if($isyanzheng){
			$verify = S($this->userinfo['编号'].'_修改密码');
			if(!$verify || $verify != intval(I('post.repwdSms/s')) || I('post.repwdSms/s')==""){
				$this->error(L('短信验证码错误或已过期'));
			}
		}
		$isyanzhengmail = CONFIG('changePwdmailSwitchyanzheng');
		if($isyanzhengmail){
			$verify = S($this->userinfo['编号'].'_修改密码');
			if(!$verify || $verify != intval(I('post.repwdMail/s')) || I('post.repwdMail/s')==""){
				$this->error(L('短信验证码错误或已过期'));
			}
		}
		M()->startTrans();
		$where['id']	= $this->userinfo['id'];
		$rs	= M('会员')->where($where)->save($map);
		if($rs === false){
			M()->rollback();
			$this->error(L('修改失败'));
		}elseif($rs===0){
			M()->rollback();
			$this->error(L('密码没有发生变化'));
		}else{
			//写入会员操作日志
			$authInfo['姓名']=$this->userinfo['姓名'];
			$authInfo['编号']=$this->userinfo['编号'];
			$authInfo['id']=$this->userinfo['id'];
			$data = array();
			$datalog['user_id']=$authInfo['id'];
			$datalog['user_name']=$authInfo['姓名'];
			$datalog['user_bh']=$authInfo['编号'];
			$datalog['ip']=$_SESSION['ip'];
			$datalog['content']='会员修改密码';
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
           S($this->userinfo['编号'].'_修改密码',null,300);
			//注册短信发送
			$user_mm = $authInfo['编号'];
	        $udata = M('会员')->where("编号 = '$user_mm'")->find();
	        $udata['一级新密码']=$pass1;
	        $udata['二级新密码']=$pass2;
			sendSms("changePwd",$this->userinfo['编号'],'会员修改密码',$udata);
			//会员修改密码发送邮件
			if(CONFIG('changePwdmailSwitch')){
	           	sendMail($udata,$this->userobj->byname.'修改密码',CONFIG('changePwdmailContent'));
			}
			//会员修改密码完成
			M()->commit();
			$this->success(L('修改成功'));
		}
	}
	//资料修改
	public function edit()
	{
		//获得注册参数设置
		$require = explode(',',CONFIG('USER_REG_REQUIRED'));
		$show    = explode(',',CONFIG('USER_VIEW_SHOW'));
	    $edit    = explode(',',CONFIG('USER_EDIT_SHOW'));
		$Bank	= M('银行卡');
		$banklist	= $Bank->order('id asc')->select();
		$this->assign('banklist',$banklist);
		import("COM.Mobile.NumCheck");
		$this->assign('NumCheck',NumCheck::$data);
		$this->assign('edit',$edit);
		$this->assign('require',$require);
		$this->assign('show',$show);
		$this->display();
	}

	//资料查看
	public function view()
	{
		$show=explode(',',CONFIG('USER_VIEW_SHOW'));
		$this->assign('show',$show);
		$this->display();
	}

	
	public function update() 
    {
    	//防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
		B('XSS');
		$model		= M('会员');
		$data		= array(); //待修改的数据
		$fieldList	= array(
			"name"				=>"姓名",
			"reciver"			=>"收货人",
			"alias"				=>"昵称",
			"sex"				=>'性别',
			"id_card"			=>"证件号码",
			"bank_apply_name"	=>"开户银行",
			"bank_apply_addr"	=>"开户地址",
			"bank_card"			=>"银行卡号",
			"bank_name"			=>"开户名",
			"email"				=>"email",
			"qq"				=>"QQ",
			"mobile"			=>"移动电话",
			"country"			=>"国家",
			"province"			=>"省份",
			"city"				=>"城市",
			"county"			=>"地区",
			"town"		    	=>"街道",
			"address"			=>"地址",
			"qq"				=>'QQ',
			"pass1"				=>'pass1',
			"pass2"				=>'pass2',
			"weixin"				=>'微信账号',
			"secretsafe_name"	=>'密保问题',
			"secretanswer"		=>'密保答案',
		);
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
			if($funval->required == "true" && $funval->regdisp){
				$requirearr[] = $funval->name;
				$edit[] = $funval->name;
			}
			$fieldList[$funval->name] = $funval->name;
		}
		foreach($requirearr as $requireinfo)
		{
            if(in_array($requireinfo,$edit) && I("post.".$requireinfo."/s")=="")
			{
				 $this->error(L('请填写').L($fieldList[$requireinfo]).L('信息'));
			}
		}
		//处理手机号校验
		import("COM.Mobile.NumCheck");
		if(in_array('country_code',$requirearr) && NumCheck::check(I("post.mobile/s"),I("post.country_code/s")))
		{
			$this->error('您的移动电话格式不正确');
		}
		foreach( I("post./a") as $key => $val )
		{
			if(!in_array($key,$edit)) continue;//防止非法添加表单
			foreach( $fieldList as $fkey=> $filed)
			{
				if( $fkey == $key )
				{
					$data[ $filed ] = $this->safe_replace($val);
					if($filed=='pass2') unset($data[$filed]);
				}
			}
		}

		$where['id']	= $_SESSION[C('USER_AUTH_KEY')];
		$updateuser = $model->find($_SESSION[C('USER_AUTH_KEY')]);
		if(I("post.pass2/s",'!!noeditpass!!')!="!!noeditpass!!"){
			$userDate = $model->where($where)->find();
			if(!chkpass(I("post.pass2/s"),$userDate['pass2'])){
				$this->error(L('二级密码错误'));
			}
		}
		M()->startTrans();
		if($model->where($where)->save($data)){
			$updateuser['修改时间'] = systemTime();
			$updateuser['ip']   = get_client_ip();
			$updateuser['userid']   = $updateuser['id'];
			unset($updateuser['id']);
			//$updateuser['logid']   = $logid;
			M('修改日志')->add($updateuser);
			//写入会员操作日志
			$authInfo['姓名']=$this->userinfo['姓名'];
			$authInfo['编号']=$this->userinfo['编号'];
			$authInfo['id']=$this->userinfo['id'];
			$data = array();
			$datalog['user_id']=$authInfo['id'];
			$datalog['user_name']=$authInfo['姓名'];
			$datalog['user_bh']=$authInfo['编号'];
			$datalog['ip']=$_SESSION['ip'];
			$datalog['content']='会员修改资料';
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
			M()->commit();
			$this->success(L('修改成功'));
		}else{
			M()->rollback();
			$this->error(L('修改失败'));
		}
	}
    // 我的会员订单
    function myreg(){
	 //循环所有的注册订单
		$baodanleibie	= array();
		foreach(X('sale_reg') as $sale){
			$baodanleibie[] =  "'".$sale->name."'";
		}
		$baodan_string = implode(',',$baodanleibie);
        $list = new TableListAction('报单');
		$list ->table('dms_报单 as a');
        //$list ->field('时间,来源,金额,余额,类型,备注')->where("编号=$this->userinfo['编号']"));
        $list->join("dms_会员 as b on b.编号=a.编号")->where("(a.服务中心编号='{$this->userinfo['编号']}' or a.注册人编号='{$this->userinfo['编号']}') and a.报单类别 in ({$baodan_string})");
		$list->order("a.id desc");
		$fieldStr = '';
		foreach(X('net_rec') as $netRec){
			$fieldStr .= 'b.'.$netRec->name.'_上级编号,';
		}
		foreach(X('net_place') as $netPlace){
			$fieldStr .= 'b.'.$netPlace->name.'_上级编号,';
		}
		foreach(X('levels') as $level){
			$fieldStr .= 'b.'.$level->name.',';
		}

		$fieldStr = trim($fieldStr,',');
        $list->field("b.编号,b.注册日期,b.姓名,{$fieldStr},a.*");
		
        $list->addshow(L('编号'),array("row"=>'<a href="__URL__/viewMyreg/id/[id]" style="color:#e4cc9c">[编号]</a>'));
		$list->addshow(L('订单状态') ,array('row'=>'[报单状态]'));
		//$list->addshow(L('物流状态') ,array('row'=>'[物流状态]'));
		$list->addshow(L('付款日期') ,array('row'=>'[到款日期]',"format"=>"time"));
		//$list->addshow(L('报单状态') ,array('row'=>'[报单状态]'));
		$list->addshow(L('报单金额') ,array('row'=>'[报单金额]'));

        $list->addshow(L('注册日期'),array("row"=>"[注册日期]","format"=>"time"));
        $list->addshow(L('姓名')     ,array("row"=>"[姓名]"));

		foreach(X('levels') as $level){
			$list->addshow(L($level->byname),array("row"=>array(array(&$this,"printUserLevel"),"[{$level->name}]",$level->name)));
		}
        
		foreach(X('net_rec') as $netRec){
			$list->addshow(L($netRec->byname).'人', array("row"=>'['.$netRec->name.'_上级编号]'));
		}
		foreach(X('net_place') as $netPlace){
			$list->addshow(L($netPlace->byname).'人',array("row"=>'['.$netPlace->name.'_上级编号]'));
		}
		$list->addshow(L('操作'),array("row"=>array(array(&$this,'getVeiwDo'),'[物流状态]','[id]',$this->userobj->haveProduct())));
		
        $data = $list->getData();
        $this->assign('data',$data);
        
		$this->display();
    }
	public function getVeiwDo($status,$id,$haveProduct){
		if($status == '未发货' && $haveProduct){
			 return '&nbsp;<a href="__URL__/viewMyreg/id/'.$id.'" >查看</a>';//&nbsp;&nbsp;&nbsp;<a href="__URL__/sended:__XPATH__/id/'.$id.'" style="color:#e4cc9c">发货</a>&nbsp;
		 }else{
			return '&nbsp;<a href="__URL__/viewMyreg/id/'.$id.'"  >查看</a>&nbsp;';
		 }
	}

	public function sended(){
		$saledata = M("报单")->find($_GET["id"]);
		$userid =$saledata['编号'];
		$status = $saledata['物流状态'];
		if($status == "已发货"){
			$this->error("此订单已发货,不可再发货!");
		}elseif($status == '已收货'){
			$this->error('此订单已收货,不可再发货!');
		}else{
			M()->startTrans();
			$result=M("报单")->where(array('id'=>$_GET["id"]))->save(array('物流状态'=>'已发货','发货日期'=>systemTime()));
			if($result){
				M()->commit();
				$this->success(L("发货成功"));
			}else{
				M()->rollback();
				$this->error(L("发货失败"));
			}
		}
	}
	//查看我的会员订单
	public function viewMyreg(){
		$map=array(
		    '_complex'=>array(
		        '_logic'=>'or',
		        '服务中心编号'=>$this->userinfo['编号'],
		        '注册人编号'=>$this->userinfo['编号']
		    ),
		    'id'=>$_GET['id']
		);
		$saleData = M('报单')->where($map)->find();
				if($saleData['产品'] == 1){
			$productData = M('产品订单')->where(array('报单id'=>$_GET['id']))->select();
			$this->assign('productData',$productData);
		}
		$this->assign('name',$this->userobj->byname);
		$this->assign('saleData' ,$saleData);
		$this->assign('adminshow',adminshow('sale_pv'));
		$this->display();
	}
    //我的订单
    function mysale(){
		
        $list=new TableListAction("报单");
        $list ->where("编号 = ".$this->userinfo["编号"]);
        $list ->order('id desc');
        $list ->setShow = array(
             L('购买日期') => array("row"=>'[购买日期]','format'=>'time'),
             L('付款日期') => array("row"=>'[到款日期]','format'=>'time'),
             L('来源编号') => array('row'=>'[来源编号]'),  
        );
        $list ->pagenum=15;        
       
        $data = $list->getData();
        $this->assign('data',$data);
        $this->display();
       
    }

	//公告列表
	public function viewNotice()
	{
		$list	= new TableListAction('公告');
		$where="语言='".C('DEFAULT_LANG')."' and (查看权限=0 or 查看权限={$this->userinfo['id']}";
		foreach(X('net_rec') as $net){
			$netshuju = $this->userinfo[$net->name.'_网体数据'];
			$where .= " or find_in_set(查看权限,'{$netshuju}')";
		}
		foreach(X('net_place') as $net){
			$netshuju = $this->userinfo[$net->name.'_网体数据'];
			foreach($net->getcon("region",array("name"=>""),false) as $region){
				$netshuju = str_replace('-'.$region['name'],'',$netshuju);
			}
			$where .= " or find_in_set(查看权限,'{$netshuju}')";
		}
		$where .=')';
		
        $list->field('id,标题,创建时间')->where($where)->order("id desc");
        $data = $list->getData();
        $this->assign('data',$data); 
		$this->display('viewNotice');
	}
	//公告查看
	public function showNotice()
	{
        $list = M('公告') ->where(array("id"=>$_GET['id']))->find();
		if($list['查看权限']!=0 && $list['查看权限']!=$this->userinfo['id']){
			$net = X('*@'.$list['netname']);
			$netshuju = $this->userinfo[$net->name.'_网体数据'];
			if(get_class($net) == 'net_place'){
				foreach($net->getcon("region",array("name"=>""),false) as $region){
					$netshuju = str_replace('-'.$region['name'],'',$netshuju);
				}
			}
			
			if(!in_array($list['查看权限'],explode(',',$netshuju))){
				$this->error(L('无权限查看'));
			}
		}
		$this ->assign('list',$list);  
		$this->display('showNotice');
	}
	/**/
	public function getSpreadCode()
	{
		$map['id']=$_SESSION[C('USER_AUTH_KEY')];
		$this->userinfo=M('会员')->where($map)->find();
		$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
		$link = $http_type.$_SERVER['HTTP_HOST'];
		$link .='/?'.$this->userinfo["编号"];
		$this->assign('link',$link);
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