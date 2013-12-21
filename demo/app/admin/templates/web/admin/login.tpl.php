	<div class="login">
        <form action="" method="post">
		<div class="login_form">
			<div class="form_info">
				<div class="field" style="margin-top:30px;">
					<label>用户名:</label>
					<input type="text" name="username" class="text" size="20">
				</div>
				<div class="field">
					<label>密　码:</label>
					<input type="password" name="password" class="text" size="20">
				</div>
				<div class="field">
					<label>验证码:</label>
					<input type="text" name="vcode" style="width:100px;" class="text" size="10">
					<input type="hidden" name="vlocation" value="<?php echo $vcode_location ?>">                    
                    <cite class="yzm"><?php echo $vcode_location ?></cite>
				</div>
				<div class="field">
					<label></label>
					<input class="button" type="submit" style="margin-left:50px;_margin-left:48px" value="">
				</div>
			</div>
		</div>
        </form>
	</div>

<?php if(!empty($errormsg)) :  ?>
<script>
pop.alert(-1, '<?php echo $errormsg ?>');
</script> 
<?php endif ?>
