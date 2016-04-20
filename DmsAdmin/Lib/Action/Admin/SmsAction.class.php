<?php
defined('APP_NAME') || die('不要非法操作哦!');
class SmsAction extends CommonAction {
    public function index(){
    	$smsType = CONFIG('smsType');//短信平台
		$telNumber = CONFIG('telNumber');//手机号码
		$companyCode = CONFIG('companyCode');//企业代码
		$smsUser = CONFIG('smsUser');//短信账号
		$smsPsw = CONFIG('smsPsw');//密码
		$smsKey = CONFIG('smsKey');//密钥
		$smsSign = CONFIG('smsSign');
		$adminLogin = CONFIG('adminLogin');//通知管理员
		//保存的数据
        $vo = array();
        //默认发送的内容模板
		$vo['regsmsContent'] = CONFIG("regsmsContent");
		$vo['accoksmsContent'] = CONFIG("accoksmsContent");
		$vo['changePwdsmsContent'] = CONFIG("changePwdsmsContent");
		$vo['verificatesmsContent'] = CONFIG("verificatesmsContent");//修改密验证码
		$vo['zhzhsmsContent'] = CONFIG("zhzhsmsContent");
		$vo['zhzhgetsmsContent'] = CONFIG("zhzhgetsmsContent");
		$vo['txmsmsContent'] = CONFIG("txmsmsContent");
		//监听手机号
		$vo['regsmsMobile'] = CONFIG("regsmsMobile");
		$vo['accoksmsMobile'] = CONFIG("accoksmsMobile");
		$vo['changePwdsmsMobile'] = CONFIG("changePwdsmsMobile");
		$vo['zhzhsmsMobile'] = CONFIG("zhzhsmsMobile");
		$vo['zhzhgetsmsMobile'] = CONFIG("zhzhgetsmsMobile");
		$vo['txmsmsMobile'] = CONFIG("txmsmsMobile");
		//发送开关 以及管理员监听
		$vo['regsmsSwitch'] = CONFIG("regsmsSwitch") == 1 ? 1 : 0;
		$vo['accoksmsSwitch'] = CONFIG("accoksmsSwitch") == 1 ? 1 : 0;
		$vo['changePwdsmsSwitch'] = CONFIG("changePwdsmsSwitch") == 1 ? 1 : 0;
		$vo['verificatesmsSwitch'] = CONFIG("verificatesmsSwitch") == 1 ? 1 : 0;//验证码发送
		$vo['zhzhsmsSwitch'] = CONFIG("zhzhsmsSwitch") == 1 ? 1 : 0;
		$vo['zhzhgetsmsSwitch'] = CONFIG("zhzhgetsmsSwitch") == 1 ? 1 : 0;
		$vo['txmsmsSwitch'] = CONFIG("txmsmsSwitch") == 1 ? 1 : 0;
		//查询余额
		if($smsType == false){
			$remained = '';
		}else{
			import('COM.SMS.DdkSms');
			$surplus = DdkSms::lookSurplus();
			if($surplus !='-1'){
				$remained = $surplus;
			}else{
				$remained = '';
			}
		}
		if($smsPsw !=''){
			$smsPsw = '***********';
		}
		//user={$web_duanxin_user}&pass={$web_duanxin_paw}&mobile={$mobile}&content={$content1}
		$this->assign('remained',$remained);
		$this->assign('companyCode',$companyCode);
		$this->assign('smsType',$smsType);
		$this->assign("telNumber",$telNumber);
		$this->assign("smsUser",$smsUser);
		$this->assign("smsPsw",$smsPsw);
		$this->assign("smsKey",$smsKey);
		$this->assign("smsSign",$smsSign);
        $this->assign("adminLogin",$adminLogin);
        $this->assign("vo",$vo);
    	$this->display();
    }
    public function setSms(){
        //验证平台公司代码 以便保存密码
        M()->startTrans();
        if(I("post.smsType/s") == "") $this->error("请选择短信平台");
        if(I("post.smsUser/s") == "") $this->error("请填写用户账号");
        if(I("post.pwdsmsPsw/s") == "") $this->error("请填写用户密码");
        $smsType = I("post.smsType/s");
        if($smsType == "DDK"){
	        if(I("post.pwdsmsPsw/s")!="" && I("post.pwdsmsPsw/s")!= '***********'){
				if(I("post.companyCode/s")==""){
		        	$this->error("请输入公司代码");
		        }
				$smsPsw = CONFIG('smsPsw',I("post.companyCode/s").I("post.pwdsmsPsw/s"));
			}
			CONFIG('companyCode',I("post.companyCode/s"));
			CONFIG('smsType',$smsType);
		}elseif($smsType == "ML" || $smsType == "MLGJ"){
			if(I("post.key/s") == "") $this->error("请填写接口密钥");
			CONFIG('smsPsw',I("post.pwdsmsPsw/s"));
			CONFIG('smsType',$smsType);
			CONFIG('smsKey',I("post.key/s"));
			CONFIG('smsSign',I("post.sign/s"));
		}
		if(I("post.adminLogin/s")!=""){
            $adminLogin = true;
        }else{
            $adminLogin = false;
        }
		$telNumber = CONFIG('telNumber',I("post.telNumber/s"));
		$adminLogin = CONFIG('adminLogin',$adminLogin);
		$smsUser = CONFIG('smsUser',I("post.smsUser/s"));
		CONFIG('regsmsContent',I("post.regsmsContent/s"));
		CONFIG('accoksmsContent',I("post.accoksmsContent/s"));
		CONFIG('changePwdsmsContent',I("post.changePwdsmsContent/s"));
		CONFIG('verificatesmsContent',I("post.verificatesmsContent/s"));
	    CONFIG("zhzhsmsContent",I("post.zhzhsmsContent/s"));
	    CONFIG("zhzhgetsmsContent",I("post.zhzhgetsmsContent/s"));
		CONFIG("txmsmsContent",I("post.txmsmsContent/s"));
		
		CONFIG('regsmsMobile',I("post.regsmsMobile/s"));
		CONFIG('accoksmsMobile',I("post.accoksmsMobile/s"));
		CONFIG('changePwdsmsMobile',I("post.changePwdsmsMobile/s"));
	    CONFIG("zhzhsmsMobile",I("post.zhzhsmsMobile/s"));
	    CONFIG("zhzhgetsmsMobile",I("post.zhzhgetsmsMobile/s"));
		CONFIG("txmsmsMobile",I("post.txmsmsMobile/s"));
		
		CONFIG('regsmsSwitch',0);
		CONFIG('accoksmsSwitch',0);
		CONFIG('verificatesmsSwitch',0);
		CONFIG('changePwdsmsSwitch',0);
		CONFIG('zhzhsmsSwitch',0);
		CONFIG('txmsmsSwitch',0);
		foreach(I("post.Switch/a") as $k=>$val){
			CONFIG($val,1);
		}
		M()->commit();
		$this->saveAdminLog('','',"短信设置");
        $this->success("保存完成！");
    }
	/*
	**短信平台的显示
	*/
	public function send(){
		//号码分组的显示
		$model	= M('号码编组');
		$group	= $model->select();
		$grouplist = array();
		if(isset($group))
		foreach ($group as $k=>$val){
			$model1	= M("号码");
			$member	= $model1->where('编组="'.$val['id'].'"')->count('id');
			//组成一个新数组
			$grouplist[]=array('id'=>$val['id'],'分组名称'=>$val['分组名称'],'数量'=>$member);
		}
		$this->assign('grouplist',$grouplist);
		$model	= M('短语');
		$phraselist = $model->select();
		$this->assign('phraselist',$phraselist);
		//获取账号余额
		if(CONFIG('smsUser')!='' && CONFIG('smsPsw')!=''){
			$duanxin_name = CONFIG('smsUser');//用户账户
			$duanxin_paw  = CONFIG('smsPsw');//用户密码
			if(CONFIG('smsType') == 'DDK'){
				$duanxin_paw = md5($duanxin_paw);
				$url ="http://210.5.158.31/hy/m?uid={$duanxin_name}&auth={$duanxin_paw}";
			}elseif(CONFIG('smsType') == 'ML'){
				$key  = CONFIG('smsKey');
				$url ="http://m.5c.com.cn/api/query/index.php?username={$duanxin_name}&password={$duanxin_paw}&apikey={$key}";
			}elseif(CONFIG('smsType') == 'MLGJ'){
				$key  = CONFIG('smsKey');
				$url ="http://m.5c.com.cn/api/query/?username={$duanxin_name}&password={$duanxin_paw}&apikey={$key}";
			}
			$smsreturn= $this-> smsGet($url);
			if($smsreturn<0){
				$smsreturn = $this->print_sms($smsreturn);
			}
			$this->assign("smsreturn",$smsreturn);
		}else{
			$this->assign("smsreturn",'账号或密码为空');
		}
		$this->display();
	}
	function print_sms($state){
		//$sms_state=array("-1","-2","-3","-4","-5","-6","-7","-8","-9","-10","-11","-12","-13","-14");
		$sms_name=array(
			"-1"=>"签权失败",
			"-2"=>"未检索到被叫号码",
			"-3"=>"被叫号码过多",
			"-4"=>"内容未签名",
			"-5"=>"内容过长",
			"-6"=>"余额不足",
			"-7"=>"暂停发送",
			"-8"=>"保留",
			"-9"=>"定时发送时间格式错误",
			"-10"=>"下发内容为空",
			"-11"=>"账户无效",
			"-12"=>"Ip地址非法",
			"-13"=>"操作频率快",
			"-14"=>"操作失败拓展码无效(1-999)"
		);
		if(isset($sms_name[$state])){
			return ($sms_name[$state]);
		}else{
			return "";
		}
	}
	/*
	**发送测试短信的文件
	*/
	public function testinput()
	{
		//获取账号余额
		if(CONFIG('smsUser')!='' && CONFIG('smsPsw')!=''){
			$duanxin_name = CONFIG('smsUser');//用户账户
			$duanxin_paw  = CONFIG('smsPsw');//用户密码
			if(CONFIG('smsType') == 'DDK'){
				$duanxin_paw = md5($duanxin_paw);
				$url ="http://210.5.158.31/hy/m?uid={$duanxin_name}&auth={$duanxin_paw}";
			}elseif(CONFIG('smsType') == 'ML'){
				$key  = CONFIG('smsKey');
				$url ="http://m.5c.com.cn/api/query/index.php?username={$duanxin_name}&password={$duanxin_paw}&apikey={$key}";
			}elseif(CONFIG('smsType') == 'MLGJ'){
				$key  = CONFIG('smsKey');
				$url ="http://m.5c.com.cn/api/query/?username={$duanxin_name}&password={$duanxin_paw}&apikey={$key}";
			}
			$smsreturn= $this-> smsGet($url);
			if($smsreturn<0){
				$smsreturn=$this->print_sms($smsreturn);
			}
			$this->assign("smsreturn",$smsreturn);
		}else{
			$this->assign("smsreturn",'账号或密码为空');
		}
        $this->assign("msg" ,I("get.msg/s"));
		$this->display();
	}
	/*
	**发送测试短信的文件
	*/
	public function testsend()
	{
		$mobiles = I("post.telnum/s");
		$content = I("post.msg/s");
		import('COM.SMS.DdkSms');
		
		$surplus = DdkSms::send($mobiles,$content,'会员注册');
		if($surplus['status'])
        	$this->success("测试发送成功,内容长度：".strlen($content));
        else
        	$this->error($surplus['info']);
		$this->display();
	}
	/*
	**发送
	*/
	public function smsSave(){
		if(I("post.content/s")==''){
			$this->error('请输入短信内容');
		}
		if(I("post.sendnums/s")==''){
			$this->error('请添加发送号码');
		}
		$sendnums=explode(',',I("post.sendnums/s"));
		$count=count($sendnums);
		$model=M('短信');
		$data['内容']=I("post.content/s");
		$data['发送时间']=time();
		$data['待发数量']=$count;
		$result=$model->add($data);
		//获取id
		$id=$model->order("id desc")->limit(1)->getfield('id');
		foreach ($sendnums as $k=>$val){
			$user=M('会员')->where('移动电话="'.$val.'"')->find();
			$arr11=array('[编号]','[姓名]');
			if($user['id']){
				$username=$user['姓名'];
				$arr22=array($user['编号'],$username);
				$content=str_replace($arr11,$arr22,I("post.content/s"));
			}else{
				$username='自定义号码';$arr22=array();
				$content=str_replace($arr11,$arr22,I("post.content/s"));
			}
			$model1=M('短信详细');
			$data1['d_id']=$id;$data1['接收号码']=$val;$data1['接收人']=$username;
			$data1['内容']=$content;$data1['状态']=0;
			$result1=$model1->add($data1);
		}
		if($result){
			if($result){
			//$this->runThread($id,$count);
            $cmd="php ".ROOT_PATH."clical.php Admin Sms sendsmslist id,".$id." >".ROOT_PATH."/DmsAdmin/Runtime/Logs/smssend.log";
			exec($cmd . " &",$out,$re);
			$this->success('短信正在发送中');
		}else{
			$this->error('短信未发送');
		}
	}
	}
    //发送短信
	public function sendsmslist(){
		$state=0;
		$did=$_GET['id'];
		$duanxin_name = CON('smsUser');//用户账户
		$duanxin_paw  = CON('smsPsw');//用户密码
		$smsnum=M('短信')->where('id="'.$did.'" and 待发数量>=0')->find();
		if($smsnum['待发数量']<=0){$state=2;}
		$smss=M('短信详细')->where('d_id="'.$did.'" and 状态="'.$state.'"')->order('id asc')->select();//查询未发送成功的所有记录
		//循环发送未成功的记录
		if($smss){
			foreach($smss as $sms){
				if(CONFIG('smsType') == 'DDK'){
					$duanxin_paw = md5($duanxin_paw);
					$contents = rawurlencode(trim($sms['内容']));
					$url="http://210.5.158.31:9011/hy/?uid={$duanxin_name}&auth={$duanxin_paw}&mobile={$sms['接收号码']}&msg={$contents}&expid=0&encode=utf-8";
				}elseif(CONFIG('smsType') == 'ML'){
					$duanxin_paw = md5($duanxin_paw);
					$key  = CONFIG('smsKey');
					$sign = CONFIG('smsSign');
					$content = iconv("UTF-8","GBK",$sign.trim($sms['内容']));
					$content = urlencode($content);
					$url = "http://m.5c.com.cn/api/send/index.php?username={$duanxin_name}&password_md5={$duanxin_paw}&apikey={$key}&mobile={$sms['接收号码']}&content={$content}";
				}elseif(CONFIG('smsType') == 'MLGJ'){
					$key  = CONFIG('smsKey');
					$sign = CONFIG('smsSign');
					$content = iconv("UTF-8","GBK",$sign.trim($sms['内容']));
					$content = urlencode($content);
					$url = "http://m.5c.com.cn/api/send/?username={$duanxin_name}&password={$duanxin_paw}&apikey={$key}&mobile={$sms['接收号码']}&content={$content}";
				}
				$smsreturn=$this->smsGet($url);//执行发送
				$smsary	= explode(',',$smsreturn);
				$smsfalse=$this->print_sms($smsary[0]);
				//判断发送是否成功
				if($smsary[0] == 0){
					M('短信详细')->where('id="'.$sms['id'].'"')->save(array("发送时间"=>time(),"状态"=>"1","失败原因"=>''));
				}else{
					//更新发送失败的相关数据
					M('短信详细')->where('id="'.$sms['id'].'"')->save(array("发送时间"=>time(),"状态"=>"2","失败原因"=>$smsfalse));
				}
				sleep(3);
			}
			$datat['待发数量']=M('短信详细')->where('d_id="'.$did.'" and 状态="0"')->count('id');
			$datat['已发数量']=M('短信详细')->where('d_id="'.$did.'" and 状态="1"')->count('id');
			$datat['失败数量']=M('短信详细')->where('d_id="'.$did.'" and 状态="2"')->count('id');
			M('短信')->where('id="'.$did.'"')->save($datat);
		}
	}
	/*
	**短信列表
	*/
	public function smslist(){
        $setButton=array(

			"查看"=>array("class"=>"edit","href"=>__APP__."/Admin/Sms/smsdatail/id/{tl_id}","target"=>"navTab","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_magnify.png'),
			'删除'=>array("class"=>"delete","href"=>"__APP__/Admin/Sms/dele/id/{tl_id}","target"=>"ajaxTodo" ,"title"=>"确定要删除吗?"),
			"短信设置"=>array("class"=>"edit","href"=>__APP__."/Admin/Sms/index/","target"=>"navTab","mask"=>"true",'icon'=>'/Public/Images/ExtJSicons/application/application_form_edit.png'),
        );
        $list=new TableListAction("短信");  // 实例化Model 传表名称
        $list->setButton = $setButton;      // 定义按钮显示
		$list->order("发送时间 desc");		//定义查询条件
        $list->addshow("编号",array("row"=>"<a href='".__URL__."/smsdatail/id/[id]' target='navTab' title='查看'>[id]</a>",'excelMode'=>'text'));
		$list->addshow("内容",array("row"=>"[内容]",'excelMode'=>'text'));
		$list->addshow("发送时间",array("row"=>"[发送时间]","format"=>"time","order"=>"[发送时间]"));
		$list->addshow("待发数量",array("row"=>"[待发数量]",'excelMode'=>'text',"order"=>"[待发数量]"));
		$list->addshow("已发数量",array("row"=>"[已发数量]",'excelMode'=>'text',"order"=>"[已发数量]"));
		$list->addshow("失败数量",array("row"=>"[失败数量]",'excelMode'=>'text',"order"=>"[失败数量]"));
		$this->assign('list',$list->getHtml());    
        $this->display();
	}
	public function dele(){
		$model = M("短信");
		$succNum = 0;
		$errNum = 0; 
		$errMsg = '';
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
			$where['id'] = $id;
			$result = $model -> where($where)->delete();
			$map['d_id'] = $id;
			$result1 = M("短信详细") -> where($map)->delete();
			if($result && $result1){
				$succNum++;
			}else{
				$errNum++;
			}
		}
		if($errNum !=0){
			$this->error("删除成功：".$succNum .'条记录；删除失败：'.$errNum .'条记录；');
		}else{
			$this->success("删除成功：".$succNum .'条记录；');
		}
	}
	/*
	**短信详情
	*/
	public function smsdatail(){
	
		if(strpos(I("get.id/s"),',') !== false){
			$this->error('参数错误!');
		}
		$setButton=array(
			"<img style='margin-bottom:3px;vertical-align:middle' src='__PUBLIC__/Images/view.png' /> 查看"=>array("href"=>__URL__."/smsview/id/{tl_id}","target"=>"dialog","title"=>"短信详细","width"=>"450","height"=>"300"),
			//'删除'=>array("class"=>"delete","href"=>"__APP__/Admin/sms/dele/id/{tl_id}","target"=>"ajaxTodo" ,"title"=>"确定要删除吗?"),
        );
        $list=new TableListAction("短信详细");  // 实例化Model 传表名称
        $list->setButton = $setButton;      // 定义按钮显示
		$list->excel = false;
		$list->where(array("d_id"=>I("request.id/d")))->order("发送时间 desc");		//定义查询条件
        $list->addshow("编号",array("row"=>"<a href='".__URL__."/smsview/id/[id]' target='dialog' title='短信详细' width='450' height='300'>[id]</a>"));
		$list->addshow("接收号码",array("row"=>"[接收号码]","searchMode"=>"text","searchPosition"=>"top"));
		$list->addshow("接收人",array("row"=>"[接收人]","searchMode"=>"text","searchPosition"=>"top"));
		$list->addshow("内容",array("row"=>"[内容]"));
		$list->addshow("发送时间",array("row"=>"[发送时间]","format"=>"time","order"=>"[发送时间]"));
		$list->addshow("状态",array("row"=>array(array(&$this,"printSmsState"),"[状态]"),"order"=>"[状态]"));
		$this->assign('list',$list->getHtml());    
        $this->display();
	}
	function printSmsState($state){
		if($state==0){
			return "待发送";
		}
		if($state==1){
			return "已发送";
		}
		if($state==2){
			return "发送失败";
		}
	}
	public function smsview(){
		$model=M("短信详细");
		$smsdetal=$model->where(array("id"=>I("request.id/d")))->find();
		$this->assign('smsdetal',$smsdetal);
		$this->display();
	}
	/*
	**查询会员号码
	*/
	public function check(){
		$userinfo = array();
		$userinfo= M('会员') ->where(array("移动电话"=>I("request.num/s")))->field('编号 id,姓名 name')->find();
		if($userinfo){
			echo json_encode($userinfo);
		}else{
			echo json_encode(array('id'=>'','name'=>'自定义号码'));
		}
	}
	function addgroup(){
		$this->display();
	}
	/*
	**添加短信接收手机号码编组
	*/
	public function do_addgroup(){
		$model	= M('号码编组');
		$data['分组名称'] = I("post.分组名称/s");
		$result = $model-> add($data);
		if($result){
			$this->success('添加分组完成');
		}else{
			$this->error('添加分组失败');
		}
	}
	/*
	**删除短信接收手机号码编组
	*/
	public function delgroup(){
		$result1= 1;
		$model1	= M('号码');
		M()->startTrans();
		$member = $model1->where(array("编组"=>I("request.id/d")))->count('id');
		if($member!=0){
			$result1= $model1->where(array("编组"=>I("request.id/d")))->delete();
		}
		$model	= M('号码编组');
		$result = $model->where(array("id"=>I("request.id/d")))->delete();
		if($result1 && $result){
			M()->commit();
			$this->success('数据已清除');
		}else{
			M()->rollback();
			$this->error('操作失败'.$result1.$result);
		}
	}
	/*
	**查询分组内的号码
	*/
	public function getnum(){
		$model	= M('号码');
		$numlist= $model->where(array('编组'=>I("request.id/d")))->field('号码 num,姓名 name,编号 id')->select();
		echo json_encode($numlist);
	}
	/*
	**查询分组内的号码转换成TXT文件下载
	*/
	public function downout(){
		$model	= M('号码');
		$numlist= $model->where(array("编组"=>I("request.id/d")))->select();
		$title = date("YmdHis");
		$ua = $_SERVER["HTTP_USER_AGENT"];  
		$filename = $title.".txt";  
		$encoded_filename = urlencode($filename);  
		$encoded_filename = str_replace("+", "%20", $encoded_filename);  
		header("Content-Type: application/octet-stream"); 
		if (preg_match("/MSIE/", $_SERVER['HTTP_USER_AGENT']) ) {  
			header('Content-Disposition: attachment; filename="'.$encoded_filename.'"');  
		} elseif (preg_match("/Firefox/", $_SERVER['HTTP_USER_AGENT'])) {  
			header('Content-Disposition: attachment; filename*="utf8'.$filename.'"');  
		} else {  
			header('Content-Disposition: attachment; filename="'.$filename.'"');  
		}
		$txt='';
		foreach($numlist as $k=>$val){
			$txt.=$val['号码']."   ".$val['姓名']."\r\n";
		}
		header('Content-Encoding: none');
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: " . strlen($txt));
        header('Content-Transfer-Encoding: binary');
        header("Content-Disposition: attachment; filename=" . $filename);  //以真实文件名提供给浏览器下载
        header('Pragma: no-cache');
        header('Expires: 0');
		echo $txt;
	}
	/*
	**将号码加入到编组内
	*/
	public function putinto(){
		$model	= M('号码');
		$map['号码']  = I("request.num/s");
		$map['编组']  = I("request.group/d");
		M()->startTrans();
		$id = $model ->where ($map)-> getfield('id');//查看此条号码的信息是否已加入
		
		$data['编组'] = I("request.group/d");
		$data['号码'] = I("request.num/s");
		$data['姓名'] = I("request.name/s");
		$data['编号'] = I("request.id/d");
		if($id){
			$result = $model -> where(array('id'=>$id)) -> save($data);//如果号码已存在，则更新信息
		}else{
			$result = $model -> add($data);//如果号码不存在，则插入信息
		}
		if($result){
			M()->commit();
			$this->success('已加入到分组中');
		}else{
			M()->rollback();
			$this->error('失败');
		}
	}
	/*
	**添加短语
	*/
	public function addmsg(){
		$model	= M('短语');	
		$data['短语内容'] = I("request.content/s");
		$result = $model -> add($data);
		if($result){
			$this->success('短语添加成功');
		}else{
			$this->error('短语添加失败');
		}
	}
	/*
	**删除短语
	*/
	public function delmsg(){
		$model	= M('短语');
		M()->startTrans();
		$result = $model -> where(array('id'=>I("request.id/d"))) -> delete();
		if($result){
			M()->commit();
			$this->success('短语已删除');
		}else{
			M()->rollback();
			$this->error('短语删除失败');
		}
	}
	public function addmember(){
		$nets=array();$lvs=array();$levelsopt=array();
		//会员级别
		$levelsArr = array();
		foreach(X('levels') as $key=>$levels)
		{
			$lvs[]['name']=$levels->name;
			if($key==0){
				$levelsArr[$levels->name] = array();
				foreach($levels->getcon("con",array("name"=>"","lv"=>"")) as $lvconf)
				{
					$levelsArr[$levels->name][] = $lvconf;
				}
			}
		}
		foreach(X('net_rec,net_place') as $net)
		{
			$nets[]=array("name"=>$this->userobj->byname.$net->byname."网络","path"=>$net->objPath());
		}
		$this->assign('levels',$lvs);
		$this->assign('levelsArr',$levelsArr);
		$this->assign('nets',$nets);
		$this->display();
	}
	/*
	**查询添加会员级别所有
	*/
	public function add_all(){
		$map = array();
		if(I("request.leve/d")>0){
			$map['会员级别']=I("request.leve/d");
		}
		$users= M('会员') -> where($map) -> field('编号 id,姓名 name,移动电话 num') -> select();
		echo json_encode($users);
	}
	//查询添加编号会员
	public function add_num(){
		$users= M('会员')->where(array('编号'=>I("request.userid/s")))->field('编号 id,姓名 name,移动电话 num')->select();
		echo json_encode($users);
	}
}
?>