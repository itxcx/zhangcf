<?php
//resubmit
if( isset($_REQUEST['location_url']) && $_REQUEST['location_url']!='' )
{
	$location_url	= base64_decode($_REQUEST['location_url']);
	$post_data		= array();
	$fields			= $_REQUEST;

?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html>
<head>
<title>message</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
html, body{margin:0; padding:0; border:0 none;font:14px Tahoma,Verdana;line-height:150%;background:white}
a{text-decoration:none; color:#174B73; border-bottom:1px dashed gray}
a:hover{color:#F60; border-bottom:1px dashed gray}
div.message{margin:10% auto 0px auto;clear:both;padding:5px;border:0px solid silver; text-align:center; width:45%}
span.wait{color:blue;font-weight:bold}
span.error{color:red;font-weight:bold}
span.success{color:blue;font-weight:bold}
div.msg{margin:20px 0px}
</style>
</head>
<body>
<div class="message">
	<div class="msg">
	<span class="success"></span>
	</div>
	<div style="display:none">
		<form action="<?=$location_url?>" method="post" id="frm1">
		<?php foreach( $fields as $field=>$value ){ ?>
		<input type="hidden" name="<?=$field?>" value="<?=$value?>" />
		<?php } ?>
		</form>
	</div>
</div>
</body>
</html>
<script language="javascript">
document.getElementById("frm1").submit();
</script>
<?php
}
?>