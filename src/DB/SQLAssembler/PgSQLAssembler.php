<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\DB\SQLAssembler;

use Cross\Exception\CoreException;

/**
 * @author wonli <wonli@live.com>
 * Class PgSQLAssembler
 * @package Cross\DB\SQLAssembler
 */
class PgSQLAssembler extends SQLAssembler
{
    /**
     * 带分页功能的查询
     *
     * @param string $table 联合查询$table变量 $table = table_a a LEFT JOIN table_b b ON a.id=b.aid;
     * @param string $fields 要查询的字段 所有字段的时候 $fields='*'
     * @param string $where 查询条件
     * @param int $order 排序
     * @param array $page 分页参数 默认返回50条记录
     * @param int|string $group_by
     * @return mixed|void
     * @throws CoreException
     */
    public function find(string $table, string $fields, $where, $order = null, array &$page = ['p' => 1, 'limit' => 50], $group_by = null)
    {
        $params = array();
        $field_str = $this->parseFields($fields);
        $where_str = $this->parseWhere($where, $params);
        $order_str = $this->parseOrder($order);

        //offset 起始位置
        $offset = $page['limit'] * ($page['p'] - 1);
        if (1 !== $group_by) {
            $group_str = $this->parseGroup($group_by);
            $sql = "SELECT {$field_str} FROM {$table} WHERE {$where_str} GROUP BY {$group_str} ORDER BY {$order_str} LIMIT {$page['limit']} OFFSET {$offset}";
        } else {
            $sql = "SELECT {$field_str} FROM {$table} WHERE {$where_str} ORDER BY {$order_str} LIMIT {$page['limit']} OFFSET {$offset}";
        }

        $this->setSQL($sql);
        $this->setParams($params);
    }

    /**
     * PgSQL的limit如果有第二个参数, 那么和mysql的limit行为保持一致, 并且offset()不生效
     *
     * @param int $start
     * @param bool|int $end
     * @return string
     */
    public function limit(int $start, $end = false): string
    {
        if ($end) {
            $limit = max(1, (int)$end);
            $offset = $limit * (max(1, (int)$start) - 1);

            $this->offset_is_valid = false;
            return "LIMIT {$limit} OFFSET {$offset} ";
        }

        $start = (int)$start;
        return "LIMIT {$start} ";
    }
}
