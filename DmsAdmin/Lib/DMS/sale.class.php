<?php
	class sale extends stru
	{
		//节点名
		public $name='';
		//多语言标签名
		public $l_name='';
		//级别名
		public $lvName='会员级别';
		//是否可用
		public $use=true;
		//是否自动调动升级
		public $levelUp = false;
		//使用者
		public $user='';
		//是否允许设置报单金额
		public $setMoney=false;
		//是否允许设置单数
		public $setNumber = false;
		//报单金额,-1表示不启用
		public $money = -1;
		//报单单数,-1表示不启用
		public $num = -1;
		//报单pvmoney,-1表示不启用
		public $pvmoney = -1;
		//报单完成是否生效
		public $confirm =true;
		//审核货币
		public $accBank="";
		//是否自己设置货币比率
		public $showRatio=false;
		//空点模式
		public $nullMode=false;
		//来源编号名
		public $fromNoName="";
		//来源编号的条件
		public $fromNoWhere="";
		//是否允许选填来源编号
		public $fromNoinnull=false;
		//不用填写的情况下默认当前登录会员的哪个字段是服务中心
		public $fromNo="";
		//是否使用二级密码
		public $pass2=false;
		//是否使用三级密码
		public $pass3=false;
		//扣款内容  如(购物金额*90%+购物pv*0.1+报单金额+报单pv*10)
		public $accBy="";
		//是否选产品
		public $productName = "";
		//是否算入总账业绩
		public $salePay=false;//开启订单完成时跳转到支付页面
		// 产品使用的价格
		public $productMoney = '价格';
		// 是否增加产品PV
		public $productPV = false;
		// 显示菜单条件
		public $dispWhere = '';
		//confirm为false时。前台订单审核是否能够看到和审核此订单
		public $useracc = true;
		public $extra = false;		//是否在填写物流
		public $template='';
		public $accstr="编号";
		public $ledger  ='报单金额';  //表示此订单
		public $adminAccDeduct=true;//默认后台审核扣款
		public $deduct_acc=1;//默认后台升级不扣货币 显示
		public $point=0;//默认后台升级产生业绩  显示
		public $onlyMsg='';//只能提交一次的提示
		public $logistic=false;//开启物流费
		//服务中心验证
		public function fromVerify(&$ret,$data_post=array()){
			//如果设置了店铺
		    if($this->fromNoName !='') {
		    	//如果存在会员
				if(X('user')->have('')){
					//根据店铺编号取得会员
				    $shopinfo=M("会员")->where(array("编号"=>trim($data_post['shop'])))->find();
				    //如果没有店铺存在,则提示
				    if(!$shopinfo)
				    {
		      			$ret[]   = array('shop','',$this->fromNoName.'不存在',2);
					}
					//如果店铺不符合条件,则提示
					elseif(!transform($this->fromNoWhere,$shopinfo))
			        {
                        $ret[]   = array('shop','',$this->fromNoName.'不符合条件',2);
			        }
				}
			}
			if($this->showRatio){
				$ret[] = array('accval',array(X("accbank@".$this->accBank),"checkratio"),"请重新设置货币比例",1,'function');
			}
			return $ret;
		}
		//替换字符串内的数据支持M[] 以及[]
		/*
			用法  placestr("M[sss] S[234234]",array(),array("M"=>array(),"S"=>array()))
				  placestr("[sss] [234234]",array())
		*/
		public function placestr($form,$data=array(),$bydata=array()){
			if($form=="")
				return $form;
			$form=str_replace(">>","<",$form);
			$preg_str=array();
			/*
			正则字符串缓存，如果参数中含有原生的正则判断代码，
			那么正则表达式当中可能存在中括号内容，会被进行替换识别
			所以需要预先识别并替换成临时字符标签
			*/			
			if(strpos($form,'preg_match') !== false)
			{
				preg_match_all('/preg_match\(.+\) = 1/Uis',$form,$pregform,PREG_SET_ORDER);
				foreach($pregform as $key=>$preg)
				{
					$preg_str[$key]=$preg[0];
					$form = str_replace($preg[0],'{$preg'.$key.'}',$form);
				}
			}
			preg_match_all('/(?<!\$_REQUEST)(?<!\$_POST)(?<!\$_GET)([A-Z]?)\[(.*)\]/Uis',$form,$trform,PREG_SET_ORDER);
			//循环[]字段替换值
			foreach($trform as $val)
			{
				//判断是否有M或者其他值作为主键
				if($val[1]=='')
				{
					$arrname='$data';
					$replaceData=&$data;
				}
				else
				{
					//有特殊主键判断是否有值
					if(!isset($bydata[$val[1]]))
					{
						throw_exception('placestr运行失败，处理判定'.$val[0].'时,不存在'.$val[1].'设定项');
					}
					$replaceData=&$bydata[$val[1]];
					$arrname='$bydata[\''.$val[1].'\']';
				}
				if(!isset($replaceData[$val[2]]))
				{
					throw_exception('transform运行失败，需要的替换项目未找到['.$val[2].']');
				}
				//替换数据
				$data_value=$arrname.'[\''.$val[2].'\']';
				eval('$data_value=('.$data_value.');');
				$form=str_replace($val[0],"".$data_value,$form);
			}
			//正则字符串缓存反替换
			if(count($preg_str)>0)
			{
				foreach($preg_str as $key=>$preg)
				{
					$form = str_replace('{$preg'.$key.'}',$preg,$form);
				}
			}
			//返回字符串
			return $form;
		}
		//lock配置中的验证条件
		//此函数由sale_reg,sale_buy,sale_up类中的getValidate函数进行调用,获取自定义条件判定
		public function lockconVerify(&$ret,$data){
			/*
			  根据订单表中传进来的data数组,得到的会员表记录,以及订单表记录
			  对两个记录进行了合并,用于transform判断
			*/
			if(!isset($data['udata']['编号']))
				return true;
			$checkary=array();
			//获取lock节点
			$lockcons = $this->getcon('lock',array('where'=>'','msg'=>'','state'=>'lockcon'));
			foreach($lockcons as $lockcon){
				//判断lock的where 条件
				if(transform($lockcon['where'],array(),array("M"=>$data['udata'],"S"=>$data['sdata']))){
					//判断成功,state配置项作为要显示器判定内容的表单名称.做提示输出.
					$wheredata=array("M"=>$data['udata'],"S"=>$data['sdata']);
					$lockcon['msg'] = $this->placestr($lockcon['msg'],array(),$wheredata);
					//伪造页面表单数据
					if(!isset($_POST[$lockcon['state']]))
						$_POST[$lockcon['state']]=$lockcon['state'];
					//如果未设置$lockcon['state']的值 默认为lokcon 并且默认提交的数据
					if($lockcon['state']=='lockcon' && !isset($_POST['lockcon'])){
						$_POST['lockcon']='lockcon';
					}
					if(in_array($lockcon['state'],$checkary))
					{
						continue;
					}
					//返回提示lock的提示信息
					$ret[] = array($lockcon['state'],'',$lockcon['msg'],1);
					$checkary[]=$lockcon['state'];
				}
			}
		}
        /*
        * 服务中心编号 注册人编号 
        */
		public function getFromInfo(&$sdata,&$data,$user=array()){
			$sdata['服务中心编号']='';
		    //如果订单为注册.需要对订单赋予注册人编号
			if(get_class($this) == 'sale_reg'){
				if($this->user !='admin'){
                    //判断注册人编号 如果是前台会员注册 记录当前会员 如果是前台推广注册记录注册的会员
					$sdata["注册人编号"]=isset($_SESSION[C('USER_AUTH_NUM')])?$_SESSION[C('USER_AUTH_NUM')]:$sdata["编号"];
				}else{
					$sdata["注册人编号"]=$_SESSION["loginAdminAccount"];
				}
			}
			//需要填写服务中心
			if($this->fromNoName !='' && isset($data['shop'])){
				if(trim($data['shop'])!='')
				{
					//默认编号和数据库一致
					$shopuser=M('会员')->where(array('编号'=>trim($data['shop'])))->field('编号')->find();
					if($shopuser){
						$sdata["服务中心编号"]     = $shopuser['编号'];
					}
				}
			}
			else
			{
				if(get_class($this) != 'sale_reg')
				{
					//如果没有填写服务中心,那么除了注册以外,全部以会员的服务中心编号作为订单的服务中心编号
					$sdata["服务中心编号"]     = $user['服务中心编号'];
				}
				else
				{
					//如果是前台注册,需要服务中心,但是不开放填写,则以当前会员作为服务中心编号
					if($this->user !='admin')
					{
						//不需要填并且有默认字段的情况下
						$curuser=M("会员")->where(array('编号'=>$_SESSION[C('USER_AUTH_NUM')]))->find();
						if($curuser && $this->fromNo!='' && $this->fromNoName=='')
							$sdata["服务中心编号"]  = $curuser[$this->fromNo];
					}
				}
			}
			return $sdata;
		}
		
		// 处理扣款操作
		public function accbank(&$sale,$data=array(),$user,$adminacc=false){
			//审核的订单
			if(isset($sale['id']) && $sale['报单状态']!='未确认')
				return true;
			//后台审核并且不扣款的
			if($adminacc && !$this->adminAccDeduct)
				return true;
			//如果后台升级，并且选择了不扣货币，也则直接退出
			if(get_class($this)=='sale_up'  && $this->user=='admin' && isset($data['deduct_acc']) && $data['deduct_acc'] != 0)
			{
				return true;
			}
			//不需要扣币的或者没设置扣款人字段
			if($this->accBank == '' || $this->accstr=='')
			{
				return true;
			}
			//扣款
			$accbank=X("accbank@".$this->accBank);
			if($accbank){
				$result=$accbank->accok($sale,$data,$user,$this,$adminacc);
			}else{
				throw_exception("扣款方式不存在");
			}
			return $result;
		}
		//取得实际要支付的金额
		public function getPayMoney($dataarray =array(),&$sdata,$userarray=array()){
			/*
			  如果是前台并且不允许填写编号,则使用当前登入的会员编号
			  否则使用post提交的当前会员编号
			*/
			if($this->user !='admin' && $this->lockMe)
				$map=array('id'=>$_SESSION[C('USER_AUTH_KEY')]);
			else
				$map=array('编号'=>$dataarray['userid']);
			$thisuser = M('会员')->where($map)->find();
			if(!$thisuser){
				//如果是注册的，一般按照级别算折扣，这时需要赋值级别
				if(get_class($this)=='sale_reg') $userarray[$this->lvName]=isset($dataarray['lv'])?$dataarray['lv']:1;
				$thisuser=$userarray;
			}
			$discount=$this->getDiscount($thisuser);
			$sdata['折扣']=$discount;
			$data=array();
			isset($sdata['购物金额']) && $data['购物金额']=$sdata['购物金额']*$discount;
			$data['实付款']=$sdata['实付款']*$discount;
			//如果没有设置accBy,则系统自动设置报单金额
			if(!$this->accBy){
				$accMoney = ($this->productName) ? $data['购物金额'] : $data['实付款'];
			}else{
				$accMoney = transform($this->accBy,$sdata);
			}
			return $accMoney;
		}
		
		//不同会员条件选择产品的折扣不同
		public function getDiscount($user){
			//设定原始折扣为全折
			$discount=1;
			//载入折扣设置
			$discountset=$this->getcon('discount',array('where'=>'','val'=>'1'));
			if(count($discountset)>=1 && !$this->lockMe)
			{
				//$this->error('在使用折扣设定时，sale的lockMe属性需要开启');
			}
			foreach($discountset as $c)
			{
				if(transform($c['where'],$user))
				{
					$discount = transform($c['val']);
				}
			}
			return $discount;
		}
		
		//订单被审核或者订单报单直接生效时需要进行的处理
		public function runconfirm($user,$sale,$product){
			if($sale['报单状态']=="未确认"){
				return ;
			}
			//处理sale节点中的_addval的行为
			$this->salerunadd($user,$sale);
			//处理sale节点中的_update的行为
			$this->runupdate($user,$sale);
			//减去产品数量
			if($product){
				foreach($product as $k=>$productdata){
					X('product@'.$this->productName)->stock($productdata['产品id'],$productdata['数量']);
				}
			}
			//判定是否要做自动升级处理
			if($this->levelUp)
			{
				$levels=X('levels');
				foreach($levels as $level)
				{
					//秒结触发升级时，传入当前时间
					$level->uplv('confirm',systemTime(),$sale['id']);
				}
			}
			//获取内存设置的值
			if((int)ini_get('memory_limit')<(int)'1000M'){
				ini_set('memory_limit','1500M');
			}
			//调用秒结处理
			foreach(X('tle') as $tle)
			{
				$tle->scal($sale);
			}
		}
		/*
		* -------内部函数.处理数值增加
		* 去除now 使用isnull  0 执行实点 1执行空点 2空点完成后执行
		*/
		public function salerunadd($user,$sale){
			//addval节点
			$addcon=$this->getcon("addval",array("from"=>"","to"=>"",'set'=>0,"isnull"=>0,"where"=>''),true);	
			foreach($addcon as $k=>$v)
			{
				$v['saleid']=$sale['id'];
				if(transform($v['where'],array(),array('M'=>$user,'S'=>$sale)))
				{
					//默认来源
					if($v['from'] == ""){
						$v['from'] = "1";
					}
					$num=transform($v['from'],array_merge($user,$sale));
					//默认不执行
					$run=false;
					//空单执行节点
					if($v['isnull']==1 && $sale['回填']==1 && $sale['报单状态']!="已确认"){
						$run=true;
					}
					//空单回填完成执行
					if($v['isnull']==2 && $sale['回填']==1 && $sale['报单状态']=="已确认"){
						$run=true;
					}
					//非空单或回填单执行的节点来源
					if($v['isnull']==0 && $sale['回填']==0){
						$run=true;
					}
					if(!$run){
						continue;
					}
					//执行addval的节点name值
					$to=$v['to'];
					//判断执行增加的val值的参数是否为数字
					if(!is_numeric($num))
					{
						throw_exception("在处理".$this->name."时ADDVAL的期间发现增加值不为数字参数为:".htmlentities($v['from'],ENT_COMPAT ,'UTF-8'));
					}
					if(strpos($to,'_') === false)
					{
						runadd($user,$num,$to,$v);
					}
					else
					{
						$toarr = explode('_',$to);
						if($toarr[0] == 'shop'){
							list($fromuser,$fromnum)=explode('_',$sale['来源编号']);
							if($fromuser != 'admin'){
								$fuser=M("会员")->where(array("编号"=>$fromnum))->find();
								runadd($fuser,$num,$toarr[1],$v);
							}
						}else{
							runadd($user,$num,$to,$v);
						}
					}
				}
			}
			unset($addcon);
		}
		/*
		*	对其标签下的update
		*/
		public function runupdate($udata,$sdata){
			$updatecon=$this->getcon("update",array('set'=>'',"isnull"=>0,'where'=>'','to'=>'编号'));
			if(!empty($updatecon)){
				foreach($updatecon as $k=>$v)
				{
					//默认不执行
					$run=false;
					//实单
					if($v['isnull']==0 && $sdata['回填']==0){
						$run=true;
					}
					if($v['isnull']==1 && $sdata['回填']==1 && $sdata['报单状态']!='已确认'){
						$run=true;
					}
					//空单 需要回填完成才可以执行
					if($v['isnull']==2 && $sdata['回填']==1 && $sdata['报单状态']=='已确认'){
						$run=true;
					}
					if(!$run){
						continue ;
					}
					//条件判断
					if(transform($v['where'],array(),array('M'=>$udata,'S'=>$sdata)))
					{
						//更新字段
						$set = $v['set'];
						$sql = "update dms_会员 set " . $set . " where 编号= '".$udata[$v['to']]."'";
						$rs=M()->execute($sql);
						if($rs===false){
							throw_exception("在处理".$this->name."时UPDATE出现错误：".htmlentities($sql,ENT_COMPAT ,'UTF-8'));
						}
					}
				}
				unset($updatecon);
			}
			return ;
		}
		//回填奖金发放的货币扣除 用来回填报单
		public static function event_commit(){
			//获取未回填成的报单
			$sales=M("报单")->where(array("报单状态"=>"回填"))->select();
			if(empty($sales))
				return;
			foreach($sales as $sale){
				$num=0;
				//获取回填奖金的金额
				$user=M("会员")->where(array("编号"=>$sale['编号']))->field("id,编号,".X("prize_backfill@")->name."结转")->find();
				//判断目前剩下需要回填的金额
				if((string)$user[X("prize_backfill@")->name."结转"]>=(string)($sale['回填金额']-$sale['已回金额'])){
					$num=($sale['回填金额']-$sale['已回金额']);
				}else{
					$num=$user[X("prize_backfill@")->name."结转"];
				}
				//增加回填的金额
				$sale['已回金额']+=$num;
				//判断订单是否已回填完成
				if($sale['已回金额']==$sale['回填金额']){
					//回填完成处理数据
					$data["回填"]	 	= 1;
					$data['回填日期']	= systemTime();
					$data['实付款']		= $sale['回填金额'];//订单的实付金额
					$data['已回金额']	= $sale['已回金额'];//已回金额累计
					//修改会员状态
					M("会员")->where(array("编号"=>$sale['编号']))->save(array("空点"=>0));
					//审核订单
					$sale['报单状态']="未确认";
					
					//审核订单
					$saleobj=X('sale_*@'.$sale['报单类别']);
					$saleobj->accstr="编号";
					$result=$saleobj->accok($sale,true);
				}else{
					M("报单")->where(array('id'=>$sale['id']))->save($sale);
				}
				//处理回填奖金的结转问题
				$user[X("prize_backfill@")->name."结转"]-=$num;
				M("会员")->save($user);
				unset($sale);
			}
			return;
		}

		//转实单操作
		public function applyok($saledata,$accbank){
			//回填扣除金额的货币的名称
			if($accbank!="" && !X("fun_bank@".$accbank))
				return "未设置扣款货币";
			$this->adminAccDeduct=false;
			//更新申请记录
			M("申请回填")->where(array("id"=>$saledata['pid']))->save(array("申请状态"=>'已审核',"审核日期"=>systemTime()));
			if($saledata['转正方式']=="回填转正"){
				//将订单设置为回填单
				$data['报单状态']='回填';$data['回填']=1;
				M("报单")->where(array('id'=>$saledata['id']))->save(array("报单状态"=>"回填"));
				return true;
			}else if($saledata['转正方式']=="立即转正"){
				//将订单设置为未确认  并进行审核
				$saledata['报单状态']='未确认';
				$data['报单状态']='未确认';$data['回填']=1;$data['回填日期']=systemTime();
				$num=$saledata['回填金额']-$saledata['已回金额'];
				//是否需要扣除 实付款金额
				if($accbank!=""){
					$saledata['实付款']=$num;//实际扣除金额
				}
				$data['实付款']=$saledata['回填金额'];//订单的实付金额
				$data['已回金额']=$saledata['回填金额'];//已回金额累计
				
				if($accbank!=""){
					//获取会员金额
					$money=M("货币")->where(array("userid"=>$saledata['userid']))->getField($accbank);
					
					if((string)$num>(string)$money){
						return $accbank."不足";
					}
					bankset($accbank,$saledata['编号'],-($saledata['回填金额']-$saledata['已回金额']),"回填转正扣款","订单回填转正，扣除".$num);
				}
				$this->accstr="编号";
				M("会员")->where(array("编号"=>$saledata['编号']))->save(array("空点"=>0));
				//保存订单的相关回填信息
				M("报单")->where(array('id'=>$saledata['id']))->save($data);
				//回填单相关审核进业绩
				$result=$this->accok($saledata,true);
				return $result;
			}
		}
		//订单审核
		public function accok($sdata,$isAdmin=false){
			if($sdata['报单状态']!='未确认')
			{
				return '订单不可被审核！';
			}
			//获取订单的会员信息
			$udata=M('会员')->where(array('编号'=>$sdata['编号']))->find();
			// 订单审核扣款
			$return = $this->accbank($sdata,'',$udata,$isAdmin);
			if($return !== true){
				return $return;
			}
			//判断区域代理
			$areabool=false;
			if (get_class($this)=='sale_up') {
				$levels=X("levels@".$this->lvName);
				if($levels->area){
					$areabool=true;
					$dataary=array("country1"=>$sdata['代理国家'],"province1"=>$sdata['代理省份'],"city1"=>$sdata['代理城市'],"county1"=>$sdata['代理地区'],"town1"=>$sdata['代理街道']);
					if(!$this->areaHave($sdata['升级数据'],$dataary))
						return "该会员选择的区域代理人数已达上限";
				}
			}
			//验证产品数量
			$product=array();
			if($sdata['产品']==1){
				//选出所有产品
				$product=M("产品订单")->where(array("报单id"=>$sdata['id']))->select();
				if($product){
					foreach($product as $k=>$productdata){
						if($k==0)$proobj=X("product@".$productdata['产品节点']);
						$checkstr=$proobj->checknum($productdata['产品id'],$productdata['数量']);
						if($checkstr!='') return $checkstr;
					}
				}
			}
			$sdata['到款日期']=systemTime();
			$sdata['报单状态']='已确认';
			$rs=M('报单')->save($sdata);
			if ($rs)
			{ 
				$udata=M('会员')->where(array('编号'=>$sdata['编号']))->find();
				if (get_class($this)=='sale_reg')
				{
					$updata=array();
					$udata['状态']='有效';
					$udata['审核日期']=systemTime();
					$updata['状态']='有效';
					$updata['审核日期']=systemTime();
					$urs=M('会员')->where(array("编号"=>$udata['编号']))->save($updata);
					if ($urs)
					{
						X('user')->callevent("user_verify",array("user"=>$udata));
					} else {
						return "执行".$this->name."更新".X('user')->name.'表数据时错误';
					}
				} elseif (get_class($this)=='sale_up') {
					$updata=array();
					if($udata[$this->lvName]<$sdata['升级数据']){
						$updata[$this->lvName]=$sdata['升级数据'];
						$udata[$this->lvName] =$sdata['升级数据'];
					}
					//更新会员的代理信息
					if($areabool){
						$area=$levels->getAreanum($sdata['升级数据']);
						if($area>=1){
							$updata['代理国家']=$sdata['代理国家'];$udata['代理国家']=$sdata['代理国家'];
							$updata['代理省份']=$sdata['代理省份'];$udata['代理省份']=$sdata['代理省份'];
							$updata['代理城市']=$sdata['代理城市'];$udata['代理城市']=$sdata['代理城市'];
							$updata['代理地区']=$sdata['代理地区'];$udata['代理地区']=$sdata['代理地区'];
							$updata['代理街道']=$sdata['代理街道'];$udata['代理街道']=$sdata['代理街道'];
						}
					}
					if(!empty($updata)){
						$uprs=M('会员')->where("编号='".$udata['编号']."'")->save($updata);
						if (!$uprs){
							return "执行".$this->name."更新".X('user')->name.'表数据时错误';
						}
					}
				}
				//执行订单审核后的处理
				$this->runconfirm($udata,$sdata,$product);
			} else {
				return "执行".$this->name."更新".X('user')->name.'_报单表数据时错误';
			}
			return true;
		}
		
		public function validateInt($number){
			
			if(intval($number) != $number){
				return false;
			}
			if($number<=0){
				return false;
			}
			return true;
		}
		
		//删除订单退款操作
		public function backAccMoney($saledata){
			
			if($saledata['付款人编号']=='' )
				return ;
			if($saledata['accokstr']=="")
				return ;
			//根据付款的配置退款
			$accbankary=json_decode($saledata['accokstr'],true);
			foreach($accbankary as $bankname=>$bankmoney){
				bankset($bankname,$saledata['付款人编号'],$bankmoney,"订单删除回退","订单删除回退付款金额".$bankmoney,$saledata['编号']);
			}
			return;
		}
		//会员被删除触发的事件
		public function event_userdelete($user){
			//所有该会员的订单
			$saledatas = M("报单")->where(array("编号"=>$user["编号"]))->select();
			if(isset($saledatas))
			foreach($saledatas as $saledata){
				$this->delete($saledata,true);
			}
		}
	 	/*
		 	订单删除
		 	订单删除，可直接由订单删除调用,也可能由删除会员触发，
		 	@saledata 删除订单的信息
		 	@force    强制删除，会忽略升级订单的先后顺序问题，直接做删除操作
		 	          （因为会员都已经要被删除了）
		 	          注：如果要删除升级订单，必须从后向前删除
		 	          如删除2级升3级的升级订单之前，要先删除3级升4级的订单
		 	          否则无法处理降级
	 	*/
		public function delete($saledata,$force=false){
			//不过没有传入过会员信息,则给予信息
			$sale=X('sale_*@'.$saledata['报单类别']);
			if(get_class($sale)=='sale_up' && $force)
			{
				//需要做级别判断，但是由于时间关系后期开发
				//处理级别反操作，但是由于时间关系后期开发
			}
			if($saledata['报单状态'] == '已确认'){
				X('user')->callevent('sale_delete',array('sale'=>$saledata));
				$sale->backAccMoney($saledata);
			}
			M("报单")->where(array("id"=>$saledata["id"]))->delete();
			if($saledata['产品']==1){
				M("产品订单")->where(array("报单id"=>$saledata["id"]))->delete();
			}
			return true;
		}
		
		//处理xset,在前后台报单期间,对其他对象的属性做临时调整
		public function runxset()
		{
			$xsets = $this->getcon('xset',array('name'=>''),true);
			foreach($xsets as $xset)
			{
				$obj =X('@'.$xset['name']);
				foreach($xset as $arrname=>$attval)
				{
					if($arrname != 'name')
					{
						$obj->xset($arrname,$attval);
					}
				}
			}
		}

	}
?>