<?php
class LangAction extends Action{
	function index(){
		$langs = C('LANG.SET');
		//$langs = $this -> getLang();
		$langset = $this -> getLangCode();
		//print_r ($langset);exit;
		foreach($langs as $k => $v){
			if(!array_key_exists($v,$langset)){
				$this->error($v.'语言目录未在ThinkPHP配置文件中,请配置');
				return;
			}
		}
		/*$setButton=array(
			'删除'=>array("class"=>"delete", "href"=>"__URL__/del/id/{tl_id}", "target"=>"ajaxTodo" ,"title"=>"确定要删除吗?"),
			'添加'=>array("class"=>"add",    "href"=>"__URL__/add",            "target"=>"dialog", "mask"=>"true","width"=>"600","height"=>"400" ),
			'修改'=>array("class"=>"edit",   "href"=>"__URL__/edit/id/{tl_id}","target"=>"dialog", "mask"=>"true","width"=>"700","height"=>"450" )
        ); */ 
        $setButton=array(
        	'多语言设置'=>array("class"=>"edit", "href"=>"__URL__/multiLangSet", "target"=>"dialog", "mask"=>"true","width"=>"600","height"=>"400" ),
			'语种管理'=>array("class"=>"edit", "href"=>"__URL__/langCtgMng", "target"=>"dialog", "mask"=>"true","width"=>"600","height"=>"400" ),
        ); 
        import('Admin.Action.TableListAction');
        $list=new TableListAction("langdata"); // 实例化Model 传表名称 
        $list->setButton = $setButton;       // 定义按钮显示
		$list->order("lid asc");  //定义查询条件
        $list->addshow("编号",        array("row"=>"[lid]",      "searchMode"=>"text", "excelMode"=>"text",)); 
        $list->addshow("标签名称",array("row"=>array(array(&$this,'_disName'),"[lid]","[name]"),"searchMode"=>"text", "excelMode"=>"text",'searchPosition'=>'top',));
        $list->addshow("调用位置",array("row"=>"[loadfile]","searchMode"=>"text", ));
        $list->addshow("所在位置",array("row"=>"[file]",    "searchMode"=>"text", "excelMode"=>"text",));
        foreach($langs as $k => $v){
        	$list->addshow($langset[$v]['name'], array("row"=>"[$v]", "searchMode"=>"text", "excelMode"=>"text",)); 
        }
		$this->assign('list',$list->getHtml());  
		$this->display();
	}
	
	function getLang(){
		$langDir = realpath(APP_PATH.'/lang');
		if(!file_exists($langDir)){
			$this->error('语言包目录不存在');
			return;
		}
		$items = array();
		$handle = opendir($langDir);
		if($handle){
			while (($item = readdir($handle )) !== FALSE){
				if ($item !='.' && $item !='..') {
					if (is_dir($langDir.'/'.$item)) {
							$items[] = $item;
					}
				}
			}
		}	
		closedir ($handle);
		return $items;
	}
	
	
	function _disName($id,$name){
		$nameLink = '<a target="dialog" mask="true" title="语言标签处理" href="'.__URL__.'/edit/id/'.$id.'" width="700" height="510">'.$name.'</a>';
		return $nameLink;
	}
	function _disFile($file){
		if($file === 0){
			return __ROOT__.'/'.APP_NAME.'/lang/en-us';
		}elseif($file == 1){
			return __ROOT__.'/'.APP_NAME;
		}
	}
	//点击标签名称
	function edit(){
		$id = I("get.id/d");
		if(strpos($id,',') !== false){
			$this->error('参数错误!');
			return;
		}		
		$list = M("langdata") -> find($id);
		$langs = C('LANG.SET');
		//$langs = $this -> getLang();
		$langset = $this -> getLangCode();
		$langss = array();
		foreach( $langs as $key => $val){
			if(array_key_exists($val,$langset)){
				$langss[$val] = $langset[$val]['name'];
			}
		}
		$this->assign('list',$list);
		$this->assign('langss',$langss);
		$this->display();
	}
	
	//保存
    public function editSave(){
    	$data = I("post.");
    	if(empty($data['file'])){
    		$this->ajaxReturn('','作用域不可为空',0);
    		return;
    	}
    	//获取语言包
    	$langs = C('LANG.SET');
    	//$langs  = $this -> getLang();
    	foreach($langs as $val){
    		$rela_path = LANG_PATH . $val .'/'. $data['file'];
    		if(!file_exists($rela_path)){
    			if(strpos($data['file'],'/') !== false){
    				list($group,$module) = explode('/',$data['file']);
    				$group_path = LANG_PATH . $val .'/'.$group;
    				if(!file_exists($group_path)){
    					$res = mkdir($group_path);
    					if($res){
    						file_put_contents($rela_path , "<?php \n return " . var_export(array(), true) . ";\n?>");
    					}
    				}else{
    					file_put_contents($rela_path , "<?php \n return " . var_export(array(), true) . ";\n?>");
    				}
    			}else{
    				file_put_contents($rela_path , "<?php \n return " . var_export(array(), true) . ";\n?>");
    			}
    		}
    		$real_path = realpath($rela_path);
    		$lang = require $real_path;
    		$i=0;
    		if($val == 'zh-cn'){
    			if(!array_key_exists($data['en-us'],$lang)){
    				$lang[$data['en-us']] = $data[$val];
    				$i++;
    			}else{
    				if(!$lang[$data['en-us']]){
	    				$lang[$data['en-us']] = $data[$val];
	    				$i++;
	    			}	
    			}
    		}else{
    			if(!array_key_exists($data['zh-cn'],$lang)){
    				$lang[$data['zh-cn']] = $data[$val];
	    			$i++;
    			}else{
	    			if(!$lang[$data['zh-cn']]){
	    				$lang[$data['zh-cn']] = $data[$val];
	    				$i++;
	    			}
    			}
    		}
    		if($i !=0){
    			file_put_contents($real_path , "<?php" ."\n". "return " . var_export($lang, true) . ";\n?>");
    		}
    	}
    	//存到数据表
    	$where = array("lid"=>I("get.id/s"),"name"=>$data['name']);
    	M()->startTrans();
    	$result = M('langdata') -> where($where) -> save($data);
		if($result !== false){
			M()->commit();
			$this->ajaxReturn(array('ajax'=>$data['ajax']),"保存成功");
		}else{
			M()->rollback();
			$this->error("保存失败");
		}
    }

