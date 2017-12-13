<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Cache\Driver;

use Cross\Exception\CoreException;
use Redis;

/**
 * @author wonli <wonli@live.com>
 * Class RedisDriver
 * @package Cross\Cache\Driver
 */
class RedisDriver
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Redis
     */
    protected $link;

    /**
     * @var array
     */
    protected $option;

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

        if (!isset($option['host'])) {
            $option['host'] = '127.0.0.1';
        }

        if (!isset($option['port'])) {
            $option['port'] = 6379;
        }

        if (!isset($option['timeout'])) {
            $option['timeout'] = 3;
        }

        if (strcasecmp(PHP_OS, 'linux') == 0 && !empty($option['unix_socket'])) {
            $id = $option['unix_socket'];
            $use_unix_socket = true;
        } else {
            $id = "{$option['host']}:{$option['port']}:{$option['timeout']}";
            $use_unix_socket = false;
        }

        static $connects;
        if (!isset($connects[$id])) {
            $redis = new Redis();
            if ($use_unix_socket) {
                $redis->connect($option['unix_socket']);
            } else {
                $redis->connect($option['host'], $option['port'], $option['timeout']);
            }

            if (!empty($option['pass'])) {
                $authStatus = $redis->auth($option['pass']);
                if (!$authStatus) {
                    throw new CoreException('Redis auth failed !');
                }
            }

            $connects[$id] = $redis;
        } else {
            $redis = &$connects[$id];
        }

        $this->id = $id;
        $this->link = $redis;
        $this->option = $option;
    }

    /**
     * 获取连接属性
     *
     * @return array
     */
    function getLinkOption()
    {
        return $this->option;
    }

    /**
     * 调用redis类提供的方法
     *
     * @param $method
     * @param $argv
     * @return mixed|null
     * @throws CoreException
     */
    public function __call($method, $argv)
    {
        $result = null;
        if (method_exists($this->link, $method)) {
            $this->selectCurrentDatabase();
            $result = ($argv == null)
                ? $this->link->$method()
                : call_user_func_array(array($this->link, $method), $argv);
        }

        return $result;
    }

    /**
     * 选择当前数据库
     *
     * @throws CoreException
     */
    protected function selectCurrentDatabase()
    {
        static $selected = null;

        $db = &$this->option['db'];
        $current = $this->id . ':' . $db;
        if ($selected !== $current) {
            $select_ret = $this->link->select($db);
            if ($select_ret) {
                $selected = $current;
            } else {
                throw new CoreException("Redis select DB($current) failed!");
            }
        }
    }
}
