<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class CrossArray
 */
class CrossArray
{

    /**
     * @var array 数据
     */
    protected $data;

    function __construct( $data )
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
     * $config为字符串的时候 获取配置数组,此时设定$name 则获取数组中指定项的值
     * $config为数组的时候 获取数组中指定的配置项,如果$name为true 则获取指定项之外的配置项
     *
     * @param $config
     * @param null|boolean $name
     * @return string|array
     */
    function get($config, $name=null)
    {
        if(is_string($config))
        {
            if(isset($this->data[$config])) {
                if($name) {
                    if(isset($this->data[$config][$name])) {
                        return $this->data[$config][$name];
                    } else {
                        return false;
                    }
                }
                return $this->data[$config];
            }
        }

        if(is_array($config)) {

            if($name === true) {
                foreach($config as $item) {
                    if(isset($this->data[$item])) {
                        unset($this->data[$item]);
                    }
                }
                return $this->data;
            } else {
                $_returnArr = array();
                foreach($config as $item) {
                    if(isset($this->data[$item])) {
                        $_returnArr[$item] = $this->data[$item];
                    }
                }
            }
            return $_returnArr;
        }
    }

    /**
     * 返回全部数据
     *
     * @param bool| $obj 是否返回对象
     * @return array/object
     */
    function getAll($obj = false)
    {
        if($obj) {
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
    function arrayToObject($d) {
        if (is_array($d)) {
            return (object) array_map(array($this, __FUNCTION__), $d);
        }
        else {
            return $d;
        }
    }
}
