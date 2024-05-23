<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Auth;

use Cross\I\HttpAuthInterface;
use Cross\Http\Response;
use Cross\Core\Delegate;
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
    private string $authKey = '!wl<@>c(r#%o*s&s';

    function __construct(string $authKey = '')
    {
        if ($authKey) {
            $this->authKey = $authKey;
        }
    }

    /**
     * 生成加密cookie
     *
     * @param string $key
     * @param array|string $value
     * @param int $expire
     * @return bool
     */
    function set(string $key, array|string $value, int $expire = 0): bool
    {
        if ($value === '' || $value === null) {
            $expire = time() - 3600;
            $val = null;
        } else {
            $encryptKey = $this->getEncryptKey($key);
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $val = Helper::authCode($value, 'ENCODE', $encryptKey);
            if ($expire > 0) {
                $expire = time() + $expire;
            }
        }

        Response::getInstance()->setRawCookie($key, $val, $expire, '/', Delegate::env('cookie.domain') ?? '');
        return true;
    }

    /**
     * 从已加密的cookie中取出值
     *
     * @param string $key cookie的key
     * @param bool $deCode
     * @return bool|string
     */
    function get(string $key, bool $deCode = false): bool|string
    {
        if (str_contains($key, ':') && $deCode) {
            list($name, $arrKey) = explode(':', $key);
        } else {
            $name = $key;
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
    protected function getEncryptKey(string $cookieName): string
    {
        return md5($cookieName . $this->authKey);
    }
}
