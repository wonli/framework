<form action="" method="post" class="pure-form">
    <table class="pure-table">
        <tbody>
            <tr>
                <td width="100">名称</td>
                <td>
                    <input type="text" name="name" id="" value="<?php echo $role_info['name'] ?>" />
                    <input type="hidden" name="rid" value="<?php echo $role_info['id'] ?>" />
                </td>
            </tr>

            <tr>
                <td>权限</td>
                <td><?php include $this->tpl("acl/behavior") ?></td>
            </tr>

            <tr>
                <td></td>
                <td><input class="pure-button" type="submit" value="保存"/></td>
            </tr>
        </tbody>
    </table>
</form>
