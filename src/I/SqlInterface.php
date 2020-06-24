<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\I;

/**
 * Interface SqlInterface
 *
 * @package Cross\I
 */
interface SqlInterface
{
    /**
     * 获取一条数据
     *
     * @param string $table 表名
     * @param string $fields 字段
     * @param string|array $where 条件(建议只使用字符串常量,包含变量时请使用数组)
     * @return mixed
     */
    function get(string $table, string $fields, $where);

    /**
     * 批量获取表中的数据
     *
     * @param string $table 表名
     * @param string $fields 要获取的字段名
     * @param string|array $where 条件(建议只使用字符串常量,包含变量时请使用数组)
     * @param int|string $order 排序
     * @param int|string $group_by 分组
     * @param int|string $limit 0表示无限制
     * @return mixed
     */
    function getAll(string $table, string $fields, $where = [], $order = null, $group_by = null, int $limit = 0);

    /**
     * 带分页的查询
     *
     * @param string $table 表名
     * @param string $fields 字段名
     * @param string|array $where 条件(建议只使用字符串常量,包含变量时请使用数组)
     * @param array $page ['p', 'limit'] p,当前页 limit,分页条数
     * @param string|int $order 排序
     * @param int $group_by
     * @return mixed
     */
    function find(string $table, string $fields, $where, array &$page = ['p' => 1, 'limit' => 10], $order = null, $group_by = null);

    /**
     * 添加数据
     *
     * @param string $table 表名
     * @param string $data 要插入的数据
     * @param bool $multi 是否批量插入
     * @return mixed
     */
    function add(string $table, $data, bool $multi = false);

    /**
     * 更新数据
     *
     * @param string $table 表名
     * @param string $data 要更新的数据
     * @param string $where 筛选条件
     * @return mixed
     */
    function update(string $table, $data, $where);

    /**
     * 删除数据
     *
     * @param string $table 表名
     * @param string $where 条件
     * @return mixed
     */
    function del(string $table, $where);

}
