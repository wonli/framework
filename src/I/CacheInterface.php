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
    function get(string $key = ''): mixed;

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    function set(string $key, mixed $value): bool;

    /**
     * @param string $key
     * @return mixed
     */
    function del(string $key): bool;
}
