<?php
class LangAction extends Action{
	function index(){
		$langs = C('LANG.SET');
		$langBao = $this -> getLang();
		$langset = $this -> getLangCode();
		foreach($langs as $k => $v){
			if(!in_array($k,$langBao)){
				$this->error('Admin/conf/lang.php中SET配置的 '.$k.' 语言未在语言包目录中,请重新配置或者添加 '.$k.' 语言包');
				return;
			}
			if(!array_key_exists($k,$langset)){
				$this->error($k.' 语言未在ThinkPHP/conf/langset.php配置文件中,请配置');
				return;
			}
		}
		/*$setButton=array(
			'删除'=>array("class"=>"delete", "href"=>"__URL__/del/id/{tl_id}", "target"=>"ajaxTodo" ,"title"=>"确定要删除吗?"),
			'添加'=>array("class"=>"add",    "href"=>"__URL__/add",            "target"=>"dialog", "mask"=>"true","width"=>"600","height"=>"400" ),
			'修改'=>array("class"=>"edit",   "href"=>"__URL__/edit/id/{tl_id}","target"=>"dialog", "mask"=>"true","width"=>"700","height"=>"450" )
        ); */ 
        $setButton=array(
        	'多语言设置'=>array("class"=>"edit", "href"=>"__URL__/multiLangSet", "target"=>"dialog", "mask"=>"true","width"=>"500","height"=>"300" ),
			'语种管理'=>array("class"=>"edit", "href"=>"__URL__/langCtgMng", "target"=>"dialog", "mask"=>"true","width"=>"500","height"=>"300" ),
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
        	$list->addshow($langset[$k]['name'], array("row"=>"[$k]", "searchMode"=>"text", "excelMode"=>"text",)); 
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

	//点击标签名称
	function edit(){
		$id = I("get.id/d");
		if(strpos($id,',') !== false){
			$this->error('参数错误!');
			return;
		}		
		$list = M("langdata") -> find($id);
		$langs = C('LANG.SET');
		$langset = $this -> getLangCode();
		$langss = array();
		foreach( $langs as $key => $val){
			if(array_key_exists($key,$langset)){
				$langss[$key] = $langset[$key]['name'];
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
    	foreach($langs as $key => $val){
    		$rela_path = LANG_PATH . $key .'/'. $data['file'];
    		if(!file_exists($rela_path)){
    			if(strpos($data['file'],'/') !== false){
    				list($group,$module) = explode('/',$data['file']);
    				$group_path = LANG_PATH . $key .'/'.$group;
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
    		$lang = include $real_path;
    		$i=0;
    		if(!array_key_exists($data['name'],$lang)){
				$lang[$data['name']] = $data[$key];
				$i++;
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
		$langset = require  realpath(THINK_PATH.'/conf/langset.php');
		return $langset;
	}

	//同义词
	function synonym(){
		$lang = I('get.lang/s');	
		$langset = $this -> getLangCode();
		$transTo = $langset[$lang]['trans'];
		$arr = array('transTo' => $transTo);
		$this->ajaxReturn($arr,'','','json');
	}
	//翻译
	function translate(){
		if(!C('LANG.USE')) $this->ajaxReturn('','多语言未开启，翻译不可用',0);		
		$seman = I("get.seman/s");
		$langs = C('LANG.SET');
		$langset = $this -> getLangCode();
		$result = array();
		foreach($langs as $k => $v){
			$result[$k] = language($seman,'zh',$langset[$k]['trans']);
		}
		$this->ajaxReturn($result,'','','json');
	}
	//打开多语言设置
	function multiLangSet(){
		$use = C('LANG.USE');
		$api = C('BDFY_API');
		$this -> assign('use',$use);
		$this -> assign('api',$api);
		$this -> display();
	}
	//提交多语言设置
	function multiLangOpen(){
		$multiLang = I('post.multiLang');
		$appid = I('post.appid');
		$key = I('post.key');
		//开启、关闭多语言
		$path = ROOT_PATH . 'Admin/conf/lang.php';
		if(!file_exists($path)){
			file_put_contents($path , "<?php \n return " . var_export(array('LANG'=>array()), true) . ";\n?>");
		}
		$realpath = realpath($path);
		$conf = include $realpath;
		if($conf['LANG']['USE'] == false){
			if(!$multiLang){
				$this -> ajaxReturn('','多语言已关闭',1);
			}
			if(!$appid) $this -> ajaxReturn('','请输入appid',0);
			if(!$key) $this -> ajaxReturn('','请输入key',0);
			$conf['LANG']['USE'] = true;
			if($appid !== $conf['BDFY_API']['APPID'])
				$conf['BDFY_API']['APPID'] = $appid;
			if($key !== $conf['BDFY_API']['KEY'])
				$conf['BDFY_API']['KEY'] = $key;
			$res = file_put_contents($realpath , "<?php" ."\n". "return " . var_export($conf, true) . ";\n?>");
			if($res)
			$this -> ajaxReturn('','多语言已开启',1);
		}else{
			if(!$multiLang){
				$conf['LANG']['USE'] = false;
				$res = file_put_contents($realpath , "<?php" ."\n". "return " . var_export($conf, true) . ";\n?>");
				if($res)
				$this -> ajaxReturn('','多语言已关闭',1);
			}
			if(!$appid) $this -> ajaxReturn('','请输入appid',0);
			if(!$key) $this -> ajaxReturn('','请输入key',0);
			$this -> ajaxReturn('','多语言已开启',1);
		}
	}
	//打开语种管理
	function langCtgMng(){
		$this -> display();
	}
	//添加语言包选项
	function getMUI(){
		$langset = $this -> getLangCode();
		$langs = $this -> getLang();
		foreach($langs as $v){
			unset($langset[$v]);
		}
		$this->ajaxReturn($langset,'','','json');
	}
	
	//创建语言包
	function buildMUI(){
		if(!C('LANG.USE')) $this->ajaxReturn('','多语言未开启，添加语言包不可用',0);
		$mui = I('post.mui');
		if(!$mui){
			$this ->ajaxReturn('','未选择语言包',0);
		}		
		//判断语言是否存在
		$langset = $this -> getLangCode();
		if(!isset($langset[$mui]))
		{
			$this->error('语言参数错误');
		}
		if(!file_exists(LANG_PATH . $mui)){
			$this->recurse_copy(LANG_PATH.'zh-cn',LANG_PATH.$mui);
			//如果存在自动翻译功能
			if(isset($langset[$mui]['trans']))
			{
				$path = realpath(LANG_PATH.$mui); 
				$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST); 
				foreach ($objects as $name => $object) { 
					if($objects->isFile() && $objects->getExtension()=='php')
					{
						$conf = require realpath($name);
						$fydata = language(array_values($conf),'zh',$langset[$mui]['trans']);
						foreach($conf as $key=>$val)
						{
							if(isset($fydata[$val]))
							{
								$conf[$key]=$fydata[$val];
							}
						}
						$res = file_put_contents($name , "<?php" ."\n". "return " . var_export($conf, true) . ";\n?>");
					}
				}
			}
			//添加系统支持语言
			$confpath = realpath(ROOT_PATH . 'Admin/conf/lang.php');
			$langConf = include $confpath;
			if(!isset($langConf['LANG']['SET'][$mui])){
				$langConf['LANG']['SET'][$mui] =  $langset[$mui]['dispname'];
				file_put_contents($confpath,"<?php" ."\n". "return " . var_export($langConf, true) . ";\n?>");
			}
			//ajax返回
			if($res) $this->ajaxReturn('','语言包已创建',1);
		}else{
			$this->ajaxReturn('','语言包已存在',1);
		}
	}
	
	//复制目录
	function recurse_copy($src,$dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->recurse_copy($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
	}

}
?>