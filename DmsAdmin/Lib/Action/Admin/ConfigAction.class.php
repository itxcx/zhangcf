<?php
defined('APP_NAME') || die('不要非法操作哦!');
class ConfigAction extends CommonAction{
	//奖金参数设置
	public function tleedit(){
		//读取xml的配置 获取奖金配置中的tleset的数据以及参数设置
		$xml=file_get_contents(ROOT_PATH."/DmsAdmin/config.xml");
		$xml=substr($xml,strpos($xml,"<tleset"));
		$xml=substr($xml,strpos($xml,">")+1);
		$xml=substr($xml,0,strpos($xml,"</tleset>"));
		$content=$xml;
		//获取标题行 根据table的title数据
		preg_match_all("/<table[^>]*\>/",$content,$outs);
		foreach($outs[0] as $out)
		{
			$tablename=$this->gettag($out,'title');
			if($tablename)
			{
				$content=str_replace($out,$out.'<thead><th colspan="10" style="text-align:left;padding-left:10px;color:#153989"><img src="__PUBLIC__/Images/cog.png" style="vertical-align:middle;padding-right:5px;" >'.$tablename.'</th></thead>',$content);
			}
			else
			{
				$content=str_replace($out,$out.'<thead><th colspan="10">&nbsp;</th></thead>',$content);
			}
		}
		//增加表格样式 根据table下的tr 以及td标签
		$content=str_replace('<table','<table class="list" ',$content);
		//读取配置值（可计算）
		preg_match_all("/{[^}]+}/",$content,$outs);
		foreach($outs[0] as $out)
		{
			$str=str_replace("{",'',$out);
			$str=str_replace("}",'',$str);
				preg_match_all('/#(.*)#/U',$str,$truevals,PREG_SET_ORDER);
				if(count($truevals)>0){
					foreach($truevals as $trueval)
					{
						if($trueval[1] !=''){
							$str=str_replace("#".$trueval[1]."#",CONFIG($trueval[1]),$str);
						}
					}
				}
			$val=transform($str);
			$content = str_replace($out,$val,$content);
		}
		//默认值赋值处理
		//--查询所有表单
		preg_match_all("/<input[^>]*\>/",$content,$outs);
		//循环所有表单
		foreach($outs[0] as $input)
		{
			//得到表单名
			$name=$this->gettag($input,'name');
			//得到表单类型
			$type=$this->gettag($input,'type');
			//得到默认值value
			$value=$this->gettag($input,'value');
			//如果类型为null
			if($type==null)
			{
				$type='text';
			}
			if(CONFIG('',$name))
			{
				$nowval=CONFIG($name);
				if($type=='date')
				{
					$nowval=date('Y-m-d',$nowval);
				}
			}
			else
			{
				$nowval = $value;
			}
			if($type=='text')
			{
				//对表单原有的value进行删除
				$newinput = $this->settag($input,'value',$nowval);
				if($this->gettag($input,'size') == null)
				{
					$newinput = $this->settag($newinput,'size',8);
				}
				$content = str_replace($input,$newinput,$content);
			}
			if($type=='date')
			{
				$newinput = $this->settag($input,'value',$nowval);
				$newinput = $this->settag($newinput,'class','date textInput');
				$content = str_replace($input,$newinput,$content);
			}
			if($type=='checkbox')
			{
				//如果设置过值.则需要处理.否则不需要动
				if(CONFIG('',$name))
				{
					if(CONFIG($name)==$value)
					{
						$newinput = $this->settag($input,'checked','checked');
					}
					else
					{
						$newinput = $input;
						preg_match("/checked\s*=\s*['\"]?([^\s\>'\"]+)['\"].*?/",$newinput,$ifchecked);
						if($ifchecked)
						{
							$newinput = str_replace($ifchecked[0],'',$newinput);
						}
					}
					$content = str_replace($input,$newinput,$content);
				}
			}
		}
		//对select循环进行处理
		preg_match_all('/<select[^>]*[^>]*>[^<]*(<option[^>]*>[^<]*<\/option>[^<]*)*<\/select>/i',$content,$outs2);
		foreach($outs2[0] as $out2)
		{
			preg_match("/name\s*=\s*['\"]?([^\s\>'\"]+)['\"]?/",$out2,$out22);
			$name=$out22[1];
			if(CONFIG('',$name))
			{
				$nowval=CONFIG($name);
				preg_match_all('/<option[^>]*>[^<]*<\/option>/i',$out2,$uee);
				foreach($uee[0] as $uees){
					$valuesel=$this->gettag($uees,'value');
					if($nowval==$valuesel){	
						$b2=str_replace('<option','<option selected=selected ',$uees);
						$contentss=str_replace($uees,$b2,$out2);
						$content=str_replace($out2,$contentss,$content);
					}
				}
			}
		}
		$this->assign('content',$content);
		$this->display();
	}
	
