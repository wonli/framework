<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\I;

/**
 * Interface HttpAuthInterface
 *
 * @package Cross\I
 */
interface HttpAuthInterface
{
    /**
     * 设置
     *
     * @param string $key
     * @param array|string $value
     * @param int $expire
     * @return mixed
     */
    public function set(string $key, array|string $value, int $expire = 0): bool;

    /**
     * 获取
     *
     * @param string $key
     * @param bool $deCode
     * @return mixed
     */
    public function get(string $key, bool $deCode = false): mixed;
}
