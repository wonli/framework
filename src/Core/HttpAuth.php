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
use ReflectionClass;

/**
 * @Auth: wonli <wonli@live.com>
 * Class HttpAuth
 * @package Cross\Core
 */
class HttpAuth
{
    /**
     * @var CookieAuth|SessionAuth|HttpAuthInterface|object
     */
    static $obj;

    /**
     * 创建用于会话管理的对象
     *
     * @param string|object $type
     * <pre>
     *  type 默认为字符串(COOKIE|SESSION|包含命名空间的类的路径)
     *  也可以是一个实现了HttpAuthInterface接口的对象
     * </pre>
     *
     * @return CookieAuth|SessionAuth|HttpAuthInterface|object
     * @throws \Cross\Exception\CoreException
     */
    public static function factory($type = 'COOKIE')
    {
        if (! self::$obj) {
            if (is_string($type)) {
                if (strcasecmp($type, 'cookie') == 0) {
                    self::$obj = new CookieAuth();
                } elseif (strcasecmp($type, 'session') == 0) {
                    self::$obj = new SessionAuth();
                } else {
                    $object = new ReflectionClass($type);
                    if ($object->implementsInterface('Cross\I\HttpAuthInterface')) {
                        self::$obj = $object->newInstance();
                    } else {
                        throw new CoreException('会话管理类必须实现HttpAuthInterface接口');
                    }
                }
            } elseif (is_object($type)) {
                if ($type instanceof HttpAuthInterface) {
                    self::$obj = $type;
                } else {
                    throw new CoreException('会话管理类必须实现HttpAuthInterface接口');
                }
            } else {
                throw new CoreException('无法识别的会话管理类');
            }
        }
        return self::$obj;
    }
}







