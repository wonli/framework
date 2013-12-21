<form action="" method="post">
<div>

	<table class="tb2">
		<tr>
			<th>菜单</th>
			<th style="text-align:center">子菜单</th>
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
				<table class="tb1">
                    <?php if(isset($menu["method"])) : ?>
					<?php foreach($menu["method"] as $m => $set ) : ?>
					<tr>
						<td style="width:120px;"><?php echo $m ?></td>
						<td><input type="text" name="menu[<?php echo $menu["id"] ?>][<?php echo $m ?>][name]" id="" value="<?php if(isset($set["name"])) { echo $set["name"]; } ?>" /></td>
						<td><input type="checkbox" name="menu[<?php echo $menu["id"] ?>][<?php echo $m ?>][display]" <?php if(isset($set["display"]) && $set["display"] == 1) :?>checked<?php endif;?> id="" /></td>
						<td><input type="text" name="menu[<?php echo $menu["id"] ?>][<?php echo $m ?>][order]" <?php if(! empty($set["order"])) :?>value="<?php echo $set["order"]; ?>"<?php endif;?> style="width:50px;" /></td>
					</tr>
					<?php endforeach;?>
                    <?php endif ?>
				</table>
			</td>
		</tr>
        <?php endforeach ?>
        <tr>
        	<td colspan="2">
				<input class="btn" style="margin-left:30px;width:60px;height:25px;" type="submit" value="提交" />
        	</td>
        </tr>
    </table>

</div>
</form>


