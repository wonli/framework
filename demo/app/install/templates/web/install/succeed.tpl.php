<div class="pure-u-1">
    <div class="pure-u-5-6" style="margin-top: 50px;"> </div>

    <div class="pure-u-5-6">
        <form action="<?php echo $this->link("install/createAdmin") ?>" method="post" class="pure-form pure-form-aligned">
            <fieldset>
                <legend>Succeed 创建管理员账户</legend>

                <div class="pure-control-group">
                    <label for="name">用户名</label>
                    <input id="name" name="name" type="text" placeholder="Username">
                </div>

                <div class="pure-control-group">
                    <label for="password">密码</label>
                    <input id="password" name="password" type="password" placeholder="Password">
                </div>

                <div class="pure-controls">
                    <label for="cb" class="pure-checkbox">
                    </label>
                    <button type="submit" class="pure-button pure-button-primary">Submit</button>
                </div>
            </fieldset>
        </form>
    </div>

</div>
