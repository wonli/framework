<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.3
 */
namespace Cross\Core;

use Cross\Exception\CoreException;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Config
 * @package Cross\Core
 */
class Config
{
    /**
     * 配置资源文件地址
     *
     * @var string
     */
    protected $res_file;

    /**
     * 单例模式
     *
     * @var object
     */
    private static $instance;

    /**
     * 所有配置
     *
     * @var array
     */
    protected $init;

    private function __construct($res_file)
    {
        if (!file_exists($res_file)) {
            throw new CoreException("读取配置文件失败");
        }

        $this->res_file = $res_file;
    }

    /**
     * 实例化配置类
     *
     * @param string $file
     * @return Config
     */
    static function load($file)
    {
        if (!isset(self::$instance[$file])) {
            self::$instance[$file] = new Config($file);
        }

        return self::$instance[$file];
    }

    /**
     * 解析配置文件和自定义参数
     *
     * @param null $user_config 用户自定义参数
     * @return $this
     */
    function parse($user_config = null)
    {
        $config_data = $this->readConfigFile();
        if (null !== $user_config) {
            if (!is_array($user_config) && is_file($user_config)) {
                $config_set = require $user_config;
                $this->setData($config_set);

                return $this;
            } else if (!empty($user_config) && is_array($user_config)) {
                foreach ($user_config as $key => $_config) {
                    if (is_array($_config)) {
                        foreach ($_config as $_config_key => $_config_value) {
                            $config_data [$key] [$_config_key] = $_config_value;
                        }
                    } else {
                        $config_data [$key] = $_config;
                    }
                }
            }
        }

        $this->setData($config_data);
        return $this;
    }

    /**
     * 保存配置参数
     *
     * @param array $init 配置文件
     * @return array
     */
    function setData($init)
    {
        $this->init = $init;
    }

    /**
     * 从文件读取配置文件 支持PHP / JSON
     *
     * @return mixed
     * @throws CoreException
     */
    function readConfigFile()
    {
        return Loader::read($this->res_file);
    }

    /**
     * 获取配置参数
     * $config为字符串的时候 获取配置数组,此时设定$name 则获取数组中指定项的值
     * $config为数组的时候 获取数组中指定的配置项,如果$name为true 则获取指定项之外的配置项
     *
     * @param $config
     * @param $name null|boolean
     * @return string|array
     */
    function get($config, $name = null)
    {
        return CrossArray::init($this->init)->get($config, $name);
    }

    /**
     * 设定配置项的值
     *
     * @param string $name 要设定的项
     * @param array|string $values 设定的项的值
     * @return bool|null
     */
    function set($name, $values = null)
    {
        if (!isset($this->init[$name])) {
            if (is_array($values)) {
                $this->init[$name] = array();
            } else {
                return $this->init[$name] = $values;
            }
        }

        foreach ($values as $k => $v) {
            $this->init[$name][$k] = $v;
        }

        return true;
    }

    /**
     * 返回全部配置
     *
     * @param bool|$obj 是否返回对象
     * @return array/object
     */
    function getAll($obj = false)
    {
        return CrossArray::init($this->init)->getAll($obj);
    }
}
