<?php
/**
 * @Auth wonli <wonli@live.com>
 *
 * Class CacheInterface
 */

interface CacheInterface
{
    /**
     * @return mixed get cache
     */
    function get( $key );

    /**
     * @return mixed set
     */
    function set( $key, $value );
}