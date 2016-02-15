<?php if (!defined('THINK_PATH')) exit();?><div class="pageContent"><div class="panelBar"><ul class="toolBar"><!--<li><a class="add" id="download" href="__URL__/downloadBak/file/{back_name}" target="dwzExport"><span>下载</span></a></li><li><a class="edit" href="__URL__/recover/file/{back_name}" target="ajaxTodo" mask="true" title="确定恢复数据库吗？"><span>恢复</span></a></li>--><li><a class="delete" href="__URL__/deletebak/file/{back_name}" target="ajaxTodo" title="确定删除该条备份吗？"><span>删除</span></a></li><li><a class="add" href="javascript:;" title="备份数据库"><span onclick="mylocation('__URL__/back','备份数据库',500,200)">备份数据库</span></a></li><li><a class="delete" href="__URL__/clear" target="dialog" mask="true" width="530" height="200"><span>清空数据库</span></a></li><!-- <li><a class="edit" href="__URL__/query_sql" target="dialog" mask="true" width="600" height="400"><span>控制台</span></a></li> --><!--		<li><a class="delete" href="__URL__/delectz" target="dialog" mask="true" width="530" height="300"><span>根据时间删除备份</span></a></li>--></ul></div><table class="table" width="100%" layoutH="138"><thead><tr><th>备份文件名</th><th>备份时间</th><th>备份大小</th><th>操作</th></tr></thead><tbody><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr target="back_name" rel="<?php echo ($vo["name"]); ?>"><td><?php echo ($vo["shortname"]); ?></td><td><?php echo ($vo["time"]); ?></td><td><?php echo ($vo["size"]); ?></td><!----><td><a href="/dbbackup/<?php echo ($vo["name"]); ?>" target="dwzExport">下载</a>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
				<a id="<?php echo ($vo["name"]); ?>"   title="确定恢复数据库吗？" <?php if(adminshow('cliSwitch')) echo 'href="###" onclick="submitrecover('."'".$vo['name']."'".')"'; else echo 'href="__URL__/prerecover/file/'.$vo['name'].'" target="ajaxTodo"'; ?>>恢复</a>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
				<a href="__URL__/deletebak/file/<?php echo urlencode($vo['name']);?>" target="ajaxTodo" title="确定删除该条备份吗？">删除</a></td></tr><?php endforeach; endif; else: echo "" ;endif; ?></tbody></table></div><script>$(function(){
	$("#download").click(function(){
		if(typeof($("#back_name").val())=="undefined"){
			alertMsg.error('请选择信息')
			return false;
		}else{
			$(this).attr("href","__URL__/downloadBak/file/"+$("#back_name").val());
		}
	});
	$("input[name=autobackup]").change(function(){
		
		if($('input[name=autobackup]').attr("checked")==true || $('input[name=autobackup]').attr("checked")=='checked'){
			var is=1;
		}else{
			var is=0;
		}
		//alert(is)
		$.post('__URL__/autobackup',{is:is},function(data){
			//alert(data)
		});
	});
});


function mylocation(url,title,width,height){
	$.pdialog.open(url,'newdialog',title,{width:width,height:height,mask:true,mixable:true,minable:true,resizable:true,drawable:true});
}

var curline = 0;
var flushflag = 1;
getstate();
function getstate()
{
	$.ajax({
       url:"__APP__/Backup/getstateajax",
       type:"POST",
       data:null,
       dataType:"JSON",
       global:false,
       success:function(data){
       		if(data.status>0){
	       		flushflag =0;
	       		mylocation('__URL__/progress','运行中',500,200);
	       		
       		}else{
       			flushflag=1;
       		}
       }
    });
    if(flushflag==0){
    	setTimeout(arguments.callee, 3000);
    }

}

function submitrecover(name)
{
	postdata = {file:name};
	$.ajax({
       	url:"__APP__/Backup/prerecover",
       	type:"GET",
       	data:postdata,
       	dataType:"JSON",
       	global:false,
       	success:function(data){
       		if(data.status){
				flushflag =0;
				mylocation('__URL__/progress','运行中',500,200);
			}else{
				flushflag=1;
			}
       	}
    });
    //
}
</script>