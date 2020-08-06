<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\I;

use PDO;

/**
 * PDO连接类的统一接口
 *
 * Interface PDOConnector
 * @package Cross\I
 */
interface PDOConnector
{
    /**
     * 获取一个单例实例
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param array $options
     * @return mixed
     */
    static function getInstance(string $dsn, string $user, $password, array $options);

    /**
     * 获取表的主键名
     *
     * @param string $table_name
     * @return string
     */
    function getPK(string $table_name): string;

    /**
     * 获取表的字段信息
     *
     * @param string $table_name
     * @param bool $fields_map
     * @return mixed
     */
    function getMetaData(string $table_name, bool $fields_map = true): array;

    /**
     * 设置序号（oracle）
     *
     * @param string $sequence
     */
    function setSequence(string $sequence): void;

    /**
     * 返回一个PDO连接对象的实例
     *
     * @return mixed
     */
    function getPDO(): PDO;

    /**
     * 最后插入时自增ID的值
     *
     * @return mixed
     */
    function lastInsertId();
}
