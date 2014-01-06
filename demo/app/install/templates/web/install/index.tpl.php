<div class="pure-u-5-6">
    <form class="pure-form" action="<?= $this->link("install:config") ?>" method="POST">
        <fieldset>
            <legend>安装协议</legend>

            <div class="pure-u-1">
                <textarea class="pure-form-message" id="notes" cols="100" rows="30"><?= $data['agreement'] ?></textarea>
            </div>

            <div class="pure-u-1" style="margin-top: 30px;">
                <input type="submit" value="下一步" class="pure-button" />
            </div>
        </fieldset>
    </form>
</div>
