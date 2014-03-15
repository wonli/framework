<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class CoreModule
 */
class CoreModule extends FrameBase
{
    /**
     * database type name
     *
     * @var string
     */
    protected $db_type;

    /**
     * @var MysqlModel
     */
    protected $link;

    /**
     * 数据库配置
     *
     * @var object
     */
    protected static $db_config;

    /**
     * 缓存文件
     *
     * @var object
     */
    protected static $cache_file;

    /**
     * 实例化module
     *
     * @param null $params 指定数据库配置
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
    function db_config( )
    {
        if(! self::$db_config)
        {
            self::$db_config = CrossArray::init( Loader::read("::config/db.config.php") );
        }
        return self::$db_config;
    }

    /**
     * 取缓存key
     *
     * @param $key_name
     * @param null $key_value
     * @throws FrontException
     * @return mixed
     */
    static function cache_key($key_name, $key_value=null)
    {
        if( ! self::$cache_file) {
            self::$cache_file = Loader::read("::config/cachekey.php");
        }
        $cache_key_object = CrossArray::init(self::$cache_file);

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
            if(null !== $key_value)
            {
                return "{$cache_key}:{$key_value}";
            }

            return $cache_key;
        }
        else
        {
            throw new FrontException("缓存key {$key_name} 未定义");
        }
    }

    /**
     * 加载其他module
     *
     * @param string $module_name 要加载的module的全名
     * @return object
     */
    final function load($module_name)
    {
        $name = substr($module_name, 0, -6);
        return $this->loadModule($name);
    }
}
