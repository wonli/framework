<table>
	<tr>
		<td>hi,我是一个包含布局的输出</td>
	</tr>
	<tr>
		<td><?php echo $data ?></td>		
	</tr>
	<tr>
		<td><a href="<?php echo $this->link("main:hello") ?>">点这里连接到main->hello()</a></td>		
	</tr>
	<tr>
		<td></td>
	</tr>
	<tr>
		<td><a href="<?php echo $this->link("main:test2") ?>">点我连接到main->test2()</a></td>		
	</tr>
	<tr>
		<td><a href="<?php echo $this->link("main:j") ?>">点我连接到main->j()</a></td>		
	</tr>	
	<tr>
		<td><a href="<?php echo $this->link("main:j") ?>">点我连接到main->data()</a></td>		
	</tr>		
</table>
