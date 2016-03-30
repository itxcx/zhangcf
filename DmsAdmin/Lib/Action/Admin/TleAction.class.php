<?php
defined('APP_NAME') || die('不要非法操作哦!');
// 本类由系统自动生成，仅供测试用途
class TleAction extends CommonAction {
	//级别名称缓存
	public $_lvNameCache = array();
	//用于奖金表级别显示的函数
	public function _printLevel($level,$levelname="")
	{
		if(!isset($this->_lvNameCache[$levelname]))
		{
			foreach(X('levels') as $levels)
			{	
				$namearr = array();
				if($levelname == $levels->name){
					foreach($levels->getcon("con",array("name"=>"","lv"=>0)) as $lvconf)
					{
						$namearr[$lvconf['lv']] = $lvconf['name'];
						
					}
					$this->_lvNameCache[$levels->name]=$namearr;
				}
			}
		}
		return $this->_lvNameCache[$levelname][$level];
	}
	public function index(tle $tle)
	{
        $list=new TableListAction($tle->name);
        $list->table('dms_'.$tle->name.' a');
		$list->setButton=array(                 // 底部操作按钮显示定义
			'修改'=>array("class"=>"edit","href"=>"__URL__/edit:__XPATH__/id/{tl_id}","target"=>"dialog","title"=>"修改","mask"=>"true","width"=>"500","height"=>"480"),
        );
        $where   =array();
      //  foreach(X('prize_*',$tle) as $prize1)
      //  {
            //判定是一种数值计算形奖金(主要为了去除prize_sql)
       //     if($prize1->prizeMode >= 0){
       //     //如果会员没有开启allInTle,则判定必须有奖金项金额大于0的情况下.才会增加奖金记录.
        //        $where['a.'.$prize1->name]=array('gt',0);
        //    }
        //}
       // foreach(X('net_place') as $net_place)
       // {
        //    foreach($net_place->getcon("region",array("name"=>"")) as $nameconf)
        //    {
            	//$where['a.' . $net_place->name."_".$nameconf['name']."区本日业绩"]=array('gt',0);
        //    }
        //}
        //$where['_logic']="or";
		$list ->join('inner join dms_会员 as b on a.编号=b.编号')->field('a.*,b.姓名');
        $list->where($where)->order("计算日期 desc,a.id desc");
        $list->addshow("计算日期",array("row"=>"[计算日期]","format"=>"date","searchMode"=>"date","url"=>__APP__."/Admin/Tle/prizeForm:".__XPATH__."/id/[id]","target"=>"dialog","urlAttr"=>'mask="true" width="500" height="480" title="奖金明细"','order'=>'计算日期',"searchPosition"=>"top",'searchRow'=>'a.计算日期'));
        $list->addshow("编号",array("row"=>"[编号]","searchMode"=>"text",'searchGet'=>'userid',"searchPosition"=>"top","excelMode"=>"text",'searchRow'=>'a.编号'));  
		$list->addshow('姓名',array('row'=>'[姓名]',"searchMode"=>"text",'searchRow'=>'b.姓名'));
		foreach(X('levels') as $levels)
        {
        	$_temp=array();
			foreach($levels->getcon("con",array("name"=>"","lv"=>"")) as $lvconf)
			{
				$_temp[ $lvconf['name'] ] = $lvconf['lv'];
 			}
 			if(count($_temp)>1){ 	$list->addshow($levels->byname,array("row"=>array(array(&$this,"_printLevel"),"[".$levels->name."]",$levels->name),"searchMode"=>"num","searchSelect"=>$_temp,"searchRow"=>"[a.".$levels->name."]","order"=>'a.'.$levels->name));
 			}
        }
		foreach(X('net_place') as $netPlace){
			if($netPlace->pvFun)
			{
				$region=array();
				$netName = $netPlace->name;
				$addRowArr = array(array(&$this,"getAddRow"));
				$addRowStr = '';
				$remianRowArr = array(array(&$this,"getAddRow"));
				$remianRowStr = '';
				$totalRowArr = array(array(&$this,"getAddRow"));
				$totalRowStr = '';
				foreach($netPlace->getcon("region",array("name"=>"")) as $nameconf)
				{
					$region[] =  $nameconf['name'];
					array_push($addRowArr,'['.$netName.'_'.$nameconf['name'].'区本日业绩]');
					$addRowStr .= $netName.'_'.$nameconf['name'].'区本日业绩+';
					array_push($remianRowArr,'['.$netName.'_'.$nameconf['name'].'区结转业绩]');
					$remianRowStr .= $netName.'_'.$nameconf['name'].'区结转业绩+';
					array_push($totalRowArr,'['.$netName.'_'.$nameconf['name'].'区累计业绩]');
					$totalRowStr .= $netName.'_'.$nameconf['name'].'区累计业绩+';
	 			}
				//  $netName.'_'.$region[0].'区本日业绩'
				  $list ->addshow("新增业绩",array('row'=>$addRowArr));
				//is_BumpPrize()此函数判断是否有对碰奖,如果有对碰奖则显示结转业绩
				if($this->is_BumpPrize()){
			      $list ->addshow("结转业绩",array('row'=>$remianRowArr));
			    }
				$list ->addshow("累计业绩",array('row'=>$totalRowArr));
			}
		}
		//$list->addExcel("环迅",array("url"=>__URL__."/getHxExcel:__XPATH__",'background'=>'url(__PUBLIC__/Images/excel.jpg) no-repeat scroll 0 2px;'));
		$list->addExcel("汇总",array("url"=>__URL__."/getTotalExcel:__XPATH__",'background'=>'url(__PUBLIC__/Images/excel.jpg) no-repeat scroll 0 2px;'));
        foreach(X('prize_*',$tle) as $prize)
        {
        	if($prize->prizeMode>=0 && $prize->adminDisp)
        	{
        		$list->addshow($prize->byname,array("row"=>"[{$prize->name}]","sum"=>'a.'.$prize->name,"searchMode"=>"num",'searchRow'=>'a.'.$prize->name)); 
        	}
        }
        $list->addshow("奖金",array("row"=>"[奖金]","searchMode"=>"num",'searchRow'=>'a.奖金'));
        $list->addshow("收入",array("row"=>"[收入]","searchMode"=>"num",'searchRow'=>'a.收入'));
        $list->addshow("累计收入",array("row"=>"[累计收入]","searchMode"=>"num",'searchRow'=>'a.累计收入'));
        $list->addshow("生成日期",array("row"=>"[生成日期]",'format'=>'time','hide'=>true,"searchMode"=>"date",'searchRow'=>'a.生成日期'));
        $this->assign('list',$list->getHtml());

        $this->display('index');
	}
	
