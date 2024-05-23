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
     */
    function set(string $value): void;

    /**
     * 获取缓存内容
     *
     * @return mixed
     */
    function get(): string;

    /**
     * 是否有效
     *
     * @return bool
     */
    function isValid(): bool;

    /**
     * 缓存配置
     *
     * @param array $config
     * @return void
     */
    function setConfig(array $config = []): void;

    /**
     * 获取缓存配置
     *
     * @return mixed
     */
    function getConfig(): array;
}
