<div class="pure-u-5-6">
    <?php
    foreach($data['config'] as $db_type => $db_conf) {
        $TEST->getTestTpl( $db_type, $db_conf );
    }
    ?>
</div>

<div class="pure-u-5-6" style="padding-bottom:20px;margin-bottom: 20px;">
    <form action="<?php echo $this->link("install:importData") ?>" method="post">
        <input type="submit" id="submit" class="pure-button pure-button-selected" value="下一步"/>
    </form>
</div>
