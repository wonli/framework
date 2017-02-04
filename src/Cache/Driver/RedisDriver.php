<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Cache\Driver;

use Cross\Exception\CoreException;
use Exception;
use Redis;

/**
 * @Auth: wonli <wonli@live.com>
 * Class RedisDriver
 * @package Cross\Cache\Driver
 */
class RedisDriver
{
    /**
     * @var Redis
     */
    protected $link;

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
    function __construct(array $option)
    {
        if (!extension_loaded('redis')) {
            throw new CoreException('Not support redis extension !');
        }

        $redis = new Redis();
        if (strcasecmp(PHP_OS, 'linux') == 0 && !empty($option['unix_socket'])) {
            $redis->connect($option['unix_socket']);
        } else {
            $redis->connect($option['host'], $option['port'], $option['timeout']);
        }

        $authStatus = true;
        if (!empty($option['pass'])) {
            $authStatus = $redis->auth($option['pass']);
        }

        try {
            if ($authStatus) {
                $redis->select($option['db']);
                $this->link = $redis;
            } else {
                throw new CoreException('Redis auth failed !');
            }
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
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
