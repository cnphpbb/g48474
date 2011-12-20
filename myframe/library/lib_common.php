<?php

/* 控制器调用函数 */

function execute_ctl($controller_name, $action = '')
{

    try
    {
        $controller_name = 'ctl_' . preg_replace("/[^0-9a-z_]/i", '', $controller_name);
        $action = preg_replace("/[^0-9a-z_]/i", '', $action);

        $action = empty($action) ? $action = 'index' : $action;

        $path = PATH_CONTROL . '/' . $controller_name . '.php';

        if (file_exists($path))
        {
            require $path;
        }
        else
        {
            throw new Exception("Contrl {$controller_name} is not exists!");
        }

        if (method_exists($controller_name, $action) === true)
        {
            $instance = new $controller_name ( );
            $instance->$action();
        }
        else
        {
            throw new Exception("Method {$action}() is not exists!");
        }
    }
    catch (Exception $e)
    {

        if (DEBUG_LEVEL === true)
        {
            trigger_error("Load Controller false!");
        }
        //生产环境中，找不到控制器的情况不需要记录日志
        else
        {
            header("location:/404.html");
            die();
        }
    }
}

/*
  检测目录是否有写入权限
  由于is_writeable
 */

function test_write_able($d, $c=false)
{
    $tfile = '_write_able.txt';
    $d = preg_replace("/\/$/", '', $d);
    $fp = @fopen($d . '/' . $tfile, 'w');
    if (!$fp)
    {
        if ($c == false)
        {
            @chmod($d, 0777);
            return false;
        }
        else
        {
            return test_write_able($d, true);
        }
    }
    else
    {
        fclose($fp);
        return @unlink($d . '/' . $tfile) ? true : false;
    }
}

/* 路径是否存在? */

function path_exists($path)
{
    $pathinfo = pathinfo($path . '/tmp.txt');
    if (!empty($pathinfo ['dirname']))
    {
        if (file_exists($pathinfo ['dirname']) === false)
        {
            if (mkdir($pathinfo ['dirname'], 0777, true) === false)
            {
                return false;
            }
        }
    }
    return $path;
}

/* 写文件 */

function put_file($file, $content, $flag = 0)
{
    $pathinfo = pathinfo($file);
    if (!empty($pathinfo ['dirname']))
    {
        if (file_exists($pathinfo ['dirname']) === false)
        {
            if (@mkdir($pathinfo ['dirname'], 0777, true) === false)
            {
                return false;
            }
        }
    }
    if ($flag === FILE_APPEND)
    {
        return @file_put_contents($file, $content, FILE_APPEND);
    }
    else
    {
        return @file_put_contents($file, $content, LOCK_EX);
    }
}

/* 读缓存 */

function get_cache($prefix, $key, $is_memcache = true)
{
    global $config;
    $key = md5($key);
    /* 如果启用MC缓存 */
    if ($is_memcache === true && !empty($config['memcache']) && $config['memcache'] ['is_mc_enable'] === true)
    {
        $mc_path = empty($config['memcache'] ['mc'] [substr($key, 0, 1)]) ? $config['memcache'] ['mc'] ['default'] : $config['memcache'] ['mc'] [substr($key, 0, 1)];
        $mc_path = parse_url($mc_path);
        $key = ltrim($mc_path ['path'], '/') . '_' . $prefix . '_' . $key;
        if (empty($GLOBALS ['mc_' . $mc_path ['host']]))
        {
            $GLOBALS ['mc_' . $mc_path ['host']] = new Memcache ( );
            $GLOBALS ['mc_' . $mc_path ['host']]->connect($mc_path ['host'], $mc_path ['port']);
        }
        return $GLOBALS ['mc_' . $mc_path ['host']]->get($key);
    }
    $key = substr($key, 0, 2) . '/' . substr($key, 2, 2) . '/' . substr($key, 4, 2) . '/' . $key;
    $result = @file_get_contents(PATH_DATA . "/cache/$prefix/$key");
    if ($result === false)
    {
        return false;
    }
    $result = @unserialize($result);
    if (empty($result ['timeout']) || $result ['timeout'] < time())
    {
        return false;
    }
    return $result ['data'];
}

/* 写缓存 */

