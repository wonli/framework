<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.1
 */
namespace cross\core;

use cross\auth\CookieAuth;
use cross\auth\SessionAuth;

/**
 * @Auth: wonli <wonli@live.com>
 * Class HttpAuth
 * @package cross\core
 */
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







