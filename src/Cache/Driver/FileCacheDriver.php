<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Cache\Driver;

use Cross\Exception\CoreException;
use Cross\I\CacheInterface;

/**
 * @author wonli <wonli@live.com>
 * Class FileCacheDriver
 * @package Cross\Cache\Driver
 */
class FileCacheDriver implements CacheInterface
{
    /**
     * 缓存文件路径
     *
     * @var string
     */
    private $cache_path;

    function __construct(string $cache_path)
    {
        $cache_path = rtrim($cache_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->cache_path = $cache_path;
    }

    /**
     * 返回缓存文件
     *
     * @param string $key
     * @return mixed
     */
    function get(string $key = '')
    {
        $cache_file = $this->cache_path . $key;
        if (!file_exists($cache_file)) {
            return false;
        }

        return file_get_contents($cache_file);
    }

    /**
     * 保存缓存
     *
     * @param string $key
     * @param string $value
     * @return mixed|void
     * @throws CoreException
     */
    function set(string $key, $value)
    {
        $cacheFile = $this->cache_path . $key;
        if (!file_exists($cacheFile)) {
            $filePath = dirname($cacheFile);
            if (!is_dir($filePath)) {
                $createDir = mkdir($filePath, 0755, true);
                if (!$createDir) {
                    throw new CoreException('创建缓存目录失败');
                }
            }
        }

        file_put_contents($cacheFile, $value, LOCK_EX);
    }

    /**
     * 删除缓存
     *
     * @param string $key
     * @return mixed|void
     */
    function del(string $key)
    {
        $cacheFile = $this->cache_path . $key;
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}
