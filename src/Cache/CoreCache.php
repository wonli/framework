<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.6
 */
namespace Cross\Cache;

use Cross\Exception\CoreException;

/**
 * @Auth: wonli <wonli@live.com>
 * Class CoreCache
 * @package Cross\Cache
 */
class CoreCache
{
    /**
     * 缓存类型
     *
     * @var array
     */
    static $cache_type = array(1 => 'file', 2 => 'memcache', 3 => 'redis');

    /**
     * 实例化缓存类
     *
     * @param $cache_config
     * @return FileCache|MemcacheBase|RedisCache
     * @throws CoreException
     */
    static function factory($cache_config)
    {
        switch ($cache_config['type']) {
            case 1:
                return new FileCache($cache_config);

            case 2:
                return new MemcacheBase($cache_config);

            case 3:
                return new RedisCache($cache_config);

            default :
                throw new CoreException('不支持的缓存');
        }
    }
}
