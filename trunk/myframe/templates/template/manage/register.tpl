<form name="myform" action="<{$form_action}>" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="even" value="save" />
<div class="form_reg">
    <div class="input_text">
        <div>登录帐号：&nbsp;</div><div><input type="text" name="form[user]" value="" /></div><div class="msg_info">登录帐号只能使用英文+数字+下划线的组合6-30个字符</div>
    </div>
    <div class="input_text">
        <div>登录密码：&nbsp;</div><div><input type="text" name="form[passwd]" value="" /></div><div class="msg_info">登录密码6-30个字符</div>
    </div>
    <div class="input_text">
        <div>确认密码：&nbsp;</div><div><input type="text" name="form[cmfpasswd]" value="" /></div><div class="msg_info">确认密码</div>
    </div>
    <div class="buttun">
        <button type="submit">注册帐号</button>
    </div>
</div>
</form>