	//导出奖金汇总表
	public function getTotalExcel($tle){
		ini_set('memory_limit','500M');
		set_time_limit(300);
        $m= M()->table('dms_'.$tle->name." as a");
		$where = unserialize(base64_decode($_REQUEST['_where']));
		//$whereArr = explode(' ',$where);
		//$where = preg_replace("/(\S+)\s*[=><]/U",'a.$0',$where);
        $result=$m->join('dms_会员 as b on a.编号=b.编号')->field("a.编号,b.姓名,b.证件号码,b.移动电话,b.开户银行,b.开户地址,b.省份,b.城市,b.银行卡号,a.奖金,a.收入")->where($where)->order("a.计算日期 desc")->select();
		$temp = array();
		$total = array(
			'编号'=>'',
			'姓名'=>'',
			'证件号码'=>'',
			'移动电话'=>'',
			'开户银行'=>'',
			'开户地址'=>'',
			'银行卡号'=>'',
			'奖金'=>0,
			'收入'=>0
		);
		//dump($result);die;
		foreach($result as $v){
			$key  = $v["编号"];
			if( isset($temp[$key])){
				$temp[$key]['奖金'] = floatval($v['奖金']) + $temp[$key]['奖金'];
				$temp[$key]['收入'] = floatval($v['收入']) + $temp[$key]['收入'];
			}else{
				$temp[$key] = array(
					'编号'=>$v["编号"],
					'姓名'=>$v["姓名"],
					'证件号码'=>$v["证件号码"],
					'移动电话'=>$v["移动电话"],
					'开户银行'=>$v["开户银行"],
					'开户地址'=>$v["开户地址"],
					'银行卡号'=>$v["银行卡号"],
					'奖金'=>$v['奖金'],
					'收入'=>$v['收入']
				);
			}
			$total['奖金'] +=floatval($v['奖金']);
			$total['收入'] +=floatval($v['收入']);
			//$temp[$key]['收入'] = isset($temp[$key]) ? floatval($v['收入']) + $temp[$key] : floatval($v['收入']);
		}
		$temp['汇总'] = $total;

		//dump($temp);die;
		
		if(Extension_Loaded('zlib')){
			Ob_Start('ob_gzhandler');
		}
		Header("Content-type: text/html"); 
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        $title =date("YmdHis");
        header("Content-Disposition: attachment; filename=\"excel_{$title}.xls\"");
        echo '<html xmlns="http://www.w3.org/1999/xhtml">';
        echo '<head>';
        echo '<title>Untitled Document</title>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        echo '</head>';
        echo '<body>';
        echo '<table style="WIDTH: 80%" cellspacing="0" cellpadding="1" border="1" bandno="0">';
        echo '<tr><th>顺序号</th><th>编号</th><th>姓名</th><th>证件号码</th><th>移动电话</th><th>开户银行</th><th>开户地址</th><th>银行卡号</th><th>奖金</th><th>收入</th></tr>';
        $i=1;
		
        foreach($temp as $key=>$val){
			if($key !=='汇总'){
				echo '<tr><td>'.$i.'</td>';
			}else{
				echo '<tr><td style="text-align:right">汇总</td>';
			}
            foreach($val as $k=>$v){
				if($k !== '奖金' && $k !== '收入'){
					echo '<td style="vnd.ms-excel.numberformat:@">'.$v.'</td>';
				}else{
					echo '<td>'.$v.'</td>';
				}
            }
            echo '</tr>';
            $i++;
        }
        echo '</table>';
		echo '</body>';
        echo '</html>';
		if(Extension_Loaded('zlib')) Ob_End_Flush(); 
	}
	/*
	//导出环迅格式EXCEL
    public function getHxExcel($tle){
		ini_set('memory_limit','500M');
		set_time_limit(300);
        $m= M()->table('dms_'.$tle->name." as a");
		$where = unserialize(base64_decode($_REQUEST['_where']));
		//$whereArr = explode(' ',$where);
		//$where = preg_replace("/(\S+)\s*[=><]/U",'a.$0',$where);
        $result=$m->join('dms_会员 as b on a.编号=b.编号')->field("b.姓名,b.证件号码,b.移动电话,b.开户银行,b.开户地址,b.省份,b.城市,b.银行卡号,a.收入")->where($where)->order("a.计算日期 desc")->select();
		$temp = array();
		foreach($result as $v){
			$key1 = $v["姓名"].','.$v["证件号码"].','.$v["移动电话"].','.$v["开户银行"].','.$v["开户地址"].','.$v["省份"].','.$v["城市"].','.$v["银行卡号"];
			$key  = $v["姓名"].','.$v["银行卡号"];
			if( isset($temp[$key])){
				$temp[$key]['收入'] = floatval($v['收入']) + $temp[$key]['收入'];
			}else{
				$temp[$key] = array('信息'=>$key1,'收入'=>$v['收入']);
			}
			//$temp[$key]['收入'] = isset($temp[$key]) ? floatval($v['收入']) + $temp[$key] : floatval($v['收入']);
		}
		$temp1 = array();
		foreach($temp as $tk =>$tv){
			if($tv['收入'] <=45000){
				$list = explode(',',$tv['信息']);
				$list[] = $tv['收入'];
				$temp1[] = $list;
				//$temp1[]= $tv['收入'];
			}else{
				$n = ceil($tv['收入']/45000);
				for($i=0;$i<$n-1;$i++){
					$list = explode(',',$tv['信息']);
					$list[] = 45000;
					$temp1[] = $list;
				}
				$list = explode(',',$tv['信息']);
				$list[] = $tv['收入']-45000*($n-1);
				$temp1[] = $list;
			}
		}
		if(Extension_Loaded('zlib')){
			Ob_Start('ob_gzhandler');
		}
		Header("Content-type: text/html"); 
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        $title =date("YmdHis");
        header("Content-Disposition: attachment; filename=\"excel_{$title}.xls\"");
        echo '<html xmlns="http://www.w3.org/1999/xhtml">';
        echo '<head>';
        echo '<title>Untitled Document</title>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        echo '</head>';
        echo '<body>';
        echo '<table style="WIDTH: 80%" cellspacing="0" cellpadding="1" border="1" bandno="0">';
        echo '<tr><th>顺序号</th><th>收款人</th><th>身份证号</th><th>手机号</th><th>收款银行</th><th>收款账号开户行</th><th>收款账号省份</th><th>收款账号地市</th><th>收款人账号</th><th>收款金额</th></tr>';
        $i=1;
		
        foreach($temp1 as $val){
            echo '<tr><td>'.$i.'</td>';
            foreach($val as $k=>$v){
				if($k !== 8){
					echo '<td style="vnd.ms-excel.numberformat:@">'.$v.'</td>';
				}else{
					echo '<td>'.$v.'</td>';
				}
            }
            echo '</tr>';
            $i++;
        }
        echo '</table>';
		echo '</body>';
        echo '</html>';
		if(Extension_Loaded('zlib')) Ob_End_Flush(); 
    } 
    */
	// index列表row 处理函数   sprintf("%.2f", $num); 
	public function getAddRow(){
		$args = func_get_args();
		$str = '';
		foreach($args as $val){
			$str .= floatval($val).'/';
		}
		return  trim($str,'/');
	}
	//输出奖金构成
    public function prizeForm(tle $tle){
    	import('DmsAdmin.DMS.SYS.PrizeData');
    	//安全问题
       $id    = $_REQUEST["id"];
       //取得奖金记录的ID
       $m_tle = M($tle->name);
       $m_tle->where("id=".$id);
       $tlddata=$m_tle->find();
       $id     = $tlddata['id'];
       $userid = M('会员')->where(array('编号'=>$tlddata['编号']))->getField('id');
       $data=array();
       $undata=array();
       foreach(X('prize_*',$tle) as $prize)
       {
        	if($prize->prizeMode>=0 && $prize->isSee)
        	{
        		$fromlist = PrizeData::getprizedata($tlddata['计算日期'],$tle->name,$prize->name,$userid,$prize->byname,$prize->unmemo);
        		if(is_array($fromlist)){
	      			foreach($fromlist['prize'] as $key=>$val){
	        			$fromlist['prize'][$key]['lv'] = $this->_printUserLevel($val['lv'],$val['lvname']);
	        		}
	       			if(count($fromlist['prize'])>0)
	        		{
						//奖金to到另一个奖金中 构成以显示过去
	        			$data[$prize->name] = array("name"=>$prize->byname,"list"=>$fromlist['prize'],"TK"=>isset($fromlist['TK'])?$fromlist['TK']:array(),"PK"=>isset($fromlist['PK'])?$fromlist['PK']:array(),"KW"=>isset($fromlist['KW'])?$fromlist['KW']:array());
	        		}
					if($prize->unmemo)
					{
		        		if(count($fromlist['prizeup'])>0)
		        		{
		        			$undata[] = array("name"=>$prize->byname,"list"=>$fromlist['prizeup']);
		        		}
	        		}
        		}
        	}
        }
        
        $this->assign("data",$data);
        $this->assign("undata",$undata);
        $this->display();
    }
    public function edit(tle $tle){
		if(strpos(I("get.id/s"),',') !== false){
			$this->error('参数错误!');
		}
        $m=M($tle->name);
		$list = $m->find(I("get.id/d"));
		//含有奖金回填,是否能改回填
		$backfill=false;
		$prize_backfill=X("prize_backfill@");
		if($prize_backfill){
			$tlestate=M($tle->name."总账")->where(array("计算日期"=>$list['计算日期']))->getField('state');
			if($tlestate==1 || ($tlestate==0 && $prize_backfill->auto)){//已发放肯定已回填 或 未发放,结算完就回填
				$backfill=true;//可以改收入，不能改回填,因产生业绩会对上产生奖金
			}
		}
		
		$tleName = array();
        foreach(X('prize_*',$tle) as $prize)
        {
        	if($prize->prizeMode>0)
        	{
				$tleName[$prize->name] =array('mode'=>$prize->prizeMode,'val'=>$list[$prize->name]);
				if(get_class($prize)=='prize_backfill' && $backfill) $tleName[$prize->name]['readonly']='readonly';
        	}
        }
		$this->assign('tleSet',$tleName);
		$this->assign('list',$list);
        $this->display();
    }
	public function editTle(tle $tle){
		M()->startTrans();
        $m=M($tle->name);
        $data = $m->create();
        //得到现有数据
        $tledata = $m->where(array('id'=>$data['id']))->find();
		
        //得到奖金总账的发放状态
        $tlestate = M($tle->name.'总账')->where(array('计算日期'=>$tledata['计算日期']))->getfield('state');
		$resutlt=$m->where(array('id'=>$data['id']))->save($data);

		//更新会员表中的累计收入
		M('会员')->where(array('编号'=>$tledata['编号']))->setInc('累计收入',$data['收入']-$tledata['收入']);
		//更新总账的本期奖金
		M($tle->name.'总账')->where(array('计算日期'=>$tledata['计算日期']))->setInc('本期奖金',$data['收入']-$tledata['收入']);
		//更新总账的总奖金
		M($tle->name.'总账')->where("计算日期 >= {$tledata['计算日期']}")->setInc('总奖金',$data['收入']-$tledata['收入']);
		
        if($tlestate == 1 && I("post.sum/f")>0)
        {
        	//执行货币同步
        	$tle->givePrice($m->where(array('id'=>$data['id']))->select());
        }
        $this->saveAdminLog($tledata,$resutlt,'修改会员奖金',"修改会员[".$tledata['编号']."]".date("Y-m-d",$tledata['计算日期']).'奖金');
        M()->commit();
		$this->success('修改成功！');
	}
	public function ledger(tle $tle)
	{
		//总账表显示
        $list=new TableListAction($tle->name."总账");
        $list->table("dms_{$tle->name}总账 as a");
		$list->join("dms_{$tle->name} as b on b.计算日期=a.计算日期");
        if(adminshow('allzongzhang')>0){
           		$pricestr="";
    		foreach(X('prize_*',$tle) as $price)
    		{
    			if($price->prizeMode>=1)
    			{
    				$pricestr.=",sum(b.".$price->name.") as price".$price->getPos();
    			}
    		}
            $list->field("a.*".$pricestr);
        }else{
        $list->field("a.*");
        }
        $list->order("计算日期 desc")->group('a.计算日期');
		if(!$tle->autoGive && !$tle->weekAutoGive){
			$list->setButton = array(
			   "发放"    =>array("class"=>"add","href"=>"__APP__/Admin/Cal/givePrice:__XPATH__/id/{tl_id}","target"=>"ajaxTodo","title"=>'确定发放吗?'),
			);
			if(!$tle->haveScal()){
		   		$list->setButton["删除结算"]=array("class"=>"add","href"=>"__APP__/Admin/Tle/rollback:__XPATH__/id/{tl_id}" ,"target"=>"ajaxTodo","title"=>'确定要进行奖金删除?');			
		   	}
			if($tle->tleMode != 's' && !$tle->secAutoGive && (!X('prize_bump@') || X('prize_bump@')->tleMode=='s' || X('prize_bump@')->tleMode=='d')){
			}
		}
		
        $list->addshow("计算日期",array("row"=>"[计算日期]","format"=>"date","searchMode"=>"date","url"=>__APP__."/Admin/Tle/prizeForm:".__XPATH__."/id/[id]","target"=>"dialog",'order'=>'计算日期',"searchPosition"=>"top","excelMode"=>"text",'searchRow'=>'a.计算日期'));
		$list->addshow("新增".$this->userobj->byname,array("row"=>"[新增会员]","searchMode"=>"text","excelMode"=>"text"));
        $list->addshow("本期奖金",array("row"=>array(array(&$this,'bqjj'),"[本期奖金]","[本期业绩]"),"searchMode"=>"text","excelMode"=>"text"));
	if(adminshow('allzongzhang')>0){
		foreach(X('prize_*',$tle) as $prize)
		{
		 if($prize->prizeMode>=1)
			 {
			 	$list->addshow($prize->byname,array("row"=>array(array(&$this,'everyprice'),"[price".$prize->getPos()."]","[本期业绩]",$prize->prizeMode)));  
			 }
		}
    }
		$list ->addshow("总奖金",array("row"=>array(array(&$this,'zjj'),"[总奖金]","[总业绩]"),"searchMode"=>"text","excelMode"=>"text"));
		$list ->addshow("全部".$this->userobj->byname,array("row"=>"[全部会员]","searchMode"=>"text","excelMode"=>"text"));
		$list ->addshow("本期业绩",array("row"=>"[本期业绩]","searchMode"=>"text","excelMode"=>"text"));
		$list ->addshow("总业绩",array("row"=>"[总业绩]","searchMode"=>"text","excelMode"=>"text"));
		$list ->addshow("状态",array("row"=>array(array(&$this,"tleState"),"[state]"),"searchMode"=>"text","searchPosition"=>"top","searchSelect"=>array("已发放"=>"1","未发放"=>"0"),"searchRow"=>"[a.state]"));
		$list ->addshow("操作",array("row"=>array(array(&$this,'outdayA'),"[id]")));
        $this->assign('list',$list->getHtml());
        $this->display();
	}
	public function outdayA($id){
		return '<a href="__APP__/Admin/Tle/outday:__XPATH__/id/'.$id.'">导出本日奖金</a>';
	}
	public function outday(tle $tle)
	{
		$id=I("get.id/d");
		$data=M($tle->name.'总账')->find($id);
		header("Location:".U('Admin/Tle/index:'.__XPATH__.'?excel=1&_where='.urlencode(base64_encode(serialize(array('a.计算日期'=>array('between',array((int)$data['计算日期'],(int)$data['计算日期']))))))));
	}
	//本期奖金
	public function bqjj($nowprice,$allprice)
	{
	   $per=($allprice != 0) ? round($nowprice/$allprice,4)*100 : 0;
	   return $nowprice."(".$per."%)";
	}
	//本期业绩
	public function bqyj($nowprice,$allprice)
	{
	   $per=($allprice != 0) ? round($nowprice/$allprice,4)*100 : 0;
	   return $nowprice."(".$per."%)";
	}
	//总奖金
	public function zjj($nowprice,$allprice)
	{
	   $per=($allprice != 0) ? round($nowprice/$allprice,4)*100 : 0;
	   return $nowprice."(".$per."%)";
	}
	//总奖金
	public function everyprice($nowprice,$allprice)
	{
	 
	   $per=($allprice != 0) ? round($nowprice/$allprice,4)*100 : 0;
	   return $nowprice."(".$per."%)";
	}
	public function tleState($state)
	{
	    if($state=="0"){
			return "未发放";
		}
		if($state=="1"){
			return "已发放";
		}
		if($state=="2"){
			return "已删除";
		}
	}
	public function rollback(tle $tle)
	{
		$data = M($tle->name.'总账')->where(array('id'=>I("get.id/d")))->find();
		if(!$data)
		{
			$this->error('未找到回退记录');
		}
		if($tle->haveScal()){
			$this->error('系统含有秒结奖金，不能删除');
		}
		//如果找到后边还有记录则不能删除
		if(M($tle->name.'总账')->where(array('计算日期'=>array('gt',$data['计算日期'])))->find())
		{
			$this->error('您只能删除最后一天的奖金记录');
		}
		M()->startTrans();
		X('user')->callevent('rollback',array('time'=>$data['计算日期']));
		CONFIG('CAL_START_TIME',$data['计算日期']);
		M()->commit();
		//删除奖金构成文件
		$file=APP_PATH.'PrizeData/'.date('Y/md',$data['计算日期']).'/data.php';
 		if(is_file($file))@unlink ($file); 
		$this->success('撤销完成');
	}	
}

?>