	function getLangCode(){
		$langset = require_once  realpath(THINK_PATH.'/conf/langset.php');
		return $langset;
	}
	function getLangTrans(){
		$langTrans = require_once  realpath(THINK_PATH.'/conf/langTrans.php');
		return $langTrans;
	}
	function synonym(){
		$name = I('get.name/s');	
		$langTrans = $this->getLangTrans();
		if($name == '中文(繁体)' || $name == '中文(香港)' || $name == '中文(澳门)'){
			$transTo = $langTrans['繁体中文'];
		}else{
			if(strpos($name,'(') === false){
				$transTo = $langTrans[$name];
			}else{
				list($a,$b) = explode('(',$name);
				$transTo = $langTrans[$a];
			}
		}	
		$arr = array('transTo' => $transTo);
		$this->ajaxReturn($arr,'','','json');
	}
	
	function translate(){		
		$seman = I("get.seman/s");
		$langs = C('LANG.SET');
		//$langs = $this -> getLang();
		$langset = $this -> getLangCode();
		$langTrans = $this -> getLangTrans();
		$result = array();
		foreach($langs as $v){
			if($v != 'zh-cn'){
				if($langset[$v]['name'] == '中文(繁体)' || $langset[$v]['name'] == '中文(香港)' || $langset[$v]['name'] == '中文(澳门)'){
					$transTo = $langTrans['繁体中文'];
					$result[$v] = language($seman,'auto',$transTo);
				}else{
					if(strpos($langset[$v]['name'],'(') === false){
						$transTo = $langTrans[$langset[$v]['name']];
						$result[$v] = language($seman,'auto',$transTo);
						//$result[$v] = youdaoFanyi($seman);
					}else{
						list($a,$b) = explode('(',$langset[$v]['name']);
						$transTo = $langTrans[$a];
						$result[$v] = language($seman,'auto',$transTo);
					}
				}
			}
		}
		$this->ajaxReturn($result,'','','json');
	}
	function multiLangSet(){
		$use = C('LANG.USE');
		$this -> assign('use',$use);
		$this -> display();
	}
	function multiLangOpen(){
		$multiLang = I('post.multiLang');

		//开启、关闭多语言
		$path = ROOT_PATH . 'Admin/conf/lang.php';
		if(!file_exists($path)){
			file_put_contents($path , "<?php \n return " . var_export(array('LANG'=>array()), true) . ";\n?>");
		}
		$realpath = realpath($path);
		$conf = include $realpath;
		if($conf['LANG']['USE'] == false){
			if(!$multiLang){
				$this -> ajaxReturn('','多语言未选择',0);
			}
			$conf['LANG']['USE'] = true;
			$res = file_put_contents($realpath , "<?php" ."\n". "return " . var_export($conf, true) . ";\n?>");
			if($res !==false)
			$this -> ajaxReturn('','多语言已开启',1);
		}else{
			if(!$multiLang){
				$conf['LANG']['USE'] = false;
				$res = file_put_contents($realpath , "<?php" ."\n". "return " . var_export($conf, true) . ";\n?>");
				if($res !==false)
				$this -> ajaxReturn('','多语言已关闭',1);
			}
			$this -> ajaxReturn('','多语言已开启',1);
		}
	}
	
	function langCtgMng(){
		//$support = join(',',C('LANG.SET'));
		$langs = C('LANG.SET');
		$langset = $this -> getLangCode();
		$langName = array();
		foreach($langs as $lang){
			$langName[] = $langset[$lang]['name'];
		}
		$support = join(',',$langName);
		$default = C('LANG.DEFAULT');
		$default = $langset[$default]['name'];
		$this -> assign('support',$support);
		$this -> assign('default',$default);
		$this -> display();
	}
	
	function getMUI(){
		$langset = $this -> getLangCode();
		$this->ajaxReturn($langset,'','','json');
	}
	function buildMUI(){
		$mui = I('post.mui');
		if(!$mui){
			$this ->ajaxReturn('','未选择语言包',0);
		}
		list($a,$b) = explode('_',$mui);
		if(!file_exists(LANG_PATH . $a)){
			$res = mkdir(LANG_PATH . $a);
			if($res){
				$this->ajaxReturn('','创建'.$b.'语言包成功',1);
			}
		}else{
			$this->ajaxReturn('',$b.'语言包已存在',1);
		}
	}
}
?>