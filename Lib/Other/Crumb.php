<?php
namespace Cross\Lib\Other;

class Crumb
{
    CONST SALT = "!@c#r$!o>s<s&*";
    static $ttl = 1800; //过期时间

    static public function oath_hotp($data)
    {
        return hash_hmac('crc32', $data, self::SALT);
    }

    static public function make($key, $action = -1)
    {
        $i = ceil(time() / self::$ttl);
        return substr(self::oath_hotp($i . $action . $key), -12, 10);
    }

    static public function verify($key, $crumb, $action = -1)
    {
        $i = ceil(time() / self::$ttl);
        if (substr(self::oath_hotp($i . $action . $key), -12, 10) === $crumb ||
            substr(self::oath_hotp(($i - 1) . $action . $key), -12, 10) === $crumb
        ) {
            return true;
        }

        return false;
    }
}
