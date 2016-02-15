<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><link rel="icon" href="__PUBLIC__/favicon.ico" type="image/x-icon"><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title><?php echo CONFIG('SYSTEM_TITLE');?></title><link rel="stylesheet" type="text/css" href="__TMPL__Public/css/style.css" /><link href="__TMPL__Public/css/css.css" rel="stylesheet" type="text/css"><script  src="__PUBLIC__/jquery-1.x.min.js"></script><script src="__PUBLIC__/directSell/area_select.js" type="text/javascript"></script><script src="__PUBLIC__/js/xstable.js" type="text/javascript"></script><script src="__PUBLIC__/kindeditor/kindeditor.js"></script><script src="__PUBLIC__/js/transfer.js" type="text/javascript"></script><script type="text/javascript">	$(document).ready(function(){
	//判断当前方法是哪一个 对其进行显
	var data = '<?php echo ($menu_jsons); ?>';
	var action = '<?php echo ($now_action); ?>';
	var model = '<?php echo ($now_model); ?>';
	var title='';
	data_arr = {};
	 data_arr = eval('('+data+')');
       for(var key in data_arr){//key 为资料管理 data_arr[key] 为一维数组 key 为一级菜单的标题
           for(var key1 in data_arr[key]){//key1 为一维数组的元素 data_arr[key][key1]['model']为模型 data_arr[key][key1]['Action']为方法 data_arr[key][key1]['title']为二级菜单标题  
             if(data_arr[key][key1]['model']==model && data_arr[key][key1]['action']==action){
               //替换属性的值
               $("#"+key).next().css('display','block').siblings(".menuContent").hide();
               //判断Action是否相等
               if(data_arr[key][key1]['action']==action){

                  $("#"+key).attr("class","menuTitle_active");                    	
						$("#"+data_arr[key][key1]['title']).addClass("curr");
                     	title = data_arr[key][key1]['title'];
               }
             }
             
           }
       
       }
   //判断事件     
          
		$("#ulstyle li").mousemove(function(){
		  if(title){
			 var title_now = $(this).attr("id");
			 if(title!=title_now){				
				$(this).addClass("curr");				
				$("#"+title).removeClass("curr");					
			 }
		  }
		});
		$("#ulstyle li").mouseleave(function(){
		  if(title){
			 var title_now = $(this).attr("id");
			 if(title!=title_now){				
				$(this).removeClass("curr");				
				$("#"+title).addClass("curr");					
			 }
		  }
		});
    });
    
 	$(function(){
		$(".menuTitle,.menuTitle_active").click(function(){
		   if($(this).next().css('display')=='none'){
			 $(this).next().css('display','block').siblings(".menuContent").hide();
		   }else{
			   $(this).next().css('display','none');
		   }
		});
		$("#ulstyle li").mousemove(function(){		  			
				$(this).addClass("curr");				
		});
		$("#ulstyle li").mouseleave(function(){		  			
			$(this).removeClass("curr");				
		});

        var bdh = $('body').height();
        var crh = $('.centre_right').height();
        if (bdh > crh) {
            $('.container').height(bdh - 83);
        } else {
            $('.container').height(crh + 50);
        }
	});	
