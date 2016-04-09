<?php
defined('APP_NAME') || die(L('不要非法操作哦'));
class SaleAction extends CommonAction {
	//用户注册
	public function reg(sale_reg $sale_reg){
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
		if(I("get.position/s")!='')
		{
			$position	= I("get.position/s");
		}
		if(I("get.pid/s")!='')
		{
			$parentid	= I("get.pid/s");
		}
		//是否验证邮件
		if($sale_reg->mailcheck){
			if(I("post.pkey/s")==''){
				$this ->redirect('SendEmail/index:__XPATH__',array('position'=>$position,'pid'=>$parentid));
					die;
			}else if((!S(I("post.pkey/s")) || S(I("post.pkey/s")) != I("post.pval/s"))){
				$this ->error(L('验证密码错误或已过期'));
			}
		}
		//注册必填项
	    $require=explode(',',CONFIG('USER_REG_REQUIRED'));
		//注册显示项
		$show=explode(',',CONFIG('USER_REG_SHOW'));
		//获得是否显示服务中心
   	    //判断是否需要生成编号
		if($this->userobj->idAutoEdit)
		{
			//创建新编号
			M()->startTrans();
			$newid=$this->userobj->getnewid();
			M()->commit();
			if(!$this->userobj->idEdit){
				session('userid_reg',$newid);
			}
			//赋值模板
			$this->assign('userid',$newid);
		}
        $this->assign('userial',L($this->userobj->byname.'编号'));
		$this->assign('user',$this->userobj);
		$this->assign('sale',$sale_reg);
		$this->assign('alert',$sale_reg->alert);
		//取得网体信息
		$nets=array();
		foreach(X('net_rec,net_place') as $net)
		{
			//如果配置regDisp为false 跳过
			if(!$net->useBySale($sale_reg)) continue;
			//需要调用的其他连带表单
			$otherpost='';
			if(isset($net->fromNet) && $net->fromNet!='')
			{
				$otherpost.=',net_'.$net->getPos();
				$otherpost.=',net_'.X('net_rec@'.$net->fromNet)->getPos();
			}
			$value='';
			/*
			如果设置了dispWhere就不在默认推荐人，因为很有可能是服务中心为其他会员注册
			设置默认就不合适了
			*/
			
			if(get_class($net)=='net_rec' && $sale_reg->dispWhere==''){
				$value	= $this->userinfo['编号'];
			}
			//$position	= $net->getRegion();
			if(isset($net->setRegion) && $net->setRegion==true)
			{
				if($parentid != ''){
					$value	  = $parentid;
				}
				$otherpost='net_'.$net->getPos()."_Region";
			}
			if((get_class($net)=='net_rec' && $net->setNowUser == false) || get_class($net)=='net_place')
			{
				$nets[]=array("type"=>'text',"name"=>L($net->byname."人编号"),"inputname"=>"net_".$net->getPos(),"otherpost"=>$otherpost,"value"=>$value,'require'=>$net->mustUp);
			}
			if(isset($net->setRegion) && $net->setRegion==true)
			{
				$RegionSet=array();
				foreach($net->getRegion() as $Region)
				{
					//是否可以显示这个region
					$regiondisp = true;
					//默认有where则关闭掉
					if(isset($Region['where']) && $Region['where']!='')
					{
						$regiondisp=false;
						//如果存在通过网络图点击得到的特定会员编号
						if($value)
						{
							//找到这个会员
							$upuser = M('会员')->where(array('编号'=>$value))->find();
							//对显示区域的where做判断
							if($Region['where']!="{myrec}"){
								if($upuser && transform($Region['where'],$upuser))
								{
									//判断成功.这个区也可以显示
									$regiondisp = true;
								}
							}
						}
					}
					if($regiondisp)
					{
						$RegionSet[]=$Region;
					}
				}
				$nets[]=array("type"=>'select',"Region"=>$RegionSet,"name"=>L($net->byname."人位置"),"inputname"=>"net_".$net->getPos()."_Region","otherpost"=>'net_'.$net->getPos(),'require'=>$net->mustUp);
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
		$this->assign('levelsopt',$sale_reg->getLvOption());//会员级别数组
			
		//取得开户银行信息
		$Bank	= M('银行卡');
		$banklist	= $Bank->order('id asc')->select();
		$this->assign('banklist',$banklist);
		//注册协议
        if(X("user")->agreement || adminshow('agreement')){
            $this->assign('regAgreement',F('regAgreement'));
        }
		if($sale_reg->showRatio){
			$accbankObj=X("accbank@".$sale_reg->accBank);
			$this->assign('bankRatio',$accbankObj->getcon("bank",array("name"=>"","minval"=>'0%',"maxval"=>'100%',"extra"=>false),true));
		}
		$this->assign('pwd3Switch',adminshow('pwd3Switch'));
		$this->assign('position',$position);				//位置
		$this->assign('fun_val',$fun_arr);					//附加表单
		$this->assign('nets',$nets);						//网体信息
        $this->assign('levelsname',L($levels->byname));		//级别名称
		$shop=$sale_reg->fromNoName;
		$shopblank=$sale_reg->fromNoinnull;
		$this->assign('shop',$shop);						//专卖店
		$this->assign('shopblank',$shopblank);
		$this->assign('require',$require);					//注册必填项
		$this->assign('jsrequire',json_encode($require));
		$this->assign('show',$show);						//注册显示项
		
		$this->assign('funReg',$funReg);
		$this->assign('haveuser',CONFIG('HAVEUSER'));			//是否为第一个会员
		$this->display($sale_reg->template);
	}
	//用户注册完成
	public function regSave(sale_reg $sale_reg){
		//防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
		B('XSS');
		set_time_limit(1800);
		ini_set('memory_limit','500M');
		//获得当前注册单节点
		if(!$sale_reg->use){
			echo "<script>alert('没有权限');</script>";
			die;
		}		
		$m_user = M('会员');
		M()->startTrans();
		//如果编号为自动生成,并且不能编辑,则取得reg方法时生成的会员新编号
		if($this->userobj->idAutoEdit && !$this->userobj->idEdit){
			$_POST["userid"]=session('userid_reg');
		}
		$checkResult = $sale_reg->getValidate(I('post.'));	//自动验证
		//如果验证失败
		if($checkResult['error']){
			//输出错误内容
			$errorStr = '';
			foreach($checkResult['error'] as $error){
				$errorStr .= $error.'<br>';
			}
			$this->error($errorStr);
		}
		//注册过程中产生错误
		$return=$sale_reg->regSave(I('post.'));
		if(gettype($return)=='string')
		{
			$this->error($return);
		}
		M()->commit();
		M()->startTrans();
		$udata=M("会员")->where(array("编号"=>$return['userid']))->find();
		//注册短信发送
		$sendresult=sendSms("reg",$return['userid'],$this->userobj->byname.'注册',$udata);
		//注册邮件发送
		if(CONFIG('regmailSwitch')){
			sendMail($udata,$this->userobj->byname.'注册',CONFIG('regmailContent'));
		}
		CONFIG('HAVEUSER') == false && CONFIG('HAVEUSER',true);
		M()->commit();
		$this->redirect("/User/Sale/recipt:".__XPATH__."/newuserid/".$return['userid']);
	}
    //注册回执页
    function recipt(sale_reg $sale_reg)
    {
        $reciptarr = array();
        if(adminshow('admin_receipt_myreg'))
            $reciptarr['我的会员订单']=__APP__.'/User/User/myreg';
        if(adminshow('admin_receipt_acclist'))
            $reciptarr['会员订单审核']=__APP__.'/User/Sale/acclist';
        if(adminshow('admin_receipt_net_rec')){
            foreach(X('net_rec') as $rec)
            {
                if($sale_reg->netName=='all' || in_array($rec->name,explode(',',$sale_reg->netName)))
                    $reciptarr[$rec->name.'网络']=__APP__.'/User/Net/disp:'.$rec->xpath;
            }
        }
        if(adminshow('admin_receipt_net_place')){
            foreach(X('net_place') as $place)
            {
                if($sale_reg->netName=='all' || in_array($place->name,explode(',',$sale_reg->netName)))
                    $reciptarr[$place->name.'网络']=__APP__.'/User/Net/disp:'.$rec->xpath;
            }
        }
        $this->assign('newuserid',I('get.newuserid/s'));
        $this->assign('recipts',$reciptarr);
        $this->display();
    }
    function showProtaocan(){
      //查看详情
      	$list=M('产品套餐')->where(array("产品id"=>I('get.proid/d')))->select();
        $proinfo = M('产品')->where(array("id"=>I('get.proid/d')))->find();
        $this->assign('proinfo',$proinfo); 
        $this->assign('list',$list); 
		$this->display();
    }
	//产品简介
	public function showProinfo(){
        if(I('get.name/f')==='obj'){
           $list=M('产品套餐')->where(array("id"=>I('get.id/d')))->find();
        }else{
          $list=M('产品')->where(array("id"=>I('get.id/d')))->find();
        }
        $this->assign('list',$list); 
		$this->display();
	}
	//用户注册Ajax表单验证
	public function regAjax(sale_reg $sale_reg){
		//如果编号为自动生成,并且不能编辑,则取得reg方法时生成的会员新编号
		if($this->userobj->idAutoEdit && !$this->userobj->idEdit){
			$_POST["userid"]=session('userid_reg');
		}
		$result = $sale_reg->getValidate(I('post.'));		//自动验证
		foreach($result['data'] as $key=>$data){
			$this->assign($key,$data);
		}
		$errs=funajax($result['error'],$this->userobj);
		$this->assign('errs',$errs);
		$this->display();
	}
  
	//重复消费
	public function buy(sale_buy $sale_buy){
		if(!$sale_buy->use)
		{
			echo "<script>alert('没有权限');</script>";die;
		}
		//是否选产品
		$zkbool=false;$logistic=false;
		if($sale_buy->productName){
			$proobj=X("product@".$sale_buy->productName);
			$productArr =$proobj->getProductArray($sale_buy);
			$this->assign('productArr',$productArr);
			$this->assign('proobj',$proobj);
			//是否有折扣
			$zkbool=$this->userobj->haveZhekou($sale_buy);
			//是否有物流费
			if($sale_buy->logistic) $logistic=true;
		}
		$this->assign('zkbool',$zkbool);
		$this->assign('logistic',$logistic);
		if($sale_buy->showRatio){
			$accbankObj=X("accbank@".$sale_buy->accBank);
			$this->assign('bankRatio',$accbankObj->getcon("bank",array("name"=>"","minval"=>'0%',"maxval"=>'100%',"extra"=>false),true));
		}
		$this->assign('sale',$sale_buy);
		$this->assign('shop',$sale_buy->fromNoName);
		$this->assign('shopblank',$sale_buy->fromNoinnull);
		$accbanks=X('accbank@'.$sale_buy->accBank)->getcon("bank",array("name"=>""));
		$banks=array();
		foreach($accbanks as $accbank){
			$banks[] = X('fun_bank@'.$accbank['name']);
		}
		$this->assign('banks',$banks);
		//购物协议
        if(adminshow('agreement')){
            $this->assign('Buy_agreement',F('Buy_agreement'));
        }
		$this->display($sale_buy->template);
	}
	//重复消费AJAX验证
	public function buyAjax(sale_buy $sale_buy){
		$postname=I('post.postname/s');//='net_6,net_7'
		$result = $sale_buy->getValidate(I('post.'));		//自动验证
		foreach($result['data'] as $key=>$data){
			$this->assign($key,$data);
		}
		$errs=funajax($result['error'],$this->userobj);
		$this->assign('errs',$errs);
		$this->display('ajax:sale:buyajax');
	}	
	//重复消费完成
	public function buySave(sale_buy $sale_buy){
	//防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
		set_time_limit(1800);
		ini_set('memory_limit','500M');
		M()->startTrans();
		if(!$sale_buy->use){
			echo "<script>alert('没有权限');</script>";
			die;
		}
		if($sale_buy->onlyMsg!=''){
			$have=M("报单")->where(array("编号"=>$this->userinfo['编号'],"报单类别"=>$sale_buy->name))->find();
			if($have){
				$this->error($sale_buy->onlyMsg);
			}
		}
		if($sale_buy->extra && (I('post.country/s')=='' || I('post.province/s')=='' || I('post.city/s')=='' || I('post.county/s')=='' || I('post.town/s')=='' || I('post.reciver/s')=='' || I('post.address/s')=='' || I('post.mobile/s')=='')){
			$this->error(L("请完善收货信息"));
		}
		$checkResult = $sale_buy->getValidate(I('post.'));   //自动验证
		if($checkResult['error']){
			$errorStr = '';
			foreach($checkResult['error'] as $error){
				$errorStr .= $error . '<br/>';
			}
			$this->error($errorStr);
		}else{   
			$rswhere=$sale_buy->iswhere($this->userinfo);
			if($rswhere !== true){
				$this->error($rswhere);
			}
			//$_POST['userid']=$this->userinfo['编号'];
			$return = $sale_buy->buy(I('post.'));
			if(gettype($return)=='string')
			{
				$this->error($return);
			}
			M()->commit();
			$userMenuPower = $this->userobj->getatt('userMenuPower');
			if(!$userMenuPower && !in_array('Sale-mysale',$userMenuPower)){
				$this->success('订购成功',__URL__.'/productmysale');
			}else{
				$this->success(L('订购成功'),__URL__.'/mysale');
			}
		}
	}
	
	//会员升级
	public function up(sale_up $sale_up){
		if($this->userinfo['状态'] != '有效')
		{
			$this->error(L('状态无效，不能操作'));
		}
		//是否选产品
		$zkbool=false;$logistic=false;
		if($sale_up->productName){
			$proobj=X("product@".$sale_up->productName);
			$productArr = $proobj->getProductArray($sale_up);
			$this->assign('productArr',$productArr);
			$this->assign('proobj',$proobj);
			//是否有折扣
			$zkbool=$this->userobj->haveZhekou($sale_up);
			//是否有物流费
			if($sale_up->logistic) $logistic=true;
		}
		$this->assign('zkbool',$zkbool);
		$this->assign('logistic',$logistic);

		$shop=$sale_up->fromNoName;
		$shopblank=$sale_up->fromNoinnull;

		$levels=X('levels@'.$sale_up->lvName);
		$lv		= $this->userinfo[$sale_up->lvName];
		$levelsopts=$sale_up->getLvOption($lv);
		$this->assign('levelsopts',$levelsopts);
		$area=$sale_up->getLvArea();
		$this->assign('area'      ,$area);
		if(count($levelsopts)==0){
			$this->error(L('您已升至最高级别'),"__APP__/User/Index/index");
		}
		if($sale_up->showRatio){
			$accbankObj=X("accbank@".$sale_up->accBank);
			$this->assign('bankRatio',$accbankObj->getcon("bank",array("name"=>"","minval"=>'0%',"maxval"=>'100%',"extra"=>false),true));
		}
		$this->assign('sale',$sale_up);
		$this->assign('levels',$levels);
		$this->assign('shop',$shop);
		$this->assign('shopblank',$shopblank);
		//区域代理
		$userinfo=$this->userinfo;
		if(count($area)>0){
			//初始化
			if(!isset($area['city'])) $area['city']='';
			if(!isset($area['county'])) $area['county']='';
			if(!isset($area['town'])) $area['town']='';
			if($userinfo['代理国家']=='') $userinfo['代理国家']=$userinfo['国家'];
			if($userinfo['代理省份']=='') $userinfo['代理省份']=$userinfo['省份'];
			if($userinfo['代理城市']=='') $userinfo['代理城市']=$userinfo['城市'];
			if($userinfo['代理地区']=='') $userinfo['代理地区']=$userinfo['地区'];
			if($userinfo['代理街道']=='') $userinfo['代理街道']=$userinfo['街道'];
		}
		$this->display($sale_up->template);
	}
	//升级完成
	public function upSave(sale_up $sale_up){
		if($this->userinfo['状态'] != '有效')
		{
			$this->error(L('状态无效，不能操作'));
		}		
	   //防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
		 B('XSS');
		set_time_limit(1800);
		ini_set('memory_limit','500M');
		M()->startTrans();
        if($sale_up->lockMe==true){
           $_POST['userid'] = $this->userinfo['编号'];
        }	
		//判断物流信息
		if($sale_up->extra && (I('post.reciver/s')=='' || I('post.mobile/s')=='' || I('post.address/s')=='' || I('post.country/s')=='' || I('post.province/s')=='' || I('post.city/s')=='' || I('post.county/s')=='' || I('post.town/s')==''))
        {
			$this->error(L("请完善物流信息"));
		}
		$levels=X('levels@'.$sale_up->lvName);
		$checkResult = $sale_up->getValidate(I("post."));
		if($checkResult['error']){
			$errorStr = '';
			foreach($checkResult['error'] as $error){
				$errorStr .= $error . '<br/>';
			}
			$this->error(L($errorStr));
		}
		$return = $sale_up->upSave(I("post."));
		if(gettype($return)=='string')
		{
			$this->error(L($return));
		}
		M()->commit();
		$userMenuPower = $this->userobj->getatt('userMenuPower');
		if(!$userMenuPower && !in_array('Sale-mysale',$userMenuPower)){
			$this->success(L('操作成功'));
		}else{
			$this->success(L('操作成功'),__URL__.'/mysale');
		}
	}
	//会员升级ajax验证
	public function upAjax(sale_up $sale_up){
		$userid			= I("get.userid/s");
		$levels			= X('levels@'.$sale_up->lvName);
		$levelsopt		= $levels->getcon("con",array("name"=>"","lv"=>0));
		$m				= M('会员');
		$lv			    = $m->where(array("编号"=>"$userid"))->getField($levels->name);
		$levelsopts		= array();
		if($lv)
		{
			$levelsopts		= array();
			$levelsopts=$sale_up->getLvOption($lv);
			$this->ajaxReturn($levelsopts,L('成功'),1);
		}
		else
		{
			$this->ajaxReturn('',L('失败'),0);
		}
	}
	
    //注册订单审核
	public function acclist(){
	    $name=$this->userobj->name;
		$net_rec_name	= X("net_rec@")->name;
		$useracc = '';
		foreach(X('sale_*') as $sale){
			if($sale->useracc && $sale->user != 'admin'){
				$useracc .= "(a.报单类别='{$sale->name}' ";
				//谁能看到此订单
				if($sale->accview!=''){
					$useracc.=" and (";
					$accuser=explode(",",$sale->accview);
					foreach($accuser as $uname){
						$useracc.=("b.".$uname."='".$this->userinfo['编号']."' or ");
					}
					$useracc=trim($useracc,'or ');
					$useracc.=")";
				}
				$useracc .= ") or";
			}
		}
		if($useracc == ''){
			$this->error(L('没有操作权限'));
		}else{
			$useracc = '('.trim($useracc,'or ').')';
		}
		$list = new TableListAction('报单');
		$list->table('dms_报单 a');
        $list->join('left join '.C('DB_PREFIX') . $name ." as b on b.编号=a.编号")->where($useracc." and a.报单状态 = '未确认' and  是否推广链接='0'");
        $list->field("a.id,b.编号,b.注册日期,b.姓名,b.{$name}级别,b.{$net_rec_name}_上级编号,b.注册人编号,b.服务中心编号,a.报单状态,a.报单金额,a.服务中心编号,a.购买日期,a.购物金额,a.升级数据,a.报单类别");
        $list->title="订单审核列表";            // 列表标题
        $list->pagenum=15;                   // 每页显示数量  默认20
        $list->order  ="a.购买日期 desc";
        $list->addshow(L('编号')     ,array("row"=>"[编号]")); 
        //L('注册日期')=>array("row"=>"[注册日期]","format"=>"time"),
        $list->addshow(L('购买日期'),array("row"=>"[购买日期]","format"=>"time"));
        $list->addshow(L('姓名')          ,array("row"=>"[姓名]"));
        $list->addshow(L('级别')         ,array("row"=>array(array(&$this,"printUserLevel"),"[{$name}级别]","{$name}级别")));
		$list->addshow(L('报单金额')     ,array("row"=>"[报单金额]","searchMode"=>"num"));
		if($this->userobj->haveProduct()){
			$list->addshow(L('购物金额')           ,array("row"=>"[购物金额]","searchMode"=>"num"));
		}
		//$list->addshow(L('升级数据') ,array("row"=>"[升级数据]"));
		$list->addshow(L('报单状态')        ,array("row"=>"[报单状态]"));
		$list->addshow(L('报单类别'),array("row"=>"[报单类别]"));
		$list->addshow(L('操作')     ,array("row"=>array(array(&$this,"getsale1"),"[报单类别]","[编号]","[id]"),));
		$data = $list->getData(); 
		$this ->assign('name',X('user')->byname);
		$this->assign('data',$data);
		$this->display();
	}
	 //推广链接订单审核
	public function tj_acclist(){
	    $name=$this->userobj->name;
		$net_rec_name	= X("net_rec@")->name;
		$useracc = '';
		foreach(X('sale_*') as $sale){
			if($sale->useracc && $sale->user != 'admin'){
				$useracc .= "(a.报单类别='{$sale->name}' ";
				//谁能看到此订单
				if($sale->accview!=''){
					$useracc.=" and (";
					$accuser=explode(",",$sale->accview);
					foreach($accuser as $uname){
						$useracc.=("b.".$uname."='".$this->userinfo['编号']."' or ");
					}
					$useracc=trim($useracc,'or ');
					$useracc.=")";
				}
				$useracc .= ") or";
			}
		}
		if($useracc == ''){
			$this->error(L('没有操作权限'));
		}else{
			$useracc = '('.trim($useracc,'or ').')';
		}
		$list = new TableListAction('报单');
		$list->table('dms_报单 a');
        $list->join('left join '.C('DB_PREFIX') . $name ." as b on b.编号=a.编号")->where($useracc." and a.报单状态 = '未确认' and 是否推广链接='1'");
        $list->field("a.id,b.编号,b.注册日期,b.姓名,b.{$name}级别,b.{$net_rec_name}_上级编号,b.注册人编号,b.服务中心编号,a.报单状态,a.报单金额,a.服务中心编号,a.购买日期,a.购物金额,a.升级数据,a.报单类别");
        $list->title="订单审核列表";            // 列表标题
        $list->pagenum=15;                   // 每页显示数量  默认20
        $list->order  ="a.购买日期 desc";
        $list->addshow(L('报单类别')     ,array("row"=>"[编号]"));
        //L('注册日期')=>array("row"=>"[注册日期]","format"=>"time"),
        $list->addshow(L('购买日期'),array("row"=>"[购买日期]","format"=>"time"));
        $list->addshow(L('姓名')          ,array("row"=>"[姓名]"));
        $list->addshow(L('级别')         ,array("row"=>array(array(&$this,"printUserLevel"),"[{$name}级别]"),));
		$list->addshow(L('报单金额')     ,array("row"=>"[报单金额]","searchMode"=>"num"));
		if($this->userobj->haveProduct())
		$list->addshow(L('购物金额')           ,array("row"=>"[购物金额]","searchMode"=>"num"));
		$list->addshow(L('报单状态')        ,array("row"=>"[报单状态]"));
		$list->addshow(L('报单类别'),array("row"=>"[报单类别]"));
		$list->addshow(L('操作')     ,array("row"=>array(array(&$this,"getsale1"),"[报单类别]","[编号]","[id]"),));
        $data = $list->getData(); 
		$this ->assign('name',$this->userobj->byname);
        $this->assign('data',$data);
		$this->display();
	}
	
	//l008中的审核时用的审核
	public function getsale1($salename,$userid,$id){ 
		//判断是否开启转账给未激活(状态=无效)会员
		if(adminshow(zhuanzhang))
		{
			return "<a href='__URL__/tj_accok/id/{$id}/userid/{$userid}'>".L('激活')."</a>";
		}else{
			return "<a href='__URL__/del/id/{$id}'>".L('删除')."</a>&nbsp;&nbsp;&nbsp;<a href='__URL__/tj_accok/id/{$id}/userid/{$userid}'>".L('激活')."</a>";
		}
	}
	public function getsale($salename,$userid,$id)
	{ 
		return "<a href='__URL__/accok/id/{$id}/userid/{$userid}'>确认</a>";
	}
    //订单审核确认 
	public function accok(){
		set_time_limit(1800);
        $saleid   = I("REQUEST.id/d");
		$userid   = I("REQUEST.userid/s");
		if($saleid=='' || $userid=='')
		{
			$this->error(L('参数错误'));
		}
		$sale_where['id']=$saleid;
		
		M()->startTrans();
		M('会员')->lock(true)->find();
		$sdata = M('报单')->lock(true)->where($sale_where)->find();
		if(!$sdata)
		{
			$this->error(L('订单未找到'));
		}
		if($sdata['报单状态']!='未确认'){
		    $this->error(L('订单状态错误'));
		}
		$sale  = X('sale_*@'.$sdata['报单类别']);
		if(!$sale->useracc)
		{
			$this->error(L("此订单不允许{$this->userobj->byname}审核"));
		}
		//由于要执行审核，需要强制改变订单的确认状态
		//审核 扣款
		$return = $sale->accok($sdata);
		if($return !== true){
			M()->rollback();
			$this->error($return);
		}
		M()->commit();
		M()->startTrans();
		$udata=M("会员")->where(array("编号"=>$sdata['编号']))->find();
		//审核短信发送
		sendSms("accok",$sdata['编号'],$sale->byname.'审核',$sdata);
		//审核发送邮件
		if(get_class($sale) == 'sale_reg' && CONFIG('exammailSwitch')){
			sendMail($udata,$this->userobj->byname.'审核',CONFIG('exammailContent'));
		}
		M()->commit();
		$this->success(L('操作完成'));
	}
	//推广链接订单审核
	public function tj_accok(){
		set_time_limit(1800);
		$saleid   = I("REQUEST.id/d");
		$userid   = I("REQUEST.userid/s");
		if($saleid=='' || $userid=='')
		{
			$this->error(L('参数错误'));
		}
		$sale_where['id']=$saleid;
		
		M()->startTrans();
		M('会员')->where('id<0')->lock(true)->find();
		$sdata = M('报单')->lock(true)->where($sale_where)->find();
		if(!$sdata)
		{
			$this->error(L('订单未找到'));
		}
		if($sdata['报单状态']!='未确认'){
		    $this->error(L('订单状态错误'));
		}
		$sale  = X('sale_*@'.$sdata['报单类别']);
		if(!$sale->useracc)
		{
			$this->error(L("此订单不允许{$this->userobj->byname}审核"));
		}
		//由于要执行审核，需要强制改变订单的确认状态
		//审核 扣款
		$return = $sale->accok($sdata);
		if($return !== true){
			M()->rollback();
			$this->error(L($return));
		}
		M()->commit();
		M()->startTrans();
		$udata=M("会员")->where(array("编号"=>$sdata['编号']))->find();
		//注册短信发送
		sendSms("accok",$sdata['编号'],$sale->byname.'审核',$sdata);
		//审核发送邮件
		if(get_class($sale) == 'sale_reg' && CONFIG('exammailSwitch')){
			sendMail($udata,$this->userobj->byname.'审核',CONFIG('exammailContent'));
		}
		M()->commit();
		$this->success(L('操作完成'));
	}
	//我的订单
    function mysale(){
        $list=new TableListAction("报单");
        $list ->where(array("编号"=>$this->userinfo["编号"],"产品"=>0))->order('id desc');
		$list ->addshow( L('购买日期'),array("row"=>'[购买日期]','format'=>'time'));
		$list ->addshow( L('付款日期'), array("row"=>'[到款日期]','format'=>'time'));
		$list ->addshow( L('报单金额'), array("row"=>"[报单金额]","searchMode"=>"num"));
	if(adminshow('bd_pv_head')){
		$list ->addshow( L('报单PV'), array("row"=>"[报单PV]","searchMode"=>"num"));
	}
		$list ->addshow( L('订单类别'), array('row'=>'[报单类别]'));
		$list ->addshow( L('订单状态'), array('row'=>array(array(&$this,"operate"),"[报单状态]","","[编号]","[id]"),));
		
		if($this->userobj->haveProduct())
		{
			$list ->addshow( L('物流状态'), array("row"=>'[物流状态]'));
		}
		$list ->addshow( L('操作'), array("row"=>array(array(&$this,"checkgeted"),"[物流状态]","[id]",$this->userobj->haveProduct())));
        $list ->pagenum=15;
		$data = $list->getData();
        $this->assign('data',$data);
        $this->display();
    }
    //我的产品订单
    function productmysale(){
        $list=new TableListAction("报单");
        $list ->where(array("编号"=>$this->userinfo["编号"],"产品"=>1))->order('id desc');
		$list ->addshow( L('购买日期'),array("row"=>'[购买日期]','format'=>'time'));
		$list ->addshow( L('付款日期'), array("row"=>'[到款日期]','format'=>'time'));
		$list ->addshow( L('报单金额'), array("row"=>"[报单金额]","searchMode"=>"num"));
		if($this->userobj->haveProduct()){
			$list ->addshow( L('购物金额'), array("row"=>"[购物金额]","searchMode"=>"num"));
			$list ->addshow( L('实付款'), array("row"=>"[实付款]","searchMode"=>"num"));
			if(adminshow('sale_pv_head')){
				$list ->addshow( L('购物PV'), array("row"=>"[购物PV]","searchMode"=>"num"));
			}
		}
			if(adminshow('bd_pv_head')){
		$list ->addshow( L('报单PV'), array("row"=>"[报单PV]","searchMode"=>"num"));
	}
		$list ->addshow( L('订单类别'), array('row'=>'[报单类别]'));
		//$list ->addshow( L('订单状态'), array('row'=>array(array(&$this,"operate"),"[报单状态]","[saleid]","[编号]","[id]"),));
		$list ->addshow( L('订单状态'), array('row'=>array(array(&$this,"operate"),"[报单状态]","","[编号]","[id]"),));
		
		if($this->userobj->haveProduct())
		{
			$list ->addshow( L('物流状态'), array("row"=>'[物流状态]'));
		}
		$list ->addshow( L('操作'), array("row"=>array(array(&$this,"checkgeted"),"[物流状态]","[id]",$this->userobj->haveProduct())));
        $list ->pagenum=15;
		$data = $list->getData();
        $this->assign('data',$data);
        $this->display();
       
    }
    //空单申请回填
    public function apply_back(){
    	//查询注册订单显示到页面中
    	$map['报单状态']=array("in","空单,回填");
	    $map['编号']=$this->userinfo['编号'];
		$saleData=M("报单")->where($map)->select();
		if(!$saleData){
			//$this->error(L('没有需要回填的订单'));
		}
		$this->assign('saleData',$saleData);

		//奖金回填方案
		$this->assign("backfill",X("prize_backfill"));
		$list = new TableListAction("报单");
		$list->table("dms_报单 a");
		$list->join("inner join (select * from dms_申请回填 where 编号='".$this->userinfo['编号']."') b on a.id=b.saleid");
        $list ->where(array('a.编号'=>$this->userinfo['编号']))->order("b.id desc");
        $list->field('a.*,b.*');
        $list ->setShow = array(
            L('申请日期')=>array("row"=>"[申请日期]","format"=>"time"),
            L('转正方式')=>array("row"=>"[转正方式]"),
            L('购买日期')=>array("row"=>"[购买日期]","format"=>"time"),
            L('报单类别')=>array("row"=>"[报单类别]"),
            L('报单状态')=>array("row"=>"[报单状态]"),
            L('审核日期')=>array("row"=>"[审核日期]","format"=>"time"),
            L('申请状态')=>array("row"=>"[申请状态]"),
        );
        $list ->addShow(L('操作'),array("row"=>array(array(&$this,"dofun"),"[申请状态]","[id]")));
        $data = $list->getData();
        $this->assign('data',$data);
		$this->display();
	}
	public function dofun($state,$id){
		if($state=='未审核')
			return "<a href='__URL__/cancelapply/id/{$id}'>撤销申请</a>";
		else
			return "已审核";
	}
	public function cancelapply(){
		if(I("get.id/d")<=0){
			$this->error(L('参数错误'));
		}
		M()->startTrans();
		//查询数据
		$applydata=M("申请回填")->where(array("id"=>I("get.id/d")))->lock(true)->find();
		if(isset($applydata['申请状态']) && $applydata['申请状态']!="未审核"){
			$this->error(L("非法操作"));
		}
		M("申请回填")->delete($applydata['id']);
		M()->commit();
		$this->success(L("申请已撤销"));
	}
	public function apply_backsave(){
		if(I("post.__hash__/s")==""){
			$this->error(L('参数错误'));
		}
		M()->startTrans();
		$map['报单状态']=array("in","空单,回填");
	    $map['编号']=$this->userinfo['编号'];
		if(I("post.type/a")){
			foreach(I("post.type/a") as $saleid=>$type){
				if($type){
					//找出订单
					$map['id']=$saleid;
					$saleData=M("报单")->where($map)->lock(true)->find();
					if($saleData['报单状态']!="空单"){
						if($saleData['报单状态']=="回填" && $type=="回填转正"){
							$this->error(date("Y-m-d H:i:s",$saleData['购买日期']).$saleData['报单类别']."L(报单已成为回填单)");
						}
						if($saleData['报单状态']!="回填"){
							$this->error(date("Y-m-d H:i:s",$saleData['购买日期']).$saleData['报单类别']."L(报单已回填完成)");
						}
					}
					//申请记录的状态判断
					$where['转正方式']=$type;
					$where['申请状态']='未审核';
					$where['saleid']=$saleData['id'];
					$where['编号']=$saleData['编号'];
					$applydata=M("申请回填")->where($where)->find();
					if(isset($applydata)){
						$this->error(date("Y-m-d H:i:s",$saleData['购买日期']).$saleData['报单类别']."L(报单已有申请提交等待审核或者已申请过)".$type);
					}
					//保存申请记录
					$data=array(
						"saleid"=>$saleData['id'],
						"编号"=>$this->userinfo['编号'],
						"转正方式"=>$type,
						"申请日期"=>systemTime(),
						"申请状态"=>"未审核"
					);
					M("申请回填")->add($data);
				}else{
					continue;
				}
			}
		}
		M()->commit();
		$this->success(L("申请已提交，请等待审核..."));
	}
	public function checkgeted($status,$id,$haveProduct){
		 if($status=='已发货' && $haveProduct){
			 return "<a href='__URL__/viewMysale/id/{$id}'>" . L('查看') . "</a> <a href='__URL__/confirmget/id/{$id}'>" . L('确认收货') . "</a>";
			 
		 }else{
			 return "<a href='__URL__/viewMysale/id/{$id}'>" . L('查看') . "</a>";
		 }
	}
	public function viewMysale(){
		$saleData = M('报单')->where(array('编号'=>$this->userinfo['编号'],'id'=>I("get.id/d")))->find();
		if($saleData['产品'] == 1){
			$productData = M('产品订单')->where(array('报单id'=>I("get.id/d")))->select();
			
			$this->assign('productData',$productData);
		}
		if($saleData['升级数据']>0){
			$nowlevel = $this->printUserLevel($saleData['升级数据'],'',$saleData['报单类别']);
			$oldlevel = $this->printUserLevel($saleData['old_lv'],'',$saleData['报单类别']);
			$this->assign('nowlevel',$nowlevel);
			$this->assign('oldlevel',$oldlevel);
		}
		$this->assign('haveProduct',$this->userobj->haveProduct());
		$this->assign('saleData',$saleData);
		$this->assign('adminshow',adminshow('sale_pv_head'));
		$this->display();
	}
	public function confirmget(){
		M()->startTrans();
		$model		= M("报单");
		$result		 = $model->where(array('id'=>I("get.id/d"),'编号'=>$this->userinfo['编号']))->save(array('物流状态'=>'已收货','收货日期'=>systemTime()));
		if($result){
			M()->commit();
			$this->success(L("确认收货成功"));
		}else{
			$this->error(L("确认收货失败"));
		}
	}
	
	public function operate($state,$saleid,$userid,$id){
		if($state=='未确认'){
			return '未结算';
		}else{
			return '已结算';
		}
	}
	//删除会员未审核订单
	public function del(){
		$saleid		= I("get.id/d");
		if(!is_numeric($saleid)){
			$this->error(L('参数非法'));
		}
	
		//查询推广链接的订单 
		 $saledata_tj = M('报单')->where(array('id'=>$saleid))->find();
		 $saledata =$saledata_tj;
		if(!$saledata){
			$this->error(L('订单不存在'));
		}
        //判断审核人是否是注册人或者服务中心编号
        if($saledata['服务中心编号']!=$this->userinfo['编号'] && $saledata['注册人编号']!=$this->userinfo['编号']){
           $this->error(L('您无权操作'));
        }
		if($saledata['报单状态'] != '未确认')
		{
			$this->error(L('此订单不是未确认状态，不能进行删除'));
		}
		$saleobj = X('sale_*@'.$saledata['报单类别']);
		//判断节点类型
		if(get_class($saleobj)=='sale_reg')
		{
			//获取所有钱包
			foreach(X('fun_bank') as $bank)
			{
				$banks[]=$bank->name;
			}
			//计算被删除会员的所有钱包之和
			$sumMoney = M('货币')->where(array('编号'=>$saledata['编号']))->sum(implode("+",$banks));
			//判断是否开启转账给未激活(状态=无效)会员
			if(adminshow(zhuanzhang) && $sumMoney>0)
			{
				$this->error(L('非法操作'));
			}
		}
		M()->startTrans();
		//判断如果是注册订单。则同步删除会员
		if(get_class($saleobj)=='sale_reg')
		{
			$userdata= M('会员')->where(array('编号' => $saledata['编号']))->find();
			if(!$userdata){
				M()->rollback();
				$this->error(L('要删除的'.$this->userobj->byname.'不存在'));
			}
			
			$message   = $this->userobj->delete($userdata['id']);
			if($message!==true)
			{
				M()->rollback();
				$this->error($message);
			}
			M()->commit();
			$this->success(L('删除成功'));
		}
		else
		{
			$saleobj->delete($saledata);
			M()->commit();
			$this->success(L('删除成功'));
		}
	}
	//获取物流费和折扣并计算实付款
	function wuliufei(){
        $zhekou=1;$wlf=0;
		$province 	= I("post.province/s");
		$weight 	= I("post.weight/d");
		$zongjia 	= I("post.zongjia/d");
		//$userid  	= I("post.userid/s");
		$salename   = I("post.salename/s");
		$sale=X("@".$salename);
		$saletype=get_class($sale);
		//计算折扣
		if(X('user')->haveZhekou($sale)){
			//注册的默认按照会员级别来计算折扣
			if($saletype=='sale_reg'){
				$name1=$sale->lvName;
				$user=array($name1=>I("post.lv/d"));
			}else{//升级或购买，按照填写的会员信息
				//if($userid!=''){//升级按照统一的，没设计按照老级别还是新级别
				//	$user=M("会员")->where(array("编号"=>$userid))->find();
				//}elseif($saletype=='sale_shop'){
				$user=M("会员")->where(array("编号"=>$_SESSION[C('USER_AUTH_NUM')]))->find();
				//}
			}
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
		$this->ajaxReturn($ress,L('成功'),1);
    }
}
?>