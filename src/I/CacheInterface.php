<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
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
    function get(string $key = '');

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed set
     */
    function set(string $key, $value);

    /**
     * @param string $key
     * @return mixed
     */
    function del(string $key);
}
