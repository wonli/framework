<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.1
 */
namespace cross\cache;

use cross\exception\CoreException;
use Redis;

/**
 * @Auth: wonli <wonli@live.com>
 * Class RedisCache
 * @package cross\cache
 */
class RedisCache
{
    /**
     * @var redis
     */
    public $link;

    /**
     * 连接redis
     * <pre>
     * unixsocket设置
     * unixsocket /tmp/redis.sock
     * unixsocketperm 777
     * </pre>
     *
     * @param $option
     * @throws \cross\exception\CoreException
     */
    function __construct($option)
    {
        if (!extension_loaded('redis')) {
            throw new CoreException('NOT_SUPPORT : redis');
        }

        $obj = new Redis();
        if (strcasecmp(PHP_OS, 'linux') == 0 && !empty($option['unix_socket'])) {
            $obj->connect('/tmp/redis.sock');
        } else {
            $obj->connect($option ['host'], $option ['port']);
        }

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
        if (method_exists($this->link, $method)) {
            $result = ($argv == null)
                ? $this->link->$method()
                : call_user_func_array(array($this->link, $method), $argv);
        }

        return $result;
    }
}
