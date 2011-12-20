<?php
//严禁直接访问类文件
if( !defined('PATH_ROOT') )
{
    exit('Request Error!');
}
/**
 * 简单对话框类
 * @author kevinG <cnphpbb@hotmail.com>
 * @version 2011-12-21
 */
class cls_msgbox
{
    /**
    * 显示一个简单的对话框
    *
    * @parem $title 标题
    * @parem $msg 消息
    * @parem $gourl 跳转网址（其中 javascript:; 或 空 表示不跳转）
    * @parem $limittime 跳转时间
    *
    * @return void
    *
    */
    public static function show($title, $msg, $gourl='', $limittime=5000)
    {
        if($title=='') $title = '系统提示信息';
        $jumpmsg = $jstmp = '';
        //返回上一页
        if($gourl=='javascript:;')
        {
            $gourl == '';
        }
        if($gourl=='-1')
        {
           $gourl = "javascript:history.go(-1);";
        }
        if( $gourl != '' )
        {
            $jumpmsg = "<div class='ct2'><a href='{$gourl}'>如果你的浏览器没反应，请点击这里...</a></div>";
            $jstmp = "setTimeout('JumpUrl()', {$limittime});";
        }
        cls_template::$appname = 'system';
        cls_template::assign('title', $title);
        cls_template::assign('msg', $msg);
        cls_template::assign('gourl', $gourl);
        cls_template::assign('jumpmsg', $jumpmsg);
        cls_template::assign('jstmp', $jstmp);
        cls_template::display('cls_msgbox.tpl');
        exit();
    }
}