<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="icon" href="__PUBLIC__/favicon.ico" type="image/x-icon">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo CONFIG('SYSTEM_TITLE');?></title>
<link rel="stylesheet" type="text/css" href="__TMPL__Public/css/style.css" />
<link href="__TMPL__Public/css/css.css" rel="stylesheet" type="text/css">
<script  src="__PUBLIC__/jquery-1.x.min.js"></script>
<script src="__PUBLIC__/directSell/area_select.js" type="text/javascript"></script>
<script src="__PUBLIC__/js/xstable.js" type="text/javascript"></script>
<script src="__PUBLIC__/kindeditor/kindeditor.js"></script>
<script src="__PUBLIC__/js/transfer.js" type="text/javascript"></script>
<script type="text/javascript"> 
	$(document).ready(function(){
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
</script>
</head> 
<!--onkeydown="if(event.keyCode==116){location.href='__GROUP__/Index/index';return false;}"-->
<body  id="blanc_blue"><!---->
<!--头部-->
<div class="header">
 <div class="left" style="padding-top: 15px;text-indent: 10px;">
    <span style="font-size:20px;padding-top:50px;color:white"><?php echo CONFIG('SYSTEM_COMPANY');?><span style="font-size:11px;display:block;padding-top:5px;"><?php echo CONFIG('SYSTEM_MEMO');?></span></span> 
 </div>
 <div class="right">
   <div class="right_1">
     <div id="user-nav" class="navbar">
       <ul class="nav1">
         <li><a title="" href="__GROUP__/Index/index"><div class="icon-user"></div><span class="text">欢迎回来！</span></a></li>
         <li><a title="" href="__GROUP__/User/viewnotice"><div class="icon-messages"></div><span class="text">系统信息</span></a></li>
         <!-- <li><a title="" href=""><div class="icon-cog"></div><span class="text">系统设置</span></a></li> -->
         <li><a title="" href="__GROUP__/Public/logout"><div class="icon-share-alt"></div> <span class="text">安全退出</span></a></li>                        
         <li style="float:right; border:none; padding-top:5px;">        
         <form class="top_form" >
           <input type="text" value="" class="input_left"/>
           
           <input type="button" value="" class="input_search"/>
         </form>
           
         </li>         
       </ul>
     </div>
   </div>
   <div class="right_2">
     <div id="breadcrumb"><span class="icon-home"></span><a href="__GROUP__/Index/index"><span class="icon-align-justify"></span><?php echo L('home_page');?></a></div>
   </div>
 </div>
 <div class="clearfix"></div>
</div>
<!--头部结束-->

<div class="centre">
 <!--左侧菜单栏-->
 <div class="centre_left">
     <div class="container">
		<div  <?php if($now_model == 'Index'): ?>class="menuTitle_active"<?php else: ?>class="menuTitle"<?php endif; ?> > <a href="__GROUP__/Index/index"><span class="dh-home"></span>首页</a></div>
	
		<?php if(is_array($menu)): foreach($menu as $key=>$vo): ?><div  id="<?php echo ($key); ?>" class="menuTitle" ><span class="dh-signal" oldClass=""></span><?php echo ($key); ?><span class="dh_sec"><?php echo (count($vo)); ?></span></div>
			<div class="menuContent">
			<ul id="ulstyle">
			<?php if(is_array($vo)): foreach($vo as $key=>$val): if(!$userMenuPower or in_array($val['model'].'-'.$val['action'],$userMenuPower)): ?><li id="<?php echo ($val["title"]); ?>"><a href="__GROUP__/<?php echo ($val["model"]); ?>/<?php echo ($val["action"]); ?>"><?php echo ($val["title"]); ?></a></li><?php endif; endforeach; endif; ?>
			</ul>
			</div><?php endforeach; endif; ?>
     </div>
 </div>
 <!--左侧菜单栏结束-->

 






<script type="text/javascript">
function change(obj){
	var comment = document.getElementById(obj.id);
 
	if (document.all) {
	 // For IE
	comment.click();
	} else if (document.createEvent) {
	   //FOR DOM2
	var ev = document.createEvent('MouseEvents');
	 ev.initEvent('click', false, true);
	 comment.dispatchEvent(ev);
	}
}


</script>
<style>
#tabless ul{padding:0px;margin:0px;width:auto;border:1px solid #EEEEEE;}
#tabless li{padding:0;margin:0;float:left;height:30px;line-height:30px;padding:5px;}
</style>
<!--

	<td width="15%" align="right"><?php echo L('user1_name_serial');?>：</td>
				<td width="18%"><span class="tab_font"><?php echo ($userinfo["编号"]); ?></span></td>
				<td width="15%" align="right"><?php echo L('name');?>：</td>
				<td width="18%"><span class="tab_font"><?php echo ($userinfo["姓名"]); ?></span></td>
				
					<tr>
				<?php if(is_array($funbank)): foreach($funbank as $key=>$fun_bank): ?><td align="right"><?php echo ($key); ?>：</td>
				<td ><?php echo ($fun_bank); ?></td><?php endforeach; endif; ?>
			</tr>
-->
<div class="centre_right">
		<?php $i=1; ?>
		<?php if(is_array($menu)): foreach($menu as $key=>$vo): ?><div class="dh_img_<?php echo ($i); ?> dh_img" id='<?php echo ($key); ?>' onclick="change(this)"><center><img src="__TMPL__Public/images/<?php echo ($i); ?>.png" width="40" height="35"  style="cursor:pointer"/><div class="dh_font"><?php echo ($key); ?></div></center></div>
		<?php $i++; endforeach; endif; ?>
    <div class="dh_tab">
		<table border="0" width="96%" cellspacing="0" cellpadding="0" bgcolor="#FFFFFF" id="tabless" >
			<tr>
				<td  bgcolor="#efefef"><span class="tab_title"></span><span class="tab_font" style="line-height:20px;">基本信息</span></td>
			</tr>
			<tr>
			<td  style="border:none;padding:0px;">
	           <ul>
		            <li align="right"  style="width:19.1%;"><?php echo L('user1_name_serial');?>：</li>	
		            <li  style="width:26.2%;"><?php echo ($userinfo["编号"]); ?></li>	
		            <li  style="width:19.1%;" align="right" ><?php echo L('name');?>：</li>	
		            <li  style="width:26.2%;"><?php if($userinfo['姓名']): echo ($userinfo["姓名"]); else: ?>[暂无]<?php endif; ?></li>	
	           </ul>
              </td>
			</tr>
		    <tr>
			<td  width="100%" style="border:none;padding:0px">
	           <ul>
	           	<?php if(is_array($funbank)): foreach($funbank as $key=>$fun_bank): ?><li align="right"   style="width:19.1%;"><?php echo ($fun_bank["name"]); ?>：</li>	
		            <li style="width:26.2%;"><?php echo ($fun_bank["num"]); ?></li><?php endforeach; endif; ?>	
	           </ul>
              </td>
			</tr>
			<tr>
			
				<td  width="100%" style="border:none;padding:0px">
	           <ul>
	           	<?php if(is_array($userlevel)): foreach($userlevel as $key=>$lv): ?><li align="right"   style="width:19.1%;"><?php echo ($key); ?>：</li>	
		            <li style="width:26.2%;"><?php echo ($lv["byname"]); ?></li><?php endforeach; endif; ?>	
	           </ul>
              </td>
			</tr>
			<tr>
				<td width="100%" style="border:none;padding:0px">
	           <ul>
		            <li align="right"   style="width:19.1%;"><?php echo L('join_date');?>：</li>	
		            <li style="width:26.2%;"><?php echo (date('Y-m-d H:i:s',$userinfo["注册日期"])); ?></li>
		    
	           </ul>
	           </td>
			</tr>
	</table>

    </div> 
    <div style="width:48%;float:left;">
    <div class="dh_tab_1">
     <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#FFFFFF">
          <tr>
            <td width="5%" align="center" valign="middle" bgcolor="#efefef" class="dh_border_r"><div><img src="__TMPL__Public/images/breadcrumb.png" width="5" height="10" /></div></td>
            <td colspan="2" bgcolor="#efefef"><span class="tab_font" style="line-height:20px;">最新公告</span></td>
          </tr>
       </table>
     </div>
	 <div class='dh_tab_11'>
     <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#FFFFFF" style='line-height:40px;'>
		  <!-- 公告 -->
		  <?php if(is_array($notice)): foreach($notice as $key=>$vo): ?><tr>
            <td>
              <p style="font-weight:bold;padding-left:50px;"><a href="__GROUP__/User/shownotice/id/<?php echo ($vo["id"]); ?>" style="color: #000;"><?php echo ($vo["标题"]); ?></a></p>
              <!-- <p><?php echo ($vo["内容"]); ?></p> -->
            </td>
            <td style='text-align:center;width:150px;'>
             <?php echo (date('Y-m-d',$vo["创建时间"])); ?>
            </td>
          </tr><?php endforeach; endif; ?>
       </table>
     </div>
	</div>
    <div style="width:48%;float:left;">
    <div class="dh_tab_1">
     <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#FFFFFF">
          <tr>
            <td width="5%" align="center" valign="middle" bgcolor="#efefef" class="dh_border_r"><div><img src="__TMPL__Public/images/breadcrumb.png" width="5" height="10" /></div></td>
            <td colspan="2" bgcolor="#efefef"><span class="tab_font" style="line-height:20px;">最新邮件</span></td>
          </tr>
       </table>
     </div>
	 <div class='dh_tab_11'>
     <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#FFFFFF" style='line-height:40px;'>
		<!-- 邮件 -->
		  <?php if(is_array($mail)): foreach($mail as $key=>$vo): ?><tr>
            <td>
              <p style="font-weight:bold;padding-left:50px;"><a href="__GROUP__/Mail/view/id/<?php echo ($vo["id"]); ?>"><?php echo ($vo["标题"]); ?></a></p>
              <!-- <p>内容内容内容内容内容内容内容内容内容</p> -->
            </td>
            <td width="150px">
             <?php echo (date('Y-m-d',$vo["发送时间"])); ?>
            </td>
          </tr><?php endforeach; endif; ?>
       </table>
     </div>
	</div>
  </div>
 

 
</div>
<!--中间结束-->