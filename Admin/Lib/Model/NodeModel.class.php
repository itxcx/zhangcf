<?php
// 节点模型
class NodeModel extends CommonModel 
{
	/*
	protected $_validate	=	array(
		array('name','checkNode','节点已经存在',0,'callback'),
	);
	*/

	public function checkNode() 
	{
		$map['name']	= $_POST['name'];
		$map['pid']		= isset($_POST['pid'])?$_POST['pid']:0;
        $map['status']	= 1;
		$map['type']	= isset($_POST['type'])?$_POST['type']:0;
        if(!empty($_POST['id'])) {
			$map['id']	=	array('neq',$_POST['id']);
        }
		$result	=	$this->where($map)->field('id')->find();
        if($result) {
        	return false;
        }else{
			return true;
		}
	}

	/*
	* 获取节点树
	*/
	public function getNodeTree()
	{
		$appList = $this->field('id,name,title')->where('level=1 and status=1')->select();
		$arr = array();
		foreach($appList as $m=>$n){
			if($n['title'] !='系统设置'){
				$appname = $n['title'];
			}
			$nList = $this->field('id,name,title')->where('level=2 and status=1 and pid='.$n['id'])->select();
			if($nList){
				$idstr = '';
				foreach($nList as $a=>$b){
					$idstr .=$b['id'].',';
				}
				$firstList = $this->field('id,name,title,pid,parent,setparent')->where('level=3 and status=1 and pid in('.trim($idstr,',').')')->select();
				$nodeArr=array();
				foreach($firstList as $k=>$v){
					$nodeArr[$v['parent']] = isset($nodeArr[$v['parent']]) ? $nodeArr[$v['parent']] : array();
					if($v['setparent'] !=''){
						$nodeArr[$v['parent']][$v['setparent']] = isset($nodeArr[$v['parent']][$v['setparent']]) ? $nodeArr[$v['parent']][$v['setparent']] : array();
						$nodeArr[$v['parent']][$v['setparent']][] = $v;
					}else{
						$nodeArr[$v['parent']][] = $v;
					}
				}
				$arr[$n['title']]= $nodeArr;
			}
		}
		
		if(count($arr) ==2){
			foreach($arr['系统设置'] as $val){
				foreach($val as $k=>$v){
					$arr[$appname]['系统设置'][$k] = $v;
				}
			}
			unset($arr['系统设置']);
		}
		return $arr;
	}
}
?>