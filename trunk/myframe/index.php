<?php
header('Content-Type: text/html; charset=utf-8');
$page_start_time = microtime(true);

require './init.php';

$domain = $_SERVER["HTTP_HOST"];
if('2510.cn' == $domain){
    header('HTTP/1.1 301 Moved Permanently');
    header('Location:'.URL);
    exit;
}

$config_pool_name = $config_appname  =  $config_cp_url = '';

execute_ctl(cls_request::$forms['ct'], cls_request::$forms['ac']);


?>