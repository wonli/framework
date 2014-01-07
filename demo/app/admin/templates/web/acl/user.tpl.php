<div class="pure-form">
    <form action="" method="post">
    <table class="pure-table pure-table-horizontal" style="border:none">
        <tr>
            <th>id</th>
            <th>用户名</th>
            <th>密码</th>
            <th>状态</th>
            <th>角色</th>
        </tr>
        <?php foreach($data['u'] as $u) : ?>
        <tr>
            <td width="30"><?php echo $u['id'] ?></td>
            <td><input type="text" name="a[<?php echo $u['id'] ?>][name]" value="<?php echo $u['name'] ?>" id=""/></td>
            <td><input type="text" style="width:365px" name="a[<?php echo $u['id'] ?>][password]" value="<?php echo $u['password'] ?>" id=""/></td>
            <td><input type="text" style="width:35px" name="a[<?php echo $u['id'] ?>][t]" value="<?php echo $u['t'] ?>" id=""/></td>
            <td>
                <select name="a[<?php echo $u['id'] ?>][rid]" id="">
                    <?php foreach( $data['roles'] as $r ) : ?>
                        <?php if($r['id'] == $u['rid']): ?>
                        <option value="<?php echo $r['id'] ?>" selected><?php echo $r['name'] ?></option>
                        <?php else : ?>
                        <option value="<?php echo $r['id'] ?>"><?php echo $r['name'] ?></option>
                        <?php endif ?>
                    <?php endforeach ?>
                </select>
            </td>
        </tr>
        <?php endforeach ?>
        <tr>
            <td width="30">+</td>
            <td><input type="text" name="a[+][name]" value="" id=""/></td>
            <td><input type="text" style="width:365px" name="a[+][password]" value="" id=""/></td>
            <td><input type="text" style="width:35px" name="a[+][t]" value="" id=""/></td>
            <td>
                <select name="a[+][rid]" id="">
                    <?php foreach( $data['roles'] as $r ) : ?>
                        <option value="<?php echo $r['id'] ?>"><?php echo $r['name'] ?></option>
                    <?php endforeach ?>
                </select>
            </td>
        </tr>
        <tr>
            <td></td>
            <td colspan="12"><input class="pure-button" type="submit" value="保存"/></td>
        </tr>
    </table>
    </form>
</div>
