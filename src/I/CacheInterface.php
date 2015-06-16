<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.3.0
 */
namespace Cross\I;

/**
 * Interface CacheInterface
 *
 * @package Cross\I
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
