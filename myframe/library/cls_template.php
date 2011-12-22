<?php
//严禁直接访问类文件
if( !defined('PATH_ROOT') )
{
    exit('Request Error!');
}
/**
 * Smarty 模板引擎
 *
 * @author kevinG <cnphpbb@hotmail.com>
 * @version 2011-12-21
 */
require_once PATH_LIBRARY . '/smarty/Smarty.class.php'; //Smarty version 3.1.7
class cls_template
{
    protected static $instance = null;
    public static $appname = '';
    public static $debug_error = '';
    /**
     * Smarty
     *
     * @return resource
     */
    public static function init ()
    {
        global $config_appname;
        self::$appname = empty(self::$appname) ? $config_appname : self::$appname;
        if (self::$instance === null)
        {
            self::$instance = new Smarty();
            spl_autoload_register("__autoload");
            self::$instance->setTemplateDir(path_exists(PATH_ROOT . '/templates/template/'));
            self::$instance->setCompileDir(path_exists(PATH_ROOT . '/templates/compile/'));
            self::$instance->setCacheDir(path_exists(PATH_ROOT . '/templates/cache/'));
            self::$instance->left_delimiter = '<{';
            self::$instance->right_delimiter = '}>';
            self::$instance->caching = false;
            self::$instance->cache_lifetime = 120;
            self::$instance->compile_check = true;
            self::$instance->setPluginsDir(path_exists(PATH_LIBRARY . '/smarty_plugins'));
            //self::$instance->load_filter ( 'output', 'gzip' );
            self::config();
        }
        return self::$instance;
    }

    protected static function config ()
    {
        $instance = self::init();
        $instance->assign('URL_STATIC', URL.'/static');
        $instance->assign('URL', URL);
    }

    public static function assign ($tpl_var, $value)
    {
        $instance = self::init();
        $instance->assign($tpl_var, $value);
    }

    public static function display ($tpl, $is_debug_mt=true, $cache_id = null, $compile_id = null, $parent = null)
    {
        $instance = self::init();
        $app_tpldir = empty(self::$appname) ? '' : self::$appname.'/';
        $instance->display($app_tpldir.$tpl,$cache_id, $compile_id, $parent);
        if( $is_debug_mt && PHP_SAPI !== 'cli' )
        {
            debug_hanlde_xhprof();
        }
    }

    public static function fetch($tpl, $cache_id = null, $compile_id = null, $display = false)
    {
        $instance = self::init();
        return $instance->fetch($tpl, $cache_id, $compile_id, $display);
    }
    /**
     * 增加模板中使用的CSS
     */
    public static function addCss($mixed)
    {
        $URL_STATIC = URL.'/static';
        $css_code = '<link rel="stylesheet" type="text/css" href="%css_path%" />';
        $css_path = $URL_STATIC."/css/";
        $add_css = "";
        foreach ($mixed as $value)
        {
            if(!empty($add_css)){
                $add_css .= "\n";
            }
            $css_path_all = $css_path.$value;
            $add_css .= str_replace("%css_path%", $css_path_all, $css_code);
        }
        self::assign("add_css", $add_css);
    }

    /**
     * 增加模板中使用的JAVASCRIPT
     */
    public static function addJs($mixed)
    {
        $URL_STATIC = URL.'/static';
        $js_code = '<script type="text/javascript" src="%js_path%"></script>';
        $js_path = $URL_STATIC."/js/";
        $add_js = "";
        if(is_array($mixed)){
            if(count($mixed) > 0){
                foreach ($mixed as $value)
                {
                    if(!empty($add_js)){
                        $add_js .= "\n";
                    }
                    $js_path_all = $js_path.$value;
                    $add_js .= str_replace("%js_path%", $js_path_all, $js_code);
                }
            }
        }else{
            $js_path_all = empty($mixed) ? "" :  $js_path.$mixed;
            if(!empty($js_path_all)){
                $add_js = str_replace("%js_path%", $js_path_all, $js_code);
            }
        }
        self::assign("add_javascript", $add_js);
    }
}
?>