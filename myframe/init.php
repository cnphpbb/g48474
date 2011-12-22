<?php
// 严格开发模式
error_reporting( E_ALL );

//强制要求对gpc变量进行转义处理
if ( !ini_get('magic_quotes_gpc') )
{
    exit('php.ini magic_quotes_gpc must is On! ');
}

//开启register_globals会有诸多不安全可能性，因此强制要求关闭register_globals
if ( ini_get('register_globals') )
{
    exit('php.ini register_globals must is Off! ');
}

//禁止 session.auto_start
if ( ini_get('session.auto_start') )
{
    exit('php.ini session.auto_start must is off ! ');
}

//外部请求程序处理(路由)
require dirname(__FILE__).'/library/cls_request.php';
cls_request::init();

// 系统配置
require dirname(__FILE__).'/config/inc_config.php';

session_save_path(PATH_DATA.'/session');

// 错误控制
$GLOBALS['debug_safe'] = false;  //这个变量不要修改，它是允许指定IP显示错误使用的
$cli_ip = empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_CLIENT_IP'];
if( in_array($cli_ip, $GLOBALS['config']['safe_client_ip']) )
{
    $GLOBALS['debug_safe'] = true;
    ini_set('display_errors', 'On');
}
else if( DEBUG_LEVEL !== true )
{
    ini_set('display_errors', 'Off');
}
require PATH_LIBRARY.'/lib_debug.php';
set_exception_handler('debug_exception_handler');
set_error_handler('debug_error_handler', E_ALL);
register_shutdown_function('debug_error_show');

//设置时区
date_default_timezone_set('Asia/Shanghai');
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');
//加载函数库
require PATH_LIBRARY . '/lib_common.php';

//自动加载类库处理
//系统自动加载 “/library” 和 “应用目录/model” 目录的类，其它地方的类需要手工require
function __autoload($classname)
{
        $classname = preg_replace("/[^0-9a-z_]/i", '', $classname);
        if( class_exists ( $classname ) )
        {
            return true;
        }
        $classfile = $classname.'.php';
        try
        {
            if ( is_file ( PATH_LIBRARY.'/'.$classfile ) )
            {
                 require PATH_LIBRARY.'/'.$classfile;
            }
            else if( is_file ( PATH_MODEL.'/'.$classfile ) )
            {
                 require PATH_MODEL.'/'.$classfile;
            }
            else
            {
                  if (DEBUG_LEVEL === true)
                  {
                       throw new Exception ( 'Error: Cannot find the '.$classname );
                  }
                  else
                  {
                       header ( "location:/404.html" );
                       die ();
                  }
            }
        }
        catch ( Exception $e )
        {
             trigger_error("__autoload class {$classname} not found!");
             exit ();
        }
}

//检测目录的写入权限
if( !file_exists(PATH_DATA.'/has_test_writeable.txt') && is_array($GLOBALS['config']['write_ables']) )
{
    $errdir = '';
    foreach($GLOBALS['config']['write_ables'] as $d)
    {

        if( test_write_able($d) !== true )
        {
            $errdir .= $d." <br />\n";
        }
    }
    if( $errdir != '')
    {
        header('Content-Type: text/html; charset=utf-8');
        echo "以下目录需写入权限，但它们不支持写入，并且程序本身无法自动完成更改操作，请联系相关负责人手动处理：<hr />";
        echo $errdir;
        exit();
    }
    else
    {
        file_put_contents( PATH_DATA.'/has_test_writeable.txt', 'ok' );
    }
}


?>