<div class="main">
    <div class="left">
        <ul>
            <?php foreach($data["tag"] as $tag) : ?>
            <li>
                <?php if($data["tid"] == $tag["id"]) : ?>
                <a style="color:#151A91" href="<?php echo $this->link("tag",array('id'=>$tag["id"])) ?> "><?php echo $tag["name"] ?></a>
                <?php else : ?>
                <a href="<?php echo $this->link("tag",array('id'=>$tag["id"])) ?> "><?php echo $tag["name"] ?></a>
                <?php endif ?>
            </li>
            <?php endforeach ?>
        </ul>
    </div>

    <div class="right">
    <?php
    if(! empty($article)) {
        include $this->tpl("article/list");
    } else {
        echo '暂无';
    }
    ?>
    <div class="page"><?php $this->page($page) ?></div>
    </div>
</div>

