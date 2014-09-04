<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.2
 */
namespace cross\i;

/**
 * Interface HttpAuthInterface
 *
 * @package cross\i
 */
interface HttpAuthInterface
{

    /**
     * 设置
     *
     * @param     $key
     * @param     $value
     * @param int $exp
     * @return mixed
     */
    public function set($key, $value, $exp = 86400);

    /**
     * 获取
     *
     * @param      $key
     * @param bool $de
     * @return mixed
     */
    public function get($key, $de = false);
}
