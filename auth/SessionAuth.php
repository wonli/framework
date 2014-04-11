<?php
/**
 * @Auth: wonli <wonli@live.com>
 * SessionAuth.php
 */

class SessionAuth implements HttpAuthInterface
{
    function __construct()
    {
        if(empty($_SESSION)) session_start();
    }

    /**
     * 设置cookie的值
     *
     * @param     $key
     * @param     $value
     * @param int $exp
     * @return bool
     */
    function set($key, $value, $exp = 86400)
    {
        $_SESSION[$key] = $value;
        return true;
    }

    /**
     * 获取cookie的值
     *
     * @param      $key
     * @param bool $de
     * @return mixed
     */
    function get($key, $de = false)
    {
        if(false !== strpos($key, ":")) {
            list($vkey, $ckey) = explode(":", $key);
        } else {
            $vkey = $key;
        }

        $_result = $_SESSION[$vkey];

        if( !empty($ckey) && isset($_result[$ckey]) ) return $_result[$ckey];
        else return $_result;
    }
}
