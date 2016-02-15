<?php
//区域代理级差，支持订单和会员表
	class prize_areadiff extends prize
	{
		//产生类型
		public $prizeMode=1;
		//奖金来源表达式
		public $rowName = '';
		//奖金来源类型
		public $rowFrom = 1;
		//来源表条件
		public $where = '';
		//订单来源状态下的订单类别
		public $saleState = '已结算,已确认';
		//小数精度
		public $decimalLen = 2;
		//判断是否显示奖金构成
		public $isSee = true;
		//级差奖是否包含自身
		public $haveMe = false;
		//朝上返奖的人读取哪个区域
		public $userarea="";// ''会员表的省市区，'代理'会员表的代理省市区,'收货'订单表的收货省市区，'会员代理'表示，如果此会员也是代理，就读代理，否则读本身的
		//拿奖的人读取哪个区域
		public $uparea="代理";// ''|代理,会员表对应的省市区
		public $from=""; //默认以自己的区域朝上返，如果填写服务中心编号，则表示以服务中心的区域朝上返
		public $lvcache=array();
		function scal($sale)
		{
			if($this->rowFrom==0)
			{
				$this->cal();
			}
			else
			{
				if($this->where=="")
				{
					$this->where="id=".$sale["id"];
					$this->cal();
					$this->where="";
				}
				else
				{
					$otherwhere=$this->where;
					$this->where='('.$otherwhere.") and id=".$sale["id"];
					$this->cal();
					$this->where=$otherwhere;
				}
			}
		}
		
		function cal()
		{
			if(!$this->ifrun()) return;
			if($this->rowName == '')
			{
				throw_exception($this->name.'奖金模块的rowName没有设置');
			}
			if($this->lvName == '')
			{
				throw_exception($this->name.'奖金模块的lvName没有设置');
			}
			if($this->rowFrom==0 && $this->userarea=='收货'){
				throw_exception($this->name.'奖金模块的userarea设置错误,会员表没有【收货】区域选项');
			}
			//5种区域代理数字化
			$areaary=array("country"=>1,"province"=>2,"city"=>3,"county"=>4,"town"=>5);
			//获得奖金计算设置 级别对应的代理区域设定数组组合 如果级别没有相关设定的参数  那么最终得到空数组 提示报错 停止
			foreach(X("@".$this->lvName)->getcon('con',array("lv"=>1,"area"=>"")) as $lvcon){
				if($lvcon['area']!=""){
					$lvareas[$lvcon['lv']]=$lvcon['area'];
                    //判断是否uparea 为空 如果是空则读取会员表注册的区域 如果不为空则读取申请代理时的级别
                    if($this->uparea!=''){
                         if($lvcon['area']=="town")
    						$areastr['town']="{$this->uparea}国家,'-',{$this->uparea}省份,'-',{$this->uparea}城市,'-',{$this->uparea}地区,'-',{$this->uparea}街道";
    					if($lvcon['area']=="county")
    						$areastr['county']="{$this->uparea}国家,'-',{$this->uparea}省份,'-',{$this->uparea}城市,'-',{$this->uparea}地区";
    					if($lvcon['area']=="city")
    						$areastr['city']="{$this->uparea}国家,'-',{$this->uparea}省份,'-',{$this->uparea}城市";
    					if($lvcon['area']=="province")
    						$areastr['province']="{$this->uparea}国家,'-',{$this->uparea}省份";
    					if($lvcon['area']=="country")
    						$areastr['country']="{$this->uparea}国家";
                    }else{
                        if($lvcon['area']=="town")
    						$areastr['town']="国家,'-',省份,'-',城市,'-',地区,'-',街道";
    					if($lvcon['area']=="county")
    						$areastr['county']="国家,'-',省份,'-',城市,'-',地区";
    					if($lvcon['area']=="city")
    						$areastr['city']="国家,'-',省份,'-',城市";
    					if($lvcon['area']=="province")
    						$areastr['province']="国家,'-',省份";
    					if($lvcon['area']=="country")
    						$areastr['country']="国家";
                      }
				}
			}
			//代理区域的数组为空数组 报错
			if(!isset($areastr)){
				throw_exception("结算".$this->name."时".$this->lvName."没有设定相关的代理区域的area值");
			}
			//获得奖金节点中的con 节点
			$cons = $this->getcon('con',array("lv"=>0,"val"=>'',"area"=>"","where"=>''));
			//得到以lv为key的数组
			$realcon=array();
			foreach($cons as $con){
				$realcon[$con['lv']]['val']=$con['val'];
				$realcon[$con['lv']]['where']=$con['where'];
			}
			//获得user表的字段名称由于查询时使用*代替出错  所以找出所有的字段来
			$fieldsarr=M("会员")->get_Property("fields");
			unset($fieldsarr['_autoinc']);
			unset($fieldsarr['_pk']);
			$fields=join(',',$fieldsarr);
			unset($fieldsarr);
			//处理代理信息缓存
			foreach($cons as $con)
			{
				$lvusers=M('会员')->where($this->lvName.'='.$con['lv'])->getField("concat({$areastr[$lvareas[$con['lv']]]}) area,{$fields},{$this->name}比例");
				if(isset($lvusers))
					$this->lvcache[$con['lv']]=$lvusers;
			}
			//未找到代理会员 结算提示
			if(!$this->lvcache){
				calmsg('未找到'.$this->lvName.'代理会员','/Public/Images/ExtJSicons/bell_error.png');
				return ;
			}
			//从订单获取奖金来源
			if($this->rowFrom == 1)
			{
				$sales=$this->getsale($this->where,"*,$this->rowName as t_num");
   
				if(isset($sales)){
					foreach($sales as $sale)
					{
						$this->calculate($sale,$sale['userid'],$sale,$cons,$areaary,$realcon,$lvareas);
					}
				}
				unset($sales);
			}
			//从user中获取来源
			if($this->rowFrom == 0)
			{
				$users=$this->getuser($this->where,"*,$this->rowName as t_num");
				foreach($users as $user)
				{
					$this->calculate($user,$user['id'],null,$cons,$areaary,$realcon,$lvareas);
				}
				unset($users);
			}
			//清除内存
			unset($cons);unset($areaary);unset($areastr);
			$this->prizeUpdate();
		}
		public function calculate(&$from,$userid,$sale=null,&$cons,$areaary,$realcon,$lvareas)
		{
			if($this->rowFrom == 0){
				$user=$from;
			}else{
				$user   =M('会员')->find((int)$userid);
			}
   
			$curuser=$user;
			//如果直接给上级服务中心
			if($this->from!='') 
				$user=M('会员')->where(array("编号"=>$from[$this->from]))->find();
			if(!$user)
				return;
			$upusers=array();
            $upuser = array();
			//以会员的本身区域或者代理区域
			$realfrom=$user;
			$area=$this->userarea;
			//以订单的收货区域
			if($area=='收货')
				$realfrom=$from;
			$uparea=$this->uparea;
			//选出con里面area对应的上级
			foreach($cons as $con){
          
				$where=array();
				if($con['lv']<$user[$this->lvName]) 
					continue;//自己本身是代理了，肯定会找到自己，所以小于自己级别的拿不到级差
				if($this->from!='' && $user[$this->lvName]==$con['lv']){
					$upuser=$user;//直接给上级服务中心
				}else{
                    //判断产生奖金的人是否是读取会员表的注册国家地区还是会员表其他字段userarea
					//获取key值
                    if($this->userarea!=''){
                         if($lvareas[$con['lv']]=="town")
						$areakey=$user["{$this->userarea}国家"].'-'.$user["{$this->userarea}省份"].'-'.$user["{$this->userarea}城市"].'-'.$user["{$this->userarea}地区"].'-'.$user["{$this->userarea}街道"];
    					if($lvareas[$con['lv']]=="county")
    						$areakey=$user["{$this->userarea}国家"].'-'.$user["{$this->userarea}省份"].'-'.$user["{$this->userarea}城市"].'-'.$user["{$this->userarea}地区"];
    					if($lvareas[$con['lv']]=="city")
    						$areakey=$user["{$this->userarea}国家"].'-'.$user["{$this->userarea}省份"].'-'.$user["{$this->userarea}城市"];
    					if($lvareas[$con['lv']]=="province")
    						$areakey=$user["{$this->userarea}国家"].'-'.$user["{$this->userarea}省份"];
    					if($lvareas[$con['lv']]=="country")
    						$areakey=$user["{$this->userarea}国家"];
                    }else{
                        if($lvareas[$con['lv']]=="town")
						$areakey=$user["国家"].'-'.$user["省份"].'-'.$user["城市"].'-'.$user["地区"].'-'.$user["街道"];
    					if($lvareas[$con['lv']]=="county")
    						$areakey=$user["国家"].'-'.$user["省份"].'-'.$user["城市"].'-'.$user["地区"];
    					if($lvareas[$con['lv']]=="city")
    						$areakey=$user["国家"].'-'.$user["省份"].'-'.$user["城市"];
    					if($lvareas[$con['lv']]=="province")
    						$areakey=$user["国家"].'-'.$user["省份"];
    					if($lvareas[$con['lv']]=="country")
    						$areakey=$user["国家"];
                    }
					if(!isset($areakey))
						continue;
					//获取上级代理
					if(isset($this->lvcache[$con['lv']][$areakey]))
						$upuser=$this->lvcache[$con['lv']][$areakey];
                    else 
                        continue;
				}
				//如果找到的是自己，不参与计算的话
				if(isset($upuser)){
                if(isset($upuser['id']))
				if($upuser['id']==$user['id'] && !$this->haveMe) 
					continue;
					$upusers[$con['lv']]=$upuser;
				}
			}
			unset($cons);
			$oldcon = 0;
     
			if(isset($upusers))
			foreach($upusers as $lv=>$upuser){
                if(isset($upuser))
                {
                   	$con['val']=$realcon[$lv]['val'];
    				$con['where']=$realcon[$lv]['where'];
    				$wheredata=array('U'=>&$user,'M'=>&$upuser,'S'=>&$sale);
    				if(transform($con['where'],array(),$wheredata))
    				{
                        if(isset($upuser[$this->name.'比例']))
                        {
    					//得到最 终的奖金额
    					$prizenum=getnum($from['t_num'],$con['val']."-".$oldcon,$this->decimalLen,$upuser[$this->name.'比例']);
    					//增加奖金
    					$this->addprize($upuser,$prizenum,$curuser,substr($con['val'],-1,1) == '%'?$from['t_num'].'*（'.$con['val']."-".$oldcon.')':$con['val']."-".$oldcon);
                        
                        }
    				}
    				$oldcon=$con['val'];    
                }
			
			}
			unset($upusers);
		}
	}
?>