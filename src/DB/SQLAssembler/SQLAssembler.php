<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.3
 */
namespace Cross\DB\SQLAssembler;

use Cross\Exception\CoreException;
use Cross\Exception\FrontException;
use Cross\I\SqlInterface;

/**
 * @Auth: wonli <wonli@live.com>
 * Class SQLAssembler
 * @package Cross\DB\SQLAssembler
 */
class SQLAssembler implements SqlInterface
{
    /**
     * @var string
     */
    protected $sql;

    /**
     * @var string
     */
    protected $params;

    /**
     * 获取单条数据sql语句
     *
     * @param string $table database table
     * @param string $fields fields
     * @param string $where conditions
     * @return mixed
     */
    function get($table, $fields, $where)
    {
        $params = array();
        $one_sql = "SELECT %s FROM {$table} WHERE %s LIMIT 1";

        $field_str = $this->parseFields($fields);
        $where_str = $this->parseWhere($where, $params);

        $sql = sprintf($one_sql, $field_str, $where_str);
        $this->setSQL($sql);
        $this->setParams($params);
    }

    /**
     * 取出所有结果
     *
     * @param string $table 联合查询$table变量 $table = table_a a LEFT JOIN table_b b ON a.id=b.aid;
     * @param string $fields 要查询的字段 所有字段的时候 $fields='*'
     * @param string $where 查询条件
     * @param int $order 排序
     * @param int $group_by
     * @return array
     */
    public function getAll($table, $fields, $where = '', $order = 1, $group_by = 1)
    {
        $params = array();
        $field_str = $this->parseFields($fields);
        $where_str = $this->parseWhere($where, $params);
        $order_str = $this->parseOrder($order);
        $group_str = $this->parseGroup($group_by);

        if (1 !== $group_by) {
            $sql_tpl = "SELECT %s FROM {$table} WHERE %s GROUP BY %s ORDER BY %s";
            $sql = sprintf($sql_tpl, $field_str, $where_str, $group_str, $order_str);
        } else {
            $sql_tpl = "SELECT %s FROM {$table} WHERE %s ORDER BY %s";
            $sql = sprintf($sql_tpl, $field_str, $where_str, $order_str);
        }

        $this->setSQL($sql);
        $this->setParams($params);
    }

    /**
     * 插入
     *
     * @param string $table 表名称
     * @param array $data 要处理的数据,跟表中的字段对应
     * @param bool $multi <pre>
     * @param array $insert_data
     *  是否批量插入数据,如果是
     *      $data = array(
     *          'fields' => array(字段1,字段2,...),
     *          'values' => array(
     *                      array(字段1的值, 字段2的值),
     *                      array(字段1的值, 字段2的值))
     *      );
     *  </pre>
     * @return array|bool
     * @throws FrontException
     */
    public function add($table, $data, $multi = false, & $insert_data = array())
    {
        $params = array();
        $field = $value = '';
        $insert_sql = "INSERT INTO {$table} (%s) VALUES (%s)";

        if (true === $multi) {
            foreach ($data ['fields'] as $d) {
                $field .= "{$d},";
                $value .= '?,';
            }

            $params = array();
            $sql = sprintf($insert_sql, rtrim($field, ','), rtrim($value, ','));
            foreach ($data ['values'] as $p) {
                $params[] = $p;
            }
        } else {
            foreach ($data as $_field => $_value) {
                $field .= "{$_field},";
                $value .= '?,';
                $params[] = $_value;
            }
            $sql = sprintf($insert_sql, rtrim($field, ','), rtrim($value, ','));
        }

        $this->setSQL($sql);
        $this->setParams($params);
    }

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
        $field_str = $this->parseFields($fields);
        $where_str = $this->parseWhere($where, $params);
        $order_str = $this->parseOrder($order);
        $group_str = $this->parseGroup($group_by);

        $page['limit'] = max(1, (int)$page['limit']);
        $page['total_page'] = ceil($page['result_count'] / $page['limit']);
        $page['p'] = max(1, min($page['p'], $page['total_page']));

        $p = ($page['p'] - 1) * $page['limit'];
        if (1 !== $group_by) {
            $sql_tpl = "SELECT %s FROM {$table} WHERE %s GROUP BY %s ORDER BY %s LIMIT %s";
            $sql = sprintf($sql_tpl, $field_str, $where_str, $group_str, $order_str, "{$p}, {$page['limit']}");
        } else {
            $sql_tpl = "SELECT %s FROM {$table} WHERE %s ORDER BY %s LIMIT %s";
            $sql = sprintf($sql_tpl, $field_str, $where_str, $order_str, "{$p}, {$page['limit']}");
        }

