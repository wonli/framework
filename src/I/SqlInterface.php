<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.3.0
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
    function get($table, $fields, $where);

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
    function getAll($table, $fields, $where = '', $order = 1, $group_by = 1, $limit = 0);

    /**
     * 带分页的查询
     *
     * @param string $table 表名
     * @param string $fields 字段名
     * @param string|array $where 条件(建议只使用字符串常量,包含变量时请使用数组)
     * @param string|int $order 排序
     * @param array $page array('p', 'limit'); p表示当前页数, limit表示要取出的条数
     * @return mixed
     */
    function find($table, $fields, $where, $order = 1, & $page = array('p', 'limit'));

    /**
     * 添加数据
     *
     * @param string $table 表名
     * @param string $data 要插入的数据
     * @param bool $multi 是否批量插入
     * @return mixed
     */
    function add($table, $data, $multi = false);

    /**
     * 更新数据
     *
     * @param string $table 表名
     * @param string $data 要更新的数据
     * @param string $where 筛选条件
     * @return mixed
     */
    function update($table, $data, $where);

    /**
     * 删除数据
     *
     * @param string $table 表名
     * @param string $where 条件
     * @return mixed
     */
    function del($table, $where);

}
