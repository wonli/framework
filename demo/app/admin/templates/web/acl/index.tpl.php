<form class="pure-form" action="" method="post">
	<table>
		<tr>
			<th style="text-align: center">类名称</th>
			<th style="text-align:center">
                <table>
                    <tr>
                        <th>
                            <td style="height:30px;width:120px;">方法名称</td>
                            <td style="width:150px;">名称</td>
                            <td style="width:50px;">是否显示</td>
                            <td style="width:30px;">排序</td>
                        </th>
                    </tr>
                </table>
            </th>
		</tr>

        <?php foreach($menu_list as $menu) : ?>
		<tr>
			<td>
				<?php echo $menu["name"] ?>
				<span style="margin-right:20px;">
					( <?php echo $menu["link"] ?> )
				</span>
			</td>
			<td style="margin-left:30px;">
				<table class="pure-table pure-table-horizontal">
                    <?php if(! empty($menu["method"])) : ?>
                        <?php foreach($menu["method"] as $m => $set ) : ?>
                        <tr>
                            <td style="width:120px;"><?php echo $m ?></td>
                            <td><input type="text" name="menu[<?php echo $menu["id"] ?>][<?php echo $m ?>][name]" id="" value="<?php if(isset($set["name"])) { echo $set["name"]; } ?>" /></td>
                            <td><input type="checkbox" name="menu[<?php echo $menu["id"] ?>][<?php echo $m ?>][display]" <?php if(isset($set["display"]) && $set["display"] == 1) :?>checked<?php endif;?> id="" /></td>
                            <td><input type="text" name="menu[<?php echo $menu["id"] ?>][<?php echo $m ?>][order]" <?php if(! empty($set["order"])) :?>value="<?php echo $set["order"]; ?>"<?php endif;?> style="width:50px;" /></td>
                        </tr>
                        <?php endforeach;?>
                    <?php endif; ?>
				</table>
			</td>
		</tr>
        <?php endforeach ?>
        <tr>
            <td></td>
        	<td colspan="2">
				<input class="pure-button" style="" type="submit" value="提交" />
        	</td>
        </tr>
    </table>
</form>


