<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Cache\Request;

use Cross\Cache\Driver\FileCacheDriver;
use Cross\Exception\CoreException;
use Cross\I\RequestCacheInterface;

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
    private $cacheKey;

    /**
     * @var string
     */
    private $cachePath;

    /**
     * @var array
     */
    private $config;

    /**
     * 缓存过期时间(秒)
     *
     * @var int
     */
    private $expireTime = 3600;

    /**
     * @var FileCacheDriver
     */
    private $driver;

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
    function set($value)
    {
        $this->driver->set($this->cacheKey, $value);
    }

    /**
     * 获取缓存内容
     *
     * @return mixed get cache
     */
    function get()
    {
        return $this->driver->get($this->cacheKey);
    }

    /**
     * 是否有效
     *
     * @return bool
     */
    function isValid()
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
