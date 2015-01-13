<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.1
 */
namespace Cross\Core;

use ArrayIterator;

/**
 * @Auth: wonli <wonli@live.com>
 * Class ArrayMap
 * @package Cross\Core
 */
class ArrayMap extends ArrayIterator
{
    /**
     * 构造函数
     *
     * @param array $array
     */
    public function __construct(array $array = array())
    {
        foreach ($array as &$value) {
            if (is_array($value) && isset($value)) {
                $value = new self($value);
            }
        }
        parent::__construct($array);
    }

    /**
     * 实例化类
     *
     * @param $array
     * @return CrossArray
     */
    static public function init($array)
    {
        return new self($array);
    }

    /**
     * _get
     *
     * @param $index
     * @return mixed
     */
    public function __get($index)
    {
        return $this->offsetGet($index);
    }

    /**
     * 设置
     *
     * @param $index
     * @param $value
     */
    public function __set($index, $value)
    {
        if (is_array($value) && isset($value)) {
            $value = new self($value);
        }

        $this->offsetSet($index, $value);
    }

    /**
     * 值是否存在
     *
     * @param $index
     * @return bool
     */
    public function __isset($index)
    {
        return $this->offsetExists($index);
    }

    /**
     * 清空值
     *
     * @param $index
     */
    public function __unset($index)
    {
        $this->offsetUnset($index);
    }

    /**
     * 转换为数组
     *
     * @param array $array
     * @return array
     */
    public function toArray($array = array())
    {
        if (empty($array)) {
            $array = $this->getArrayCopy();
        }

        foreach ($array as &$value) {
            if ($value instanceof self) {
                $value = $value->toArray();
            }
        }

        return $array;
    }

    /**
     * 输出字符串
     *
     * @return mixed
     */
    public function __toString()
    {
        return var_export($this->toArray(), true);
    }

    /**
     * 输出为json
     *
     * @return string
     */
    public function json()
    {
        return json_encode($this->toArray());
    }

    /**
     * 设置值
     *
     * @param $index
     * @param $value
     */
    public function put($index, $value)
    {
        if (is_array($value) && isset($value)) {
            $value = new self($value);
        }
        $this->offsetSet($index, $value);
    }

    /**
     * @see put()
     * @param $index
     * @param $value
     */
    public function set($index, $value)
    {
        $this->put($index, $value);
    }

    /**
     * 获取值
     * <ul>
     *  <li>$index为字符串的时候 获取配置数组,此时设定$key 则获取数组中指定项的值</li>
     *  <li>$index为数组的时候 获取数组中指定的配置项</li>
     * </ul>
     *
     * @param $index
     * @param null $key
     * @return array|bool|mixed
     */
    public function get($index, $key = null)
    {
        if (is_array($index)) {
            $result = array();
            foreach ($index as $i) {
                if ($this->__isset($i)) {
                    $result[$i] = $this->offsetGet($i);
                }
            }

            return $result;
        } else {
            if ($this->__isset($index)) {
                $index_value = $this->offsetGet($index);
                if (null !== $key && $index_value instanceof self) {
                    return $index_value->get($key);
                }

                return $index_value;
            }

            return false;
        }
    }
}
