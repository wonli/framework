<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.1
 */
namespace cross\i;

/**
 * Interface CacheInterface
 *
 * @package cross\i
 */
interface CacheInterface
{
    /**
     * @param string $key
     * @return mixed get cache
     */
    function get($key = '');

    /**
     * @param $key
     * @param $value
     * @return mixed set
     */
    function set($key, $value);
}
