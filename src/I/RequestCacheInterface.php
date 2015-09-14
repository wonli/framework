<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 */
namespace Cross\I;

/**
 * Interface CacheInterface
 *
 * @package Cross\I
 */
interface RequestCacheInterface
{
    /**
     * @param $key
     * @param $value
     * @return mixed set
     */
    function set($key, $value);

    /**
     * @param string $key
     * @return mixed get cache
     */
    function get($key = '');

    /**
     * 缓存是否过期
     *
     * @return bool
     */
    function getExpireTime();

    /**
     * 缓存配置
     *
     * @param array $config
     * @return mixed
     */
    function setConfig($config = array());

    /**
     * 获取缓存配置
     *
     * @return mixed
     */
    function getConfig();
}
