<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Lib\Upload;

/**
 * Interface IFilter
 *
 * @package lib\Upload
 * @author wonli <wonli@live.com>
 */
interface IFilter
{
    /**
     * 上传文件过滤
     *
     * @param mixed $file 文件信息
     * @param string $error 失败信息
     * @return bool 成功返回true
     */
    function filter(mixed $file, string &$error = ''): bool;
}