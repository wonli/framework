<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Core;

use Cross\Auth\CookieAuth;
use Cross\Auth\SessionAuth;
use Cross\Exception\CoreException;
use Cross\I\HttpAuthInterface;

use ReflectionClass;
use Exception;

/**
 * @author wonli <wonli@live.com>
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
     * @param string $auth_key 指定加密key
     * @return CookieAuth|SessionAuth|HttpAuthInterface|object
     * @throws CoreException
     */
    public static function factory(string $type = 'COOKIE', string $auth_key = ''): object
    {
        if (!self::$obj) {
            if (is_string($type)) {
                if (strcasecmp($type, 'cookie') == 0) {
                    self::$obj = new CookieAuth($auth_key);
                } elseif (strcasecmp($type, 'session') == 0) {
                    self::$obj = new SessionAuth($auth_key);
                } else {
                    try {
                        $object = new ReflectionClass($type);
                        if ($object->implementsInterface('Cross\I\HttpAuthInterface')) {
                            self::$obj = $object->newInstance();
                        } else {
                            throw new CoreException('会话管理类必须实现HttpAuthInterface接口');
                        }
                    } catch (Exception $e) {
                        throw new CoreException('Reflection ' . $e->getMessage());
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
