<?php



//公共函数
function toDate($time, $format = 'Y-m-d H:i:s') {
	if (empty ( $time )) {
		return '';
	}
	$format = str_replace ( '#', ':', $format );
	return date ($format, $time );
}

function getStatus($status, $imageShow = true) {
	switch ($status) {
		case 0 :
			$showText = '待审';
			$showImg = '<img src="/Public/Images/tag.gif"  class="_status_image_" border="0" alt="待审" titele="待审" />';
			break;
		case 2 :
			$showText = '审核不通过';
			$showImg = '<img src="/Public/Images/status2.gif" class="_status_image_" border="0" alt="审核不通过" title="审核不通过" />';
			break;
	    case 3 :
			$showText = '锁定';
			$showImg = '<img src="/Public/Images/locked.gif" class="_status_image_" width="20" height="20" border="0" alt="锁定" title="锁定" />';
			break;
		case - 1 :
			$showText = '删除';
			$showImg = '<img src="/Public/Images/del.gif" class="_status_image_" width="20" height="20" border="0" alt="删除" title="删除" />';
			break;
		case 1 :
		default :
			$showText = '审核通过';
			$showImg = '<img src="/Public/Images/status1.gif" class="_status_image_" border="0" alt="审核通过" title="审核通过">';

	}
	return ($imageShow === true) ?  $showImg  : $showText;
}
function showStatus($status, $id) {
	switch ($status) {
		case 0 :
			$info = '<a href="javascript:common_pass(' . $id . ')">审核通过</a>';
			$info .= ' | <a href="javascript:common_forbid(' . $id . ')">审核不通过</a>';
			break;
		case 1 :
			$info = '<a href="javascript:common_forbid(' . $id . ')">审核不通过</a>';
			$info .= ' | <a href="javascript:common_recycle(' . $id . ')">还原待审</a>';
			break;
		case 2 :
			$info = '<a href="javascript:common_pass(' . $id . ')">审核通过</a>';
			$info .= ' | <a href="javascript:common_recycle(' . $id . ')">还原待审</a>';
			break;
	}
	return $info;
}
//返回当前登录用户名
function getLoginedUser()
{
    return isset($_SESSION['loginUserName'])?$_SESSION['loginUserName']:'未知';
}
//返回模块缓存目录
function getCacheDir($model,$id,$other='')
{
    $key = md5($id);
    //取前2位字符
    $dir = substr($key,0,2);
    return $model.'/'.$dir.'/'.intval($id).$other;
}
//缓存清理
function cacheClear($model,$id,$app="Public",$other='')
{
    $cacheFile    = "./{$app}/Html/".getCacheDir($model,$id,$other).'.html';
    if(file_exists_case($cacheFile)){
		unlink($cacheFile);
	}
}


//获取栏目名称
function getCategoryName($cid)
{
	$categroyList = F('categoryList');
	return isset($categroyList[$cid])?$categroyList[$cid]['name']:'暂无';
}

/*
* 图片宽高自动调整函数
*
* $width:	希望显示的宽度
*
* $height:	希望显示的高度
*/
function imageAutoReZize($url,$width,$height)
{
	$_image_info	= getimagesize($url);
	$_width			= $_image_info[0];
	$_height		= $_image_info[1];
	if( $_width <= $width || $_height <=$height )
	{
		return '<img src="'.$url.'" width="'.$_width.'" height="'.$_height.'" />';
	}
	for($i=0;$i<($width+$height);$i++)
	{
		$_width		= $_width / 2;
		$_height	= $_height / 2;
		if( $_width <= $width || $_height <=$height )
		{
			break;
		}
	}
	return '<img src="'.$url.'" width="'.$_width.'" height="'.$_height.'" />';
}
function menuList($con)
	{
		$menu=array();
		$list=array();
		foreach (X('user') as $v)
		{
			if(method_exists($v,'getmenu'))
			{
				$list=$v->getmenu($menu);
			}
		}
		return $list;
	}
//取得菜单对应的xml标题
function titleList($con)
{
        $title=array();
		$list=array();
		foreach (X('user') as $v)
		{
			if(method_exists($v,'gettitle'))
			{
				$list=$v->gettitle($title);
			}
		}
		return $list;
}
?>