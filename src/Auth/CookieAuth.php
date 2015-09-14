<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.4.1
 */
namespace Cross\Auth;

use Cross\Core\Helper;
use Cross\I\HttpAuthInterface;

/**
 * @Auth: wonli <wonli@live.com>
 * Class CookieAuth
 * @package cross\auth
 */
class CookieAuth implements HttpAuthInterface
{
    /**
     * 加解密默认key
     *
     * @var string
     */
    private $key = '!wl<@>c(r#%o*s&s';

    function __construct($key = '')
    {
        if ($key) {
            $this->key = $key;
        }
    }

    /**
     * 生成加密cookie
     *
     * @param $name
     * @param $params
     * @param int $exp
     * @return bool|mixed
     */
    function set($name, $params, $exp = 86400)
    {
        $key = $this->cookieKey($name);
        if (is_array($params)) {
            $params = json_encode($params);
        }

        $str = Helper::authCode($params, 'ENCODE', $key);
        $expire_time = time() + $exp;

        $cookie_domain = null;
        if (defined('COOKIE_DOMAIN')) {
            $cookie_domain = COOKIE_DOMAIN;
        }

        if (setcookie($name, $str, $expire_time, '/', $cookie_domain, null, true)) {
            return true;
        }

        return false;
    }

    /**
     * 生成加密COOKIE的密钥 用户ip.浏览器AGENT.key.params
     *
     * @param $params
     * @return string
     */
    protected function cookieKey($params)
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        } else {
            $agent = 'agent';
        }

        return md5($agent . $this->key . $params);
    }

    /**
     * 从已加密的cookie中取出值
     *
     * @param string $params cookie的key
     * @param bool $de
     * @return bool|mixed|string
     */
    function get($params, $de = false)
    {
        $de_json = false;
        if (false !== strpos($params, ':')) {
            list($v_key, $c_key) = explode(':', $params);
            $de_json = true;
        } else {
            $v_key = $params;
        }

        if (isset($_COOKIE [$v_key])) {
            $str = $_COOKIE [$v_key];
        } else {
            return false;
        }

        $key = $this->cookieKey($v_key);
        $result = Helper::authCode($str, 'DECODE', $key);

        if (!$result) {
            return false;
        }

        if ($de_json || $de) {
            $result = json_decode($result, true);
            if (!empty($c_key) && !empty($result [$c_key])) {
                return $result[$c_key];
            }

            return $result;
        }

        return $result;
    }
}
