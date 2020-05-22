<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Core;

use Cross\Exception\CoreException;

/**
 * @author wonli <wonli@live.com>
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
     * @var CrossArray
     */
    private $ca;

    /**
     * 查询缓存
     *
     * @var array
     */
    private static $cache;

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

        $this->ca = CrossArray::init($this->config_data, $this->res_file);
    }

    /**
     * 实例化配置类
     *
     * @param string $file
     * @return Config
     * @throws CoreException
     */
    static function load($file): self
    {
        if (!isset(self::$instance[$file])) {
            self::$instance[$file] = new self($file);
        }

        return self::$instance[$file];
    }

    /**
     * 合并附加数组到源数组
     *
     * @param array $append_config
     * @param bool $cover 是否覆盖已有值
     * @return $this
     */
    function combine(array $append_config = array(), $cover = true): self
    {
        if (!empty($append_config)) {
            foreach ($append_config as $key => $value) {
                if ($cover) {
                    $configValue = &$this->config_data[$key];
                    if (is_array($value) && is_array($configValue)) {
                        $this->config_data[$key] = array_merge($configValue, $value);
                    } else {
                        $this->config_data[$key] = $value;
                    }
                } else {
                    if (isset($this->config_data[$key])) {
                        $configValue = &$this->config_data[$key];
                        if (is_array($value) && is_array($configValue)) {
                            $this->config_data[$key] = array_merge($value, $configValue);
                        }
                    } elseif (!isset($this->config_data[$key])) {
                        $this->config_data[$key] = $value;
                    }
                }

                $this->clearIndexCache($key);
            }
        }

        return $this;
    }

    /**
     * 获取指定配置
     *
     * @param string $index
     * @param string|array $options
     * @return string|array
     * @see CrossArray::get()
     */
    function get($index, $options = '')
    {
        $key = $this->getIndexCacheKey($index);
        if (is_array($options)) {
            $opk = implode('.', $options);
        } elseif ($options) {
            $opk = $options;
        } else {
            $opk = '-###-';
        }

        if (!isset(self::$cache[$key][$opk])) {
            self::$cache[$key][$opk] = $this->ca->get($index, $options);
        }

        return self::$cache[$key][$opk];
    }

    /**
     * 更新指定配置
     *
     * @param string $index
     * @param array|string $values
     * @return bool
     * @see CrossArray::get()
     */
    function set($index, $values = '')
    {
        $result = $this->ca->set($index, $values);
        $this->clearIndexCache($index);

        return $result;
    }

    /**
     * 返回全部配置数据
     *
     * @param bool $obj 是否返回对象
     * @return array|object
     * @see CrossArray::getAll()
     */
    function getAll($obj = false)
    {
        if ($obj) {
            return CrossArray::arrayToObject($this->config_data);
        }

        return $this->config_data;
    }

    /**
     * 获取数组索引缓存key
     *
     * @param string $index
     * @return string
     */
    protected function getIndexCacheKey($index)
    {
        return $this->res_file . '.' . $index;
    }

    /**
     * 清除缓存
     *
     * @param string $index
     */
    protected function clearIndexCache($index)
    {
        $key = $this->getIndexCacheKey($index);
        unset(self::$cache[$key]);
    }
}
