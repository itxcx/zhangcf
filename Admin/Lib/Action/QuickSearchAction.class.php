<?php
// 快捷搜索模块
class QuickSearchAction extends Action 
{
	public function index()
	{
		$value		= I("request.value/s");  //输入的值
        import('DmsAdmin.DMS.stru');
		$con=new stru();
		$html='';
		//1. 搜索user会员表
		$user = X('user');
		//编号搜索
		/****** 编号模糊查询******/
		$finduser=M('会员','dms_')->where(array('编号'=>array('like','%'.$value.'%')))->select();
		if(empty($finduser))
		{
			/****** 姓名模糊查询******/
			$finduser=M('会员','dms_')->where(array('姓名'=>array('like','%'.$value.'%')))->select();
		}
	
		//foreach($rsidlike as $v)
		//{
		//	$namestr='';
		//	if($v['姓名']!='')
		//	$namestr.='('.$v['姓名'].')';
		//	$html.='<a class="edit" href="#" search="'.$v['编号'].'" target="search"  title="查看'.$user->name.'"><span>['.$v['编号'].$namestr.']</span></a>&nbsp;&nbsp;';
		//}

		//返回值
		/****当精确查询和模糊查询的结果只有一个时****/
		if(count($finduser)==1){
				
				$userdata=$finduser[0];
				$id=$userdata['id'];
				$username=$userdata['姓名'];
				$userid=$userdata['编号'];
				$html.='<table class="list"><tfoot>';
				$html.='<tr><td><img src="/Public/Images/ExtJSicons/user/user.png"></td><td style="float:left;height:20px"><a href="/index.php?s=/Admin/User/loginToUser/id/'.$userid.'/args/'.$user->objPath().'" rel="admin_edit" target="_blank" title="登陆'.$user->byname.'前台"><span>编号:'.$userid.'</span></a>&nbsp;&nbsp;&nbsp;</td></tr>';
				$html.='<tr><td><img src="/Public/Images/ExtJSicons/application/application_form_edit.png"></td><td style="float:left;height:20px"><a href="/index.php?s=/Admin/User/edit/id/'.$id.'/args/'.$user->objPath().'" rel="admin_edit" target="navTab" mask="true" width="550" height="420" title="修改'.$user->byname.'"><span>姓名：'.$username.'</span></a></td></tr>';
                //得到货币的明细
				foreach(X('fun_bank') as $funbank)
				{
                    $html.='<tr><td><img src="/Public/Images/ExtJSicons/table/table_lightning.png"></td><td style="float:left;height:20px" ><a href="/index.php?s=/Admin/FunBank/index/userid/'.$userid.'/args/'.$funbank->objPath().'" rel="admin_edit" target="navTab" title="'.$user->name.$userid.$funbank->name.'明细"><span>'.$funbank->name.':'.$userdata[$funbank->name].'</span></a></td></tr>';
				}
				//得到销售奖金的明细
				foreach(X('tle') as $tle)
				{
                    $html.='<tr><td><img src="/Public/Images/ExtJSicons/coins.png"></td><td style="float:left;height:20px" ><a href="/index.php?s=/Admin/Tle/index/userid/'.$userid.'/args/'.$tle->objPath().'" rel="admin_edit" target="navTab" title="'.$user->name.$userid.$tle->name.'"><span>'.$tle->name.'查询</span></a></td></tr>';
				}
                //得到福利奖的明细
				foreach(X('fun_fuli') as $fuli)
				{
                    $html.='<tr><td><img src="/Public/Images/ExtJSicons/package.png"></td><td style="float:left;height:20px" ><a href="/index.php?s=/Admin/FunFuli/index/userid/'.$userid.'/args/'.$fuli->objPath().'" rel="admin_edit" target="navTab" title="'.$user->name.$userid.$fuli->name.'"><span>'.$fuli->name.'查询</span></a></td></tr>';
				}
				$salenum = M('报单','dms_')->where(array('编号'=>$userid))->count();
				//得到订单列表
                $html .='<tr><td><img src="/Public/Images/ExtJSicons/application/application_view_detail.png"></td><td style="float:left;height:20px" ><a href="/index.php?s=/Admin/Sale/index/userid/'.$userid.'/args/'.$user->objPath().'" rel="admin_edit" target="navTab" title="'.$user->name.$userid.'订单"><span>订单查询:'.$salenum.'</span></a></td></tr>';
                $html.='</tfoot></table >';
		}else{
			if(count($finduser)<20)
			{
				$html='<table class="list"><tr>';
				foreach($finduser as $k=>$v)
				{
					$namestr='';
					if($v['姓名']!='')
					$namestr.='('.$v['姓名'].')';
					$html.='<td><a class="edit" href="#" search="'.$v['编号'].'" target="search"  title="查看'.$user->name.'"><span>['.$v['编号'].$namestr.']</span></a></td>';
					if($k % 3 == 2)
					{
						$html.='</tr><tr>';
					}
				}
				$html.='</tr></table>';
			}
		}
		echo $html;
	}
}
?>