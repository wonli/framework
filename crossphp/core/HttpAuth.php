<?php defined('CROSSPHP_PATH')or die('Access Denied');

/**
 * @Author: wonli <wonli@live.com>
 */

interface iHttpAuth {
    public function set($key, $value, $exp = 86400);
    public function get($key, $de = false);
}

/**
 * cookie保存登录状态
 * Class CookieAuth
 */
class CookieAuth implements iHttpAuth
{
    /**
     * @var string 加解密key
     */
    private $key = "!wl<@>c(r#%o*s&s";

    /**
     * 生成密钥 用户ip+浏览器AGENT+key+params
     *
     * @param $params 值
     * @return string md5 字符串
     */
    function cookieKey($params)
    {
        return md5(Helper::getIp().$_SERVER ["HTTP_USER_AGENT"].$this->key.$params);
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
        if(is_array($params)) {
            $_cookie = $this->get($name, true);

            if($_cookie)
            {
                if(is_array($_cookie) && !empty($_cookie)) {
                    $params = array_merge($_cookie, $params);
                } else {
                    $params [] = $_cookie;
                }
                array_filter($params);
            }

            $params = json_encode($params);
        }

        $str = Helper::authcode($params, "ENCODE", $key);
        $exp = time() + $exp;

        if( setcookie($name, $str, $exp, "/", COOKIE_DOMAIN) ) return true;
        else return false;
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
        if(false !== strpos($params, ":")) {
            list($vkey, $ckey) = explode(":", $params);
            $dejson = true;
        } else {
            $vkey = $params;
        }

        if( isset($_COOKIE [$vkey]) ) {
            $str = $_COOKIE [$vkey];
        } else {
            return false;
        }

        $key = $this->cookieKey($vkey);
        $result = Helper::authcode($str, "DECODE", $key);

        if(! $result) return false;
        if( $dejson || $de ) {
            $result = json_decode($result, true);
            if( ! empty($ckey) && !empty($result [$ckey]) ) {
                return $result[$ckey];
            }
            return $result;
        }
        return $result;
    }
}


/**
 * session保存登录状态
 *
 * Class SessionAuth
 */
class SessionAuth implements iHttpAuth
{
    function __construct()
    {
        if(empty($_SESSION)) session_start();
    }

    function set($key, $value, $exp = 86400)
    {
        $_SESSION[$key] = $value;
        return true;
    }

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

class HttpAuth
{
    static $obj;
    public static function factory($type = 'COOKIE')
    {
        $type = strtoupper($type);
        switch($type)
        {
            case 'COOKIE' :
                self::$obj = new CookieAuth();
                break;

            case 'SESSION' :
                self::$obj = new SessionAuth();
                break;

            default:
                self::$obj = new CookieAuth();
                break;
        }

        return self::$obj;
    }
}







