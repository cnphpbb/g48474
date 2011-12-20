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

    public function index() {
        echo "这个是没使用Smarty模板的输出!!";
    }

    public function test() {
        $form = request('form');
        $even = request("even");
        $tpl_name = "test/test.tpl";
        $form_action = empty($even) ? "?ac=test&even=save" : "?ac=test";
        if($even == "save"){
            debugVar($form);
        }
        cls_template::assign("form_action", $form_action);
        cls_template::display($tpl_name);
    }

}

?>
