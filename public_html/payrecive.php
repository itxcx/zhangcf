<?php
ini_set('display_errors','on');
require 'function.php';
define('APP_NAME', 'Admin');
define('APP_PATH', './Admin/');
define('APP_DEBUG', true);
$_GET['s']="Payment/receive";
require './ThinkPHP/ThinkPHP.php'; ?>