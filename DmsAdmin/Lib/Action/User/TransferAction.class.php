<?php
defined('APP_NAME') || die('不要非法操作哦');
class TransferAction extends CommonAction{
	public function index()
	{
		$banks = M('转账设置')->where(array('status'=>1))->select();
		$bankdata = array();
		foreach((array)$banks as $bank)
		{
			$bankdata[$bank['id']] = $bank['title'];
		}
		$this->assign('pwd3Switch',adminshow('pwd3Switch'));
		$this->assign('giveMoneyPass2',CONFIG('giveMoneyPass2'));
		$this->assign('giveMoneyPass3',CONFIG('giveMoneyPass3'));
		$this->assign('giveMoneySmsSwitch',CONFIG('giveMoneySmsSwitch'));
		$this->assign('giveMoneySmsContent',CONFIG('giveMoneySmsContent'));
		$this->assign('bankdata',$bankdata);
		$this->display();
	}
	public function giveType()
	{
		$bank = M('转账设置')->where(array('id'=>I("post.giveToid/d")))->find();
		if($bank)
		{
			$bank['balance'] = $this->userinfo[$bank['bank']];
		}
		if($bank)
		{
			$this->ajaxReturn($bank,L('成功'),1);
		}else{
			$this->ajaxReturn('',L('失败'),0);
		}
	}
	//转账验证
	public function giveAjax()
	{
		$user='';
		if(I("post.userid/s")!=USER_NAME)
		{
			//判断是否开启转账给未激活(状态=无效)会员
			if(adminshow(zhuanzhang))
			{
				$user = $this->userobj->getuser(I("post.userid/s"));
			}else{
				$user = M('会员')->where(array('编号'=>I("post.userid/s"),'状态'=>'有效'))->find();
			}
		}
		if($user && I("post.userid/s")!= '')
		{
			$this->ajaxReturn(array('姓名'=>$user['姓名']),'成功',1);
		}
		else
		{
			$this->ajaxReturn('',L('失败'),0);
		}
	}
	//转账提交
	public function giveSave()
	{
		//验证转账货币、转账类型是否已选择
		if(I("post.giveTo/s")=="" || I("post.giveTypes/s")=="" || I("post.giveTypes/s")=='wu')
		{
			$this->error(L('输入的信息不完整'));
		}
		//验证是否有提交的转账类型
		$have = M('转账设置')->where(array('id'=>I("post.giveTo/s"),I("post.giveTypes/s")=>1,'status'=>1))->find();
		if(!$have)
		{
			$this->error(L('操作失败'));
		}
		//获取转账货币的节点
		$bank=X('fun_bank@'.$have['bank']);
		M()->startTrans();
		$mess='';
		//判断是否服务中心限定
		if($have['shop']!='无'){
			if($this->userinfo['服务中心']==0){
				$mess = L('仅限服务中心使用此功能');
			}
		}
		//如果需要验证二级密码
		if(CONFIG('giveMoneyPass2')==1 && !chkpass(I("post.pass2/s"),$this->userinfo["pass2"])){
		    $mess = L('二级密码错误');
		}
        //如果需要验证三级密码
        if(CONFIG('giveMoneyPass3')==1 && !chkpass(I("post.pass3/s"),$this->userinfo["pass3"])){
	        $mess .= L('三级密码错误');
        }
        //如果被锁定
        if($this->userinfo[$bank->name.'锁定']==1)
        {
        	$mess=L('您的账户处于锁定状态.不能操作');
        }
        //验证会员条件谁否可以转账
        if(!transform($bank->giveMoneyWhere,$this->userinfo))
        {
        	$mess=$bank->giveMoneyMsg;
        }
        //如果有验证没通过,报告错误信息
        if($mess != ""){				
            $this->error($mess);
            exit();
        }
		if(CONFIG('giveMoneySmsSwitch')==1){
			$verify = S(USER_NAME.'_'.$bank->name.'转账');
			if(!$verify || $verify != I("post.giveSmsVerfy/s") || I("post.giveSmsVerfy/s")==""){
				$this->error(L('短信验证码错误或已过期'));
			}
		}
		//如果转账给其他人
		$message='';
		if(I("post.giveTypes/s")=='toyou')
		{
			$userid = trim(I("post.userid/s"));
			//判断是否开启转账给未激活(状态=无效)会员
			if(adminshow(zhuanzhang))
			{
				if($userid =="" || !$this->userobj->have($userid)){
	                $message .= L('转入账户不存在')."<br/>";     //输出会员不存在提示
	    		}
			}else{
				if($userid =="" || !$this->userobj->haveActive($userid)){
	                $message .= L('转入账户不存在或未激活')."<br/>";     //输出会员不存在提示
	    		}
			}
            if(strtolower($userid) == strtolower($this->userinfo["编号"])){
                $message .= L('转入账户不能为自己')."<br/>";
            }
            // 网体判断
            if($have["nets"]!="无"){
                foreach(X('net_rec,net_place') as $net){
                    if($net->name == $have["nets"]){
                        $up = $net->getups($this->userinfo,0,0,"编号='$userid'");
                        $down = $net->getdown($this->userinfo,0,0,"编号='$userid'");
                        if(!$up && !$down){
                            $message .= L('只能转入'.$net->byname.'网体')."<br/>";
                        }
                    }elseif($net->name.'上级' == $have["nets"]){
						$up = $net->getups($this->userinfo,0,0,"编号='$userid'");
						if(!$up){
                            $message .= L('只能转入'.$net->byname.'上级')."<br/>";
                        }
					}elseif($net->name.'下级' == $have["nets"]){
						$up = $net->getdown($this->userinfo,0,0,"编号='$userid'");
						if(!$up){
                            $message .= L('只能转入'.$net->byname.'下级')."<br/>";
                        }
					}
                }
            }
            //转账他人的限制
            if($have["toyoutype"]!=""){
                $fwzx=M("会员")->where(array('编号'=>$userid))->getField('服务中心');
                $toyoutype=explode(',',$have["toyoutype"]);
                $typestr=$this->userinfo['服务中心']."-".$fwzx;
                if(!in_array($typestr,$toyoutype)){
                    $this->error(L('转账他人选择受限'));
                }
            }
            //默认编号和数据库一致
            if($message==''){
            	$userid=M('会员')->where(array("编号"=>$userid))->getField('编号');
            }
		}
        //获取转账金额
        $givesum = I("post.givesum/f");
        if(!is_numeric($givesum)|| $givesum <= 0){		//如果转账金额不是大于0的数字,报错
            $message .= L('转账金额不是大于0的数字')."<br/>";
        }else if($have["minnum"] > $givesum){	//如果转账金额小于最小转账金额限定,报错
            $message .= L('转账金额小于最小转账金额').$have['minnum']."<br/>";
        }else if($have["maxnum"] !=0 && $have["maxnum"] < $givesum){	//如果转账金额大于最大转账金额限定,报错
            $message .= L('转账金额大于最大转账金额').$have['maxnum']."<br/>";
        }
        if($have['intnum'] !="0" && $givesum % $have['intnum'] != 0){//如果转账金额不符合设定的整数倍限定,报错
            $message .= L('转账金额必须为').$have['intnum'].L('的整数倍')."<br/>";
        }
        if($message != ""){		//输出错误信息
            $this->error($message);
        }
        $m_user = M('会员');
        //获取转账的会员
        $re = $m_user->where("编号='".$this->userinfo["编号"]."'")->lock(true)->find();
        $re_h = M('货币')->where("编号='".$this->userinfo["编号"]."'")->lock(true)->find();//货币分离
        $re=$re+$re_h;
        //做上限判断,账户金额不能大于多少
        if(I("post.giveTypes/s")=='toyou')
        {
        	$touser=$m_user->where(array('编号'=>$userid))->lock(true)->find();
        	$touser_h=M('货币')->where(array('编号'=>$userid))->lock(true)->find();
        	$touser=$touser+$touser_h;
        }
        else
        {
        	$touser=$re;
        }
        //获取转账到那种货币的节点
        $tobank=X('fun_bank@'.$have['tobank']);
        $tops = $tobank->getcon('top',array('where'=>'','val'=>0,'msg'=>''));
        foreach($tops as $top)
        {
        	if(transform($top['where'],$touser) && $touser[$tobank->name] + $givesum > $top['val'])
        	{
        		if($top['msg'] == '')
        		{
        			$this->error(L('要转入的账户超过限额'));
        		}
        		else
        		{
        			$this->error($top['msg']);
        		}
        	}
        }
        //计算手续费
        $taxstr="";
        $tax = ($have['tax']/100) * $givesum;
        //判断手续费的最大最小值
        if($tax<$have['taxlow']){
        	$tax=$have['taxlow'];
        }else if($have['taxtop']>0 && $tax>$have['taxtop']){
        	$tax=$have['taxtop'];
        }
        if($have['taxfrom']==0){
        	$taxstr="，扣除手续费".$tax;
        	$givesum1  = $givesum;		//转入的金额
        	$givesum+=$tax;
        }else{
        	$givesum1  = $givesum - $tax;		//扣除手续费后转入的金额
        }
        //判断完成
        if($re[$bank->name] < $givesum){  //如果余额不足,输出错误信息
			$m_user->execute('unlock tables');
            $this->error(L('余额不足'.$givesum.$taxstr));
        }else{
        	//转换比率
        	$givesum1 = $givesum1 * ($have["sacl"]/100);
            //转账成功
            if($userid !=""){	//如果是转入其他人的账户, 转账处理,当前用户扣除货币,转入人增加货币
                $bank->set($this->userinfo["编号"],$userid,-$givesum,'转账转出',$_POST["memo"]."(转给[{$userid}]的{$tobank->byname})".$taxstr);
                $data=array(
            		"转出编号"=>$this->userinfo["编号"],
            		"转出货币"=>$bank->name,
            		"转出金额"=>$givesum,
            		"手续费"=>$tax,
            		"转入编号"=>$userid,
            		"转入货币"=>$tobank->name,
            		"转入金额"=>$givesum1,
            		"转换比率"=>$have["sacl"],
            		"操作时间"=>systemTime(),
            		"状态"=>"未审核",
            		"备注"=>I("post.memo/s")
            	);
                if(CONFIG('sureGiveMoney')==1){
                	M("转账明细")->add($data);
                }ELSE{
                	$tobank->set($userid,$this->userinfo["编号"],$givesum1,'转账转入',I("post.memo/s")."(转自[".$this->userinfo["编号"]."]的{$bank->byname})");
                }
            }else{
            	$userid=$this->userinfo["编号"];
            	//如果转入自己的其他货币账户, 扣除转出账户金额,增加转入账户金额
                $bank->set($this->userinfo["编号"],$this->userinfo["编号"],-$givesum,'转账转出',$_POST["memo"]."(转给自己的{$tobank->byname})".$taxstr);
                $data=array(
            		"转出编号"=>$this->userinfo["编号"],
            		"转出货币"=>$bank->name,
            		"转出金额"=>$givesum,
            		"手续费"=>$tax,
            		"转入编号"=>$userid,
            		"转入货币"=>$tobank->name,
            		"转入金额"=>$givesum1,
            		"转换比率"=>$have["sacl"],
            		"操作时间"=>systemTime(),
            		"状态"=>"未审核"
            	);
                if(CONFIG('sureGiveMoney')==1){
                	M("转账明细")->add($data);
                }ELSE{
                	$tobank->set($userid,$this->userinfo["编号"],$givesum1,'转账转入',$_POST["memo"]."(转自自己的{$bank->byname})");
                }
            }
            //写入会员操作日志
            X("user")->adduserlog($this->userinfo,$_SESSION['ip'],'会员转账');
            //写入会员操作日志结束
			//发送的验证码注销
			S(USER_NAME.'_'.$bank->name.'转账',null,300);
			//写入会员操作日志结束
			//添加会员转账短信提醒
            /*if($this->userobj->getatt('zhzhmsmsSwitch')){
            	if($this->userobj->getatt('zhzhmsmsSwitch1')){
          			 $copy=1; 
            	}
				sendSms($this->userinfo,$this->userobj->byname.'转账',$this->userobj->getatt('zhzhmsmsContent'),$copy);
            }*/
            
            //添加会员转账邮件提醒
            if(CONFIG('zhzhmmailSwitch')){
				sendMail($this->userinfo,$this->userobj->byname.'转账',CONFIG('zhzhmmailContent'));
            }
            //添加结束
           	M()->commit();
           	M()->startTrans();
           	sendSms('zhzh',USER_NAME,$this->userobj->byname.'转账',$data);
			if(CONFIG('sureGiveMoney')==1){
				sendSms('zhzhget',$userid,$this->userobj->byname.'转账',$data);
			}
			M()->commit();
			//独立跳转提示,在Action.class.php中判定
			$this->assign('newJump','1');
            $this->success(L('转账成功'),"__URL__/index/");
        }
	}
}
?>