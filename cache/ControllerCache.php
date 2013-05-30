<?php
class ControllerCache implements CacheInterface
{
    private static $instance;
    private $controller_cache;
    private $cache_dir;

    private function __construct($controller_cache_data)
    {
        $this->controller_cache = $controller_cache_data;
        $this->init();
    }

    static function getInstance($controller_cache_data)
    {
        if(! self::$instance) {
            self::$instance = new ControllerCache($controller_cache_data);
        }
        return self::$instance;
    }

    function init()
    {
        if(! $this->cache_dir) {
            $this->cache_dir = Cross::config()->get("sys", "cache_path").'controller'.DS;
        }
        
        if(! is_dir($this->cache_dir)) {
            if(! mkdir($this->cache_dir, 0777, true) ) {
                throw new CoreException("建立controller缓存失败");
            }
        }
    }

    function get()
    {
        $cache_file = $this->cache_dir.$this->controller_cache.'.php';
        if(! is_file($cache_file)) return false;
        return include $cache_file;
    }
    
    function set()
    {
        $cache_file = $this->cache_dir.strtolower($this->controller_cache["controller"]).'.php';
        $cache_content = '<?php return '.var_export($this->controller_cache, true).';';
        file_put_contents($cache_file, $cache_content);
    }
}





