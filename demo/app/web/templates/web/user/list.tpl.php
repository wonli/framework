<div class="list">
    <ul>
        <?php
        foreach($data as $item)
        {
            ?>
            <li>
                <a href="<?php echo $this->link("article:detail", "{$item["id"]}.html") ?>"><?php echo $item["title"] ?></a>
                <a class="bmodify" href="<?php echo $this->link("user:edit", $item["id"]) ?>">修改</a>
                <a class="bdel" href="<?php echo $this->link("user:del", $item["id"]) ?>">删除</a>
            </li>
            <?php
        }
        ?>
    </ul>
</div>
<div><?php $this->page($page) ?></div>
