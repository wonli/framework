
<form action="" method="post">
    名称 <input type="text" name="name" id="" />
    连接 <input type="text" name="link" id="" />
    <input type="submit" name="add" value="提交" />
</form>

<form id="form_nav" action="" method="post">
    <div style="margin-top: 30px;">
        <table class="tb2">
            <tr>
                <th style="text-align: center">ID</th>
                <th style="text-align: center">名称</th>
                <th style="text-align: center">类名称</th>
                <th style="text-align: center">是否显示</th>
                <th style="text-align: center">排序</th>
                <th style="text-align: center">操作</th>
            </tr>
            <?php foreach($data["menu"] as $m) : ?>
                <tr>
                    <td>
                        <?php echo $m['id'] ?>
                        <input type="hidden" id="ele_id" name="id" value="" />
                        <input type="hidden" name="nav[<?php echo $m['id'] ?>][id]" id="" value="<?php echo $m['id'] ?>" />
                    </td>
                    <td><input type="text" name="nav[<?php echo $m['id'] ?>][name]" id="" value="<?php echo $m['name'] ?>" /></td>
                    <td><input type="text" name="nav[<?php echo $m['id'] ?>][link]" id="" value="<?php echo $m['link'] ?>" /></td>
                    <td><input type="text" name="nav[<?php echo $m['id'] ?>][status]" id="" value="<?php echo $m['status'] ?>" /></td>
                    <td><input type="text" name="nav[<?php echo $m['id'] ?>][order]" id="" value="<?php echo $m['order'] ?>" /></td>
                    <td><a href="<?php echo $this->link("acl:del", array('id'=>$m['id'])) ?>">删除</a></td>
                </tr>
            <?php endforeach ?>
            <tr>
                <td></td>
                <td colspan="5"><input type="submit" name="save" value="保存"/></td>
            </tr>
        </table>
    </div>
</form>
