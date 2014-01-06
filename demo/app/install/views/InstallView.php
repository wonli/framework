<?php
/**
 * @Auth: wonli <wonli@live.com>
 * BaseView.php
 */

class InstallView extends CoreView {

    /**
     * @param array $data
     */
    function index( $data = array() ) {
        include $this->tpl("install/index");
    }

    /**
     * @param array $data
     */
    function config( $data = array() ) {
        include $this->tpl("install/config");
    }

    /**
     * @param array $data
     */
    function test( $data = array() ) {
        $TEST = new TestView();
        include $this->tpl("install/test");
    }

    /**
     * 导入数据失败时的模板
     */
    function importData() {
        include $this->tpl("install/result");
    }

    /**
     * 导入数据成后提示生成管理员账户
     */
    function succeed() {
        include $this->tpl("install/succeed");
    }

    /**
     * 安装结束后的导航页
     */
    function endNav() {
        include $this->tpl("install/nav");
    }
}
