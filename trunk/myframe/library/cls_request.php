<?php
/**
 * 处理外部请求变量的类
 *
 * 禁止此文件以外的文件出现 $_POST、$_GET、$_FILES变量及eval函数(用 cls_request::myeval )
 * 以便于对主要黑客攻击进行防范
 *
 * @author kevinG <cnphpbb@hotmail.com>
 * @version 2011-12-21
 */
//简化 cls_request::item() 函数
function request($key, $df='')
{
    return cls_request::item($key, $df);
}
class cls_request
{
    //用户的cookie
    public static $cookies = array();

    //把GET、POST的变量合并一块，相当于 _REQUEST
    public static $forms = array();

    //_GET 变量
    public static $gets = array();

    //_POST 变量
    public static $posts = array();

    //用户的请求模式 GET 或 POST
    public static $request_type = 'GET';

    //文件变量
    public static $files = array();

    //url_rewrite
    public static $url_rewrite = true;

    //严禁保存的文件名
    public static $filter_filename = '/\.(php|pl|sh|js)$/i';

   /**
    * 初始化用户请求
    * 对于 post、get 的数据，会转到 selfforms 数组， 并删除原来数组
    * 对于 cookie 的数据，会转到 cookies 数组，但不删除原来数组
    */
    public static function init()
    {
        //处理post、get
        $formarr = array('p' => $_POST, 'g' => $_GET);
        foreach($formarr as $_k => $_r)
        {
            if( count($_r) > 0 )
            {
                foreach($_r as $k=>$v)
                {
                    if( preg_match('/^config/i', $k) )
	                {
                        continue;
	                }
                    self::$forms[$k] = $v;
                    if( $_k=='p' )
                    {
                        self::$posts[$k] = $v;
                    } else {
                        self::$gets[$k] = $v;
                    }
                }
            }
        }
        unset($_POST);
        unset($_GET);
        unset($_REQUEST);

        //处理url_rewrite
        //请求用 ?q1--v1--q2--v2--q3--v3...这种格式，第一个参数必须为 ct 或 ac
        //如：xxx.com/?ct--union--ac--login/ 结尾的 / 是可有可无的
        //对于重复出现的q值，会被转换为数组，如：q--1--q--2，这里会直接把q转为数组因此不要写成 q[]--1--q[]--2
        //当get string存在时表示 ?ct--index--ac--test 这种模式
        //否则表示 index.php/ct/index/ac/test 这种模式
        if( self::$url_rewrite )
        {
            $gstr = empty($_SERVER['QUERY_STRING']) ? '' : $_SERVER['QUERY_STRING'];
            if( empty($gstr) )
            {
                $gstr = empty($_SERVER['PATH_INFO']) ? '' : $_SERVER['PATH_INFO'];
            }
            if( !empty($gstr) && $gstr[0]=='/' )
            {
                $exptag = '/';
            }
            else
            {
                $exptag = '--';
            }
            if( preg_match('/^(ct-|\/ct)/', $gstr) || preg_match('/^(ac-|\/ac)/', $gstr) )
            {
                $gstr = preg_replace("/\/$/", '', $gstr);
                $gstr = preg_replace("/^\//", '', $gstr);
                $gstrs = explode($exptag, $gstr);
                $glen = count($gstrs);
                for($i=0; $i < $glen; $i++)
                {
                    if( strlen($gstrs[$i]) > 0 && strlen($gstrs[$i]) < 33 )
                    {
                        $v = isset( $gstrs[$i+1] ) ? $gstrs[$i+1] : '';
                        if( isset(self::$forms[ $gstrs[$i] ]) )
                        {
                            if( is_array(self::$forms[ $gstrs[$i] ]) )
                            {
                                self::$forms[ $gstrs[$i] ][] = $v;
                            }
                            else
                            {
                                $narr[] = self::$forms[ $gstrs[$i] ];
                                self::$forms[ $gstrs[$i] ] = $narr;
                                self::$forms[ $gstrs[$i] ][] = $v;
                            }
                        }
                        else
                        {
                            self::$forms[ $gstrs[$i] ] = $v;
                        }
                    }
                    $i++;
                }
            }
        }
        //默认ac和ct
        self::$forms['ct'] = isset(self::$forms['ct']) ? self::$forms['ct'] : 'index';
        self::$forms['ac'] = isset(self::$forms['ac']) ? self::$forms['ac'] : 'index';

        //处理cookie
        if( count($_COOKIE) > 0 )
        {
            foreach($_COOKIE as $k=>$v)
            {
                if( preg_match('/^config/i', $k) )
	            {
                    continue;
	            }
                self::$cookies[$k] = $v;
            }
        }
        //unset($_POST, $_GET);

        //上传的文件处理
        if( isset($_FILES) && count($_FILES) > 0 )
        {
            self::filter_files($_FILES);
        }

        //global变量
        //self::$forms['_global'] = $GLOBALS;
    }

