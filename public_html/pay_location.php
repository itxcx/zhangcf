<?php
/*
*名称：支付转发独立模块
*版本：Ver 3.1.40
*修档：2015/08/06
*开发者：0025
*验收人：冯露露
*版权归属：临沂市新商网络技术有限公司
*/
if( isset($_REQUEST['location_url']) && $_REQUEST['location_url']!='' ){
	$location_url	= base64_decode($_REQUEST['location_url']);
	$fields			= $_REQUEST;
	unset($fields['location_url']);
?>
<!doctype html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>在线支付</title>
</head>
<body>
	<div>
		<form action="<?=htmlentities($location_url)?>" method="post" id="frm1">
		<?php foreach( $fields as $field=>$value ){ ?>
		<input type="hidden" name="<?=htmlentities($field)?>" value="<?=htmlentities($value)?>" />
		<?php } ?>
		</form>
	</div>
</body>
</html>
<script language="javascript">
document.getElementById("frm1").submit();
</script>
<?php
}
?>