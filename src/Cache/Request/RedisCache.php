<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Cache\Request;

use Cross\Cache\Driver\RedisDriver;
use Cross\Exception\CoreException;
use Cross\I\RequestCacheInterface;

/**
 * @Auth: wonli <wonli@live.com>
 * Class RequestRedisCache
 * @package Cross\Cache\Request
 */
class RedisCache extends RedisDriver implements RequestCacheInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * 缓存key
     *
     * @var string
     */
    protected $cache_key;

    /**
     * 有效时间
     *
     * @var int
     */
    protected $expire_time;

    /**
     * 设置缓存key和缓存有效期
     *
     * @param array $option
     * @throws CoreException
     */
    function __construct(array $option)
    {
        parent::__construct($option);
        $this->setConfig($option);
        if (empty($option['key']) || empty($option['expire_time'])) {
            throw new CoreException('请指定缓存key和过期时间');
        }

        $this->cache_key = $option ['key'];
        $this->expire_time = $option ['expire_time'];
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
        $this->link->setex($this->cache_key, $this->expire_time, $value);
    }

    /**
     * 检查缓存key是否有效
     *
     * @return bool
     */
    function isValid()
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
