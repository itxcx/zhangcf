<?php
	class sale_up extends sale
	{
		//是否锁定报单人编号为自己,不允许填写报单人编号
		public $lockMe  =true;
		//可升级级别
		public $lvLock = "";
		//升级是否计算差额
		public $diff = true;
		//是否填写物流信息
		public $extra = false;
		//判断会员升级是否要使用产生业绩
		public $unuse=false;
		//此订单扣谁的钱
		public $accstr='编号';
		public $accview='';//审核人（谁能看到此订单）
		public $selLv ='';
		/*获得MODEL校验数组*/
		public function getValidate($data_post=array())
		{
			$ret = array();
			//获得USER
			$user=$this->parent();
			//当需要手动填写会员编号时
			$ret[] = array('userid','require','编号不能为空！',1);
			$ret[] = array('userid',array($user,"have"),"您的编号不存在",2,'function');
			$ret[] = array('userid',array($this,"haveNull"),"您已经有未审核的申请存在，请不要重复提交",2,'function');
			
			//!!校验lv是否为空
			$ret[] = array('lv','require','未填写级别信息！',1);
			$ret[] = array('lv',array($this,"areaNull"),"请选择级别对应的代理地区",2,'function',3,array($data_post));
			$ret[] = array('lv',array($this,"areaHave"),"您选择的区域代理人数已达上限",2,'function',3,array($data_post));
			//产品
			if($this->productName){
				$productObj = X('product@'.$this->productName);
				$productObj -> formVerify($ret,$this);
			}
			//对级别参数的有效性验证
			$iflv = array();
			foreach($this->getLvOption() as $opt)
			{
				$iflv[]=$opt['lv'];
			}
			$ret[] = array('lv',$iflv,L('level_illicit'),2,'in');			
			//对级别升级范围的验证
			$ret[] = array('lv',array($this,"v_iflv"),'此'.$user->name.'已经达到或者超过此级别,不能升级！',2,'function',3,array(trim($data_post['userid'])));
			//lock配置中的验证条件验证
			$this->lockconVerify($ret,$this->getUpdata($data_post));
			if($this->fromNoName!=''){
				$ret[] = array('shop','require',L($this->fromNoName.'不能为空'),1);
			}
			$this->fromVerify($ret,$data_post);	//服务中心验证
			//回填或空点激活，需先回填完成再升级
			if($this->lvName==X("sale_reg@")->lvName){
				//$ret[] = array('lv',array($this,"is_usernull"),'会员为空点，请先转正',2,'function',3,array(trim($data_post['userid'])));
			}
			$m=M();
			$m->setProperty("patchValidate",true);
			$m->setProperty("_validate",$ret);
			if($m->create() === false){
				$error = $m->getError();
			}else{
				$error = null;
			}
			return array('error'=>$error);
		}
		//判断是否是空点会员
		public function is_usernull($lv,$userid){
			if(!isset($userid) || $userid == '')
				return false;
			$null=M("会员")->where(array("编号"=>$userid))->getField("空点");
			if($null==1){//回填或空点激活
				if(adminshow('admin_backfill') || (adminshow('admin_blank') && (adminshow('admin_bank_backfill') || adminshow('user_bank_backfill'))))
				return false;
			}
			return true;
		}
		//处理表单判断当中的级别升级验证
		public function v_iflv($lv,$userid)
		{
			if(!isset($userid) || $userid == '')
				return true;
			$user   =M('会员')->lock(true)->where(array('编号'=>$userid))->find();
			if(!$user)
			return true;
			//第一个注册的sale_reg级别
			$name1=X("sale_reg@")->lvName;
			if($user[$this->lvName] >= $lv)
			{
				if($this->user!='admin') return false;
				//默认第一种级别不能随便降级
				elseif($name1==$this->lvName) return false;
			}
			unset($user);
		}
		public function haveNull($para=null){
			$where=array();
			if($para !==null){
				if(is_string($para))
				{
					$para=trim($para);
					if($para!='')
					$where['编号']=$para;
				}else
				{
					$where['userid']=$para;
				}
			}
			$where['报单类别']=$this->name;
			$where['报单状态']='未确认';
			//查询
			if(count($where)!=0)
			{
				$rs = M("报单")->where($where)->find();
			}
			if($rs)
			{
				return false;
			}else{
				return true;
			}
		}
		//验证区域代理填写
		public function areaNull($lv,$data){
			$levels =X('levels@'.$this->lvName);
			if($levels->area){
				foreach($levels->getcon("con",array("area"=>"","lv"=>0)) as $lvconf){
					if($lvconf['lv']==$lv && $lvconf['area']!='' && $data[$lvconf['area']."1"]=='') return false;
				}
			}
			return true;
		}
		//判断代理存在
		public function areaHave($lv,$data){
			$levels =X('levels@'.$this->lvName);
			if($levels->area){
				foreach($levels->getcon("con",array("area"=>"","lv"=>0,"only"=>0)) as $lvconf){
					if($lvconf['lv']==$lv && $lvconf['area']!='' && $lvconf['only']>0){
						switch($lvconf['area']){
							case 'country':$where=array("代理国家"=>$data['country1']);break;
							case 'province':$where=array("代理国家"=>$data['country1'],"代理省份"=>$data['province1']);break;
							case 'city':$where=array("代理国家"=>$data['country1'],"代理省份"=>$data['province1'],"代理城市"=>$data['city1']);break;
							case 'county':$where=array("代理国家"=>$data['country1'],"代理省份"=>$data['province1'],"代理城市"=>$data['city1'],"代理地区"=>$data['county1']);break;
							case 'town':$where=array("代理国家"=>$data['country1'],"代理省份"=>$data['province1'],"代理城市"=>$data['city1'],"代理地区"=>$data['county1'],"代理街道"=>$data['town1']);break;
						}
						$where[$this->lvName]=$lv;
						$have=M('会员')->where($where)->count();
						if(!$have) $have=0;
						if($have>=$lvconf['only']) return false;
					}
				}
			}
			return true;
		}
		public function upSave($option=array())
		{
			M("会员")->where('id<0')->lock(true)->count();
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
			$levels =X('levels@'.$this->lvName);
			$updata = $this->getUpdata($option);
			//得到的会员信息
			$udata   = $updata['udata'];
			$sdata   = $updata['sdata'];
			$upudata = $updata['upudata'];
			$lvlog   = $updata['lvlog'];
			/*付款审核*/
			$accret=$this->accbank($sdata,$option,$udata);
			
			if($accret!==true)
			{
				return $accret;
			}
			/*付款审核完成*/
			$m_sale=M("报单");
			$sdata["id"] = $m_sale->add($sdata);
			$product = $updata['productdata'];
			if($product){
				foreach($product as $k=>$productdata){
					$productdata['报单id'] = $sdata["id"];
					M('产品订单')->add($productdata);
				}
			}
			$confirm = isset($option['confirm']) ? $option['confirm'] : $this->confirm;
			if($confirm)
			{
				//增加升级记录
				//为升级记录增加saleid
				$lvlog['saleid'] = $sdata["id"];
				//为升级记录增加adminid
				if($this->user == 'admin')
				{
					$lvlog['adminid']=$_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ];
				}
				M('lvlog')->add($lvlog);
	            M('会员')->save($upudata);
				$this->runconfirm($udata,$sdata,$product);
			}
			$return=array();
			$return['saleid'] = $sdata["id"];
			$return['userid'] = $udata['编号'];
			return $return;
		}
		//获得升级的必要信息作为数组
		/*
			udata   完整会员信息
			upudata 用于更新会员信息（进行局部数据更新，避免发生更新冲突）
			sdata   用于插入订单的信息
			productdata 用于产品的信息
		*/
		public function getUpdata($option=array()){
			$userid=trim($option['userid']);
			if($this->lockMe && $this->user !='admin'){
				$udata = M("会员")->where(array("编号"=>$_SESSION[C('USER_AUTH_NUM')]))->find();
			}else{
				$udata = M('会员')->where(array('编号'=>$userid))->find();
			}
			$confirm = isset($option['confirm']) ? $option['confirm'] : $this->confirm;
			$sdata=array();
			$sdata["购买日期"]    =systemTime();
			$sdata['编号']        =$udata['编号'];
			$sdata['userid']      =$udata['id'];
			$sdata['报单类别']    =$this->name;
			$sdata["byname"]      =$this->byname;
			
			$this->getFromInfo($sdata,$option,$udata);
			$lv=(int)$option['lv'];
			$levels =X('levels@'.$this->lvName);
			$sdata["old_lv"] =$udata[$levels->name];
			$sdata["升级数据"]    =$lv;
			//生成升级日志
			
			//设置升级日志
			$lvlog =null;
			if($confirm)
			{
				//如果立即要处理升级，则设置升级日志
				$lvlog =array(
					'lvname'=>$this->lvName,
					'userid'=>$udata['id'],
					'username'=>$udata['编号'],
					'time'=>systemTime(),
					'olv'=>$udata[$levels->name],
					'nlv'=>  $lv,
					'saleid'=> 0,
					'adminid'=>0,
				);
				$sdata["报单状态"]='已确认';
				$sdata["到款日期"]=systemTime();
				$udata[$levels->name]=$lv;
			}
			else
			{
				$sdata["报单状态"]='未确认';
			}

			if($this->extra==true){
				isset($option['country']) && $sdata['收货国家']=$option['country'];
				isset($option['province']) && $sdata['收货省份']=$option['province'];
				isset($option['city']) && $sdata['收货城市']=$option['city'];
				isset($option['county']) && $sdata['收货地区']=$option['county'];
				isset($option['town']) && $sdata['收货街道']=$option['town'];
				isset($option['reciver']) && $sdata['收货人']  =$option['reciver'];
				isset($option['address']) && $sdata['收货地址']=$option['address'];
				isset($option['mobile']) && $sdata['联系电话']=$option['mobile'];
			}else{
				$sdata['收货国家']=$udata['国家'];
				$sdata['收货省份']=$udata['省份'];
				$sdata['收货城市']=$udata['城市'];
				$sdata['收货地区']=$udata['地区'];
				$sdata['收货街道']=$udata['街道'];
				$sdata['收货人']  =$udata['收货人'];
				$sdata['收货地址']=$udata['地址'];
				$sdata['联系电话']=$udata['移动电话'];
			}
			
			$money=$this->getSaleMoneys($option);
			$sdata["报单金额"]    =$money['pvmoney'];
			$sdata["报单单数"]    =$money['num'];
			$sdata["实付款"]      =$money['money'];
				
			//产品
			$sdata['物流费']      =0;
		    if($this->productName){
				$productObj = X('product@'.$this->productName);
				if($productObj){
					$productdata = $productObj -> setField($sdata,$option['productNum'],$this);
				}else{
					//根据提交的产品数据形成订单
					$productdata = $option['product'];
					$sdata['购物金额']=$option['productCountMoney'];
					$sdata['产品'] =1;
				}
			}
			$paymoney	     = $this->getPayMoney($option,$sdata);
			$sdata["accbank"]="";$sdata["accokstr"]="";
			//生成支付的数据
			if(isset($option['paycons'])){
				$accbankObj = X("accbank@".$this->accBank);
				if(isset($accbankObj)){
					$sdata["accbank"]=$accbankObj->makejson($option['paycons']);
				}
			}
			$sdata["实付款"] = $paymoney;
			$productdata = isset($productdata) ? $productdata : null;
			//不扣款
			if(isset($option['deduct_acc']) && $option['deduct_acc']!=0){
				$sdata["实付款"]=0;
			}
			//不累计业绩
			if(isset($option['point']) && $option['point']!=0){
				$sdata["报单金额"]    =0;
				$sdata["报单单数"]    =0;
			}
			//回填
			if($this->user=='admin' && isset($option['backFill']) && $option['backFill']==1){
				$sdata["报单状态"]='回填';
				$sdata["回填金额"]=$paymoney+$sdata['物流费'];
				$sdata["回填"]=1;
				$sdata["实付款"]=0;
			}
			$upudata = array();
			$upudata['id'] = $udata['id'];
			$upudata[$levels->name] = $udata[$levels->name];
			//区域代理
			if($levels->area){
				if(!isset($option['city1'])) $option['city1']='';
				if(!isset($option['county1'])) $option['county1']='';
				if(!isset($option['town1'])) $option['town1']='';
				foreach($levels->getcon("con",array("area"=>"","lv"=>0)) as $lvconf){
					if($lvconf['lv']==$lv){
						if($confirm){//直接更新会员的代理情况
							if($lvconf['area']!=''){
								$upudata['代理国家']=$option['country1'];
								$upudata['代理省份']=$option['province1'];
								$upudata['代理城市']=$option['city1'];
								$upudata['代理地区']=$option['county1'];
								$upudata['代理街道']=$option['town1'];
							}else{
								$upudata['代理国家']='';
								$upudata['代理省份']='';
								$upudata['代理城市']='';
								$upudata['代理地区']='';
								$upudata['代理街道']='';
							}
							//udata传值以便秒结
							$udata['代理国家']=$upudata['代理国家'];
							$udata['代理省份']=$upudata['代理省份'];
							$udata['代理城市']=$upudata['代理城市'];
							$udata['代理地区']=$upudata['代理地区'];
							$udata['代理街道']=$upudata['代理街道'];
						}
						//先存入订单记录
						if($lvconf['area']!=''){
							$sdata['代理国家']=$option['country1'];
							$sdata['代理省份']=$option['province1'];
							$sdata['代理城市']=$option['city1'];
							$sdata['代理地区']=$option['county1'];
							$sdata['代理街道']=$option['town1'];
						}
						break;
					}
				}
			}
			return array(
				'udata'=>$udata,
				'upudata'=>$upudata,
				'sdata'=>$sdata,
				'productdata'=>$productdata,
				'lvlog'=>$lvlog
			);
		}
		//获得报单金额 报单pv 报单单数
		public function getSaleMoneys($option)
		{
			$ret=array();
			$userid = trim($option['userid']);
			$udata = M('会员')->where("编号='$userid'")->find();
			$oldlv = (int)$udata[$this->lvName];
			$newlv = (int)$option['lv'];
			$levels    = X('levels@'.$this->lvName);
            $old_level = $levels->getlevel($oldlv);
			$new_level = $levels->getlevel($newlv);
            $ret['pvmoney']= $this->diff ? $new_level['pvmoney'] - $old_level['pvmoney'] : $new_level['pvmoney'];
			$ret['money']  = $this->diff ? $new_level['money']   - $old_level['money']   : $new_level['money'];
			$ret['num'] = $this->diff ? $new_level['num']  - $old_level['num']  : $new_level['num'];
			return $ret;
		}
		//级别为最大级locakme='false'菜单不显示
		public function getMaxLv()
		{
			$levels =X('levels@'.$this->lvName);
			$lv_cons	= $levels->getcon('con',array('lv'=>0,'name'=>''));
			$maxlv	= 0;
			foreach($lv_cons as $key=>$lv_con)
			{
				$maxlv	= $maxlv<$lv_con['lv']?$lv_con['lv']:$maxlv;
			}
			return $maxlv;
		}
		//取得可升级的设定
		//参数为当前级别,输出的内容不能小于等于当前级别,0为不限
		public function getLvOption($thisLv=0)
		{
			$levels=X('levels@'.$this->lvName);
			$levelsopt=array();
			$setLvArr=explode(',',$this->selLv);
			foreach($levels->getcon("con",array("name"=>"","lv"=>0,'use'=>'')) as $opt)
			{
				if($opt['use']!='false' && $opt['lv']>$thisLv && ($this->selLv=='' || in_array($opt['lv'],$setLvArr)))
				{
					$levelsopt[]=array('lv'=>$opt['lv'],'name'=>L($opt['name']));
				}
			}
			return $levelsopt;
		}
		//取得可升级的设定
		public function getLvArea()
		{
			$levels=X('levels@'.$this->lvName);
			$area=array();
			$setLvArr=explode(',',$this->selLv);
			foreach($levels->getcon("con",array("name"=>"","lv"=>0,'use'=>'','area'=>'')) as $opt)
			{
				if($levels->area && $opt['area']!=''){
					$area[$opt['area']]=$opt['name'];
				}
			}
			return $area;
		}		
	}
?>