<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Cache\Request;

use Cross\I\RequestCacheInterface;
use Cross\Cache\Driver\MemcacheDriver;
use Cross\Exception\CoreException;

/**
 * @author wonli <wonli@live.com>
 * Class RequestMemcache
 * @package Cross\Cache\Request
 */
class Memcache implements RequestCacheInterface
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
     * @var int
     */
    protected int $flag = 0;

    /**
     * @var MemcacheDriver
     */
    protected MemcacheDriver $driver;

    /**
     * Memcache constructor.
     *
     * @param array $option
     * @throws CoreException
     */
    function __construct(array $option)
    {
        if (empty($option['key'])) {
            throw new CoreException('请指定缓存KEY');
        }

        $this->cacheKey = &$option['key'];
        $this->driver = new MemcacheDriver($option);

        if (isset($option['flag']) && $option['flag']) {
            $this->flag = &$option['flag'];
        }

        if (isset($option['expire_time'])) {
            $this->expireTime = &$option['expire_time'];
        }
    }

    /**
     * 设置request缓存
     *
     * @param string $value
     */
    function set(string $value): void
    {
        $this->driver->set($this->cacheKey, $value, $this->flag, $this->expireTime);
    }

    /**
     * 获取request缓存
     *
     * @param int $flag
     * @return string
     */
    function get(int &$flag = 0): string
    {
        return $this->driver->get($this->cacheKey, $flag);
    }

    /**
     * 查看key是否有效
     *
     * @return bool
     */
    function isValid(): bool
    {
        if ($this->driver->get($this->cacheKey)) {
            return true;
        }

        return false;
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
