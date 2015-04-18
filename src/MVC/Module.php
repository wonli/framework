<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.3
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
 */
class Module extends FrameBase
{
    /**
     * @var \Cross\Cache\RedisCache|\Cross\DB\Drivers\CouchDriver|\Cross\DB\Drivers\MongoDriver|\Cross\DB\Drivers\PDOSqlDriver
     */
    public $link;

    /**
     * database type name
     *
     * @var string
     */
    protected $db_type;

    /**
     * 连接配置文件名
     *
     * @var string
     */
    protected $db_config_file;

    /**
     * 数据库连接配置
     *
     * @var object
     */
    protected static $database_config;

    /**
     * 实例化module
     *
     * @param null $params 指定数据库配置
     */
    function __construct($params = null)
    {
        parent::__construct();
        $this->link = $this->getLink($params);
    }

    /**
     * @param string $params
     * @return \Cross\Cache\RedisCache|\Cross\DB\Drivers\CouchDriver|\Cross\DB\Drivers\MongoDriver|\Cross\DB\Drivers\PDOSqlDriver
     * @throws CoreException
     */
    function getLink($params = '')
    {
        $db_config = $this->databaseConfig();
        if ($params) {
            list($link_type, $link_config) = explode(':', $params);
            $link_params = $db_config->get($link_type, $link_config);

            if (empty($link_params)) {
                throw new CoreException("未配置的数据库: {$link_type}:{$link_config}");
            }
        } else {
            if ($default = $db_config->get('default')) {
                $default_config = each($default);
                $link_type = $default_config['key'];
                $link_params = $db_config->get($link_type, $default_config['value']);
            } else if ($link_params = $db_config->get('mysql', 'db')) {
                $link_type = 'mysql';
            } else {
                throw new CoreException('未找到数据库默认配置');
            }
        }

        return DBFactory::make($link_type, $link_params, $this->config);
    }

    /**
     * 读取并解析数据库配置
     *
     * @return CrossArray
     */
    protected function databaseConfig()
    {
        if (!self::$database_config) {
            $link_config_file = $this->getDatabaseConfigFile();
            self::$database_config = CrossArray::init(Loader::read("::config/{$link_config_file}"));
        }

        return self::$database_config;
    }

    /**
     * 设置连接配置文件名
     *
     * @param $link_config_file
     */
    protected function setDatabaseConfigFile($link_config_file)
    {
        $this->db_config_file = $link_config_file;
    }

    /**
     * 获取连接配置文件名
     *
     * @return mixed
     */
    private function getDatabaseConfigFile()
    {
        if (!$this->db_config_file) {
            $db_config_file = parent::getConfig()->get('sys', 'db_config');
            if (!$db_config_file) {
                $db_config_file = 'db.config.php';
            }

            $this->setDatabaseConfigFile($db_config_file);
        }

        return $this->db_config_file;
    }
}
