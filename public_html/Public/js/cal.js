//通用计算显示接口

//计算物流费---------------------------------------------------
//----前台计算实付款
function user_getTotalzf(salename,modelname){
	if(!modelname)
		modelname="Sale";
	var postdata={};
	postdata['province'] = $("#province_id  option:selected").text();//省份
 	postdata['weight'] = $("#totalweight").html();//总重量		
 	postdata['zongjia'] = $("#totalprice").html();
	postdata['lv'] = $("#lv").val();
	postdata['salename'] = salename;//类型
   	$.post('/index.php?s=/User/'+modelname+'/wuliufei',postdata,function(data){
		eval("var json="+data);
		var userinfo = json.data;
		$("#zk").html(userinfo['zk']*10);
		$("#wlf").html(userinfo['wlf']);
		$("#totalzf").html(userinfo['totalzf']);
	});
}

//----后台计算实付款
function admin_getTotalzf(salename){
	var postdata={};
	postdata['province'] = navTab.getCurrentPanel().find("#province_id  option:selected").text();//省份
 	postdata['weight']   = navTab.getCurrentPanel().find("#totalweight").html();//总重量		
 	postdata['zongjia']  = navTab.getCurrentPanel().find("#totalprice").html();//产品总价
 	postdata['lv']   = navTab.getCurrentPanel().find("#lv").val();//编号
	postdata['salename'] = salename;//类型
   	$.post('/index.php?s=/Admin/Sale/wuliufei',postdata,function(data){
		eval("var json="+data);
		var userinfo = json.data;
		navTab.getCurrentPanel().find("#zk").html(userinfo['zk']*10);
		navTab.getCurrentPanel().find("#wlf").html(userinfo['wlf']);
		navTab.getCurrentPanel().find("#totalzf").html(userinfo['totalzf']);
	});
}