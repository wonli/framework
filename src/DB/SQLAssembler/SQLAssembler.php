<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.3.0
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
     * offset()在limit()中已经传递了第二个参数时不再生效
     *
     * @var bool
     */
    protected $offset_is_valid = true;

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
     * @param int|string $limit
     * @return array
     * @throws CoreException
     */
    public function getAll($table, $fields, $where = '', $order = 1, $group_by = 1, $limit = 0)
    {
        $params = array();
        $field_str = $this->parseFields($fields);
        $where_str = $this->parseWhere($where, $params);
        $order_str = $this->parseOrder($order);
        $group_str = $this->parseGroup($group_by);

        if (1 !== $group_by) {
            if (0 !== $limit) {
                $sql_tpl = "SELECT %s FROM {$table} WHERE %s GROUP BY %s ORDER BY %s LIMIT %s";
                $sql = sprintf($sql_tpl, $field_str, $where_str, $group_str, $order_str, $limit);
            } else {
                $sql_tpl = "SELECT %s FROM {$table} WHERE %s GROUP BY %s ORDER BY %s";
                $sql = sprintf($sql_tpl, $field_str, $where_str, $group_str, $order_str);
            }
        } else {
            if (0 !== $limit) {
                $sql_tpl = "SELECT %s FROM {$table} WHERE %s ORDER BY %s LIMIT %s";
                $sql = sprintf($sql_tpl, $field_str, $where_str, $order_str, $limit);
            } else {
                $sql_tpl = "SELECT %s FROM {$table} WHERE %s ORDER BY %s";
                $sql = sprintf($sql_tpl, $field_str, $where_str, $order_str);
            }
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
     * @throws CoreException
     */
    public function add($table, $data, $multi = false, & $insert_data = array())
    {
        $params = array();
        $field = $value = '';
        $insert_sql = "INSERT INTO {$table} (%s) VALUES (%s)";

        if (true === $multi) {
            if (empty($data['fields']) || empty($data['values'])) {
                throw new CoreException('data format error!');
            }

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
     * @param string $fields
     * @return string
     */
    public function select($fields = '*')
    {
        return "SELECT {$this->parseFields($fields)} ";
    }

    /**
     * @param $table
     * @return string
     */
    public function from($table)
    {
        return "FROM {$table} ";
    }

    /**
     * @param string|array $where
     * @param array $params
     * @return string
     * @throws CoreException
     */
    public function where($where, & $params)
    {
        return "WHERE {$this->parseWhere($where, $params)} ";
    }

    /**
     * @param int $start
     * @param bool|int $end
     * @return string
     */
    public function limit($start, $end = false)
    {
        if ($end) {
            $end = (int)$end;
            $this->offset_is_valid = false;
            return "LIMIT {$start}, {$end} ";
        }

        $start = (int)$start;
        return "LIMIT {$start} ";
    }

    /**
     * @param int $offset
     * @return string
     */
    public function offset($offset)
    {
        if ($this->offset_is_valid) {
            return "OFFSET {$offset} ";
        }

        return "";
    }

    /**
     * @param $order
     * @return string
     */
    public function orderBy($order)
    {
        return "ORDER BY {$this->parseOrder($order)} ";
    }

    /**
     * @param $group
     * @return string
     */
    public function groupBy($group)
    {
        return "GROUP BY {$this->parseOrder($group)} ";
    }

    /**
     * @param $having
     * @return string
     */
    public function having($having)
    {
        return "HAVING {$having} ";
    }

    /**
     * @param $procedure
     * @return string
     */
    public function procedure($procedure)
    {
        return "PROCEDURE {$procedure} ";
    }

    /**
     * @param $var_name
     * @return string
     */
    public function into($var_name)
    {
        return "INTO {$var_name} ";
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
        if (!empty($where)) {
            if (is_array($where)) {
                if (isset($where[1])) {
                    $where_str = $where[0];
                    if (!is_array($where[1])) {
                        $params = array($where[1]);
                    } else {
                        $params = $where[1];
                    }
                } else {
                    $where_str = $this->parseWhereFromHashMap($where, $params);
                }
            } else {
                $where_str = $where;
            }
        } else {
            $where_str = '1=1';
        }
        return $where_str;
    }

    /**
     * 解析关联数组
     *
     * @param array $where
     * @param array $params
     * @return string
     * @throws CoreException
     */
    private function parseWhereFromHashMap($where, & $params)
    {
        $where_str = '';
        $all_condition = array();
        foreach ($where as $field => $field_config) {
            $operator = '=';
            $field = trim($field);
            $is_mixed_field = false;
            $condition_connector = $connector = 'AND';
            $condition = array();

            if ($field[0] == '(' && $field[strlen($field) - 1] == ')') {
                $is_mixed_field = true;
            }

            if ($is_mixed_field === false && is_array($field_config)) {
                if (count($field_config) == 3) {
                    list($connector, $field_true_value, $condition_connector) = $field_config;
                } else {
                    list($connector, $field_true_value) = $field_config;
                }

                $condition_connector = strtoupper(trim($condition_connector));
                $connector = strtoupper(trim($connector));
                $field_config = $field_true_value;
            }

            switch ($connector) {
                case 'OR':
                    if (!is_array($field_config)) {
                        $field_config = array($field_config);
                    }
                    foreach ($field_config as $field_single_config) {
                        if (is_array($field_single_config)) {
                            list($operator, $single_field_value) = $field_single_config;
                            $params [] = $single_field_value;
                        } else {
                            $params [] = $field_single_config;
                        }
                        $condition[' OR '][] = "{$field} {$operator} ?";
                    }
                    break;

                case 'AND':
                    if ($is_mixed_field) {
                        $condition[" {$condition_connector} "][] = $field;
                        if (is_array($field_config)) {
                            foreach ($field_config as $f) {
                                $params [] = $f;
                            }
                        } else {
                            $params[] = $field_config;
                        }
                    } else {
                        if (is_array($field_config)) {
                            list($operator, $field_true_value) = $field_config;
                            $params [] = $field_true_value;
                        } else {
                            $params [] = $field_config;
                        }

                        $condition[' AND '][] = "{$field} {$operator} ?";
                    }
                    break;

                case 'IN':
                case 'NOT IN':
                    if (!is_array($field_config)) {
                        throw new CoreException('IN or NOT IN need a array parameter');
                    }

                    $in_where_condition = array();
                    foreach ($field_config as $in_field_val) {
                        $params[] = $in_field_val;
                        $in_where_condition [] = '?';
                    }

                    $condition[" {$condition_connector} "][] = sprintf('%s %s (%s)', $field, $connector,
                        implode(',', $in_where_condition)
                    );
                    break;

                case 'BETWEEN':
                case 'NOT BETWEEN':
                    if (!is_array($field_config)) {
                        throw new CoreException('BETWEEN need a array parameter');
                    }

                    if (!isset($field_config[0]) || !isset($field_config[1])) {
                        throw new CoreException('BETWEEN parameter error!');
                    }

                    $condition[" {$condition_connector} "][] = sprintf('%s %s %s AND %s', $field, $connector,
                        $field_config[0], $field_config[1]
                    );
                    break;

                case 'FIND_IN_SET':
                    $condition[" {$condition_connector} "][] = sprintf('find_in_set(?, %s)', $field);
                    $params[] = $field_config;
                    break;

                case 'REGEXP':
                    $condition[" {$condition_connector} "][] = sprintf('%s REGEXP(?)', $field);
                    $params[] = $field_config;
                    break;

                default:
                    $operator = $connector;
                    $condition[" {$condition_connector} "][] = "{$field} {$operator} ?";
                    $params [] = $field_config;
            }

            $all_condition[] = $condition;
        }

        foreach ($all_condition as $condition) {
            foreach ($condition as $where_connector => $where_condition) {
                if (isset($where_condition[1])) {
                    $where_snippet = sprintf('(%s)', implode($where_connector, $where_condition));
                    $where_connector = ' AND ';
                } else {
                    $where_snippet = $where_condition[0];
                }

                if ('' === $where_str) {
                    $where_str = $where_snippet;
                } else {
                    $where_str .= $where_connector . $where_snippet;
                }
            }
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

    /**
     * @return string
     */
    public function getSQL()
    {
        return $this->sql;
    }

    /**
     * @param $sql
     */
    protected function setSQL($sql)
    {
        $this->sql = $sql;
    }

    /**
     * @return string|array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param $params
     */
    protected function setParams($params)
    {
        $this->params = $params;
    }
}
