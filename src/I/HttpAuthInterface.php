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
     * @param string|array $value
     * @param int $expire
     * @return mixed
     */
    public function set($key, $value, $expire = 0);

    /**
     * 获取
     *
     * @param string $key
     * @param bool $deCode
     * @return mixed
     */
    public function get($key, $deCode = false);
}
