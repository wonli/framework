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
    private string $cachePath;

    function __construct(string $cachePath)
    {
        $cachePath = rtrim($cachePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->cachePath = $cachePath;
    }

    /**
     * 返回缓存文件
     *
     * @param string $key
     * @return string|bool
     */
    function get(string $key = ''): string|bool
    {
        $cacheFile = $this->cachePath . $key;
        if (!file_exists($cacheFile)) {
            return false;
        }

        return file_get_contents($cacheFile);
    }

    /**
     * 保存缓存
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     * @throws CoreException
     */
    function set(string $key, mixed $value): bool
    {
        $cacheFile = $this->cachePath . $key;
        if (!file_exists($cacheFile)) {
            $filePath = dirname($cacheFile);
            if (!is_dir($filePath)) {
                $createDir = mkdir($filePath, 0755, true);
                if (!$createDir) {
                    throw new CoreException('创建缓存目录失败');
                }
            }
        }

        $n = file_put_contents($cacheFile, $value, LOCK_EX);
        if ($n === false) {
            return false;
        }

        return true;
    }

    /**
     * 删除缓存
     *
     * @param string $key
     * @return bool
     */
    function del(string $key): bool
    {
        $cacheFile = $this->cachePath . $key;
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }

        return true;
    }
}
