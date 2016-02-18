<?php if (!defined('THINK_PATH')) exit();?><form id="js-setting">

<table border="0" cellpadding="0" cellspacing="0" style="margin:0 auto;">
<tr>
<td height="49"><img src="/Public/<?php echo (APP_NAME); ?>/Common/images/Step-step3-<?php echo ($lang); ?>.gif" /></td>
<td></td>
</tr>
<tr>
<td valign="top">
<div id="wrapper">





  <h3><?php echo ($langs['db_account']); ?></h3>

<table width="450" class="list">

<tr>
    <td width="90" align="left"><?php echo ($langs['db_host']); ?></td>
    <td align="left"><input type="text" name="js-db-host"  value="<?php echo ($oldconfig["DB_HOST"]); ?>" /></td>
</tr>
<tr>
    <td width="90" align="left"><?php echo ($langs['db_port']); ?></td>
    <td align="left"><input type="text" name="js-db-port"  value="<?php echo ($oldconfig["DB_PORT"]); ?>" /></td>
</tr>
<tr>
    <td width="90" align="left"><?php echo ($langs['db_user']); ?></td>
    <td align="left"><input type="text" name="js-db-user"  value="<?php echo ($oldconfig["DB_USER"]); ?>" /></td>
</tr>
<tr>
    <td width="90" align="left"><?php echo ($langs['db_pass']); ?></td>
    <td align="left"><input type="password" name="js-db-pass"  value="<?php echo ($oldconfig["DB_PWD"]); ?>" /></td>
</tr>
<tr>
    <td width="90" align="left"><?php echo ($langs['db_name']); ?></td>
    <td align="left"><input type="text" name="js-db-name"  value="<?php echo ($oldconfig["DB_NAME"]); ?>"/>
        <select name="js-db-list">
            <option><?php echo ($langs['db_list']); ?></option>
        </select>
        <input type="button" name="js-go" class="button" value="<?php echo ($langs['go']); ?>" />
   </td>
</tr>
<tr>
    <td width="90" align="left">&nbsp;</td>
    <td align="left">
		<span style="color:red" name="checkresult" id='checkresult'></span>
   </td>
</tr>
</table>


<div id="js-monitor" style="display:none;text-align:left;position:absolute;top:45%;left:35%;width:300px;z-index:1000;border:1px solid #000;">
    <h3 id="js-monitor-title"><?php echo ($langs['monitor_title']); ?></h3>
    <div style="background:#fff;padding-bottom:20px;">
        <img id="js-monitor-loading" src='/Public/<?php echo (APP_NAME); ?>/Common/images/loading.gif' /><br /><br />
        <strong id="js-monitor-wait-please" style='color:blue;width:65%;float:left;margin-left:3px;'></strong>
        <span id="js-monitor-view-detail" style="color:gray;cursor:pointer;;float:right;margin-right:3px;"></span>
    </div>
    <div id="js-monitor-notice" name="js-monitor-notice" style="display:block;">
        <div id="js-notice" style="background:#fff;margin:0px;padding:5px 0 0 3px;height:100%;font:12px  Arial, Helvetica, sans-serif;line-height:130%; border-color: #BBDDE5 -moz-use-text-color #BBDDE5 #BBDDE5;border-style: dashed;border-width: 1px 0 0 0;"></div>
        <a id="js-bottom"></a>
    </div>
    <img id="js-monitor-close" src='/Public/<?php echo (APP_NAME); ?>/Common/images/close.gif' style="position:absolute;top:10px;right:10px;cursor:pointer;" />
</div>

<h3><?php echo ($langs['admin_account']); ?></h3>
<table width="450" class="list">
<tr>
    <td width="90" align="left"><?php echo ($langs['admin_name']); ?></td>
    <td align="left"><input type="text" id="super_admin" name="js-admin-name"  value="admin" /></td>
</tr>
<tr>
    <td width="90" align="left"><?php echo ($langs['admin_password']); ?></td>
    <td align="left"><input type="password" name="js-admin-password"  value="admin" /><span id="js-admin-password-result"></span></td>
