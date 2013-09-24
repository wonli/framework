<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Auth <wonli@live.com>
 *
 * Class Cache
 */
class CoreCache
{
    static $cache_type = array(1=>'file', 2=>'memcache', 3=>'redis');

    static function create($cache_config, $cache_key)
    {
        switch($cache_config["type"])
        {
            case 1:
                return new FileCache($cache_key, $cache_config["extime"] );

            case 2:
                return new Memcache($cache_key);

            case 3:
                return new RedisCache( );

            default :
                throw new CacheException("不支持的缓存");
        }
    }

    static function factory($cache_type, $cache_config)
    {
        switch($cache_type)
        {
            case 'redis' :
                $obj = new RedisCache($cache_config);
        }

        return $obj;
    }
}