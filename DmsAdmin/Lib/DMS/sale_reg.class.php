<?php
	class sale_reg extends sale{
		public $setLv=true;//注册是否选择级别
		public $selLv='';//注册是否选择级别
		public $dispWhere="";
		public $fromNoName="";
		//默认级别
		public $defaultLv=1;
		//前台注册是否验证邮箱
		public $mailcheck = false;
		//是否填写物流信息
		public $extra = false;
		//是否需要确认信息
		public $alert = false;
		public $lockMe = true;
		//正常注册可以使用的网体
		public $netName='all';
		//匿名注册可以使用的网体
		public $webRegNetname='all';
		//此订单扣谁的钱
		public $accstr ='注册人编号';
		public $accview='注册人编号';//审核人（谁能看到此订单）//可增加,服务中心编号,推荐_上级编号,编号
		public $fieldRelations=array(
			"name"=>"姓名",
			"alias"=>"昵称",
			"sex"=>'性别',
			"id_card"=>"证件号码",
			"email"=>"email",
			"qq"=>"QQ",
			"weixin"=>'微信账号',
			"country_code"=>'国家代码',
			"mobile"=>"移动电话",
			"country"=>"国家",
			"province"=>"省份",
			"city"=>"城市",
			"county"=>"地区",
			"town"=>"街道",
			"address"=>"地址",
			"reciver"=>"收货人",
			"pass1"=>"一级密码",
			"pass1c"=>"一级密码确认",
			"pass2"=>"二级密码",
			"pass2c"=>"二级密码确认",
			"pass3"=>"三级密码", 
			"pass3c"=>"三级密码确认",
			"bank_apply_name"=>"开户银行",
			"bank_card"=>"银行卡号",
			"bank_name"=>"开户名",
			"bank_apply_addr"=>"开户地址",
			"secretsafe_name"=>"密保问题",
			"secretanswer"=>"密保答案",
		);
		
		//注册自动验证
		public function getValidate($data_post=array()){
			$ret = array();
			//获得USER
			$user=$this->parent();
			//遍历fun_val得到必填的fun_val
			foreach(X('fun_val') as $funval){
				if($funval->required == "true" && $funval->regDisp){
					$ret[] = array($funval->name,'require',L($funval->byname).L('不能为空'),1);
					$this->fieldRelations[$funval->name] = $funval->name;
				}
				if($funval->usernameLock && $funval->regDisp && $data_post[$funval->name]!=''){
					$ret[] = array($funval->name,array($user,"have"),L($funval->byname).L('不存在'),2,'function');
				}
			}
			//判断必填
			$showarr=explode(',',CONFIG('USER_REG_SHOW'));
		    $requirearr=explode(',',CONFIG('USER_REG_REQUIRED'));
            foreach($requirearr as $requireinfo)
			{
				if($requireinfo !='' && in_array($requireinfo,$showarr))
				{
					if($requireinfo=="area")
					{
						$ret[] = array("country",'require',$this->fieldRelations["country"].L('不能为空'),1);
						$ret[] = array("province",'require',$this->fieldRelations["province"].L('不能为空'),1);
						$ret[] = array("city",'require',$this->fieldRelations["city"].L('不能为空'),1);
						$ret[] = array("county",'require',$this->fieldRelations["county"].L('不能为空'),1);
						$ret[] = array("town",'require',$this->fieldRelations["town"].L('不能为空'),1);
					}else if($requireinfo=="shop"){
						if($this->fromNoName!=""){
							$counts = M('会员')->count();
							if($counts){
						    	$ret[] = array($requireinfo,'require',L($this->fromNoName.'不能为空'),1);
						    }
					    }
					}else{
						$ret[] = array($requireinfo,'require',L($this->fieldRelations[$requireinfo]).L('不能为空'),1);
					}
				}
			}
			//存在国家号码
			if(in_array('country_code',$requirearr))
			{
				//
				import("COM.Mobile.NumCheck");
				$ret[] = array('mobile',array('NumCheck','check'),"电话号码不符",2,'function',3,$data_post['country_code']);
			}
			
			//唯一性判定
			if($user->onlyMobile == 1)
				$ret[] = array('mobile',array($user,"checkOnly"),"电话号码必须唯一",2,'function',3,array('mobile'));
			if($user->onlyMobile > 1)
				$ret[] = array('mobile',array($user,"checkOnly"),"同一电话最多只能注册".$user->onlyMobile."人"    ,2,'function',3,array('mobile'));

			if($user->onlyIdCard == 1)
				$ret[] = array('id_card',array($user,"checkOnly"),"证件号码必须唯一",2,'function',3,array('idcard'));
			if($user->onlyIdCard > 1)
				$ret[] = array('id_card',array($user,"checkOnly"),"同一证件最多只能注册".$user->onlyIdCard."人"    ,2,'function',3,array('idcard'));

			if($user->onlyBankCard == 1)
				$ret[] = array('bank_card',array($user,"checkOnly"),"银行卡号必须唯一",2,'function',3,array('bankcard'));
			if($user->onlyBankCard > 1)
				$ret[] = array('bank_card',array($user,"checkOnly"),"同一银行卡最多只能注册".$user->onlyBankCard."人",2,'function',3,array('bankcard'));
			//真实性
			if(isset($data_post['id_card']) && $data_post['id_card']!=''){
				$ret[] = array('id_card',array($user,"checkTruth"),'证件号码有误',2,'function',3,array('id_card'));
			}
			
			$ret[] = array('userid','require',L('id_required'),1);
			$ret[] = array('userid','/^[a-zA-Z0-9_]*$/',L('id_true'),1);
			//idExp在手工输入编号的时候的正则验证
			//idExpMsg在手工输入编号校验失败时的提示信息
			if($user->idExp != '' && !$user->idedit && $user->idAutoEdit)
			$ret[] = array('userid',$user->idExp,($user->idExpMsg =='' ? L('id_illicit') :$user->idExpMsg));
			$ret[] = array('userid',array($user,"nothave"),L('id_exist'),2,'function');
			//判定级别
			if($this->setLv)
			{
				//!!校验lv是否为空
				$ret[] = array('lv','require',L('level_required'),1);
				$ret[] = array('lv','number',L('is_level'),1);
				$iflv = array();
				foreach($this->getLvOption() as $opt)
				{
					$iflv[]=$opt['lv'];
				}
				$ret[] = array('lv',$iflv,L('level_illicit'),2,'in');
			}
			if($this->setNumber){
				$ret[] = array('setNumber','require',L('单数不能为空'),1);
				$ret[] = array('setNumber',array($this,'validateInt'),L('单数必须为大于0的整数'),1,'function');
			}
			//密保
			if(adminshow('mibao')) {
				$ret[] = array('secretsafe_name','require',L('请选择密保问题'),1);
				$ret[] = array('secretanswer','require',L('请填写密保答案'),1);
			}
			//产品
			if($this->productName){
				$productObj = X('product@'.$this->productName);
				$productObj -> formVerify($ret,$this);
			}
			
			//服务中心验证
            $this->fromVerify($ret,$data_post);

			foreach(X('fun_val') as $fun_val)
			{
				if($fun_val->regDisp && $fun_val->resetrequest!='')
				{ 
					foreach(explode(',',$fun_val->resetrequest) as $confirmnet){
						$data_post[$confirmnet]=$data_post['fun_'.$fun_val->getPos()];
						$data_post[$confirmnet]=$data_post['fun_'.$fun_val->getPos()];
					}				        
				}
			}
			//对所有的网络关系进行校验
			if($user->have())
			{
				foreach(X('net_rec') as $net)
				{
					//网体在注册的时候需要指定
					//如果显示网络，并且没有开启自动会员编号赋值，或者在后台调用.则不验证推荐人
					if($net->useBySale($this))
					{
						if(GROUP_NAME == 'User' && $net->setNowUser)
						{
							//取得当前登入的会员
							//把当前登入的账号赋予表单
							$map['id']=$_SESSION[C('USER_AUTH_KEY')];
							$upuser = M($_SESSION[C('USER_AUTH_TYPE')])->where($map)->find();
							$data_post['net_'.$net->getPos()]=$upuser['编号'];
						}
						//必须存在上级
						if($net->mustUp)
						{
							$ret[] = array('net_'.$net->getPos(),'require',L($net->byname).L('人不能为空'),1);	
						}
						
						$ret[] = array('net_'.$net->getPos(),array($net,"lvHave"),L($net->byname).L('net_not_exist'),2,'function');
						//非正式会员不能作为上级
						if(!$net->nullUp)
						{
							$ret[] = array('net_'.$net->getPos(),array($user,"isRegular"),L($net->byname).L('net_informal'),2,'function');
						}
						if($net->maxUser>0)
						{
							$ret[] = array('net_'.$net->getPos(),array($net,"isMaxuser"),L($net->byname).L('net_enough'),2,'function');
						}
						//判定推荐人额外条件
						$lockcons = $net->getcon('lock',array('where'=>'','msg'=>''));
						foreach($lockcons as $lockcon)
						{
							$ret[] = array('net_'.$net->getPos(),array($net,"ifLock"),$lockcon['msg'],2,'function',3,array($lockcon['where']));
						}
					}
				}
				foreach(X('net_place') as $net)
				{
					//网体在注册的时候需要指定
					if($net->useBySale($this))
					{
                     if($net->have())
					 { 
						 //判断安置人在推荐人的推荐网体下
						if($net->lockrec && $net->fromNet<>''){
							$rec1=X('net_rec@'.$net->fromNet);
							$ret[] = array('net_'.$net->getPos(),array($rec1,"recLock"),$net->byname."人，必须在".$rec1->byname."人的".$rec1->byname."网体下",2,'function',3,
								array($data_post['net_'.$rec1->getPos()])
								);
						}
						 //判断安置人在推荐人的安置网体下
						if($net->lockplace && $net->fromNet<>''){
							$rec1=X('net_rec@'.$net->fromNet);
							$ret[] = array('net_'.$net->getPos(),array($net,"placeLock"),$net->byname."人，必须在".$rec1->byname."人的".$net->byname."网体下",2,'function',3,
								array($data_post['net_'.$rec1->getPos()])
								);
						}
						//必须存在上级
						if($net->mustUp)
						{
							$ret[] = array('net_'.$net->getPos(),'require',L($net->byname).L('人不能为空'),1);
						}
						
						if(!$net->nullUp)
						{
							$ret[] = array('net_'.$net->getPos(),array($user,"isRegular"),L($net->byname).L('net_informal'),2,'function');
						}
							//如果允许选择区域
							if($net->setRegion)
							{
								$ret[] = array('net_'.$net->getPos().'_Region',$net->getBranch(),L($net->byname).L('net_place_info'),0,'in');
								//如果禁止滑落
								if(!$net->backFall)
								{
									$ret[] = array('net_'.$net->getPos(),array($net,"nothaveRegion"),L($net->byname).L('区域指定已经有人'),2,'function',3,array($data_post['net_'.$net->getPos().'_Region']));
								}
								//必须以从左到右依次注册
								if($net->Sequence)
								{
									$ret[] = array('net_'.$net->getPos().'_Region',array($net,"lockSequence"),'您的上一个区存在没有安置，请从新选择',2,'function',3,array($data_post['net_'.$net->getPos()]));
								}
								//对各区的条件进行判定
								
								foreach($net->getcon('region',array('name'=>'','where'=>'','msg'=>'')) as $region)
								{
									//如果遍历区域正是当前所选择的区域,同时此区域也需要有额外判定条件
									if($region['name'] == $data_post['net_'.$net->getPos().'_Region'] && $region['where']!='')
									{
										//取得安置人信息
										$placeuser=M('会员')->where(array('编号'=> $data_post['net_'.$net->getPos()]))->find();
										$where=$region['where'];
										//对myrec做判断,用于判定此区域必须是自己推荐
										if(strpos($where,'{myrec}')!==false)
										{
											if($net->fromNet=='')
											{
												throw_exception('在net_place中分支显示条件使用{merec}关键字时,必须设置网体的fromNet属性');
											}
											$recnet=X('net_rec@'.$net->fromNet);
											if($data_post['net_'.$net->getPos()]==$data_post['net_'.$recnet->getPos()])
											{
												$where=str_replace('{myrec}','true',$where);
											}
											else
											{
												$where=str_replace('{myrec}','false',$where);
											}
										}
										if(!transform($where,$placeuser))
										{
											$ret[] = array('net_'.$net->getPos(),'',$region['msg'],1,'equal');
										}
									}
								}
							}else{
								$ret[] = array('net_'.$net->getPos(),array($net,"haveAllRegion"),L($net->byname),2,'function');
							}
							//判断推荐的第一个人是推荐人的左区
							if($net->oneInLeft)
							{
								//取得对应的推荐网
								$rec=X('net_rec@'.$net->fromNet);
								if($data_post['net_'.$rec->getPos()]!=""){
								$ret[] = array(
									'net_'.$net->getPos(),
									array($net,"isInLeft"),
									$net->byname."人必须在".$rec->byname."人的最左边",
									2,
									'function',
									3,
									array($data_post['net_'.$rec->getPos()],$data_post['net_'.$net->getPos().'_Region'])
									);
								}
							}
							//安置人必须在推荐人网络体系之下的判定
							if($net->inOwn)
							{
								//取得对应的推荐网
								$rec=X('net_rec@'.$net->fromNet);
								if($data_post['net_'.$rec->getPos()]!=""){
								$ret[] = array(
									'net_'.$net->getPos(),
									array($net,"inOwnNet"),
									$net->byname."人必须在".$rec->byname."人的体系内",
									2,
									'function',
									3,
									array($data_post['net_'.$rec->getPos()])
									);
								}
							}
							$ret[] = array('net_'.$net->getPos(),array($net,"lvHave"),L($net->byname).L('net_not_exist'),2,'function');
					}
					}
				}
			}
			if(isset($data_post['pass1c']))
			$ret[] = array('pass1c','pass1',L('different_one'),2,'confirm');
			if(isset($data_post['pass2c']))
			$ret[] = array('pass2c','pass2',L('different_two'),2,'confirm');
			//基本信息验证
			//注册必选内容
			//两个数组根据值取得交集最后得到$regShow
			//$fieldRelations=$this->$fieldRelations;
			//$regShow的值与$FieldRelations的键值取交集得到$fieldRelations
			//foreach($fieldRelations as $key=>$FieldRelation)
			//{
				//增加表单名为$key不能为空的判定,如果为空则提示$FieldRelation不能为空
			//}
			//lock配置中的验证条件
			//如果没有指定要验证某一个字段,则表示是提交数据之前的整体验证.才会触发lock标签验证
			if(!isset($data_post["postname"])){
				$this->lockconVerify($ret,$this->getRegData($data_post,true));
			}
			//根据验证数组$ret自动验证操作
			$chksets = $ret;
			$m=M($this->parent()->name);
			$m->setProperty("patchValidate",true);		//开启批量验证
			
			$data = array();
			$errs = array();
			
			//如果提交数据有postname,则验证推荐/安置上级关系   否则全部表单验证
			if(isset($data_post["postname"]) && $data_post["postname"]!=''){
				//重新设置需要验证的字段
				$newchksets=array();
				foreach($chksets as &$chkset){	
					if(strpos($data_post["postname"],$chkset[0]) !== false){
						$newchksets[]=$chkset;
					}
				}
				//对安置区域进行判断,首先遍历所有安置网络关系
				foreach(X('net_place') as $net){
					//当前验证的表单名和此网络关系对应
					if(strpos($data_post["postname"],'net_'.$net->getPos()) !== false)
					{
						$where=array();
						//设置上级编号条件
						$where['编号']	= $data_post['net_'.$net->getPos()];
						//取得当前正选中的区域位置
						$data['posselect'] = $data_post['net_'.$net->getPos().'_Region'];
						//寻找到指定的上级
						$m_user=M($this->parent()->name)->where($where)->find();
						if($m_user)
						{
							//设置要输出的区域设置变量
							$RegionSet=array();
							//循环所有设置
							foreach($net->getRegion() as $Region)
							{
								//如果没有条件.则直接输出
								if(!isset($Region['where']) || $Region['where']=="")
								{
									$RegionSet[]=$Region;
								}else{
									$where=$Region['where'];
									if(strpos($where,'{myrec}')!==false)
									{
										$recnet=X('net_rec@'.$net->fromNet);
										if($data_post['net_'.$net->getPos()]==$data_post['net_'.$recnet->getPos()])
										{
											$where=str_replace('{myrec}','true',$where);
										}
										else
										{
											$where=str_replace('{myrec}','false',$where);
										}
									}
									//如果有条件,则安置人符合条件.才输出
									if(transform($where,$m_user))
									{
										$RegionSet[]=$Region;	
									}
								}
							}
							//设置安置位置属性
							$data['RegionSet'] = array('net_'.$net->getPos().'_Region',$RegionSet);
						}
					}
				}
				$m->setProperty("_validate",$newchksets);
				$m->create();
				if($m->create() === false){		
					$errs=$m->getError();
				}else{
					foreach(X('net_rec,net_place') as $net)
					{
						if($net->useBySale($this)){
							$where=array();
							$where['编号']	= $data_post['net_'.$net->getPos()];
							if(strpos($data_post["postname"],'net_'.$net->getPos()) !== false && !isset($errs['net_'.$net->getPos()]))
							{
								$errs['net_'.$net->getPos()]=M($this->parent()->name)->where($where)->getfield('姓名');
							}
							if(strpos($data_post["postname"],'net_'.$net->getPos()) !== false && !isset($errs['net_'.$net->getPos().'_Region']))
							{
								$errs['net_'.$net->getPos().'_Region']='';
							}
						}
					}
					if(strpos($data_post["postname"],'shop') !== false && !isset($errs['shop']))
					{
						$where['编号']	= $data_post['shop'];
						$errs['shop']=M($this->parent()->name)->where($where)->getfield('姓名');
					}
				}
			}else{
				$m->setProperty("_validate",$chksets);
				if($m->create() === false)
				{
					$errs=$m->getError();
				}
			}
			return array('error'=>$errs,'data'=>$data);
		}
		//正式用户注册信息
		public function regSave($data=array(),$autoreg=false){
			$user=$this->parent();
			M('会员')->where('id<0')->lock(true)->find();
			//扣款的设置
			if($this->showRatio){
				$data['paycons']=array();
				$accbankObj=X("accbank@".$this->accBank);
				$accRatioary=$accbankObj->getcon("bank",array("name"=>"","minval"=>"0%","maxval"=>'100%',"extra"=>false),true);
				foreach($accRatioary as $acckey=>$accRatio){
					if($accRatio['extra']){
						$val=$accRatio['maxval'];
					}else{
						$val=$data['accval'][$acckey];
					}
					if(strstr($val,"%"))
					{
						$data['paycons'][$accRatio['name']]=$val."%";
					}
					else
					{
						$data['paycons'][$accRatio['name']]=$val;
					}
				}
			}
            
			$regdata = $this->getRegData($data);
			$udata = $regdata['udata'];
			$sdata = $regdata['sdata'];
			$fdata = $regdata['fdata'];//货币分离
			$stckdata=$regdata['stckdata'];//股票
			/*付款审核*/
			$accret=$this->accbank($sdata,$data,$udata);
			if($accret!==true)
			{
				return $accret;
				//throw_exception("会员注册时，货币验证失败，但应该在AJAX层面提前进行验证。请检查程序");
			}
			/*付款审核完成*/
			$m_user=M("会员");
			$m_sale=M("报单");
            
            //密保问题
            if(adminshow('mibao')){
                $mibao['编号']=$udata['编号'];
                $mibao['密保问题']=trim($udata['密保问题']);
                $mibao['密保答案']=trim($udata['密保答案']);
                $m_mibao=M('密保');
                $m_mibao->add($mibao);
                unset($udata['密保问题'],$udata['密保答案'],$mibao);
            }
            
			//得到新数据库ID
			$udata["id"] = $m_user->add($udata);
			if($udata['id'] == false)
			{
				M()->rollback();
				throw_exception('注册插入'.$user->name.'失败，原因为'.htmlentities(M()->getDbError(),ENT_COMPAT ,'UTF-8'));
			}
			$udata = $m_user->find($udata['id']);
			//赋值到报单记录中
			$sdata["userid"]=$udata["id"];
			//货币分离
			$fdata["userid"]=$udata["id"];
			M('货币')->add($fdata);
			//股票数据
			if(count($stckdata)>0){
				$stdata=array(
					"userid"=>$udata["id"],
					"编号"=>$udata["编号"],
					"数量"=>0
					);
				foreach($stckdata as $stname=>$sval){
					M($stname)->add($stdata);
				}
			}
			//增加报单记录
			$sdata["id"] = $m_sale->add($sdata);
			$sdata=$m_sale->find($sdata["id"]);
			if($sdata['id'] == false)
			{
				M()->rollback();
				throw_exception('注册插入'.$user->name.'订单失败，原因为'.htmlentities(M()->getDbError(),ENT_COMPAT ,'UTF-8'));
			}
			//产品信息
			$product = $regdata['productdata'];
			if($product){
				foreach($product as $k=>$productdata){
					$productdata['报单id'] = $sdata["id"];
					M('产品订单')->add($productdata);
				}
			}
			X('user')->callevent("user_reg",array("user"=>&$udata,'sale_reg'=>$this));
			$confirm = isset($data['confirm']) ? $data['confirm'] : $this->confirm;
			if($confirm)
			{
				//处理审核操作
				$this->runconfirm($udata,$sdata,$product);
				X('user')->callevent("user_verify" ,array("user"=>$udata));
			 }
			 $return=array();
			 $return['saleid'] = $sdata["id"];
			 $return['userid'] = $udata['编号'];
			 //进行自动化多点位注册
			 if(!$autoreg)
			 {
				 $regcons = $this->getcon('reg',array('lv'=>1,'num'=>0,'confirm'=>'false','userwhere'=>'','placename'=>''));
				 foreach($regcons as $regcon)
				 {
				     if(transform($regcon['userwhere'],$udata))
				     {
				     	if($regcon['placename']!='')
				     	{
				     		$pnet=X('net_place@'.$regcon['placename']);
				     	}
				     	else
				     	{
				     		$pnets=X('net_place');
				     		if(count($pnets)==1)
				     		{
				     			$pnet=$pnets[0];
				     		}
				     	}
				     	if(!$pnet)
				     	{
				     		//输出警告
				     	}
				     	//----------------------------
				     	$Branch=$pnet->getBranch();
				     	$regarr=array();
				     	$regarr[1]=$udata['编号'];
				     	
				     	for($i =2;$i<=$regcon['num']+1;$i++)
				     	{
				     		$newid = $this->parent()->getnewid();
				     		while(in_array($newid,$regarr))
				     		{
				     			$newid = $this->parent()->getnewid();
				     		}
				     		$regarr[$i] = $newid;
				     		//复制注册信息
				     		$regdata=$data;
				     		$regdata['userid'] = $regarr[$i];
				     		//循环推荐网
				     		foreach(X('net_rec') as $netrec)
				     		{
				     			$regdata['net_'.$netrec->getPos()]=$udata['编号'];
				     		}
				     		$regdata['net_'.$pnet->getPos()]=$regarr[floor($i/2)];
				     		$regdata['net_'.$pnet->getPos().'_Region']= $Branch[ $i % 2 ];
				     		$regdata['lv']=$regcon['lv'];
				     		$regdata['confirm'] = ($regcon['confirm']=='true');
				     		$this->regSave($regdata,true);
				     	}
				     }
				 }
			}
			//如果注册的为第一个会员，则更新结算起始日
			//有一些效率问题，应该要做一个配置项，HAVEUSER，默认为FALSE 在注册完成之后为TRUE。
			//$usernum=M($user->name)->count();
			//if($usernum==1){
			//	CONFIG('CAL_START_TIME',strtotime(date('Y-m-d',systemTime())));
			//}
			return $return;
		}
		//获得注册用户信息 订单信息
		public function getRegData($data,$isAjax=false){
			$sdata=array();
			$udata  = array("编号"=>$data["userid"]);
			//是否为正式状态
			$confirm = isset($data['confirm']) ? $data['confirm'] : $this->confirm;
			//空点实点，没有选项默认实点
			if(!isset($data['nullMode'])) $this->nullMode=0;else $this->nullMode=$data['nullMode'];
			
			//获取注册人及服务中心编号
			$this->getFromInfo($sdata,$data);
			$udata['服务中心编号'] = $sdata["服务中心编号"];
			$udata['注册人编号']=$sdata['注册人编号'];
			
			//默认密码设置  如果默认密码不为空，那么注册未设置密码使用默认密码；默认密码为空则使用注册会员编号
			!isset($data['pass1']) && $data['pass1']='';
			!isset($data['pass2']) && $data['pass2']='';
			if($data['pass1']==""){
				CONFIG('DEFAULT_USER_PASS1')?$data['pass1']=CONFIG('DEFAULT_USER_PASS1'):$data['pass1']=$data["userid"];
			}
			if($data['pass2']==""){
				CONFIG('DEFAULT_USER_PASS2')?$data['pass2']=CONFIG('DEFAULT_USER_PASS2'):$data['pass2']=$data["userid"];
			}
			$udata['pass1']= md100( $data['pass1']);
			$udata['pass2']= md100( $data['pass2']);
			if(adminshow('pwd3Switch')){
				if(!isset($data['pass3']) || $data['pass3']==""){CONFIG('DEFAULT_USER_PASS3')?$data['pass3']=CONFIG('DEFAULT_USER_PASS3'):$data['pass3']=$data["userid"];}
				$udata['pass3']= md100( $data['pass3']);
			}
			//设置级别
			$udata[$this->lvName] = $this->setLv ? (int)$data["lv"] : $this->defaultLv;
			$udata['申请'.$this->lvName] = $udata[$this->lvName];
			
			//设置注册类型
			$udata['注册类型']=$this->user;
			//更新注册日期
			$udata["注册日期"]	= systemTime();
			$sdata["购买日期"]  = systemTime();
			//直接确认
			if($confirm)
			{
				$udata["审核日期"]=systemTime();
				$udata["状态"]="有效";
				$sdata["报单状态"]='已确认';
				$sdata["到款日期"]=systemTime();
			}
			else
			{
				$udata["状态"]="无效";
				$sdata["报单状态"]='未确认';
			}
			//网体数据
			foreach(X('net_rec') as $net)
			{
				if($net->useBySale($this))
				{
					//后台注册后者处于未绑定当前会员,走手工填写上级编号流程
					if(GROUP_NAME == 'Admin' || $net->setNowUser == false)
					{
						$udata[$net->name.'_上级编号'] = '';
						if(isset($data["net_".$net->getPos()])){
							//默认编号和数据库一致
							$netup=M("会员")->where(array("编号"=>trim($data["net_".$net->getPos()])))->field('编号')->find();
							if($netup) $udata[$net->name.'_上级编号'] =$netup['编号'];
						}
					}
					//前台当前会员强制注册流程
					if(GROUP_NAME == 'User' && $net->setNowUser)//会员前台注册设置当前会员为上级编号
					{
						$map['id']=$_SESSION[C('USER_AUTH_KEY')];
						$upuser = M('会员','dms_')->where($map)->field('编号')->find();
						$udata[$net->name.'_上级编号'] = $upuser['编号'];
					}
					//根据上级编号处理相关默认数据
					$udata[$net->name.'_网体数据'] ='';
					$udata[$net->name.'_层数'] = 0;
				}
			}
			foreach(X('net_place') as $net)
			{
				$udata[$net->name.'_上级编号']='';
				$udata[$net->name.'_位置']='';
				$udata[$net->name.'_层数']=0;
				$udata[$net->name.'_网体数据'] ='';
				foreach($net->getBranch() as $Branch)
				{
					$udata[$net->name.'_'.$Branch."区"]='';
				}				
				if($net->useBySale($this))
				{
					//初始化一下

					//设置填写的上级编号
					if(isset($data["net_".$net->getPos()])){
						$netup=M("会员")->where(array("编号"=>trim($data["net_".$net->getPos()])))->field('编号')->find();
						if($netup) $udata[$net->name.'_上级编号'] =$netup['编号'];
					}
					//判断不是注册第一个人
					if(X("user")->have()){
						//强制选择位置
						if($net->setRegion)
						{
							$Region  = $data["net_".$net->getPos()."_Region"];
							$branch  = $net->getBranch();
							$oldUpId = $udata[$net->name.'_上级编号'];
							//如果开启滑落
							$upuserid=M("会员")->where(array("编号"=>$udata[$net->name.'_上级编号']))->getField('id');
							if($net->backFall && $udata[$net->name.'_上级编号'] != '' && $upuserid)
							{
								while(true)
								{
									//找到上级
									$upuser = M('会员','dms_')->where(array('编号'=>$udata[$net->name.'_上级编号']))->find();
									if($upuser)
									{
										//上级特定区不为空的情况
										if($upuser[$net->name.'_'.$Region."区"]!='')
										{
											//向特定区进行滑落
											$udata[$net->name.'_上级编号']=$upuser[$net->name.'_'.$Region."区"];
										}
										else
										{
											//特定区为空,取得所有区,进行对比,看当前滑落区是否为左区
											if($branch[0] == $Region || $oldUpId == $udata[$net->name.'_上级编号'])
											{
												//如果是左区,则跳出循环
												break;
											}
											else
											{
												//设置为左区继续滑落
												$Region = $branch[0];
											}
										}
									}
								}
							}
							$udata[$net->name.'_位置'] = $Region;
						}
						else
						{
							//自动选择位置
							$udata[$net->name.'_位置'] = $net->getNullRegion($udata[$net->name.'_上级编号']);
						}
					}
					if(!isset($udata[$net->name.'_上级编号']) || $udata[$net->name.'_上级编号']==NULL)
						$udata[$net->name.'_上级编号']='';
					if(!isset($udata[$net->name.'_位置']) || $udata[$net->name.'_位置']==NULL)
						$udata[$net->name.'_位置']='';
					//设置默认层数
					$udata[$net->name.'_层数']=1;
					$udata[$net->name.'_网体数据'] ='';
					if($udata[$net->name.'_上级编号']!='')
					{
						$upuser = M('会员')->where(array('编号'=>$udata[$net->name.'_上级编号']))->find();
						$udata[$net->name.'_网体数据'] = trim($upuser[$net->name.'_网体数据'].",".$upuser["id"].'-'.$udata[$net->name.'_位置'],',');
						$udata[$net->name.'_层数'] = $upuser[$net->name.'_层数'] + 1;
						if(!$isAjax)
						{
							M('会员')->where("编号='".$udata[$net->name.'_上级编号']."'")->save(array(
							$net->name."_".$udata[$net->name.'_位置'].'区'=>$udata['编号']
							));
						}
					}
				}
			}			
			//对常规信息进行加载
			foreach($this->fieldRelations as $key=>$val)
			{
				$udata[$val]	  = isset($data[$key]) ? $data[$key] : '';
			}
			//货币分离，批量注册
			$fdata=array();
			if(isset($data['funbank'])){
				foreach($data['funbank'] as $fk=>$fval){
					$fdata[$fk]	  =$fval;
				}
			}
			$fdata['编号']    =$udata['编号'];
			//收货信息
			$sdata['收货国家']=isset($data['country']) ? $data['country']  : '';
			$sdata['收货省份']=isset($data['province'])? $data['province'] : '';
			$sdata['收货城市']=isset($data['city'])    ? $data['city']     : '';
			$sdata['收货地区']=isset($data['county'])  ? $data['county']   : '';
			$sdata['收货街道']=isset($data['town'])    ? $data['town']     : '';
			$sdata['收货人']  =isset($data['reciver']) ? $data['reciver']  : '';
			$sdata['收货地址']=isset($data['address']) ? $data['address']  : '';
			$sdata['联系电话']=isset($data['mobile'])  ? $data['mobile']   : '';
			//报单信息
			$sdata['编号']        =$udata['编号'];
			$sdata['报单类别']    =$this->name;
			$sdata["byname"]      =$this->byname;
			//推广链接注册的不需要注册人
			if(isset($data['shifoutuiguang']) && $data['shifoutuiguang']==1){
			  	$sdata['是否推广链接']        =1;
			  	$udata['注册人编号']='';
			  	$sdata['注册人编号']='';
			}

			//报单金额
			$bdmoney=$this->getSaleMoneys($data);
			$sdata["报单金额"]    =$bdmoney['pvmoney'];
			$sdata["报单单数"]    =$bdmoney['num'];
			$sdata["实付款"]	  =$bdmoney['money'];
			//产品数据
			$sdata['物流费']      =0;
			if($this->productName){
				$data['productNum'] = isset($data['productNum'])?$data['productNum']:array();
				$productObj = X('product@'.$this->productName);
				if(isset($productObj)){
					$productdata = $productObj -> setField($sdata,$data['productNum'],$this);
				}else{
					if(isset($data['product'])){
						//根据提交的产品数据形成订单
						$productdata = $data['product'];
						$sdata['购物金额']=$data['productCountMoney'];
						$sdata['实付款'] = $data['payCountMoney'];
						$sdata['运费'] = $data['wuliu'];
						$sdata['产品'] =1;
					}
				}
			}
			//计算实付款
			$payMoney = $this->getPayMoney($data,$sdata);
			$sdata["accbank"]="";$sdata["accokstr"]="";
			//生成支付的数据
			if(isset($data['paycons'])){
				$accbankObj = X("accbank@".$this->accBank);
				if(isset($accbankObj)){
					$sdata["accbank"]=$accbankObj->makejson($data['paycons']);
				}
			}
			//判断空点
			if($this->nullMode){ 
				$udata["空点"]		  =	1;
				$sdata["回填金额"]	  =	$payMoney+$sdata['物流费'];
				$sdata["回填"]		  = 1;
				$sdata["实付款"]      =	0;
				if($this->nullMode==1){
					$sdata["报单状态"]= '空单';
				}elseif($this->nullMode==2){
					$sdata["报单状态"]= '回填';
				}
				//$sdata["到款日期"]    = 0;
			}else{
				$udata["空点"]		  =	0;
				$sdata["实付款"]      =	$payMoney;
			}
			$productdata = isset($productdata) ? $productdata : null;
			$stckdata=array();
			foreach(X("fun_stock") as $fun_stock){
				$stckdata[$fun_stock->name]=0;
			}
			return array('udata'=>$udata,'sdata'=>$sdata,'fdata'=>$fdata,'stckdata'=>$stckdata,'productdata'=>$productdata);
		}
		//获取报单金额和报单单数
		public function getSaleMoneys($data){
			//初始化
			$ret=array("money"=>0,"pvmoney"=>0,"num"=>0);
			//得到实际级别
			$lv =$this->setLv ? (int)$data["lv"] : $this->defaultLv;
			//得到LEVELS对象设置
			$lvcons=X('levels@'.$this->lvName)->getcon('con',array('lv'=>0,"money"=>0,"pvmoney"=>-1,"num"=>0,'number'=>1));
			foreach($lvcons as $lvcon)
			{
				if($lvcon["lv"]==$lv)
				{
					if($this->setNumber){
						$number = intval($data['setNumber']);
						$this->num = $number;//报单单数=设定单数
					}else{
						$number = $lvcon['number'];
					}
					if($this->money != -1){
						$money = $this->money;
					}else{
						$money = $lvcon["money"];
					}
					if($this->pvmoney != -1){
						$pvmoney = $this->pvmoney;
					}else{
						$pvmoney = $lvcon["pvmoney"];
					}
					if($this->num != -1){
						$num = $this->num;
					}else{
						$num = $lvcon['num'];
					}
					$ret['money']  =$money * $number;
					$ret['pvmoney']=($pvmoney == -1 ? $money : $pvmoney)*$number;
					$ret['num']    =$num;
					break;
				}
			}
			return $ret;
		}
		//根据levels的use设置以及sale类的sellv设置获取可用级别
		public function getLvOption()
		{
			$levels=X('levels@'.$this->lvName);
			$levelsopt=array();
			$setLvArr=explode(',',$this->selLv);
			foreach($levels->getcon("con",array("name"=>"","lv"=>0,'use'=>'')) as $opt)
			{
				if($opt['use']!='false' && ($this->selLv=='' || in_array($opt['lv'],$setLvArr)))
				{
					$levelsopt[]=array('lv'=>$opt['lv'],'name'=>L($opt['name']));
				}
			}
			return $levelsopt;
		}
	}
?>