	public function settag($html,$tagname,$val){
		preg_match("/".$tagname."\s*=\s*['\"]?([^\s\>'\"]+)['\"].*?/",$html,$out2);
		if(count($out2)==0)
		{
			return str_replace('/>'," ".$tagname."='".$val."'/>",$html);
		}
		else
		{
			return str_replace($out2[0],"".$tagname."='".$val."'",$html);
		}
	}
	public function gettag($html,$tagname){
		preg_match("/$tagname\s*=\s*['\"]?([^\s\>'\"]+)['\"].*?/",$html,$out2);
		if(count($out2)==0)
		{
			return null;
		}
		return $out2[1];
	}
	
	public function getcons(){
		$ret=array();
		$content=file_get_contents(ROOT_PATH."/DmsAdmin/config.xml");
		$content=substr($content,strpos($content,"<tleset"));
		$content=substr($content,strpos($content,">")+1);
		$content=substr($content,0,strpos($content,"</tleset>"));
		$content=$content;
		preg_match_all("/<input[^>]*\>|<select[^>]*\>/",$content,$inputs);
		foreach($inputs[0] as $input)
		{
			//表单标签的name值
			$name    =$this->gettag($input,'name');
			//$value   =$this->gettag($input,'value');
			//表单标签的类型
			$type    =$this->gettag($input,'type');
			//判断提交的数据是字符串类型还是数字类型
			$isnum	 =$this->gettag($input,'isnum');
			//对比较特殊的情况进行处理,如果是checkbox的话.没有设置checked,则要使用offvalue作为value值
			$offvalue=$this->gettag($input,'offvalue');
			if($type == 'checkbox')
			{
				if($this->gettag($input,'checked') == null)
				{
					$value=$offvalue;
				}
			}
			$ret[]=array('name'=>$name,/*'value'=>$value,*/'offvalue'=>$offvalue,'type'=>$type,'isnum'=>$isnum!=''?$isnum:true);
		}
		return $ret;
	}
	//系统设置
	public function sysedit(){
		//获得时间信息设置数据
		if(!CONFIG('SYSTEM_STATE'))
		{
			M()->startTrans();
			CONFIG('SYSTEM_STATE',1);
			M()->commit();
		}
		if(CONFIG('USER_LOGIN_VERIFY') === null){
			M()->startTrans();
			CONFIG('USER_LOGIN_VERIFY',1);
			M()->commit();
		}
		$this->assign('SYSTEM_STATE',CONFIG('SYSTEM_STATE'));
		//编号生成 需判断是豪华版还是简化版
		$this->assign('Complete',true);
		$this->assign('SYSTEM_TITLE',CONFIG('SYSTEM_TITLE'));
		$this->assign('SYSTEM_COMPANY',CONFIG('SYSTEM_COMPANY'));
		$this->assign('SYSTEM_MEMO',CONFIG('SYSTEM_MEMO'));
		$this->assign('USER_LOGIN_VERIFY',CONFIG('USER_LOGIN_VERIFY'));
		$this->assign('USER_LOGIN_VERIFY_TYPE',CONFIG('USER_LOGIN_VERIFY_TYPE'));
		$this->assign('DEFAULT_USER_PASS1',CONFIG('DEFAULT_USER_PASS1'));
		$this->assign('DEFAULT_USER_PASS2',CONFIG('DEFAULT_USER_PASS2'));
		$this->assign('DEFAULT_USER_PASS3',CONFIG('DEFAULT_USER_PASS3'));
		$this->assign('pwd3Switch',adminshow('pwd3Switch'));
		//客服qq的设置
		$this->assign('TYPE_QQ',CONFIG('TYPE_QQ'));
		$this->assign('SERVICE_QQ_0',CONFIG('SERVICE_QQ_0'));//普通qq
		$this->assign('SERVICE_QQ_1',CONFIG('SERVICE_QQ_1'));//营销qq
		
		$this->assign('SYSTEM_CLOSE_TITLE',CONFIG('SYSTEM_CLOSE_TITLE'));
        //获得用户数据设置信息
		$showarr   =explode(',',CONFIG('USER_REG_SHOW'));
		$editarr   =explode(',',CONFIG('USER_EDIT_SHOW'));
		$viewarr   =explode(',',CONFIG('USER_VIEW_SHOW'));
		$trutharr  =explode(',',CONFIG('USER_TRUTH'));
		$requirearr=explode(',',CONFIG('USER_REG_REQUIRED'));
		
		$idEdit=$this->userobj->getatt('idEdit');
		$idAutoEdit=$this->userobj->getatt('idAutoEdit');
		$idRand=$this->userobj->getatt('idRand');
		$idInDate=$this->userobj->getatt('idInDate');
		$idSerial=$this->userobj->getatt('idSerial');
		$idPrefix=$this->userobj->getatt('idPrefix');
		$idLength=$this->userobj->getatt('idLength');
		$onlyMobile=$this->userobj->getatt('onlyMobile');
		$onlyIdCard=$this->userobj->getatt('onlyIdCard');
		$onlyBankCard=$this->userobj->getatt('onlyBankCard');
		$user=array(
			'show'=>$showarr,
			'edit'=>$editarr,
			'view'=>$viewarr,
			'truth'=>$trutharr,
			'require'=>$requirearr,
			'idEdit'=>$idEdit,
			'idRand'=>$idRand,
			'idAutoEdit'=>$idAutoEdit,
			'idInDate'=>$idInDate,
			'idSerial'=>$idSerial,
			'idPrefix'=>$idPrefix,
			'idLength'=>$idLength,
			'onlyMobile'=>$onlyMobile,
			'onlyIdCard'=>$onlyIdCard,
			'onlyBankCard'=>$onlyBankCard,
		);
		
		
		$this->assign('user',$user);
		$this->assign('startOpenTime',CONFIG('startOpenTime'));//开放起始时间
		$this->assign('endOpenTime',CONFIG('endOpenTime'));	 //开放结束时间
		$this->assign('shop',X('user')->shopWhere != '');
	    
		$this->display();
	}
    //系统设置更新
    public function sysupdate(){
		$data=array();
		M()->startTrans();
		//$settlement=strtotime($_POST['tle']);
		//系统维护提示内容  系统状态//
		$SYSTEM_CLOSE_TITLE  = I("POST.SYSTEM_CLOSE_TITLE/s");
		$SYSTEM_STATE        = I("POST.SYSTEM_STATE/d");
		
		CONFIG('SYSTEM_CLOSE_TITLE',$SYSTEM_CLOSE_TITLE);
	    CONFIG('SYSTEM_STATE',$SYSTEM_STATE);
		//系统开启时间和关闭时间//
		CONFIG('startOpenTime',$_POST['startOpenTime']);
		CONFIG('endOpenTime',$_POST['endOpenTime']);
		
		//前台模版标题显示//
		$SYSTEM_TITLE        = I("POST.SYSTEM_TITLE/s");
		$SYSTEM_COMPANY      = I("POST.SYSTEM_COMPANY/s");
		$SYSTEM_MEMO         = I("POST.SYSTEM_MEMO/s");
		
		CONFIG('SYSTEM_TITLE',$SYSTEM_TITLE);
		CONFIG('SYSTEM_COMPANY',$SYSTEM_COMPANY);
		CONFIG('SYSTEM_MEMO',$SYSTEM_MEMO);
		
		//前台登陆验证码//
		$USER_LOGIN_VERIFY   = I("POST.USER_LOGIN_VERIFY/d");
		
		CONFIG('USER_LOGIN_VERIFY',$USER_LOGIN_VERIFY);
		
		$USER_LOGIN_VERIFY_TYPE   = I("POST.USER_LOGIN_VERIFY_TYPE/d");	
					
		CONFIG('USER_LOGIN_VERIFY_TYPE',$USER_LOGIN_VERIFY_TYPE);
	
		//默认密码设置//
		$DEFAULT_USER_PASS1  = I("POST.DEFAULT_USER_PASS1/s");
		$DEFAULT_USER_PASS2  = I("POST.DEFAULT_USER_PASS2/s");
		$DEFAULT_USER_PASS3  = I("POST.DEFAULT_USER_PASS3/s");
		
	    CONFIG('DEFAULT_USER_PASS1',$DEFAULT_USER_PASS1);
		CONFIG('DEFAULT_USER_PASS2',$DEFAULT_USER_PASS2);
		CONFIG('DEFAULT_USER_PASS3',$DEFAULT_USER_PASS3);
		
		//客服qq的设置//
		CONFIG('TYPE_QQ',I("post.TYPE_QQ/d"));
		//字段名//
		$SERVICE_QQ='SERVICE_QQ_'.I("post.TYPE_QQ/d");
		$SERVICE_NO='SERVICE_QQ_'.abs(1-I("post.TYPE_QQ/d"));
		CONFIG($SERVICE_QQ,I("post.".$SERVICE_QQ."/s"));
		//清除为选择的设置//
		CONFIG($SERVICE_NO,'');
		
		//注册可见 注册必填 可修改项
		$infoarr=array();
		$showstr='';
		$editstr='';
		$requirestr='';
		$viewstr='';
		$truthstr='';
		foreach(I("post./a") as $k=>$v)
		{
			//显示
			if(strpos($k,'show_') !== false)
			{
				$showstr.=",".$v;
			}
			//可修改
			if(strpos($k,'edit_') !== false)
			{
				$editstr.=",".$v;
			}
			//可见
			if(strpos($k,'view_') !== false)
			{
				$viewstr.=",".$v;
			}
			//必填
			if(strpos($k,'require_') !== false)
			{
				$requirestr.=",".$v;
			}
			//真实性
			if(strpos($k,'truth_') !== false)
			{
				$truthstr.=",".$v;
			}
		}
		if($showstr !='')
		{
			$showstr=substr($showstr,1);
		}
		if($editstr !='')
		{
			$editstr=substr($editstr,1);
		}
		if($viewstr !='')
		{
			$viewstr=substr($viewstr,1);
		}
		if($requirestr !='')
		{
			$requirestr=substr($requirestr,1);
		}
		$user=X('user');
		CONFIG('USER_REG_SHOW' ,$showstr);
		CONFIG('USER_EDIT_SHOW',$editstr);
		CONFIG('USER_VIEW_SHOW',$viewstr);
		CONFIG('USER_REG_REQUIRED',$requirestr);
		CONFIG('USER_TRUTH',trim($truthstr,","));
		//唯一设置
		$user->setatt('onlyMobile',I("post.only_mobile/d"));
		$user->setatt('onlyIdCard',I("post.only_id_card/d"));
		$user->setatt('onlyBankCard',I("post.only_bank_card/d"));
		//编号的生成规则
		$user->setatt('idEdit',(I("post.idEdit/d")==1));
		$user->setatt('idRand',(I("post.idRand/d")==1));
		$user->setatt('idInDate',(I("post.idInDate/d")==1));
		$user->setatt('idAutoEdit',(I("post.idAutoEdit/d")==1));
		//注册编号生成设置
		$user->setatt('idSerial',I("post.idEdit/s"));
		$user->setatt('idPrefix',I("post.idPrefix/s"));
		$user->setatt('idLength',I("post.idLength/d"));
		$this->saveAdminLog('',I("post."),"系统设置","系统参数设置");
		M()->commit();
        $this->success('修改完成',__URL__.'/sysedit');
	}
	//前台菜单设置
	public function userMenuEdit(){
		$menu=R("User/Menu/getmenudata",array($this->userobj));
		$this->assign('menu',$menu);
		$userMenuPower = $this->userobj->getatt('userMenuPower');
		$userNoSecPwd = $this->userobj->getatt('userNoSecPwd');
		$userNoSecPwd3 = $this->userobj->getatt('userNoSecPwd3');
		$this->assign('NoSecnum',count($userNoSecPwd));
		$userShortcutMenu = $this->userobj->getatt('userShortcutMenu');
		$this->assign('userMenuPower',$userMenuPower);
		$this->assign('userNoSecPwd',$userNoSecPwd);
		$this->assign('userNoSecPwd3',$userNoSecPwd3);
		$this->assign('userShortcutMenu',$userShortcutMenu);
		$this->assign('USER_PRIZE_SWITCH',CONFIG('USER_PRIZE_SWITCH'));
		$this->assign('USER_SHOP_SALEONLY',CONFIG('USER_SHOP_SALEONLY'));
		//二级密码超时时间
		$this->assign('USER_PASS_TIMEOUT',CONFIG('USER_PASS_TIMEOUT'));
		$this->assign('pwd3Switch',adminshow('pwd3Switch'));//判断是否开启了三级密码
		$this->assign('shop',X('user')->shopWhere != '');
		$this->assign('SHOW_SHOPSET',CONFIG('SHOW_SHOPSET'));
		$this->display();
	}
	//前台菜单设置更新
	public function userMenuUpdate(){
		M()->startTrans();
		//快捷菜单  部分模板可用
		$this->userobj->setatt('userShortcutMenu',I("post.shortcut/a"));
		//会员前台菜单权限 二级密码验证
		$this->userobj->setatt('userMenuPower',I("post.level/a"));
		//
		$menu=R("User/Menu/getmenudata",array($this->userobj));
		$menuArr = array();
		$menuNode = array();
		foreach($menu as $mk=>$mv){
			foreach($mv as $v){
				$menuArr[]  = $v['model'].'-'.$v['action'].(isset($v['xpath'])?'-'.$v['xpath']:'');
				$menuNode[] = $v['model'].'-'.$v['action'];
			}
		}
		//设置是否验证二级密码
		$userNoSecPwd = array_diff($menuArr,I("post.secPwd/a"));
		$userNoSecPwd3 = array_diff($menuArr,I("post.secPwd3/a"));
		$this->userobj->setatt('userNoSecPwd',$userNoSecPwd);
		$this->userobj->setatt('userNoSecPwd3',$userNoSecPwd3);
		
		$this->userobj->setatt('userMenu',$menuNode);
		
		CONFIG('USER_PRIZE_SWITCH',I("post.USER_PRIZE_SWITCH/d"));
		CONFIG('USER_PASS_TIMEOUT',I("post.USER_PASS_TIMEOUT/d"));
		CONFIG('USER_SHOP_SALEONLY',I("post.USER_SHOP_SALEONLY/d"));
		M()->commit();
		$this->saveAdminLog('','',"前台菜单设置");
        $this->success('修改完成',__URL__.'/sysedit');
	}
	//奖金参数设置更新
	public function tleupdate(){
		$cons = $this->getcons();
		M()->startTrans();
		foreach($cons as $con)
		{
			if(I("post.".$con['name'],"null")!=null)
			{
				if($con['type']=='date')
				{
					CONFIG($con['name'],strtotime(I("post.".$con['name']."/s")));
				}
				else
				{
					//判断提交的数据为数字 非数字则会变成0
					if($con['isnum']===true){
						CONFIG($con['name'],I("post.".$con['name']."/f"));
					}else{
						CONFIG($con['name'],I("post.".$con['name']."/s"));
					}
				}
			}
			else
			{
				CONFIG($con['name'],$con['offvalue']);
			}
		}
		M()->commit();
		$this->saveAdminLog('','',"奖金参数设置");
		$this->success('奖金参数设置完成！');
	}

