<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Cache\Request;

use Cross\Cache\Driver\MemcacheDriver;
use Cross\I\RequestCacheInterface;

/**
 * @Auth: wonli <wonli@live.com>
 * Class RequestMemcache
 * @package Cross\Cache\Request
 */
class Memcache extends MemcacheDriver implements RequestCacheInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    static private $value_cache;

    /**
     * 初始化key和过期时间
     *
     * @param $option
     */
    function __construct(array $option)
    {
        parent::__construct($option);
        $this->setConfig($option);
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
    function getExpireTime()
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

    /**
     * 设置配置
     *
     * @param array $config
     * @return mixed
     */
    function setConfig(array $config = array())
    {
        $this->config = $config;
    }

    /**
     * 获取缓存配置
     *
     * @return mixed
     */
    function getConfig()
    {
        return $this->config;
    }
}
