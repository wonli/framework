<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\DB\SQLAssembler;

/**
 * @Auth: wonli <wonli@live.com>
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
     */
    public function find($table, $fields, $where, $order = 1, & $page = array('p' => 1, 'limit' => 50), $group_by = 1)
    {
        $params = array();
        $table = $this->table_prefix . $table;
        $field_str = $this->parseFields($fields);
        $where_str = $this->parseWhere($where, $params);
        $order_str = $this->parseOrder($order);
        $group_str = $this->parseGroup($group_by);

        $page['limit'] = max(1, (int)$page['limit']);
        $page['total_page'] = ceil($page['result_count'] / $page['limit']);
        $page['p'] = max(1, min($page['p'], $page['total_page']));

        //offset 起始位置
        $offset = $page['limit'] * ($page['p'] - 1);

        if (1 !== $group_by) {
            $sql_tpl = "SELECT %s FROM {$table} WHERE %s GROUP BY %s ORDER BY %s LIMIT %s OFFSET %s";
            $sql = sprintf($sql_tpl, $field_str, $where_str, $group_str, $order_str, $page['limit'], $offset);
        } else {
            $sql_tpl = "SELECT %s FROM {$table} WHERE %s ORDER BY %s LIMIT %s OFFSET %s";
            $sql = sprintf($sql_tpl, $field_str, $where_str, $order_str, $page['limit'], $offset);
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
    public function limit($start, $end = false)
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
