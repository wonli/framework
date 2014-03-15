<ul>
    <?php foreach($article as $item) : ?>
    <li>
        <div class="articlelist">
            <a class="listtitle" href="<?php echo $this->link("article:detail", array($item["id"], "{$item["title"]}.html")) ?>">
                <?php echo $item["title"] ?>
            </a>
            <?php if(! empty($item["tag"])) : ?>
                <?php foreach($item["tag"] as $tag) : ?>
                <a style="font-size:9px;background: rgb(240, 248, 250);padding: 0px 5px;border:1px solid rgb(234, 240, 243)" href="<?php echo $this->link("tag",array('id'=>$tag["id"])) ?> "><?php echo $tag["name"] ?></a>
                <?php endforeach ?>
            <?php endif ?>

            <?php if(isset($_SESSION["u"])) : ?>
            <a class="bmodify" href="<?php echo $this->link("user:edit", $item["id"]) ?>">编辑</a>
            <?php endif ?>
        </div>
        <div><?php echo stripcslashes($item["intro"]) ?></div>
    </li>
    <?php endforeach ?>
</ul>
