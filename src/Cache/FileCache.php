<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.3.0
 */
namespace Cross\Cache;

use Cross\Core\Helper;
use Cross\I\CacheInterface;

/**
 * @Auth: wonli <wonli@live.com>
 * Class FileCache
 * @package Cross\Cache
 */
class FileCache implements CacheInterface
{
    /**
     * 过期时间
     *
     * @var int
     */
    private $expire_time;

    /**
     * 缓存文件路径
     *
     * @var string
     */
    private $cache_file;

    function __construct($cache_config)
    {
        $file_ext = isset($cache_config['file_ext']) ? $cache_config['file_ext'] : '.html';
        $this->cache_file = $cache_config['cache_path'] . DIRECTORY_SEPARATOR . $cache_config['key'] . $file_ext;
        $this->expire_time = isset($cache_config ['expire_time']) ? $cache_config ['expire_time'] : 3600;
    }

    /**
     * 如果缓存文件不存在则创建
     */
    function init()
    {
        if (!file_exists($this->cache_file)) {
            Helper::mkfile($this->cache_file);
        }
    }

    /**
     * 返回缓存文件
     *
     * @param string $key
     * @return mixed
     */
    function get($key = '')
    {
        if (file_exists($this->cache_file)) {
            return file_get_contents($this->cache_file);
        }

        return false;
    }

    /**
     * 检查过期时间
     *
     * @return bool
     */
    function getExpireTime()
    {
        if (!file_exists($this->cache_file)) {
            return false;
        } elseif ((time() - filemtime($this->cache_file)) < $this->expire_time) {
            return true;
        }

        return false;
    }

    /**
     * 保存缓存
     *
     * @param $key
     * @param $value
     * @return mixed|void
     */
    function set($key, $value)
    {
        if (null == $key) {
            $key = $this->cache_file;
            if (!file_exists($key)) {
                $this->init();
            }
        }

        file_put_contents($key, $value, LOCK_EX);
    }
}
