<?php if (!defined('THINK_PATH')) exit();?><script>
function dialogAjaxDoneRoleAdd(json)
{
	DWZ.ajaxDone(json);

	if (json.statusCode == DWZ.statusCode.ok)
	{
		navTab.closeCurrentTab();
		navTab.openTab('<?php echo MD5(__URL__."/index");?>','__URL__/index',{ 'title':'权限组管理'});
	}
}
</script><div class="pageContent"><form method="post" action="__URL__/insert/navTabId/<?php echo md5(__URL__.'/index');?>" class="pageForm required-validate" onsubmit="return validateCallback(this,dialogAjaxDoneRoleAdd)"><input type="hidden" name="submit" value="1" /><input type="hidden" name="type" value="0"/><div class="pageFormContent" layoutH="58"><table cellpadding="5" cellspacing="5"><tr><th width="60">名称：</th><td style="text-align:left"><input type="text" class="required" name="name" /></td></tr><!--<tr><th>类型：</th><td style="text-align:left"><input type="radio" name="type" checked="true" value="1" id="type1" /><label for="type1">会员</label><input type="radio" name="type" value="0" id="type0" /><label for="type0">管理员</label></td></tr>--><tr><th>状态：</th><td style="text-align:left"><input type="radio" name="status" checked="true" value="1" id="status1" /><label for="status1">启用</label><input type="radio" name="status" value="2" id="status2" /><label for="status2">禁用</label></td></tr></table><style>#navbar-container {
bottom: 0;
left: 0;
right: 0;
top: 51px;
padding-top: 20px;
width: 150px;
z-index: 2;
background-color:#F8FAFB;
border-right:1px solid #c6c9ce;
float:left;
height:95%;
}

