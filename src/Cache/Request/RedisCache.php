<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Cache\Request;

use Cross\I\RequestCacheInterface;
use Cross\Cache\Driver\RedisDriver;
use Cross\Exception\CoreException;
use RedisClusterException;
use RedisException;

/**
 * @author wonli <wonli@live.com>
 * Class RequestRedisCache
 * @package Cross\Cache\Request
 */
class RedisCache implements RequestCacheInterface
{
    /**
     * @var array
     */
    protected array $config;

    /**
     * 缓存key
     *
     * @var string
     */
    protected string $cacheKey;

    /**
     * 有效时间
     *
     * @var int
     */
    protected int $expireTime = 3600;

    /**
     * @var bool
     */
    protected bool $compress = false;

    /**
     * @var RedisDriver
     */
    protected RedisDriver $driver;

    /**
     * 设置缓存key和缓存有效期
     *
     * @param array $option
     * @throws CoreException
     * @throws RedisClusterException
     * @throws RedisException
     */
    function __construct(array $option)
    {
        if (empty($option['key'])) {
            throw new CoreException('请指定缓存KEY');
        }

        if (isset($option['expire_time'])) {
            $this->expireTime = &$option['expire_time'];
        }

        if (isset($option['compress'])) {
            $this->compress = &$option['compress'];
        }

        $this->cacheKey = &$option['key'];
        $this->driver = new RedisDriver($option);
    }

    /**
     * 设置request请求
     *
     * @param string $value
     */
    function set(string $value): void
    {
        if ($this->compress) {
            $value = gzcompress($value);
        }

        $this->driver->setex($this->cacheKey, $this->expireTime, $value);
    }

    /**
     * 返回request的内容
     *
     * @return string
     */
    function get(): string
    {
        $content = $this->driver->get($this->cacheKey);
        if ($this->compress) {
            $content = gzuncompress($content);
        }

        return $content;
    }

    /**
     * 检查缓存key是否有效
     *
     * @return bool
     */
    function isValid(): bool
    {
        return $this->driver->ttl($this->cacheKey) > 0;
    }

    /**
     * 设置配置
     *
     * @param array $config
     */
    function setConfig(array $config = []): void
    {
        $this->config = $config;
    }

    /**
     * 获取缓存配置
     *
     * @return mixed
     */
    function getConfig(): array
    {
        return $this->config;
    }
}
