<?php
class CommonAction extends Action
{
	 public $con='';
	 public $userobj;
	/*
	* 权限验证方法,该方法要保证在每个Action里面执行,可以将该方法放到公共头文件里面
	*/
	
	function _initialize() 
    {
    	diffTime();
		//检查参数设置
		$this->userobj = X('user');
		import("Admin.Action.TableListAction");
		//设置字符编码
		//cli不需要进行权限验证
		if(IS_CLI)
		{
			return true;
		}
		//Header("Content-Type:text/html;charset=utf-8");
		if(MODULE_NAME=='Sms' && (ACTION_NAME=="runThread" || ACTION_NAME=="sendsmslist" || ACTION_NAME=="sendfalse")){ return true; }
		//对自动结算进行过滤
		if(isset($_GET['calpass']) && $_GET['calpass'] == F('calpass') && MODULE_NAME == 'Cal' && ACTION_NAME == 'settlementExecute'){
			return true;
		}
		if(ACTION_NAME == 'threadReg')
		{
			$ip       = get_client_ip();
			$ip_array = explode('.',$ip);
			if(($ip_array[0]==192 && $ip_array[1]==168 )||$ip='127.0.0.1')
			return true;
		}
		// 用户权限检查
		if (C ( 'RBAC_ADMIN_AUTH_ON' ) && !in_array(MODULE_NAME,explode(',',C('RBAC_NOT_AUTH_MODULE')))) 
		{
			import ( 'ORG.Util.RBAC' );
			if (! RBAC::AccessDecision()) //检查权限
			{	
				//如果是ajax请求
				if ( $this->isAjax() )
				{
					//如果没有登录
					if (! isset($_SESSION [C ( 'RBAC_ADMIN_AUTH_KEY' )])) 
					{
						//跳转到认证网关
						$this->ajaxReturn(0,'','301');
					}
					else
					{
						// 提示错误信息
						$this->error('无权限操作');
					}
				}
				//不是ajax
				else
				{
					//如果没有登录
					if (! $_SESSION [C ( 'RBAC_ADMIN_AUTH_KEY' )] ) 
					{
						//跳转到认证网关
						redirect ( "/Admin/?s=" . C ( 'RBAC_ADMIN_AUTH_GATEWAY' ) );
					}
					else
					{
						
						// 提示错误信息
						$this->assign('waitSecond',20);
						$this->assign('closeWin',true);
						$this->error('无权限操作, <a href="?s=/Public/logout" style="color:red">退出登录</a>!');
					}
				}
			}
		}
		//防XSS攻击检查
		if(!in_array(get_client_ip(),$_SESSION['loginIp']))
		{
			$this->ajaxReturn(0,'您的ip有变化存在安全隐患,请重新登录','301');
			die();
		}
		$relodargs=strstr($_SERVER['REQUEST_URI'],'&');
        $this->assign("relodargs",$relodargs);
        $this->assign("time",time());
		//秒结跨日检查
	}
	//程序出错报错
	public static function onError($errno, $errstr, $errfile, $errline)
	{
		//找出抛出的错误警告不需要
		switch ($errno) {
			case E_ERROR:
			case E_USER_ERROR:
				$result_error=json_encode(array("error"=>"[$errno] $errstr " . basename($errfile) . " 第 $errline 行."));
				self::errorSave($result_error);
				break;
		}
	}
	public static function onException($e){
		$result_error=json_encode($e->__toString());
		self::errorSave($result_error);
	}
	public static function my_exception_handler()
	{
	    if($e = error_get_last()) {
    		$errstr=json_encode($e);
	    	self::errorSave($errstr);
	    }
	}
	private static function errorSave($errstr)
	{
		$data=array(
			"post_data"=>$errstr,
			"admin_id"=>0,
			"application"=>"DmsAdmin",
			"group"=>"Admin",
			"module"=>"Tle",
			"action"=>"settlementExecute",
			"content"=>"自动结算出错",
			"post_data"=>$errstr,
			"memo"=>"自动结算出错记录",
			"create_time"=>systemTime()
		);
		M("log"," ")->add($data);
	}
	//xpath方法引导,根据action方法名得到节点对象和要调用的方法
	function __call($name,$args)
	{
		//如果不是用于加载节点的方法，则直接交给action处理
		if(strpos($name,':') === false)
		{
			parent::__call($name,$args);
		}
		list($name,$xpath) = explode(':',$name);
		if($xpath==""){
			throw_exception('未设定XPATH值或本身就不应存在');
		}
		$obj = X('>',$xpath);
		if(!$obj)
		{
			throw_exception('未能找到对象');
		}
		
		if(!method_exists($this,$name))
		{
			parent::__call($name,$args);
		}
		
		define("__XPATH__",$xpath);
		
		call_user_func_array(array($this,$name),array($obj));
		
	}
	/**
    +----------------------------------------------------------
	* 保存后台用户操作日志
    +----------------------------------------------------------
	*/
    protected function saveAdminLog($oldData,$newData=null,$content,$memo=null)
    {
        $oldp=C('DB_PREFIX');
        if($oldp!="")
            C('DB_PREFIX',"");
		$Model  = D('Admin://Log');
        C('DB_PREFIX',$oldp);
        return $Model->saveAdminLog($oldData,$newData,$content,$memo);
    }
    public function handle(){
    	//处理货币交易的超时操作
		foreach(X("fun_gold") as $glod){
			//撤销超时购买未付款 自动撤销购买
			if($glod->payTime>0){
				$paytime=$glod->payTime*60;
				$buyinfos=M($glod->name."购买")->where("购买时间+".$paytime."<=".systemTime()." and 状态='待付'")->select();
				foreach($buyinfos as $buyinfo){
					M()->startTrans();
					systemTime($buyinfo['付款时间']+$paytime);
					$result=$glod->cancelBuy($buyinfo['id']);
					if(gettype($result)!='string'){
						M()->commit();
					}else{
						M()->rollback();
					}
				}
				$this->userobj->adduserlog($this->userinfo,get_client_ip(),"自动取消超时付款买入".$gold->name."订单");
			}
			//撤销超时购买未确认 自动确认
			if($glod->confirmTime>0){
				$confirmTime=$glod->confirmTime*60;
				$confinfos=M($glod->name."购买")->where("付款时间+".$confirmTime."<=".systemTime()." and 状态='已付'")->select();
				foreach($confinfos as $confinfo){
					M()->startTrans();
					systemTime($confinfo['付款时间']+$confirmTime);
					$selluser=M("会员")->where(array("编号"=>$confinfo['买家编号']))->find();
					$result=$gold->accokTrad($selluser,$confinfo);
					if(gettype($result)!='string'){
						M()->commit();
					}else{
						M()->rollback();
					}
				}
			}
		}
    }
	// 获得用户级别信息 $Level级别lv $levelname级别类别名称
	public function _printUserLevel($level,$levelname="",$salename='',$saleid=0)
	{	
		if($levelname=='' && $salename!='')
		{
			$saleup = X("sale_up@".$salename);
			if($saleup)
			$levelname = $saleup->lvName;
		}
		
		$ret='';
		if($levelname!=''){
			$levels=X('levels@'.$levelname);
			foreach($levels->getcon("con",array("name"=>"","lv"=>"","area"=>"")) as $lvconf)
			{
				if($level == $lvconf['lv'])
				{
					$ret=$lvconf['name'];
					//显示区域代理信息
					if($lvconf['area']!='' && $saleid>0){
						$sale=M('报单')->where(array("id"=>$saleid))->find();
						switch($lvconf['area']){
							case "country":$ret.="<br>".$sale['代理国家'];break;
							case "province":$ret.="<br>".$sale['代理国家']."-".$sale['代理省份'];break;
							case "city":$ret.="<br>".$sale['代理国家']."-".$sale['代理省份']."-".$sale['代理城市'];break;
							case "county":$ret.="<br>".$sale['代理国家']."-".$sale['代理省份']."-".$sale['代理城市']."-".$sale['代理地区'];break;
							case "town":$ret.="<br>".$sale['代理国家']."-".$sale['代理省份']."-".$sale['代理城市']."-".$sale['代理地区']."-".$sale['代理街道'];break;
						}
					}
					break;
				}
			}
		}
		return $ret;
	}
	public function lang($name,$langname){
	  if(C('My_LANG_SWITCH_ON')){
			return L($langname);
		}else{
           	return $name;
	   }
	}
	function smsGet($url)
	{
		if(function_exists('file_get_contents'))
		{
			$file_contents = file_get_contents($url);
		}
		else
		{
			$ch = curl_init();
			$timeout = 5;
			curl_setopt ($ch, CURLOPT_URL, $url);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$file_contents = curl_exec($ch);
			curl_close($ch);
		}
		return $file_contents;
	} 
	//判断该系统中是否有对碰奖 如果有对碰奖则正常显示业绩和结转业绩 如果没有对碰奖的话怎不显示结转业绩 如果没有net_place 
	function is_BumpPrize(){
	   $i=0;
	   $prizes = X('prize_bump');
	   foreach($prizes as $prize){
         $i++;
	   }
	   if($i){
	     return true;
	   }else{
	     return false;
	   }
	}
}
?>