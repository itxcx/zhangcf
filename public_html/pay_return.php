<?php
require './function.php';
ini_set('display_errors','On');	
$_GET['s']='/Payment/receive/';
define('APP_NAME', 'Admin');
define('APP_PATH', './Admin/');
define('APP_DEBUG', true);
require './ThinkPHP/ThinkPHP.php';
?>