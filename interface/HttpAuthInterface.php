<?php
/**
 * @Auth: wonli <wonli@live.com>
 * HttpAuthInterface.php
 */
interface HttpAuthInterface {

    /**
     * 设置
     *
     * @param     $key
     * @param     $value
     * @param int $exp
     * @return mixed
     */
    public function set($key, $value, $exp = 86400);

    /**
     * 获取
     *
     * @param      $key
     * @param bool $de
     * @return mixed
     */
    public function get($key, $de = false);
}
