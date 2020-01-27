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
    /**
     * 加解密默认key
     *
     * @var string
     */
    protected $key;

    function __construct($key = '')
    {
        if ($key) {
            $this->key = $key;
        }

        if (!isset($_SESSION)) {
            session_start();
        }
    }

    /**
     * 设置session的值
     *
     * @param string $key
     * @param string|array $value
     * @param int $expire
     * @return bool|mixed
     */
    function set($key, $value, $expire = 0)
    {
        if (is_array($value)) {
            $value = json_encode($value);
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
    function get($key, $deCode = false)
    {
        if (false !== strpos($key, ':') && $deCode) {
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