</script></head><!--onkeydown="if(event.keyCode==116){location.href='__GROUP__/Index/index';return false;}"--><body  id="blanc_blue"><!----><!--头部--><div class="header"><div class="left" style="padding-top: 15px;text-indent: 10px;"><span style="font-size:20px;padding-top:50px;color:white"><?php echo CONFIG('SYSTEM_COMPANY');?><span style="font-size:11px;display:block;padding-top:5px;"><?php echo CONFIG('SYSTEM_MEMO');?></span></span></div><div class="right"><div class="right_1"><div id="user-nav" class="navbar"><ul class="nav1"><li><a title="" href="__GROUP__/Index/index"><div class="icon-user"></div><span class="text">欢迎回来！</span></a></li><li><a title="" href="__GROUP__/User/viewnotice"><div class="icon-messages"></div><span class="text">系统信息</span></a></li><!-- <li><a title="" href=""><div class="icon-cog"></div><span class="text">系统设置</span></a></li> --><li><a title="" href="__GROUP__/Public/logout"><div class="icon-share-alt"></div><span class="text">安全退出</span></a></li><li style="float:right; border:none; padding-top:5px;"><form class="top_form" ><input type="text" value="" class="input_left"/><input type="button" value="" class="input_search"/></form></li></ul></div></div><div class="right_2"><div id="breadcrumb"><span class="icon-home"></span><a href="__GROUP__/Index/index"><span class="icon-align-justify"></span><?php echo L('home_page');?></a></div></div></div><div class="clearfix"></div></div><!--头部结束--><div class="centre"><!--左侧菜单栏--><div class="centre_left"><div class="container"><div  <?php if($now_model == 'Index'): ?>class="menuTitle_active"<?php else: ?>class="menuTitle"<?php endif; ?> ><a href="__GROUP__/Index/index"><span class="dh-home"></span>首页</a></div><?php if(is_array($menu)): foreach($menu as $key=>$vo): ?><div  id="<?php echo ($key); ?>" class="menuTitle" ><span class="dh-signal" oldClass=""></span><?php echo ($key); ?><span class="dh_sec"><?php echo (count($vo)); ?></span></div><div class="menuContent"><ul id="ulstyle"><?php if(is_array($vo)): foreach($vo as $key=>$val): if(!$userMenuPower or in_array($val['model'].'-'.$val['action'],$userMenuPower)): ?><li id="<?php echo ($val["title"]); ?>"><a href="__GROUP__/<?php echo ($val["model"]); ?>/<?php echo ($val["action"]); ?>"><?php echo ($val["title"]); ?></a></li><?php endif; endforeach; endif; ?></ul></div><?php endforeach; endif; ?></div></div><!--左侧菜单栏结束--><link href="__TMPL__Public/style/res.css" rel="stylesheet" type="text/css" /><link href="__TMPL__Public/style/view.css" rel="stylesheet" type="text/css" /><div class="centre_right"><div class="core_main core_Sale" id="up"><div class="core_title"><span class="core_title_con"><?php echo ($msgTitle); ?></span></div><div class="core_con"><table class="message"  cellpadding=0 cellspacing=0 ><tr><td height='3'  class="topTd" ></td></tr><?php if(isset($message)): ?><tr class="row"><td style="color:blue"><?php echo ($message); ?></td></tr><?php endif; if(isset($error)): ?><tr class="row"><td style="color:red"><?php echo ($error); ?></td></tr><?php endif; if(isset($closeWin)): ?><tr class="row"><td>系统将在 <span id="wait" style="color:blue;font-weight:bold"><?php echo ($waitSecond); ?></span>        秒后自动关闭，如果不想等待,直接点击 <a id="href" href="<?php echo ($jumpUrl); ?>">这里</a> 关闭</td></tr><?php endif; if(!isset($closeWin)): ?><tr class="row"><td>系统将在 <span id="wait" style="color:blue;font-weight:bold"><?php echo ($waitSecond); ?></span>        秒后自动跳转,如果不想等待,直接点击 <a id="href" href="<?php echo ($jumpUrl); ?>">这里</a> 跳转</td></tr><?php endif; ?><tr><td height='3' class="bottomTd"></td></tr></table></div><div class="page"></div></div><meta http-equiv='Refresh' content='<?php echo ($waitSecond); ?>;URL=<?php echo ($jumpUrl); ?>'><style type="text/css">.message
{
color:#972D01;

}
</style><div class="main Public" id="success"><div class="core_con"><div class="message"></div></div></div><script type="text/javascript">(function(){
var wait = document.getElementById('wait'),href = document.getElementById('href').href;
var interval = setInterval(function(){
	var time = --wait.innerHTML;
	(time == 0) && (location.href = href);
}, 1000);	
})();
</script><div class="clearfix"></div></div><!--中间结束--></div></body></html>