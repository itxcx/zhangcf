<?php
// 本类由系统自动生成，仅供测试用途
defined('APP_NAME') || die('不要非法操作哦!');
class FunBankAction extends CommonAction {
	//货币明细
	public function index(fun_bank $bank)
	{
		//类型
		$select=array();
		$typeary=M($bank->name."明细")->group("类型")->getField('id,类型');
		if($typeary)
		foreach($typeary as $type){
			$select[$type]=$type;
		}
        $user = M('货币');//货币分离
        $sum = $user ->sum($bank->name);
        $userid=I("get.userid/s");
        $setButton=array(// 底部操作按钮显示定义
			'充值'    =>array("class"=>"add"    ,"href"=>"__URL__/recharge:__XPATH__/userid/{$userid}","target"=>"navTab",'title'=>$bank->byname.'充值'),
			'设置'    =>array("class"=>"addMore","href"=>"__URL__/config:__XPATH__","target"=>"navTab",'title'=>'设置','icon'=>'/Public/Images/ExtJSicons/cog.png'),
	    );
	    //如果是豪华版的话 则有批量充值的功能
       $setButton['批量充值'] = array("class"=>"addMore","href"=>"__URL__/rechargepl:__XPATH__","target"=>"navTab","title"=>$bank->byname.'批量充值','icon'=>'/Public/Images/ExtJSicons/money_add.png');
        $list=new TableListAction($bank->name."明细");
		$list->table('dms_'.$bank->name."明细 a");
        $list->setButton = $setButton;       // 定义按钮显示
		$list->join('dms_会员 as b on a.编号=b.编号')->field('a.*,b.姓名');
        $list->where("a.删除=0")->order("a.时间 desc,a.id desc");
        $list->addshow("时间",array("row"=>"[时间]","css"=>"width:80px;","searchMode"=>"date","format"=>"time","order"=>"时间",'searchRow'=>'a.时间',"searchPosition"=>"top"));
        $list->addshow("编号",array("row"=>"[编号]","css"=>"width:50px;","searchMode"=>"text",'searchGet'=>'userid',"excelMode"=>"text","order"=>"a.编号","searchPosition"=>"top",'searchRow'=>'a.编号'));   
		$list->addshow("姓名",array("row"=>"[姓名]","css"=>"width:50px;","searchMode"=>"text","excelMode"=>"text","order"=>"b.姓名"));
        $list->addShow("来源",array("row"=>"[来源]","css"=>"width:50px;","searchMode"=>"text","excelMode"=>"text",'searchRow'=>'a.来源'));
        $list->addshow("金额",array("row"=>array(array($this,'myfun'),"[金额]"),"css"=>"width:50px;","searchMode"=>"num",'searchRow'=>'a.金额'));
        $list->addshow("余额",array("row"=>"[余额]","css"=>"width:50px;","searchMode"=>"num",'searchRow'=>'a.余额'));
        $list->addshow("类型",array("row"=>"[类型]","css"=>"width:50px;","searchMode"=>"text",'searchRow'=>'a.类型',"searchPosition"=>"top","searchSelect"=>$select));
        $list->addshow("备注",array("row"=>"[备注]","css"=>"width:100px;","searchMode"=>"text","css"=>"width:240px",'searchRow'=>'a.备注'));
        $this->assign('list',$list->getHtml()); 

        $this->display();
	}
	//转账列表
	function Zmoney(){
        //合成合并SQL
		$sqlm=M();
		$user=X("user");
        $list=new TableListAction("转账明细");
		$list->table('dms_转账明细 a');
		$list->join('dms_会员 as b on a.转出编号=b.编号')->field('a.*,b.姓名');
        if(isset($button)){
            $list->setButton = $button;
        }else{
            $list->setButton = array(
            '审核'=>array("class"=>"edit"  ,"href"=>"__URL__/givemoneyacc/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true"),
            '撤销'=>array("class"=>"delete","href"=>"__URL__/givemoneyunpage/id/{tl_id}","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"240"),
            '删除'=>array("class"=>"delete","href"=>"__URL__/givemoneydel/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true"),
            );
        }
		$list->order("a.id desc");
		$list->addshow("ID",array("row"=>"[id]","searchMode"=>"text","searchRow"=>"id")); 
        $list->addshow("状态",array("row"=>array(array(&$this,"mytobankFun"),"[状态]"),"searchRow"=>"a.状态","searchMode"=>"text","searchGet"=>"状态","searchPosition"=>"top","searchSelect"=>array("未审核"=>"未审核","已撤销"=>"已撤销","已审核"=>"已审核")));
        $list->addshow("转出编号",array("row"=>"[转出编号]","searchMode"=>"text","searchPosition"=>"top","searchGet"=>"userid","excelMode"=>"text","searchRow"=>"a.转出编号"));
        $list->addshow("转出货币",array("row"=>"[转出货币]","searchMode"=>"text","searchRow"=>"a.转出货币"));
        $list->addshow("转出金额",array("row"=>"[转出金额]","searchMode"=>"num","searchRow"=>"a.转出金额"));
        $list->addshow("手续费",array("row"=>"[手续费]","searchMode"=>"num","searchRow"=>"a.手续费"));
        $list->addshow("转入编号",array("row"=>"[转入编号]","searchMode"=>"text","searchPosition"=>"top","searchGet"=>"userid2","excelMode"=>"text","searchRow"=>"a.转入编号"));
        $list->addshow("转入货币",array("row"=>"[转入货币]","searchMode"=>"text","searchRow"=>"a.转入货币"));
        $list->addshow("转入金额",array("row"=>"[转入金额]","searchMode"=>"num","searchRow"=>"a.转入金额"));
        $list->addshow("转换比率",array("row"=>"[转换比率]","searchMode"=>"num","searchRow"=>"a.转换比率"));
        $list->addshow("申请时间",array("row"=>"[操作时间]","format"=>"time","searchMode"=>"date","searchRow"=>"a.操作时间"));
        $list->addshow("审核时间",array("row"=>"[审核时间]","format"=>"time","searchMode"=>"date","searchRow"=>"a.审核时间"));
        //$list->addshow("转出人姓名",array("row"=>"[姓名]","searchMode"=>"text","searchRow"=>"姓名","excelMode"=>"text","order"=>"b.姓名"));
        //导出独立EXCEL需要重新架构
        $this->assign('list',$list->getHtml());
        $this->display('index');
	}
	//转账撤销页面
	public function givemoneyunpage()
	{
		$ids=I("get.id/s");
		$ids=explode(',',$ids);
		$count=M('转账明细')->where(array('id'=>array('in',$ids),'状态'=>array('eq','未审核')))->count();
		$this->assign('count',$count);
		$this->assign('id',I("get.id/s"));
		$this->display();
	}
	//转账信息审核
	public function givemoneyacc(){
        $user=X('user');
		$succNum = 0;
		$errNum = 0; 
		$errMsg = '';
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
			$give = M("转账明细");
			M()->startTrans();
			$re = $give -> where(array("id"=>$id,"状态"=>"未审核"))->find();
			if(!$re)
			{
				$errNum++;
				$errMsg .= $id."：未找到记录<br/>";
				M()->rollback();
				continue;
			}
			if($re["状态"] == "已审核"){
				$errNum++;
				$errMsg .= $id."：已经通过审核<br/>";
				M()->rollback();
			}elseif($re["状态"] == "已撤销"){
				$errNum++;
				$errMsg .= $id."：已经撤销<br/>";
				M()->rollback();
			}else{
				$data["id"] =$id;
				$data["状态"] = '已审核';
				$data["审核时间"] = systemTime();
				$res = $give->save($data);
				$tobank=X("fun_bank@".$re['转入货币']);
				$tobank->set($re['转入编号'],$re['转出编号'],$re['转入金额'],'转账转入',$re["备注"]."(转自[".$re['转出编号']."]的{$re['转出货币']})");
				if($res){
					$this->saveAdminLog('','',I("get.id/s")."提现信息审核");
					$succNum++;
					M()->commit();
				} else{
					$errNum++;
					$errMsg .= $id."：审核失败<br/>";
					M()->rollback();
				}
			}
		}
		if($errNum !=0){
			$this->error("审核成功：".$succNum .'条记录；审核失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("审核成功：".$succNum .'条记录；');
		}
	}
	//转账信息撤销
	public function givemoneyunacc(){
		M()->startTrans();
		$succNum = 0;
		$errNum = 0; 
		$errMsg = '';
		$give = M("转账明细");
		foreach(explode(',',I("post.id/s")) as $id){
			if($id == '') continue;
            $re = $give -> where(array("id"=>$id,"状态"=>"未审核"))->find();
			if(!$re)
			{
				$errNum++;
				$errMsg .= $id."：未找到记录<br/>";
				continue;
			}            
			$bank=X('fun_bank@'.$re['转出货币']);
			if($re["状态"] == "已审核"){
				$errNum++;
				$errMsg .=$id . '：已经审核！<br/>';
				continue;
			}elseif($re["状态"] == "已撤销"){
				$errNum++;
				$errMsg .=$id . '：已经撤销！<br/>';
				continue;
			}else{
                $data["id"] =$id;
                $data["状态"] = '已撤销';
                $data["审核时间"] = systemTime();
                $data["撤销理由"] = I("post.memo/s");
                $res = $give->save($data);
                $bank->set($re["转出编号"],'管理员',$re["转出金额"],'撤销转账','撤销转账返还：'.$re["转出金额"]);
            }
            if($res){ 
				$this->saveAdminLog('','',$re['转出编号']."提现转账充值，返还转账所扣".$re["转出金额"]);
				$succNum++;
            } else{
                $errNum++;
				$errMsg .=$id . '：撤销失败<br/>';
            }
        }
        if($succNum>0){
        	M()->Commit();
        }
		if($errNum !=0){
			$this->error("撤销成功：".$succNum .'条记录；撤销失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("撤销成功：".$succNum .'条记录；');
		}
	}
    function myfun($str){
        if($str > 0){
            return '<font color="green">'.$str.'</font>';
        }else{
            return '<font color="red">'.$str.'</font>';
        }
    }
    	//  提现撤销审核
	public function allowBack_apply(fun_bank $bank)
	{
		//合成合并SQL
		$sqlm=M();
		$user=X('user');
        $list=new TableListAction('提现');
        $list->where("撤销申请!=0");
        if($button){
            $list->setButton = $button;
        }else{
            $list->setButton = array(
            	'同意'=>array("class"=>"edit","href"=>"__URL__/apply_aggree:__XPATH__/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true"),
            	'拒绝'=>array("class"=>"delete","href"=>"__URL__/apply_notaggree:__XPATH__/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true"),
            );
        }
		$list->order("id desc");
        $list->addshow("状态"    ,array("row"=>array(array(&$this,"mygetFun11"),"[撤销申请]"),"searchRow"=>"撤销申请","searchMode"=>"text","searchGet"=>"撤销申请","searchPosition"=>"top","searchSelect"=>array("未审核"=>"1","已同意"=>"2","已拒绝"=>"3")));  
        $list->addshow("货币名称",array("row"=>"[货币名称]","searchMode"=>"text","searchRow"=>"货币名称"));  
        $list->addshow("申请时间",array("row"=>"[操作时间]","format"=>"time","searchMode"=>"date","searchRow"=>"操作时间"));  
        $list->addshow("审核时间",array("row"=>"[审核时间]","format"=>"time","searchMode"=>"date","searchRow"=>"审核时间"));  
        $list->addshow($this->userobj->byname."编号",array("row"=>"[编号]","searchMode"=>"text","searchPosition"=>"top","searchGet"=>"userid","excelMode"=>"text","searchRow"=>"编号"));         
        $list->addshow("姓名"    ,array("row"=>"[姓名]","searchMode"=>"text","searchRow"=>"姓名","excelMode"=>"text","order"=>"b.姓名"));
        $list->addshow("提现额"  ,array("row"=>"[提现额]","searchMode"=>"num","searchRow"=>"提现额"));     
        $list->addshow("手续费"  ,array("row"=>"[手续费]","searchMode"=>"num","searchRow"=>"手续费"));   
		//if($bank->isShowRadio){
    	$list->addshow("实发"  ,array("row"=>"[换算后实发]","searchMode"=>"num","searchRow"=>"换算后实发"));
    	//    $list->addshow("实发"  ,array("row"=>"[换算后实发]","searchMode"=>"num","searchRow"=>"换算后实发"));   
		//}else{
	    //    $list->addshow("实发"    ,array("row"=>"[实发]","searchMode"=>"num","searchRow"=>"实发"));
		//}
        $list->addshow("开户行"  ,array("row"=>"[开户行]","searchMode"=>"text","searchRow"=>"开户行"));      
        $list->addshow("银行卡号",array("row"=>"[银行卡号]","searchMode"=>"text","excelMode"=>"text","searchRow"=>"银行卡号"));  
        $list->addshow("开户地址",array("row"=>"[开户地址]","searchMode"=>"text","searchRow"=>"开户地址"));
        $list->addshow("开户名"  ,array("row"=>"[开户名]","searchMode"=>"text","searchRow"=>"开户名"));
        $list->addshow("联系电话",array("row"=>"[联系电话]","searchMode"=>"text"));
        $list->addExcel("环迅"   ,array("url"=>__URL__."/getHxExcel:__XPATH__",'background'=>'url(__PUBLIC__/Images/excel.jpg) no-repeat scroll 0 2px;'));
        //dump($list->select(false));
        $this->assign('list',$list->getHtml());
        $this->display('getmoney');
	}
	//同意撤销
	function apply_aggree(){
        $m_user=M('会员');
		$succNum = 0;
		$errNum = 0; 
		$errMsg = '';
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
			$get = M($bank->name."提现");
			M()->startTrans();
            $re = $get ->lock(true)->where(array("id"=>$id))->find();
			
    		
			if(!$re){
				$errNum++;
				$errMsg .=$id . '：记录不存在！<br/>';
			}elseif($re["状态"] == "1"){
				$errNum++;
				$errMsg .=$id . '：已经撤销！<br/>';
			}else{
				$bank=X('fun_bank@'.$re['id']);
				$where['编号']	= $re['编号'];
				$user=$m_user->where($where)->find();
                $data["id"] =$id;
                $data["状态"] = '1';
                $data["撤销申请"] = '2';
                $data["审核时间"] = systemTime();
                $res = $get->save($data);
                $bank->set($re["编号"],'管理员',$re["实发"]+$re["手续费"],'撤销提现','撤销提现返还：'.($re["实发"]+$re["手续费"]));
                
            }
            if($res){ 
				$this->saveAdminLog('','',$re['编号']."体现撤销充值，返还".$re["实发"]+$re["手续费"]);
                //$this->success("撤销成功");
				$succNum++;
                M()->commit();
            } else{
                $errNum++;
				$errMsg .=$id . '：撤销失败<br/>';
				M()->rollback();
            }
        }
		if($errNum !=0){
			$this->error("审核成功：".$succNum .'条记录；撤销失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("审核成功：".$succNum .'条记录；');
		}
	}
	function apply_notaggree(){
        $m_user=M('会员');
		$succNum = 0;
		$errNum = 0; 
		$errMsg = '';
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
				$get = M("提现");
			M()->startTrans();
            $re = $get ->lock(true)->where(array("id"=>$id))->find();
			if(!$re){
				$errNum++;
				$errMsg .=$id . '：记录不存在！<br/>';
			}elseif($re["状态"] == "1"){
				$errNum++;
				$errMsg .=$id . '：已经撤销！<br/>';
			}else{
				$bank = X('fun_bank@'.$re['类型']);
                $data["id"] =$id;
                $data["撤销申请"] = '3';
                $data["审核时间"] = systemTime();
                $res = $get->save($data);
            }
            if($res){ 
				$this->saveAdminLog('','',$re['编号']."提现撤销失败，提现额".$re["提现额"]);
				$succNum++;
				M()->commit();
            } else{
                $errNum++;
				$errMsg .=$id . '：撤销失败<br/>';
				M()->rollback();
            }
        }
		if($errNum !=0){
			$this->error("审核成功：".$succNum .'条记录；撤销失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("审核成功：".$succNum .'条记录；');
		}
	}
	//  提现
	public function getmoney($button=array(),$where='',$sta='')
	{
		//合成合并SQL
		$sqlm=M();
		$user=X("user");
		$isShowRadio=false;
		foreach(X('fun_bank') as $bank)
		{
			if($bank->getMoneyRatio!=1)
			{
				$isShowRadio=true;
			}
		}
        $list=new TableListAction("提现");
		$list->table('dms_提现 a');
		$list->join('dms_会员 as b on a.编号=b.编号')->field('a.*,b.姓名');
        if($isShowRadio)
        {
        	$list->hint='当前列表中的实发额度,显示为货币提现汇率折算后的实际人民币额度.';
        }
        if($button){
            $list->setButton = $button;
        }else{
        	//unacc
            $list->setButton = array(
            '审核'=>array("class"=>"edit"  ,"href"=>"__URL__/getmoneyacc/id/{tl_id}"   ,"target"=>"ajaxTodo","mask"=>"true"),
            '撤销'=>array("class"=>"delete","href"=>"__URL__/getmoneyunpage/id/{tl_id}","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"240"),
            '删除'=>array("class"=>"delete","href"=>"__URL__/getmoneydel/id/{tl_id}"   ,"target"=>"ajaxTodo","mask"=>"true"),
            '发放'=>array("class"=>"edit"  ,"href"=>"__URL__/getmoneygive/id/{tl_id}"   ,"target"=>"ajaxTodo","mask"=>"true"),
            );
        }
		$list->order("a.id desc");
		$list->addshow("ID",array("row"=>"[id]","searchMode"=>"text","searchRow"=>"id")); 
        $list->addshow("状态"    ,array("row"=>array(array(&$this,"mygetFun"),"[状态]"),"searchRow"=>"a.状态","searchMode"=>"text","searchGet"=>"状态","searchPosition"=>"top","searchSelect"=>array("未审核"=>"0","已撤销"=>1,"已审核"=>"2")));  
        $list->addshow("货币名称",array("row"=>"[类型]","searchMode"=>"text","searchRow"=>"类型"));  
        $list->addshow("申请时间",array("row"=>"[操作时间]","format"=>"time","searchMode"=>"date","searchRow"=>"a.操作时间"));  
        $list->addshow("审核时间",array("row"=>"[审核时间]","format"=>"time","searchMode"=>"date","searchRow"=>"a.审核时间"));  
        $list->addshow($this->userobj->byname."编号",array("row"=>"[编号]","searchMode"=>"text","searchPosition"=>"top","searchGet"=>"userid","excelMode"=>"text","searchRow"=>"a.编号"));         
        $list->addshow("姓名"    ,array("row"=>"[姓名]","searchMode"=>"text","searchRow"=>"姓名","excelMode"=>"text","order"=>"b.姓名"));
        $list->addshow("提现额"  ,array("row"=>"[提现额]","searchMode"=>"num","searchRow"=>"a.提现额"));     
        $list->addshow("手续费"  ,array("row"=>"[手续费]","searchMode"=>"num","searchRow"=>"a.手续费"));   
    	$list->addshow("实发"  ,array("row"=>"[换算后实发]","searchMode"=>"num","searchRow"=>"a.换算后实发"));
        $list->addshow("开户行"  ,array("row"=>"[开户行]","searchMode"=>"text","searchRow"=>"a.开户行"));      
        $list->addshow("银行卡号",array("row"=>"[银行卡号]","searchMode"=>"text","excelMode"=>"text","searchRow"=>"a.银行卡号"));  
        $list->addshow("开户地址",array("row"=>"[开户地址]","searchMode"=>"text","searchRow"=>"a.开户地址"));
        $list->addshow("开户名"  ,array("row"=>"[开户名]","searchMode"=>"text","searchRow"=>"a.开户名"));
        $list->addshow("联系电话",array("row"=>"[联系电话]","searchMode"=>"text"));
        //导出独立EXCEL需要重新架构
        $this->assign('list',$list->getHtml());
        $this->display('getmoney');
	}
	//导出第三方快捷支付表
 	public function getExcel()
	{
		$class=I("get.class/s");
		ini_set('memory_limit','500M');
		set_time_limit(300);
		$where = unserialize(base64_decode(I("get._where/s")));
		$whereArr = explode(' ',$where);
		$where = preg_replace("/(\S+)\s*[=><]/U",'a.$0',$where);
        $m= M("提现");
        $result = $m->table('dms_提现 as a')->join(C('DB_PREFIX').'会员 as b on a.编号=b.编号')->group('a.银行卡号')->field("a.开户名,b.证件号码,a.联系电话,a.开户行,b.开户地址,b.省份,b.城市,a.银行卡号,sum(a.换算后实发) as 换算后实发,count(*) 笔数")->where($where)->select();
		import('DmsAdmin.DMS.fun_bank.'.$class);
		$class::runget($result);
	}
	public function getmoneyunpage()
	{
		$ids=I("get.id/s");
		$ids=explode(',',$ids);
		$count=M('提现')->where(array('id'=>array('in',$ids),'状态'=>array('neq',1)))->count();
		$this->assign('count',$count);
		$this->assign('id',I("get.id/s"));
		$this->display();
	}
	function mygetFun($str){
		if($str == 0){
			return "<font color='#9d0000'>未审核</font>";
		}else if($str == 2){
			return "<font color='#079d00'>已审核</font>";
		}else if($str == 3){
			return "<font color='#079d00'>已发放</font>";
		}else{
			return "已撤销";
		}
	}
	function mygetFun11($str){
		if($str == 1){
			return "<font color='#9d0000'>未审核</font>";
		}if($str == 2){
			return "<font color='#079d00'>已同意</font>";
		}else{
		   return "<font color='#079d00'>已拒绝</font>";
		}
	}
	function mytobankFun($str){
		if($str == "未审核"){
			return "<font color='#9d0000'>{$str}</font>";
		}else {
			return "<font color='#079d00'>{$str}</font>";
		}
	}
    //提现信息审核
	public function getmoneyacc(){
        $user=X('user');
		$succNum = 0;
		$errNum = 0; 
		$errMsg = '';
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
			$get = M("提现");
			M()->startTrans();
			$re = $get -> where(array("id"=>$id))->find();
			if(!$re)
			{
				$errNum++;
				$errMsg .= $id."：未找到记录<br/>";
				M()->rollback();
				continue;
			}
			$bank=X("fun_bank@".$re['类型']);
			if($re["状态"] == "2"){
				$errNum++;
				$errMsg .= $id."：已经通过审核<br/>";
				M()->rollback();
			}elseif($re["状态"] == "1"){
				$errNum++;
				$errMsg .= $id."：已经撤销<br/>";
				M()->rollback();
			}elseif($re["状态"] == "3"){
				$errNum++;
				$errMsg .= $id."：已经发放<br/>";
				M()->rollback();
			}else{
				if($bank->getMoneyBankClear){
					$data["开户行"] = "";
					$data["银行卡号"] = "";
					$data["开户地址"] = "";
					$data["开户名"] = "";
				}
				$data["id"] =$id;
				$data["状态"] = '2';
				$data["审核时间"] = systemTime();
				$res = $get->save($data);
				if($res){
					$this->saveAdminLog('','',I("get.id/s")."提现信息审核");
					$succNum++;
					M()->commit();
				} else{
					$errNum++;
					$errMsg .= $id."：审核失败<br/>";
					M()->rollback();
				}
			}
		}
		if($errNum !=0){
			$this->error("审核成功：".$succNum .'条记录；审核失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("审核成功：".$succNum .'条记录；');
		}
	}
	//提现信息撤销
	public function getmoneyunacc(){
		M()->startTrans();
		$succNum = 0;
		$errNum = 0; 
		$errMsg = '';
		foreach(explode(',',I("post.id/s")) as $id){
			if($id == '') continue;
			$get = M("提现");
            $re = $get -> where(array("id"=>$id))->find();
			if(!$re)
			{
				$errNum++;
				$errMsg .= $id."：未找到记录<br/>";
				continue;
			}            
			$bank=X('fun_bank@'.$re['类型']);
			if($re["状态"] == "1"){
				$errNum++;
				$errMsg .=$id . '：已经撤销！<br/>';
				continue;
			}elseif($re["状态"] == "2"){
				$errNum++;
				$errMsg .=$id . '：已经审核！<br/>';
				continue;
			}elseif($re["状态"] == "3"){
				$errNum++;
				$errMsg .=$id . '：已经发放！<br/>';
				continue;
			}else{
                $data["id"] =$id;
                $data["状态"] = '1';
                $data["审核时间"] = systemTime();
                $data["撤销理由"] = I("post.memo/s");
                $res = $get->save($data);
                $bank->set($re["编号"],'管理员',($re["实发"]+$re['手续费']),'撤销提现','撤销提现返还：'.($re["实发"]+$re['手续费']));
            }
            if($res){ 
				$this->saveAdminLog('','',$re['编号']."提现撤销充值，返还提现额".($re["实发"]+$re['手续费']));
				$succNum++;
            } else{
                $errNum++;
				$errMsg .=$id . '：撤销失败<br/>';
            }
        }
        if($succNum>0){
        	M()->Commit();
        }
		if($errNum !=0){
			$this->error("撤销成功：".$succNum .'条记录；撤销失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("撤销成功：".$succNum .'条记录；');
		}
	}
	//提现打款发放
	public function getmoneygive(){
		M()->startTrans();
		$succNum = 0;
		$errNum = 0; 
		$errMsg = '';
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
			$get = M("提现");
            $re = $get -> where(array("id"=>$id))->find();
			if(!$re)
			{
				$errNum++;
				$errMsg .= $id."：未找到记录<br/>";
				continue;
			}            
			$bank=X('fun_bank@'.$re['类型']);
			if($re["状态"] == "1"){
				$errNum++;
				$errMsg .=$id . '：已经撤销！<br/>';
				continue;
			}elseif($re["状态"] == "0"){
				$errNum++;
				$errMsg .=$id . '：未审核！<br/>';
				continue;
			}elseif($re["状态"] == "3"){
				$errNum++;
				$errMsg .=$id . '：已经发放！<br/>';
				continue;
			}else{
                $data["id"] =$id;
                $data["状态"] = '3';
                $res = $get->save($data);
            }
            if($res){ 
            	$this->saveAdminLog('','',I("get.id/s")."提现发放");
				$succNum++;
				if($re["状态"]==2 && CONFIG('txmsmsSwitch')){
					if(CONFIG('txmsmsSwitch1')){
	          			 $copy=1; 
	            	}
	            	$user_bh = $re['编号'];
					$udata = M('会员')->where($user_bh)->find();
					//到账短信
					sendSms('txm',$re['编号'],$this->userobj->byname.'提现成功',$re);
				}
            } else{
                $errNum++;
				$errMsg .=$id . '：发放失败<br/>';
            }
        }
        if($succNum>0){
        	M()->Commit();
        }
		if($errNum !=0){
			$this->error("发放成功：".$succNum .'条记录；发放失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("发放成功：".$succNum .'条记录；');
		}
	}
	//提现信息删除
	public function getmoneydel(){
		$succNum = 0;
		$errNum = 0; 
		$errMsg = '';
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
			$get = M("提现");
			$where['id']=$id;
			M()->startTrans();
            $re = $get -> where($where)->find();
			if(!$re)
			{
				$errNum++;
				$errMsg .= $id."：未找到记录<br/>";
				M()->rollback();
				continue;
			}            
			$bank=X('fun_bank@'.$re['类型']);
			if($re["状态"] == "2"){
				$errNum++;
				$errMsg .=$id . '：已经审核！<br/>';
				M()->rollback();
			}elseif($re["状态"] == "3"){
				$errNum++;
				$errMsg .=$id . '：已经发放！<br/>';
				M()->rollback();
			}else{
                $res = $get->where($where)->delete();
                if($res){
                	if($re['状态']=="0"){
                		if($bank->getTaxFrom==1){
                			$banknum	  = $re["提现额"];
                		}else{
                			$banknum	  = $re["提现额"]+$re["手续费"];
                		}
                		$bank->set($re["编号"],'管理员',$banknum,'删除提现','删除提现返还：'.$banknum);
                		$this->saveAdminLog('','',$re['编号']."提现删除充值，返还提现额".$banknum);
                	}
                	$succNum++;
                	M()->commit();
                }else{
	                $errNum++;
					$errMsg .=$id . '：删除失败<br/>';
					M()->rollback();
	            }
            }
        }
		if($errNum !=0){
			$this->error("删除成功：".$succNum .'条记录；删除失败：'.$errNum .'条记录；<br/>'.$errMsg);
		}else{
			$this->success("删除成功：".$succNum .'条记录；');
		}
	}

	//充值功能
	public function recharge(fun_bank $bank){
		$userid = I("get.userid/s");
		$this->assign('userid',$userid);
        $this->assign('user',$this->userobj->byname);
        $this->assign('name',$bank->byname);
        $this->assign('xpath',$bank->objPath());
		$this->display();
	}
	//充值完成
	public function rechargeSave(fun_bank $bank){
        $userid=trim(I("post.userid/s"));//取得会员编号
		if($userid =="" || !$this->userobj->have($userid))
		{   //输出会员不存在提示
            $this->error($this->userobj->byname." $userid 不存在");
		}else{//充值成功
			M()->startTrans();
			//$olddata	= M('会员')->where(array("编号"=>$userid))->getField($bank->name);
			$olddata	= M('货币')->where(array("编号"=>$userid))->getField($bank->name);//货币分离
		    $dataid = $bank->set($userid,'',I("post.chargeSum/f"),I("post.type/s"),I("post.remark/s"));
		    M($bank->name.'明细')->where(array('id'=>$dataid))->save(array('adminuser' => $_SESSION['loginAdminAccount']));
			//$newdata	= M('会员')->where(array("编号"=>$userid))->getField($bank->name);
			$newdata	= M('货币')->where(array("编号"=>$userid))->getField($bank->name);//货币分离
			$this->saveAdminLog(array($bank->byname=>$olddata),array($bank->byname=>$newdata),$userid."充值".$bank->byname.I("post.chargeSum/f")."成功");
			M()->commit();
            $this->success("充值成功");  
		}
	}
	 public function syshk(){
		$showstr='';
		$requriestr='';
		//dump($_POST);
		if(I("post.show/a")!=null)
		{
			$showstr=implode(',',I("post.show/a"));
		}
		if(I("post.requrie/a")!=null)
		{
			$requriestr=implode(',',I("post.requrie/a"));
		}
		M()->startTrans();
		 CONFIG('hk_hkzhxz'  ,I("post.requried3/s"));
		 CONFIG('hk_addMoney',I("post.addMoney/f"));
		 CONFIG('hk_showstr'   ,$showstr);
		 CONFIG('hk_requriestr',$requriestr);
		 M()->commit();
		 $this->success("成功");
	 }


	//批量充值功能
	public function rechargepl(fun_bank $bank){
		 //$userid=trim($_POST["userid"]);//取得会员编号
        $this->assign('user',$bank->parent()->byname);
        $this->assign('name',$bank->byname);
        $this->assign('xpath',$bank->objPath());
		$this->display();
	}
	//批量充值完成
	public function rechargeSavepl(fun_bank $bank){
		$type=trim(I("post.type/s"));//取得会员编号
		//根据换行生成数组
		$payary=preg_split('[\r\n]',I("post.paypl/s"));
		$rechargeary=array();
		for($i=0;$i<count($payary);$i++){
			if(!isset($payary[$i]) || $payary[$i]=="")
				continue;
			//根据每行数据生成数组
			preg_match("/^(\S+)\s+(\S+)\s+(\S+)/",$payary[$i],$payinfo);
			//会员编号
			$username=$payinfo[1];
			$result=X("user")->have($username);
			if(!$result)
			{
				$this->error($username." 不存在");
			}
			//充值金额
			if(!isset($payinfo[2])){
				$this->error($username."编号对应的填入方法有误，请重新添加");
			}
			$money=$payinfo[2];
			if(!is_numeric($money)){
				$this->error($username."编号对应的填入金额有误，请重新添加");
			}
			$rechargeary[]=array(
				"编号"=>$payinfo[1],
				"金额"=>$payinfo[2],
				"备注"=>isset($payinfo[3])?$payinfo[3]:""
			);
		}
		M()->startTrans();
		foreach($rechargeary as $rechargeinfo){
			$olddata	= M('货币')->where(array("编号"=>$rechargeinfo['编号']))->getField($bank->name);
			bankset($bank->name,$rechargeinfo['编号'],$rechargeinfo['金额'],$type,$rechargeinfo['备注']);
			$newdata	= M('货币')->where(array("编号"=>$rechargeinfo['编号']))->getField($bank->name);
			$this->saveAdminLog(array($bank->byname=>$olddata),array($bank->byname=>$newdata),$rechargeinfo['编号']."充值".$bank->byname.$rechargeinfo['金额']."成功");
		}
		M()->commit();
		$this->success("充值成功");
	}
    public function deduction(){
		$this->display();
	}
	//汇款通知列表
    public function rem()
	{
        $list=new TableListAction("汇款通知");
        $list->numPerPage=25;                   // 每页显示数量  默认25
        $list->order("id desc");
        //$list->join('会员 N on 会员_报单.会员编号=会员.会员编号');
        $list->addshow($this->userobj->byname."编号",array("row"=>"[编号]","searchGet"=>"userid",'css'=>'width:50px;',"searchMode"=>"text","searchPosition"=>"top","excelMode"=>"text")); 
        if(adminshow('bankset')){
        	$list->addshow("汇入账户卡号",array("row"=>"[汇入账户卡号]",'css'=>'width:80px;'));
        	$list->addshow("汇入账户开户名",array("row"=>"[汇入账户开户名]",'css'=>'width:50px;'));
        	$list->addshow("汇入账户开户行",array("row"=>"[汇入账户开户行]",'css'=>'width:60px;'));    
        }else{
        	$list->addshow("汇入账户",array("row"=>"[汇入账户]",'css'=>'width:80px;'));    
        }
        $list->addshow("开户银行",array("row"=>"[开户银行]","searchMode"=>"text",'css'=>'width:60px;'));
		$list->addshow("银行卡号",array("row"=>"[银行卡号]",'css'=>'width:80px;'));
        $list->addshow("开户名",array("row"=>"[开户名]","searchMode"=>"text",'css'=>'width:50px;'));   
        $list->addshow("金额",array("row"=>"[金额]","searchMode"=>"num",'order'=>'金额','css'=>'width:50px;'));
		if(CONFIG('USER_REMIT_RATIO_USE')=="true" && CONFIG('USER_REMIT_RATIO')!=1){
        	$list->addshow("换算后金额",array("row"=>"[换算后金额]","searchMode"=>"num",'css'=>'width:50px;'));      
        }
        $list->addshow("汇款时间",array("row"=>"[汇款时间]",'css'=>'width:70px;',"format"=>"time","searchMode"=>"date"));
        $list->addshow("备注",array("row"=>"[备注]",'css'=>'width:80px;'));
        $list->addshow("状态",array("row"=>array(array(&$this,"dispFunction"),"[状态]",'css'=>'width:50px;')));  
        if(adminshow('huikuan')){
          $list->addshow("汇款方式",array("row"=>array(array(&$this,"huikuan_type"),"[汇款方式]")));  
        }
        $setButton=array(  
			'审核'=>array('class'=>'edit','href'=>"__URL__/confirmRem/id/{tl_id}","target"=>"dialog" ,"title"=>"汇款通知审核"),
			'删除'=>array("class"=>"delete","href"=>"__URL__/del1/id/{tl_id}","target"=>"ajaxTodo" ,"title"=>"确定要删除吗?"),
	    );
        $setButton['设置'] = array("class"=>"addMore","href"=>"__URL__/remitSet","target"=>"navTab","title"=>"汇款设置",'icon'=>'/Public/Images/ExtJSicons/cog.png');
		$list->setButton = $setButton; 
        $this->assign('list',$list->getHtml());         // 分配到模板有自定义函数时需传递$this;
        $this->display('rem');
	}
	public function huikuan_type($type){
		$ids = M('汇款方式')->where(array('id'=>$type))->find();
		return $ids['方式名称'];
	}
	public function dispFunction($status)
	{
		if($status==0)
		{
			return "未审核";
		}
		else
		{
			return "已审核";
		}
	}
    public function del1(){
    	M()->startTrans();
		$map['id']		= I("get.id/d");
		$result1=M('汇款通知')->where($map)->delete();
	    if($result1){
			$this->saveAdminLog('','',"删除汇款通知");
			M()->commit();
		    $this->success("删除成功",__URL__."/rem");
	    }else{
	    	M()->rollback();
		    $this->error("操作失败",__URL__."/rem");
	    }
    }
    //汇款通知设置
    public function remitSet(){
		$user = X('user');
        $bank = array();
		foreach(X('fun_bank') as $nr){
			if($nr->sysBankIn) $bank[$nr->name] = $nr->byname;
		}
		$this->assign("bank",$bank);
		//读取数据
		$this->assign('USER_REMIT_RATIO_USE'  ,CONFIG('USER_REMIT_RATIO_USE'));
		$this->assign('USER_REMIT_RATIO',CONFIG('USER_REMIT_RATIO'));
		$this->assign('USER_REMIT_INBANK'  ,CONFIG('USER_REMIT_INBANK'));
		$this->assign('USER_REMIT_MAX',CONFIG('USER_REMIT_MAX'));
		$this->assign('USER_REMIT_MIN',CONFIG('USER_REMIT_MIN'));
		$this->display();
	}
	public function remitSetSave(){
		 //汇款钱包
		 M()->startTrans();
		 CONFIG('USER_REMIT_INBANK',I("post.USER_REMIT_INBANK/s"));
		 CONFIG('USER_REMIT_RATIO_USE',I("post.USER_REMIT_RATIO_USE/s"));
		 CONFIG('USER_REMIT_RATIO',I("post.USER_REMIT_RATIO/f"));
		 CONFIG('USER_REMIT_MAX',I("post.USER_REMIT_MAX/f"));
		 CONFIG('USER_REMIT_MIN',I("post.USER_REMIT_MIN/f"));
		 M()->commit();
		 $this->success("汇款设置完成");
	 }
	public function confirmRem(){
        $bank = array();
		foreach(X('fun_bank') as $nr){
			if($nr->sysBankIn)$bank[$nr->name] = $nr->byname;
		}
		$this->assign('USER_REMIT_INBANK',CONFIG('USER_REMIT_INBANK'));
		$this->assign("bank",$bank);
		$this->display();
	}
	public function confirm()
	{
		$where['id']	= I("get.id/d");
		$m				= M('汇款通知');
		$moneyName = I("post.addMoney/s");
		M()->startTrans();
		$re = $m->where($where)->find();
		if($re['状态']==1){
			M()->rollback();
			$this->error('该记录已经通过审核！');
		}
		if($moneyName ==""){
			M()->rollback();
			$this->error('请选择汇款进入的货币类型');
		}elseif($moneyName === "0"){
			$data['状态']	= 1;
			$m->where($where)->save($data);
			$this->saveAdminLog('','',I("get.id/s")."汇款通知审核");
			M()->commit();
			$this->success("审核成功！",__URL__."/rem");
		}else{
			$data['状态']	= 1;
			if(CONFIG("USER_REMIT_RATIO_USE")){
				if($re["换算后金额"]>0){
					$money=$re["换算后金额"];
				}else if($re["金额"]>0 && $re["换算后金额"]==0){
					$money=$re["金额"]/CONFIG("USER_REMIT_RATIO");
					$data['换算后金额']=$money;
				}else{
					$this->error('汇款金额不存在，无效汇款');
				}
			}else{
				$money=$re["金额"];
			}
			$m->where($where)->save($data);
			foreach(X('fun_bank') as $nr){
				if($nr->name == $moneyName){
					$bank =$nr;
				}
			}
			$bank->set($re['编号'],'',$money,'汇款','汇款转入'.$money);
			$this->saveAdminLog('','',I("get.id/s")."汇款通知审核");
			M()->commit();
			$this->success("审核成功！",__URL__."/rem");
		}
	}
    // 银行卡管理
    function banks(){
    	//判断开关是否开启
    	if(adminshow('bankset')){
	        $setButton=array(
				//'添加'=>array("class"=>"add","href"=>"__URL__/addbanks","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"240"),
				'修改'=>array("class"=>"edit","href"=>"__URL__/editbanks/id/{tl_id}","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"240"),
	            //'删除'=>array("class"=>"delete","href"=>"__URL__/delbanks"."/id/{tl_id}","target"=>"ajaxTodo" ,"title"=>"确定要删除吗?"),
	        );
	        $setShow = array(
	            '开户银行'=>array('row'=>'[开户行]'),
	            '银行卡号'=>array('row'=>'[卡号]'),
	            '户主名称'=>array('row'=>'[户名]'),
	            //'添加时间'=>array('row'=>'[时间]','format'=>'date'),
				'是否可用'=>array('row'=>'[状态]'),
	        );
	        $list=new TableListAction("银行卡");
	        $list->setShow = $setShow;         // 定义列表显示
	        $list->setButton = $setButton;     // 定义按钮显示
	        $list->title="银行信息列表";       // 列表标题、
	        $list->hint='银行卡号，户主名称必填';
	        //$list->order("id desc"); 
	        $this->assign('list',$list->getHtml());
        }else{
			$setButton=array(
				'添加'=>array("class"=>"add","href"=>"__URL__/addbanks","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"240"),
				'修改'=>array("class"=>"edit","href"=>"__URL__/editbanks/id/{tl_id}","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"240"),
			    '删除'=>array("class"=>"delete","href"=>"__URL__/delbanks"."/id/{tl_id}","target"=>"ajaxTodo" ,"title"=>"确定要删除吗?"),
			);
			$setShow = array(
			    '开户银行'=>array('row'=>'[开户行]'),
			    '银行卡号'=>array('row'=>'[卡号]'),
			    '户主名称'=>array('row'=>'[户名]'),
			    //'添加时间'=>array('row'=>'[时间]','format'=>'date'),
				'是否可用'=>array('row'=>'[状态]'),
			);
			$list=new TableListAction("银行卡");
			$list->setShow = $setShow;         // 定义列表显示
			$list->setButton = $setButton;     // 定义按钮显示
			$list->title="银行信息列表";       // 列表标题
			$list->order("id desc"); 
			$this->assign('list',$list->getHtml()); 
        } 
        $this->display();
    }
    //添加汇款方式
    function rem_types(){
        $setButton=array(
			'添加'=>array("class"=>"add","href"=>"__URL__/add_huikuantype","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"240"),
			'修改'=>array("class"=>"edit","href"=>"__URL__/edit_huikuantype/id/{tl_id}","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"240"),
            '删除'=>array("class"=>"delete","href"=>"__URL__/delete_huikuantype/id/{tl_id}","target"=>"ajaxTodo" ,"title"=>"确定要删除吗?"),
        );
        $setShow = array(
            'id'=>array('row'=>'[id]'),
            '汇款方式'=>array('row'=>'[方式名称]',"searchPosition"=>"top","excelMode"=>"text"),
            '添加时间'=>array('row'=>'[添加时间]','format'=>'date'),
            '修改时间'=>array('row'=>'[修改时间]','format'=>'date'),
        );
        $list=new TableListAction("汇款方式");
        $list->setShow = $setShow;         // 定义列表显示
        $list->setButton = $setButton;     // 定义按钮显示
        $list->title="汇款方式列表";       // 列表标题
        $list->order("id desc"); 
        $this->assign('list',$list->getHtml()); 
        $this->display();
    }
    //修改汇款方式
    
	//修改银行卡信息
	function editbanks(){
		if(strpos(I("get.id/s"),',') !== false){
			$this->error('参数错误!');
		}
		$bankinfo = M('银行卡')->find(I("request.id/d"));
		//dump($bankinfo);
		$this->assign('bankinfo',$bankinfo);
		$this->display();
	}
	function saveEditBanks(){
		$bank = M('银行卡');
		$data['id'] = I("post.id/d");
        $data['开户行'] = I("post.开户行/s");
        $data['卡号'] = I("post.卡号/s");
        $data['户名'] = I("post.户名/s");
        $data['时间'] = systemTime();
		$data['状态'] = I("post.状态/s");
        M()->startTrans();
        if($bank ->save($data)){
			$this->saveAdminLog('','',"修改银行卡成功");
			M()->commit();
            $this->success("修改成功",'__URL__/banks');
        }else{
        	M()->rollback();
            $this->error("修改失败");
        }
	}
 // 添加银行卡信息
	    function addbanks(){
	       $this->display();
	    }
		 //保存添加的银行信息
	    function savebank(){
	        $bank = M('银行卡');
	        $data['开户行'] = I("post.开户行/s");
	        $data['卡号'] = I("post.卡号/s");
	        $data['户名'] = I("post.户名/s");
	   	  	$data['状态'] = I("post.状态/s");
	        M()->startTrans();
	        if($bank ->add($data)){
	   			$this->saveAdminLog('','',"添加银行卡成功");
	   			M()->commit();
	          $this->success("添加银行卡成功",'__URL__/banks');
	       }else{
	       	   M()->rollback();
	            $this->error("添加失败");
	       }  
	    }	
	    
	    
    //添加汇款方式
    function add_huikuantype(){
      $this->display();
    }
    // 保存添加的汇款方式
    function saveadd_huikuantype(){
        $bank = M('汇款方式');
        $data['方式名称'] = I("post.方式名称/s");
        $data['添加时间'] = systemTime();
        M()->startTrans();
        if($bank ->add($data)){
			$this->saveAdminLog('','',"添加汇款方式成功");
			M()->commit();
            $this->success("添加汇款方式成功",'__URL__/rem_types');
        }else{
        	M()->rollback();
            $this->error("添加失败");
        }  
    }
        
	//修改汇款方式
	function edit_huikuantype(){
		if(strpos(I("get.id/s"),',') !== false){
			$this->error('参数错误!');
		}
		$bankinfo = M('汇款方式')->find(I("request.id/d"));
		//dump($bankinfo);
		$this->assign('bankinfo',$bankinfo);
		$this->display();
	}
	function saveEditHuikuan(){
		$bank = M('汇款方式');
		$data['id'] = I("post.id/d");
        $data['方式名称'] = I("post.方式名称/s");
        $data['修改时间'] = systemTime();
        
        if($bank ->save($data)){
			$this->saveAdminLog('','',"修改汇款方式成功");
            $this->success("修改成功",'__URL__/rem_types');
        }else{
            $this->error("修改失败");
        }
	}
	//删除汇款方式
	function delete_huikuantype(){
	    $bank   = M('汇款方式');
		$succNum = 0;
		$errNum = 0; 
		M()->startTrans();
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
			$list	= $bank ->where(array("id"=>$id))->delete();
			if($list){
				$this->saveAdminLog('','',"删除汇款方式");
				$succNum++;
			}else{
				$errNum++;
			}
		}
		M()->commit();
		if($errNum !=0){
			$this->error("删除成功：".$succNum .'条记录；删除失败：'.$errNum .'条记录；');
		}else{
			$this->success("删除成功：".$succNum .'条记录；');
		}
	}
	//删除银行卡信息
    function delbanks(){
        $bank   = M('银行卡');
		$succNum = 0;
		$errNum = 0; 
		M()->startTrans();
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
			$list	= $bank ->where(array("id"=>$id))->delete();
			if($list){
				$this->saveAdminLog('','',"删除银行卡");
				$succNum++;
			}else{
				$errNum++;
			}
		}
		M()->commit();
		if($errNum !=0){
			$this->error("删除成功：".$succNum .'条记录；删除失败：'.$errNum .'条记录；');
		}else{
			$this->success("删除成功：".$succNum .'条记录；');
		}
        
    }
    /*公司充值明细*/
    function adminin()
    {
        $user = X('user');
        //$list=new TableListAction('会员');
        $sqlm=M();
        foreach(X('fun_bank') as $key=>$bank)
        {
        	if($key == 0)
        	{
        		//$list=new TableListAction('会员_'.$bank->name.'明细');
        		$sqlm->table('dms_'.$bank->name.'明细');
        		$sqlm->field("id,编号,时间,类型,金额,余额,备注,'".$bank->name."' as 货币名称,adminuser");
        		$sqlm->table('dms_'.$bank->name.'明细');
        	}
        	else
        	{
        		$sqlm->union("SELECT id,编号,时间,类型,金额,余额,备注,'".$bank->name."' as 货币名称,adminuser FROM dms_".$bank->name.'明细',true);
        	}
        	
        }
        $list=new TableListAction('会员');
        $list->table($sqlm->select(false).' a');
        //$list->setButton = $setButton;       // 定义按钮显示
		$list->join(C('DB_PREFIX').$user->name .' as b on a.编号=b.编号')->field('a.*,b.姓名');
		$list->order('时间 desc');
		$list->where("adminuser <> ''");
        $list->addshow("时间",array("row"=>"[时间]","css"=>"width:200px","searchMode"=>"date","format"=>"time","order"=>"时间",'searchRow'=>'a.时间'));
        $list->addshow("货币名称",array("row"=>"[货币名称]","css"=>"width:200px"));
        $list->addshow($bank->parent()->name."编号",array("row"=>"[编号]","css"=>"width:100px","searchMode"=>"text",'searchGet'=>'userid',"excelMode"=>"text","order"=>"a.编号","searchPosition"=>"top",'searchRow'=>'a.编号'));   
		$list->addshow("姓名",array("row"=>"[姓名]","css"=>"width:100px","searchMode"=>"text","excelMode"=>"text","order"=>"b.姓名"));
        $list->addshow("金额",array("row"=>"[金额]","css"=>"width:60px","searchMode"=>"num",'searchRow'=>'a.金额',"order"=>"a.金额"));
        $list->addshow("类型",array("row"=>"[类型]","css"=>"width:60px","searchMode"=>"text",'searchRow'=>'a.类型'));
        $list->addshow("备注",array("row"=>"[备注]","css"=>"width:270px","searchMode"=>"text",'searchRow'=>'a.备注'));
        $list->addshow("操作管理员",array("row"=>"[adminuser]","css"=>"width:270px","searchMode"=>"text",'searchRow'=>'a.adminuser',"searchPosition"=>"top","order"=>"a.adminuser"));
        echo $list->getHtml();
    }
	//货币设置
	public function config(fun_bank $fun_bank)
	{
		$this->assign('pwd3Switch',adminshow('pwd3Switch'));
		$this->assign("weekary",$fun_bank->getMoneyWeek);
		$this->assign('bank' ,$fun_bank);
        $this->assign('givecon',$fun_bank->giveCon);
		//dump($thisbank->giveCon);
		$this->assign('xpath',$fun_bank->objPath());
        $this->display();
	}
   //对货币的配置进行更新
	public function funbankConfigUpdate(fun_bank $fun_bank)
	{
        /*转账设置
        $givecon =$fun_bank->getatt("giveCon");
		$givecon1 = array();
		if(I("post.givecon/a")!=''){
			foreach(I("post.givecon/a") as $val){
				$givecon1[] = $givecon[intval($val)-1];
			}
		}*/
		if(I("post.bank_scale/d")==''){
			$_POST['bank_scale']=1;
		}
		M()->startTrans();
        //$fun_bank->setatt("giveCon",$givecon1);
        $this->autoSet($fun_bank);
        M()->commit();
		$this->saveAdminLog('','',$fun_bank->byname."设置");
		$this->success("设置完成！");
	}

	//增加转账配置
	public function addGive(fun_bank $thisbank){
	    $giveset=array();
		foreach(X('fun_bank') as $bank)
		{
		    $netset="";
			foreach(X('net_rec,net_place') as $net){
				$netset.=$net->byname.',';
				$netset.=$net->byname.'上级,';
				$netset.=$net->byname.'下级,';
			}
            $netset = trim($netset,',');
			if($bank !== $thisbank)
			{
				$giveset[]=array("obj"=>$bank->objPath(),"name"=>"转入自己的".$bank->byname,"isme"=>'1',"bankname"=>$bank->name,"issave"=>$bank->issave,"netset"=>'');
			}
			$giveset[]=array("obj"=>$bank->objPath(),"name"=>"转入其他".$this->userobj->byname."的".$bank->byname,"isme"=>'0',"netset"=>$netset,"bankname"=>$bank->name,"issave"=>$bank->issave);
		}
        
        $this->assign('giveTo',$giveset);
		$this->display();
	}
	public function saveAddGive(fun_bank $bank){
		$givecon = $bank->getatt("giveCon");
		$mess='';
		$givecon[] = I("post.");
		M()->startTrans();
		$bank->setatt("giveMoney",true);
        $bank->setatt("giveCon",$givecon);
        M()->commit();
		$this->success("添加成功");
	}
	//转账配置删除ajax调用
	public function deleteGiveCon(fun_bank $bank){
        $givecon =$bank->getatt("giveCon");
		$givecon1 = array();
		if(I("post.key/d")!=""){
			foreach($givecon as $key=>$val){
				if($key != I("post.key/d")-1){
					$givecon1[] = $val;
				}
			}
			M()->startTrans();
			$bank->setatt("giveCon",$givecon1);
			M()->commit();
		}
	}
	function autoSet($obj,$option=array())
	{
		foreach($obj as $k=>$v)
		{
			$newval=I("post.".$k,null);
			if($newval !== null)
			{
				if(gettype($v)=='string' || ((gettype($v)=='integer' || gettype($v)=='double') && is_numeric($newval)))
			   	{
			   		//得到对象属性类型
			   		$type=gettype($v);
			   		//如果对象属性为整数,并且新值是一个带有小数的数字,则需要换为浮点型
			   		if(gettype($v)=='integer' && is_numeric($newval) && strpos($newval,'.')!==false)
			   		{
			   			$type='float';
			   		}
			   		settype($newval,$type);
			   		if($k=='getMoneyMday') $newval=str_replace("，",",",$newval);
			   		$obj->setatt($k,$newval);
			   	}
			   	if(gettype($v)=='boolean' && (strtolower($newval)=='true' || strtolower($newval) == 'false'))
			   	{
			   		if($newval=='true')
			   			$newval=true;
			   		else
			   			$newval=false;
			   		$obj->setatt($k,$newval);
			   	}
			   	if(gettype($v)=="array"){
			   		$obj->setatt($k,$newval);	
			   	}
			}else{
				if($k=='getMoneyWeek')
					$obj->setatt($k,array());
			}
		}
	}	
	//返回姓名
	public function realnameAjax()
	{
		$user = M("会员")->where(array("编号"=>I("post.userid/s")))->find();
		if($user && I("post.userid/s")!= '')
		{
			$this->ajaxReturn(array('姓名'=>$user['姓名']),'成功',1);
		}
		else
		{
			$this->ajaxReturn('','失败',0);
		}
	}
}
?>