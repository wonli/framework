<div class="pure-form" style="text-align: left;">
    <ul>
        <?php
        foreach($data as $d)
        {
            ?>
            <li>
                <a href="<?php echo $this->link("blog:edit", array('id'=> $d['id'])) ?>"><?php echo $d["title"] ?></a>
                <a style="float:right;text-align: right;padding-right: 20px" href="<?php echo $this->link("blog:del", array('id'=> $d['id'])) ?>">删除</a>
            </li>
            <?php
        }
        ?>
    </ul>
</div>
<div style="clear:both;padding-top: 10px"><?php $this->page($page) ?></div>
