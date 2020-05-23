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
 * Class SQLAssembler
 * @package Cross\DB\SQLAssembler
 */
class SQLAssembler
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
     * 表前缀
     *
     * @var string
     */
    protected $table_prefix;

    /**
     * offset()在limit()中已经传递了第二个参数时不再生效
     *
     * @var bool
     */
    protected $offset_is_valid = true;

    /**
     * 初始化时可以指定表前缀
     *
     * @param string $table_prefix
     */
    function __construct(string $table_prefix = '')
    {
        $this->table_prefix = $table_prefix;
    }

    /**
     * 插入
     *
     * @param string $table 表名称
     * @param array $data 要处理的数据关联数组
     * @param bool $multi 是否批量插入数据
     * <pre>
     *  批量插入数据时$data的结构如下:
     *      $data = array(
     *          'fields' => array(字段1,字段2,...),
     *          'values' => array(
     *                      array(字段1的值, 字段2的值),
     *                      array(字段1的值, 字段2的值))
     *      );
     * </pre>
     */
    public function add(string $table, &$data, bool $multi = false): void
    {
        $params = [];
        if (true === $multi) {
            $field_str = $value_str = '';
            if (empty($data['fields']) || empty($data['values'])) {
                $data = $this->arrayToMultiAddFormat($data);
            }

            $params = $data['values'];
            foreach ($data['fields'] as $d) {
                $field_str .= "`{$d}`,";
                $value_str .= '?,';
            }

            $fields = trim($field_str, ',');
            $values = trim($value_str, ',');
            $into_fields = "({$fields}) VALUES ({$values})";
        } else {
            $into_fields = $this->parseData($data, $params, 'insert');
        }

        $this->setSQL("INSERT INTO {$table} {$into_fields}");
        $this->setParams($params);
    }

    /**
     * 带分页功能的查询
     *
     * @param string $table 表名称, 复杂情况下, 以LEFT JOIN为例: table_a a LEFT JOIN table_b b ON a.id=b.aid
     * @param string $fields 要查询的字段 所有字段的时候为'*'
     * @param string $where 查询条件
     * @param int|string $order 排序
     * @param array $page 分页参数 默认返回50条记录
     * @param int|string $group_by
     * @return mixed|void
     * @throws CoreException
     */
    public function find(string $table, string $fields, $where, $order = null, array &$page = ['p' => 1, 'limit' => 50], $group_by = null)
    {
        $params = [];
        $field_str = $this->parseFields($fields);
        $where_str = $this->parseWhere($where, $params);
        $order_str = $this->parseOrder($order);

        $p = ($page['p'] - 1) * $page['limit'];
        if (null !== $group_by) {
            $group_str = $this->parseGroup($group_by);
            $sql = "SELECT {$field_str} FROM {$table} WHERE {$where_str} GROUP BY {$group_str} ORDER BY {$order_str} LIMIT {$p}, {$page['limit']}";
        } else {
            $sql = "SELECT {$field_str} FROM {$table} WHERE {$where_str} ORDER BY {$order_str} LIMIT {$p}, {$page['limit']}";
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
    public function update(string $table, $data, $where)
    {
        $params = array();
        $fields = $this->parseData($data, $params);
        $where_str = $this->parseWhere($where, $params);

        $fields = trim($fields, ',');
        $this->setSQL("UPDATE {$table} SET {$fields} WHERE {$where_str}");
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
    public function del(string $table, $where, bool $multi = false)
    {
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
            foreach ($where ['values'] as $p) {
                $params[] = $p;
            }

        } else {
            $where_str = $this->parseWhere($where, $params);
        }

        $this->setSQL("DELETE FROM {$table} WHERE {$where_str}");
        $this->setParams($params);
    }

    /**
     * select
     *
     * @param string $fields
     * @param string $modifier
     * @return string
     */
    public function select(string $fields = '*', string $modifier = ''): string
    {
        return "SELECT {$modifier} {$this->parseFields($fields)} ";
    }

    /**
     * insert
     *
     * @param string $table
     * @param string $modifier
     * @param array $data
     * @param array $params
     * @return string
     */
    public function insert(string $table, $data, string $modifier = '', &$params = []): string
    {
        return "INSERT {$modifier} INTO {$table} {$this->parseData($data, $params, 'insert')} ";
    }

    /**
     * replace
     *
     * @param string $table
     * @param string $modifier
     * @return string
     */
    public function replace(string $table, string $modifier = ''): string
    {
        return "REPLACE {$modifier} {$table} ";
    }

    /**
     * @param string $table
     * @return string
     */
    public function from(string $table): string
    {
        return "FROM {$table} ";
    }

    /**
     * @param string|array $where
     * @param array $params
     * @return string
     * @throws CoreException
     */
    public function where($where, &$params): string
    {
        return "WHERE {$this->parseWhere($where, $params)} ";
    }

    /**
     * @param int $start
     * @param bool|int $end
     * @return string
     */
    public function limit(int $start, $end = false): string
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
    public function offset(int $offset): string
    {
        if ($this->offset_is_valid) {
            return "OFFSET {$offset} ";
        }

        return "";
    }

    /**
     * @param mixed $order
     * @return string
     */
    public function orderBy($order): string
    {
        return "ORDER BY {$this->parseOrder($order)} ";
    }

    /**
     * @param string $group
     * @return string
     */
    public function groupBy(string $group): string
    {
        return "GROUP BY {$this->parseGroup($group)} ";
    }

    /**
     * @param string $having
     * @return string
     */
    public function having(string $having): string
    {
        return "HAVING {$having} ";
    }

    /**
     * @param string $procedure
     * @return string
     */
    public function procedure(string $procedure): string
    {
        return "PROCEDURE {$procedure} ";
    }

    /**
     * @param string $var_name
     * @return string
     */
    public function into(string $var_name): string
    {
        return "INTO {$var_name} ";
    }

    /**
     * @param mixed $data
     * @param array $params
     * @return string
     */
    public function set($data, array &$params = []): string
    {
        return "SET {$this->parseData($data, $params)} ";
    }

    /**
     * @param string $on
     * @return string
     */
    public function on(string $on): string
    {
        return "ON {$on} ";
    }

    /**
     * 解析字段
     *
     * @param string|array $fields
     * @return string
     */
    public function parseFields($fields): string
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
    public function parseWhere($where, array &$params): string
    {
        if (!empty($where)) {
            if (is_array($where)) {
                if (isset($where[1])) {
                    $where_str = $where[0];
                    if (!is_array($where[1])) {
                        $params[] = $where[1];
                    } else {
                        foreach ($where[1] as $w) {
                            $params[] = $w;
                        }
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
     * 解析order
     *
     * @param mixed $order
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
     * @param mixed $group_by
     * @return int|string
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
    public function getSQL(): string
    {
        return $this->sql;
    }

    /**
     * @param $sql
     */
    protected function setSQL(string $sql): void
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
     * 获取表前缀
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->table_prefix;
    }

    /**
     * @param $params
     */
    protected function setParams($params): void
    {
        $this->params = $params;
    }

    /**
     * 解析where条件
     *
     * @param string $operator 字段和值之间的操作符
     * @param string $field 字段名
     * @param string|array $field_config 字段值配置
     * @param bool $is_mixed_field 区别默认字段和复合字段(带括号的字段)
     * @param string $condition_connector 每个条件之间的连接符
     * @param string $connector 每个字段之间的连接符
     * @param array $params 包含字段值的数组(prepare之后传递的参数)
     * @return array
     * @throws CoreException
     */
    protected function parseCondition(string $operator, string $field, $field_config, bool $is_mixed_field, string $condition_connector, string $connector, array &$params): array
    {
        $condition = [];
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
                        foreach ($field_config as $and_exp_val) {
                            $ex_operator = '=';
                            if (is_array($and_exp_val)) {
                                list($ex_operator, $n_value) = $and_exp_val;
                                $and_exp_val = $n_value;
                            }
                            $condition[' AND '][] = "{$field} {$ex_operator} ?";
                            $params [] = $and_exp_val;
                        }
                    } else {
                        $params [] = $field_config;
                        $condition[' AND '][] = "{$field} {$operator} ?";
                    }
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

                $in_where_condition_string = implode(',', $in_where_condition);
                $condition[" {$condition_connector} "][] = "{$field} {$connector} ($in_where_condition_string)";
                break;

            case 'BETWEEN':
            case 'NOT BETWEEN':
                if (!is_array($field_config)) {
                    throw new CoreException('BETWEEN need a array parameter');
                }

                if (!isset($field_config[0]) || !isset($field_config[1])) {
                    throw new CoreException('BETWEEN parameter error!');
                }

                $condition[" {$condition_connector} "][] = "{$field} {$connector} ? AND ?";
                $params[] = $field_config[0];
                $params[] = $field_config[1];
                break;

            case '#SQL#':
                if (is_array($field_config)) {
                    list($operator, $sql_segment) = $field_config;
                } else {
                    $sql_segment = $field_config;
                }

                $condition[" {$condition_connector} "][] = "{$field} {$operator} {$sql_segment}";
                break;

            default:
                $operator = $connector;
                $condition[" {$condition_connector} "][] = "{$field} {$operator} ?";
                $params [] = $field_config;
        }

        return $condition;
    }

    /**
     * 解析数据
     *
     * @param mixed $data
     * @param array $params
     * @param string $format
     * @return string
     */
    private function parseData($data, array &$params, $format = 'normal'): string
    {
        if (!empty($data)) {
            if (is_array($data)) {
                if (isset($data[1])) {
                    $sql_segment = $data[0];
                    if (!is_array($data[1])) {
                        $params[] = $data[1];
                    } else {
                        foreach ($data[1] as $d) {
                            $params[] = $d;
                        }
                    }
                } else {
                    if ('insert' === $format) {
                        $data_keys = $data_values = array();
                        foreach ($data as $key => $value) {
                            $data_keys[] = $key;
                            $data_values[] = '?';
                            $params[] = $value;
                        }

                        $fields = implode(',', $data_keys);
                        $values = implode(',', $data_values);
                        $sql_segment = "({$fields}) VALUES ({$values})";
                    } else {
                        $segment = '';
                        foreach ($data as $key => $value) {
                            if (is_array($value)) {
                                if (isset($value[1])) {
                                    $segment .= ", {$key} = {$value[0]}";
                                    $params[] = $value[1];
                                } else {
                                    $segment .= ", {$key} = {$value[0]}";
                                }
                            } else {
                                $segment .= ", {$key} = ?";
                                $params[] = $value;
                            }
                        }

                        $sql_segment = trim($segment, ',');
                    }
                }
            } else {
                $sql_segment = $data;
            }
        } else {
            $sql_segment = '';
        }
        return $sql_segment;
    }

    /**
     * 解析关联数组
     *
     * @param array $where
     * @param array $params
     * @return string
     * @throws CoreException
     */
    private function parseWhereFromHashMap(array $where, array &$params): string
    {
        $all_condition = array();
        foreach ($where as $field => $field_config) {
            $operator = '=';
            $field = trim($field);
            $is_mixed_field = false;
            $condition_connector = $connector = 'AND';

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

            $condition = $this->parseCondition($operator, $field, $field_config, $is_mixed_field, $condition_connector, $connector, $params);
            $all_condition[] = $condition;
        }

        return $this->combineWhereCondition($all_condition);
    }

    /**
     * 组合where条件
     *
     * @param array $where_condition
     * @return string
     */
    private function combineWhereCondition(array $where_condition): string
    {
        $where = '';
        foreach ($where_condition as $condition) {
            foreach ($condition as $where_connector => $where_condition) {
                if (isset($where_condition[1])) {
                    $where_snippet_string = implode($where_connector, $where_condition);
                    $where_snippet = "($where_snippet_string)";
                    $where_connector = ' AND ';
                } else {
                    $where_snippet = $where_condition[0];
                }

                if ('' === $where) {
                    $where = $where_snippet;
                } else {
                    $where .= $where_connector . $where_snippet;
                }
            }
        }
        return $where;
    }

    /**
     * 将数组格式化成批量添加的格式
     *
     * @param array $data
     * @return array
     */
    private function arrayToMultiAddFormat(array $data): array
    {
        $fields = $values = array();
        if (!empty($data)) {
            while ($d = array_shift($data)) {
                $keys = array_keys($d);
                if (empty($fields)) {
                    $fields = $keys;
                } elseif ($keys !== $fields) {
                    continue;
                }

                $values[] = array_values($d);
            }
        }

        return ['fields' => $fields, 'values' => $values];
    }
}
