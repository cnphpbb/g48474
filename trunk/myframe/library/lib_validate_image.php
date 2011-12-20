<?php
/**
 * 输出一个验证码图片
 * @parem $config array(
 *                   'font_size'=>文字大小（默认14）,
 *                   'img_height'=>图片高度（默认24）,
 *                   'img_width'=>图片宽度（默认68）,
 *                   'use_boder'=>使用边框（默认true）,
 *                   'font_file'=>字体文件（默认 PATH_DATA.'/font/bodi.ttf'）,
 *                   'filter_type'=>图片效果(0 无，1 反转颜色，2 浮雕化， 3 边缘检测, )
 *                 );
 */
function echo_validate_image( $config = array() )
{
        @session_start();
        
        //主要参数
        $font_size   = isset($config['font_size']) ? $config['font_size'] : 14;
        $img_height  = isset($config['img_height']) ? $config['img_height'] : 24;
        $img_width   = isset($config['img_width']) ? $config['img_width'] : 68;
        $font_file   = isset($config['font_file']) ? $config['font_file'] : PATH_DATA.'/font/ggbi.ttf';
        $use_boder   = isset($config['use_boder']) ? $config['use_boder'] : true;
        $filter_type = isset($config['filter_type']) ? $config['filter_type'] : 0;
        
        //创建图片，并设置背景色
        $im = imagecreate($img_width, $img_height);
        ImageColorAllocate($im, 255,255,255);
        
        //文字随机颜色
        $fontColor[]  = ImageColorAllocate($im, 0x15, 0x15, 0x15);
        $fontColor[]  = ImageColorAllocate($im, 0x95, 0x1e, 0x04);
        $fontColor[]  = ImageColorAllocate($im, 0x93, 0x14, 0xa9);
        $fontColor[]  = ImageColorAllocate($im, 0x12, 0x81, 0x0a);
        $fontColor[]  = ImageColorAllocate($im, 0x06, 0x3a, 0xd5);
        
        //获取随机字符
        $rndstring  = '';
        for($i=0; $i<4; $i++)
        {
            $c = chr(mt_rand(65, 90));
            if( $c=='I' ) $c = 'P';
            if( $c=='O' ) $c = 'N';
            $rndstring .= $c;
        }
        $_SESSION['dd_ckstr'] = strtolower($rndstring);

        $rndcodelen = strlen($rndstring);

        //背景横线
        $lineColor1 = ImageColorAllocate($im, 0xda,0xd9,0xd1);
        for($j=3; $j<=$img_height-3; $j=$j+3)
        {
                imageline($im, 2, $j, $img_width - 2, $j, $lineColor1);
        }
        
        //背景竖线
        $lineColor2 = ImageColorAllocate($im, 0xda,0xd9,0xd1);
        for($j=2;$j<100;$j=$j+6)
        {
            imageline($im, $j, 0, $j+8, $img_height, $lineColor2);
        }

        //画边框
        if( $use_boder && $filter_type == 0 )
        {
            $bordercolor = ImageColorAllocate($im, 0x9d,0x9e,0x96);
            imagerectangle($im, 0, 0, $img_width-1, $img_height-1, $bordercolor);
        }
        
        //输出文字
        $lastc = '';
        for($i=0;$i<$rndcodelen;$i++)
        {
            $bc = mt_rand(0, 1);
            $rndstring[$i] = strtoupper($rndstring[$i]);
            $c_fontColor = $fontColor[mt_rand(0,4)];
            $y_pos = $i==0 ? 4 : $i*($font_size+2);
            $c = mt_rand(0, 15);
            @imagettftext($im, $font_size, $c, $y_pos, 19, $c_fontColor, $font_file, $rndstring[$i]);
            $lastc = $rndstring[$i];
        }
        
        //图象效果
        switch($filter_type)
        {
            case 1:
                imagefilter ( $im, IMG_FILTER_NEGATE);
                break;
            case 2:
                imagefilter ( $im, IMG_FILTER_EMBOSS);
                break;
            case 3:
                imagefilter ( $im, IMG_FILTER_EDGEDETECT);
                break;
            default:
                break;
        }

        header("Pragma:no-cache\r\n");
        header("Cache-Control:no-cache\r\n");
        header("Expires:0\r\n");

        //输出特定类型的图片格式，优先级为 gif -> jpg ->png
        if(function_exists("imagejpeg"))
        {
            header("content-type:image/jpeg\r\n");
            imagejpeg($im);
        }
        else
        {
            header("content-type:image/png\r\n");
            imagepng($im);
        }
        ImageDestroy($im);
        exit();
}
?>