<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
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
     * @var string
     */
    private $res_file;

    /**
     * @var array
     */
    private $config_data;

    /**
     * @var self
     */
    private static $instance;

    /**
     * 读取配置
     *
     * @param string $res_file 配置文件绝对路径
     * @throws CoreException
     */
    private function __construct($res_file)
    {
        $this->res_file = $res_file;
        $this->config_data = Loader::read($res_file);
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
            self::$instance[$file] = new self($file);
        }

        return self::$instance[$file];
    }

    /**
     * @see CrossArray::get()
     *
     * @param string $index
     * @param string|array $options
     * @return string|array
     */
    function get($index, $options = '')
    {
        return CrossArray::init($this->config_data, $this->res_file)->get($index, $options);
    }

    /**
     * @see CrossArray::get()
     *
     * @param string $index
     * @param array|string $values
     * @return bool
     */
    function set($index, $values = '')
    {
        return CrossArray::init($this->config_data, $this->res_file)->set($index, $values);
    }

    /**
     * @see CrossArray::getAll()
     *
     * @param bool $obj 是否返回对象
     * @return array|object
     */
    function getAll($obj = false)
    {
        return CrossArray::init($this->config_data, $this->res_file)->getAll($obj);
    }

    /**
     * 合并运行时定义的配置
     *
     * @param array $append_config
     * @return $this
     */
    function parse(array $append_config = array())
    {
        if (!empty($append_config)) {
            foreach ($append_config as $key => $value) {
                if (isset($this->config_data[$key]) && is_array($value)) {
                    $this->config_data[$key] = array_merge($this->config_data[$key], $value);
                } else {
                    $this->config_data[$key] = $value;
                }
            }
        }

        return $this;
    }
}
