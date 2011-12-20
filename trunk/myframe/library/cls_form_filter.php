<?php

//严禁直接访问类文件
if (!defined('PATH_ROOT'))
{
    exit('Request Error!');
}

/**
 * Description of cls_form_filter
 * Page-name of  cls_form_filter.php
 *
 * @author kevinG <cnphpbb@hotmail.com>
 * @version 2011-11-10 $
 */
class cls_form_filter extends cls_validate
{
    public $filters = array();                      //验证规则
    public $formData = array();                     //表单数据
    public $attributeLabels = array();              //表单标签
    private $message = null;                        //错误信息
    protected static $instance = null;              //工厂模式的对象实例

    /**
     * 用工厂方法创建对象实例
     * @return object
     */
    public static function init()
    {
        if (self::$instance === null)
        {
            self::$instance = new cls_form_filter();
        }
        return self::$instance;
    }

    /**
     * 表单验证
     * @param type $filters
     * @param type $formData
     * @param type $attributeLabels
     * @return type
     */
    public function form_filter($filters, $formData, $attributeLabels)
    {
        $this->filters = $filters;
        $this->formData = $formData;
        $this->attributeLabels = $attributeLabels;
        $this->runFilter();
        if(empty($this->message)){
            return true;
        }else{
            //var_dump($this->formData);
            return $this->message;
        }
    }
    /**
     * 运行验证
     */
    public function runFilter()
    {
        $filters = $this->filters;
        $formData = $this->formData;
        $labels = $this->attributeLabels;
        $i = 0;
        $bool = true;
        foreach ($filters as $filter)
        {
            $method = $filter[1];
            $num = count($filter);
            $att = array_values(array_slice($filter, 2));
            if (method_exists($this, $method))
            {
                $f = explode(",", $filter[0]);
                $message = array();
                foreach ($f as $v)
                {
                    $val = trim($v);
                    switch ($num){
                        case '3' :
                            $bool = self::$method($formData[$val],$att[0]);
                            break;
                        case '4' :
                            $bool = self::$method($formData[$val],$att[0],$att[1]);
                            break;
                        case '5' :
                            $bool = self::$method($formData[$val],$att[0],$att[1],$att[2]);
                            break;
                        default :
                            $bool = self::$method($formData[$val]);
                            break;
                    }
                    if (!$bool)
                    {
                        $this->message[$i][] = $this->messages($val, $method);
                    }
                }
                if(!empty($this->message)) break;
            }
            else
            {
                throw new Exception("验证方法不存在!");
            }
            //$i++;  此处用错误！！！

        }
    }

        /**
     * 错误消息
     * @param type $f
     * @param type $method
     * @return string
     */
    private function messages($f, $method)
    {
        $message = array();
        if($method == "required"){
            $message[$f] = $this->attributeLabels[$f] . "是必填项";
        }
        elseif($method == "chinese_length"){
            $message[$f] = "请填写" . $this->attributeLabels[$f];
        }
        elseif($method == "length"){
            $message[$f] = $this->attributeLabels[$f] . "超出长度";
        }
        else{
            $message[$f] = $this->attributeLabels[$f] . "格式错误";
        }
        return $message;
    }
    /**
     * 必填项
     */
    public static function required($str)
    {
        return $str!="" ? true : false;
    }
    /**
     * 字符长度
     * @param type $str
     * @param type $max
     * @return type
     */
    public static function length($str, $max)
    {
        return self::max($str, $max);
    }

}

?>
