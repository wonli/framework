<?php
/**
 * @Auth wonli <wonli@live.com>
 *
 * Class RedisCache
 */
class RedisCache
{
    /**
     * @var redis
     */
    public $link;

    function __construct($option)
    {
        if ( ! extension_loaded('redis') ) {
            throw new CoreException('NOT_SUPPERT : redis');
        }

        $obj = new redis();

        $obj->connect($option ['host'], $option ['port']);
        $obj->select($option['db']);
        $this->link = $obj;
    }

    /**
     * 调用redis类提供的方法
     *
     * @param $method
     * @param $argv
     * @return mixed|null
     */
    public function __call($method, $argv)
    {
        $result = null;
        if(method_exists($this->link, $method))
        {
            $result = ($argv == null)
                ? $this->link->$method()
                : call_user_func_array(array($this->link, $method), $argv);
        }
        return $result;
    }
}
