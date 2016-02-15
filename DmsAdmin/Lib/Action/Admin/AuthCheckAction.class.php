<?php
class AuthCheckAction extends  Action{
	function index1(){
		//����MenuAction.class.php
		require_once __dir__.'/MenuAction.class.php';		
		$menuOjb = new MenuAction();
		$menu = $menuOjb ->getMenu();
		//��ȡMenuAction��ģ�����ͷ�����
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
		
		//��ȡ/DmsAdmin/Lib/Action/Admin/������Action��
		$items= $this->read(__dir__);
		foreach( $items as $item){
			//����Action��
			require_once __dir__.'/'.$item;
			$class_name = substr($item,0,strpos($item,'.'));
			//���䷨,��ȡ����public����
			$r = new ReflectionClass($class_name);
			foreach( $r->getMethods() as $key =>$methodObj){
				 if($methodObj->isPublic()){
				 	$module = $methodObj->class;
				 	$methods2[$module][] = $methodObj->name;
				 }
			}
		}
		//ɾ���̳е�Action
		unset($methods2['Action']);
		//�ж�$methods1��ģ��ͷ����治����$action_name1��
		unset($methods2['CommonAction']);
		foreach($methods2 as $module => $method){
			foreach($method as $m){
				if(!in_array($m,$action_name2[$module]) && substr($m,0,1)!='_'){
					echo '/DmsAdmin/Lib/Action/Admin/'.$module.'.class.php:';
					echo $m.'</br>';
					$i++;
				}
			}	
		}
	}
	function index2(){
		//����node.php
		require_once str_replace('public_html','Admin/conf/node.php',$_SERVER['DOCUMENT_ROOT']);
		//��ȡnode.php��module��action
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
		
		//��ȡ/DmsAdmin/Lib/Action/Admin/������Action��
		$directory = str_replace('public_html','Admin/Lib/Action',$_SERVER['DOCUMENT_ROOT']);
		$rsts= $this->read($directory);
		foreach( $rsts as $item){
			if($item!='CommonAction.class.php')
			{
				//����Action��
				require_once $directory.'/'.$item;
				$class_name = substr($item,0,strpos($item,'.'));
				//���䷨,��ȡ����public����
				$r = new ReflectionClass($class_name);
				foreach( $r->getMethods() as $key =>$methodObj){
					 if($methodObj->isPublic()){
					 	$module = $methodObj->class;
					 	$methods1[$module][] = $methodObj->name;
					 }
				}
			}
		}
		//ɾ���̳е�Action
		unset($methods1['Action']);
		unset($methods1['CommonAction']);
		
		//�ж�$methods1��ģ��ͷ����治����$action_name1��
		foreach($methods1 as $module => $method){
			foreach($method as $m){
				if(!in_array($m,$action_name1[$module]) && substr($m,0,1)!='_'){
					echo '/Admin/Lib/Action/'.$module.'.class.php:';
					echo $m.'</br>';
					$i++;
				}
			}
		}
	}
	//��ȡĿ¼�µ��ļ�
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