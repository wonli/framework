<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Auth;

use Cross\I\HttpAuthInterface;

/**
 * @author wonli <wonli@live.com>
 * Class SessionAuth
 * @package Cross\Auth
 */
class SessionAuth implements HttpAuthInterface
{
    function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * 设置session的值
     *
     * @param string $key
     * @param array|string $value
     * @param int $expire
     * @return bool
     */
    function set(string $key, array|string $value, int $expire = 0): bool
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $_SESSION[$key] = $value;
        return true;
    }

    /**
     * 获取session的值
     *
     * @param string $key
     * @param bool $deCode
     * @return bool|mixed
     */
    function get(string $key, bool $deCode = false): mixed
    {
        if (str_contains($key, ':') && $deCode) {
            list($key, $arrKey) = explode(':', $key);
        }

        if (!isset($_SESSION[$key])) {
            return false;
        }

        $result = $_SESSION[$key];
        if ($deCode) {
            $result = json_decode($result, true);
            if (isset($arrKey) && isset($result[$arrKey])) {
                return $result[$arrKey];
            }
        }

        return $result;
    }
}