</tr>
<tr>
    <td width="90" align="left"><?php echo ($langs['password_intensity']); ?></td>
    <td align="left"><table width="132" cellspacing="0" cellpadding="1" border="0">
              <tbody><tr align="center">
                <td width="33%" id="pwd_lower" style="border-bottom: 2px solid red;"><?php echo ($langs['pwd_lower']); ?></td>
                <td width="33%" id="pwd_middle" style="border-bottom: 2px solid rgb(218, 218, 218);"><?php echo ($langs['pwd_middle']); ?></td>
                <td width="33%" id="pwd_high" style="border-bottom: 2px solid rgb(218, 218, 218);"><?php echo ($langs['pwd_high']); ?></td>
              </tr>
            </tbody></table></td>
</tr>
<tr>
    <td width="90" align="left"><?php echo ($langs['admin_password2']); ?></td>
    <td align="left"><input type="password" name="js-admin-password2"  value="admin" /><span id="js-admin-confirmpassword-result"></span></td>
</tr>
<tr>
    <td width="90" align="left"><?php echo ($langs['admin_email']); ?></td>
    <td align="left"><input type="text" name="js-admin-email"  value="" /></td>
</tr>
</table>

<h3><?php echo ($langs['mix_options']); ?></h3>
<table width="450" class="list">
<tr>
    <td width="90" align="left"><?php echo ($langs['set_timezone']); ?></td>
    <td align="left">
     <select name="js-timezones">
            <option value="UTC" selected="true"><?php echo ($langs['timezone']['UTC']); ?></option>
            <option value="PRC" ><?php echo ($langs['timezone']['PRC']); ?></option>
            <option value="Asia/Shanghai" selected="true"><?php echo ($langs['timezone']['Asia/Shanghai']); ?></option>
            <option value="Asia/Taipei" ><?php echo ($langs['timezone']['Asia/Taipei']); ?></option>
            <option value="Asia/Chongqing" ><?php echo ($langs['timezone']['Asia/Chongqing']); ?></option>
            <option value="Asia/Harbin" ><?php echo ($langs['timezone']['Asia/Harbin']); ?></option>
            <option value="Asia/Urumqi" ><?php echo ($langs['timezone']['Asia/Urumqi']); ?></option>
            <option value="Asia/Hong_Kong" ><?php echo ($langs['timezone']['Asia/Hong_Kong']); ?></option>
            <option value="Asia/Macau" ><?php echo ($langs['timezone']['Asia/Macau']); ?></option>
            <option value="Asia/Singapore" ><?php echo ($langs['timezone']['Asia/Singapore']); ?></option>
            <option value="Asia/Seoul" ><?php echo ($langs['timezone']['Asia/Seoul']); ?></option>
            <option value="Asia/Tokyo" ><?php echo ($langs['timezone']['Asia/Tokyo']); ?></option>
            <option value="Europe/Berlin" ><?php echo ($langs['timezone']['Europe/Berlin']); ?></option>
            <option value="Europe/Dublin" ><?php echo ($langs['timezone']['Europe/Dublin']); ?></option>
            <option value="Europe/Paris" ><?php echo ($langs['timezone']['Europe/Paris']); ?></option>
   </select>
    </td>
</tr>
<!--
<tr>
    <td width="90" align="left"><?php echo ($langs['disable_captcha']); ?></td>
    <td align="left"><input type="checkbox" class="p" name="js-disable-captcha"  /> <span class="comment"> (<?php echo ($langs['captcha_notice']); ?>)</span></td>
</tr>-->
</table>



</div>
</td>

</tr>



<tr>
<td align="center" style="padding-top:10px;">
	<?php echo ($langs['select_setup_app']); ?>:
	<?php if(is_array($app_list)): foreach($app_list as $key=>$vo): ?><input type="checkbox" name="app[]" id="app_<?php echo ($vo["app"]); ?>" value="<?php echo ($vo["app"]); if(isset($vo['group'])): ?>:<?php echo ($vo["group"]); endif; ?>" <?php if($vo['checked'] == true): ?>checked<?php endif; ?>  /> <label for="app_<?php echo ($vo["app"]); ?>"><?php echo ($key); ?></label><?php endforeach; endif; ?>
</td>
</tr>
<tr>
  <td>
  <div id="install-btn">
  <input type="button" id="js-pre-step" class="button" value="<?php echo ($langs['prev_step']); echo ($langs['check_system_environment']); ?>" /> 
  <input id="js-install-at-once" type="submit" class="button" value="<?php echo ($langs['install_at_once']); ?>" />
  </div>
  </td><td></td>
</tr>
</table>
</form>