<div class="pure-u-1">
    <div class="pure-u-5-6">
        <form action="" class="pure-form pure-form-aligned">
            <fieldset>
                <legend>Redis <?= $k ?></legend>
                <div class="pure-control-group">
                    <label for="host">主机IP</label>
                    <input type="text" id="host" value="<?= $d['host'] ?>" readonly/>
                </div>

                <div class="pure-control-group">
                    <label for="port">端口</label>
                    <input type="text" id="port" value="<?= $d['port'] ?>" readonly/>
                </div>

                <div class="pure-control-group">
                    <label for="auth">auth</label>
                    <input type="text" id="auth" value="<?= $d['auth'] ?>" readonly/>
                </div>

                <div class="pure-control-group">
                    <label for="timeout">timeout</label>
                    <input type="text" id="timeout" value="<?= $d['timeout'] ?>" readonly/>
                </div>

                <div class="pure-control-group">
                    <label for="db">db</label>
                    <input type="text" id="db" value="<?= $d['db'] ?>" readonly/>
                </div>

                <div class="pure-control-group">
                    <label for="button"></label>
                    <input type="button" id="button" class="pure-button pure-button-primary" value="测试"/>
                </div>
            </fieldset>
        </form>
    </div>
</div>
