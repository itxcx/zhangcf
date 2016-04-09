<?
	//默认返回是true为执行成功，flase为执行失败
	class apiuser
	{
		/*
			默认参数
		*/
		protected static $apiurl="";//远程域名
			
		/*
		**修改资料时使用，若传过来的值中有则统一值
		**商城与结算的会员信息对应字段 key值是商城字段  val值结算字段
		*/
		protected static $fieldRelations=array(
			"truename"=>"姓名",
			"reciver"=>"收货人",
			"alias"=>"昵称",
			"sex"=>'性别',
			"id_card"=>"证件号码",
			"bank_apply_name"=>"开户银行",
			"bank_apply_addr"=>"开户地址",
			"bank_card"=>"银行卡号",
			"bank_name"=>"开户名",
			"email"=>"email",
			"qq"=>"QQ",
			"mobile"=>"移动电话",
			"country"=>"国家",
			"province"=>"省份",
			"city"=>"城市",
			"county"=>"地区",
			"town"=>"街道",
			"address"=>"地址",
			"passwd_one"=>"pass1"
		);
		
		/*
		**操作日志 向外传输数据 写的操作日志
		*/
		function wj_fsosavefile($title,$data,$result=''){
			$wj_names=date('Ymd',time());
			$wj_array1=array($title,date('Y-m-d H:i:s',time()),get_client_ip(),"http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],$data);
			$wj_content = array_merge ($wj_array1);
			$wj_char=implode(' ',$wj_content);
			$wj_char='\r\n' . ($wj_char) . ';';
			$file="./logs/".$wj_names.".txt";
			$fp = fopen($file,"a+",true);
			fwrite($fp,stripcslashes($wj_char));
			fclose($fp);
			return true;
		}
		/*
		**返回结果的输出日志
		*/
		function wj_fsosavefile_result($txt){
			$wj_names=date('Ymd',time());
			$wj_array1=array(date('Y-m-d H:i:s',time()),get_client_ip(),$txt);
			$wj_content = array_merge ($wj_array1);
			$wj_char=implode(' ',$wj_content);
			$wj_char='\r\n' . ($wj_char) . ';';
			$file="./logs/res_".$wj_names.".txt";
			$fp = fopen($file,"a+",true);
			fwrite($fp,stripcslashes($wj_char));
			fclose($fp);
			return true;
		}
		/*
		**判断会员是否在系统中
		*/
		function salecheck($data=array()){
			$resuser=M("会员")->where("编号='".$data["username"]."'")->find();
			if($resuser){
				$result['status']=true;
			}else{
				$result['status']=false;
			}
			return $result;
		}
		/*
		**注册新会员，商城传输数据过来注册，获得注册的信息，然后根据会员信息字段对应生成提交的表单信息
		**注册时直接提交到sale_reg的regsave()函数，所以在xml中设置sale_reg节点<sale_reg lvName="会员级别" name='商城注册' byname="会员注册" user='商城'  confirm="true"></sale_reg>
		**节点中适合运行所有程序，与普通的会员和后台admin注册是一致的使用方法，可加addval、update等节点
		*/
		function salereg($regdata){
			$result['status']=false;
			/*
			 *得到会员编号后，查询结算中会员是否存在
			*/
			$haveuser=M("会员")->where("编号='".$regdata['username']."'")->find();
			if($haveuser){
				$result['error']="会员已在结算注册";
				return $result;
			}else{
				/*
				**提交注册的表单数组，可根据结算中的注册流程，在提交sale_reg节点的数组做相应的修改
				*/
				if(!CONFIG('HAVEUSER') && ($regdata['lv']==1 || !isset($regdata['lv']))){
					$regdata['lv']=5;
				}else{
					$regdata['lv']=1;
				}
				$postdata=array(
					'userid'	=>	$regdata['username'],      	//注册的编号
					
					'lv'		=>	$regdata['lv'],				//默认的免费一级会员
					
					'sex'		=>  $regdata['sex'],			//性别 0 男 1 女
					
					'alias'		=>  $regdata['alias'],			//别名
					
					'name'		=>	$regdata['truename'],		//姓名
					
					'id_card'	=>  $regdata['id_card'],		//证件号码
					
					'mobile'	=>	$regdata['mobile'],			//联系方式
					
					'email'		=>	$regdata['email'],			//联系方式
					
					'qq'		=>  $regdata['qq'],				//QQ号码
					
					'pass1'		=>	$regdata['passwd_one'],		//密码
					
					'pass1c'	=>	$regdata['passwd_one'],		//确认密码
					
					'pass2'		=>	$regdata['passwd_one'],		//二级密码  由商城注册  默认等于登陆密码
					
					'pass2c'	=>	$regdata['passwd_one'],		//确认二级密码
					
					'net_'.X('net_rec@推荐')->getpos()		=>	$regdata['tjruserid'],		//推荐上级
					
					'country'	=>	"中国",						//国家
					
					'province'	=>	$regdata['province'],		//省份
					
					'city'		=>	$regdata['city'],			//城市
					
					'county'	=>	$regdata['county'],			//区县
					
					'town'		=>	$regdata['town'],			//街道
					
					'address'	=>	$regdata['address'],		//详细地址
					
					'reciver'	=>  $regdata['reciver'],		//收货人
					
					'comefrom'  =>  'shopping'
				);
				$return=X("sale_reg@商城注册")->regsave($postdata);
				if(gettype($return)=='string')
				{
					$result['error']=$return;
					return $result;
				}else{
					$result['status']=true;
				}
			}
			return $result;
		}
		/*
		**升级会员，商城传输数据过来升级，会员进行升级必须在结算中存在这个会员，不存在返回商城 会员不存在，获得升级的信息，然后根据会员的级别以及要升的级别生成提交的表单信息
		**升级时直接提交到sale_up的upsave()函数，所以在xml中设置sale_up节点<sale_up name='商城升级' byname='商城升级' lvname='会员级别' user='商城'  confirm="true" productname="商城产品"></sale_up>
		**节点中适合运行所有程序，与普通的会员和后台admin注册是一致的使用方法，可加addval、update等节点
		*/
		function upsale($salelists){
			$result['status']=false;
			/*
			 *得到会员编号后，查询结算中会员是否存在
			*/
			if(!$salelists['username']){
				return "未知会员";
			}
			$user=M("会员")->where(array("编号"=>$salelists['username']))->find();
			if(!$user)
			{
				$result['error']="结算系统会员不存在";
				return $result;
			}
			foreach($salelists['saledata'] as $key=>$sales){
				$backprice=0;$oldprice=0;
				$user=M("会员")->where(array("编号"=>$salelists['username']))->find();
				if(!$user)
				{
					$result['error']="结算系统会员不存在";
					return $result;
				}
				$nowlv=$user['会员级别'];
				$newlv=$nowlv;
				$saleupdata=array(
					'userid'	=>$salelists['username'],
					
					'productCountMoney' =>$sales['user_price'],
					
					'payCountMoney' =>$sales['user_pv'],
				);
				$levels =X('levels@会员级别');
				$lv_cons	= $levels->getcon('con',array('lv'=>0,'pvmoney'=>0),true);
				foreach($lv_cons as $lv_con){
					if($nowlv==$lv_con['lv']){
						$oldprice=$lv_con['pvmoney'];
					}
					if($sales['user_pv']>=$lv_con['pvmoney'] && $nowlv<$lv_con['lv']){
						$newlv=$lv_con['lv'];
						$backprice=$oldprice;
					}
				}
				if($newlv>$nowlv){
					$result["lv"]=$newlv;
					$result["backmoney"]=$backprice;
					$saleupdata['lv']=$newlv;
					$productlist=array();
					foreach($sales['order_list'] as $product){
						$productlist[] = array(
							'产品id'=>	$product['id'],
							'名称'	=>	$product['名称'],
							'数量'	=>	$product['数量'],
							'价格'	=>	$product["商城价"],
							'PV'	=>	$product['PV'],
							'总价'	=>	$product['数量'] * $product["商城价"],
							'总PV'	=>	$product['数量'] * $product['PV']
						);
					}
					
					$saleupdata['product']=$productlist;
					$return=X("sale_up@商城升级")->upsave($saleupdata);
					if(gettype($return)=='string')
					{
						$result['error']=$return;
					}else{
						$result['status']=true;
					}
				}else{
					$result['error']="当前级别大于等于报单级别";
					return $result;
				}
			}
			return $result;
		}
		function buysale($salelists){
			$result['status']=false;
			if(!$salelists['username']){
				$result['error']="未知会员";
				return $result;
			}
			$user=M("会员")->where(array("编号"=>$salelists['username']))->find();
			if(!$user)
			{
				$result['error']="结算系统会员不存在";
				return $result;
			}
			foreach($salelists['saledata'] as $key=>$sales){
				$salebuydata=array(
					'userid'	=>$salelists['username'],
					'折扣'		=> '1',
					'productCountMoney' =>$sales['user_price'],
					'payCountMoney' =>$sales['user_pv']
				);
				$productlist=array();
				foreach($sales['order_list'] as $product){
					$productlist[] = array(
						'产品id'=>	$product['id'],
						'名称'	=>	$product['名称'],
						'数量'	=>	$product['数量'],
						'价格'	=>	$product["商城价"],
						'PV'	=>	$product['PV'],
						'总价'	=>	$product['数量'] * $product["商城价"],
						'总PV'	=>	$product['数量'] * $product['PV']
					);
				}
				$salebuydata['product']=$productlist;
				$return = X("sale_buy@购物单")->buy($salebuydata);
				if(gettype($return)=='string')
				{
					$result['error']=$return;
					return $result;
					break;
				}else{
					$result['status']=true;
				}
			}
			return $result;
		}
		//获取会员信息
		public function getuserinfo($postdata){
			//获取会员编号
			$username=$postdata['username'];
			if($username){
				$userinfo=M("会员")->where(array("编号"=>$username))->find();
				if($userinfo){
					$data['userid']=$userinfo['编号'];
					foreach(self::$fieldRelations as $fkey=>$fieldRelation){
						$data[$fkey]=$userinfo[$fieldRelation];
					}
					$result['status']=true;
					$result['userinfo']=$data;
				}else{
					$result['error']="会员不存在";
				}
			}
			return $result;
		}
		//获取结算中的货币信息
		public static function getbank($postdata,$getdata){
			//获取会员编号
			dump($postdata);dump($getdata);die;
			$username=$postdata['username'];
			if($username){
				$userinfo=M("会员")->where(array("编号"=>$username))->find();
				if($userinfo){
					if($userinfo['抱团编号']!=""){
						$userinfo["消费基金"]=0;
					}
					if($_REQUEST['look']==1 && $_REQUEST['look']){
						$userinfo["消费基金"]=0;
					}
					if($userinfo["消费基金"]>0){
						/*计算系统扣除，转到商城系统*/
						bankset("消费基金",$userinfo['编号'],-$userinfo["消费基金"],$userinfo['编号']."结算系统转商城系统",$userinfo['编号']."结算系统中所得消费积分".$userinfo['消费基金']."转入商城系统",$userinfo['编号']);
					}
					/*返回商城系统货币金额*/
					$result=array("status"=>true,"money"=>0,"money1"=>$userinfo["消费基金"]);
				}else{
					$result=array("status"=>false,"money"=>0,"money1"=>0,"error"=>"会员不存在");
				}
			}
			return $result;
		}
		public function getbanks(&$postdata){
			//获取会员编号
			$username=$postdata['username'];
			$money=$postdata['getmoney'];
			if($username){
				$userinfo=M("会员")->where(array("编号"=>$username))->find();
				if($userinfo){
					if($userinfo["电子币"]>$money){
						//计算系统扣除，转到商城系统
						bankset("电子币",$userinfo['编号'],-$money,$userinfo['编号']."结算系统转商城系统",$userinfo['编号']."结算系统中电子币".$money."转入商城系统",$userinfo['编号']);
						$result=array("status"=>true,"money"=>$money);
					}else{
						$result=array("status"=>false,"money"=>0,"error"=>"电子币余额不足");
					}
				}else{
					$result=array("status"=>false,"money"=>0,"error"=>"会员不存在");
				}
			}
			unset($userinfo);
			return $result;
		}
		public function getmoney($postdata){
			//获得  会员编号  货币名称  货币数量
			$username=$postdata['username'];
			if($username){
				$userinfo=M("会员")->where(array("编号"=>$username))->find();
				if($userinfo){
					$result=array('status'=>true,"money"=>$userinfo['电子币']);
				}else{
					$result=array('status'=>false,"money"=>0,"error"=>"会员不存在");
				}
			}
			return $result;
		}
		public function edituser($editinfo){
			foreach(self::$fieldRelations as $key=>$fieldRelation){
				if($editinfo[$key]){
					if($fieldRelation=="pass1"){
						$data[$fieldRelation]=md100($editinfo[$key]);
					}else{
						$data[$fieldRelation]=$editinfo[$key];
					}
				}
			}
			$user=M("会员");
			$sqlresult=$user->where(array("编号"=>$editinfo['username']))->save($data);
			$result=array("status"=>true,"error"=>"");
			if(!$result){
				$result['error']="未修改数据";
			}
			return $result;
		}
		public function editpass($passinfo){
			$result=array("status"=>true,"error"=>"");
			$data["pass1"]=md100($passinfo['password']);
			M("会员")->where(array("编号"=>$passinfo['usename']))->save($data);
			return $result;
		}
		public function clock($data){
			$result=array("status"=>false,"error"=>"");
			if($data['username']){
				$userinfo=M("会员")->where(array("编号"=>$data['username']))->find();
				if($userinfo){
					M("会员")->where(array("编号"=>$data['username']))->save(array("登陆锁定"=>$data['clock_status']));
					$result['status']=true;
				}else{
					$result['error']="未找到会员信息";
				}
			}else{
				$result['error']="未得到会员编号";
			}
			return $result;
		}
		public function cleandb(){
			R('DmsAdmin://Admin/SyncDmsAdminAdmin/clearSystemData');
			M()->startTrans();
			CONFIG('TIMEMOVE_DAY' ,0);
			CONFIG('TIMEMOVE_HOUR',0);
			CONFIG('CAL_START_TIME',strtotime(date('Y-m-d',time())));
			M()->commit();
			$model = M();
			$model->execute('truncate table `log'.'`');
			$model->execute('truncate table `dms_邮件'.'`');
			$model->execute('truncate table `dms_公告'.'`');
			$model->execute('truncate table `dms_短信'.'`');
			$model->execute('truncate table `dms_短信详细'.'`');
			$model->execute('truncate table `dms_短语'.'`');
			$this->saveAdminLog('','',"清空数据库");
			return true;
		}
	}
?>