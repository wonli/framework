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
     * @param array|string $where 条件(建议只使用字符串常量,包含变量时请使用数组)
     * @return mixed
     */
    function get(string $table, string $fields, array|string $where): mixed;

    /**
     * 批量获取表中的数据
     *
     * @param string $table 表名
     * @param string $fields 要获取的字段名
     * @param array|string $where 条件(建议只使用字符串常量,包含变量时请使用数组)
     * @param mixed|null $order 排序
     * @param mixed|null $groupBy 分组
     * @param int $limit 0表示无限制
     * @return mixed
     */
    function getAll(string $table, string $fields, array|string $where = [],
                    mixed $order = null, mixed $groupBy = null, int $limit = 0): mixed;

    /**
     * 带分页的查询
     *
     * @param string $table 表名
     * @param string $fields 字段名
     * @param array|string $where 条件(建议只使用字符串常量,包含变量时请使用数组)
     * @param array $page ['p', 'limit'] p,当前页 limit,分页条数
     * @param mixed|null $order 排序
     * @param mixed|null $groupBy
     * @return mixed
     */
    function find(string $table, string $fields, array|string $where,
                  array  &$page = ['p' => 1, 'limit' => 10], mixed $order = null, mixed $groupBy = null): mixed;

    /**
     * 添加数据
     *
     * @param string $table 表名
     * @param mixed $data 要插入的数据
     * @param bool $multi 是否批量插入
     * @return mixed
     */
    function add(string $table, mixed $data, bool $multi = false): mixed;

    /**
     * 更新数据
     *
     * @param string $table 表名
     * @param mixed $data 要更新的数据
     * @param mixed $where 筛选条件
     * @return mixed
     */
    function update(string $table, mixed $data, mixed $where): mixed;

    /**
     * 删除数据
     *
     * @param string $table 表名
     * @param mixed $where 条件
     * @return mixed
     */
    function del(string $table, mixed $where): mixed;

}
