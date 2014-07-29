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
use Memcache;

/**
 * @Auth: wonli <wonli@live.com>
 * Class MemcacheBase
 * @package cross\cache
 */
class MemcacheBase
{
    /**
     * @var Memcache
     */
    public $link;

    function __construct($option)
    {
        if (!extension_loaded('memcache')) {
            throw new CoreException('NOT_SUPPORT : memcache');
        }

        $mc = new Memcache();
        $mc->addserver($option['host'], $option['port']);
        $this->link = $mc;
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
