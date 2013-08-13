<?php
class CrossArray Extends ArrayIterator
{

    /**
     * @var array 数据
     */
    protected $init;

    function __construct( $data )
    {
        parent::__construct();
        $this->init = $data;
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
     * @param $confing 字符串或数组
     * @param $name null或boolean
     * @return string或array
     */
    function get($config, $name=null)
    {
        if(is_string($config))
        {
            if(isset($this->init[$config])) {
                if($name) {
                    if(isset($this->init[$config][$name])) {
                        return $this->init[$config][$name];
                    } else {
                        return false;
                    }
                }
                return $this->init[$config];
            }
        }

        if(is_array($config)) {

            if($name === true) {
                foreach($config as $item) {
                    if(isset($this->init[$item])) {
                        unset($this->init[$item]);
                    }
                }
                return $this->init;
            } else {
                $_returnArr = array();
                foreach($config as $item) {
                    if(isset($this->init[$item])) {
                        $_returnArr[$item] = $this->init[$item];
                    }
                }
            }
            return $_returnArr;
        }
    }

    /**
     * 返回全部数据
     *
     * @param   $obj 是否返回对象
     * @return array/object
     */
    function getAll($obj = false)
    {
        if($obj) {
            return $this->arrayToObject($this->init);
        }
        return $this->init;
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