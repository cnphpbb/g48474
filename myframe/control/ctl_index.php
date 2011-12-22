<?php

//严禁直接访问类文件
if (!defined('PATH_ROOT')) {
    exit('Request Error!');
}

/**
 * Description of ctl_index
 * Page-name of  ctl_index.php
 *
 * @author kevinG <cnphpbb@hotmail.com>
 * @version 2011-12-21 $
 */
class ctl_index {

    /**
     * 首页
     */
    public function index() {
        $tpl_name = "demo/index.tpl";
        cls_template::display($tpl_name);
    }

}

?>
