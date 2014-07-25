<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class CoreModule
 */
namespace cross\mvc;

use cross\core\CrossArray;
use cross\core\FrameBase;
use cross\core\Loader;
use cross\exception\CoreException;
use cross\exception\FrontException;
use cross\model\CoreModel;

class Module extends FrameBase
{
    /**
     * @var \cross\cache\RedisCache|\cross\model\CouchModel|\cross\model\MongoModel|mixed
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
    protected static $link_config;

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
    function __construct($params = null)
    {
        parent::__construct();
        $this->link = $this->getLink($params);
    }

    /**
     * 连接数据库
     *
     * @param null $params
     * @return \cross\cache\RedisCache|\cross\model\CouchModel|\cross\model\MongoModel|mixed
     * @throws \cross\exception\CoreException
     */
    function getLink($params = null)
    {
        $db_config = $this->linkConfig();
        $controller_config = null;

        if ($params) {
            list($link_type, $link_config) = explode(":", $params);
            $link_params = $db_config->get($link_type, $link_config);

            if (empty($link_params)) {
                throw new CoreException("未配置的数据库: {$link_type}:{$link_config}");
            }
        } else {
            if ($db_config->get("mysql", "db")) {
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
    function linkConfig()
    {
        if (!self::$link_config) {
            $link_config_file = $this->getLinkConfigFile();
            self::$link_config = CrossArray::init(Loader::read("::config/{$link_config_file}"));
        }

        return self::$link_config;
    }

    /**
     * 设置连接配置文件名
     *
     * @param $link_config_file
     */
    function setLinkConfigFile($link_config_file)
    {
        $this->db_config_file = $link_config_file;
    }

    /**
     * 获取连接配置文件名
     *
     * @return mixed
     */
    function getLinkConfigFile()
    {
        if (!$this->db_config_file) {
            $db_config_file = $this->config->get('sys', 'db_config');
            if (!$db_config_file) {
                $db_config_file = 'db.config.php';
            }

            $this->setLinkConfigFile($db_config_file);
        }

        return $this->db_config_file;
    }

    /**
     * 取缓存key
     *
     * @param $key_name
     * @param null $key_value
     * @throws FrontException
     * @return mixed
     */
    static function cache_key($key_name, $key_value = null)
    {
        if (!self::$cache_file) {
            self::$cache_file = Loader::read("::config/cachekey.php");
        }
        $cache_key_object = CrossArray::init(self::$cache_file);

        if (is_array($key_name)) {
            list($key_name, $child_name) = $key_name;
            $cache_key = $cache_key_object->get($key_name, $child_name);
        } else {
            $cache_key = $cache_key_object->get($key_name);
        }

        if (!empty($cache_key)) {
            if (null !== $key_value) {
                return "{$cache_key}:{$key_value}";
            }

            return $cache_key;
        } else {
            throw new FrontException("缓存key {$key_name} 未定义");
        }
    }
}
