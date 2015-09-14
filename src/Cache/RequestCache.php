<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.4.1
 */
namespace Cross\Cache;

use Cross\Exception\CoreException;
use Cross\I\RequestCacheInterface;
use ReflectionClass;

/**
 * @Auth: wonli <wonli@live.com>
 * Class RequestCache
 * @package Cross\Cache
 */
class RequestCache extends CoreCache
{
    /**
     * @var FileCache|MemcacheBase|RedisCache|RequestMemcache|RequestRedisCache|RequestCacheInterface|object
     */
    static $cache_object;

    /**
     * 获取处理Request Cache的类实例
     *
     * @param array $cache_config
     * @return FileCache|MemcacheBase|RedisCache|RequestMemcache|RequestRedisCache|RequestCacheInterface|object
     * @throws \Cross\Exception\CoreException
     */
    static function factory($cache_config)
    {
        $cache = $cache_config['type'];
        if (!self::$cache_object) {
            if (is_int($cache)) {
                switch ($cache) {
                    case 1:
                        self::$cache_object = new FileCache($cache_config);
                        break;

                    case 2:
                        self::$cache_object = new RequestMemcache($cache_config);
                        break;

                    case 3:
                        self::$cache_object = new RequestRedisCache($cache_config);
                        break;

                    default :
                        throw new CoreException('不支持的缓存');
                }
            } elseif (is_object($cache)) {
                if ($cache instanceof RequestCacheInterface) {
                    self::$cache_object = $cache;
                    self::$cache_object->setConfig($cache_config);
                } else {
                    throw new CoreException('Request Cache必须实现RequestCacheInterface');
                }
            } elseif (is_string($cache)) {
                $object = new ReflectionClass($cache);
                if ($object->implementsInterface('Cross\I\RequestCacheInterface')) {
                    self::$cache_object = $object->newInstance();
                    self::$cache_object->setConfig($cache_config);
                } else {
                    throw new CoreException('Request Cache必须实现RequestCacheInterface');
                }
            } else {
                throw new CoreException('不支持的缓存类型');
            }
        }

        return self::$cache_object;
    }
}
