//关闭当前navTab 打开新的navTab
function navTabAjaxDoneOpen(json){

	DWZ.ajaxDone(json);
	if (json.statusCode == DWZ.statusCode.ok)
	{
		if ("closeCurrent" == json.callbackType) {
			navTab.closeCurrentTab();
		}
		navTab.openTab(json.navTabId,json.forwardUrl,{ 'title':json.navTabTitle});

	}else{
		var $pagerForm = $("#pagerForm", navTab.getCurrentPanel());
		var args = $pagerForm.size()>0 ? $pagerForm.serializeArray() : {}
		navTabPageBreak(args, json.rel);
	}
}
//关闭dialog 刷新navTab
function dialogAjaxDoneReload(json){
	DWZ.ajaxDone(json);
	if (json.statusCode == DWZ.statusCode.ok){
		navTab.reload();
		if ("closeCurrent" == json.callbackType) {
			$.pdialog.closeCurrent();
		}
	}
}
//操作完成,刷新当前navTab
function navTabAjaxDoneReload(json){
	DWZ.ajaxDone(json);
	if (json.statusCode == DWZ.statusCode.ok){
		navTab.reload(json.forwardUrl);
	}else{
		var $pagerForm = $("#pagerForm", navTab.getCurrentPanel());
		var args = $pagerForm.size()>0 ? $pagerForm.serializeArray() : {}
		navTabPageBreak(args, json.rel);
	}
}
(function($){
	$.fn.extend({ //定义鼠标右键方法，接收一个函数参数 
		"rightSelect":function(fn){ //调用这个方法后将禁止系统的右键菜单 
			$(this).mousedown(function(e){ //为这个对象绑定鼠标按下事件  
				if(3 == e.which){ //如果按下的是右键，则执行函数
					//alert($(this).html()); 
					if (window.getSelection) {
						var sel = window.getSelection();
						sel.removeAllRanges();
						var range = document.createRange();
						range.selectNodeContents(this);
						sel.addRange(range);
					} 
					else if (document.selection) {
						var textRange = document.body.createTextRange();
						textRange.moveToElementText(this);
						textRange.select();
					}
				} 
			}); 
		} 
	});
})(jQuery);
