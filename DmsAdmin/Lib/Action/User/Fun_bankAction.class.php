<?php
defined('APP_NAME') || die('不要非法操作哦');
class Fun_bankAction extends CommonAction {
	// 电子货币明细
	public function index(fun_bank $fun_bank)
	{
        $list = new TableListAction($fun_bank->name.'明细');
        //$list ->field('时间,来源,金额,余额,类型,备注')->where("编号=$this->userinfo['编号']"));
        $list ->where(array('编号'=>$this->userinfo['编号']))->order("时间 desc,id desc");
        //$list ->setSearch = array('time'=>array('row'=>'时间','exp'=>'gt'),'leavemoney'=>array('row'=>'余额'));
        $list ->setShow = array(
            L('时间')=>array("row"=>"[时间]","format"=>"time"),
            L('来源')=>array("row"=>"[来源]"),
            L('金额')=>array("row"=>"[金额]"),
            L('余额')=>array("row"=>"[余额]"),
            L('类型')=>array("row"=>"[类型]"),
            L('备注')=>array("row"=>"[备注]"),
        );
        $list->pagenum = 15;
        $this ->assign('name',$fun_bank->byname);
        $this ->assign('list',$list);
        $data = $list->getData();
        $this->assign('data',$data);
		$this->display();
	}
	// 现金提现
    public function get(fun_bank $fun_bank){
        if(!$fun_bank->getMoney || !$fun_bank->use){
            $this->error(L($fun_bank->byname).L('未开启'));
        }else{
        	//判断是否可以提现
        	if(in_array(date('w',systemTime()),$fun_bank->getMoneyWeek)){
        		$this->error("周".str_replace("0","7",implode(",",$fun_bank->getMoneyWeek))."不能提现".$fun_bank->byname);
        	}
        	if($fun_bank->getMoneyMday!='' && in_array(date('j',systemTime()),explode(",",$fun_bank->getMoneyMday))){
        		$this->error("每月的".$fun_bank->getMoneyMday."号不能提现".$fun_bank->byname);
        	}
        	//已添加的可用银行卡
        	$bankcards=M("银行卡")->where(array("卡号"=>array("neq",""),"户名"=>array("neq",""),"状态"=>"有效"))->select();
        	$this->assign('bankcards',$bankcards);
        	//银行账户列表
        	$mycards=M("银行账户")->where(array("userid"=>$this->userinfo['id']))->select();
        	$this->assign('mycards',$mycards);
        	//提现列表
            $list = new TableListAction("提现");
            $list ->where(array('编号'=>$this->userinfo['编号'],'类型'=>$fun_bank->name))->order("id desc");
            $list ->setShow = array(
                L('时间')=>array("row"=>"[操作时间]","format"=>"time"),
                L('开户行')=>array("row"=>"[开户行]"),
                L('开户名')=>array("row"=>"[开户名]"),
                L('银行卡号')=>array("row"=>"[银行卡号]"),
            );
			$list ->addShow(L('提现额'),array("row"=>"[提现额]"));
			if($fun_bank->getMoneyRatio!=1){
				$list ->addShow(L('手续费'),array("row"=>"[手续费]"));
				$list ->addShow(L('原始提现额'),array("row"=>"[实发]"));
				$list ->addShow(L('实发额'),array("row"=>"[换算后实发]"));
			}else{
                $list ->addShow(L('手续费'),array("row"=>"[手续费]"));
				$list ->addShow(L('实发'),array("row"=>"[换算后实发]"));
			}
			$list ->addShow(L('状态'),array("row"=>array(array(&$this,"dofun1"),"[状态]",'[撤销理由]')));
			if($fun_bank->allowBack_apply){
			$list ->addShow(L('撤销状态'),array("row"=>array(array(&$this,"dofun11"),"[撤销申请]")));
			}
			$list ->addShow(L('操作'),array("row"=>array(array(&$this,"dofun"),"[状态]","[id]",$fun_bank->objPath(),$fun_bank,"[撤销理由]")));
            $data = $list->getData();
            /*对getOnly的处理，如果已存在未审核提现，则不能继续提现*/
            if($fun_bank->getOnly && M('提现')->where(array('编号'=>$this->userinfo['编号'],'状态'=>0))->find())
            {
            	$this->assign('onlyLock',true);
            }
            $this->assign('data',$data);
            $this->assign('user',$this->userobj->byname);
            $this->assign('name',$fun_bank->byname."提现");  // 货币名称
            $this->assign('bank',$fun_bank);
            $this->display();
        }
    }
    //显示状态
    function dofun1($status,$memo)
    {
        if($status == 0){
            return L('未审核');
        }elseif($status == 1){
            return "<a href='javascript:alert(\"撤销理由：".$memo."\")'>".L('已撤销').'</a>';
        }elseif($status == 3){
            return L('已发放');
        }else{
            return L('已审核');
        }
    }
    function dofun11($status)
    {
        if($status == 1){
            return L('申请中');
        }if($status == 2){
            return L('已同意撤销');
        }if($status == 3){
            return L('拒绝撤销');
        }else{
           return L('未进行撤销申请');
        }
    }
    function dofun($str,$str1,$str2,$bank,$memo){
        if($str == 0){
            return '<a href="__URL__/getcancel:__XPATH__/id/'.$str1.'"  callback="delete_done">'.L('revoke').'</a>';
        }elseif($str == 1){
        	if($memo != '')
        	{
            	return '<a href="javascript:alert(\'撤销理由：'.$memo.'\')"  callback="delete_done">查看理由</a>';
            }
        }
    }
    // 提现撤销
    public function getcancel(){
        M()->startTrans();
        $getModel = M("提现");
        $where['id'] = I("get.id/d");
        $where['编号'] = $this->userinfo['编号'];
        $re = $getModel -> where($where)->find();
        $bank	= X('fun_bank@'.$re['类型']);
		if(!$bank->allowBack){
			$this->error(L('此货币不允许撤销!'));
		}
	   if($bank->allowBack_apply){
			//增加货币提现的是否被允许撤销的申请
			if($re && $re["撤销申请"] == "0"){
	            $data["id"] =I("get.id/d");
	            $data["撤销申请"] = '1';
	            $data["操作时间"] = systemTime();
	            $res = $getModel->save($data);
	            if($res){
	                $this->success(L('已进入撤销申请,会尽快回复'),__URL__.'/get:__XPATH__');
	            } else{
	                $this->error(L('审核失败'));
	            }
	        }
	       if($re && $re["撤销申请"] == "1"){
	          $this->error(L('撤销申请已提交过,请耐心等待'));
	       }
	       if($re && $re["撤销申请"] == "3"){
	          $this->error(L('已拒绝您的撤销申请,不能再进行撤销'));
	       }
		}
		else
		{
			if($re && $re["状态"] == "0"){
			    $data["id"] =I("get.id/d");
			    $data["状态"] = '1';
			    $data["审核时间"] = systemTime();          
			    $res = $getModel->save($data);
			    if($res){
			    	$bank->set($this->userinfo['编号'],$this->userinfo['编号'],($re["实发"]+$re['手续费']),'撤销提现','撤销提现返还：'.($re["实发"]+$re['手续费']));
			    	M()->Commit();
			        $this->success(L('撤销成功'),__URL__.'/get:__XPATH__');
			    } else{
			        $this->error(L('撤销失败'));
			    }
			}
       	}
    }
    // 保存提现信息
    public function getSave(fun_bank $fun_bank){
    	//防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
		B('XSS');
		M()->startTrans();
        $mess = "";
		if($fun_bank->getMoneyPass2){
		    if(!chkpass(I('post.pass2/s'),$this->userinfo["pass2"])){
		        $mess = L('二级密码错误');
		    }
		}
        if($fun_bank->getMoneyPass3){
            if(!chkpass(I('post.pass3/s'),$this->userinfo["pass3"])){
		        $mess .= L('三级密码错误');
		    }
        }
		if($fun_bank->getMoneySmsSwitch){
			$verify = S($this->userinfo['编号'].'_'.$bank->name.'提现');
			if(!$verify || $verify != I('post.getSmsVerfy/d') || I('post.getSmsVerfy/d')>0){
				$this->error(L('短信验证码错误或已过期!'));
			}
		}
		if($fun_bank->getSecretSafe){
            if($this->userinfo["密保答案"] != I('post.getsafeanswer/s')){
		        $this->error(L('密保答案有误'));
		    }
        }
        //如果被锁定
        if($this->userinfo[$fun_bank->name.'锁定']==1)
        {
        	$this->error(L('您的账户处于锁定状态.不能操作'));
        }
        $getsum = I("post.getsum/f");
        if(!is_numeric($getsum)|| $getsum <= 0){
            $mess .=L('金额不能为空<br>');
        }
        $checktype=M("银行账户")->where(array("userid"=>$this->userinfo['id'],"id"=>I("post.getbanktype/d")))->find();
		if(!$checktype){
			$mess .=L('请重新选择提款地址<br>');
		}
        if(!transform($fun_bank->getMoneyWhere,$this->userinfo))
        {
        	$mess=$fun_bank->getMoneyMsg;
        }
        if($mess != ""){
            $this->error($mess);
		}
		$userinfo=M("货币")->where(array("userid"=>$this->userinfo['id']))->find();
		//if(!M()->autoCheckToken(I("post.")))
		//{
		//	$this->error('您已经提交过提现申请,如继续操作,请从新点击提现功能');
		//}
        $re = $this->setGet($fun_bank,$userinfo,$checktype);
        if($re == ""){	
	        //写入会员操作日志
			$authInfo['姓名']=$this->userinfo['姓名'];
			$authInfo['编号']=$this->userinfo['编号'];
			$authInfo['id']=$this->userinfo['id'];
			$data = array();
			$datalog['user_id']=$authInfo['id'];
			$datalog['user_name']=$authInfo['姓名'];
			$datalog['user_bh']=$authInfo['编号'];
			$datalog['ip']=$_SESSION['ip'];
			$datalog['content']='会员提现';
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
			// 防止点击多次提交按钮，重复提交
			$checks = M('会员');
			M()->commit();
			M()->startTrans();
			//发送的验证码注销
			S($this->userinfo['编号'].'_'.$fun_bank->name.'提现',null,300);
			//添加会员提现邮件提醒
            if(CONFIG('txmmailSwitch')){
				sendMail($this->userinfo,$this->userobj->byname.'提现',CONFIG('txmmailContent'));
            }
            M()->commit();
            $this->success('操作成功');
        }else{// 错误信息
            $this->error($re);
        }
    }
    public function addgetcount(){
    	M()->startTrans();
    	//提现账户数量
    	$num=M("银行账户")->where(array("userid"=>$this->userinfo['id']))->count('id');
    	$lastinfo=M("银行账户")->where(array("userid"=>$this->userinfo['id'],"标签"=>trim(I("post.label/s"))))->find();
    	if($num<10 || $lastinfo){
	    	$data=array(
	    		"userid"=>$this->userinfo['id'],
	    		"标签"=>trim(I("post.label/s")),
	    		"银行"=>I("post.bank/s"),
	    		"银行卡号"=>I("post.bankcard/s"),
	    		"开户名"=>I("post.bankname/s"),
	    		"省份"=>I("post.province/s"),
	    		"城市"=>I("post.city/s"),
	    		"区县"=>I("post.city/s"),
	    		"时间"=>systemTime()
	    	);
	    	if($lastinfo){
	    		M("银行账户")->where(array("id"=>$lastinfo['id']))->save($data);
	    	}else{
	    		M("银行账户")->add($data);
	    	}
	    	M()->commit();
	    	if($lastinfo){
	    		$this->success("修改成功");
	    	}else{
	    		$this->success("添加成功");
	    	}
    	}else{
    		$this->error("最多可添加十个");
    	}
    }
    public function delgetcount(){
    	M()->startTrans();
    	$lastinfo=M("银行账户")->where(array("userid"=>$this->userinfo['id'],"id"=>I("post.id/d")))->find();
    	if($lastinfo){
    		M("银行账户")->delete($lastinfo['id']);
    		M()->commit();
	    	$this->success("删除成功");
    	}else{
    		$this->error("不存在数据，请刷新页面");
    	}
    }
    public function setgetcount(){
    	M()->startTrans();
    	$lastinfo=M("银行账户")->where(array("userid"=>$this->userinfo['id'],"id"=>I("post.id/d")))->find();
    	if($lastinfo){
    		M("银行账户")->where(array("userid"=>$this->userinfo['id'],"状态"=>1))->save(array("状态"=>0));
    		M("银行账户")->save(array("id"=>$lastinfo['id'],"状态"=>1));
    		M()->commit();
	    	$this->success("设置默认成功");
    	}else{
    		$this->error("不存在数据，请刷新页面");
    	}
    }
    //  提现  添加会员编号,提现金额,开户行,银行卡号,开户地址,开户名
	public function setGet($bank,$user,$checktype){
        $getsum = I("post.getsum/f");
        $bankname    = $checktype["银行"];
        $cardnumble  = $checktype["银行卡号"];
        $cardaddress = $checktype["省份"]."-".$checktype["城市"]."-".$checktype["区县"];
        $cardname    = $checktype["开户名"];
        $cardtel     = $this->userinfo["移动电话"];
		$data=array(
			"编号"=>$this->userinfo['编号'],
			"提现额"=>$getsum,
			"开户行"=>$bankname,
			"银行卡号"=>$cardnumble,
			"开户地址"=>$cardaddress,
			"开户名"=>$cardname,
			"联系电话"=>$cardtel,
			"操作时间"=>systemTime(),
		);
		$data['类型']=$bank->name;
		$data["手续费"] = ($bank->getMoneyTax/100) * $getsum;
        if($data["手续费"] < $bank->getMoneyTaxMin){
            $data["手续费"] = $bank->getMoneyTaxMin;
        }else if($bank->getMoneyTaxMax != 0 && $data["手续费"] > $bank->getMoneyTaxMax){
            $data["手续费"] = $bank->getMoneyTaxMax;
        }
        if($bank->getTaxFrom==1){
          	$data["实发"] = $getsum - $data["手续费"];
           	$banknum	  = $getsum;
        }else{
            $data["实发"] = $getsum;
           	$banknum	  = $getsum+$data["手续费"];
        }
		if($getsum < $bank->getMoneyMin ){
		    return L('不能少于最小提现额')."{$bank->getMoneyMin}！";
		}else if($bank->getMoneyMax > 0 && $getsum > $bank->getMoneyMax){
			return L('最大提现额不能超过')."{$bank->getMoneyMax}！";
		}else if($user[$bank->name] - $banknum < 0){
			return L('余额不足');
		}else if($bank->getMoneyInt != 0 && fmod($getsum,$bank->getMoneyInt)!=0){
			return L('提现金额需为{$bank->getMoneyInt}的倍数！');
		}else{
            $m_bank=M("提现");
			//提现汇率换算
			if($bank->getMoneyRatio){
				$data["换算后实发"]=$data["实发"]*$bank->getMoneyRatio;
			}
            $data["状态"] = "0";
			$re2=$m_bank->add($data);
			if($re2){
			    $bank->set( $user["编号"], $user["编号"],-$banknum,$this->userobj->byname.'提现','申请提现扣除：'.$getsum);
				return "";
			}else{
				return L('error_title');
			}
		}
	}
	//汇款通知列表
	public function rem()
	{
        $list = new TableListAction('汇款通知');
        $list ->where(array('编号'=>"{$this->userinfo['编号']}"))->order("id desc");
       
        $data = $list->getData();
         if(adminshow('huikuan')){
	         foreach($data['list'] as $key=>$v){
	           //查询汇款方式
	           $huikuan = M('汇款方式')->where(array('id'=>$v['汇款方式']))->find();
	           $data['list'][$key]['汇款方式'] = $huikuan['方式名称'];
	         }
         }
        $this->assign('is_huikuanimg',CONFIG('bankset'));
        $this->assign('hk_type',adminshow('huikuan'));
        $this->assign('data',$data);
		$this->display();
	}
	//不带图片的添加汇款
	function add_rem_two(){
		$bank = M("银行卡");
		$data = $bank ->where(array('卡号'=>array('neq',''),'状态'=>'有效'))->select();
		$this->assign('bank',$data);
		$this->assign('hkzhxz',CONFIG('hk_hkzhxz'));
		$this->assign('USER_REMIT_MIN',CONFIG('USER_REMIT_MIN'));
		$this->assign('USER_REMIT_MAX',CONFIG('USER_REMIT_MAX'));
		$this->display();
	}
	//不带图片的汇款保存
	function rem_save_two(){
        //防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
		B('XSS');
	  	$m = M('汇款通知');
		//判断未审核
		$unAudit=$m->where(array('编号'=>$_SESSION[C('USER_AUTH_NUM')],'状态'=>0))->find();
		if($unAudit){
			$this->error(L('您已经有未审核的记录存在，请等待审核后再提交'));
		}
		if(trim(I("post.汇入账户/s"))==''){
			$this->error(L('汇入账户不能为空'));
		}
		if(I("post.金额/f")<=0){
			$this->error(L('汇款金额输入有误'));
		}
		if(I("post.汇款时间/s")==''){
			$this->error(L('请输入汇款时间'));
		}
		if(trim(I("post.开户银行/s"))==''){
			$this->error(L('汇款银行不能为空'));
		}
		if(trim(I("post.银行卡号/s"))==''){
			$this->error(L('汇款卡号不能为空'));
		}
		if(trim(I("post.开户名/s"))==''){
			$this->error(L('汇款开户名不能为空'));
		}
		$USER_REMIT_MIN=CONFIG('USER_REMIT_MIN');
		$USER_REMIT_MAX=CONFIG('USER_REMIT_MAX');
		if($USER_REMIT_MIN != ''){
			if(I("post.金额/f")<$USER_REMIT_MIN){
				$this->error('填写金额小于最低汇款限制'.$USER_REMIT_MIN);
			}
		}
		if($USER_REMIT_MAX>0){
			if(I("post.金额/f")>$USER_REMIT_MAX){
				$this->error('填写金额大于最高汇款限制'.$USER_REMIT_MAX);
			}
		}
		$data	= $m->create();
		if($data===false){
			$this->error();
		}else{	
			$data['汇款时间']	= strtotime(I("post.汇款时间/s"));
			if(CONFIG("USER_REMIT_RATIO_USE")=="true"){
				$data['换算后金额'] = $data['金额']/CONFIG("USER_REMIT_RATIO");
			}
			if($m->add($data)){
				$this->success(L('操作成功'),__URL__.'/rem');
			}else{
				$this->error(L('操作失败'));
			}
		}
	
	}
	public function dispFunction($status)
	{
		if($status==0){
			return "未审核";
		}else{
			return "已审核";
		}
	}
	//添加汇款通知
		public function add_rem()
	{
		$bank = M("银行卡");	
		$data = $bank ->where("状态!='无效' and 卡号!='' and 户名!=''")->select();
		$this->assign('tp',$data);
		$this->assign('bank',$data);
		//$this->assign('hkzhxz',CONFIG('hk_hkzhxz'));
		$this->display();
	}
	public function add_rem1()
	{
		$id=I("get.id/d");
		$bank = M("银行卡");
		$data = $bank ->where(array("id"=>$id))->select();
		$this->assign('bank',$data);
		//$this->assign('hkzhxz',CONFIG('hk_hkzhxz'));
		$this->assign('hk_type',adminshow('huikuan'));
		if(adminshow('huikuan')){
			//查询所有的汇款方式
			$huikuans  = M('汇款方式')->select();
			$this->assign('huikuans',$huikuans);
		}
		$this->display();
	}
	//添加汇款通知保存
	public function rem_save()
	{
		//防XSS跨站攻击登入 调用ThinkPHP中的XSSBehavior
		B('XSS');
		$m = M('汇款通知');
		M()->startTrans();
		//判断未审核
		$unAudit=$m->where(array('编号'=>$_SESSION[C('USER_AUTH_NUM')],'状态'=>0))->find();
		if($unAudit){
			$this->error(L('您已经有未审核的记录存在，请等待审核后再提交'));
		}
		/*if(trim(I("post.汇入账户/s")==''){
			$this->error(L('汇入账户不能为空'));
		}*/
		if(I("post.金额/f")<=0){
			$this->error(L('汇款金额输入有误'));
		}
		if(I("post.汇款时间/s")==''){
			$this->error(L('请输入汇款时间'));
		}
		if(trim(I("post.开户银行/s"))==''){
			$this->error(L('汇款银行不能为空'));
		}
		if(trim(I("post.银行卡号/s"))==''){
			$this->error(L('汇款卡号不能为空'));
		}
		if(trim(I("post.开户名/s"))==''){
			$this->error(L('汇款开户名不能为空'));
		}
		if(adminshow('huikuan')){
			//判断是否选择汇款方式
			if(I("post.汇款方式/s")==""){
			   	$this->error(L('请选择汇款方式'));
			}
		}
		$data	= $m->create();
		if($data===false){
			$this->error();
		}else{
			$data['汇款时间']	= strtotime(I("post.汇款时间/s"));
			if(CONFIG("USER_REMIT_RATIO_USE")=="true"){
				$data['换算后金额'] = $data['金额']/CONFIG("USER_REMIT_RATIO");
			}
			
			if($m->add($data)){
				M()->commit();
				$this->success(L('操作成功'),__URL__.'/rem');
			}else{
				M()->rollback();
				$this->error(L('操作失败'));
			}
		}
	}
	//删除汇款通知
	public function rem_delete()
	{
		$m = M('汇款通知');
		$where['id']	= I("get.id/d");
		$where['编号']  = $this->userinfo['编号'];
		M()->startTrans();
		$m->where($where)->delete();
		M()->commit();
		$this->success(L('操作成功'));
	}

		//检验汇款通知银行卡卡号
	public function checkBank()
	{
		$cardid=I("post.cardid/s");
		$bank = M("银行卡");
		$data = $bank ->where(array('卡号'=>$cardid))->find();
		if($data)
		{
			echo "$('#state_incardid').html('您输入的卡号正确');$('#submit').removeAttr('disabled');";
		}
		else
		{
			echo "$('#state_incardid').html('未找到银行卡信息');$('#submit').attr('disabled','true');";
		}
	}
}
?>