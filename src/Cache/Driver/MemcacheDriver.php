<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Cache\Driver;

use Cross\Exception\CoreException;
use Exception;
use Memcache;

/**
 * @author wonli <wonli@live.com>
 * Class MemcacheDriver
 * @package Cross\Cache\Driver
 */
class MemcacheDriver
{
    /**
     * @var Memcache
     */
    public $link;

    /**
     * 集群参数默认配置
     *
     * @var array
     */
    protected $defaultOptions = [
        'persistent' => true,
        'weight' => 1,
        'timeout' => 1,
        'retry_interval' => 15,
        'status' => true,
        'failure_callback' => null
    ];

    /**
     * MemcacheDriver constructor.
     *
     * @param array $option
     * @throws CoreException
     */
    function __construct(array $option)
    {
        if (!extension_loaded('memcache')) {
            throw new CoreException('Not support memcache extension !');
        }

        if (!isset($option['host'])) {
            $option['host'] = '127.0.0.1';
        }

        if (!isset($option['port'])) {
            $option['port'] = 11211;
        }

        if (!isset($option['timeout'])) {
            $option['timeout'] = 1;
        }

        try {
            $mc = new Memcache();
            //集群服务器IP用|分隔
            if (false !== strpos($option['host'], '|')) {
                $opt = &$this->defaultOptions;
                foreach ($opt as $k => &$v) {
                    if (isset($option[$k])) {
                        $v = $option[$k];
                    }
                }

                $serverList = explode('|', $option['host']);
                foreach ($serverList as $server) {
                    $host = $server;
                    $port = $option['port'];
                    if (false !== strpos($server, ':')) {
                        list($host, $port) = explode(':', $server);
                    }

                    $host = trim($host);
                    $port = trim($port);
                    $mc->addServer($host, $port, $opt['persistent'], $opt['weight'], $opt['timeout'],
                        $opt['retry_interval'], $opt['status'], $opt['failure_callback']);
                }
            } else {
                $mc->connect($option['host'], $option['port'], $option['timeout']);
            }

            $this->link = $mc;
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * 从缓存中获取内容
     *
     * @param string $key
     * @param int|array $flag
     * @return array|string
     */
    function get(string $key, &$flag = 0)
    {
        return $this->link->get($key, $flag);
    }

    /**
     * 调用Memcache类提供的方法
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
