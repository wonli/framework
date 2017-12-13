<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\MVC;

use Cross\Cache\Driver\RedisDriver;
use Cross\Exception\CoreException;
use Cross\DB\Drivers\PDOSqlDriver;
use Cross\DB\Drivers\CouchDriver;
use Cross\DB\Drivers\MongoDriver;
use Cross\DB\DBFactory;
use Cross\Core\FrameBase;
use Cross\Core\Config;
use Cross\Http\Response;
use Cross\Http\Request;

/**
 * @author wonli <wonli@live.com>
 * Class Module
 * @package Cross\MVC
 * @property RedisDriver|CouchDriver|MongoDriver|PDOSqlDriver $link
 */
class Module extends FrameBase
{
    /**
     * 数据库连接的model名称
     *
     * @var string
     */
    private $linkName;

    /**
     * 数据库连接model类型
     *
     * @var string
     */
    private $linkType;

    /**
     * 数据库连接的model配置
     *
     * @var array
     */
    private $linkConfig;

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
     * 解析要连接model的参数
     *
     * @param string $params 指定要连接的数据库和配置项的key, 如mysql['db']这里的params应该为mysql:db
     * @throws CoreException
     */
    function __construct($params = '')
    {
        parent::__construct();

        $config = $this->parseModelParams($params);
        $this->linkName = &$config['model_name'];
        $this->linkType = &$config['model_type'];
        $this->linkConfig = &$config['model_config'];
    }

    /**
     * 创建model实例,参数格式和构造函数一致
     *
     * @param string $params
     * @param array $config
     * @return RedisDriver|CouchDriver|MongoDriver|PDOSqlDriver|mixed
     * @throws CoreException
     */
    function getModel($params = '', &$config = array())
    {
        static $cache = array();
        if (!isset($cache[$params])) {
            $config = $this->parseModelParams($params);
            $model = DBFactory::make($config['model_type'], $config['model_config'], array($this->getConfig()));
            $cache[$params] = array('config' => $config, 'model' => $model);
        } else {
            $model = $cache[$params]['model'];
            $config = $cache[$params]['config'];
        }

        return $model;
    }

    /**
     * 当前link的model名称
     *
     * @return string
     */
    function getLinkName()
    {
        return $this->linkName;
    }

    /**
     * 当前link的model类型
     *
     * @return string
     */
    function getLinkType()
    {
        return $this->linkType;
    }

    /**
     * 当前link的model详细配置信息
     *
     * @return array
     */
    function getLinkConfig()
    {
        return $this->linkConfig;
    }

    /**
     * 获取带配置前缀的表名
     *
     * @param string $table
     * @return string
     */
    function getPrefix($table = '')
    {
        return $this->link->getPrefix() . $table;
    }

    /**
     * 读取并解析数据库配置
     *
     * @return Config
     */
    protected function databaseConfig()
    {
        static $database_config = null;
        if (null === $database_config) {
            $database_config = parent::loadConfig($this->getModuleConfigFile());
        }

        return $database_config;
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

        static $cache;
        if (!isset($cache[$model_type][$model_name])) {
            $databaseConfig = $this->databaseConfig();
            $model_config = $databaseConfig->get($model_type, $model_name);
            if (empty($model_config)) {
                throw new CoreException("未配置的Model: {$model_type}:{$model_name}");
            }

            $cache[$model_type][$model_name] = array(
                'model_name' => $model_name,
                'model_type' => $model_type,
                'model_config' => $model_config,
            );
        }

        return $cache[$model_type][$model_name];
    }

    /**
     * 获取默认model的实例
     *
     * @return RedisDriver|CouchDriver|MongoDriver|PDOSqlDriver|mixed
     * @throws CoreException
     */
    private function getLink()
    {
        return DBFactory::make($this->linkType, $this->linkConfig, array($this->getConfig()));
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
     * @param string $property
     * @return RedisDriver|Config|CouchDriver|MongoDriver|PDOSqlDriver|Request|Response|View|mixed|null
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
