<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo ($langs['welcome_title']); ?></title>
<link href="/Public/<?php echo (APP_NAME); ?>/Common/css/install.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="/Public/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="/Public/<?php echo (APP_NAME); ?>/Common/js/transport.js"></script>
<script type="text/javascript" src="/Public/<?php echo (APP_NAME); ?>/Common/js/common.js"></script>
<script type="text/javascript" src="/Public/<?php echo (APP_NAME); ?>/Common/js/draggable.js"></script>
<script type="text/javascript" src="/Public/<?php echo (APP_NAME); ?>/Common/js/setting.js"></script>
<script type="text/javascript">

var $_LANG = {};

<?php foreach($langs['js_languages'] as $key=>$val){ ?>
$_LANG["<?php echo ($key); ?>"] = "<?php echo ($val); ?>";
<?php } ?>
</script>

</head>
<body id="welcome">
<div id="logos">
	<div id="lang-menu">
		<div id="lang-menu-inside">
		<?php echo ($langs['select_installer_lang']); ?><img src="/Public/<?php echo (APP_NAME); ?>/Common/images/cn.gif" alt="<?php echo ($langs['simplified_chinese']); ?>" />
		<input type="radio" name="js-lang" id="js-zh_cn" class="p" />
		<label for="js-zh_cn"><?php echo ($langs['simplified_chinese']); ?></label>&nbsp;&nbsp;&nbsp;&nbsp;
	

		<img src="/Public/<?php echo (APP_NAME); ?>/Common/images/tw.gif" alt="<?php echo ($langs['traditional_chinese']); ?>" />
		<input type="radio" name="js-lang" id="js-zh_tw" class="p" />
		<label for="js-zh_tw"><?php echo ($langs['traditional_chinese']); ?></label>&nbsp;&nbsp;&nbsp;&nbsp;

		<img src="/Public/<?php echo (APP_NAME); ?>/Common/images/us.gif" alt="<?php echo ($langs['americanese']); ?>" />
		<input type="radio" name="js-lang" id="js-en_us" class="p" />
		<label for="js-en_us"><?php echo ($langs['americanese']); ?></label>  
	
		</div>
	</div>
</div>
<div id="content">
<p style="font-size:30px;text-align: center;margin-top:50px;"><?php echo ($langs['loading']); ?></p>
<img id="js-monitor-loading" src='/Public/<?php echo (APP_NAME); ?>/Common/images/loading.gif' style="margin:30px 0 50px 0;"/>
</div>

<?php if(isset($app_list)): ?><input type="hidden" id="app_list" value="<?php echo ($app_list); ?>"/>

<?php else: ?>

<input type="hidden" id="app_list" value=""/><?php endif; ?>

<input type="hidden" id="step" value="<?php echo ($step); ?>"/>

<script type="text/javascript">

<?php if(isset($app_list)): ?>Ajax.call('/xsinst.php?s=/Install/<?php echo ($step); ?>/lang/<?php echo ($lang); ?>/app_list/<?php echo ($app_list); ?>','', callback_api, 'GET', 'TEXT','FLASE');

<?php else: ?>

Ajax.call('/xsinst.php?s=/Install/<?php echo ($step); ?>/lang/<?php echo ($lang); ?>','', callback_api, 'GET', 'TEXT','FLASE');<?php endif; ?>


