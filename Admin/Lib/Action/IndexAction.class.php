<?php
/*
* 管理后台默认模块
*/
class IndexAction extends CommonAction 
{
	/*
	* 默认方法
	*/
    public function index()
	{
		$Node			= M('Node');
		$appList		= array();
		//判断是否需要进行数据库修正
		/*$md5=md5_file(ROOT_PATH."/DmsAdmin/config.xml");
		if($md5!=CONFIG('XMLMD5'))
		{	
			M()->startTrans();
			CONFIG('XMLMD5',$md5);
			M()->commit();
			//R('DbRevise/index',array(false));
		}*/
		import ( 'ORG.Util.RBAC' );
		$showinfo=false;
		//不是超级管理员
		if(!isset($_SESSION[C('RBAC_SUPER_ADMIN_KEY')]))
		{
			$adminapp=RBAC::readAccessList($_SESSION[C('RBAC_ADMIN_AUTH_KEY')]);
			if($adminapp!=""){
				//获取有权限的应用列表
				foreach($adminapp as $_appName=>$appData)
				{
					$appNameGroup				= explode('_',$_appName);
					$appName					= $appNameGroup[0];
					$appGroup					= $appNameGroup[1];
					$where['level']				= 1;
					$where['status']			= 1;
					$where['is_sync_menu']		= 1;
					$where['type']				= 0;
					$where['name']				= strtolower($appName);
					$where['group']				= strtolower($appGroup);
					$app						= $Node->where($where)->find();
					if( $app ) 
						$appList[] = $app;
				}
				//判断首页信息的应用
				$showinfo=isset($adminapp['ADMIN_']['INDEX']["CHECKXML"]);
			}
		}
		else
		{
			$appList = $Node->where("level=1 and status=1 and is_sync_menu=1 and type=0")->order("sort asc")->select();
			$showinfo=true;
		}
		$appStr = '';
		foreach($appList as $v){
			$appStr .=$v['name'].',';
		}
		$appStr = trim($appStr,',');
		define('APP_LIST',$appStr);
		/*
		* 需要做的几件事
		1: 从权限列表里面取出,当前角色有权限的应用列表
		2: 获取第一个应用的名称，并将其做为默认应用显示
		*/
		$this->assign('appNameList',explode(',',APP_LIST));
		$settlementTime=CONFIG('CAL_START_TIME');
		$TIMEMOVE_HOUR=CONFIG('TIMEMOVE_HOUR');
		$TIMEMOVE_DAY=CONFIG('TIMEMOVE_DAY');
		$shifttime=($TIMEMOVE_HOUR+$TIMEMOVE_DAY*24)*3600*1000;
		$this->assign('shifttime',$shifttime);
		M()->startTrans();
		$Sync			= D('Sync');
		$adminApp		= array();
		//提取后台设置的节点
		foreach( $appList as $key=>$app )
		{
			//系统设置 排除 应用列表
			if( $app['name']=='Admin' )
			{
				$adminApp			= $app; //系统设置独立出来
				$adminApp['menu']	= $Sync->syncAppMenuList( $app['id'] );
				unset($appList[$key]); //删除
			}
			else
			{
				$appList[$key]['menu_list']	= $Sync->syncAppMenuList1( $app['id'] );
			}
		}
		$this->assign('adminApp',$adminApp);
		$nolookmsg=M('邮件','dms_')->where("收件人类型='管理员' and 状态=0")->count();
		$userinfo['nolookmsg'] = $nolookmsg;
		$notranders=M('汇款通知','dms_')->where('状态=0')->count('id');
		$userinfo['未处理汇款']=$notranders;
		$this->assign('showinfo',$showinfo);
		$this->assign('userinfo',$userinfo);
		$this->assign('Sync',$Sync);
		$this->assign('appList',$appList);
		$this->assign('haveProduct',X('user')->haveProduct());
		M()->commit();
		//检查压缩文件
		$filestr=IfZipExists();
		$this->assign('filestr',$filestr);
		$this->display();
    }
    public function checkxml(){
        $addnet=array();
    	foreach(X('sale_*') as $sale)
		{
			$addcon=$sale->getcon('addval',array(),true);
			foreach($addcon as $con)
			{
				$obj=X('@'.$con['to']);
				if(get_class($obj) == "net_place" && !isset($con['set']))
				{
					if(!isset($addnet[$obj->byname]))
					{
						$addnet[$obj->byname]=array();
					}
					$addnet[$obj->byname][]=$sale->name.'('.$sale->byname.')';
				}
			}
		}
		foreach(X('prize_*') as $prize)
		{
			if(property_exists($prize,'netName'))
			{
				$prizeNet[$prize->byname]=$prize->netName;
			}
		}
		$html='<div style="float:left;width:400;margin-right:15px;">';
		if(isset($prizeNet)){
			$html.='<table width="300px" class="list">
				<thead>
					<tr>
					  <th colspan="5" style="text-align:left"><img style="vertical-align:middle" src="/Public/Images/ExtJSicons/chart/chart_organisation.png" />&nbsp;&nbsp;奖金网络使用</th>
					</tr>
				</thead>
				<tfoot>
					';
			foreach($prizeNet as $prizename=>$netName){
				$html.='<tr><td style="text-align:right;width:50%;border-bottom:1px solid #ededed" align="right">'.$prizename.'：</td>
					<td style="text-align:left;border-bottom:1px solid #ededed">'.$netName.' </td></tr>';
			}
			$html.='</tfoot>
			</table>
			';
		}
		if(isset($addnet)){
			foreach($addnet as $netname=>$saleary){
				$html.='<table width="300px" class="list">
					<thead>
						<tr>
						  <th colspan="5" style="text-align:left"><img style="vertical-align:middle" src="/Public/Images/ExtJSicons/chart/chart_organisation.png" />&nbsp;&nbsp;以下订单会进入'.$netname.'网络业绩</th>
						</tr>
					</thead>
					<tfoot>
						';
				foreach($saleary as $salename){
					$html.='<td style="text-align:center;border-bottom:1px solid #ededed">'.$salename.' </td></tr>';
				}
				$html.='</tfoot>
				</table>
			';
			}
		}
		$html.='</div>';
		echo $html;
    }
    //删除压缩文件
    public function delzip(){
    	$re = delZips();
    	if($re){
    		echo '删除成功';
    	}else{
    		echo '删除失败';
    	}
    }
}
?>