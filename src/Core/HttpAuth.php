<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Core;

use Cross\Auth\CookieAuth;
use Cross\Auth\RedisAuth;
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
     * @var HttpAuthInterface|object
     */
    static $authHandler;

    /**
     * 创建用于会话管理的对象
     *
     * @param string|object $type
     * <pre>
     *  type 默认为字符串(COOKIE|SESSION|REDIS|包含命名空间的类的路径)
     *  也可以是一个实现了HttpAuthInterface接口的对象
     * </pre>
     * @param string $authKey 加解密密钥
     * @return HttpAuthInterface|object
     * @throws CoreException
     */
    public static function factory($type = 'cookie', string $authKey = ''): object
    {
        if (!self::$authHandler) {
            if (is_string($type)) {
                if (strcasecmp($type, 'cookie') == 0) {
                    self::$authHandler = new CookieAuth($authKey);
                } elseif (strcasecmp($type, 'session') == 0) {
                    self::$authHandler = new SessionAuth($authKey);
                } elseif (strcasecmp($type, 'redis') == 0) {
                    self::$authHandler = new RedisAuth($authKey);
                } else {
                    try {
                        $object = new ReflectionClass($type);
                        if ($object->implementsInterface('Cross\I\HttpAuthInterface')) {
                            self::$authHandler = $object->newInstance();
                        } else {
                            throw new CoreException('The auth class must implement the HttpAuthInterface interface.');
                        }
                    } catch (Exception $e) {
                        throw new CoreException('Reflection ' . $e->getMessage());
                    }
                }
            } elseif (is_object($type)) {
                if ($type instanceof HttpAuthInterface) {
                    self::$authHandler = $type;
                } else {
                    throw new CoreException('The auth class must implement the HttpAuthInterface interface.');
                }
            } else {
                throw new CoreException('Unrecognized auth classes!');
            }
        }
        return self::$authHandler;
    }
}
