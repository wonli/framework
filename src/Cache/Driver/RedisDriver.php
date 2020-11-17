<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Cache\Driver;

use Cross\Exception\CoreException;
use RedisCluster;
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
     * @var Redis|RedisCluster
     */
    protected $link;

    /**
     * 是否集群模式
     *
     * @var bool
     */
    protected $clusterMode = false;

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
     * @throws
     */
    function __construct(array $option)
    {
        if (!extension_loaded('redis')) {
            throw new CoreException('Not support redis extension !');
        }

        $option['host'] = $option['host'] ?? '127.0.0.1';
        $option['port'] = $option['port'] ?? 6379;
        $option['timeout'] = $option['timeout'] ?? 3;
        $option['readTimeout'] = $option['readTimeout'] ?? $option['timeout'];
        $option['clusterName'] = $option['clusterName'] ?? null;

        //是否使用长链接
        if (PHP_SAPI == 'cli') {
            ini_set('default_socket_timeout', -1);
            $persistent = true;
        } else {
            $persistent = $option['persistent'] ?? false;
        }

        if (is_array($option['host'])) {
            $id = implode('|', $option['host']);
            $useUnixSocket = false;
            $this->clusterMode = true;
        } else {
            $this->clusterMode = false;
            if (strcasecmp(PHP_OS, 'linux') == 0 && !empty($option['unix_socket'])) {
                $id = $option['unix_socket'];
                $useUnixSocket = true;
            } else {
                $id = "{$option['host']}:{$option['port']}:{$option['timeout']}";
                $useUnixSocket = false;
            }
        }

        static $connects;
        if (!isset($connects[$id])) {
            if ($this->clusterMode) {
                $redis = new RedisCluster($option['clusterName'], $option['host'], $option['timeout'],
                    $option['readTimeout'], $persistent, $option['pass'] ?: null);
            } else {
                $redis = new Redis();
                if ($persistent) {
                    if ($useUnixSocket) {
                        $redis->pconnect($option['unix_socket']);
                    } else {
                        $redis->pconnect($option['host'], $option['port'], 0);
                    }
                } else {
                    if ($useUnixSocket) {
                        $redis->connect($option['unix_socket']);
                    } else {
                        $redis->connect($option['host'], $option['port'], $option['timeout']);
                    }
                }

                if (!empty($option['pass'])) {
                    $authStatus = $redis->auth($option['pass']);
                    if (!$authStatus) {
                        throw new CoreException('Redis auth failed !');
                    }
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
                : call_user_func_array([$this->link, $method], $argv);
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
        if ($this->clusterMode) {
            return;
        }

        static $selected = null;
        $db = &$this->option['db'];
        $current = $this->id . ':' . $db;
        if ($selected !== $current) {
            $selectRet = $this->link->select($db);
            if ($selectRet) {
                $selected = $current;
            } else {
                throw new CoreException("Redis select DB($current) failed!");
            }
        }
    }
}
