<div class="detail">
    <h1><?php echo $content["title"] ?></h1>
    
    <div style="margin-top:20px;"><?php echo stripslashes(htmlspecialchars_decode($content["content"])) ?></div>
</div>

<div class="comment">
<!-- Duoshuo Comment BEGIN -->
	<div class="ds-thread"></div>
	<script type="text/javascript">
	var duoshuoQuery = {short_name:"ideaa"};
	(function() {
		var ds = document.createElement('script');
		ds.type = 'text/javascript';ds.async = true;
		ds.src = 'http://static.duoshuo.com/embed.js';
		ds.charset = 'UTF-8';
		(document.getElementsByTagName('head')[0] 
		|| document.getElementsByTagName('body')[0]).appendChild(ds);
	})();
	</script>
<!-- Duoshuo Comment END --> 
    <!--
    <form action="<?php echo $this->link("article:comment") ?>" method="post">
        <ul>
            <li>
                <input type="hidden" name="id" value="<?php echo $content["id"] ?>"/>
                <div class="ctxt">昵称: </div>
                <div class="cinp"><input type="text" name="user" id="" /><span style="color:red">*</span></div>
            </li>
            <li>
                <div class="ctxt">电子邮件: </div>
                <div class="cinp"><input type="text" name="email" id="" /></div>
            </li>
            <li>
                <div class="ctxt">链接: </div> 
                <div class="cinp"><input type="text" name="link" id="" /></div>
            </li>
            <li>
                <div class="ctxt">&nbsp;</div>
                <div class="cinp">
                    <textarea name="comment" id="" cols="40" rows="6" style="float:left;"></textarea>
                    <span style="color:red;line-height:90px;">*</span>
                </div>
            </li>
            <li>
                <div class="ctxt">&nbsp;</div>
                <div class="cinp"><input style="height:40px;width:100px;" type="submit" value="提交" /></div>
            </li>
        </ul>
    </form>
    -->
</div>
<script type="text/javascript">
prettyPrint();
</script>