<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Cache\Request;

use Cross\I\RequestCacheInterface;
use Cross\Cache\Driver\FileCacheDriver;
use Cross\Exception\CoreException;

/**
 * @author wonli <wonli@live.com>
 *
 * Class FileCache
 * @package Cross\Cache\Request
 */
class FileCache implements RequestCacheInterface
{
    /**
     * @var string
     */
    private string $cacheKey;

    /**
     * @var string
     */
    private string $cachePath;

    /**
     * @var mixed
     */
    private array $config;

    /**
     * 缓存过期时间(秒)
     *
     * @var int
     */
    private int $expireTime = 3600;

    /**
     * @var FileCacheDriver
     */
    private FileCacheDriver $driver;

    /**
     * FileCache constructor.
     *
     * @param array $config
     * @throws CoreException
     */
    function __construct(array $config)
    {
        if (!isset($config['cache_path'])) {
            throw new CoreException('请指定缓存目录');
        }

        if (!isset($config['key'])) {
            throw new CoreException('请指定缓存KEY');
        }

        if (isset($config['expire_time'])) {
            $this->expireTime = &$config['expire_time'];
        }

        $this->cacheKey = &$config['key'];

        $this->cachePath = rtrim($config['cache_path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->driver = new FileCacheDriver($this->cachePath);
    }

    /**
     * 写入缓存
     *
     * @param string $value
     * @throws CoreException
     */
    function set(string $value): void
    {
        $this->driver->set($this->cacheKey, $value);
    }

    /**
     * 获取缓存内容
     *
     * @return mixed get cache
     */
    function get(): string
    {
        return $this->driver->get($this->cacheKey);
    }

    /**
     * 是否有效
     *
     * @return bool
     */
    function isValid(): bool
    {
        $cacheFile = $this->cachePath . $this->cacheKey;
        if (!file_exists($cacheFile)) {
            return false;
        }

        if ((time() - filemtime($cacheFile)) < $this->expireTime) {
            return true;
        }

        return false;
    }

    /**
     * 缓存配置
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
