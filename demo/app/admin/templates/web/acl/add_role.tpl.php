<form class="pure-form" action="" method="post">
    <table>
        <tr>
            <td align="left">
                名称: <input type="text" name="name" id=""/>
                <input class="pure-button" type="submit" value="提交"/>
            </td>
        </tr>
        <tr>
            <td style="height: 20px"></td>
        </tr>
        <tr>
            <td><?php include $this->tpl("acl/behavior")  ?></td>
        </tr>
    </table>
</form>

