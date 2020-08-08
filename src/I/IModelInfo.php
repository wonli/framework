<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\I;

/**
 * 数据库表模型接口
 *
 * Interface IModelInfo
 * @package Cross\I
 */
interface IModelInfo
{
    /**
     * 获取表主键
     *
     * @return string
     */
    function getPK(): string;

    /**
     * 获取模型信息
     *
     * @return array
     */
    function getModelInfo(): array;

    /**
     * 获取表字段信息
     *
     * @return array
     */
    function getFieldInfo(): array;

    /**
     * 获取分表配置
     *
     * @return array
     */
    function getSplitConfig(): array;

    /**
     * 获取数据库配置文件地址
     *
     * @return string
     */
    function getConfigFile(): string;
}