<div class="pure-u-5-6">
    <form action="<?= $this->link("install:saveConfig") ?>" method="post" class="pure-form">
        <fieldset>
            <legend>更新配置文件</legend>
            <textarea name="dbConfig" id="" cols="100" rows="30"><?= $data['db_config'] ?></textarea>
            <div class="pure-u-1" style="margin-top: 30px;">
                <input type="submit" value="下一步" class="pure-button" />
            </div>
        </fieldset>
    </form>
</div>