function set_cache($prefix, $key, $value, $timeout = 3600, $is_memcache = true)
{
    global $config;
    $key = md5($key);
    /* 如果启用MC缓存 */
    if (!empty($config['memcache']) && $config['memcache'] ['is_mc_enable'] === true && $is_memcache === true)
    {
        $mc_path = empty($config['memcache'] ['mc'] [substr($key, 0, 1)]) ? $config['memcache'] ['mc'] ['default'] : $config['memcache'] ['mc'] [substr($key, 0, 1)];
        $mc_path = parse_url($mc_path);
        $key = ltrim($mc_path ['path'], '/') . '_' . $prefix . '_' . $key;
        if (empty($GLOBALS ['mc_' . $mc_path ['host']]))
        {
            $GLOBALS ['mc_' . $mc_path ['host']] = new Memcache ( );
            $GLOBALS ['mc_' . $mc_path ['host']]->connect($mc_path ['host'], $mc_path ['port']);
            //设置数据压缩门槛
            //$GLOBALS ['mc_' . $mc_path ['host']]->setCompressThreshold(2048, 0.2);
        }
        $result = $GLOBALS ['mc_' . $mc_path ['host']]->set($key, $value, MEMCACHE_COMPRESSED, $timeout);
        return $result;
    }
    $key = substr($key, 0, 2) . '/' . substr($key, 2, 2) . '/' . substr($key, 4, 2) . '/' . $key;
    $tmp ['data'] = $value;
    $tmp ['timeout'] = time() + (int) $timeout;
    return @put_file(PATH_DATA . "/cache/$prefix/$key", @serialize($tmp));
}

/* 删缓存 */

function del_cache($prefix, $key, $is_memcache = true)
{
    global $config;
    $key = md5($key);
    /* 如果启用MC缓存 */
    if (!empty($config['memcache']) && $config['memcache'] ['is_mc_enable'] === true && $is_memcache === true)
    {
        $mc_path = empty($config['memcache'] ['mc'] [substr($key, 0, 1)]) ? $config['memcache'] ['mc'] ['default'] : $config['memcache'] ['mc'] [substr($key, 0, 1)];
        $mc_path = parse_url($mc_path);
        $key = ltrim($mc_path ['path'], '/') . '_' . $prefix . '_' . $key;
        if (empty($GLOBALS ['mc_' . $mc_path ['host']]))
        {
            $GLOBALS ['mc_' . $mc_path ['host']] = new Memcache ( );
            $GLOBALS ['mc_' . $mc_path ['host']]->connect($mc_path ['host'], $mc_path ['port']);
        }
        return $GLOBALS ['mc_' . $mc_path ['host']]->delete($key);
    }
    $key = substr($key, 0, 2) . '/' . substr($key, 2, 2) . '/' . substr($key, 4, 2) . '/' . $key;
    return @unlink(PATH_DATA . "/cache/$prefix/$key");
}

/* 获得用户的真实 IP 地址 */

function get_client_ip()
{
    static $realip = NULL;
    if ($realip !== NULL)
    {
        return $realip;
    }
    if (isset($_SERVER))
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR2']))
        {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR2']);
            /* 取X-Forwarded-For2中第?个非unknown的有效IP字符? */
            foreach ($arr as $ip)
            {
                $ip = trim($ip);
                if ($ip != 'unknown')
                {
                    $realip = $ip;
                    break;
                }
            }
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            /* 取X-Forwarded-For中第?个非unknown的有效IP字符? */
            foreach ($arr as $ip)
            {
                $ip = trim($ip);
                if ($ip != 'unknown')
                {
                    $realip = $ip;
                    break;
                }
            }
        }
        elseif (isset($_SERVER['HTTP_CLIENT_IP']))
        {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        }
        else
        {
            if (isset($_SERVER['REMOTE_ADDR']))
            {
                $realip = $_SERVER['REMOTE_ADDR'];
            }
            else
            {
                $realip = '0.0.0.0';
            }
        }
    }
    else
    {
        if (getenv('HTTP_X_FORWARDED_FOR2'))
        {
            $realip = getenv('HTTP_X_FORWARDED_FOR2');
        }
        elseif (getenv('HTTP_X_FORWARDED_FOR'))
        {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        }
        elseif (getenv('HTTP_CLIENT_IP'))
        {
            $realip = getenv('HTTP_CLIENT_IP');
        }
        else
        {
            $realip = getenv('REMOTE_ADDR');
        }
    }
    preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
    $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
    return $realip;
}