function callback_api(result)
{
  if(result)
  {
    setInnerHTML('content',result);
    setInputCheckedStatus();

	<?php switch($step): case "welcome": ?>var agree = $("js-agree");
		var submit = $("js-submit");
		submit.disabled=!agree.checked;
		agree.onclick = function () {
			submit.disabled=!this.checked;
		};
		submit.onclick=function () {
			this.form.action = "/xsinst.php?s=/Install/index/lang/" + getAddressLang() +"/step/check";
		};<?php break;?>

	<?php case "check": ?>jQuery('.list tr').bind('mouseover',function()
		{
			jQuery(this).css('backgroundColor','#EEEEEE');
		});

		jQuery('.list tr').bind('mouseout',function()
		{
			jQuery(this).css('backgroundColor','white');
		});

		document.getElementById('formcheck').action='xsinst.php?s=/Install/index/lang/' + getAddressLang() + '/step/setup/app/' + getAppList();<?php break;?>

	<?php case "setup": ?>var f = $("js-setting");

		f.setAttribute("action", "javascript:install();void 0;");

		f["js-db-name"].onblur = function () {
			var list = getDbList();
			for (var i = 0; i < list.length; i++) {
				if (f["js-db-name"].value === list[i]) {
					document.getElementById("checkresult").innerHTML = $_LANG["db_exists"]
					//var answer = confirm($_LANG["db_exists"]);
					//if (answer === false) {
					//	f["js-db-name"].value = "";
					//}
				}
			}
		}
		f["js-admin-password"].onblur = function  () {
			var password = f['js-admin-password'].value;
			var confirm_password = f['js-admin-password2'].value;

			/**
			* 取消格式和长度检测
			if ( !(password.length >= 6 && /[0-9]+/.test(password) && /[a-zA-Z]+/.test(password)) )
			{
				$("js-install-at-once").setAttribute("disabled", "true");
				if (!(password.length >= 6)){
					$("js-admin-password-result").innerHTML="<span class='comment'><img src='/Install/Common/images//no.gif'>"+$_LANG["password_short"]+"</span>";
				}
				else
				{
					$("js-admin-password-result").innerHTML="<span class='comment'><img src='/Install/Common/images/no.gif'>"+$_LANG["password_invaild"]+"</span>";
				}
			}
			*/
			if( password.length == 0 )
			{
				$("js-install-at-once").setAttribute("disabled", "true");
				$("js-admin-password-result").innerHTML="<span class='comment'><img src='/Public/Install/Common/images/no.gif'>"+$_LANG["password_short"]+"</span>";
			}
			else
			{
			
				$("js-admin-password-result").innerHTML="<img src='/Public/Install/Common/images/yes.gif'>";
				if (password==confirm_password)
				{
					$("js-install-at-once").removeAttribute("disabled");
					$("js-admin-confirmpassword-result").innerHTML="<img src='/Public/Install/Common/images/yes.gif'>";
				}
				else
				{
					$("js-install-at-once").setAttribute("disabled", "true");
					if (confirm_password!='')
					{
					$("js-admin-confirmpassword-result").innerHTML="<span class='comment'><img src='/Public/Install/Common/images/no.gif'>"+$_LANG["password_not_eq"]+"</span>";
					}
				}
			}
		}
		f["js-admin-password2"].onblur = function  () {
			var password = f['js-admin-password'].value;
			var confirm_password = f['js-admin-password2'].value;
			/**
			* 取消格式和长度检测
			if (!(confirm_password.length >= 6 && /[0-9]+/.test(confirm_password) && /[a-zA-Z]+/.test(confirm_password) && password==confirm_password))
			{
			  $("js-install-at-once").setAttribute("disabled", "true");
				
			  if (!(confirm_password.length >= 6)){
						$("js-admin-confirmpassword-result").innerHTML="<span class='comment'><img src='/Install/Common/images/no.gif'>"+$_LANG["password_short"]+"</span>";
			  }
			  else
			  {
						if (password==confirm_password){
							$("js-admin-confirmpassword-result").innerHTML="<span class='comment'><img src='/Install/Common/images/no.gif'>"+$_LANG["password_invaild"]+"</span>";
						}
						else
						{
							$("js-admin-confirmpassword-result").innerHTML="<span class='comment'><img src='/Install/Common/images/no.gif'>"+$_LANG["password_not_eq"]+"</span>";
						}
			  }
			}
			*/
			if( confirm_password == 0 || password != confirm_password )
			{
				$("js-admin-confirmpassword-result").innerHTML="<span class='comment'><img src='/Public/Install/Common/images/no.gif'>"+$_LANG["password_not_eq"]+"</span>";
			}
			else
			{
			
				$("js-install-at-once").removeAttribute("disabled");
				$("js-admin-confirmpassword-result").innerHTML="<img src='/Public/Install/Common/images/yes.gif'>";
			}
		}
		f["js-admin-password"].onkeyup = function () {
		  var pwd = f['js-admin-password'].value;
		  var Mcolor = "#FFF",Lcolor = "#FFF",Hcolor = "#FFF";
		  var m=0;

		  var Modes = 0;
		  for (i=0; i<pwd.length; i++)
		  {
			var charType = 0;
			var t = pwd.charCodeAt(i);
			if (t>=48 && t <=57)
			{
			  charType = 1;
			}
			else if (t>=65 && t <=90)
			{
			  charType = 2;
			}
			else if (t>=97 && t <=122)
			  charType = 4;
			else
			  charType = 4;
			Modes |= charType;
		  }

		  for (i=0;i<4;i++)
		  {
			if (Modes & 1) m++;
			  Modes>>>=1;
		  }

		  if (pwd.length<=4)
		  {
			m = 1;
		  }

		  switch(m)
		  {
			case 1 :
			  Lcolor = "2px solid red";
			  Mcolor = Hcolor = "2px solid #DADADA";
			break;
			case 2 :
			  Mcolor = "2px solid #f90";
			  Lcolor = Hcolor = "2px solid #DADADA";
			break;
			case 3 :
			  Hcolor = "2px solid #3c0";
			  Lcolor = Mcolor = "2px solid #DADADA";
			break;
			case 4 :
			  Hcolor = "2px solid #3c0";
			  Lcolor = Mcolor = "2px solid #DADADA";
			break;
			default :
			  Hcolor = Mcolor = Lcolor = "";
			break;
		  }
		  if (document.getElementById("pwd_lower"))
		  {
			document.getElementById("pwd_lower").style.borderBottom  = Lcolor;
			document.getElementById("pwd_middle").style.borderBottom = Mcolor;
			document.getElementById("pwd_high").style.borderBottom   = Hcolor;
		  }
		}
		f["js-go"].onclick = displayDbList;

		f["js-monitor-close"].onclick = function () {
			$("js-monitor").style.display = "none";
			unlockSpecInputs();
		};

		var detail = $("js-monitor-view-detail")
		detail.innerHTML = $_LANG["display_detail"];
		detail.onclick = function () {
			var mn = $("js-monitor-notice");
			if (mn.style.display === "block") {
				mn.style.display = "none"
				this.innerHTML = $_LANG["display_detail"];
			} else {
				mn.style.display = "block"
				this.innerHTML = $_LANG["hide_detail"];
			}
		};

		notice = $("js-notice");
		var d = new Draggable();
		d.bindDragNode("js-monitor", "js-monitor-title");


		$("js-pre-step").onclick = function () {
			location.href = "/xsinst.php?s=/Install/index/lang/" + getAddressLang() + '/step/check/app/' + getAppList();
		};<?php break; endswitch;?>



  }
}
</script>
</body>
</html>