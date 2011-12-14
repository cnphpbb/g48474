/**
 * 弹出窗体JS文件
 * Page-name of  jquery.dialog.js
 * 与jQuery UI 配合使用
 * @author kevinG <cnphpbb@hotmail.com>
 * @version 2011-12-14 $
 */
(function($) {
    var plugin_dialog = jQuery.sub();
    $.plugin_dialog = function(element, options) {
        // 默认值 支持 DIALOG option
        var defaults = {
            type:'',             //显示的数据类型
            url: '',             //如果使用AJAX或IFRAME 类型:必须要设置URL
            message: '',         // 消息
            autoCloseTime: 0,    //自动关闭时间 默认:0 不自动关闭
            title: '',           //显示在title上的文字 默认: 空
            modal: false,        //遮罩
            width: 0,            //宽度
            height: 'auto',      //高度
            top_close: true,     //关闭顶部 CLOSE按钮
            remove: true,        //是否移除父窗体
            titlebar: true       //是否显示titlebar boolean (true|false) 默认: true
        };
        var plugin = this;
        plugin.settings = {};
        var $element = $(element), element = element;
        options = $.extend({}, defaults, arguments[1]);
        plugin.init = function() {
            plugin.settings = $.extend({}, defaults, options);
            switch (plugin.settings.type) {
                case "div":
                    $element = $('<div id="dialog_content"></div>');
                    break;
                case "ajax":
                    $element = $('<div id="dialog_content"></div>');
                    break;
                case "iframe":
                    $element = $('<div id="dialog_content" style="overflow-x: hidden; overflow-y: hidden;"><iframe src="'+ plugin.settings.url +'" width="100%" scrolling="auto" height="100%" frameborder="0" marginheight="0" marginwidth="0"></iframe></div>');
                    break;
                case "alert":
                    $element = $('<div id="dialog_content"></div>');
                    var buttons = {"确定": function() {$(this).dialog("close");}};
                    if(plugin.settings.buttons == undefined){
                        plugin.settings.buttons = buttons;
                    }
                    break;
                default:
                   $element = $('<div id="dialog_content"></div>');
                   break;
            }
            if(plugin.settings.message != ''){
                $element.html(plugin.settings.message);
            }
            options = {};

        }

        plugin.show = function() {
            $.fx.speeds._default = 1000;
            if(plugin.settings.remove){
                _remove($element);
            }
            $element.dialog({
                show: plugin.settings.show,
                hide: plugin.settings.hide,
                autoOpen: false,
                resizable: false,
                autoResize: true,
                close: function() {
                    _remove($element);
                }
            });
            if(!plugin.settings.titlebar){
                $(".ui-dialog-titlebar").hide();
            }else{
                $(".ui-dialog-titlebar").show();
            }
            if(plugin.settings.autoCloseTime > 0){
                setTimeout(function(){
                    _remove($element);
                },plugin.settings.autoCloseTime);
            }
            if(plugin.settings.title != undefined || plugin.settings.title != ''){
                plugin.set_title(plugin.settings.title);
            }
            if(plugin.settings.show != undefined){
                plugin.set_show(plugin.settings.show);
            }
            if(plugin.settings.dialogClass != undefined){
                plugin.set_dialogClass(plugin.settings.dialogClass);
            }
            if(plugin.settings.buttons != undefined){
                plugin.set_buttons(plugin.settings.buttons);
            }
            if(plugin.settings.position != undefined){
                plugin.set_position(plugin.settings.position);
            }
            if(!plugin.settings.top_close){
                plugin.set_top_close(plugin.settings.top_close);
            }
            if(plugin.settings.modal){
                plugin.set_modal(plugin.settings.modal);
            }
            if(plugin.settings.width > 0){
                plugin.set_width(plugin.settings.width);
            }
            if(plugin.settings.height > 0 && plugin.settings.height !='auto' ){
                plugin.set_height(plugin.settings.height);
            }
            if(plugin.settings.minHeight != undefined){
                plugin.set_minHeight(plugin.settings.minHeight);
            }
            if(plugin.settings.type == "iframe" || plugin.settings.type == "ajax"){
                var left = ($(window).width() - $(".ui-dialog").width()) / 2;
                if(plugin.settings.type == "ajax"){
                    var top = ($(window).height() - plugin.settings.minHeight) / 2;
                    $element.load(plugin.settings.url).hide();
                }
                if(plugin.settings.type == "iframe"){
                    $("#"+$element.attr("id")).css({'height':plugin.settings.height+'px'});
                    var top = ($(window).height() - $(".ui-dialog").height()) / 2;
                }
                plugin.set_position([left,top]);
            }
            $element.dialog( "open" );
            return false;
        }
        plugin.set_message = function(message){
            plugin.settings.message = message;
        }
        plugin.set_title = function(title){
            $element.dialog( "option", 'title', title );
        }
        plugin.set_show = function(show){
            $element.dialog( "option", 'show', show );
        }
        plugin.set_dialogClass = function(dialogClass){
            $element.dialog( "option", 'dialogClass', dialogClass );
        }
        plugin.set_position = function(position){
            $element.dialog( "option", 'position', position );
        }
        plugin.set_buttons = function(buttons){
            $element.dialog( "option", 'buttons', buttons );
        }
        plugin.set_modal = function(modal){
            $element.dialog( "option", "modal", modal );
        }
        plugin.set_width = function(width){
            $element.dialog( "option", 'width', width );
        }
        plugin.set_height = function(height){
            $element.dialog( "option", 'height', height );
        }
        plugin.set_minHeight = function(minHeight){
            $element.dialog( "option", 'minHeight', minHeight );
        }
        plugin.set_top_close = function(){
            $(".ui-dialog-titlebar-close").hide();
        }
        var _ajax = function(url,ev){
            ev.load(url);
        }
        var _get_id = function(e){
            return e.attr('id');
        }
        var _get_class = function(e){
            return e.attr('class');
        }
        var _remove = function(e){
            if(_get_id(e)){
                $("#"+e.attr("id")).remove();
            }
            if(_get_class(e)){
                $("."+e.attr("class")).remove();
            }
            e.dialog("destroy");
        }
        plugin.init();
    }

    $.fn.plugin_dialog = function(options) {
        return this.each(function() {
            if (undefined == $(this).data('plugin_dialog')) {
                var plugin = new $.plugin_dialog(this, options);
                $(this).data('plugin_dialog', plugin);
            }
        });
    }
})(jQuery);


