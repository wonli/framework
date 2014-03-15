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
</div>
<script type="text/javascript">
prettyPrint();
</script>
