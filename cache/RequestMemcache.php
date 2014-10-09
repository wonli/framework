<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.3
 */
namespace Cross\Cache;

use Cross\I\CacheInterface;

/**
 * @Auth: wonli <wonli@live.com>
 * Class RequestMemcache
 * @package Cross\Cache
 */
class RequestMemcache extends MemcacheBase implements CacheInterface
{
    static private $value_cache;

    function __construct($option)
    {
        parent::__construct($option);
        $this->cache_key = $option ['key'];
        $this->expire = time() + $option ['expire_time'];
    }

    /**
     * 获取request缓存
     *
     * @param string $key
     * @return array|mixed|string
     */
    function get($key = '')
    {
        if (self::$value_cache[$this->cache_key]) {
            return self::$value_cache[$this->cache_key];
        }

        if (!$key) {
            $key = $this->cache_key;
        }

        return $this->link->get($key);
    }

    /**
     * 设置request缓存
     *
     * @param $key
     * @param $value
     * @return mixed set
     */
    function set($key, $value)
    {
        if (!$key) {
            $key = $this->cache_key;
        }

        $this->link->set($key, $value, $this->expire);
    }

    /**
     * 查看key是否有效
     *
     * @return bool
     */
    function getExtime()
    {
        if (isset(self::$value_cache[$this->cache_key])) {
            return true;
        }

        $value = $this->link->get($this->cache_key);
        if (!empty($value)) {
            self::$value_cache[$this->cache_key] = $value;
            return true;
        }

        return false;
    }
}