navbar-bg {
position: absolute;
z-index: 10;
right: 0;
width: 100%;
}
.navbar-item {
color: #5D7388;
cursor: pointer;
display: block;
font-size: 12px;
outline: none;
padding: 7px 0px;
text-align: right;
position: relative;
}
.navbar-item-color{
	color: #3170e0;
	cursor: pointer;
	display: block;
	font-size: 12px;
	outline: none;
	padding: 7px 0px;
	text-align: right;
	position: relative;
}
.navbar-item-selected {
-webkit-box-shadow: 0px 1px 0px #F7F7F7;
background-color: #326fde;
color: #fff;
}
.checked{
background-repeat:no-repeat;
background-position:165px -100px;
}
.indeterminate{
background-repeat:no-repeat;
background-position:165px -200px;
}
.menu{ width:auto; height:auto;}
.app_html{ width:auto;}
.app_menu{ width:auto; height:30px;}
.app_menu li{ float:left; width:auto; height:30px; padding:0 10px; line-height:30px; list-style-type:none; text-align:center; background:#AAD2FF; cursor:pointer;}
.app_menu li.curr{ background:#F2F2F2;}
</style><div class="app_html"><?php if(count($newtreeList) != 1): ?><div class="app_menu"><?php $w=0; if(is_array($newtreeList)): foreach($newtreeList as $mk=>$appList): $w++; ?><li <?php if($w == 1): ?>class="curr"<?php endif; ?> menu="menu_html_<?php echo ($w); ?>"><?php echo ($mk); ?></li><?php endforeach; endif; ?></div><?php endif; $m=0; if(is_array($newtreeList)): $mk = 0; $__LIST__ = $newtreeList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$appList): $mod = ($mk % 2 );++$mk; $m++; ?><div class="menu" id="menu_html_<?php echo ($m); ?>" style="<?php if(($m) != "1"): ?>display:none<?php endif; ?>" ><!--1start--><div id="navbar-container"><div class="navbar-bg"><ul id="navbar"><?php $i=0; if(is_array($appList)): foreach($appList as $fk=>$firstList): if($fk != ''): $i++; ?><li class="<?php if(($firstList['roleAccess']) == "1"): ?>navbar-item-color <?php else: ?> navbar-item<?php endif; if(($i) == "1"): echo PHP_EOL;?> navbar-item-selected<?php endif; ?>" fk='<?php echo ($fk); ?>' itemlist='1'><span style="padding-right:0px"><?php echo ($fk); ?></span><span style="padding:1px 30px 2px 10px;width:30px;height:30px;background:url('__PUBLIC__/dwz/themes/default/images/tree/check.png') no-repeat 20px <?php if( $firstList['roleAccess'] == 1): ?>-203px<?php elseif($firstList['roleAccess'] == 2): ?>-103px<?php else: ?>-3px<?php endif; ?>;" checklist='1' checktype="<?php if( $firstList['roleAccess'] == 1): ?>1<?php elseif($firstList['roleAccess'] == 2): ?>2<?php else: ?>0<?php endif; ?>">&nbsp;</span></li><?php endif; endforeach; endif; ?></ul></div></div><div style="float:left;width:800px;height:500px" id="mainview"><div id="maincontent" style="height:300px"><?php $i=0; if(is_array($appList)): foreach($appList as $fk=>$firstList): $i++; ?><div style="<?php if(($i) != "1"): ?>display:none<?php endif; ?>" fk="<?php echo ($fk); ?>" class="content"><!--<div style="padding:15px;font-weight:bold;border-bottom: 1px solid #EEE;"><?php echo ($module["title"]); ?></div>--><div><?php if(is_array($firstList)): foreach($firstList as $sk=>$secondList): if(!isset($secondList["title"])): if(is_array($secondList)): ?><div style="padding:10px;font-weight:bold;border-top: 1px solid #6495ED;clear:both"><?php echo ($sk); ?></div><?php endif; if(is_array($secondList)): foreach($secondList as $tk=>$thirdList): ?><span style="padding:5px;width:140px;display:block;float:left;"><label style="cursor:pointer"><input type="checkbox" name="node[]" roleid="<?php echo ($thirdList['id']); ?>" value="<?php echo ($thirdList["id"]); ?>_<?php echo ($thirdList["pid"]); ?>_3" <?php if((in_array($thirdList['id'],$accessList))): ?>checked<?php endif; ?>><?php echo ($thirdList["title"]); ?></label></span><?php endforeach; endif; ?><div style="clear:both;padding-bottom:10px;"></div><?php else: ?><span style="padding:5px;width:140px;display:block;float:left;"><label style="cursor:pointer"><input type="checkbox" name="node[]"  roleid="<?php echo ($secondList['id']); ?>" value="<?php echo ($secondList["id"]); ?>_<?php echo ($secondList["pid"]); ?>_3" <?php if((in_array($secondList['id'],$accessList))): ?>checked<?php endif; ?>><?php echo ($secondList["title"]); ?></label></span><?php endif; endforeach; endif; ?></div></div><?php endforeach; endif; ?></div></div><!--1end--></div><?php endforeach; endif; else: echo "" ;endif; ?></div><script>$(function(){
	$('#maincontent',navTab.getCurrentPanel()).html($('.mainview[fk=0]').html());
	$('[itemlist=1]').bind('click',function(e){
		
		var fk = $(this).attr('fk');
		$("#navbar>li",navTab.getCurrentPanel()).removeClass('navbar-item-selected');
		$(this).addClass('navbar-item-selected');
		$('.content',navTab.getCurrentPanel()).hide();
		$('.content[fk='+fk+']',navTab.getCurrentPanel()).css('display','block');
	});
	$('[checklist=1]',navTab.getCurrentPanel()).bind('click',function(){
		var checktype = $(this).attr('checktype');
		//alert(checktype)
		var fk = $(this).parent().attr('fk');
		//alert(fk)
		if(checktype == 0 || checktype == 1){
			$(this).attr('checktype','2');
			$(this).css('background-position','20px -103px');
			$('.content[fk='+fk+']',navTab.getCurrentPanel()).find('input[type=checkbox]').attr('checked',true);
		}else if(checktype == 2){
			$(this).attr('checktype','0');
			$(this).css('background-position','20px -3px');
			$('.content[fk='+fk+']',navTab.getCurrentPanel()).find('input[type=checkbox]').attr('checked',false);
		}
	});
	$('[checklist=1]',navTab.getCurrentPanel()).bind('mouseover',function(){
		var checktype = $(this).attr('checktype');
		if(checktype == 0){
			$(this).css('background-position','20px -53px');
		}else if(checktype == 1){
			$(this).css('background-position','20px -253px');
		}else if(checktype == 2){
			$(this).css('background-position','20px -153px');
		}
	}).mouseout(function(){
		var checktype = $(this).attr('checktype');
		if(checktype == 0){
			$(this).css('background-position','20px -3px');
		}else if(checktype == 1){
			$(this).css('background-position','20px -203px');
		}else if(checktype == 2){
			$(this).css('background-position','20px -103px');
		}
	});
	$("input[name='node[]']",navTab.getCurrentPanel()).change(function(){
		var parent = $(this).parents('.content');
		var fk = $(this).parents('.content').attr('fk');
		var num = 0;
		var count = parent.find("input[name='node[]']").length;
		//alert(count);
		parent.find("input[name='node[]']").each(function(i,v){
			if($(v).is(':checked')){
				num++;
			}
		});
		if(num == 0){
			$('[itemlist=1][fk='+fk+']>span[checklist=1]').css('background-position','20px -3px');
			$('[itemlist=1][fk='+fk+']>span[checklist=1]').attr('checktype','0');
		}else if(num == count){
			$('[itemlist=1][fk='+fk+']>span[checklist=1]').css('background-position','20px -103px');
			$('[itemlist=1][fk='+fk+']>span[checklist=1]').attr('checktype','2');
		}else if(num < count){
			$('[itemlist=1][fk='+fk+']>span[checklist=1]').css('background-position','20px -203px');
			$('[itemlist=1][fk='+fk+']>span[checklist=1]').attr('checktype','1');
		}
		
	});
	$("input[name='role[]']",navTab.getCurrentPanel()).change(function(){
		
		var accessStr='';
		$("input[name='role[]']:checked",navTab.getCurrentPanel()).each(function(i,v){
			accessStr +=$(v).attr('roleList')+'-';
		});
		var accessArr = accessStr.substring(0,accessStr.lastIndexOf('-')).split('-');
		$("input[name='node[]']",navTab.getCurrentPanel()).parent('label').parent('span').css('color','#000');
		$("#navbar>.navbar-item-color",navTab.getCurrentPanel()).addClass('navbar-item');
		$("#navbar>.navbar-item-color",navTab.getCurrentPanel()).removeClass('navbar-item-color');
		for(var i in accessArr){
			var thisobj = $("input[name='node[]'][roleid="+accessArr[i]+"]",navTab.getCurrentPanel());
			var fk = thisobj.parents('.content').attr('fk');
			if(!$('#navbar>li[fk='+fk+']',navTab.getCurrentPanel()).is('.navbar-item-color')){
				$('#navbar>li[fk='+fk+']',navTab.getCurrentPanel()).addClass('navbar-item-color');
			}
			thisobj.parent('label').parent('span').css('color','#3170e0');
		}
	});
	
	$(".app_menu>li",navTab.getCurrentPanel()).click(function(){
		$(this,navTab.getCurrentPanel()).addClass("curr").siblings().removeClass("curr");
		var tab = $(this).attr("menu");
		$("#" + tab,navTab.getCurrentPanel()).show().siblings('.menu',navTab.getCurrentPanel()).hide();  
	});
	
});

</script></div><div class="formBar"><ul><li><div class="buttonActive"><div class="buttonContent"><button id="submit" type="submit">确定</button></div></div></li><li><div class="button"><div class="buttonContent"><button type="button" class="close">取消</button></div></div></li></ul></div></form></div>