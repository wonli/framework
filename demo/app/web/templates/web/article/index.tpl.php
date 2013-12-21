<div class="tag">
    <?php foreach($tag as $t) : ?>
        <a href="<?php echo $this->link("tag",array($t["id"], $t["name"])) ?> "><?php echo $t["name"] ?></a>
    <?php endforeach ?>
</div>
<div class="main">
<?php include $this->tpl("article/list"); ?>
<div class="page"><?php echo $this->page( $page ) ?></div>
</div>

