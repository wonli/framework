<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Auth wonli <wonli@live.com>
 * Class FileCache
 */

//文件缓存实现
class FileCache implements CacheInterface
{
    /**
     * @var cache key
     */
    private $cachekey;

    /**
     * @var 过期时间
     */
    private $extime;

    /**
     * @var 缓存的文件
     */
    private $cachefile;

    function __construct($cache_key, $extime)
    {
        $this->setCacheKey($cache_key);
        $this->extime = $extime;
        $this->init();
    }

    function init()
    {
        $cachedir = Cross::config()->get("sys", "cache_path");
        $cachekey = str_replace(Cross::config()->get("url","dot"), DS, $this->cachekey);

        $this->cachefile = $cachedir.$cachekey.'.html';
    }

    function setCacheKey($cache_key){
        $this->cachekey = $cache_key;
    }

    function getCacheKey()
    {
        return $this->cachekey;
    }

    function get()
    {
        return include $this->cachefile;
    }

    function getExtime()
    {
        if(! file_exists($this->cachefile)) {
            return false;
        }

        if( (time() - filemtime( $this->cachefile )) < $this->extime) {
            return true;
        } else {
            return false;
        }
    }

    function set()
    {

    }
}