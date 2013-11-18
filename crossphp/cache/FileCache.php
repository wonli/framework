<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Auth wonli <wonli@live.com>
 * Class FileCache
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
        $this->cache_file = $cache_config['cache_path'].DS.$cache_config['key'].$cache_config['file_ext'];
        $this->expire_time = $cache_config ['expire_time'];

        $this->init();
    }

    /**
     * 如果缓存文件不存在则创建
     */
    function init()
    {
        if(! file_exists($this->cache_file))
        {
            Helper::mkfile($this->cache_file);
        }
    }

    /**
     * 返回缓存文件
     *
     * @param string $key
     * @return mixed
     */
    function get( $key = '' )
    {
        return file_get_contents( $this->cache_file );
    }

    /**
     * 检查过期时间
     *
     * @return bool
     */
    function getExtime()
    {
        if(! file_exists($this->cache_file))
        {
            return false;
        }

        if( (time() - filemtime( $this->cache_file )) < $this->expire_time)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * 保存缓存
     *
     * @param $key
     * @param $value
     * @return mixed|void
     */
    function set( $key, $value )
    {
        var_dump(  $key );
    }
}
