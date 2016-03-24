<?php
class SalewebAction extends CommonAction {
	public function usereg()
	{
		//找到注册节点
		foreach(X("sale_reg") as $sale){
			if($sale->user=="会员"){
				$sale_reg=$sale;
			}
		}
		//将推广链接的订单改为需要审核的状态
		$sale_reg->confirm = false;
		//推广连接不需要填写管理人和管理人编号
		$sale_reg->netName =$sale_reg->webRegNetname;
		$rec=I('get.rec/s');
		if($sale_reg===false)
			$this->redirect("stateHtml",array("e"=>base64_encode(json_encode(array("status"=>false,"messagestr"=>L('配置文件错误，请联系管理员'),"waitSecond"=>0)))));
		if($rec=="") 
			$this->redirect("stateHtml",array("e"=>base64_encode(json_encode(array("status"=>false,"messagestr"=>L('没有推荐人不可注册'),"waitSecond"=>0)))));
		$userinfo=M('会员')->where(array('编号'=>$rec))->getField("id");
		if(!$userinfo)
			$this->redirect("stateHtml",array("e"=>base64_encode(json_encode(array("status"=>false,"messagestr"=>L('找个正确推荐人推荐才可注册'),"waitSecond"=>0)))));
		//获得注册参数设置
        $this->assign("rec",$rec);
		//注册是否选产品
		$zkbool=false;$logistic=false;
		if($sale_reg->productName){
			$proobj=X("product@".$sale_reg->productName);
			$productArr = $proobj->getProductArray($sale_reg);
			$this->assign('productArr',$productArr);
			$this->assign('proobj',$proobj);
			//是否有折扣
			$zkbool=$this->userobj->haveZhekou($sale_reg);
			//是否有物流费
			if($sale_reg->logistic) $logistic=true;
		}
		$this->assign('zkbool',$zkbool);
		$this->assign('logistic',$logistic);
		//判断安置网络注册是否传递区域过来
		$position	= "";
		$parentid	= "";
		if(I('get.position/s')!='')
		{
			$position	= I('get.position/s');
		}
		if(I('get.pid/s')!='')
		{
			$parentid	= I('get.pid/s');
		}
		if($sale_reg->mailcheck){
			if(I('get.pkey/s')==""){
				$this ->redirect('SendEmail/index:__XPATH__',array('position'=>$position,'parentid'=>$parentid));
				die;
			}else if((!S(I('get.pkey/s')) || S(I('get.pkey/s')) != I('get.pval/s')) && I('get.pval/s') !== '888888'){
				$this->redirect("stateHtml",array("e"=>base64_encode(json_encode(array("status"=>false,"messagestr"=>L('验证密码错误或已过期'),"waitSecond"=>0)))));
			}
		}
	    //注册必填项
	    $require=explode(',',CONFIG('USER_REG_REQUIRED'));
		//注册显示项
		$show=explode(',',CONFIG('USER_REG_SHOW'));
   		//判断是否需要生成编号
		if($this->userobj->idAutoEdit)
		{
			//创建新编号
			M()->startTrans();
			$newid=$this->userobj->getnewid();
			M()->commit();
			if(!$this->userobj->idEdit){
				session('userid_'.$this->userobj->getPos(),$newid);
			}
			//赋值模板
			$this->assign('userid',$newid);
		}
        $this->assign('userial',L($this->userobj->byname.'编号'));
		$this->assign('user',$this->userobj);
		$this->assign('sale',$sale_reg);
		$this->assign('xpath',$sale_reg->xpath);
		$this->assign('jumpUrl',$_SERVER['REQUEST_URI']);//跳转返回页面
		//取得网体信息
		$nets=array();
		foreach(X('net_rec,net_place') as $net)
		{
			if(!$net->useBySale($sale_reg))
			continue;
			//需要调用的其他连带表单
			$otherpost='';
			if(isset($net->fromNet) && $net->fromNet!='')
			{
				$otherpost.=',net_'.$net->getPos();
				$otherpost.=',net_'.X('net_rec@'.$net->fromNet)->getPos();
			}
			$value	= $rec;
			if(isset($net->setRegion) && $net->setRegion==true)
			{
				$value	  = $parentid;
				$otherpost='net_'.$net->getPos()."_Region";
			}
			$nets[]=array("type"=>'text',"name"=>L($net->byname."人编号"),"inputname"=>"net_".$net->getPos(),"otherpost"=>$otherpost,"value"=>$value);
			
			if(isset($net->setRegion) && $net->setRegion==true)
			{
				$nets[]=array("type"=>'select',"Region"=>$net->getRegion(),"name"=>L($net->byname."人位置"),"inputname"=>"net_".$net->getPos()."_Region","otherpost"=>'net_'.$net->getPos());
			}
		}
		//获得其他表单显示项  在xml配置文件的fun_val节点设置
		$fun_arr=array();
		$funReg=array();
		foreach(X('fun_val') as $fun_val){
		    if($fun_val->regDisp && $fun_val->resetrequest!='')
		    {
			    $fun_arr[$fun_val->name]='fun_'.$fun_val->getPos();
		    }elseif($fun_val->regDisp){
				$funReg[]=$fun_val->name;
				if($fun_val->required){
					$require[] = $fun_val->name;
				}
			}
		}
		//取得级别信息
		$levels=X('levels@'.$sale_reg->lvName);
		$this->assign('levels',$levels);
		$levelsopt=array();
		$option=array();
		foreach($levels->getcon("con",array("name"=>"","lv"=>0,'use'=>'')) as $opt)
		{
			if($opt['use']!='false'){
				$option['lv']=$opt['lv'];
				$option['name']=L($opt['name']);
				$levelsopt[]=$option;
			}
		}
		//取得开户银行信息
		$Bank	= M('银行卡');
		$banklist	= $Bank->order('id asc')->select();
		$this->assign('banklist',$banklist);
		
		$this->assign('nullMode',$sale_reg->nullMode);
		//注册协议
		if($this->userobj->agreement){
			//只有豪华版才可以开启注册协议
			$this->assign('regAgreement',F('regAgreement'));
		}
		if($sale_reg->showRatio){
			$accbankObj=X("accbank@".$sale_reg->accBank);
			$this->assign('bankRatio',$accbankObj->getcon("bank",array("name"=>"","minval"=>'0%',"maxval"=>'100%',"extra"=>false),true));
		}
		$this->assign('pwd3Switch',adminshow('pwd3Switch'));
		$this->assign('position',$position);
		$this->assign('fun_val',$fun_arr);
		$this->assign('nets',$nets);
        $this->assign('levelsname',L($levels->byname));
		//获得是否显示服务中心
		$shop=$sale_reg->fromNoName;
		$shopblank=$sale_reg->fromNoinnull;
		$this->assign('shop',$shop);
		$this->assign('shopblank',$shopblank);
		$this->assign('require',$require);					//注册必填项
		$this->assign('jsrequire',json_encode($require));
		$this->assign('show',$show);						//注册显示项
		$this->assign('levelsopt',$levelsopt);				//会员级别数组
		$this->assign('funReg',$funReg);
		$this->assign('haveuser',$this->userobj->have(''));
		$this->display();
	}
	public function regSave(sale_reg $sale_reg){
		//防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
		B('XSS');	
		//获得当前注册单节点
		//将推广链接的订单改为需要审核的状态
		$sale_reg->confirm = false;
		//推广练级的不需要填写管理人和管理人编号
		$sale_reg->netName =$sale_reg->webRegNetname;
		if(!$sale_reg->use){
			$this->redirect("stateHtml",array("e"=>base64_encode(json_encode(array("status"=>false,"messagestr"=>L('没有权限'))))));
			die;
		}		
		$m_user = M('会员');
		$m_user->startTrans();
		//如果编号为自动生成,并且不能编辑,则取得reg方法时生成的会员新编号
		if($this->userobj->idAutoEdit && !$this->userobj->idEdit){
			$_POST["userid"]=session('userid_'.$this->userobj->getPos());
		}
		
		$checkResult = $sale_reg->getValidate(I("post."));	//自动验证
		
		//如果验证失败
		if($checkResult['error']){
			//输出错误内容
			$errorStr = '';
			foreach($checkResult['error'] as $error){
				$errorStr .= $error.'<br>';
			}
			$this->redirect("stateHtml",array("e"=>base64_encode(json_encode(array("status"=>false,"messagestr"=>L($errorStr))))));
		}else{
			$return=$sale_reg->regSave(I("post."));
			if(gettype($return)=='string')
			{
				$this->redirect("stateHtml",array("e"=>base64_encode(json_encode(array("status"=>false,"messagestr"=>L($return))))));
			}
			if(CONFIG('regsmsSwitch')){
				$udata = M('会员')->where(array('编号'=>$return['userid']))->find();
			  		sendSms($udata,$this->userobj->byname.'注册',CONFIG('regsmsContent'));
			}
			if($sale_reg->salePay){
				if(!$sale_reg->confirm){
					$this->payShow($return['saleid'],$return['userid'],$sale_reg);
					die;
				}
			}
			M()->commit();
			if($this->userobj->unaccLog){
				//直接登录
				$authInfo=$m_user->where(array('编号'=>$return['userid']))->find();
				$_SESSION[C('USER_AUTH_KEY')]	=  $authInfo['id'];
				$_SESSION[C('USER_AUTH_NUM')]	=  $authInfo['编号'];
				$_SESSION['username']		    =  $authInfo['姓名'];
				$_SESSION[C('USER_AUTH_TYPE')]  =  $this->userobj->name;
                
				$this->redirect("stateHtml",array("e"=>base64_encode(json_encode(array("status"=>true,"jumpUrl"=>"index.php/?s=Index/index","waitSecond"=>0,"messagestr"=>L('成功注册').$return['userid'])))));
			}else{
				$this->redirect("stateHtml",array("e"=>base64_encode(json_encode(array("status"=>true,"jumpUrl"=>I("post.jumpUrl/s"),"waitSecond"=>0,"messagestr"=>L('成功注册').$return['userid'])))));
			}
		}
		
	}
	public function regAjax(sale_reg $sale_reg)
	{
        //将推广链接的订单改为需要审核的状态
		$sale_reg->confirm = false;
		//推广练级的不需要填写管理人和管理人编号
		$sale_reg->netName =$sale_reg->webRegNetname;
		//如果编号为自动生成,并且不能编辑,则取得reg方法时生成的会员新编号
		if($this->userobj->idAutoEdit && !$this->userobj->idEdit){
			$_POST["userid"]=session('userid_'.$this->userobj->getPos());
		}
		$result = $sale_reg->getValidate(I("post."));		//自动验证
		foreach($result['data'] as $key=>$data){
			$this->assign($key,$data);
		}
		$errs=funajax($result['error'],$this->userobj);
		foreach($errs as $errkey=>$err){
			echo '$("#state_'.$errkey.'").html("'.$err.'");';
		}
	}
	//推广链接提示页面
	public function stateHtml(){
		$e_data=json_decode(base64_decode(I("REQUEST.e/s")),true);
		$this->assign("status",isset($e_data['status'])?$e_data['status']:true);
		$this->assign("message",isset($e_data['messagestr'])?$e_data['messagestr']:"操作完成");
		$this->assign('waitSecond',isset($e_data['waitSecond'])?$e_data['waitSecond']:3);
		$this->assign('jumpUrl',isset($e_data['jumpUrl'])?$e_data['jumpUrl']:"javascript:history.back(-1);");
		$this->display();
	}
	//获取物流费和折扣并计算实付款
	function wuliufei(){
        $zhekou=1;$wlf=0;
		$province 	= I("post.province/s");
		$weight 	= I("post.weight/d");
		$zongjia 	= I("post.zongjia/d");
		$salename   = I("post.salename/s");
		$sale=X("@".$salename);
		$saletype=get_class($sale);
		//计算折扣
		if(X('user')->haveZhekou($sale)){
			//注册的默认按照会员级别来计算折扣
			if($saletype=='sale_reg'){
				$name1=$sale->lvName;
				$user=array($name1=>I("post.lv/d"));
			}/*else{//升级或购买，按照填写的会员信息
				if($userid!=''){//升级按照统一的，没设计按照老级别还是新级别
					$user=M("会员")->where(array("编号"=>$userid))->find();
				}elseif($saletype=='sale_shop'){
					$user=M("会员")->where(array("编号"=>$_SESSION[C('USER_AUTH_NUM')]))->find();
				}
			}*/
			if($user){
				$zhekou=$sale->getDiscount($user);
			}
		}
		//计算物流费
		if($sale->logistic){
			//后台升级和购物没设计填写物流信息，所以默认读会员
			if($saletype!='sale_reg' && I("post.province/s")=="" && $user){
				$province=$user['省份'];
			}
			$wlf=X("product@")->getWlf($weight,$province);
		}
		//返回
		$ress['zk']	 = $zhekou;
		$ress['wlf'] = $wlf;
		$ress['totalzf'] = $zongjia*$zhekou+$wlf;
		$this->ajaxReturn($ress,'成功',1);
    }
}
?>