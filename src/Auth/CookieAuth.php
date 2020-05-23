<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Auth;

use Cross\I\HttpAuthInterface;
use Cross\Core\Helper;

/**
 * @author wonli <wonli@live.com>
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

    function __construct(string $key = '')
    {
        if ($key) {
            $this->key = $key;
        }
    }

    /**
     * 生成加密cookie
     *
     * @param string $name
     * @param string|array $params
     * @param int $expire
     * @return bool
     */
    function set(string $name, $params, int $expire = 0): bool
    {
        if ($params === '' || $params === null) {
            $expire = time() - 3600;
            $value = null;
        } else {
            $encryptKey = $this->getEncryptKey($name);
            if (is_array($params)) {
                $params = json_encode($params);
            }
            $value = Helper::authCode($params, 'ENCODE', $encryptKey);
            if ($expire > 0) {
                $expire = time() + $expire;
            }
        }

        $cookie_domain = null;
        if (defined('COOKIE_DOMAIN')) {
            $cookie_domain = COOKIE_DOMAIN;
        }

        return setcookie($name, $value, $expire, '/', $cookie_domain, null, true);
    }

    /**
     * 从已加密的cookie中取出值
     *
     * @param string $params cookie的key
     * @param bool $deCode
     * @return bool|string
     */
    function get(string $params, bool $deCode = false)
    {
        if (false !== strpos($params, ':') && $deCode) {
            list($name, $arrKey) = explode(':', $params);
        } else {
            $name = $params;
        }

        if (!isset($_COOKIE[$name])) {
            return false;
        }

        $value = $_COOKIE[$name];
        $encryptKey = $this->getEncryptKey($name);
        $result = Helper::authCode($value, 'DECODE', $encryptKey);
        if (!$result) {
            return false;
        }

        if ($deCode) {
            $result = json_decode($result, true);
            if (isset($arrKey) && isset($result[$arrKey])) {
                return $result[$arrKey];
            }
        }

        return $result;
    }

    /**
     * 生成密钥
     *
     * @param string $cookieName
     * @return string
     */
    protected function getEncryptKey($cookieName): string
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        } else {
            $agent = 'agent';
        }

        return md5($agent . $this->key . $cookieName);
    }
}
