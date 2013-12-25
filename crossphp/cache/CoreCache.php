<?php
/**
 * @Auth <wonli@live.com>
 * Class Cache
 */
class CoreCache
{
    static $cache_type = array(1=>'file', 2=>'memcache', 3=>'redis');

    /**
     * 实例化缓存类
     *
     * @param $cache_config
     * @return FileCache|Memcache|RedisCache
     * @throws CoreException
     */
    static function factory($cache_config)
    {
        switch($cache_config["type"])
        {
            case 1:
                return new FileCache( $cache_config );

            case 2:
                return new Memcache( $cache_config );

            case 3:
                return new RedisCache( $cache_config );

            default :
                throw new CoreException("不支持的缓存");
        }
    }
}
