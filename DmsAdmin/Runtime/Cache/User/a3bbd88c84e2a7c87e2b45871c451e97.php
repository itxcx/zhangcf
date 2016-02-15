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
</script></head><!--onkeydown="if(event.keyCode==116){location.href='__GROUP__/Index/index';return false;}"--><body  id="blanc_blue"><!----><!--头部--><div class="header"><div class="left" style="padding-top: 15px;text-indent: 10px;"><span style="font-size:20px;padding-top:50px;color:white"><?php echo CONFIG('SYSTEM_COMPANY');?><span style="font-size:11px;display:block;padding-top:5px;"><?php echo CONFIG('SYSTEM_MEMO');?></span></span></div><div class="right"><div class="right_1"><div id="user-nav" class="navbar"><ul class="nav1"><li><a title="" href="__GROUP__/Index/index"><div class="icon-user"></div><span class="text">欢迎回来！</span></a></li><li><a title="" href="__GROUP__/User/viewnotice"><div class="icon-messages"></div><span class="text">系统信息</span></a></li><!-- <li><a title="" href=""><div class="icon-cog"></div><span class="text">系统设置</span></a></li> --><li><a title="" href="__GROUP__/Public/logout"><div class="icon-share-alt"></div><span class="text">安全退出</span></a></li><li style="float:right; border:none; padding-top:5px;"><form class="top_form" ><input type="text" value="" class="input_left"/><input type="button" value="" class="input_search"/></form></li></ul></div></div><div class="right_2"><div id="breadcrumb"><span class="icon-home"></span><a href="__GROUP__/Index/index"><span class="icon-align-justify"></span><?php echo L('home_page');?></a></div></div></div><div class="clearfix"></div></div><!--头部结束--><div class="centre"><!--左侧菜单栏--><div class="centre_left"><div class="container"><div  <?php if($now_model == 'Index'): ?>class="menuTitle_active"<?php else: ?>class="menuTitle"<?php endif; ?> ><a href="__GROUP__/Index/index"><span class="dh-home"></span>首页</a></div><?php if(is_array($menu)): foreach($menu as $key=>$vo): ?><div  id="<?php echo ($key); ?>" class="menuTitle" ><span class="dh-signal" oldClass=""></span><?php echo ($key); ?><span class="dh_sec"><?php echo (count($vo)); ?></span></div><div class="menuContent"><ul id="ulstyle"><?php if(is_array($vo)): foreach($vo as $key=>$val): if(!$userMenuPower or in_array($val['model'].'-'.$val['action'],$userMenuPower)): ?><li id="<?php echo ($val["title"]); ?>"><a href="__GROUP__/<?php echo ($val["model"]); ?>/<?php echo ($val["action"]); ?>"><?php echo ($val["title"]); ?></a></li><?php endif; endforeach; endif; ?></ul></div><?php endforeach; endif; ?></div></div><!--左侧菜单栏结束--><link href="__TMPL__Public/style/res.css" rel="stylesheet" type="text/css" /><link href="__TMPL__Public/style/view.css" rel="stylesheet" type="text/css" /><div class="centre_right"><!--<script type="text/javascript" src="__PUBLIC__/directSell/area_select.js"></script>--><div class="core_main Sale" id="usereg"><div class="core_title"><span class="core_title_con"><span>当前位置</span>： <?php echo ($nowtitle); ?></span></div><div class="core_con" style="overflow:hidden"><div id="salereg" style="<?php if(isset($regAgreement)): ?>display:none<?php endif; ?>"><form name="form1" method="post" action="__GROUP__/Sale/regSave:__XPATH__" id="form"><table class="tablebg" id="table1"><tr><td class="tbkey" ><?php echo ($user->byname); ?>编号：</td><td class="tbval" ><?php if(($user->idEdit == true) and ($user->idAutoEdit == true)): ?><input type="text" value="<?php echo ($userid); ?>" name="userid" id="userid"/><?php elseif(($user->idAutoEdit == true)): echo ($userid); else: ?><input type="text" value="" name="userid" id="userid"/><?php endif; ?><span class="msg" id="state_userid" style="color: red"></span></td></tr><?php if(($sale->setLv == true)): ?><TR><td class="tbkey" ><?php echo ($levels->byname); ?>：</td><td class="tbval" ><select name='lv' id="lv" <?php if($zkbool == true): ?>onChange="user_getTotalzf('<?php echo ($sale->name); ?>','Sale');"<?php endif; ?>><?php if(is_array($levelsopt)): foreach($levelsopt as $key=>$level): if(!empty($maxlv)): if($level["lv"] <= $maxlv): ?><option value="<?php echo ($level["lv"]); ?>"><?php echo ($level["name"]); ?></option><?php endif; else: ?><option value="<?php echo ($level["lv"]); ?>"><?php echo ($level["name"]); ?></option><?php endif; endforeach; endif; ?></select>                            &nbsp;&nbsp;<span class="msg" id="state_lv"></span></td></TR><?php else: ?><input type="hidden" name="lv" id="lv" value="<?php echo ($sale->defaultLv); ?>" /><?php endif; if(($sale->setNumber == true)): ?><tr><td class="tbkey" >单数：</td><td class="tbval" ><input type="text" value="" name="setNumber" />&nbsp;&nbsp;<span class="msg" id="state_setNumber">*</span></td></tr><?php endif; if(($sale->setMoney == true)): ?><tr><td class="tbkey" >报单金额：</td><td class="tbval" ><input type="text" value=""  name="setMoney" />&nbsp;&nbsp;<span class="msg" id="state_setMoney">*</span></td></tr><?php endif; ?><!--基本信息--><?php if(in_array('name',$show) == true): ?><tr><td class="tbkey" >姓名：</td><td class="tbval" ><span><input type="text" value="" name="name" /></span>&nbsp;&nbsp;<span class="msg" id="state_name"><?php if(in_array('name',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('sex',$show) == true): ?><tr><td class="tbkey" >性别：</td><td class="tbval" ><span><input type="radio" name="sex" value="男" checked/>男<input type="radio" name="sex" value="女" />女</span>                            &nbsp;&nbsp;<span class="msg" id="state_sex"><?php if(in_array('sex',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('alias',$show) == true): ?><tr><td class="tbkey" >昵称：</td><td class="tbval" ><span><input type="text" value="" name="alias" /></span>&nbsp;&nbsp;<span class="msg" id="state_alias"><?php if(in_array('alias',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('id_card',$show) == true): ?><tr><td class="tbkey" >证件号码：</td><td class="tbval" ><span ><input type="text" value="" name="id_card"/></span>&nbsp;&nbsp;<span class="msg" id="state_id_card"><?php if(in_array('id_card',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('email',$show) == true): ?><tr><td class="tbkey" >Email：</td><td class="tbval" ><span><input type="text" value="" name="email" /></span>&nbsp;&nbsp;<span class="msg" id="state_email"><?php if(in_array('email',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('qq',$show) == true): ?><tr><td class="tbkey" >QQ：</td><td class="tbval" ><span><input type="text" value="" name="qq" /></span>&nbsp;&nbsp;<span class="msg" id="state_qq"><?php if(in_array('qq',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; ?><!--微信填写--><?php if(in_array('weixin',$show) == true): ?><tr><td class="tbkey" >微信账号：</td><td class="tbval" ><span><input type="text" value="" name="weixin"  onkeyup="getInfo(this)" id="weixin" autocomplete="off"/></span>&nbsp;&nbsp;<span class="msg" id="state_weixin"><?php if(in_array('weixin',$require) == true): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('country_code',$show) == true): ?><tr><td class="tbkey">国家区号：</td><td class="tbval"><span><!--data-pattern 正则表达式--><select name='country_code' id="country_code" style="width: 152px;height: 21px"><option value="86" data-pattern="^(86){0,1}1\d{10}$">中国大陆(+86)</option><option value="886" data-pattern="^(00){0,1}(886){1}0{0,1}[6,7,9](?:\d{7}|d{8}|\d{10})$">台湾(+886)</option><option value="852" data-pattern="^(00){0,1}(852){1}0{0,1}[1,5,6,9](?:\d{7}|d{8}|\d{12})$">香港(+852)</option><option value="60" data-pattern="^(00){0,1}(60){1}1d{8,9}$">马来西亚(+60)</option><option value="65" data-pattern="^(00){0,1}(65){1}[13689]d{6,7}$">新加坡(+65)</option><option value="81" data-pattern="^(00){0,1}(81){1}0{0,1}[7,8,9](?:\d{8}|d{9})$">日本(+81)</option><option value="82" data-pattern="^(00){0,1}(82){1}0{0,1}[7,1](?:\d{8}|d{9})$">韩国(+82)</option><option value="1us" data-pattern="^(00){0,1}(1){1}d{10,12}$">美国(+1)</option><option value="1ca" data-pattern="^(00){0,1}(1){1}d{10}$">加拿大(+1)</option><option value="61" data-pattern="^(00){0,1}(61){1}4d{8,9}$">澳大利亚(+61)</option><option value="64" data-pattern="^(00){0,1}(64){1}[278]d{7,9}$">新西兰(+64)</option><option value="54" data-pattern="^(00){0,1}(54){1}d{6,12}$">阿根廷(+54)</option><option value="971" data-pattern="^(00){0,1}(971){1}d{6,12}$">阿联酋(+971)</option><option value="353" data-pattern="^(00){0,1}(353){1}d{6,12}$">爱尔兰(+353)</option><option value="20" data-pattern="^(00){0,1}(20){1}d{6,12}$">埃及(+20)</option><option value="372" data-pattern="^(00){0,1}(372){1}d{6,12}$">爱沙尼亚(+372)</option><option value="43" data-pattern="^(00){0,1}(43){1}d{6,12}$">奥地利(+43)</option><option value="853" data-pattern="^(00){0,1}(853){1}6d{7}$">澳门(+853)</option><option value="1242" data-pattern="^(00){0,1}(1242){1}d{6,12}$">巴哈马(+1242)</option><option value="507" data-pattern="^(00){0,1}(507){1}d{6,12}$">巴拿马(+507)</option><option value="55" data-pattern="^(00){0,1}(55){1}d{6,12}$">巴西(+55)</option><option value="375" data-pattern="^(00){0,1}(375){1}d{6,12}$">白俄罗斯(+375)</option><option value="359" data-pattern="^(00){0,1}(359){1}d{6,12}$">保加利亚(+359)</option><option value="32" data-pattern="^(00){0,1}(32){1}d{6,12}$">比利时(+32)</option><option value="48" data-pattern="^(00){0,1}(48){1}d{6,12}$">波兰(+48)</option><option value="501" data-pattern="^(00){0,1}(501){1}d{6,12}$">伯利兹(+501)</option><option value="45" data-pattern="^(00){0,1}(45){1}d{6,12}$">丹麦(+45)</option><option value="49" data-pattern="^(00){0,1}(49){1}1(d{5,6}|\d{9,12})$">德国(+49)</option><option value="7" data-pattern="^(00){0,1}(7){1}[13489]d{9,11}$">俄罗斯(+7)</option><option value="33" data-pattern="^(00){0,1}(33){1}[168](d{5}|\d{7,8})$">法国(+33)</option><option value="63" data-pattern="^(00){0,1}(63){1}[24579](d{7,9}|\d{12})$">菲律宾(+63)</option><option value="358" data-pattern="^(00){0,1}(358){1}d{6,12}$">芬兰(+358)</option><option value="57" data-pattern="^(00){0,1}(57){1}d{6,12}$">哥伦比亚(+57)</option><option value="31" data-pattern="^(00){0,1}(31){1}6d{8}$">荷兰(+31)</option><option value="996" data-pattern="^(00){0,1}(996){1}d{6,12}$">吉尔吉斯斯坦(+996)</option><option value="855" data-pattern="^(00){0,1}(855){1}d{6,12}$">柬埔寨(+855)</option><option value="974" data-pattern="^(00){0,1}(974){1}d{6,12}$">卡塔尔(+974)</option><option value="370" data-pattern="^(00){0,1}(370){1}d{6,12}$">立陶宛(+370)</option><option value="352" data-pattern="^(00){0,1}(352){1}d{6,12}$">卢森堡(+352)</option><option value="40" data-pattern="^(00){0,1}(40){1}d{6,12}$">罗马尼亚(+40)</option><option value="960" data-pattern="^(00){0,1}(960){1}d{6,12}$">马尔代夫(+960)</option><option value="976" data-pattern="^(00){0,1}(976){1}d{6,12}$">蒙古(+976)</option><option value="51" data-pattern="^(00){0,1}(51){1}d{6,12}$">秘鲁(+51)</option><option value="212" data-pattern="^(00){0,1}(212){1}d{6,12}$">摩洛哥(+212)</option><option value="52" data-pattern="^(00){0,1}(52){1}d{6,12}$">墨西哥(+52)</option><option value="27" data-pattern="^(00){0,1}(27){1}d{6,12}$">南非(+27)</option><option value="234" data-pattern="^(00){0,1}(234){1}d{6,12}$">尼日利亚(+234)</option><option value="47" data-pattern="^(00){0,1}(47){1}d{6,12}$">挪威(+47)</option><option value="351" data-pattern="^(00){0,1}(351){1}d{6,12}$">葡萄牙(+351)</option><option value="46" data-pattern="^(00){0,1}(46){1}[124-7](d{8}|\d{10}|\d{12})$">瑞典(+46)</option><option value="41" data-pattern="^(00){0,1}(41){1}d{6,12}$">瑞士(+41)</option><option value="381" data-pattern="^(00){0,1}(381){1}d{6,12}$">塞尔维亚(+381)</option><option value="248" data-pattern="^(00){0,1}(248){1}d{6,12}$">塞舌尔(+248)</option><option value="966" data-pattern="^(00){0,1}(966){1}d{6,12}$">沙特阿拉伯(+966)</option><option value="94" data-pattern="^(00){0,1}(94){1}d{6,12}$">斯里兰卡(+94)</option><option value="66" data-pattern="^(00){0,1}(66){1}[13456789]d{7,8}$">泰国(+66)</option><option value="90" data-pattern="^(00){0,1}(90){1}d{6,12}$">土耳其(+90)</option><option value="216" data-pattern="^(00){0,1}(216){1}d{6,12}$">突尼斯(+216)</option><option value="58" data-pattern="^(00){0,1}(58){1}d{6,12}$">委内瑞拉(+58)</option><option value="380" data-pattern="^(00){0,1}(380){1}[3-79]d{8,9}$">乌克兰(+380)</option><option value="34" data-pattern="^(00){0,1}(34){1}d{6,12}$">西班牙(+34)</option><option value="30" data-pattern="^(00){0,1}(30){1}d{6,12}$">希腊(+30)</option><option value="36" data-pattern="^(00){0,1}(36){1}d{6,12}$">匈牙利(+36)</option><option value="39" data-pattern="^(00){0,1}(39){1}[37]d{8,11}$">意大利(+39)</option><option value="972" data-pattern="^(00){0,1}(972){1}d{6,12}$">以色列(+972)</option><option value="91" data-pattern="^(00){0,1}(91){1}d{6,12}$">印度(+91)</option><option value="62" data-pattern="^(00){0,1}(62){1}[2-9]d{7,11}$">印度尼西亚(+62)</option><option value="44" data-pattern="^(00){0,1}(44){1}[347-9](d{8,9}|\d{11,12})$">英国(+44)</option><option value="1284" data-pattern="^(00){0,1}(1284){1}d{6,12}$">英属维尔京群岛(+1284)</option><option value="962" data-pattern="^(00){0,1}(962){1}d{6,12}$">约旦(+962)</option><option value="84" data-pattern="^(00){0,1}(84){1}[1-9]d{6,9}$">越南(+84)</option><option value="56" data-pattern="^(00){0,1}(56){1}d{6,12}$">智利(+56)</option></select></span>                     &nbsp;&nbsp;<span class="msg" id="country_state"><?php if(in_array('country_code',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('mobile',$show) == true): ?><tr><td class="tbkey" >移动电话：</td><td class="tbval" ><span><input type="text" value="" name="mobile" id="mobile" /></span>&nbsp;&nbsp;<span class="msg" id="state_mobile"><?php if(in_array('mobile',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if($haveuser == true): if(!empty($fun_val)): if(is_array($fun_val)): foreach($fun_val as $key=>$fun): ?><tr><td class="tbkey" ><?php echo ($key); ?>：</td><td class="tbval" ><input type="text" name="<?php echo ($fun); ?>" value="" />&nbsp;&nbsp;<span class="msg" id="state_<?php echo ($fun); ?>"></span></td></tr><?php endforeach; endif; endif; endif; if(($haveuser == true)): if(is_array($nets)): foreach($nets as $key=>$net): if(($net["type"] == 'text')): ?><tr><td class="tbkey" ><?php echo ($net["name"]); ?>：</td><td class="tbval" ><span><input type="text" value="<?php echo ($net["value"]); ?>" name="<?php echo ($net["inputname"]); ?>" otherpost='<?php echo ($net["otherpost"]); ?>' onkeyup="getInfo(this)" id="<?php echo ($net["inputname"]); ?>" autocomplete="off"/></span>&nbsp;&nbsp;<span class="msg" id="state_<?php echo ($net["inputname"]); ?>"><?php if(($net["require"] == true)): ?>*<?php endif; ?></span></td></tr><tr><?php endif; if(($net["type"] == 'select')): ?><tr><td class="tbkey" ><?php echo ($net["name"]); ?>：</td><td class="tbval" ><select name='<?php echo ($net["inputname"]); ?>'  otherpost='<?php echo ($net["otherpost"]); ?>' id="<?php echo ($net["inputname"]); ?>" onchange="getInfo(this)"><?php if(is_array($net["Region"])): foreach($net["Region"] as $key=>$Region): ?><option value='<?php echo ($Region["name"]); ?>' <?php if(isset($_GET['position']) and $_GET['position']==$key): ?>selected<?php endif; ?>><?php echo ($Region["name"]); ?></option><?php endforeach; endif; ?></select>                            &nbsp;&nbsp;<span class="msg" id="state_<?php echo ($net["inputname"]); ?>"><?php if(($net["require"] == true)): ?>*<?php endif; ?></span></td></tr><?php endif; endforeach; endif; endif; if(isset($funReg)): if(is_array($funReg)): foreach($funReg as $key=>$fun): ?><tr><td class="tbkey"><?php echo ($fun); ?>：</td><td class="tbval"><span><input type="text" value="" name="<?php echo ($fun); ?>" /></span>							  &nbsp;&nbsp;<span class="msg" id="state_<?php echo ($fun); ?>"><?php if(in_array($fun,$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endforeach; endif; endif; ?><!--所属商铺--><?php if(!empty($shop)): ?><tr><td class="tbkey" ><?php echo ($shop); ?>：</td><td class="tbval" ><span><input type="text" value="" name="shop"  onkeyup="getInfo(this)" id="shop" autocomplete="off"/></span>&nbsp;&nbsp;<span class="msg" id="state_shop"><?php if(in_array('shop',$require) == true): ?>*<?php endif; ?></span></td></tr><?php endif; if($reg_safe == true): ?><tr><td class="tbkey">密保问题：</td><td class="tbval"><span><select name="secretsafe_name"><option value=""><?php echo L('请选择');?></option><?php if(is_array($SecretSafelist)): foreach($SecretSafelist as $key=>$SecretSafe): ?><option value="<?php echo ($SecretSafe["密保问题"]); ?>"><?php echo L($SecretSafe['密保问题']);?></option><?php endforeach; endif; ?></select></span>                    &nbsp;&nbsp;<span class="msg" id="state_secretsafe_name">*</span></td></tr><tr><td class="tbkey">密保答案：</td><td class="tbval"><span><input type="text" value="" name="secretanswer" /></span>                     &nbsp;&nbsp;<span class="msg" id="state_secretanswer">*</span></td></tr><?php endif; if(in_array('area',$show) == true): ?><tr><td class="tbkey" >地址：</td><td class="tbval" ><link rel="stylesheet" href="__PUBLIC__/areaselect/style.css"><div id="address-box"><div class="input-box" style="display: none;"><input class="country" name="country" type="text"><input class="province" name="province" type="text"><input class="city" name="city" type="text"><input class="county" name="county" type="text"><input class="town" name="town" type="text"></div><div class="country-select arrow-bg"><a class="country-now" href="javascript:void(0)">请选择国家</a><ul class="country-list"><li class="current"><i>&radic;</i><a data-value="0" href="javascript:void(0)">请选择国家</a></li><li><i>&radic;</i><a data-value="1" href="javascript:void(0)">中国</a></li><li><i>&radic;</i><a data-value="2" href="javascript:void(0)">海外</a></li></ul></div><div class="location-box arrow-bg"></div></div><!--[if lt IE 8]><script src="__PUBLIC__/areaselect/json2.js"></script><![endif]--><script src="__PUBLIC__/areaselect/areaselect.js"></script><script>                                $.areaSelect('#address-box', '__PUBLIC__/areaselect');
                            </script></td></tr><!--
                    <tr><td class="tbkey" >国家：</td><td class="tbval" ><select name="country" id="country_id" data-role="none"><option value="">请选择</option></select><select name="province"  id="province_id" data-role="none" <?php if($logistic == true): ?>onChange="user_getTotalzf('<?php echo ($sale->name); ?>','Sale');"<?php endif; ?>><option value="">省/州</option></select><select name="city"  id="city_id" data-role="none" ><option value="">城市</option></select></td></tr><tr><td class="tbkey" >区县：</td><td class="tbval" ><select name="county"  id="county_id" data-role="none" ><option value="">区/县</option></select><select name="town"  id="town_id" data-role="none" ><option value="">街道</option></select>                            &nbsp;&nbsp;<span class="msg" id="state_town" ><?php if(in_array('area',$require) == true ): ?>*<?php endif; ?></span></td></tr>                    --><?php endif; if(in_array('address',$show) == true): ?><tr><td class="tbkey" >详细地址：</td><td class="tbval" ><span><input type="text"  value="" name="address" /></span>&nbsp;&nbsp;<span class="msg" id="state_address" ><?php if(in_array('address',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('reciver',$show) == true): ?><tr><td class="tbkey" >收货人：</td><td class="tbval" ><span><input type="text" value="" name="reciver" /></span>&nbsp;&nbsp;<span class="msg" id="state_reciver" ><?php if(in_array('reciver',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('bank_apply_name',$show) == true): ?><tr><td class="tbkey" >开户行：</td><td class="tbval" ><select name="bank_apply_name" id="bank"><option value="">请选择</option><?php if(is_array($banklist)): foreach($banklist as $key=>$bank): ?><option value="<?php echo ($bank["开户行"]); ?>"><?php echo ($bank["开户行"]); ?></option><?php endforeach; endif; ?></select>                            &nbsp;&nbsp;<span class="msg" id="state_bank_apply_name" ><?php if(in_array('bank_apply_name',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('bank_card',$show) == true): ?><tr><td class="tbkey" >银行卡号：</td><td class="tbval" ><span ><input  type="text" value="" name="bank_card"/></span>&nbsp;&nbsp;<span class="msg" id="state_bank_card" ><?php if(in_array('bank_card',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('bank_name',$show) == true): ?><tr><td class="tbkey" >开户名：</td><td class="tbval" ><span><input type="text" value="" name="bank_name" /></span>&nbsp;&nbsp;<span class="msg" id="state_bank_name" ><?php if(in_array('bank_name',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('bank_apply_addr',$show) == true): ?><tr><td class="tbkey" >开户地址：</td><td class="tbval" ><span><input type="text" value="" name="bank_apply_addr" /></span>&nbsp;&nbsp;<span class="msg" id="state_bank_apply_addr" ><?php if(in_array('bank_apply_addr',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('pass1',$show) == true): ?><tr><td class="tbkey" >一级密码：</td><td class="tbval"><span><input type="password" value="" name="pass1" /></span>&nbsp;&nbsp;<span class="msg" id="state_pass1"><?php if(in_array('pass1',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('pass1c',$show) == true): ?><tr><td class="tbkey" >一级密码确认：</td><td class="tbval" ><span><input type="password" value="" name="pass1c" /></span>&nbsp;&nbsp;<span class="msg" id="state_pass1c"><?php if(in_array('pass1c',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('pass2',$show) == true): ?><tr><td class="tbkey" >二级密码：</td><td class="tbval" ><span><input type="password" value="" name="pass2" /></span>&nbsp;&nbsp;<span class="msg" id="state_pass2"><?php if(in_array('pass2',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('pass2c',$show) == true): ?><tr><td class="tbkey" >二级密码确认：</td><td class="tbval" ><span><input type="password" value="" name="pass2c" /></span>&nbsp;&nbsp;<span class="msg" id="state_pass2c"><?php if(in_array('pass2c',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(($pwd3Switch) == "true"): if(in_array('pass3',$show) == true): ?><tr><td class="tbkey" >三级密码：</td><td class="tbval" ><span><input type="password" value="" name="pass3" /></span>&nbsp;&nbsp;<span class="msg" id="state_pass3"><?php if(in_array('pass3',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; if(in_array('pass3c',$show) == true): ?><tr><td class="tbkey" >三级密码确认：</td><td class="tbval" ><span><input type="password" value="" name="pass3c" /></span>&nbsp;&nbsp;<span class="msg" id="state_pass3c"><?php if(in_array('pass3c',$require) == true ): ?>*<?php endif; ?></span></td></tr><?php endif; endif; ?></table><div style="margin-top: 10px;margin-left: 10px;float: left;margin: 5px;font-size: 14px;color: red;"><span id="state_lockcon"></span></div><!--基本信息结束--><?php if(isset($productArr)): ?><style>    .productSelect {
        background: #ccc;
        border-radius: 3px;
    }
    #state_productCountMoney {
        margin: 5px;
        font-size: 14px;
        color: red;
    }
</style><div class="core_main Sale" id="product"><div class="core_conp"><div id="state_productCountMoney"></div><table class="tablebg" id="table1"><thead><tr><td colspan="8" ><?php echo ($sale->productName); ?>选购</td></tr><tr ><td colspan="8" ><?php $i=1; if(is_array($productArr)): foreach($productArr as $key=>$product): ?><div id="productCategory_<?php echo ($i); ?>" productCategoryid="<?php echo ($i); ?>"><?php echo ($key); ?></div><?php $i++; endforeach; endif; ?></td></tr><tr><td >序号</td><td >产品名称</td><td >图片</td><td >数量</td><td ><?php echo ($sale->productMoney); ?></td><?php if(($sale->productPV) == "true"): ?><td >PV</td><?php endif; if($logistic == true): ?><td >重量</td><?php endif; if(($proobj->productnumCheck == true) or (adminshow('prostock') == true)): ?><td >库存</td><?php endif; ?></tr></thead><?php $ii=1; if(is_array($productArr)): foreach($productArr as $fenlei=>$product): ?><tbody id="productTbody_<?php echo ($ii); ?>" style="<?php if(($ii) != "1"): ?>display:none<?php endif; ?>"><?php if(is_array($product)): $i = 0; $__LIST__ = $product;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr><td><?php echo ($key+1);?></td><td><?php  $nums = M($proobj->name.'套餐')->where(array('产品id'=>$vo['id']))->count(); if($nums>0){ ?><a href="__URL__/showProtaocan/proid/<?php echo ($vo["id"]); ?>"><?php echo ($vo["名称"]); ?>(套餐详情)</a><?php }else{ ?><a href="__URL__/showProinfo/id/<?php echo ($vo["id"]); ?>"><?php echo ($vo["名称"]); ?></a><?php } ?></td><td><?php $imgstr=$vo['图片']; if((strlen($imgstr) == 0)): ?>无
                        <?php $productimg='无'; else: ?><img src="<?php echo ($vo["图片"]); ?>" width='120px' /><?php $productimg="<img src='".$vo['图片']."' width='120px'/>"; endif; ?></td><td><input type="text" name="productNum[<?php echo ($vo["id"]); ?>]"  id="productNum_<?php echo ($vo["id"]); ?>" productNumInfo="<?php echo ($vo["id"]); ?>_<?php echo ($fenlei); ?>_<?php echo ($vo["名称"]); ?>_<?php echo ($vo[$sale->productMoney]); ?>_<?php echo ($vo["PV"]); ?>_<?php echo ($vo["重量"]); ?>" pronum="<?php echo ($vo["可订购数量"]); ?>" style="width:35px" productimg="<?php echo ($productimg); ?>" /></td><td><?php echo ($vo[$sale->productMoney]); ?></td><?php if(($sale->productPV) == "true"): ?><td><?php echo ($vo["PV"]); ?></td><?php endif; if($logistic == true): ?><td><?php echo ($vo["重量"]); ?></td><?php endif; if(($proobj->productnumCheck == true) or (adminshow('prostock') == true)): ?><td id="state_productnum<?php echo ($vo["id"]); ?>"><?php echo ($vo["可订购数量"]); ?></td><?php endif; ?></tr><?php endforeach; endif; else: echo "" ;endif; ?></tbody><?php $ii++; endforeach; endif; ?></table><table class="tablebg" id="table2"><thead><tr><td colspan="8">已选产品</td></tr><tr><td >序号</td><td >类别</td><td >产品名称</td><td >图片</td><td >总计数量</td><td >总金额</td><?php if(($sale->productPV) == "true"): ?><td >总PV</td><?php endif; if($logistic == true): ?><td>总重量</td><?php endif; ?></tr></thead><tbody id="selectedProduct"></tbody><tr><td colspan="4"  style="text-align:right">汇总：</td><td id="totalnum">0</td><td id="totalprice">0</td><?php if(($sale->productPV) == "true"): ?><td id="totalpv">0</td><?php endif; if($logistic == true): ?><td id="totalweight">0</td><?php endif; ?></tr><?php if(($logistic == true) or ($zkbool == true)): ?><tr><td colspan="4" style="text-align:right;"><span style="display:inline-block;text-align:left;width:70%"><?php if($zkbool == true): ?>折扣：<span id="zk"></span>折<?php endif; ?>					&nbsp;&nbsp;&nbsp;
					<?php if($logistic == true): ?>物流费：<span id="wlf"></span><?php endif; ?></span><span>实际支付:</span></td><td id="totalzf" colspan="4" style="text-align:center;">0</td></tr><?php endif; ?></table></div></div><?php if(($logistic == true) or ($zkbool == true)): ?><script language="javascript" src="__PUBLIC__/js/cal.js"></script><?php endif; ?><script>  $(function(){
  	<?php if(($logistic == true) or ($zkbool == true)): ?>user_getTotalzf('<?php echo ($sale->name); ?>','Sale');<?php endif; ?>	var productStock = <?php if(($proobj->productnumCheck == true) or (adminshow('prostock') == true)): ?>true<?php else: ?>false<?php endif; ?>;
    $("[id^='productCategory_']").first().addClass('productSelect');
	$("[id^='productCategory_']").click(function(){
		$('[id^=productTbody_]').hide();
		$('#productTbody_'+$(this).attr('productCategoryid')).show();
	    $('[id^=productCategory_]').removeClass('productSelect');
	    $(this).addClass('productSelect');
	});
    $('[id^=productCategory_]').css({float:'left',cursor:'pointer',padding: '0 5px',margin:'0 3px',border:'1px solid #ccc','border-radius':'3px'});
	$("[id^=productNum_]").keyup(function(){
		var product =$(this).attr('productNumInfo').split("_");
		var productimg = $(this).attr('productimg');
		var num = $(this).val();
		num = parseInt(num.replace(/b(0+)/gi,""));
		if(!(num > 0 && (!isNaN(num)))){
			$(this).val('');
		}
		//开启库存时，不能超过库存量
		var realnum=$(this).attr('pronum');
		if(productStock && num > realnum){
			num =  parseInt(realnum);
			$(this).val(num);
		}
		
		//定义序号
		var k=0;
		$("#selectedProduct > tr").each(function(i,v){
			if($(v).attr('selectedProductid') == product[0]){
				$(this).remove();//删除
			}else{
				k=parseInt($(this).find("td:first").html());
			}
		});
		if((!isNaN(num)) && num > 0){
			$("#selectedProduct").append('<tr selectedProductid="'+product[0]+'" style="border-bottom:1px solid #EDEDED;"><td>'+(parseInt(k)+1)+'</td><td>'+product[1]+'</td><td>'+product[2]+'</td><td>'+productimg+'</td><td id="selnum_'+product[0]+'">'+num+'</td><td id="selprice_'+product[0]+'">'+(num*product[3]).toFixed(2)+'</td><?php if(($sale->productPV) == "true"): ?><td id="selpv_'+product[0]+'">'+(num*product[4]).toFixed(2)+'</td><?php endif; if($logistic == true): ?><td id="selweight_'+product[0]+'">'+(num*product[5]).toFixed(2)+'</td><?php endif; ?></tr>');
		}
		//统计
		var countNum = 0;
		var countMoney = 0;
		var countPV = 0;
		var countWeight = 0;
		$("#selectedProduct > tr").each(function(i,v){
			var proid=$(this).attr('selectedProductid');
			countNum +=parseFloat($('#selnum_'+proid).html());
			countMoney +=parseFloat($('#selprice_'+proid).html());
			<?php if($sale->productPV == true): ?>countPV +=parseFloat($('#selpv_'+proid).html());<?php endif; if($logistic == true): ?>countWeight +=parseFloat($('#selweight_'+proid).html());<?php endif; ?>		});
		
		$("#totalnum").html(countNum);
		$("#totalprice").html(countMoney.toFixed(2));
		<?php if(($sale->productPV) == "true"): ?>$("#totalpv").html(countPV.toFixed(2));<?php endif; if($logistic == true): ?>$("#totalweight").html(countWeight.toFixed(2));<?php endif; ?>		//计算实付款并显示
		<?php if(($logistic == true) or ($zkbool == true)): ?>user_getTotalzf('<?php echo ($sale->name); ?>','Sale');<?php endif; ?>	});
});
</script><?php endif; if(isset($bankRatio)): ?><TABlE class="tablebg" style="clear:both;width:90%;margin:10px auto; margin-top:50px;"><?php $ratio=0;$p=false; if(is_array($bankRatio)): foreach($bankRatio as $fkey=>$bankval): $val=0+$bankval['maxval']; if($bankval['extra']==false){ if(100>=$ratio && $val+$ratio>100){ $val=100-$ratio; } $ratio+=$val; } if(strstr($bankval['maxval'],"%")){ $p=true; } ?><tr><td class="tbkey" style="width:40%"><?php echo ($bankval["name"]); ?>（<?php echo $userinfo[$bankval['name']];?>）：</td><td class="tbval" style="width:20%">&nbsp;&nbsp;
	                		<?php if($bankval['extra']): echo ($val); else: ?><input  name="accval[<?php echo ($fkey); ?>]" value="<?php echo ($val); ?>" type="text" size="7"/><?php endif; if($p): ?>%<?php endif; ?>&nbsp;<span id="money<?php echo ($fkey); ?>">&nbsp;&nbsp;</span></td><td class="msg">&nbsp;<?php if($p): ?>支付时货币比率<?php else: ?>支付时货币金额<?php endif; ?>&nbsp;<?php if($bankval['extra']): ?>支付额外金额<?php endif; ?></td></tr><?php endforeach; endif; ?><tr><td colspan="3" class="msg">提示：设定的比率排除额外支付，相加等于支付订单金额的100%，并且每个货币的余额足够支付的比率！<br><span id="state_accval"></span></td></tr></TABLE><?php endif; ?><table class="tablebg" id="table3" style="clear:both"><TR><td colspan="3" ><input class="button_text" type="button" value="确定" onclick="regAjaxall()" id="regsubbutton"></TD></TR></table></form></div><?php if(isset($regAgreement)): ?><div id="regAgreement" style="display:block;"><table class="tablebg" id="table4"><tr><td class="tbkey" style="text-align:center;" >注册协议内容</td></tr><tr><td class="tbval"><?php echo ($regAgreement); ?></td></tr><tr><td class="tbkey"  style="text-align:center;" ><INPUT class="button_text" type="button" value="同意并注册" onclick="$('#regAgreement').hide();$('#salereg').show()"/></td></tr></table></div><?php endif; ?></div><div class="core_page"></div></div><?php if(($alert == true)): ?><!--是否显示确认框--><link rel="stylesheet" href="__PUBLIC__/zxxbox/common.css" type="text/css" /><script type="text/javascript" src="__PUBLIC__/zxxbox/jquery.zxxbox.3.0.js"></script><script type="text/javascript">function alertcheck(){
	var alertstr ='<table class="tablebg" style="margin-top:0;">';
		alertstr+='<tr><td class="tbkey" style="padding-right:5px;">'+'<?php echo ($levels->byname); ?>'+'</td>'
		alertstr+='<td class="tbval">'+$("#lv option:selected").text()+'</td></tr>';
	<?php if(is_array($nets)): foreach($nets as $key=>$net): if(($net["type"] == 'text')): ?>alertstr+='<tr><td class="tbkey" style="padding-right:5px;"><?php echo ($net["name"]); ?></td>';
		alertstr+='<td class="tbval">'+$("#<?php echo ($net["inputname"]); ?>").val()+'</td></tr>';<?php endif; endforeach; endif; ?>	alertstr+='<tr><td colspan="2" class="tbval" style="text-align:center;">一旦注册，将不能修改，确定请点击确认</td></tr>';      
	alertstr+='</table>';
	$.zxxbox.ask(alertstr, function(){
	    $('#form').submit();
		$('#regsubbutton').attr('disabled','true');
	    $.zxxbox.hide();
	}, null, {
	    title: "友情提示",
	    fix: true
	});
}
</script><?php endif; ?><script>$('#country_code').change(function () {
    if (test('#country_code', $('#mobile'))) {
        $('#state_mobile').html('√');
    } else {
        $('#state_mobile').html('×');
    }
});
$('#mobile').blur(function () {
    if (test('#country_code', $(this))) {
        $('#state_mobile').html('√');
    } else {
        $('#state_mobile').html('×');
    }
}).keyup(function () {
    if (test('#country_code', $(this))) {
        $('#state_mobile').html('√');
    } else {
        $('#state_mobile').html('×');
    }
});
function test(phoneNum, _this) {
    optionVal = $(phoneNum).val();
    pattern = RegExp($(phoneNum + ' option[value='+optionVal+']').attr("data-pattern") || ".+");
    return pattern.test(_this.val());
}

//$.area_default_show = true; //显示默认区域
//$.area_select_bind( 'country_id' , 'province_id' , 'city_id' , 'county_id','town_id');
var vd;
var lastname;
function getInfo(e)
{
    var thisname=e.name;
    if(lastname == thisname){
    
	clearTimeout(vd);
	vd = setTimeout("regAjax('"+e.id+"')",600);
	}else{
	regAjax(e.id);
	lastname=thisname;
	}
}
function regAjaxall()
{
	$("[id^=state_]").text("");
	$('#state_productCountMoney').text("");
	var arr=<?php echo ($jsrequire); ?>;
	var postdata	= {};
	$("input").each(function(i,n){
		 var postname	= n.name;
		 var value  = n.value;
		 postdata[postname]	= value;
	});
	$("select").each(function(i,n){
	 var postname	= n.name;
	 var value  = n.value;
	 postdata[postname]	= value;
	});
		$.post('__GROUP__/Sale/regAjax:__XPATH__',postdata,function(data){
		if(!data)
		{
			<?php if(($alert == true)): ?>alertcheck();
			<?php else: ?>				$('#form').submit();
				$('#regsubbutton').attr('disabled','true');<?php endif; ?>		}
		else
		{
			eval(data);
			return false;
		}
	});
}
function regAjax(name)
{
	var id			= $('#'+name).val();
	var postname	= name;
	var otherpost	= $('#'+name).attr('otherpost');
	var postdata = {postname:name};
		$("input").each(function(i,n){
		 var postname	= n.name;
		 var value  = n.value;
		 postdata[postname]	= value;
		  
		});
		$("select").each(function(i,n){
		 var postname	= n.name;
		 var value  = n.value;
		 postdata[postname]	= value;
		});
	   $.ajax({
	       url:"__APP__/User/Sale/regAjax:__XPATH__",
	       type:"POST",
	       data:postdata,
	       dataType:"script",
	       global:false,
	       success:function(data){
			   if(data == ''){
				  $("#state_"+name).html('<img src="__PUBLIC__/Images/ExtJSicons/tick.png"/>');
			   }else{
				  data;
			   }
	       }  
	    });
}
</script><div class="clearfix"></div></div><!--中间结束--></div></body></html>