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
interface RequestCacheInterface
{
    /**
     * 写入缓存
     *
     * @param string $value
     * @return mixed set
     */
    function set($value);

    /**
     * 获取缓存内容
     *
     * @return mixed get cache
     */
    function get();

    /**
     * 是否有效
     *
     * @return bool
     */
    function isValid();

    /**
     * 缓存配置
     *
     * @param array $config
     * @return mixed
     */
    function setConfig(array $config = array());

    /**
     * 获取缓存配置
     *
     * @return mixed
     */
    function getConfig();
}
