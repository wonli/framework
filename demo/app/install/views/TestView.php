<?php
/**
 * @Auth: wonli <wonli@live.com>
 * TestView.php
 */

class TestView extends CoreView {

    /**
     * 获取测试模板
     *
     * @param $type
     * @param $data
     */
    function getTestTpl( $type, $data ) {

        $type = strtolower($type);

        foreach($data as $k => $d) {
            include $this->tpl("test/{$type}");
        }
    }


}
