<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class RequestCache
 */
namespace cross\cache;

use cross\exception\CoreException;

class RequestCache extends CoreCache
{
    /**
     * 请求url缓存
     *
     * @param $cache_config
     * @return FileCache|MemcacheBase|RedisCache|RequestMemcache|RequestRedisCache
     * @throws CoreException
     */
    static function factory($cache_config)
    {
        switch ($cache_config["type"]) {
            case 1:
                return new FileCache($cache_config);

            case 2:
                return new RequestMemcache($cache_config);

            case 3:
                return new RequestRedisCache($cache_config);

            default :
                throw new CoreException("不支持的缓存");
        }
    }
}
