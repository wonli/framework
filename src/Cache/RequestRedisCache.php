<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.3
 */
namespace Cross\Cache;

use Cross\I\RequestCacheInterface;

/**
 * @Auth: wonli <wonli@live.com>
 * Class RequestRedisCache
 * @package Cross\Cache
 */
class RequestRedisCache extends RedisCache implements RequestCacheInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * 设置缓存key和缓存有效期
     *
     * @param $option
     */
    function __construct($option)
    {
        parent::__construct($option);
        $this->setConfig($option);
        $this->cache_key = $option ['key'];
        $this->key_ttl = $option ['expire_time'];
    }

    /**
     * 设置request请求
     *
     * @param $key
     * @param $value
     * @return mixed|void
     */
    function set($key, $value)
    {
        $this->link->setex($this->cache_key, $this->key_ttl, $value);
    }

    /**
     * 检查缓存key是否有效
     *
     * @return bool
     */
    function getExpireTime()
    {
        return $this->link->ttl($this->cache_key) > 0;
    }

    /**
     * 返回request的内容
     *
     * @param string $key
     * @return bool|mixed|string
     */
    function get($key = '')
    {
        if (!$key) {
            $key = $this->cache_key;
        }

        return $this->link->get($key);
    }

    /**
     * 设置配置
     *
     * @param array $config
     * @return mixed
     */
    function setConfig($config = array())
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
