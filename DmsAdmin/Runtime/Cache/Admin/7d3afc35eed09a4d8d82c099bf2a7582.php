<?php if (!defined('THINK_PATH')) exit();?><script type="text/javascript" >function navTabAjaxDoneToolsIndex(json)
{
	DWZ.ajaxDone(json);

	//if (json.statusCode == DWZ.statusCode.ok)
	//{
		navTab.openTab('<?php echo MD5(__APP__."/Admin/Tle/index:".$tlepath);?>','__APP__/Admin/Tle/index:<?php echo ($tlepath); ?>',{ 'title':'销售奖金查询'});
	//}
}
</script><script type="text/javascript" >function navTabAjaxDoneToolsIndex1(json)
{
	DWZ.ajaxDone(json);

	//if (json.statusCode == DWZ.statusCode.ok)
	//{
		navTab.openTab('<?php echo MD5(__APP__."/Admin/Tools/index");?>','__APP__/Admin/Tools/index',{ 'title':'批量注册'});
	//}
}
</script><div layoutH="0"><!--会员各个表--><div id="tb"><form action="__URL__/userInsert" method="post"  class="pageForm required-validate" onsubmit="return validateCallback(this, navTabAjaxDoneToolsIndex)"><table width="521" class="list"><thead><tr><th colspan="2" style="text-align:left;"><img src="__PUBLIC__/Images/user_add.png" style="vertical-align:middle" />&nbsp;&nbsp;&nbsp;批量注册</th></tr></thead><tbody><!--会员编号--><tr><td  style="text-align:left;width:150px">起始编号:</td><td  style="text-align:left"><input type="text" size="13" name="originUserNum" value="">&nbsp;&nbsp;注：留空则清空数据库重新注册</td></tr><!--会员编号--><tr><td style="text-align:left;width:150px">编号起点:</td><td style="text-align:left"><input type="text" size="13" name="serial" value="000001">&nbsp;&nbsp;<font color='blue'>注：默认密码1</font></td></tr><!--注册会员人数--><tr><td style="text-align:left">人数:</td><td style="text-align:left"><input id="num" type="text" size="10" name="num" value="10" />人&nbsp;&nbsp;<!--<input type="button" value="100" onclick="usernum(this);" /><input type="button" value="200" onclick="usernum(this);" /><input type="button" value="1000" onclick="usernum(this);" />--></td></tr><!--注册单类型--><?php if(!empty($sale_regs)): ?><tr><td style="text-align:left">注册类型:</td><td  style="text-align:left"><select name="sale_reg"><?php if(is_array($sale_regs)): foreach($sale_regs as $sale_regkey=>$sale_reg): ?><option value="<?php echo ($sale_regkey); ?>" selected><?php echo ($sale_regkey); ?>-<?php echo ($sale_reg["lvname"]); ?></option><?php endforeach; endif; ?></select></td></tr><?php endif; ?><!--会员级别的--><?php if(!empty($levels)): if(is_array($levels)): foreach($levels as $levelskey=>$levels): ?><tr><td style="text-align:left"><?php echo ($levelskey); ?>:</td><td style="text-align:left"><select name="level<?php echo ($levels["pos"]); ?>"><option value="rand">随机</option><!--
         <option value="lowmore">低级别比例高</option><option value="highmore">高级别比例高</option>
         --><?php if(is_array($levels)): foreach($levels as $key=>$level): if($key !== 'pos'){ ?><option value="<?php echo ($level["lv"]); ?>"><?php echo ($level["name"]); ?></option><?php } endforeach; endif; ?></select></td></tr><?php endforeach; endif; endif; ?><!--会员推荐网--><?php if(!empty($net_recs)): if(is_array($net_recs)): foreach($net_recs as $key=>$net_rec): ?><tr><td  style="text-align:left"><?php echo ($net_rec); ?>:</td><td  style="text-align:left"><input type="text" name="tjnum1<?php echo ($key); ?>" id="tjnum1<?php echo ($key); ?>" value="2" size="5">人&nbsp;&nbsp;<select onchange="tjnum(this,<?php echo ($key); ?>);"><option value="">请选择</option><option value="1-3">1-3人</option><option value="1-5">1-5人</option><option value="1-10">1-10人</option></select></td></tr><?php endforeach; endif; endif; ?><!--会员安置网--><?php if(!empty($net_places)): if(is_array($net_places)): foreach($net_places as $net_placekey=>$net_place): ?><tr><td  style="text-align:left"><?php echo ($net_placekey); ?>:</td><td  style="text-align:left"><select name="place<?php echo ($net_place["pos"]); ?>"><option value="balance">多线平衡</option><option value="desc">向下安置</option></select></td></tr><?php endforeach; endif; endif; ?><tr><td style="text-align:left">结算周期：</td><td style="text-align:left"><input type="text" name="tleday" value="0" size="5"/>天</td></tr><!--注册时间--><tr><td  style="text-align:left">注册时间:</td><td style="text-align:left"><input type="text" name="regdate" id="regdate" value="1" size="5">天&nbsp;&nbsp;
           <select onchange="regtime(this);"><option value="1">请选择</option><option value="递增">递增</option><option value="1">1天</option><option value="10">10天</option><option value="30">30天</option><option value="60">60天</option><option value="90">90天</option></select></td></tr><!--注册时间为递增时--><tr id="show2" style="disable:none"><td colspan="2" style="text-align:left"><table width="100%" id="checkList"><tbody><tr><td style="text-align:left;width:150px">首日人数:</td><td style="text-align:left"><input type="text" name="todaynum" id="todaynum" value="10" size="5">人</td></tr><tr><td style="text-align:left">日递增幅:</td><td style="text-align:left"><input name="everyadd" id="everyadd" type="text" value="0" size="5">%&nbsp;&nbsp;
             <select onchange="everydayadd(this);"><option value="0">请选择</option><option value="1">1%</option><option value="1.5">1.5%</option><option value="2">2%</option><option value="5">5%</option><option value="10">10%</option></select></td></tr></tbody></table></td></tr><!--货币--><?php if(is_array($funbanks)): foreach($funbanks as $key=>$bank): ?><tr><td  style="text-align:left">初始<?php echo ($bank); ?>:</td><td style="text-align:left"><input type="text" name="funbank[]" value="0" size="5">&nbsp;&nbsp;  
			</td></tr><?php endforeach; endif; ?><tr><td  style="text-align:left">随机种子:</td><td style="text-align:left"><input type="text" name="srand" value="000000" size="5"><?php if(isset($srand)): endif; ?></td></tr><tr><td  style="text-align:left">多线程:</td><td style="text-align:left"><input type="checkbox" name="thread" value="1" >&nbsp;&nbsp;注：适用于秒结测试
			</td></tr><!--提交--><tr><td colspan="2" valign="top"><div class="buttonActive" style="margin-left:210px;margin-top:5px;"><div class="buttonContent" ><button type="submit" onclick="return confirm('该操作会清除会员即相关所有信息，请慎重考虑');">确定</button></div></div></tr></tbody></table></form></div><!---注册压力测试--><form action="__URL__/userBulkInsert" method="post"  class="pageForm required-validate" onsubmit="return validateCallback(this, navTabAjaxDoneToolsIndex)"><table width="521" class="list"><thead><tr><th colspan="2" style="text-align:left;"><img src="__PUBLIC__/Images/user_add.png" style="vertical-align:middle" />&nbsp;&nbsp;&nbsp;注册压力测试</th></tr></thead><tbody><tr><td style="text-align:left">人数:</td><td style="text-align:left"><input id="num" type="text" size="10" name="num" value="10" />人&nbsp;&nbsp;<!--<input type="button" value="100" onclick="usernum(this);" /><input type="button" value="200" onclick="usernum(this);" /><input type="button" value="1000" onclick="usernum(this);" />--></td></tr><!--注册单类型--><?php if(!empty($sale_regs)): ?><tr><td style="text-align:left">注册类型:</td><td  style="text-align:left"><select name="sale_reg"><?php if(is_array($sale_regs)): foreach($sale_regs as $sale_regkey=>$sale_reg): ?><option value="<?php echo ($sale_regkey); ?>" selected><?php echo ($sale_regkey); ?>-<?php echo ($sale_reg["lvname"]); ?></option><?php endforeach; endif; ?></select></td></tr><?php endif; ?><tr><td colspan="2" valign="top"><div class="buttonActive" style="margin-left:210px;margin-top:5px;"><div class="buttonContent" ><button type="submit" >确定</button></div></div></tr></tbody></table></form><!---注册压力测试--><form action="__URL__/isuseregs" method="post"  class="pageForm required-validate" onsubmit="return validateCallback(this, navTabAjaxDoneToolsIndex1)"><table width="521" class="list"><thead><tr><th colspan="2" style="text-align:left;"><img src="__PUBLIC__/Images/user_add.png" style="vertical-align:middle" />&nbsp;&nbsp;&nbsp;系统设置中显示</th></tr></thead><tbody><tr><td colspan='2' ><label style="padding-right:50px;"><input type="radio" name='SHOW_BULKREG' value='1' <?php if(($SHOW_BULKREG) == "1"): ?>checked<?php endif; ?>/>开启</label><label><input type="radio" name='SHOW_BULKREG' value='0' <?php if(($SHOW_BULKREG) == "0"): ?>checked<?php endif; ?>/>关闭</label></td></tr><tr><td colspan="2" valign="top"><div class="buttonActive" style="margin-left:210px;margin-top:5px;"><div class="buttonContent" ><button type="submit" >确定</button></div></div></tr></tbody></table></form></div><!-----></div><script type="text/javascript">
function tjnum(obj,id)
{
  var tjnums=obj.value;
  //document.getElementById('tjnum1').value=tjnums;
  var id='tjnum1'+id;
    $('#'+id).val(tjnums);
 }
function regtime(obj)
{
  var regdates=obj.value;
  $('#regdate').val(regdates);
  if(regdates=='递增')
  {
  $('#show2').show();
  }else{
    $('#show2').hide();
  }
 }
function everydayadd(obj)
{
  var everyrate=obj.value;
  $('#everyadd').val(everyrate);
 }
</script>