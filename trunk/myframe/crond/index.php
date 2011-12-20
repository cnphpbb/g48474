<?php

/**
 *  CROND 定时控制器
 * 
 * @author kevinG <cnphpbb@hotmail.com>
 * @version 2011-12-21
 */

define('__THIS__', strtr(__FILE__, array('\\' => '/', '/index.php' => '', '\index.php' => '')));
require __THIS__ . '/../init.php';

/* 永不超时 */
ini_set('max_execution_time', 0);

/* 执行CROND */
exit(crond());

/* CROND函数 */

function crond() {
    require PATH_CONFIG . '/inc_crond.php';

    $time = microtime(true);

    /* 提取要执行的文件 */
    $exe_file = array();
    foreach ($GLOBALS['CROND_TIMER']['the_format'] as $format) {
        $key = date($format, ceil($time));
        if (is_array(@$GLOBALS['CROND_TIMER']['the_time'][$key])) {
            $exe_file = array_merge($exe_file, $GLOBALS['CROND_TIMER']['the_time'][$key]);
        }
    }

    echo "\n" . date('Y-m-d H:i', time()), "\n\n";

    /* 加载要执行的文件 */
    foreach ($exe_file as $file) {
        echo '  ', $file, "\n";
        @include __THIS__ . '/' . $file;
        echo "\n\n";
    }

    echo 'total: ', microtime(true) - $time . "\n";

//    sleep(2);
//    crond();
}

?>