	//自动设置
	function autoSet($obj,$option=array()){
		M()->startTrans();
		foreach($obj as $k=>$v)
		{
			$newval=I("post.".$k."/s");
			if($newval !== NULL)
			{
				if(gettype($v)=='string' || ((gettype($v)=='integer' || gettype($v)=='double') && is_numeric($newval)))
			   	{
			   		
			   		settype($newval,gettype($v));
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
			}
		}
		M()->commit();
	}
	//登录口设置
	public function LoginTempSetup(){
		$template	= array();
		$path= ROOT_PATH.'DmsAdmin/Tpl/User/login/';
		if(!is_dir($path)) return;
		
		$handle		= opendir($path);
		$nowNum = CONFIG('DEFAULT_LOGIN_THEME')?CONFIG('DEFAULT_LOGIN_THEME'):"2";
		while(false!==($file = readdir($handle)))
		{
			$fileTime = date('Y-m-d H:i:s', filemtime($path . $file));
			if(is_dir($path . $file) && $file !="."&& $file !=".." && $file != '.svn'){
				$num	= $file;
                if($nowNum == $num){
                    $template['status'] = "1";
                }else{
                    $template['status'] = "0";
                }
                if($num == ''){
                	$multiLang = '不支持';
                }else{
                	$multiLang = '支持';
                }
				$path1	= "UserTpl/login/".$num.'/preview.jpg';
				$template['path']	= $path1;
				$template['number'] = $num;
				$template['multiLang'] = $multiLang;
				$template['fileTime'] = $fileTime;
				$template['description'] = '[暂无]';
				//$template['catalog'] = $path.$num."/";
				$template['catalog'] = $num;
				$info[]	= $template;
			}
		}
		$this->assign('USER_LOGIN_URL',CONFIG('USER_LOGIN_URL'));
		
		$info = $this->tsort($info);
		$this->assign('info',$info);
		$this->display();
	}
	//登录口预览
	public function viewLoginTemp(){
		if(isset($_SESSION[C('USER_AUTH_KEY')])) {
            unset($_SESSION[C('USER_AUTH_KEY')]);
			unset($_SESSION[C('USER_AUTH_NUM')]);
            unset($_SESSION[C('PWD_SAFE')]);
			unset($_SESSION[C('SAFE_PWD')]);
			unset($_SESSION['logintype']);
			unset($_SESSION['username']);
			unset($_SESSION['ip']);
        }
		$this->redirect('User/Public/login',array('loginTempNumber'=>I("get.number/s")));
	}
	//模版主题设置
	public function ThemeTempSetup(){		
		$themePath = 'UserTpl/';
		$nowTheme = CONFIG('DEFAULT_THEME')?CONFIG('DEFAULT_THEME'):'default_sj';
		if(is_dir($themePath)){
			$themeName = array();
			$handle1		= opendir($themePath);
			while(false!==($filename = readdir($handle1))){
				if(is_dir($themePath.$filename) && $filename!='.' && $filename!='..' && $filename!='login' && $filename != '.svn' && $filename != 'core'){
					$themeTime = date('Y-m-d H:i:s', filemtime($themePath . $filename));
					if($nowTheme==$filename){
						$themeName['status'] = "1";
					}else{
						$themeName['status'] = "0";
					}
					$themeName['name'] = $filename;
					$themeName['path']	= $themePath;
					$themeName['themeTime'] = $themeTime;
					$themeName['description'] = '[暂无]';
					//$themeName['catalog'] = ROOT_PATH.'DmsAdmin/Tpl/User/'.$filename."/";
					$themeName['catalog'] = $filename;
					$themeInfo[]	= $themeName;
				}
			}
		}
		$this->assign('theme',$themeInfo);
        $this->assign('nowTheme',$nowTheme);
		$this->display();
	}
	
	// 排序
	private function tsort($ary){
        for($i=0; $i<count($ary) ;$i++){
            for($j=0; $j<$i; $j++){
                if($ary[$i]['number'] < $ary[$j]['number']){
                    $temp = $ary[$i];
                    $ary[$i] = $ary[$j];
                    $ary[$j] = $temp;
                }
	        }
        }
        return $ary;
    }
	//更换登陆入口
	public function tempChange()
	{
		if(I("request.number/s")!='')
		{
			$number	= I("request.number/s");
			M()->startTrans();
			CONFIG('DEFAULT_LOGIN_THEME',$number);
			M()->commit();
			$this->ajaxReturn('0','设置成功！',1);
		}
		else
		{
			$this->ajaxReturn('0','设置失败！',0);
		}
	}
	//设置登录口地址
	public function loginUrl(){
		if(I("post.USER_LOGIN_URL/s"))
		{
			$this->error('请输入指定登录的地址!');
		}else{
			//判断是否输入正确 正则
			$result=preg_match("/^(https|http):\/\//",I("post.USER_LOGIN_URL/s"));
			if($result==0){
				$this->error("请填写正确地址");
			}
			M()->startTrans();
			CONFIG('USER_LOGIN_URL',I("post.USER_LOGIN_URL/s"));
			M()->commit();
			$this->success('修改成功!');
		}
	}
	// 更换主题
	public function themeChange(){
		if(I("request.themename/s")!='')
		{
			$themename	= I("request.themename/s");
			M()->startTrans();
			CONFIG('DEFAULT_THEME',$themename);
			M()->commit();
			$this->ajaxReturn('1','设置成功！',1);
		}
		else
		{
			$this->ajaxReturn('1','设置失败！',0);
		}
	}

	public function prizeEdit(){
		$userdata=array();
		$prizedata=array();
		foreach(X('tle') as $tleobj)
		{
			foreach(X('prize_*',$tleobj) as $p)
			{
				if(get_class($p)!='prize_sql')
				{
					$prizedata[]=array('name'=>$p->byname,'class'=>get_class($p),'xpathmd5'=>md5($p->objPath()),'use'=>$p->use,'startDate'=>$p->startDate,'endDate'=>$p->endDate);
				}
			}
		}
		if(count($prizedata)>0)
		$userdata[$this->userobj->name]=$prizedata;
		
		$this->assign('data',$userdata);
		$this->display();
	}
	public function prizeEditSave(){
		M()->startTrans();
		foreach(X('tle') as $tleobj)
		{
			foreach(X('prize_*',$tleobj) as $p)
			{
				/*
					没有开放prize_layer的原因是
					本来能产生层碰的会员因关闭后没执行.层碰数据没生成
					后期开放本奖金.就会导致补发.
					但是一般客户的意思为.关闭期间碰过的..视为碰过
				*/
				if(get_class($p)!='prize_sql' && get_class($p)!='prize_layer'){
					$pmd5=md5($p->objPath());
					if(I("post.".$pmd5.'_use/s')!="")
					{
						$p->setatt('use',I("post.".$pmd5.'_use/s')=='true');
						if(I("post.".$pmd5.'_use/s')=='true')
							$edit_data[$p->byname.'开启']="开启";
						else
							$edit_data[$p->byname.'开启']="关闭";
						//设置开始日期
						if(I("post.".$pmd5.'_start/s')=='')
						{
							$p->setatt('startDate',0);
							$edit_data[$p->byname.'开始']=0;
						}
						else
						{
							$p->setatt('startDate',strtotime(I("post.".$pmd5.'_start/s')));
							$edit_data[$p->byname.'开始']=strtotime(I("post.".$pmd5.'_start/s'));
						}
						//设置结束日期
						if(I("post.".$pmd5.'_end/s')=='')
						{
							$p->setatt('endDate',0);
							$edit_data[$p->byname.'结束']=0;
						}
						else
						{
							$p->setatt('endDate',strtotime(I("post.".$pmd5.'_end/s')));
							$edit_data[$p->byname.'结束']=strtotime(I("post.".$pmd5.'_end/s'));
						}
					}
				}
			}
		}
		M()->commit();
		$this->saveAdminLog('',$edit_data,"奖金开关设置");
		$this->success('设置完成');
	}
	function autoList(){
		ini_set("display_errors","On");
		$children=glob(VENDOR_PATH.'Workerman/Applications/*/start.php');
		$autoary=array();$donot=false;
		foreach($children as $child){
			$filename=basename(dirname($child));
			//判断是否有开启
			if(file_exists(VENDOR_PATH.'Workerman/Applications/'.$filename.'/taskset.php')){
				$autoset=require_once(VENDOR_PATH.'Workerman/Applications/'.$filename.'/taskset.php');
				if($autoset['use'] && adminshow('AUTO_'.$filename)){
					$autoary[$filename]=array();
					//查看运行状态
					exec('php '.VENDOR_PATH.'Workerman/start.php runstatic '.basename(dirname($child)),$ret);
					if(!isset($ret[1])){
						$donot=true;
						$autoary[$filename]['error']=$ret[0];
					}else{
						$autoary[$filename]=$ret;
					}
					unset($ret);
					$autoary[$filename]['autoname']=$autoset['showName'];
				}
			}
		}
		$this->assign('autoary',$autoary);
		$this->assign('donot',$donot);
		$this->display();
	}
	function autosetsave($calmsg=NULL){
		ini_set("display_errors","On");
		if($calmsg ===null || $calmsg==true){
    		define('autoset',true);
		}
		$autotype=I("post.autotype/s");
		$type=I("post.type/s");
		$autoary=array();
		if($autotype=='all'){
			//获取所有的自动节点操作
			$children=glob(VENDOR_PATH.'Workerman/Applications/*/start.php');
			foreach($children as $child){
				$filename=basename(dirname($child));
				if(adminshow("AUTO_".$filename)){
					$autoary[]=$filename;
				}
			}
		}else{
			$autoary[]=$autotype;
		}
		$result=array();$typeary=array("start"=>"启动服务","restart"=>"重启服务","stop"=>"终止服务");
		//循环操作
		foreach($autoary as $autoid){
			//重启 先关闭再启动
			exec('php '.VENDOR_PATH.'Workerman/start.php '.$type.' '.$autoid,$ret);
			$autoset=require_once(VENDOR_PATH.'Workerman/Applications/'.$autoid.'/taskset.php');
			//分析
			foreach($ret as $msg){
				//替换[]内的内容
				$msg=str_replace('[1A','',$msg);
				$msg=str_replace('[K','',$msg);
				$msg=str_replace('[47;30m','',$msg);
				$msg=str_replace('[0m','',$msg);
				$msg=str_replace('[32;40m','',$msg);
				$msg=preg_replace('/\[.*\]/','',$msg);
				if(trim($msg)==""){
					continue;
				}
				if(isset($typeary[$msg])){
					calmsg($typeary[$msg]." ".$autoset['showName'],"/Public/Images/ExtJSicons/resultset_next.png");
				}else{
					calmsg($msg,"/Public/Images/ExtJSicons/lightning.png");
				}
			}
			unset($ret);
		}
		calmsg('操作完成！',"/Public/Images/ExtJSicons/tick.png");
		$this->ajaxReturn(array(date('Y-m-d',systemTime())));
	}
	function autostatus(){
		$type=I("post.type/s");
		//获取所有的自动节点操作
		$autoary=array();
		$children=glob(VENDOR_PATH.'Workerman/Applications/*/start.php');
		foreach($children as $child){
			$filename=basename(dirname($child));
			if(adminshow("AUTO_".$filename)){
				$autoary[]=$filename;
			}
		}
		$result=array();
		foreach($autoary as $autoid){
			//重启 先关闭再启动
			exec('php '.VENDOR_PATH.'Workerman/start.php '.$type.' '.$autoid,$ret);
			$result[$autoid]=$ret[1];
		}
		$this->ajaxReturn($result);
	}
	//系统使用书名书下载
	function system_do_info(){
	   $this->display();
	}
	function doaddfile(){
        $filename = '管理系统使用说明书.pdf';
        $pathfile = $_SERVER['DOCUMENT_ROOT'].'/Public/shiyongshuoming.pdf';
		
        $file = fopen($pathfile, "r"); // 打开文件
        // 输入文件标签
		if(Extension_Loaded('zlib')){Ob_Start('ob_gzhandler');}
        header('Content-Encoding: none');
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: " . filesize($pathfile));
        header('Content-Transfer-Encoding: binary');
        header("Content-Disposition: attachment; filename=" . $filename);  //以真实文件名提供给浏览器下载
        header('Pragma: no-cache');
        header('Expires: 0');
        //输出文件内容
        echo fread($file,filesize($pathfile));
        fclose($file);
		if(Extension_Loaded('zlib')) Ob_End_Flush(); 
	}
}
?>