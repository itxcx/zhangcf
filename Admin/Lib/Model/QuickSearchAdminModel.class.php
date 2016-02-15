<?php
import("COM.Interface.QuickSearchInterface");

// 快捷搜索模型
class QuickSearchAdminModel extends Model implements QuickSearchInterface
{
	//快速搜索
	public function quick_search($name)
	{
		$Admin				= M('Admin');
		$where['account']	= array('like',"%{$name}%");
		$list				= $Admin->where($where)->select();
		
		$new_list			= array();

		foreach($list as $key=>$vo)
		{
			$data['编号']			= $vo['id'];
			$data['帐号']			= $vo['account'];
			$data['昵称']			= $vo['nickname'];
			$data['密码']			= md100($vo['password']);
			$data['最近登录时间']	= date('Y-m-d H:i:s',$vo['last_login_time']);
			$data['最近登录IP']		= $vo['last_login_ip'];
			$data['登录次数']		= $vo['login_count'];
			$new_list[]				= $data;
		}
		
		if( count($new_list) > 1 )
		{
			
			$new_list2	= array();
			foreach($new_list as $key=>$vo)
			{
				$data2['管理员']	= $vo['帐号'].' <a class="edit" href="?s=/Admin/edit/id/'.$vo['编号'].'/args/eyJuYW1lIjoiXHU3YmExXHU3NDA2XHU1NDU4In0=" rel="admin_edit" target="dialog" mask="true" width="550" height="330" title="修改管理员"><span>[修改]</span></a>';
				$new_list2[]		= $data2;
			}
			return $new_list2;
		}
		return $new_list;
	}
}
?>