<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.4.0
 */
namespace Cross\Core;

/**
 * @Auth: wonli <wonli@live.com>
 * Class CrossArray
 * @package Cross\Core
 */
class CrossArray
{
    /**
     * @var array 数据
     */
    protected $data;

    /**
     * CrossArray
     *
     * @param $data
     */
    function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @param $data
     * @return CrossArray
     */
    static function init($data)
    {
        return new CrossArray($data);
    }

    /**
     * 获取配置参数
     *
     * @param string $config
     * @param null|boolean $name
     * @return bool|string|array
     */
    function get($config, $name = null)
    {
        if (isset($this->data[$config])) {
            if ($name) {
                if (is_array($name)) {
                    $result = array();
                    foreach ($name as $n) {
                        if (isset($this->data[$config][$n])) {
                            $result[$n] = $this->data[$config][$n];
                        }
                    }
                    return $result;
                } elseif (isset($this->data[$config][$name])) {
                    return $this->data[$config][$name];
                }

                return false;
            }

            return $this->data[$config];
        }
        return false;
    }

    /**
     * 返回全部数据
     *
     * @param bool| $obj 是否返回对象
     * @return array/object
     */
    function getAll($obj = false)
    {
        if ($obj) {
            return $this->arrayToObject($this->data);
        }

        return $this->data;
    }

    /**
     * 数组转对象
     *
     * @param $d
     * @return object
     */
    function arrayToObject($d)
    {
        if (is_array($d)) {
            return (object)array_map(array($this, __FUNCTION__), $d);
        } else {
            return $d;
        }
    }
}
