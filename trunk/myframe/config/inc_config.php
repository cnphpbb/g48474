<?php
//基本常量
define('PATH_MODEL', './model');
define('PATH_CONTROL', './control');
define('PATH_ROOT', substr(dirname(__FILE__), 0, -7) );
define('PATH_LIBRARY', PATH_ROOT . '/library');
define('PATH_CONFIG', PATH_ROOT . '/config');
define('PATH_API', PATH_ROOT . '/api');
define('PATH_DATA', PATH_ROOT . '/data');
define('PATH_DM_CONFIG', PATH_DATA . '/dm_config');
define('PATH_UP_TMP',PATH_ROOT.'/temp'); #上传临时文件
define('PATH_TXTDB_DIR',PATH_DATA.'/txt_db'); 

define('COOKIE_DOMAIN', ''); //正式环境中如果要考虑二级域名问题的应该用 .my.com
define('URL', 'http://localhost');
define('DEBUG_LEVEL', true);

//调试选项（指定某些IP允许开启调试）
//数组格式为 array('ip1', 'ip2'...)
$GLOBALS['config']['safe_client_ip'] = array("127.0.0.1","192.168.100.129");

//-------------------------------------------
//memcache配置
$GLOBALS['config']['memcache'] = array(
    'is_mc_enable'  => false,
    'mc_cache_time' => 300,
    'mc' => array(
        'default' => 'memcache://127.0.0.1:11211/default10-21',
    )
);

//发送邮件配置
$GLOBALS['config']['send_account']["host"] = "";
$GLOBALS['config']['send_account']['port'] = 25;
$GLOBALS['config']['send_account']['user'] = "";
$GLOBALS['config']['send_account']['passwd'] = "";
$GLOBALS['config']['send_account']['auth'] = true;
$GLOBALS['config']['send_account']['secure'] = "ssl";
$GLOBALS['config']['send_account']['from_title'] = "";
$GLOBALS['config']['send_account']['debug'] = false;

//接口配置
define( "WB_AKEY" , '' );
define( "WB_SKEY" , '' );
define( "WB_CALLBACK_URL" , URL.'/?ct=openid&ac=weibo' );

//MySql配置
$GLOBALS['config']['db_host']['master'] = 'localhost:3306';
$GLOBALS['config']['db_host']['slave'][] = 'localhost:3306';
$GLOBALS['config']['db_user'] = 'db_test';
$GLOBALS['config']['db_pass'] = 'db_test';
$GLOBALS['config']['db_name'] = 'db_test';
$GLOBALS['config']['db_charset'] = 'utf-8';

// url重写是否开启
// 此项需要修改 PATH_DATA/rewrite.ini
$GLOBALS['config']['use_rewrite'] = false;
//指示替换网址是在编译前还是输出前，0--前者性能好，1--后者替换更彻底
$GLOBALS['config']['rewrite_rptype'] = 1;

//#-------------------------
//# 非固定配置
//#-------------------------
$GLOBALS['config']['cookie_pwd'] = 'h5ChwPpGXKd4mCsY';
$GLOBALS['config']['upload_dir'] = '/static/uploads';


//需要写入权限的目录
$GLOBALS['config']['write_ables'][] = PATH_DATA;
$GLOBALS['config']['write_ables'][] = PATH_DATA.'/admin';
$GLOBALS['config']['write_ables'][] = PATH_DATA.'/backup';
$GLOBALS['config']['write_ables'][] = PATH_DATA.'/cache';
$GLOBALS['config']['write_ables'][] = PATH_DATA.'/log';
$GLOBALS['config']['write_ables'][] = PATH_DATA.'/lurdtmp';
$GLOBALS['config']['write_ables'][] = PATH_DATA.'/txt_db';
$GLOBALS['config']['write_ables'][] = PATH_UP_TMP;
$GLOBALS['config']['write_ables'][] = PATH_CONFIG;
$GLOBALS['config']['write_ables'][] = PATH_ROOT.$GLOBALS['config']['upload_dir'];
$GLOBALS['config']['write_ables'][] = PATH_ROOT.'/templates/cache';
$GLOBALS['config']['write_ables'][] = PATH_ROOT.'/templates/compile';


?>