        $this->setSQL($sql);
        $this->setParams($params);
    }

    /**
     * 更新
     *
     * @param string $table
     * @param string $data
     * @param string $where
     * @return mixed|void
     * @throws CoreException
     */
    public function update($table, $data, $where)
    {
        $up_sql = "UPDATE {$table} SET %s WHERE %s";

        $field = '';
        $params = array();
        if (!empty($data)) {
            if (is_array($data)) {
                foreach ($data as $d_key => $d_value) {
                    $field .= "{$d_key} = ? ,";
                    $params [] = strval($d_value);
                }
            } else {
                $field = $data;
            }
        }

        $where_params = array();
        $where_str = $this->parseWhere($where, $where_params);

        foreach ($where_params as $wp) {
            $params [] = $wp;
        }

        $sql = sprintf($up_sql, trim($field, ','), $where_str);
        $this->setSQL($sql);
        $this->setParams($params);
    }

    /**
     * 删除
     *
     * @param string $table
     * @param string|array $where
     * @param bool $multi 是否批量删除数据
     *      $where = array(
     *          'fields' => array(字段1,字段2,...),
     *          'values' => array(
     *                      array(字段1的值, 字段2的值),
     *                      array(字段1的值, 字段2的值))
     *      );
     * @return mixed|void
     * @throws CoreException
     */
    public function del($table, $where, $multi = false)
    {
        $del_sql = "DELETE FROM %s WHERE %s";

        $params = array();
        if (true === $multi) {
            if (empty($where ['fields']) || empty($where ['values'])) {
                throw new CoreException('data format error!');
            }

            $where_condition = array();
            foreach ($where ['fields'] as $d) {
                $where_condition[] = "{$d} = ?";
            }

            $where_str = implode(' AND ', $where_condition);
            $sql = sprintf($del_sql, $table, $where_str);
            foreach ($where ['values'] as $p) {
                $params[] = $p;
            }

        } else {
            $where_str = $this->parseWhere($where, $params);
            $sql = sprintf($del_sql, $table, $where_str);
        }

        $this->setSQL($sql);
        $this->setParams($params);
    }

    /**
     * @param $sql
     */
    protected function setSQL($sql)
    {
        $this->sql = $sql;
    }

    /**
     * @param $params
     */
    protected function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function getSQL()
    {
        return $this->sql;
    }

    /**
     * @return string|array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * 解析字段
     *
     * @param string|array $fields
     * @return string
     */
    public function parseFields($fields)
    {
        if (empty($fields)) {
            $field_str = '*';
        } else {
            if (is_array($fields)) {
                $field_str = implode(',', $fields);
            } else {
                $field_str = $fields;
            }
        }

        return $field_str;
    }

    /**
     * 解析where
     *
     * @param string|array $where
     * @param array $params
     * @return string
     * @throws CoreException
     */
    public function parseWhere($where, & $params)
    {
        $condition = array();
        if (!empty($where)) {
            if (is_array($where)) {
                foreach ($where as $w_key => $w_value) {
                    $operator = '=';
                    if (is_array($w_value)) {
                        list($operator, $n_value) = $w_value;
                        $operator = strtoupper(trim($operator));
                        $w_value = $n_value;
                    }

                    switch ($operator) {
                        case 'OR':
                        case 'AND':
                            if (!is_array($w_value)) {
                                throw new CoreException('OR need a array parameter');
                            }
                            foreach ($w_value as $or_exp_val) {
                                $ex_operator = '=';
                                if (is_array($or_exp_val)) {
                                    list($ex_operator, $n_value) = $or_exp_val;
                                    $or_exp_val = $n_value;
                                }

                                $condition[" {$operator} "][] = "{$w_key} {$ex_operator} ?";
                                $params [] = $or_exp_val;
                            }
                            break;

                        case 'IN':
                        case 'NOT IN':
                            if (!is_array($w_value)) {
                                throw new CoreException('IN or NOT IN need a array parameter');
                            }

                            $in_where_condition = array();
                            foreach ($w_value as $in_exp_val) {
                                $params[] = $in_exp_val;
                                $in_where_condition [] = '?';
                            }

                            $condition[' AND '][] = sprintf('%s %s (%s)', $w_key, $operator, implode(',', $in_where_condition));
                            break;

                        case 'BETWEEN':
                        case 'NOT BETWEEN':
                            if (!is_array($w_value)) {
                                throw new CoreException('BETWEEN need a array parameter');
                            }

                            if (!isset($w_value[0]) || !isset($w_value[1])) {
                                throw new CoreException('BETWEEN parameter error!');
                            }

                            $condition[' AND '][] = sprintf('%s %s %s AND %s', $w_key, $operator, $w_value[0], $w_value[1]);
                            break;

                        default:
                            $condition[' AND '][] = "{$w_key} {$operator} ?";
                            $params [] = $w_value;
                    }
                }

                $r = array();
                foreach ($condition as $sql_opt => $where_condition) {
                    $r[] = implode($sql_opt, $where_condition);
                }

                $where_str = implode(' AND ', $r);
            } else {
                $where_str = $where;
            }
        } else {
            $where_str = '1=1';
        }

        return $where_str;
    }

    /**
     * 解析order
     *
     * @param string $order
     * @return int|string
     */
    public function parseOrder($order)
    {
        if (!empty($order)) {
            if (is_array($order)) {
                $order_str = implode(',', $order);
            } else {
                $order_str = $order;
            }
        } else {
            $order_str = 1;
        }

        return $order_str;
    }

    /**
     * 解析group by
     *
     * @param string $group_by
     * @return int
     */
    public function parseGroup($group_by)
    {
        if (!empty($group_by)) {
            $group_str = $group_by;
        } else {
            $group_str = 1;
        }

        return $group_str;
    }
}
