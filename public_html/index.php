<?php    //���ؿ������ļ�
ini_set('display_errors','On');
require '../function.php';
//������Ŀ��Ϣ
define('APP_NAME', 'DmsAdmin');
define('APP_PATH', '../DmsAdmin/');
//����DEBUGģʽ
$debugstate = require '../Admin/Conf/debug.php';
define('APP_DEBUG'    , $debugstate['APP_DEBUG']);
//���ƹ�ע�ᴦ��
if($_GET && key($_GET)!=='s')
{
	$_GET['s']='/User/Saleweb/usereg/rec/'.key($_GET);
}
//����ҳ������ת
if(!$_GET)
{
	$_GET['s']='/User/Public/login';
}
require '../ThinkPHP/ThinkPHP.php';
?>