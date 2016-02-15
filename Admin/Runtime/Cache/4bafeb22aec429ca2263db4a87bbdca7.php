<?php if (!defined('THINK_PATH')) exit();?><div class="pageContent"><form method="post" action="__APP__/Admin/update" class="pageForm required-validate" onsubmit="return validateCallback(this,navTabAjaxDone)"><input type="hidden" name="id" value="<?php echo ($vo["id"]); ?>" /><input type="hidden" name="account" value="<?php echo ($vo["account"]); ?>" /><input type="hidden" name="submit" value="1" /><input type="hidden" name="forwardUrl" value="__APP__/Admin/index<?php echo ($relodargs); ?>"/><input type="hidden" name="navTabTitle" value="管理员管理"/><div class="pageFormContent" layoutH="58"><table cellpadding="5" cellspacing="5"><tr><th width="80" style="text-align:right">帐号：</th><td style="text-align:left" width="200"><input type="text" class="required" name="account" value="<?php echo ($vo["account"]); ?>" /></td><th>昵称：</th><td style="text-align:left"><input type="text" class="required" name="nickname" value="<?php echo ($vo["nickname"]); ?>" /></td></tr><tr><th  style="text-align:right">密码：</th><td style="text-align:left"><input type="password" class="required" minlength="1" maxlength="20" id="password" name="password" value="noeditpass" /></td><th>邮箱：</th><td style="text-align:left"><input type="text" class="email" name="email" value="<?php echo ($vo["email"]); ?>" /></td></tr><tr><th  style="text-align:right">临时密码：</th><td style="text-align:left"><input type='text' value='<?php echo ($rndpass); ?>' readonly='true' size='32'/></td></tr><tr><th  style="text-align:right">yubi绑定：</th><td style="text-align:left"><a target="dialog" href='__URL__/addyubicloudprefix/aid/<?php echo ($vo["id"]); ?>' height="470" width="600" mask="true"><div class="buttonActive"><div class="buttonContent"><button id="googlePassBut" type="button" >添加绑定</button></div></div></a></td></tr><tr><th  style="text-align:right">yubi列表：</th><td style="text-align:left" colspan="3"><table><?php if(is_array($yubiprefixs)): foreach($yubiprefixs as $key=>$yubiprefix): ?><tr><td style="text-align:left"><?php echo ($yubiprefix["yubi_prefix"]); ?>&nbsp;<?php if(($yubiprefix['yubi_prefix_name']) != ""): ?>（<?php echo ($yubiprefix["yubi_prefix_name"]); ?>）<?php endif; ?>&nbsp;<?php if(($yubiprefix['endtime']) > "0"): ?>&nbsp;截止到&nbsp;<?php echo date("Y-m-d",($yubiprefix['endtime']+86400));?>&nbsp;<?php if(($yubiprefix['state']) == "1"): ?>失效<?php else: ?><span style="color:#ff0000">已失效<span><?php endif; endif; ?>&nbsp;</td><td><a  href="__URL__/delyubicloudprefix/aid/<?php echo ($vo["id"]); ?>/kid/<?php echo ($yubiprefix["id"]); ?>" target="dialog" height="230" width="620" mask="true"><div class="buttonActive"><div class="buttonContent"><button id="googlePassBut" type="button" >删除</button></div></div></a></td></tr><?php endforeach; endif; ?></table></td></tr><tr><th  style="text-align:right">超管权限：</th><td style="text-align:left" colspan="3"><input type="radio" name="admin_status" <?php if(($vo["admin_status"]) == "1"): ?>checked="true"<?php endif; ?> value="1"/><label for="status1">启用</label><input type="radio" name="admin_status" <?php if(($vo["admin_status"]) == "0"): ?>checked="true"<?php endif; ?> value="0"/><label for="status2">取消</label></td></tr><tr><th  style="text-align:right">状态：</th><td style="text-align:left" colspan="3"><input type="radio" name="status" <?php if(($vo["status"]) == "1"): ?>checked="true"<?php endif; ?> value="1" id="status1" /><label for="status1">启用</label><input type="radio" name="status" <?php if(($vo["status"]) == "2"): ?>checked="true"<?php endif; ?> value="2" id="status2" /><label for="status2">禁用</label></td></tr><tr><th  style="text-align:right">描 述：</th><td colspan="3" style="text-align:left"><TEXTAREA class="large bLeft" name="remark"  ROWS="3" COLS="40"><?php echo ($vo["remark"]); ?></TEXTAREA></td></tr></table><style>#navbar-container {
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
</style><div class="app_html"><?php if(isset($adminInfo)): ?><div style="padding:10px">管理员：<?php echo ($adminInfo["nickname"]); ?> ( <?php echo ($adminInfo["account"]); ?> )</div><?php endif; ?><div style="padding:10px" id="roleAccess">所属权限组：
	
	<?php if(is_array($roleList)): foreach($roleList as $key=>$role): ?><span style="padding-right:5px"><label style="cursor:pointer"><input name="role[]" value="<?php echo ($role["id"]); ?>" roleList="<?php echo ($role["access"]); ?>" <?php if(isset($roleAdmin) and in_array($role['id'],$roleAdmin)): ?>checked<?php endif; ?> type="checkbox"/><?php echo ($role["name"]); ?></label></span><?php endforeach; endif; if(isset($vo['admin_status']) and $vo['admin_status'] == 1): ?><span style="color:#3170e0;padding:2px 17px;">此帐号是超管 拥有所有权限</span><?php else: ?><span style="color:#3170e0;padding:2px 17px;">该颜色表示权限已在权限组中授予</span><?php endif; ?></div><?php if(!isset($vo) or (isset($vo['admin_status']) and $vo['admin_status'] != 1 and isset($admins['admin_status']) and $admins['admin_status'] == 1)): if(count($newtreeList) != 1): ?><div class="app_menu"><?php $w=0; if(is_array($newtreeList)): foreach($newtreeList as $mk=>$appList): $w++; ?><li <?php if($w == 1): ?>class="curr"<?php endif; ?> menu="menu_html_<?php echo ($w); ?>"><?php echo ($mk); ?></li><?php endforeach; endif; ?></div><?php endif; $m=0; if(is_array($newtreeList)): $mk = 0; $__LIST__ = $newtreeList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$appList): $mod = ($mk % 2 );++$mk; $m++; ?><div class="menu" id="menu_html_<?php echo ($m); ?>" style="<?php if(($m) != "1"): ?>display:none<?php endif; ?>" ><!--1start--><div id="navbar-container"><div class="navbar-bg"><ul id="navbar"><?php $i=0; if(is_array($appList)): foreach($appList as $fk=>$firstList): $i++; ?><li class="<?php if(($firstList['roleAccess']) == "1"): ?>navbar-item-color <?php else: ?> navbar-item<?php endif; if(($i) == "1"): echo PHP_EOL;?> navbar-item-selected<?php endif; ?>" fk='<?php echo ($fk); ?>' itemlist='1'><span style="padding-right:0px"><?php echo ($fk); ?></span><span style="padding:1px 30px 2px 10px;width:30px;height:30px;background:url('__PUBLIC__/dwz/themes/default/images/tree/check.png') no-repeat 20px <?php if( $firstList['adminAccess'] == 1): ?>-203px<?php elseif($firstList['adminAccess'] == 2): ?>-103px<?php else: ?>-3px<?php endif; ?>;" checklist='1' checktype="<?php if( $firstList['adminAccess'] == 1): ?>1<?php elseif($firstList['adminAccess'] == 2): ?>2<?php else: ?>0<?php endif; ?>">&nbsp;</span></li><?php endforeach; endif; ?></ul></div></div><div style="float:left;width:600px;height:500px" id="mainview"><div id="maincontent" style="height:300px"><?php $i=0; if(is_array($appList)): foreach($appList as $fk=>$firstList): $i++; ?><div style="<?php if(($i) != "1"): ?>display:none<?php endif; ?>" fk="<?php echo ($fk); ?>" class="content"><!--<div style="padding:15px;font-weight:bold;border-bottom: 1px solid #EEE;"><?php echo ($module["title"]); ?></div>--><div><?php if(is_array($firstList)): foreach($firstList as $sk=>$secondList): if(!isset($secondList["title"])): if(isset($secondList) and is_array($secondList)): ?><div style="padding:10px;font-weight:bold;border-top: 1px solid #6495ED;clear:both"><?php echo ($sk); ?></div><?php endif; if(is_array($secondList)): foreach($secondList as $tk=>$thirdList): ?><span style="padding:5px;width:140px;display:block;float:left;<?php if((isset($roleAccessArray) and in_array($thirdList['id'],$roleAccessArray))): ?>color:#3170e0<?php endif; ?>"><label style="cursor:pointer"><input type="checkbox" name="node[]" roleid="<?php echo ($thirdList['id']); ?>" value="<?php echo ($thirdList["id"]); ?>_<?php echo ($thirdList["pid"]); ?>_3" <?php if((isset($roleAccessArray) and in_array($thirdList['id'],$adminAccessArray))): ?>checked<?php endif; ?> ><?php echo ($thirdList["title"]); ?></label></span><?php endforeach; endif; ?><div style="clear:both;padding-bottom:10px;"></div><?php else: ?><span style="padding:5px;width:140px;display:block;float:left;<?php if((isset($roleAccessArray) and in_array($secondList['id'],$roleAccessArray))): ?>color:#3170e0<?php endif; ?>"><label style="cursor:pointer"><input type="checkbox" name="node[]"  roleid="<?php echo ($secondList['id']); ?>" value="<?php echo ($secondList["id"]); ?>_<?php echo ($secondList["pid"]); ?>_3" <?php if((isset($roleAccessArray) and in_array($secondList['id'],$adminAccessArray))): ?>checked<?php endif; ?> ><?php echo ($secondList["title"]); ?></label></span><?php endif; endforeach; endif; ?></div></div><?php endforeach; endif; ?></div></div><!--1end--></div><?php endforeach; endif; else: echo "" ;endif; endif; ?></div><script>$(function(){
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

</script></div><div class="formBar"><ul><li><div class="buttonActive"><div class="buttonContent"><button id="submit" type="submit">确定</button></div></div></li><li><div class="button"><div class="buttonContent"><button type="button" class="close">取消</button></div></div></li></ul></div></form></div><script language="JavaScript"></script>