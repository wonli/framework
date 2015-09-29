<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\MVC;

use Cross\Core\CrossArray;
use Cross\Core\FrameBase;
use Cross\Core\Loader;
use Cross\DB\DBFactory;
use Cross\Exception\CoreException;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Module
 * @package Cross\MVC
 * @property \Cross\Cache\RedisCache|\Cross\DB\Drivers\CouchDriver|\Cross\DB\Drivers\MongoDriver|\Cross\DB\Drivers\PDOSqlDriver link
 */
class Module extends FrameBase
{
    /**
     * link的model类型
     *
     * @var string
     */
    private $link_type;

    /**
     * link的model配置
     *
     * @var array
     */
    private $link_config;

    /**
     * 连接配置文件名
     * <pre>
     * 默认为项目目录下的config/db.config.php
     * 可以在app目录下init.php文件中通过'sys' => 'db_config'指定
     * </pre>
     *
     * @var string
     */
    protected $db_config_file;

    /**
     * module配置缓存
     *
     * @var object
     */
    protected static $module_config;

    /**
     * 解析要连接model的参数
     *
     * @param string $params 指定要连接的数据库和配置项的key, 如mysql['db']这里的params应该为mysql:db
     */
    function __construct($params = '')
    {
        parent::__construct();
        $this->initModelParams($params);
    }

    /**
     * 创建model实例,参数格式和构造函数一致
     *
     * @param string $params 要实例化model的参数
     * @param array $config 解析params获得的model详细配置
     * @return \Cross\Cache\RedisCache|\Cross\DB\Drivers\CouchDriver|\Cross\DB\Drivers\MongoDriver|\Cross\DB\Drivers\PDOSqlDriver
     * @throws CoreException
     */
    function getModel($params = '', &$config = array())
    {
        $config = $this->parseModelParams($params);
        return DBFactory::make($config['model_type'], $config['model_config'], $this->getConfig());
    }

    /**
     * 当前link的model类型
     *
     * @return string
     */
    function getLinkType()
    {
        return $this->link_type;
    }

    /**
     * 当前link的model详细配置信息
     *
     * @return array
     */
    function getLinkConfig()
    {
        return $this->link_config;
    }

    /**
     * 读取并解析数据库配置
     *
     * @return CrossArray
     */
    protected function databaseConfig()
    {
        if (!self::$module_config) {
            $link_config_file = $this->getModuleConfigFile();
            self::$module_config = CrossArray::init(Loader::read("::config/{$link_config_file}"));
        }

        return self::$module_config;
    }

    /**
     * 设置配置文件名
     *
     * @param $link_config_file
     */
    protected function setDatabaseConfigFile($link_config_file)
    {
        $this->db_config_file = $link_config_file;
    }

    /**
     * 解析指定model的类型和参数
     *
     * @param string $params
     * @return array
     * @throws CoreException
     */
    protected function parseModelParams($params = '')
    {
        $db_config_params = '';
        if ($params) {
            $db_config_params = $params;
        } else {
            static $default_db_config = '';
            if ($default_db_config === '') {
                $default_db_config = $this->getConfig()->get('sys', 'default_db');
            }

            if ($default_db_config) {
                $db_config_params = $default_db_config;
            }
        }

        if ($db_config_params) {
            if (strpos($db_config_params, ':') === false) {
                throw new CoreException("数据库参数配置格式不正确: {$db_config_params}");
            }

            list($model_type, $model_name) = explode(':', $db_config_params);
        } else {
            $model_name = 'db';
            $model_type = 'mysql';
        }

        static $model_config_cache;
        if (!isset($model_config_cache[$model_type][$model_name])) {
            $all_db_config = $this->databaseConfig();
            $model_config = $all_db_config->get($model_type, $model_name);
            if (empty($model_config)) {
                throw new CoreException("未配置的Model: {$model_type}:{$model_name}");
            }

            $model_config_cache[$model_type][$model_name] = array(
                'model_type' => $model_type,
                'model_config' => $model_config,
            );
        }

        return $model_config_cache[$model_type][$model_name];
    }

    /**
     * 获取默认model的实例
     *
     * @return \Cross\Cache\RedisCache|\Cross\DB\Drivers\CouchDriver|\Cross\DB\Drivers\MongoDriver|\Cross\DB\Drivers\PDOSqlDriver|mixed
     * @throws CoreException
     */
    private function getLink()
    {
        return DBFactory::make($this->link_type, $this->link_config, $this->getConfig());
    }

    /**
     * 初始化model_type和model_config
     *
     * @param string $params
     * @throws CoreException
     */
    private function initModelParams($params = '')
    {
        $config = $this->parseModelParams($params);
        $this->link_type = $config['model_type'];
        $this->link_config = $config['model_config'];
    }

    /**
     * 获取连接配置文件名
     *
     * @return mixed
     */
    private function getModuleConfigFile()
    {
        if (!$this->db_config_file) {
            $db_config_file = $this->getConfig()->get('sys', 'db_config');
            if (!$db_config_file) {
                $db_config_file = 'db.config.php';
            }

            $this->setDatabaseConfigFile($db_config_file);
        }

        return $this->db_config_file;
    }

    /**
     * 访问link属性时才实例化model
     *
     * @param $property
     * @return \Cross\Cache\RedisCache|\Cross\Core\Request|\Cross\Core\Response|\Cross\DB\Drivers\CouchDriver|\Cross\DB\Drivers\MongoDriver|\Cross\DB\Drivers\PDOSqlDriver|View|null
     * @throws CoreException
     */
    function __get($property)
    {
        switch ($property) {
            case 'link' :
                return $this->link = $this->getLink();

            default :
                return parent::__get($property);
        }
    }
}
