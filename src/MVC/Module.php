<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\MVC;

use Cross\Core\FrameBase;
use Cross\Core\Config;

use Cross\Http\Response;
use Cross\Http\Request;

use Cross\Exception\DBConnectException;
use Cross\Exception\CoreException;

use Cross\Cache\Driver\RedisDriver;
use Cross\DB\Drivers\PDOSqlDriver;
use Cross\DB\Drivers\CouchDriver;
use Cross\DB\Drivers\MongoDriver;
use Cross\DB\DBFactory;


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
    protected $dbConfigFile;

    /**
     * 解析要连接model的参数
     *
     * @param string $params 指定要连接的数据库和配置项的key, 如mysql['db']这里的params应该为mysql:db
     * @throws CoreException
     */
    function __construct(string $params = '')
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
     * @throws DBConnectException
     */
    function getModel(string $params = '', &$config = []): object
    {
        static $cache = [];
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
    function getLinkName(): string
    {
        return $this->linkName;
    }

    /**
     * 当前link的model类型
     *
     * @return string
     */
    function getLinkType(): string
    {
        return $this->linkType;
    }

    /**
     * 当前link的model详细配置信息
     *
     * @return array
     */
    function getLinkConfig(): array
    {
        return $this->linkConfig;
    }

    /**
     * 获取带配置前缀的表名
     *
     * @param string $table
     * @return string
     */
    function getPrefix(string $table = ''): string
    {
        return $this->link->getPrefix() . $table;
    }

    /**
     * 读取并解析数据库配置
     *
     * @return Config
     * @throws CoreException
     */
    protected function databaseConfig(): Config
    {
        static $databaseConfig = null;
        if (null === $databaseConfig) {
            $databaseConfig = parent::loadConfig($this->getModuleConfigFile());
        }

        return $databaseConfig;
    }

    /**
     * 设置配置文件名
     *
     * @param string $linkConfigFile
     */
    protected function setDatabaseConfigFile(string $linkConfigFile): void
    {
        $this->dbConfigFile = $linkConfigFile;
    }

    /**
     * 解析指定model的类型和参数
     *
     * @param string $params
     * @return array
     * @throws CoreException
     */
    protected function parseModelParams(string $params = ''): array
    {
        $dbConfigParams = '';
        if ($params) {
            $dbConfigParams = $params;
        } else {
            static $defaultDbConfig = '';
            if ($defaultDbConfig === '') {
                $defaultDbConfig = $this->getConfig()->get('sys', 'default_db');
            }

            if ($defaultDbConfig) {
                $dbConfigParams = $defaultDbConfig;
            }
        }

        if ($dbConfigParams) {
            if (strpos($dbConfigParams, ':') === false) {
                throw new CoreException("数据库参数配置格式不正确: {$dbConfigParams}");
            }

            list($modelType, $modelName) = explode(':', $dbConfigParams);
        } else {
            $modelName = 'db';
            $modelType = 'mysql';
        }

        static $cache;
        if (!isset($cache[$modelType][$modelName])) {
            $databaseConfig = $this->databaseConfig();
            $modelConfig = $databaseConfig->get($modelType, $modelName);
            if (empty($modelConfig)) {
                throw new CoreException("未配置的Model: {$modelType}:{$modelName}");
            }

            $cache[$modelType][$modelName] = [
                'model_name' => $modelName,
                'model_type' => $modelType,
                'model_config' => $modelConfig,
            ];
        }

        return $cache[$modelType][$modelName];
    }

    /**
     * 获取默认model的实例
     *
     * @return RedisDriver|CouchDriver|MongoDriver|PDOSqlDriver|mixed
     * @throws CoreException
     * @throws DBConnectException
     */
    private function getLink(): object
    {
        return DBFactory::make($this->linkType, $this->linkConfig, [$this->getConfig()]);
    }

    /**
     * 获取连接配置文件名
     *
     * @return mixed
     */
    private function getModuleConfigFile(): string
    {
        if (!$this->dbConfigFile) {
            $dbConfigFile = $this->getConfig()->get('sys', 'db_config');
            if (!$dbConfigFile) {
                $dbConfigFile = 'db.config.php';
            }

            $this->setDatabaseConfigFile($dbConfigFile);
        }

        return $this->dbConfigFile;
    }

    /**
     * 访问link属性时才实例化model
     *
     * @param string $property
     * @return RedisDriver|Config|CouchDriver|MongoDriver|PDOSqlDriver|Request|Response|View|mixed|null
     * @throws CoreException
     * @throws DBConnectException
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
