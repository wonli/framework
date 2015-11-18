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
use Memcache;

/**
 * @Auth: wonli <wonli@live.com>
 * Class MemcacheDriver
 * @package Cross\Cache\Driver
 */
class MemcacheDriver
{
    /**
     * @var Memcache
     */
    public $link;

    function __construct(array $option)
    {
        if (!extension_loaded('memcache')) {
            throw new CoreException('Not support memcache extension !');
        }

        try {
            $mc = new Memcache();
            $mc->addserver($option['host'], $option['port']);
            $this->link = $mc;
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
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
