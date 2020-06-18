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
     * @var string
     */
    protected $table;

    /**
     * oracle序号名称
     *
     * @var string
     */
    protected $sequence = '';

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
     * 包裹过字段名的字符
     *
     * @var string
     */
    protected $field_quote_char = '`';

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
     * @param array $data 要处理的数据（关联数组，批量插入会自动转换格式）
     * @param bool $multi 是否批量插入数据
     */
    public function add(string $table, array &$data, bool $multi = false): void
    {
        $params = [];
        if (true === $multi) {
            $into_fields = $this->insertDataToSQLSegment($data[0], false, $_notUse, $sequenceKey);
            $data = $this->arrayToMultiAddFormat($data, $sequenceKey);
            $params = $data['values'];
        } else {
            $into_fields = $this->insertDataToSQLSegment($data, true, $params);
        }

        $this->setSQL("INSERT INTO {$this->getTable($table)} {$into_fields}");
        $this->setParams($params);
    }

    /**
     * 带分页功能的查询
     *
     * @param string $table 表名称, 复杂情况下, 以LEFT JOIN为例: table_a a LEFT JOIN table_b b ON a.id=b.aid
     * @param string $fields 要查询的字段 所有字段的时候为'*'
     * @param string $where 查询条件
     * @param array $page 分页参数 默认返回50条记录
     * @param int|string $order 排序
     * @param int|string $group_by
     * @return mixed|void
     * @throws CoreException
     */
    public function find(string $table, string $fields, $where, array &$page = ['p' => 1, 'limit' => 50], $order = null, $group_by = null)
    {
        $params = [];
        $field_str = $this->parseFields($fields);
        $where_str = $this->parseWhere($where, $params);

        $sql = "SELECT {$field_str} FROM {$this->getTable($table)} WHERE {$where_str}";
        if (null !== $group_by) {
            $group_str = $this->parseGroup($group_by);
            $sql .= " GROUP BY {$group_str}";
        }

        if (null !== $order) {
            $order_str = $this->parseOrder($order);
            $sql .= " ORDER BY {$order_str}";
        }

        $p = ($page['p'] - 1) * $page['limit'];
        $sql .= ' ' . $this->getLimitSQLSegment($p, $page['limit']);

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
        $params = [];
        $fields = $this->parseData($data, $params);
        $where_str = $this->parseWhere($where, $params);

        $fields = trim($fields, ',');
        $this->setSQL("UPDATE {$this->getTable($table)} SET {$fields} WHERE {$where_str}");
        $this->setParams($params);
    }

    /**
     * 删除
     *
     * @param string $table
     * @param string|array $where
     * @return mixed|void
     * @throws CoreException
     */
    public function del(string $table, $where)
    {
        $params = [];
        $where_str = $this->parseWhere($where, $params);
        $this->setSQL("DELETE FROM {$this->getTable($table)} WHERE {$where_str}");
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
        return "INSERT {$modifier} INTO {$this->getTable($table)} {$this->insertDataToSQLSegment($data, true, $params)} ";
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
        return "REPLACE {$modifier} {$this->getTable($table)} ";
    }

    /**
     * @param string $table
     * @return string
     */
    public function from(string $table): string
    {
        return "FROM {$this->getTable($table)} ";
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
     * @param int $start 从第几页开始
     * @param int $end 取多少条
     * @return string
     */
    public function limit(int $start, int $end = null): string
    {
        if (null !== $end) {
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
                    $this->beforeParseData($where);
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
     * 获取表名
     *
     * @param string $table
     * @return string
     */
    protected function getTable(string $table)
    {
        $this->table = $table;
        return $this->table;
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

                $in_where_condition = [];
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
     * @return string
     */
    protected function parseData($data, array &$params): string
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
                    $segment = '';
                    $this->beforeParseData($data);
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
            } else {
                $sql_segment = $data;
            }
        } else {
            $sql_segment = '';
        }
        return $sql_segment;
    }

    /**
     * 插入数据转换为SQL片段
     *
     * @param mixed $data
     * @param bool $parseParams
     * @param array $params
     * @return string
     */
    protected function insertDataToSQLSegment(array $data, bool $parseParams = true, &$params = [], &$sequenceKey = null): string
    {
        $fields = $values = [];
        foreach ($data as $key => $value) {
            $addToParseParams = true;
            if (is_array($value)) {
                $addToParseParams = false;
                $type = key($value);
                if ($type == '#SEQ#') {
                    //oracle sequence 插入时默认跟 NEXTVAL
                    $sequenceKey = $key;
                    $this->setSequence($value[$type]);
                    $sqlValue = $value[$type] . '.NEXTVAL';
                } else {
                    //待扩展支持其他标识
                    $sqlValue = $value[$type];
                }
            } else {
                $sqlValue = '?';
            }

            if ($addToParseParams && $parseParams) {
                $params[] = $value;
            }

            $fields[] = sprintf('%s%s%s', $this->field_quote_char, $key, $this->field_quote_char);
            $values[] = $sqlValue;
        }

        return sprintf('(%s) VALUES (%s)', implode(',', $fields), implode(',', $values));
    }

    /**
     * 设置序号
     *
     * @param string $sequence
     */
    public function setSequence(string $sequence): void
    {
        $this->sequence = $sequence;
    }

    /**
     * @return string
     */
    public function getSequence(): string
    {
        return $this->sequence;
    }

    /**
     * 生成分页片段
     *
     * @param int $p
     * @param int $limit
     * @return string
     */
    protected function getLimitSQLSegment(int $p, int $limit): string
    {
        return "LIMIT {$p}, {$limit}";
    }

    /**
     * 解析数据之前执行
     *
     * @param array $data
     */
    protected function beforeParseData(&$data)
    {

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
        $all_condition = [];
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
     * @param string $ignoreKey 忽略指定键
     * @return array
     */
    private function arrayToMultiAddFormat(array $data, string $ignoreKey = null): array
    {
        $fields = $values = [];
        if (!empty($data)) {
            while ($d = array_shift($data)) {
                if (!empty($ignoreKey)) {
                    unset($d[$ignoreKey]);
                }

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