/* 验证IP */

function validate_ip($ip)
{
    if (!preg_match('/((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]\d)|\d)(\.((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]\d)|\d)){3}/', $ip))
    {
        return false;
    }
    else
    {
        return true;
    }
}

/**
 * 获得当前的Url
 */
function get_cururl()
{
    if (!empty($_SERVER["REQUEST_URI"]))
    {
        $scriptName = $_SERVER["REQUEST_URI"];
        $nowurl = $scriptName;
    }
    else
    {
        $scriptName = $_SERVER["PHP_SELF"];
        $nowurl = empty($_SERVER["QUERY_STRING"]) ? $scriptName : $scriptName . "?" . $_SERVER["QUERY_STRING"];
    }
    return $nowurl;
}

/**
 * 用递归方式创建目录
 */
function mkdir_recurse($pathname, $mode)
{
    $pathname = rtrim(preg_replace(array('/\\{1,}/', '/\/{2,}/'), '/', $pathname), '/');
    is_dir(dirname($pathname)) || mkdir_recurse(dirname($pathname), $mode);
    return is_dir($pathname) || @mkdir($pathname, $mode);
}

/**
 * 用递归方式删除目录
 */
function rm_recurse($file)
{
    if (is_dir($file) && !is_link($file))
    {
        foreach (glob($file . '/*') as $sf)
        {
            if (!rm_recurse($sf))
            {
                return false;
            }
        }
        return @rmdir($file);
    }
    else
    {
        return @unlink($file);
    }
}

/* 判断是否为utf8字符串 */

