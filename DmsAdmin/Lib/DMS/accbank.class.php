<?php
	/*电子货币模块*/
	class accbank extends stru
	{
        
        
		public function accok(&$sale,$data=array(),$user,$saleobj,$adminacc=false){
			//直接生成的订单
			if(!isset($sale['id']) && ($sale['报单状态']=='未确认' || $sale['报单状态']=='空单' || $sale['报单状态']=='回填'))
				return true;
			//审核的订单
			if(isset($sale['id']) && $sale['报单状态']!='未确认')
				return true;
			//后台审核并且不扣款的
			if($adminacc && !$saleobj->adminAccDeduct) return true;
			//不是后台审核，则使用当前登入用户（这里前台审核单会列出当前登录会员所能审核的订单）
			if(!$adminacc && $saleobj->user!='admin'){
				$accuser = M('货币')->where(array("编号"=>$_SESSION[C('USER_AUTH_NUM')]))->find();//货币分离
			}else{
				$accuser = M('货币')->where(array("编号"=>$user[$saleobj->accstr]))->find();//货币分离
			}
			//实际支付金额
			$wuliu=isset($sale['物流费'])?$sale['物流费']:0;
			$paymoney = $sale['实付款']+$wuliu;
			$lastmoney=$paymoney;
			//获取订单中的配置信息
			!isset($sale['accbank']) && $sale['accbank']='';
			if($sale['accbank']!=""){
				$bankcons=json_decode($sale['accbank'],true);
			}else{
				$bankcons=$this->getcon("bank",array("name"=>"","minval"=>"0%","maxval"=>'100%',"extra"=>false),true);
				$sale["accbank"]=json_encode($bankcons);
			}
			//得到用于支付的配置文件节点有几个
			$bankconnum=0;
			foreach($bankcons as $key=>$bankcon){
				if($bankcon['extra']!=true){
					$bankconnum++;
				}
			}
			//实付款实际金额
			$truepay=0;
			//本次订单的扣除获得相对应金额
			$accokarr=array();$bkey=0;
			foreach($bankcons as $key=>$bankcon){
				//获取支付的最大与最小值
				$minmoney=getnum($paymoney,$bankcon['minval']);
				$maxmoney=getnum($paymoney,$bankcon['maxval']);
				
				if($bankcon['extra']==true){
					//在实付款外进行的扣除，不计入实付款
					//必须最大值和最小值相等
					if($minmoney-$maxmoney!=0){
						return L("使用".$bankcon['name']."支付时参数错误");
					}
					if((string)$minmoney + $truepay >(string)$accuser[$bankcon['name']]){
						return L('会员编号为'.$accuser['编号'].'的会员'.$bankcon['name'].'余额不足');
					}
					//实际支付金额
					$realpaymoney=$maxmoney;
					//扣款
					X("fun_bank@".$bankcon['name'])->set($accuser['编号'],$sale['编号'],-$realpaymoney,$saleobj->byname,X('user')->byname.'['.$sale['编号'].']'.$saleobj->byname.'花费'.$realpaymoney);
					$accokarr[$bankcon['name']]=$maxmoney;
				}else{
					$bkey++;
					if($bkey==$bankconnum){
						$minmoney=$lastmoney;
					}
					//判断钱包是否余额充足 并且剩余支付的金额小于钱包金额
					if((string)$minmoney >(string)$accuser[$bankcon['name']] && (string)$lastmoney>(string)$accuser[$bankcon['name']]){
						return L('会员编号为'.$accuser['编号'].'的会员'.$bankcon['name'].'余额不足');
					}else{
						
						//判断钱包可支付的最大金额
						if((string)$accuser[$bankcon['name']]<(string)$maxmoney){
							$maxmoney=$accuser[$bankcon['name']];
						}
						//剩余支付金额
						if((string)$lastmoney<(string)$maxmoney){
							$maxmoney=$lastmoney;
						}
						$realpaymoney=$maxmoney;
						$accuser[$bankcon['name']]-=$realpaymoney;
						//扣款
						X("fun_bank@".$bankcon['name'])->set($accuser['编号'],$sale['编号'],-$realpaymoney,$saleobj->byname,X('user')->byname.'['.$sale['编号'].']'.$saleobj->byname.'花费'.$realpaymoney);
						//实际支付
						$truepay+=$realpaymoney;
						$lastmoney-=$realpaymoney;
						$accokarr[$bankcon['name']]=$maxmoney;
					}
				}
				//扣除货币
				$accuser[$bankcon['name']]-=$realpaymoney;
			}
			$sale['付款人编号']  = $accuser['编号'];
			$sale['实付款'] = $truepay-$wuliu;
			$sale['accokstr'] = json_encode($accokarr);
			return true;
		}
		//生成json扣款数据
		public function makejson($setbank){
			$bankary=array();
			$bankcons=$this->getcon("bank",array("name"=>"","minval"=>"0%","maxval"=>'100%',"extra"=>false),true);
			if(!isset($setbank) || $setbank==""){
				$bankjson=json_encode($bankcons);
			}else{
				foreach($bankcons as $bankcon){
					if(isset($setbank[$bankcon['name']])){
						$bankcon['minval']=$setbank[$bankcon['name']];
						$bankcon['maxval']=$setbank[$bankcon['name']];
						$bankary[]=$bankcon;
					}
				}
				$bankjson=json_encode($bankary);
			}
			return $bankjson;
		}
		//生成扣款配置比例
		public function checkratio($accary){
			$ratio=0;
			$bankcons=$this->getcon("bank",array("name"=>"","minval"=>"0%","maxval"=>'100%',"extra"=>false),true);
			//循环增加
			foreach($accary as $akey=>$accratio){
				if($bankcons[$akey]['extra']==false)
					$ratio+=$accratio;
			}
			if($ratio<>100)
			{	
				return false;
			}
			else
			{	
				return true;
			}	
		}
	}
?>