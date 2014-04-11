<?php
/**
 * @Auth: wonli <wonli@live.com>
 * CookieAuth.php
 */

class CookieAuth implements HttpAuthInterface
{
    /**
     * @var string
     */
    private $key;

    /**
     * 加解密默认key
     *
     * @var string
     */
    private $default_key = "!wl<@>c(r#%o*s&s";

    /**
     * 生成密钥 用户ip+浏览器AGENT+key+params
     *
     * @param $params 值
     * @return string md5 字符串
     */
    protected function cookieKey($params)
    {
        if (isset($_SERVER['HTTP_USER_AGENT']))
        {
            $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        } else {
            $agent = 'agent';
        }

        return sha1(Helper::getIp().$agent.$this->key.$params);
    }

    /**
     * 设置key
     *
     * @param $key
     */
    protected function setKey( $key )
    {
        $this->key = $key;
    }

    /**
     * 设置cookie的key
     *
     * @param $key
     * @return string
     */
    protected function getKey( $key )
    {
        if (! $this->key)
        {
            $this->key = $this->default_key;
        }

        return $this->key;
    }

    /**
     * 生成加密后的cookie
     *
     * @param $name cookie的key
     * @param $params cookie的值
     * @param int $exp 过期时间
     * @return bool
     */
    function set($name, $params, $exp = 86400)
    {
        $key = $this->cookieKey($name);
        if (is_array($params)) {
            $_cookie = $this->get($name, true);

            if ($_cookie)
            {
                if (is_array($_cookie) && !empty($_cookie)) {
                    $params = array_merge($_cookie, $params);
                } else {
                    $params [] = $_cookie;
                }
                array_filter($params);
            }

            $params = json_encode($params);
        }

        $str = Helper::authcode($params, "ENCODE", $key);
        $expire_time = time() + $exp;

        $cookie_domain = null;
        if (defined(COOKIE_DOMAIN))
        {
            $cookie_domain = COOKIE_DOMAIN;
        }

        if ( setcookie($name, $str, $expire_time, "/", $cookie_domain, null, true) )
        {
            return true;
        }

        return false;
    }

    /**
     * 从已加密的cookie中取出值
     *
     * @param $params cookie的key
     * @param bool $de
     * @return bool|mixed|string
     */
    function get($params, $de = false)
    {
        $dejson = false;
        if (false !== strpos($params, ":")) {
            list($vkey, $ckey) = explode(":", $params);
            $dejson = true;
        } else {
            $vkey = $params;
        }

        if ( isset($_COOKIE [$vkey]) ) {
            $str = $_COOKIE [$vkey];
        } else {
            return false;
        }

        $key = $this->cookieKey($vkey);
        $result = Helper::authcode($str, "DECODE", $key);

        if (! $result) return false;

        if ( $dejson || $de ) {
            $result = json_decode($result, true);
            if ( ! empty($ckey) && !empty($result [$ckey]) ) {
                return $result[$ckey];
            }
            return $result;
        }
        return $result;
    }
}
