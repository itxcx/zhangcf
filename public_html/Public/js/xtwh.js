/*$(document).bind('keydown',function(event){
	if(event.keyCode == '27')
	{
		//按esc键时隐藏弹层
		try
		{
			$.pdialog.closeCurrent();
		}
		catch(e)
		{
			
		}
	}
	if(event.shiftKey==true && event.ctrlKey==true && event.altKey==true && event.keyCode == '123'){
		//判断是否已开启
		//mylocation("/Admin/index.php?s=/Public/checkPass",'newdialog','系统维护密码验证',500,280)
		$.pdialog.open("?s=/Public/checkPass",'newdialog','系统维护密码验证',{width:300,height:150,fresh:false,mask:true,mixable:true,minable:true,resizable:true,drawable:true});
	}
});*/
//获取url中#值,显示系统维护
$(function(){
	if(document.location.hash == '#weihu'){
		$("#navMenu ul").append('<li id="sysmenu" style="display:none"><a href="javascript:;" item="xitongweihu"><span>系统维护</span></a></li>');
		var html = 
				'<div id="xitongweihu" style="display:none" class="app_menu_div">'+
					'<div fillspace="sideBar" class="accordion dwz-accordion">'+
						'<div class="accordionHeader">'+
						'<h2 class="collapsable"><span>system</span>系统维护</h2>'+
						'</div>'+
						'<div class="accordionContent">'+
							'<ul class="tree treeFolder expand">'+
								'<li>'+
									'<a rel="whrunstate" target="navTab" title="运行设置" href="?s=/RunSet/index">'+
										'运行模式及日志设置'+
									'</a>'+
								'</li>'+
								'<li>'+
										'<a rel="wh1" target="navTab" title="系统加密打包" href="?s=/Lock/index">'+
											'系统加密打包操作'+
										'</a>'+
								'</li>'+
								'<li>'+
										'<a rel="wh2" target="navTab" title="快速安装" href="?s=/Install/index">'+
											'快速安装'+
										'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh3" target="navTab" title="系统文件处理" href="?s=/Bom/index">'+
										'系统文件处理'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh4" target="navTab" title="系统设置" href="?s=/System/index">'+
										'系统设置'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh5" target="navTab" title="数据库操作" href="?s=/Backup/men_index">'+
										'数据库维护操作'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh8" target="navTab" title="系统时间设置" href="?s=/System/settime/setShow/1">'+
										'系统时间设置'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh9" target="navTab" title="批量注册" href="/index.php?s=/Admin/Tools/index">'+
										'批量注册'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh10" target="navTab" title="模版设置" href="/index.php?s=/Admin/Config/ThemeTempSetup">'+
										'模版设置'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh11" target="navTab" title="自动结算" href="/index.php?s=/Admin/Cal/AutoSet">'+
										'自动结算地址'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh12" target="navTab" title="数据修正" href="/index.php?s=/Admin/Repair/index">'+
										'数据修正'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh12" target="navTab" title="系统自检" href="/index.php?s=/Admin/Check/index">'+
										'系统自检'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh12" target="navTab" title="货币归档" href="/index.php?s=/Admin/Tools/arrange">'+
										'货币归档'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh13" target="_blank" title="结算日志分析" href="?s=/SqlLog/index">'+
										'结算日志分析'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh14" target="navTab" title="添加产品功能" href="/index.php?s=/Admin/ProductCategory/productfunction">'+
										'添加产品功能'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="" target="_blank" title="测试页面" href="/index.php?s=/Admin/Test/index">'+
										'TestAction'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh15" target="navTab" title="修改系统维护密码" href="?s=/Public/changePass">'+
										'修改系统维护密码'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh16" target="navTab" title="检测权限功能" href="/index.php?s=/Admin/AuthCheck/index1">'+
										'检测权限功能'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh16" target="navTab" title="检测权限功能" href="/index.php?s=/Admin/AuthCheck/index2">'+
										'检测权限功能'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="wh17" target="navTab" title="名称修改" href="/index.php?s=/Admin/Mc/index">'+
										'名称修改'+
									'</a>'+
								'</li>'+
								'<li>'+
									'<a rel="langset" target="navTab" title="语言管理" href="/index.php?s=/Admin/Lang/index">'+
										'语言管理'+
									'</a>'+
								'</li>'+
							'</ul>'+
						'</div>'+
					'</div>'+
				'</div>';
		$("#sidebar").append(html);
		$("#navMenu li").removeClass();
		$("#sysmenu").show().addClass('selected');
		$(".app_menu_div").hide();
		$("#xitongweihu").show();
	}
});