<div class="tag">
    <?php foreach($data["tag"] as $tag) : ?>
    <a href="<?php echo $this->link("tag",array('id'=>$tag["id"])) ?> "><?php echo $tag["name"] ?></a>
    <?php endforeach ?>
</div>
<div class="main">
<?php include $this->tpl("article/list") ?>
</div>

