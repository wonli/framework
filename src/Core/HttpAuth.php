<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.5
 */
namespace Cross\Core;

use Cross\Auth\CookieAuth;
use Cross\Auth\SessionAuth;

/**
 * @Auth: wonli <wonli@live.com>
 * Class HttpAuth
 * @package Cross\Core
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







