<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:       wonli
 * @Version: $Id: CoreModule.php 116 2013-08-17 08:48:35Z ideaa $
 */
class CoreModule extends FrameBase
{
    /**
     * @var db_type
     */
    protected $db_type;

    /**
     * @var link resource;
     */
    protected $link;

    /**
     * @var 缓存key
     */
    protected static $cache_key;

    /**
     * 调用moduel
     *
     * @param null 指定数据库配置
     */
    function __construct( $params = null )
    {
        parent::__construct();
        $this->link = $this->getLink( $params );
    }

    /**
     * 连接数据库
     *
     * @param null $params
     * @return bool|RedisCache
     * @throws CoreException
     */
    function getLink( $params = null )
    {
        $db_config = $this->db_config( );
        $controller_config = null;

        if( $params )
        {
            list($link_type, $link_config) = explode(":", $params);
            $link_params = $db_config->get($link_type, $link_config);

            if( empty($link_params) )
            {
                throw new CoreException("未配置的数据库: {$link_type}:{$link_config}");
            }
        }
        else
        {
            if($db_config->get("mysql", "db"))
            {
                $link_type = 'mysql';
                $link_params = $db_config->get("mysql", "db");
            } else {
                throw new CoreException("未找到数据库默认配置");
            }
        }

        return CoreModel::factory($link_type, $link_params);
    }

    /**
     * 读取并解析数据库配置
     *
     * @return array
     */
    function db_config( $type='all' )
    {
        return $config = CrossArray::init( Loader::import("::config/db.config.php", true) );
    }

    /**
     * 取缓存key
     *
     * @param $key_name
     * @return mixed
     * @throws FrontException
     */
    static function cache_key($key_name, $key_value)
    {
        if( empty(self::$cache_key) ) {
            self::$cache_key = Loader::import("::config/cachekey.php", true);
        }

        $cache_key_object = CrossArray::init(self::$cache_key);

        if(is_array($key_name))
        {
            list($key_name, $child_name) = $key_name;
            $cache_key = $cache_key_object->get($key_name, $child_name);
        }
        else
        {
            $cache_key = $cache_key_object->get($key_name);
        }

        if(! empty($cache_key))
        {
            return "{$cache_key}:{$key_value}";
        }
        else
        {
            throw new FrontException("缓存key {$key_name} 未定义");
        }
    }

    /**
     * 加载其他module
     * @param $module_name 要加载的module的全名
     * @return object
     */
    final function load($module_name)
    {
        $name = substr($module_name, 0, -6);
        return $this->loadModule($name);
    }
}
