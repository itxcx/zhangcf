<?php
// 本类由系统自动生成，仅供测试用途
defined('APP_NAME') || die('不要非法操作哦!');
class IndexAction extends Action {
    public function index() {
    	$error='';
    	//报单表与货币表中会员与会员表不对应
    	import ( 'DmsAdmin.CHECK.sale_CK');
     	$sale = new sale_CK;
		$callback = call_user_func_array(array($sale,"check"),array());
		if($callback !=1 )$error .= $callback."<br>";
    	/*****************货币相关*********************
		货币明细与当前货币余额不对应
		货币明细中会员与会员表不对应
		**********************************************/
     	import ( 'DmsAdmin.CHECK.funbank_CK');
     	$funbank = new funbank_CK;
   		foreach(X('fun_bank') as $banks){
			$callback = call_user_func_array(array($funbank,"check"),array($banks->name));
			if($callback !=1 )$error .= $callback."<br>";
		}
		
		/*****************奖金相关*********************
		prize奖金prizemode=1的奖金，在奖金表记录中为负数
		prize奖金prizemode=2的奖金，在奖金表记录中为正数
		各个奖金累计与会员表中的累计不符
		奖金明细中奖金或者收入为负
		累计收入不对应
		**********************************************/
     	import ( 'DmsAdmin.CHECK.prize_CK');
     	$prizes = new prize_CK;
     	$tlearr = array();
     	foreach(X("tle") as $tle)
        {
			foreach(X('prize_*',$tle) as $prize)
			{
				if($prize->prizeMode >= 0)
				{
					$callback = call_user_func_array(array($prizes,"check"),array($tle->name,$prize->name,$prize->prizeMode));
					if($callback !=1 )$error .= $callback."<br>";
					$callback = call_user_func_array(array($prizes,"checklj"),array($tle->name,$prize->name,$prize->prizeMode));
					if($callback !=1 )$error .= $callback."<br>";
				}
				if(get_class($prize)=='prize_bump'){
					//检查奖金表中结转业绩和累计业绩
					$cons  = $prize->getcon('con',array("bump"=>"","val"=>"","where"=>"",'only'=>false,'top'=>0));
					$callback = call_user_func_array(array($prizes,"checkTlePv"),array($tle->name,get_class(X("@".$prize->netName)),$cons));
					if($callback !=1 )$error .= $callback."<br>";
				}
			}
			$callback = call_user_func_array(array($prizes,"checkTle"),array($tle->name,$prize->name,$prize->prizeMode));
			if($callback !=1 )$error .= $callback."<br>";
			
			
			//所有奖金表
			$tlearr[]=$tle->name;
		}
		if(!empty($tlearr)){//检查会员表中累计收入
			$callback = call_user_func_array(array($prizes,"checklj"),array($tlearr));
			if($callback !=1 )$error .= $callback."<br>";
		}
		
		/*****************网络相关*********************
		net_place会员表中 管理_x区累计 ，新增，结转  与 管理_业绩不符
		net_place  net_rec网络数据异常，层数异常  ，上级不存在 ，大小写不相符。
		推荐人数的检查
		**********************************************/
     	import ( 'DmsAdmin.CHECK.net_CK');
     	$nets = new net_CK;
		foreach(X('net_rec,net_place') as $net)
        {
        	//上级编号是否存在（包括大小写不相符）
        	$callback = call_user_func_array(array($nets,"check"),array($net->name));
        	if($callback !=1 )$error .= $callback."<br>"; 
        	
        	//检测网体数据
        	$callback = call_user_func_array(array($nets,"checknetdata"),array($net->name,$net));
        	if($callback !=1 )$error .= $callback."<br>"; 
        	
          	if(get_class($net)=='net_place' && $net->pvFun)
        	{
        		//管理层数不符合
        		$callback = call_user_func_array(array($nets,"checkceng"),array($net->name));
        		if($callback !=1 )$error .= $callback."<br>";
        		//管理_x区累计 ，新增，结转  与 管理_业绩不符
        		$callback = call_user_func_array(array($nets,"checkyj"),array($net->name,$net));
        		if($callback !=1 )$error .= $callback."<br>";
        		
        	}
        	if(get_class($net)=='net_rec')
        	{
        		//推荐人数不符合
        		$callback = call_user_func_array(array($nets,"checktjs"),array($net->name));
        		if($callback !=1 )$error .= $callback."<br>";
        		//推荐层数不符合
        		$callback = call_user_func_array(array($nets,"checkceng"),array($net->name));
        		if($callback !=1 )$error .= $callback."<br>";
        	}
        }	
		
		if($error=='')echo "<br><span style='color:green;'>无异常</span>";
		else echo $error;
		
    }
}
?>