<?php
/**
 * @Auth wonli <wonli@live.com>
 *
 * Class RedisCache
 */

class RedisCache
{
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
