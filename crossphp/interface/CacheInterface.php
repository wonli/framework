<?php
/**
 * @Auth wonli <wonli@live.com>
 * Class CacheInterface
 */
interface CacheInterface
{
    /**
     * @param string $key
     * @return mixed get cache
     */
    function get( $key = '' );

    /**
     * @param $key
     * @param $value
     * @return mixed set
     */
    function set( $key, $value );
}
