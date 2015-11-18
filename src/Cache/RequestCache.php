<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Cache;

use Cross\Cache\Driver\FileCacheDriver;
use Cross\Cache\Request\RedisCache;
use Cross\Cache\Request\Memcache;
use Cross\Exception\CoreException;
use Cross\I\RequestCacheInterface;
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
    const FILE_TYPE = 1;
    const MEMCACHE_TYPE = 2;
    const REDIS_TYPE = 3;

    /**
     * @var RedisCache|Memcache|FileCacheDriver|RequestCacheInterface
     */
    static $instance;

    /**
     * RequestCache
     *
     * @param int|object|string $cache_type
     * @param array $cache_config
     * @return FileCacheDriver|Memcache|RedisCache|RequestCacheInterface|object
     * @throws CoreException
     */
    static function factory($cache_type, array $cache_config)
    {
        if (!self::$instance) {
            if (is_int($cache_type)) {
                switch ($cache_type) {
                    case self::FILE_TYPE:
                        self::$instance = new FileCacheDriver($cache_config);
                        break;

                    case self::MEMCACHE_TYPE:
                        self::$instance = new Memcache($cache_config);
                        break;

                    case self::REDIS_TYPE:
                        self::$instance = new RedisCache($cache_config);
                        break;

                    default :
                        throw new CoreException('不支持的缓存类型');
                }
            } elseif (is_object($cache_type)) {
                if ($cache_type instanceof RequestCacheInterface) {
                    self::$instance = $cache_type;
                    self::$instance->setConfig($cache_config);
                } else {
                    throw new CoreException('Request Cache必须实现RequestCacheInterface');
                }
            } elseif (is_string($cache_type)) {
                $object = new ReflectionClass($cache_type);
                if ($object->implementsInterface('Cross\I\RequestCacheInterface')) {
                    self::$instance = $object->newInstance();
                    self::$instance->setConfig($cache_config);
                } else {
                    throw new CoreException('Request Cache必须实现RequestCacheInterface');
                }
            } else {
                throw new CoreException('不能识别的缓存类型');
            }
        }

        return self::$instance;
    }
}
