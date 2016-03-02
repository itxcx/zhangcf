<?php
class AuthCheckAction extends  Action{
	function index1(){
		//加载MenuAction.class.php
		require_once __dir__.'/MenuAction.class.php';		
		$menuOjb = new MenuAction();
		$menu = $menuOjb ->getMenu();
		//获取MenuAction中模块名和方法名
		$module_name2 = array();
		$action_name2 = array();
		foreach( $menu as $val){
			$module = $val['model'].'Action';
			if( strpos($val['action'],',') !== false && strpos($val['action'],':') === false){
				$arr = explode(',',$val['action']);
				foreach($arr as $v){
					$action_name2[$module][]=$v;
				}
			}elseif( strpos($val['action'],',') === false && strpos($val['action'],':') !== false){
				list($a,$b) = explode(':',$val['action']);
				$action_name2[$module][]=$a;
			}elseif( strpos($val['action'],',') !== false && strpos($val['action'],':') !== false){
				$arr = explode(',',$val['action']);
				foreach($arr as $v){
					list($a,$b) = explode(':',$v);
					$action_name2[$module][]=$a;
				}
			}else{
				$action_name2[$module][]=$val['action'];
			}
		}
		
		//获取/DmsAdmin/Lib/Action/Admin/下所有Action类
		$items= $this->read(__dir__);
		foreach( $items as $item){
			//加载Action类
			require_once __dir__.'/'.$item;
			$class_name = substr($item,0,strpos($item,'.'));
			//反射法,获取类中public方法
			$r = new ReflectionClass($class_name);
			foreach( $r->getMethods() as $key =>$methodObj){
				 if($methodObj->isPublic()){
				 	$module = $methodObj->class;
				 	$methods2[$module][] = $methodObj->name;
				 }
			}
		}
		//删除继承的Action
		unset($methods2['Action']);
		//判断$methods1中模块和方法存不存在$action_name1中
		unset($methods2['CommonAction']);
		foreach($methods2 as $module => $method){
			foreach($method as $m){
				if(!in_array($m,$action_name2[$module]) && substr($m,0,1)!='_'){
					echo '/DmsAdmin/Lib/Action/Admin/'.$module.'.class.php:';
					echo $m.'</br>';
				}
			}	
		}
	}
	function index2(){
		//加载node.php
		$arr=require_once str_replace('public_html','Admin/conf/node.php',$_SERVER['DOCUMENT_ROOT']);

		//获取node.php中module和action
		$module_name1 = array();
		$action_name1 = array();
		foreach( $arr as $key => $value ){
			$module = $value['module'].'Action';
			foreach($value['childs'] as $val){
				if(strpos($val['action'],',') !== false ){
					$arr = explode(',',$val['action']);
					foreach($arr as $v){
						$action_name1[$module][]=$v;
					}
				}else{
					$action_name1[$module][]=$val['action'];
				}		
			}
		}
		//获取/DmsAdmin/Lib/Action/Admin/下所有Action类
		$directory = str_replace('public_html','Admin/Lib/Action',$_SERVER['DOCUMENT_ROOT']);
		$rsts= $this->read($directory);
		foreach( $rsts as $item){
			if($item!='CommonAction.class.php')
			{
				//加载Action类
				require_once $directory.'/'.$item;
				$class_name = substr($item,0,strpos($item,'.'));
				//反射法,获取类中public方法
				$r = new ReflectionClass($class_name);
				foreach( $r->getMethods() as $key =>$methodObj){
					 if($methodObj->isPublic()){
					 	$module = $methodObj->class;
					 	$methods1[$module][] = $methodObj->name;
					 }
				}
			}
		}
		//删除继承的Action
		unset($methods1['Action']);
		unset($methods1['CommonAction']);
		
		//判断$methods1中模块和方法存不存在$action_name1中
		foreach($methods1 as $module => $method){
			foreach($method as $m){
				if(!in_array($m,$action_name1[$module]) && substr($m,0,1)!='_'){
					echo '/Admin/Lib/Action/'.$module.'.class.php:';
					echo $m.'</br>';
				}
			}
		}
	}
	//读取目录下的文件
	function read($path) {
		if (! file_exists ( $path )) {
			return false;
		}
		$handle = opendir ( $path );
		if($handle){
			while ( ($item = readdir ( $handle )) !== FALSE ) {
				if ($item != '.' && $item != '..') {
					if (is_file ( $path . '/' . $item ) ) {
						if( $item != 'PaymentAction.class.php' && $item !='tbszip.php' ){
							$items[] = $item;
						}
					} else {
						$func = __FUNCTION__;
						$func ( $path . '/' . $item );
					}
				}
			}
		}	
		closedir ( $handle );
		return $items;
	}
}
?>