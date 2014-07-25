<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class HttpAuth
 */
namespace cross\core;

use cross\auth\CookieAuth;
use cross\auth\SessionAuth;

class HttpAuth
{
    /**
     * @var object
     */
    static $obj;

    public static function factory($type = 'COOKIE')
    {
        $type = strtoupper($type);
        switch ($type) {
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







