<?php
/*
* 快捷搜索接口定义
*/
interface QuickSearchInterface 
{
	//根据输入的值进行搜索

	/*
	* 返回数据格式
	* 
	return '<table><tr><td>......</td></tr></table>'
	*
	*
	*
	*
	*/
	public function returnQuickSearch($name);
}
?>