function is_utf8($str)
{
    if ($str === mb_convert_encoding(mb_convert_encoding($str, "UTF-32", "UTF-8"), "UTF-8", "UTF-32"))
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * 分页处理
 *
 *  @param array $config
 *               $config['start']         //当前页进度
 *               $config['per_count']     //每页显示多少条
 *               $config['count_number']  //总记录数
 *               $config['url']           //网址
 * @return string
 */
function pagination($config)
{
    /*
      <div class="page">
      <span class="nextprev">&laquo; 上一页</span>
      <span class="current">1</span>
      <a href="">2</a>
      <a href="">3</a>
      <a href="" class="nextprev">下一页 &raquo;</a>
      <span>共 100 页</span>
      </div>
     */
    //网址
    $config['url'] = empty($config['url']) ? '' : $config['url'];
    //总记录数
    $config['count_number'] = empty($config['count_number']) ? 0 : (int) $config['count_number'];
    //每页显示数
    $config['per_count'] = empty($config['per_count']) ? 10 : (int) $config['per_count'];
    //总页数
    $config['count_page'] = ceil($config['count_number'] / $config['per_count']);
    //分页名
    $config['page_name'] = empty($config['page_name']) ? 'start' : $config['page_name'];
    //当前页数
    //$config['current_page'] = max(1, ceil($config['start'] / $config['per_count']) + 1);
    $config['current_page'] = $config['start'];
    //总页数不到二页时不分页
    if (empty($config) or $config['count_page'] < 2)
    {
        return false;
    }
    //分页内容
    $pages = '<div class="mfw-page">';
    //$pages = '';
    //下一页
    $next_page = $config['start'] > $config['count_page'] ? $config['count_page'] : $config['current_page'] +1;
    //上一页
    $prev_page = $config['start']==1 ? 1 : $config['current_page'] -1 ;
    //末页
    //$last_page = ($config['count_page'] - 1) * $config['per_count'];
    $last_page =  $config['count_page'];
    $flag = 0;

    if ($config['current_page'] > 1)
    {
        //首页
        $pages .= "<a href='{$config['url']}' class='nextprev'>&laquo;首页</a>\n";
        //上一页
        $pages .= "<a href='{$config['url']}&{$config['page_name']}={$prev_page}' class='nextprev'>&laquo;上一页</a>\n";
    }
    else
    {
        $pages .= "<a class='nextprev'>&laquo;首页</a>\n";
        $pages .= "<a class='nextprev'>&laquo;上一页</a>\n";
    }
    //前偏移
    for ($i = $config['current_page'] - 6; $i <= $config['current_page'] - 1; $i++)
    {
        if ($i < 1)
        {
            continue;
        }

        //$_start = ($i - 1) * $config['per_count']; #
        $_start = $i;
        $pages .= "<a href='{$config['url']}&{$config['page_name']}=$_start'>$i</a>\n";
    }
    //当前页
    $pages .= "<a class='current'>" . $config['current_page'] . "</a>\n";
    //后偏移
    if ($config['current_page'] < $config['count_page'])
    {
        for ($i = $config['current_page'] + 1; $i <= $config['count_page']; $i++)
        {
            //$_start = ($i - 1) * $config['per_count']; #
            $_start = $i;

            $pages .= "<a href='{$config['url']}&{$config['page_name']}=$_start'>$i</a>\n";

            $flag++;

            if ($flag == 6)
            {
                break;
            }
        }
    }
    if ($config['current_page'] != $config['count_page'])
    {
        //下一页
        $pages .= "<a href='{$config['url']}&{$config['page_name']}={$next_page}' class='nextprev'>下一页&raquo;</a>\n";
        //末页
        $pages .= "<a href='{$config['url']}&{$config['page_name']}={$last_page}' class='end'>末页&raquo;</a>\n";
    }
    else
    {
        $pages .= "<a class='nextprev'>下一页&raquo;</a>\n";
        $pages .= "<a class='nextprev'>末页&raquo;</a>\n";
    }
    //增加输入框跳转 by skey 2009-09-02
    if (!empty($config['input']))
    {
        $pages .= '<input type="text" onkeydown="javascript:if(event.keyCode==13){ var offset = ' . $config['per_count'] . '*(this.value-1);location=\'' . $config["url"] . '&' . $config["page_name"] . '=\'+offset;}" onkeyup="value=value.replace(/[^\d]/g,\'\')" />';
    }
    $pages .= "<span>共 {$config['count_page']} 页， {$config['count_number']} 条记录</span>\n";
    $pages .= '</div>';

    return $pages;
}

/**
 * utf8编码模式的中文截取2，单字节截取模式
 * 如果mbstring扩展开启使用mb_substr函数
 * @return string
 * @date  2011-10-22
 */
function utf8_substr($str, $slen, $startdd=0)
{
    if (function_exists("mb_substr"))
    {
        $str = strip_tags($str, "<br>");
        $str = preg_replace("/(\r\n|\n|\r){1}/", "", $str);
        $str = strtr($str, array("&nbsp;" => " "));

        $str = preg_replace("/<br\s*(\/)?>/", "\n", $str);
        return nl2br(mb_substr($str, $startdd, $slen, 'UTF-8'));
    }
    else
    {
        if (strlen($str) < $startdd || empty($str))
        {
            return $str;
        }
        $ar = array();
        preg_match_all("/./su", $str, $ar);
        $str = '';
        $maxlen = $startdd + $slen;
        for ($i = 0; isset($ar[0][$i]); $i++)
        {
            if ($i < $startdd)
            {
                continue;
            }
            else if ($i < $maxlen)
            {
                $str .= $ar[0][$i];
            }
            else
            {
                break;
            }
        }
        return $str;
    }
}

/*
 * 关键字过滤，去掉 . 以外的所有ANSI特殊符号，及一些中文特殊符号
 */

function filter_keyword($str)
{
    $arr = array();
    preg_match_all("/./su", $str, $arr);
    $okstr = '';
    $fiter_arr = array('、', '。', '·', 'ˉ', 'ˇ', '¨', '〃', '々', '—', '～', '‖', '…', '‘', '’', '“', '”', '？', '：', '〔',
        '〕', '〈', '〉', '《', '》', '「', '」', '『', '』', '〖', '〗', '【', '】', '±', '×', '÷', '∶', '∧', '∨', '∑', '∏',
        '∪', '∩', '∈', '∷', '√', '⊥', '∥', '∠', '⌒', '⊙', '∫', '∮', '≡', '≌', '≈', '∽', '∝', '≠', '≮', '≯', '≤',
        '≥', '∞', '∵', '∴', '♂', '♀', '°', '′', '″', '℃', '＄', '¤', '￠', '￡', '‰', '§', '№', '☆', '★', '○', '●', '◎', '◇',
        '◆', '□', '■', '△', '▲', '※', '→', '←', '↑', '↓', '〓', '　', '！', '＂', '＃', '￥', '％', '＆', '＇', '（', '）', '＊',
        '＋', '，', '－', '．', '／', '；', '＜', '＝', '＞', '＠', '［', '＼', '］', '＾', '＿', '｀', '｛', '｜', '｝', '￣');
    foreach ($arr[0] as $a)
    {
        if (strlen($a) == 1 && !preg_match("/[0-9a-z@_:\.\+\-]/i", $a))
        {
            $okstr .= ' ';
        }
        else
        {
            $okstr .= in_array($a, $fiter_arr) ? ' ' : $a;
        }
    }
    $okstr = trim(preg_replace("/[ ]{1, }/", ' ', $okstr));
    return $okstr;
}

/**
 * 从普通时间返回Linux时间截
 */
function get_mktime($dtime)
{
    if (!preg_match("/[^0-9]/", $dtime))
    {
        return $dtime;
    }
    $dtime = trim($dtime);
    $dt = Array(1970, 1, 1, 0, 0, 0);
    $dtime = preg_replace("/[\r\n\t]|日|秒/", " ", $dtime);
    $dtime = str_replace("年", "-", $dtime);
    $dtime = str_replace("月", "-", $dtime);
    $dtime = str_replace("时", ":", $dtime);
    $dtime = str_replace("分", ":", $dtime);
    $dtime = trim(preg_replace("/[ ]{1,}/", " ", $dtime));
    $ds = explode(" ", $dtime);
    $ymd = explode("-", $ds[0]);
    if (!isset($ymd[1]))
    {
        $ymd = explode(".", $ds[0]);
    }
    if (isset($ymd[0]))
    {
        $dt[0] = $ymd[0];
    }
    if (isset($ymd[1]))
        $dt[1] = $ymd[1];
    if (isset($ymd[2]))
        $dt[2] = $ymd[2];
    if (strlen($dt[0]) == 2)
        $dt[0] = '20' . $dt[0];
    if (isset($ds[1]))
    {
        $hms = explode(":", $ds[1]);
        if (isset($hms[0]))
            $dt[3] = $hms[0];
        if (isset($hms[1]))
            $dt[4] = $hms[1];
        if (isset($hms[2]))
            $dt[5] = $hms[2];
    }
    foreach ($dt as $k => $v)
    {
        $v = preg_replace("/^0{1,}/", '', trim($v));
        if ($v == '')
        {
            $dt[$k] = 0;
        }
    }
    $mt = mktime($dt[3], $dt[4], $dt[5], $dt[1], $dt[2], $dt[0]);
    if (!empty($mt))
    {
        return $mt;
    }
    else
    {
        return time();
    }
}

/**
 * 检测日期格式
 * 2009-12-23, 2009-12, 检测类似这样的格式
 * @param string $date
 * @param string $kind 严格模式 验证 2009-12-23
 * @return boolean
 * @todo 写得有点啰嗦
 */
function check_date($date, $kind = '1')
{
    $date = explode('-', $date);
    if (count($date) < 2)
    {
        return false;
    }
    if (count($date) < 3 && $kind == 2)
    {
        return false;
    }
    if (!preg_match("/^[0-9]{4}$/", $date[0]))
    {
        return false;
    }
    if (!preg_match("/^[0-9]{1,2}$/", $date[1]))
    {
        return false;
    }
    else
    {
        if (intval($date[1] > 12) || intval($date[1] <= 0))
        {
            return false;
        }
    }
    if (!empty($date[2]))
    {
        if (!preg_match("/^[0-9]{1,2}$/", $date[2]))
        {
            return false;
        }
        else
        {
            if (intval($date[2] > 31) || intval($date[2] <= 0))
            {
                return false;
            }
        }
    }
    return true;
}

/**
 * 发送邮件
 * @param array  $to      收件人
 * @param string $subject 邮件标题
 * @param string $body　      邮件内容
 * @return bool
 * @author xiaocai
 */
function send_email($to, $subject, $body)
{
    global $send_account;

    try
    {
        $result = cls_mailer::sendmail($to, $subject, $body, $send_account);
        return $result;
    }
    catch (Exception $e)
    {
        return false;
    }
}

//引入项目私有的公共函数
require PATH_LIBRARY . '/lib_private.php';
?>