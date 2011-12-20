<?php
/********************************
 * 为了保持框架文件的整洁性，所有项目自身私有的公共函数写在此文件
*********************************/

/**
 * 创建插入数据的SQL语句
 * @param  array  $fields_array   : 字段/值数组，例如: $fields_arr = array("Username" => "$Username","Password" => "$Password");
 * @param  string $table_name    : 表名
 * @return string
 * @author kevinG <cnphpbb@hotmail.com>
 * @version 2011-11-04
 */
function create_insert_sql($fields_array, $table_name)
{
    $keys = '';
    $vals = '';
    foreach ($fields_array as $key => $val)
    {
        if (!empty($keys) && !empty($vals))
        {
            $keys .= ", ";
            $vals .= ", ";
        }
        $keys .= "`$key`";
        $vals .= "'$val'";
    }
    return "insert into `$table_name` ($keys) values ($vals)";
}

/**
 * 创建修改数据的SQL语句
 * @param array $fields_array 字段/值数组，例如: $fields_arr = array("Username" => "$Username", "Password" => "$Password");
 * @param type  $condition SQL语句条件，例如: `id`='$id' and `uid`='$uid'
 * @return string
 * @author kevinG <cnphpbb@hotmail.com>
 * @version 2011-11-04
 */
function create_update_sql($fields_array, $condition, $table_name)
{
    $sql_str = '';
    foreach ($fields_array as $key=>$val) {
        if (!empty($sql_str)) {
            $sql_str .= ", ";
        }
        $sql_str .= "`$key`='$val'";
    }
    return "update `$table_name` SET $sql_str where $condition";
}


/**
 * 创建SQL搜索子句
 * $search_arr   : SQL搜索数组
 * $par_junk     : and , 将返回 and (...) 形式子句
 *                 or , 将返回 or (...) 形式子句
 *                 空 , 将返回 ... 子句
 * 例 :
 * $search_arr[] = array( "field" => "title", "keyword" => "aa", "condition" => "like", "junk" => "and" );
 * $search_arr[] = array( "field" => "title", "keyword" => "bb", "condition" => "=", "junk" => "or" );
 * $search_arr[] = array( "field" => "title", "keyword" => "cc", "condition" => ">", "junk" => "and" );
 * create_search_sql($search_arr,"and")
 * 将返回 ： and (`title` like '%aa%' or `title`='bb' and `title`>'cc')
 * 注 ：
 * 数组各字段不能为空
 * field         字段名
 * keyword       搜索关键字
 * condition     查询条件            like , = , > , < , <>
 * junk          条件查询关系        and , or
 */
function create_search_sql($search_arr, $par_junk="")
{
    $first = true;
    foreach ($search_arr as $key => $val)
    {
        if (is_array($val))
        {
            $junk = ($val["junk"] != "and" && $val["junk"] != "or") ? "" : $val["junk"];
            $field = $val["field"];
            $condition = $val["condition"];
            $keyword = $val["keyword"];
            if (!empty($field) && !empty($condition) && !empty($keyword))
            {
                if ($first)
                {
                    $junk = "";
                    $first = false;
                }
                else
                {
                    $junk = " $junk";
                }
                switch ($condition)
                { // like , < , > , = , <> ,
                    case "like":
                        $sql_clause .= "$junk `$field` like '%$keyword%'";
                        break;
                    case "<":
                    case ">":
                    case "=":
                    case "<>":
                        $sql_clause .= "$junk `$field`$condition'$keyword'";
                        break;
                }
            }
        }
    }
    if (!empty($sql_clause) && ($par_junk == "and" || $par_junk == "or"))
    {
        $sql_clause = "$par_junk ($sql_clause)";
    }
    return $sql_clause;
}

/**
 * 输出字符替换
 */
function output_replace($str){
        $str = strip_tags($str, "<br>");
//        $str = preg_replace("/(\r\n|\n|\r){1}/", "", $str);
        $str = strtr($str, array("&nbsp;" => " "));
        $str = preg_replace("/<br\s*(\/)?>/", "\n", $str);
        return $str;
}
/**
 * 输入字符替换
 */
function input_replace($str){
     $str = preg_replace("/<br\s*(\/)?>/", "\n", $str);
     $str = strtr($str, array(" " => "&nbsp;"));
     $str = strip_tags($str, "<br>");
     return htmlspecialchars($str, ENT_QUOTES);
}
/**
 * 同 print_r(), 加了<xmp> 标记要输出内容上.
 * @param type $var
 */
function pr($var)
{
    if (!DEBUG_LEVEL)
    {
        echo '<xmp>';
        print_r($var);
        echo '</xmp>';
        exit();
    }
}
/**
 * 把多个变量或数组合并为一个数组
 * @return array
 */
function am()
{
    $r = array();
    $args = func_get_args();
    foreach ($args as $a)
    {
        if (!is_array($a))
        {
            $a = array($a);
        }
        $r = array_merge($r, $a);
    }
    return $r;
}

/**
 * 把调试的数据打印出来
 * 使用方法: debugVar($var0, $var1, ...)
 */
function debugVar()
{
    header('Content-type:text/html;charset=utf-8');
    $r = null;
    $args = func_get_args();
    echo '<xmp>', var_dump($args), '</xmp>';
    exit;
}
/**
 * 记录错误日志
 * @param type $sNewLineText
 * @param type $err_log_filename
 */
function debugLog( $sNewLineText = 'debug' , $err_log_filename="debug")
{
    $sFileName = PATH_DATA."/log/".$err_log_filename."_".date("Ymd").".log";
    error_log( $sNewLineText . "\n", 3, $sFileName );
}

?>