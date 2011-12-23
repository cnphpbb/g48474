<?php
header('Content-Type: text/html; charset=utf-8');
$page_start_time = microtime(true);

require './init.php';

$config_pool_name = $config_appname  =  $config_cp_url = '';

execute_ctl(cls_request::$forms['ct'], cls_request::$forms['ac']);


?>