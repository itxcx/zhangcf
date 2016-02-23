<?php
	class user extends stru
	{
		//会员为自动生成编号
		public $idAutoEdit=true;
		//会员编号在注册时是否可以人工修改
		public $idEdit=true;
		// 密码机密函数
		public $enPass='';
		//会员编号前缀
		public $idPrefix="cn";
		//会员编号长度(不包含前缀长度)
		public $idLength=6;
		//编号与前缀之间是否增加日期
		public $idInDate=false;
		//在手工输入编号的时候的正则验证
		public $idExp="";
		//在手工输入编号校验失败时的提示信息
		//public $idExpMsg="";
		//序号 自动并且不随机生成编号时存储的下一个编号
		public $idSerial;
		public $idYmd;
		//编号是否为随机模式
		public $idRand = true;
		//会员未审核是否可以登陆
		public $unaccLog=false;  
		//电话号码唯一设置
		public $onlyMobile = 0;
		//证件号码唯一设置
		public $onlyIdCard = 0;
		//银行卡号唯一设置
		public $onlyBankCard = 0;
		//每个会员是否都要插入到销售奖金表中
		public $allInTle=false;
		//注册协议
		public $agreement = false;
		public $regAgreement = '';
		//此节点是否存在服务中心功能
		public $shopWhere = "[服务中心]=1";
		public $userMenu = array();
		public $userMenuPower = array();
		public $userNoSecPwd = array();
		public $changePwdsmsSwitch=0;
		public $changePwdsmsContent='';
		public $regsmsSwitch=0;
		public $regsmsContent='';
		public $tradeMoney='';
		//默认必须有升级
		public $noup=false;
		public $truth='';
		public $userNoSecPwd3='';
		//模板快捷菜单设置
		public $userShortcutMenu='';
		//数值长度以及小数位
		public $decimalLon = 14;
		public $decimalLen = 2;
		//取得会员的自动会员编号
		public function getnewid()
		{
			//如果自动生成编号未开启
			if(!$this->idAutoEdit){
				return NULL;
			}
			//含有时间前缀
			if($this->idInDate)
			{
				if($this->getatt("idYmd")!=date("ymd")||$this->getatt("idYmd")==null)
				{
					$this->setatt("idYmd",date("ymd"));
					$this->setatt("idSerial",1);
				}
			}
			//随机会员编号
			if($this->idRand)
			{
                if($this->idLength>8){
                    $str='';
                    $i=0;
                    do{
                        $str.=(string)mt_rand(10000000,99999999);
                        $i+=8;
                    }while($this->idLength>$i);
                    $ret=substr($str,0,$this->idLength);
                }else{
                    $ret = (string)mt_rand(0,pow(10,$this->idLength));
                }
			}
			else
			{
				$ret = $this->getatt("idSerial");
				if($ret==null)$ret=1;
				$this->setatt("idSerial",$ret+1);
                if($this->idLength>0)
				$ret=str_pad($ret,$this->idLength,"0",STR_PAD_LEFT);
			}
			if($this->idInDate)
			$ret=$this->getatt("idYmd").$ret;
			$ret=$this->idPrefix.$ret;
			if($this->idRand && $this->idLength){
				$result = M('会员')->lock(true)->where(array('编号'=>$ret))->find();
				if($result){
					return $this->getnewid();
				}
			}
			return $ret;
		}
		//系统清空事件
        public function event_sysclear()
		{
            $model=M();
            $model->execute('SET FOREIGN_KEY_CHECKS = 0;');
			$model->execute('truncate table `dms_会员`');
            $model->execute('truncate table `dms_密保`');
			$model->execute('truncate table `dms_货币`');//货币分离
			$model->execute('truncate table `dms_log_user`');
			$model->execute('truncate table `dms_报单`');
			$model->execute('truncate table `dms_汇款通知'.'`');
			$model->execute('truncate table `dms_lvlog`');
			$model->execute('SET FOREIGN_KEY_CHECKS = 1;');
			if($this->tradeMoney!=""){
				$model->execute('truncate table `dms_'.$this->tradeMoney.'交易`');
			}
			if($this->haveProduct()){
				$model->execute('truncate table `dms_产品订单`');
			}
			$model->execute('truncate table `dms_申请回填'.'`');
			$model->execute('truncate table `dms_apipost'.'`');
			$model->execute('truncate table `dms_apiget'.'`');
		}
		public function nothave($para)
		{
			return !$this->have($para);
		}
		//判断编号是否存在
		public function have($para=null)
		{
			$where=array();
			if($para !==null){
				if(is_string($para))
				{
					if($para!='')
					$where['编号']=$para;
				}else
				{
					$where['id']=$para;
				}
			}
			
			$m_user=M('会员');
			if(count($where)!=0)
			{
			  $rs = $m_user->where($where)->lock(true)->find();
			}else{
			  $rs = $m_user->lock(true)->find();
			}
			if($rs)
			{
				return true;
			}else{
				return false;
			}
		}
		//取得会员编号
		public function getuser($para,$fromprize=false)
		{
			$m_user=M('会员');
			$where=array();
			if($para !='')
			{
				if(gettype($para) == "string")
				{
					$where['编号'] = trim($para);
				}else{
					$where['id']   = $para;
				}
			}
			$rs=$m_user->lock(true)->where($where)->find();
			return $rs;
		}
		//货币分离 获得货币信息
		public function getuser_h($para){
			$where=array();
			if($para!='')
			{
				if(gettype($para) == "string")
				{
					$where['编号'] = trim($para);
				}else{
					$where['userid']   = $para;
				}
			}
			$m_user=M('货币');
			$rs=$m_user->lock(true)->where($where)->find();
			return $rs;
		}

		/*
		* 包含网体下级的删除 找出网体下级的会员  循环删除，中间如果有会员未被删除，那么最终执行是一部分被删除
		* 删除错误原因除了delete()函数中的原来，还有就是网体关系的  上下级  这边的上级可能是那边的下级 那么需要移动网体后再删除
		*/
		public function down_delete($userid){
			//记录删除的会员编号
			$deluname=array();
			//判断会员是否在系统中
			$user = M("会员")->where(array("id"=>$userid))->find();
			if(!$user){
				return "系统不存在";
			}
			//形成sql语句条件 根据网体net_节点生成查询网体下级的sql语句
			foreach(X("net_*") as $net){
				$sqlwhere.=$sqlwhere!=""?" or ":"";
				//net_rec的语句
				if(get_class($net)=="net_rec"){
					$sqlwhere.="(".$net->name."_网体数据 like '".($user[$net->name.'_网体数据'] ? $user[$net->name.'_网体数据'].',' : '').$user['id'].",%' or ".$net->name."_上级编号='".$user['编号']."')";
				}else{
					//net_place的语句
					$sqlwhere.=$net->name . "_网体数据 like '".($user[$net->name.'_网体数据'] ? $user[$net->name.'_网体数据'].',' : '').$user['id']."-%' or {$net->name}_上级编号='".$user['编号']."'";
				}
				//找出net_place和net_rec中最下面的会员判断语句
				if(get_class($net)=="net_place"){
					foreach($net->getBranch() as $key=>$Branch){
						$brabchwhere.=$brabchwhere!=""?" and ":"";
						$brabchwhere.=$net->name."_".$Branch."区=''";
					}
				}else{
					$brabchwhere.=$brabchwhere!=""?" and ":"";
					$brabchwhere.="编号 not in (select {$net->name}_上级编号 from dms_会员)";
				}
			}
			//最终语句生成
			$sqlwhere=$sqlwhere==""?"1!=1":$sqlwhere;$brabchwhere=$brabchwhere==""?"1!=1":$brabchwhere;
			$sql="select id,编号 from dms_会员 where (".$sqlwhere.") and ".$brabchwhere;
			//生成while循环查找删除
			$checkcontinue=true;
			while($checkcontinue){
				$delusers=array();
				//执行语句 查出结果
				$downusers=M()->query($sql);
				//循环删除数组中的会员
				$endcheck=true;
				foreach($downusers as $downuser){
					$result=$this->delete($downuser['id']);
					//删除成功记录所删除的会员编号用于最后的返回
					if($result==true){
						$delusers[]=$downuser;
						$deluname[]=$downuser['编号'];
					}
				}
				//判断是否还要继续查找删除  如果没有要删除的下级会员跳出循环
				if(!$downusers){
					$checkcontinue=false;
				}
				if(count($delusers)>0){
					$checkcontinue=true;
				}
				//清除已进行删除的会员 数组
				unset($downusers);
				unset($delusers);
			}
			//删除当前的会员
			$result=$this->delete($userid);
			if($result==true)
				$deluname[]=$user['编号'];
			return $deluname;
		}
		// 删除user
		public function delete($userid){
			$userInfo = M("会员")->where(array("id"=>$userid))->lock(true)->find();
			foreach(X('net_rec,net_place') as $net){
				if($net->getdown($userInfo,0,1)){
					return L('该'.$this->byname.'已有下级网体关系，不能删除');
				}
			}
			$userdata= M('会员')->where(array("id"=>$userid))->find();
			if(!$userdata){
				return L('要删除的'.$this->byname.'不存在');
			}
			//服务中心2014-1-25
			if($this->shopWhere){
				$fwuser=M('会员')->where(array("服务中心编号"=>$userdata['编号']))->find();
				if($fwuser && $userdata['服务中心']){
					return L('要删除的服务中心下已有'.$this->byname.'存在');
				}
			}
			//判断此会员是否参与结算
			if(!adminshow('deluser')) {
				$tles=X('tle');;//所有奖金表
				foreach($tles as $tle){
					$isjs=M($tle->name."总账")->where("datediff(from_unixtime(`计算日期`),from_unixtime('".$userdata['审核日期']."'))>=0")->find();
					if($isjs && $userdata['状态']=='有效') return L('已参与结算的'.$this->byname.'不能删除');
				}
			}
			$this->callevent('userdelete',array('user'=>$userdata));
			//货币分离
			M('货币')->where("userid = $userid")->delete();
			M('会员')->where("id = $userid")->delete();
			return true;
		}

		//删除无效会员
		public function deleteAllInvalidUser(){

			$users = M('会员')->where(array('状态'=>'无效'))->select();
			
			$num = 0;
			foreach($users as $userInfo){
				$havedown = false;
				foreach(X('net_rec,net_place') as $net){
					
					$downuser = $net->getdown($userInfo,0,0,"状态='有效'");
					if($downuser !== null){
						$havedown = true;
						break;
					}
				}
				if(!$havedown){
					$this->callevent('userdelete',array('user'=>$userInfo));
					M('会员')->where("id = {$userInfo['id']}")->delete();
					$num++;
				}
			}
			return $num;
		}
		//过滤缓存数组的数据
		public function filt($strary,$user){
			//替换缓存数组的值
			foreach($strary as $str){
				$result=X("@".$str)->event_getCache(0,$user);
				//如果返回的数据不是false或者true 那么需要替换成返回的数据
				if($result!==false && $result!==true){
					$user=$result;
				}
			}
			return $user;
		}
		public function isRegular($userid){
			$userinfo = M("会员")->where(array("编号"=>$userid))->lock(true)->find();
			if(!$userinfo || $userinfo['状态'] == '无效'){
				return false;
			}else{
				return true;
			}
		}
		
		//判断是否唯一
		public function checkOnly($val,$type)
		{
			//移动电话
			if($type == 'mobile')
			{
				
				$havenum=M('会员')->lock(true)->where(array('移动电话'=>$val))->count();
				if($havenum >= $this->onlyMobile)
				{
					return false;
				}
			}
			//证件号码
			if($type == 'idcard')
			{
				$havenum=M('会员')->lock(true)->where(array('证件号码'=>$val))->count();
				if($havenum >= $this->onlyIdCard)
				{
					return false;
				}
			}
			//证件号码
			if($type == 'bankcard')
			{
				$havenum=M('会员')->lock(true)->where(array('银行卡号'=>$val))->count();
				if($havenum >= $this->onlyBankCard)
				{
					return false;
				}
			}
			return true;
		}
		//判断真实性
		public function checkTruth($val,$type){//type和config里的value值一致
			$return=true;
			$val=trim($val);
			//开启才会验证
			$trutharr=explode(',',$this->getatt('truth'));
			if(in_array($type,$trutharr)){
				switch ($type)
				{
					case 'id_card':$return=$this->id_cardChecktrue($val);break;
				}
			}
			return $return;
		}

		//验证身份证
		public function id_cardChecktrue($id_card)
		{
			//长度验证--未包含行政区域和国外
		    if(!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $id_card)) return false;
		    //地区验证
			$vCity=array(11=>"北京",12=>"天津",13=>"河北",14=>"山西",15=>"内蒙古",21=>"辽宁",22=>"吉林",23=>"黑龙江",31=>"上海",32=>"江苏",33=>"浙江",34=>"安徽",35=>"福建",36=>"江西",37=>"山东",41=>"河南",42=>"湖北",43=>"湖南",44=>"广东",45=>"广西",46=>"海南",50=>"重庆",51=>"四川",52=>"贵州",53=>"云南",54=>"西藏",61=>"陕西",62=>"甘肃",63=>"青海",64=>"宁夏",65=>"新疆",71=>"台湾",81=>"香港",82=>"澳门",91=>"国外");
		    if(!array_key_exists(intval(substr($id_card,0,2)),$vCity))  return false;

			//长度
			$idCardLength = strlen($id_card); 
			if($idCardLength == 15){ 
				if(!checkdate(substr($id_card,8,2),substr($id_card,10,2),'19'.substr($id_card,6,2))){
					return false;
				} 
				$idcard2 = substr($id_card,0,6)."19".substr($id_card,6,9);//15to18 
				$Bit18 = $this->getVerifyBit($idcard2);//算出第18位校验码 
				$idcard2 = $idcard2.$Bit18; 
			} 
			else{
				$idcard2=$id_card;
			}
			// 判断是否大于今年，小于1900年 
			$newyear=date('Y',time());
			$year = substr($idcard2,6,4); 
			if ($year<1910 || $year>$newyear ){ 
				return false;
			} 
			else{
				//18位身份证处理
				//月、日、年
				if(!(checkdate(substr($idcard2,10,2),substr($idcard2,12,2),substr($idcard2,6,4)))){
					return false;
				} 
				//身份证编码规范验证 
				$idcard_base = substr($idcard2,0,17); 
				if(strtoupper(substr($idcard2,17,1)) != $this->getVerifyBit($idcard_base)){
					return false;
				} 
			}

			return true;
		}
		public function getVerifyBit($idcard_base){ 
			if(strlen($idcard_base) != 17){ 
				return false; 
			} 
			//加权因子 
			$factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2); 
			//校验码对应值 
			$verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4','3', '2'); 
			$checksum = 0; 
			for ($i = 0; $i < strlen($idcard_base); $i++){ 
				$checksum += substr($idcard_base, $i, 1) * $factor[$i]; 
			} 
			$mod = $checksum % 11; 
			$verify_number = $verify_number_list[$mod]; 
			return $verify_number; 
		} 
		//判定是否存在产品
		public function haveProduct()
		{
			if(X('product'))
			{
				return true;
			}
			return false;
		}
		//返回是否存在需要审核的订单
		public function haveConfirm()
		{
			$sales = X('sale_*');
			foreach($sales as $sale)
			{
				if($sale->confirm === false)
				{
					return true;
				}
			}
			return false;
		}
		//返回是否存在需要审核的注册订单
		public function haveNoregConfirm()
		{
			$sales = X('sale_reg');
			foreach($sales as $sale)
			{
				if($sale->confirm === false)
				{
					return true;
				}
			}
			return false;
		}
		//是否只存在注册订单
		public function RegOnly()
		{
			$sales = X('sale_up,sale_buy');
			return (count($sales) == 0);
		}
		//是否有服务中心
		public function haveShop()
		{
			return true;
		}
		//是否有升级
		public function haveUp()
		{
			$sales = X('sale_up');
			foreach($sales as $sale){
				if($sale->productName=='')return true;
			}
			return false;
		}
		//是否升级选产品
		public function haveProUp()
		{
			$sales = X('sale_up');
			foreach($sales as $sale){
				if($sale->productName!='')return true;
			}
			return false;
		}
		//是否含有区域代理
		public function haveAreaLv()
		{
			$levels = X('levels');
			foreach($levels as $level){
				if($level->area) 
					return true;
			}
			return false;
		}

		//是否含有折扣,根据此在product.html页面显示相关信息
		public function haveZhekou($sale){
			$discountset=$sale->getcon('discount',array('where'=>'','val'=>'1'));
			if(count($discountset)>=1){
				return true;
			}
			return false;
		}
		/*
		* 找出业绩排序指定区域的网体下的会员人数
		* 规则：$regionnum=1表示第一大区 找出所有的会员 循环数组 判断where条件，符合条件并且处于当前区域的上级人数加1
		* 业绩根据当前会员表的区域累计业绩进行降序排序后找出数组的KEYS数组，规定区域指的是KEYS数组中以$regionnum-1为主键的数值
		*/
		public function placeallusers($netname='管理',$regionnum=1,$where='',$con='',$endtime){
			//组成查询的字段字符串
			$rows='id keyid,id,'.$netname.'_网体数据,'.$netname.'_上级编号';
			if(preg_match_all('/(?<!\$_REQUEST)(?<!\$_POST)(?<!\$_GET)([UM]?)\[(.*)\]/Uis',$where,$trform,PREG_SET_ORDER))
			{
				foreach($trform as $val)
				{
					$rows.=','.$val[2];
				}
			}
			$Branchs=X("@".$netname)->getBranch();
			foreach($Branchs as $key=>$Branch){
				$rows.=",".$netname.'_'.$Branch."区累计业绩 as ".$Branch;
			}
			$users = M("会员")->where("审核日期<=".$endtime)->order("{$netname}_层数 desc")->getField($rows);
			//统计网体的下级符合条件的人数
			$newUsers=array();
			foreach($users as $user){
				if($user[$netname.'_上级编号']!=''){
					//网体数据作为数组循环查找上级
					$upids=explode(',',$user[$netname.'_网体数据']);
					foreach($upids as $upkey=>$upidstr){
						$upidary=explode('-',$upidstr);
						$upid=$upidary[0];//上级id  寻找上级会员信息
						if(!isset($users[$upid]))
							continue;
						$upuser=$users[$upid];
						//当前区域
						$region=$upidary[1];
						//业绩数组
						$netdata=array();
						foreach($Branchs as $Branch){
							$netdata[$Branch]=$upuser[$Branch];
						}
						//排序 获取到当前会员的固定区域
						arsort($netdata);
						//获取逐渐id
						$keys=array_keys($netdata);
						unset($netdata);
						//判断条件 上级和下级同时条件判断 并判断当前的会员是否在上级符合要求的区域内
						$wheredata=array('U'=>&$user,'M'=>&$upuser);
						if(transform($where,array(),$wheredata) && isset($keys[($regionnum-1)]) && $keys[($regionnum-1)]==$region){
							//判断是否已有记录
							if(isset($newUsers[$upid])){
								$newUsers[$upid]+=1;//人数增加
							}else{
								$newUsers[$upid]=1;
							}
						}
						unset($keys);
					}
				}
			}
			unset($users);
			//找出符合个数的会员id
			$str = '';
			foreach($newUsers as $key=>$newUsernum){
				if($con!=''){
					eval('$result=('.$newUsernum.$con.');');
					if($result){
						$str .=",'".$key."'";
					}
				}
				unset($newUsers[$key]);
			}
			unset($newUsers);
			return trim($str,',');
		}
		//含有符合（[会员级别]>=2）的团队数量要>2个
		//推荐,U[职称]>=2 and U[推荐_层数]-M[推荐_层数]>>=3,>=2 推荐网体两条线上三代以内都有 至少一人满足（职称>=2）的人
		public function allusers($netname='推荐',$where='',$con2='',$con3=""){
			$rows='id keyid,id,'.$netname.'_网体数据,'.$netname.'_上级编号';
			if(preg_match_all('/(?<!\$_REQUEST)(?<!\$_POST)(?<!\$_GET)([UM]?)\[(.*)\]/Uis',$where,$trform,PREG_SET_ORDER))
			{
				foreach($trform as $val)
				{
					$rows.=','.$val[2];
				}
			}
			//过滤条件，去掉M[]的
			/*$tmpwhere =preg_replace("/(\(M\[)[^\)]+\)/",'1 ',$where);
			$tmpwhere =str_replace('U','',$tmpwhere);
			$tmpwhere = delsign($tmpwhere);
			$users = M($this->name)->where($tmpwhere)->order("{$netname}_层数 desc")->getField($rows);*/
			$users = M($this->name)->order("{$netname}_层数 desc")->getField($rows);
			$newUsers=array();
            if($users){
              	foreach($users as $user){
				if($user[$netname.'_上级编号']!=''){
					$upids=explode(',',$user[$netname.'_网体数据']);
					foreach($upids as $upkey=>$upidstr){
						$upidary=explode('-',$upidstr);
						$upid=$upidary[0];
						$upuser=$users[$upid];
						
						$wheredata=array('U'=>&$user,'M'=>&$upuser);
						if(transform($where,array(),$wheredata)){
							if(!isset($upids[$upkey+1])){
								$nupid=$user['id'];
							}else{
								$nupidary=explode('-',$upids[$upkey+1]);
								$nupid=$nupidary[0];
							}
							if(isset($newUsers[$upid])){
								if(isset($newUsers[$upid][$nupid])){
									$newUsers[$upid][$nupid]+=1;
								}else{
									$newUsers[$upid][$nupid]=1;
								}
							}else{
								$newUsers[$upid][$nupid]=1;
							}
						}
					}
				}
			}
            }
		
			$str = '';
			foreach($newUsers as $key=>$newUser){
				if($con3!=''){
					foreach($newUser as $downkey=>$downnewUser){
						eval('$downresult=('.$downnewUser.$con3.');');
						if(!$downresult){
							unset($newUser[$downkey]);
						}
					}
				}
				eval('$result=('.count($newUser).$con2.');');
				if($result){
					$str .=",'".$key."'";
				}
			}
			unset($newUsers);
			return trim($str,',');
		}
		//写入会员操作日志  会员信息 ip 操作内容
		public function adduserlog($user,$ip,$content){
			$datalog['user_id']=$user['id'];
			$datalog['user_name']=$user['姓名'];
			$datalog['user_bh']=$user['编号'];
			$datalog['ip']=$ip;
			$datalog['content']=$content;;
			$datalog['create_time']=time();
			//获取会员的IP地址
			import("ORG.Net.IpLocation");
			$IpLocation				= new IpLocation("qqwry.dat");
			$loc					= $IpLocation->getlocation();
			$country				= mb_convert_encoding ($loc['country'] , 'UTF-8','GBK' );
			$area					= mb_convert_encoding ($loc['area'] , 'UTF-8','GBK' );
			$datalog['address']		= $country.$area;
			M('log_user')->add($datalog);
		}
		//更新记录奖金计算的prizelog日志
		/**
			判断上一天是否有记录 如果有记录直接将上期的记录更新到计算当天，然后最相应的数据进行更新
			没有上一天的记录 那么把会员表的编号id空点赋值过来 
		*/
		public function updatelog($caltime){
			//查询上期的记录  移动本期  如果没有上期的数据那么默认为默认值
			$getresult=M()->table("dms_会员 as user")->join("inner join (select * from dms_prizelog where 时间=".($caltime-86400).") prizelog on prizelog.userid=user.id")->where("审核日期<".($caltime+86400))->field("user.id uid")->select();
			//获取levels相关字段 根据会员信息设置默认值
			$levelstr="";
			foreach(X("levels") as $level){
				$levelstr.=",".$level->name;
			}
			if(!empty($getresult)){
				//获得表中的字段
				$fieldsarr=M("prizelog")->get_Property("fields");
				//剔除时间字段,因为在插入时,时间要为新的一天
				unset($fieldsarr[ array_search('时间',$fieldsarr)]);
				//删除主键字段
				unset($fieldsarr[0]);
				//删除THINK字段
				unset($fieldsarr['_autoinc']);
				unset($fieldsarr['_pk']);
				$fields=join(',',$fieldsarr);
				//插入上期已有数据
				$sql="insert into dms_prizelog ({$fields},时间) 
					select ".$fields.",{$caltime} from dms_prizelog as a where a.时间=({$caltime}-86400)
				";
				M()->execute($sql);
				//插入上期没有的会员数据
				$ids=M()->table("dms_会员 as user")->join("inner join (select * from dms_prizelog where 时间=".($caltime-86400).") prizelog on prizelog.userid=user.id")->where("审核日期<".($caltime+86400))->getField("user.id,user.id as uid");
				if(isset($ids))
					$ids=implode(',',$ids);
				//保存上期没有会员记录的会员
				$sql="insert into dms_prizelog (userid,编号,时间,空点{$levelstr}) 
					select id,编号,{$caltime},空点{$levelstr} from dms_会员 where 审核日期<({$caltime}+86400) and id not in (".$ids.")
				";
				M()->execute($sql);
			}else{
				//保存上期没有会员记录的会员
				$sql="insert into dms_prizelog (userid,编号,时间,空点{$levelstr}) select id,编号,{$caltime},空点{$levelstr} from dms_会员 where 审核日期<({$caltime}+86400)";
				M()->execute($sql);
			}
		}
		//
		public function event_modifyId($oldbh,$newbh)
		{ 
			M()->execute("update dms_会员 set 编号='{$newbh}' where 编号='{$oldbh}'");
			M()->execute("update dms_会员 set 服务中心编号='{$newbh}' where 服务中心编号='{$oldbh}'");
			M()->execute("update dms_会员 set 注册人编号='{$newbh}' where 注册人编号='{$oldbh}'");
			M()->execute("update dms_会员 set 关联账号='{$newbh}' where 关联账号='{$oldbh}'");
			//订单组
			M()->execute("update dms_报单 set 编号='{$newbh}'         where 编号='{$oldbh}'");
			M()->execute("update dms_报单 set 服务中心编号='{$newbh}' where 服务中心编号='{$oldbh}'");
			M()->execute("update dms_报单 set 注册人编号='{$newbh}'   where 注册人编号='{$oldbh}'");
			M()->execute("update dms_报单 set 付款人编号='{$newbh}'   where 付款人编号='{$oldbh}'");
			M()->execute("update dms_汇款通知 set 编号='{$newbh}'   where 编号='{$oldbh}'");
			//货币组
			M()->execute("update dms_货币     set 编号='{$newbh}' where 编号='{$oldbh}'");//货币分离
			M()->execute("update dms_prizelog set 编号='{$newbh}' where 编号='{$oldbh}'");//货币分离
			//电子币交易 
			if($this->tradeMoney != '')
			{
				M()->execute("update dms_".$this->tradeMoney."交易 set 编号='{$newbh}' where 编号='{$oldbh}'");
				M()->execute("update dms_".$this->tradeMoney."交易 set 购买编号='{$newbh}' where 购买编号='{$oldbh}'");
				M()->execute("update dms_修改日志 set 编号='{$newbh}' where 编号='{$oldbh}'");
			}
			//短信分组号码表
			M()->execute("update dms_号码 set 编号='{$newbh}' where 编号='{$oldbh}'");
			M()->execute("update dms_onlinepay set 编号='{$newbh}' where 编号='{$oldbh}'");
			//提现
			M()->execute("update dms_提现 set 编号='{$newbh}' where 编号='{$oldbh}'");
		}
	}
?>