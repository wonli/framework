<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.6
 */
namespace Cross\Core;

use Cross\Auth\CookieAuth;
use Cross\Auth\SessionAuth;
use Cross\Exception\CoreException;
use Cross\I\HttpAuthInterface;

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

    /**
     * 创建用于会话管理的对象
     *
     * @param string|object $type
     * <pre>
     *  type 默认为字符串(COOKIE)
     *  也可以是一个实现了HttpAuthInterface接口的对象
     * </pre>
     * @return CookieAuth|SessionAuth|HttpAuthInterface|object
     * @throws \Cross\Exception\CoreException
     */
    public static function factory($type = 'COOKIE')
    {
        if (! self::$obj) {
            if (is_string($type)) {
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
            } elseif (is_object($type)) {
                if ($type instanceof HttpAuthInterface) {
                    self::$obj = $type;
                } else {
                    throw new CoreException('必须实现HttpAuthInterface接口');
                }
            }
        }
        return self::$obj;
    }
}







