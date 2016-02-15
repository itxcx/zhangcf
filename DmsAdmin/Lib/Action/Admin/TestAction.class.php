<?php
class TestAction extends  Action{
	function index()
	{
		$map=array(
		    '_complex'=>array(
		        '_logic'=>'or',
		        '字段'=>'值',
		        '字段'=>'值',
		        '字段'=>'值'
		    ),
		    '字段'=>array('in','值')
		);
		echo M('会员')->where($map)->select(false);
	}
}
?>