   /**
    * 把 eval 重命名为 myeval
    */
    public static function myeval( $phpcode )
    {
        return eval( $phpcode );
    }

   /**
    * 获得指定表单值
    */
    public static function item( $formname, $defaultvalue = '' )
    {
        return isset(self::$forms[$formname]) ? self::$forms[$formname] :  $defaultvalue;
    }

   /**
    * 获得指定临时文件名值
    */
    public static function upfile( $formname, $defaultvalue = '' )
    {
        return isset(self::$files[$formname]['tmp_name']) ? self::$files[$formname]['tmp_name'] :  $defaultvalue;
    }

   /**
    * 过滤文件相关
    */
    public static function filter_files( &$files )
    {
        foreach($files as $k=>$v)
        {
            self::$files[$k] = $v;
        }
        unset($_FILES);
    }

   /**
    * 移动上传的文件
    */
    public static function move_upload_file( $formname, $filename, $filetype = '' )
    {
        if( self::is_upload_file( $formname ) )
        {
            if( preg_match(self::$filter_filename, $filename) )
            {
                return false;
            }
            else
            {
                return move_uploaded_file(self::$files[$formname]['tmp_name'], $filename);
            }
        }
    }

   /**
    * 获得文件的扩展名
    */
    public static function get_shortname( $formname )
    {
        $filetype = strtolower(isset(self::$files[$formname]['type']) ? self::$files[$formname]['type'] : '');
        $shortname = '';
        switch($filetype)
        {
            case 'image/jpeg':
                $shortname = 'jpg';
                break;
            case 'image/pjpeg':
                $shortname = 'jpg';
                break;
            case 'image/gif':
                $shortname = 'gif';
                break;
            case 'image/png':
                $shortname = 'png';
                break;
            case 'image/xpng':
                $shortname = 'png';
                break;
            case 'image/wbmp':
                $shortname = 'bmp';
                break;
            default:
                $filename = isset(self::$files[$formname]['name']) ? self::$files[$formname]['name'] : '';
                if( preg_match("/\./", $filename) )
                {
                    $fs = explode('.', $filename);
                    $shortname = strtolower($fs[ count($fs)-1 ]);
                }
                break;
        }
        return $shortname;
    }

   /**
    * 获得指定文件表单的文件详细信息
    */
    public static function get_file_info( $formname, $item = '' )
    {
        if( !isset( self::$files[$formname]['tmp_name'] ) )
        {
            return false;
        }
        else
        {
            if($item=='')
            {
                return self::$files[$formname];
            }
            else
            {
                return (isset(self::$files[$formname][$item]) ? self::$files[$formname][$item] : '');
            }
        }
    }

   /**
    * 判断是否存在上传的文件
    */
    public static function is_upload_file( $formname )
    {
        if( !isset( self::$files[$formname]['tmp_name'] ) )
        {
            return false;
        }
        else
        {
            return is_uploaded_file( self::$files[$formname]['tmp_name'] );
        }
    }

    /**
     * 检查文件后缀是否为指定值
     *
     * @param  string  $subfix
     * @return boolean
     */
    public static function check_subfix($formname, $subfix = 'csv')
    {
        if( self::get_shortname( $formname ) != $subfix)
        {
            return false;
        }
        return true;
    }

}
?>