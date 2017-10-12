<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Cache;

use Cross\I\RequestCacheInterface;
use Cross\Exception\CoreException;
use Cross\Cache\Request\FileCache;
use Cross\Cache\Request\RedisCache;
use Cross\Cache\Request\Memcache;
use ReflectionClass;

/**
 * RequestCache工厂类
 *
 * @Auth: wonli <wonli@live.com>
 * Class RequestCache
 * @package Cross\Cache
 */
class RequestCache
{
    const FILE = 1;
    const MEMCACHE = 2;
    const REDIS = 3;

    /**
     * @var FileCache|Memcache|RedisCache|RequestCacheInterface
     */
    static $instance;

    /**
     * RequestCache
     *
     * @param int|object|string $type
     * @param array $options
     * @return FileCache|Memcache|RedisCache|RequestCacheInterface
     * @throws CoreException
     */
    static function factory($type, array $options)
    {
        switch ($type) {
            case 'file':
            case self::FILE:
                $instance = new FileCache($options);
                break;

            case 'memcache':
            case self::MEMCACHE:
                $instance = new Memcache($options);
                break;

            case 'redis':
            case self::REDIS:
                $instance = new RedisCache($options);
                break;

            default:
                if (is_string($type)) {
                    $rf = new ReflectionClass($type);
                    if ($rf->implementsInterface('Cross\I\RequestCacheInterface')) {
                        $instance = $rf->newInstance();
                    } else {
                        throw new CoreException('Must implement RequestCacheInterface');
                    }
                } elseif (is_object($type)) {
                    if ($type instanceof RequestCacheInterface) {
                        $instance = $type;
                    } else {
                        throw new CoreException('Must implement RequestCacheInterface');
                    }
                } else {
                    throw new CoreException('Unsupported cache type!');
                }
        }

        $instance->setConfig($options);
        return $instance;
    }
}
