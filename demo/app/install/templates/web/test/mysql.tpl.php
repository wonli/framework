<div class="pure-u-1">
    <div class="pure-u-5-6">
        <form action="" class="pure-form pure-form-aligned">
            <fieldset>
                <legend>MySQL数据库 <?= $k ?></legend>
                <div class="pure-control-group">
                    <label for="host">主机IP</label>
                    <input type="text" id="host" value="<?= $d['host'] ?>" readonly/>
                </div>

                <div class="pure-control-group">
                    <label for="port">主机端口</label>
                    <input type="text" id="port" value="<?= $d['port'] ?>" readonly/>
                </div>

                <div class="pure-control-group">
                    <label for="user">用户名</label>
                    <input type="text" id="user" value="<?= $d['user'] ?>" readonly/>
                </div>

                <div class="pure-control-group">
                    <label for="pass">密码</label>
                    <input type="text" id="pass" value="<?= $d['pass'] ?>" readonly/>
                </div>

                <div class="pure-control-group">
                    <label for="name">数据库名</label>
                    <input type="text" id="name" value="<?= $d['name'] ?>" readonly/>
                </div>

                <div class="pure-control-group">
                    <label for="charset">字符集</label>
                    <input type="text" id="charset" value="<?= $d['charset'] ?>" readonly/>
                </div>

                <div class="pure-control-group">
                    <label for="button"></label>
                    <input type="button" id="button" class="pure-button pure-button-primary" value="测试"/>
                </div>
            </fieldset>
        </form>
    </div>
</div>
