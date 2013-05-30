<?php defined('CROSSPHP_PATH')or die('Access Denied');
//缓存类
class Cache
{
    static $cache_type = array(1=>'file', 2=>'memcache', 3=>'redis');
    
    static function create($cache_config, $cache_key)
    {   
        switch($cache_config["type"])
        {
            case 1:
            return new FileCache($cache_key, $cache_config["extime"] );

            case 2:
            return new Memcache($cache_key);

            default :
            throw new CacheException("不支持的缓存");
        }
    }
}

//缓存实现
class FileCache implements CacheInterface
{
    private $cachekey;
    private $extime;
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