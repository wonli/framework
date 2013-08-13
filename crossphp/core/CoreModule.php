<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:       wonli
 * @Version: $Id: CoreModule.php 106 2013-08-09 08:26:21Z ideaa $
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
        $db_config = $this->_config( );
        $controller_config = null;

        if( $params )
        {
            list($link_type, $link_config) = explode(":", $params);

            if( isset( $db_config [$link_type] [$link_config] ) )
            {
                $link_params = $db_config [$link_type] [$link_config];
            } else {
                throw new CoreException("未配置的数据库: {$link_type}:{$link_config}");
            }
        }
        else
        {
            if( $db_config ["mysql"] ["db"] )
            {
                $link_type = 'mysql';
                $link_params = $db_config ["mysql"] ["db"];
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
    function _config( $type='all' )
    {
        $config = Config::load( APP_NAME, "config/db.config.php")->parse('', false)->getAll();

        if($type == 'all' || null == $type )
        {
            return $config;
        }
        else
        {
            $_type = $type;
            $_conf = null;

            if(false !== strpos($type, ":"))
            {
                list($_type, $_conf) = explode(":", $type);
            }

            if( isset($config [$_type]) )
            {
                if(null != $_conf)
                {
                    if( isset($config [$_type] [$_conf]) )
                    {
                        return $config [$_type] [$_conf];
                    }
                    throw new CoreException("未发现 {$_type}->{$_conf} 配置项");
                }
                return $config [$_type];
            }
            throw new CoreException("未发现 {$_type} 配置项");
        }
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
            self::$cache_key = Config::load(APP_NAME, "config/cachekey.php")->parse("",false)->getAll();
        }

        if(isset(self::$cache_key [$key_name]))
        {
            return self::$cache_key [$key_name].":{$key_value}";
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
