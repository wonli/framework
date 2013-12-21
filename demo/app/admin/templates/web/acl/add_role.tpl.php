<form action="" method="post">
    <table class="tb">
        <tr>
            <td colspan="5" style="float: left;margin-left: 30px;">
                名称: <input type="text" name="name" id=""/><input type="submit" value="提交"/>
            </td>
        </tr>

        <tr>
            <td><?php include $this->tpl("acl/behavior")  ?></td>
        </tr>

    </table>
</form>

