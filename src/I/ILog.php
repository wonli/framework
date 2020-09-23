<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\I;

/**
 * 日志接口
 *
 * Interface ILog
 * @package Cross\I
 */
interface ILog
{
    /**
     * 输出日志
     *
     * @param string $e 文件名或日志标签名
     * @param mixed $log
     * @return mixed
     */
    function write(string $e, $log = '');

    /**
     * 添加到日志
     *
     * @param string $tag 日志标签
     * @param mixed $data 日志内容
     * @return $this
     */
    function addToLog(string $tag, $data = []);
}