<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Cache;

use Cross\I\RequestCacheInterface;

use Cross\Cache\Request\FileCache;
use Cross\Cache\Request\RedisCache;
use Cross\Cache\Request\Memcache;

use Cross\Exception\CoreException;
use RedisClusterException;
use RedisException;
use ReflectionClass;
use Exception;

/**
 * RequestCache工厂类
 *
 * @author wonli <wonli@live.com>
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
    static FileCache|RedisCache|RequestCacheInterface|Memcache $instance;

    /**
     * RequestCache
     *
     * @param object|int|string $type
     * @param array $options
     * @return FileCache|Memcache|RedisCache|RequestCacheInterface
     * @throws CoreException
     * @throws RedisClusterException
     * @throws RedisException
     */
    static function factory(object|int|string $type, array $options): Memcache|RequestCacheInterface|RedisCache|FileCache
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
                    try {
                        $rf = new ReflectionClass($type);
                        if ($rf->implementsInterface('Cross\I\RequestCacheInterface')) {
                            $instance = $rf->newInstance();
                        } else {
                            throw new CoreException('Must implement RequestCacheInterface');
                        }
                    } catch (Exception $e) {
                        throw new CoreException('Reflection ' . $e->getMessage());
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
