<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.5
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
     * 默认追加的系统配置项
     *
     * @var array
     */
    protected $sys;

    /**
     * app名称
     *
     * @var string
     */
    protected $appName;

    /**
     * 配置资源文件地址
     *
     * @var string
     */
    protected $res_file;

    /**
     * 基础路径
     *
     * @var string
     */
    protected $base_path;

    /**
     * 避免重复加载
     *
     * @var array
     */
    protected static $loaded;

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
        $this->res_file = Loader::getFilePath('app::' . $res_file);
    }

    /**
     * 实例化配置类
     *
     * @param string $file
     * @return Config
     */
    static function load($file = 'init.php')
    {
        if (!isset(self::$instance)) {
            self::$instance = new Config($file);
        }

        return self::$instance;
    }

    /**
     * 解析配置文件和自定义参数
     *
     * @param null $user_config 用户自定义参数
     * @param bool $append_sys 是否附加系统默认参数
     * @return $this
     */
    function parse($user_config = null, $append_sys = true)
    {
        $config_data = $this->readConfigFile();

        if (true === $append_sys) {
            $this->sys = $this->getSysSet();

            if (isset($config_data ['sys'])) {
                $config_data ['sys'] = array_merge($this->sys, array_filter($config_data ['sys']));
            } else {
                $config_data ['sys'] = $this->sys;
            }
        }

        if (null !== $user_config) {
            if (!is_array($user_config) && is_file($user_config)) {
                $config_set = require $user_config;
                $this->setData($config_set);

                return $this;
            } else if (!empty($user_config) && is_array($user_config)) {
                foreach ($user_config as $key => $_config) {
                    if (is_array($_config)) {
                        foreach ($_config as $_config_key => $_config_value) {
                            if ($_config_value) {
                                $config_data [$key] [$_config_key] = $_config_value;
                            }
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
     * 设置默认追加的系统参数
     *
     * @return array
     */
    private function getSysSet()
    {
        $host = Request::getInstance()->getHostInfo();
        $base_url = Request::getInstance()->getBaseUrl();
        $script_path = Request::getInstance()->getScriptFilePath();

        $_sys = array(
            'host' => $host,
            'base_url' => $base_url,
            'site_url' => $host . $base_url,
            'static_url' => $base_url . '/static/',
            'static_path' => $script_path . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR,
        );

        return $_sys;
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
        if (! isset($this->init[$name])) {
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
