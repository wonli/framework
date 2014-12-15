<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.6
 */
namespace Cross\Auth;

use Cross\I\HttpAuthInterface;

/**
 * @Auth: wonli <wonli@live.com>
 * Class SessionAuth
 * @package Cross\Auth
 */
class SessionAuth implements HttpAuthInterface
{
    function __construct()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
    }

    /**
     * 设置session的值
     *
     * @param $key
     * @param $value
     * @param int $exp
     * @return bool|mixed
     */
    function set($key, $value, $exp = 86400)
    {
        $_SESSION[$key] = $value;

        return true;
    }

    /**
     * 获取session的值
     *
     * @param $key
     * @param bool $de
     * @return mixed
     */
    function get($key, $de = false)
    {
        if (false !== strpos($key, ':')) {
            list($v_key, $c_key) = explode(':', $key);
        } else {
            $v_key = $key;
        }

        $_result = $_SESSION[$v_key];
        if (!empty($c_key) && isset($_result[$c_key])) {
            return $_result[$c_key];
        }

        return $_result;
    }
}
