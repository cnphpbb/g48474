<{include file="admin/header.tpl"}>
<div style="width:96%;margin:auto;padding:auto">
<form name="form1" action="~lurdurl~&even=saveedit&tb=~tablename~" method="POST" enctype="multipart/form-data">
<table class="form">
<{lurd_list item='v'}>
    ~listitem~
<{/lurd_list}>
<tr>
  <td colspan='2' align='center' height='60'>
      <button type="submit">保存</button> &nbsp;&nbsp;&nbsp;
      <button type="reset">重设</button>
  </td>
</tr>
</table>
</form>
</div>
</body>
</html>
