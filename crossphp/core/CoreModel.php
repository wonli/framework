<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Auth: wonli <wonli@live.com>
 * Class CoreModel
 */
class CoreModel extends FrameBase
{
    /**
     * @var 数据库类型
     */
    private $dbtype;

    /**
     * @var bool|Mongo|PdoDataAccess
     */
    protected $link;

    /**
     * @var 缓存key
     */
    protected static $cache_key;

    /**
     * 构造函数连接数据库
     *
     * @param null $controller
     */
    function __construct($controller = null)
    {
        parent::__construct();
        $this->controller = $controller;
        $this->link = $this->db = $this->dbcontent();
        $this->dbconfig = $this->_config();
    }

    /**
     * 建立与数据库的连接
     * @return bool|Mongo|PdoDataAccess
     * @throws CoreException
     */
    private function dbcontent()
    {
        $db = $this->getDBConfig();
        $dbtype = $this->getDBType();

        if(!$db) {
            return false;
        }

        switch($dbtype)
        {
            case 'mysql' :
                return PdoAccess::getInstance($db["dsn"], $db["user"], $db["pass"]);

            case 'mongodb' :
                return  new Mongo($db["dsn"]);

            default :
                throw new CoreException("不支持的数据库类型!请自行扩展");
        }
    }

    /**
     * 读取数据库配置
     *
     * @param string $type
     * @throws CoreException
     * @return array
     */
    function _config($type='all')
    {
        $config = Config::load( APP_NAME, "config/db.config.php")->parse('', false)->getAll();

        if($type == 'all')
        {
            return $config;
        } else {
            if(isset( $config [$type] )) {
                return $config [$type];
            }
            throw new CoreException("未发现 {$type} 配置项");
        }
    }

    /**
     * 设置数据库类型
     * @param $type
     */
    private function setDBType($type)
    {
        if(! $this->dbtype) {
            $this->dbtype = $type;
        }
    }

    /**
     * 取得数据库类型
     * @return mixed
     */
    private function getDBType()
    {
        return $this->dbtype;
    }

    /**
     * 读取数据库配置
     *
     * @param string $type
     * @throws CoreException
     * @return bool
     */
    private function getDBConfig( $type='CONTROLLER' )
    {
        $db_config = $this->_config( );
        $controller_config = $this->config->get("controller", strtolower($this->controller));

        if(isset( $controller_config ['db']))
        {
            list($type, $config_num) = explode(":", $controller_config ["db"]);

            if($type) {
                $this->setDBType($type);
            }

            if( isset( $db_config[$type] [$config_num] ) )
            {
                return $db_config[$type] [$config_num];
            } else {
                throw new CoreException("指定的数据库连接未配置: ".$type.'-'.$config_num);
            }
        }
        else
        {
            if($db_config["mysql"]["db"]) {
                $this->setDBType("mysql");
                return $db_config["mysql"]["db"];
            } else {
                throw new CoreException("未找到数据库默认配置");
            }
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
        return $this->initModule($name);
    }

    /**
     * 加载缓存
     *
     * @param string $type
     * @return stdClass
     * @throws CoreException
     */
    function get_cache($type = 'redis')
    {
        $cache_config = $this->_config( $type );
        $obj = new stdClass();

        if(! empty($cache_config))
        {
            if(is_array($cache_config))
            {
                foreach($cache_config as $_cache_name => $_cache_config)
                {
                    $obj->$_cache_name = Cache::factory($type, $_cache_config);
                }
            }

            return $obj;
        } else {
            throw new CoreException("未配置的缓存类型 {$type}");
        }
    }

    /**
     * 取缓存key
     *
     * @param $key_name
     * @param $key_value
     * @throws FrontException
     * @return mixed
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

}
