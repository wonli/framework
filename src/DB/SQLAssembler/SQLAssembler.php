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
    protected string $sql;

    /**
     * @var string|array
     */
    protected string|array $params;

    /**
     * @var string
     */
    protected string $table;

    /**
     * oracle序号名称
     *
     * @var string
     */
    protected string $sequence = '';

    /**
     * 表前缀
     *
     * @var string
     */
    protected string $tablePrefix;

    /**
     * offset()在limit()中已经传递了第二个参数时不再生效
     *
     * @var bool
     */
    protected bool $offsetIsValid = true;

    /**
     * 包裹过字段名的字符
     *
     * @var string
     */
    protected string $fieldQuoteChar = '';

    /**
     * 初始化时可以指定表前缀
     *
     * @param string $tablePrefix
     */
    function __construct(string $tablePrefix = '')
    {
        $this->tablePrefix = $tablePrefix;
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
            $intoFields = $this->insertDataToSQLSegment(current($data), false, $_notUse, $sequenceKey);
            $data = $this->arrayToMultiAddFormat($data, $sequenceKey);
            $params = $data['values'];
        } else {
            $intoFields = $this->insertDataToSQLSegment($data, true, $params);
        }

        $this->setSQL("INSERT INTO {$this->getTable($table)} {$intoFields}");
        $this->setParams($params);
    }

    /**
     * 带分页功能的查询
     *
     * @param string $table 表名称, 复杂情况下, 以LEFT JOIN为例: table_a a LEFT JOIN table_b b ON a.id=b.aid
     * @param string $fields 要查询的字段 所有字段的时候为'*'
     * @param mixed $where 查询条件
     * @param array $page 分页参数 默认返回50条记录
     * @param mixed|null $order 排序
     * @param mixed|null $groupBy
     * @return void
     * @throws CoreException
     */
    public function find(string $table, string $fields, mixed $where, array &$page = ['p' => 1, 'limit' => 50], mixed $order = null, mixed $groupBy = null): void
    {
        $params = [];
        $fieldStr = $this->parseFields($fields);
        $whereStr = $this->parseWhere($where, $params);

        $sql = "SELECT {$fieldStr} FROM {$this->getTable($table)} WHERE {$whereStr}";
        if (null !== $groupBy) {
            $groupStr = $this->parseGroup($groupBy);
            $sql .= " GROUP BY {$groupStr}";
        }

        if (null !== $order) {
            $orderStr = $this->parseOrder($order);
            $sql .= " ORDER BY {$orderStr}";
        }

        $sql .= ' ' . $this->getLimitSQLSegment($page['p'], $page['limit']);
        $this->setSQL($sql);
        $this->setParams($params);
    }

    /**
     * 更新
     *
     * @param string $table
     * @param mixed $data
     * @param mixed $where
     * @return void
     * @throws CoreException
     */
    public function update(string $table, mixed $data, mixed $where): void
    {
        $params = [];
        $fields = $this->parseData($data, $params);
        $whereStr = $this->parseWhere($where, $params);

        $fields = trim($fields, ',');
        $this->setSQL("UPDATE {$this->getTable($table)} SET {$fields} WHERE {$whereStr}");
        $this->setParams($params);
    }

    /**
     * 删除
     *
     * @param string $table
     * @param array|string $where
     * @return void
     * @throws CoreException
     */
    public function del(string $table, array|string $where): void
    {
        $params = [];
        $whereStr = $this->parseWhere($where, $params);
        $this->setSQL("DELETE FROM {$this->getTable($table)} WHERE {$whereStr}");
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
    public function insert(string $table, array $data, string $modifier = '', array &$params = []): string
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
     * @param array|string $where
     * @param array $params
     * @return string
     * @throws CoreException
     */
    public function where(array|string $where, array &$params): string
    {
        return "WHERE {$this->parseWhere($where, $params)} ";
    }

    /**
     * @param int $start 从第几页开始
     * @param int|null $end 取多少条
     * @return string
     */
    public function limit(int $start, int $end = null): string
    {
        if (null !== $end) {
            $end = (int)$end;
            $this->offsetIsValid = false;
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
        if ($this->offsetIsValid) {
            return "OFFSET {$offset} ";
        }

        return "";
    }

    /**
     * @param mixed $order
     * @return string
     */
    public function orderBy(mixed $order): string
    {
        return "ORDER BY {$this->parseOrder($order)} ";
    }

    /**
     * @param $group
     * @return string
     */
    public function groupBy(  $group): string
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
     * @param string $varName
     * @return string
     */
    public function into(string $varName): string
    {
        return "INTO {$varName} ";
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
     * @param array|string $fields
     * @return string
     */
    public function parseFields(array|string $fields): string
    {
        if (empty($fields)) {
            $fieldStr = '*';
        } else {
            if (is_array($fields)) {
                $fieldStr = implode(',', $fields);
            } else {
                $fieldStr = $fields;
            }
        }

        return $fieldStr;
    }

    /**
     * 解析where
     *
     * @param array|string $where
     * @param array $params
     * @return string
     * @throws CoreException
     */
    public function parseWhere(array|string $where, array &$params): string
    {
        if (!empty($where)) {
            if (is_array($where)) {
                if (isset($where[1])) {
                    $whereStr = $where[0];
                    if (!is_array($where[1])) {
                        $params[] = $where[1];
                    } else {
                        foreach ($where[1] as $w) {
                            $params[] = $w;
                        }
                    }
                } else {
                    $this->beforeParseData($where);
                    $whereStr = $this->parseWhereFromHashMap($where, $params);
                }
            } else {
                $whereStr = $where;
            }
        } else {
            $whereStr = '1=1';
        }
        return $whereStr;
    }

    /**
     * 解析数据
     *
     * @param mixed $data
     * @param array $params
     * @return string
     */
    function parseData($data, array &$params): string
    {
        if (!empty($data)) {
            if (is_array($data)) {
                if (isset($data[1])) {
                    $sqlSegment = $data[0];
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
                            $type = $value[0];
                            switch ($type) {
                                case '#SQL#':
                                    $segment .= ", {$key} = $value[1]";
                                    break;
                                case '#RAW#':
                                    $segment .= ", $value[1]";
                                    break;
                                default:
                                    if (isset($value[1])) {
                                        $segment .= ", {$key} = {$value[0]}";
                                        $params[] = $value[1];
                                    } else {
                                        $segment .= ", {$key} = {$value[0]}";
                                    }
                            }
                        } else {
                            $segment .= ", {$key} = ?";
                            $params[] = $value;
                        }
                    }
                    $sqlSegment = trim($segment, ',');
                }
            } else {
                $sqlSegment = $data;
            }
        } else {
            $sqlSegment = '';
        }
        return $sqlSegment;
    }

    /**
     * 解析order
     *
     * @param mixed $order
     * @return int|string
     */
    public function parseOrder(mixed $order): int|string
    {
        if (!empty($order)) {
            if (is_array($order)) {
                $orderStr = implode(',', $order);
            } else {
                $orderStr = $order;
            }
        } else {
            $orderStr = 1;
        }

        return $orderStr;
    }

    /**
     * 解析group by
     *
     * @param string $groupBy
     * @return int|string
     */
    public function parseGroup(string $groupBy): int|string
    {
        if (!empty($groupBy)) {
            $groupStr = $groupBy;
        } else {
            $groupStr = 1;
        }

        return $groupStr;
    }

    /**
     * @return string
     */
    public function getSQL(): string
    {
        return $this->sql;
    }

    /**
     * @param string $sql
     */
    protected function setSQL(string $sql): void
    {
        $this->sql = $sql;
    }

    /**
     * @return string|array
     */
    public function getParams(): array|string
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
        return $this->tablePrefix;
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
    protected function getTable(string $table): string
    {
        $this->table = $table;
        return $this->table;
    }

    /**
     * 解析where条件
     *
     * @param string $operator 字段和值之间的操作符
     * @param string $field 字段名
     * @param array|string $fieldConfig 字段值配置
     * @param bool $isMixedField 区别默认字段和复合字段(带括号的字段)
     * @param string $conditionConnector 每个条件之间的连接符
     * @param string $connector 每个字段之间的连接符
     * @param array $params 包含字段值的数组(prepare之后传递的参数)
     * @return array
     * @throws CoreException
     */
    protected function parseCondition(string $operator, string $field, array|string $fieldConfig, bool $isMixedField, string $conditionConnector, string $connector, array &$params): array
    {
        $condition = [];
        switch ($connector) {
            case 'OR':
                if (!is_array($fieldConfig)) {
                    $fieldConfig = array($fieldConfig);
                }
                foreach ($fieldConfig as $fieldSingleConfig) {
                    if (is_array($fieldSingleConfig)) {
                        list($operator, $singleFieldValue) = $fieldSingleConfig;
                        $params [] = $singleFieldValue;
                    } else {
                        $params [] = $fieldSingleConfig;
                    }
                    $condition[' OR '][] = "{$field} {$operator} ?";
                }
                break;

            case 'AND':
                if ($isMixedField) {
                    $condition[" {$conditionConnector} "][] = $field;
                    if (is_array($fieldConfig)) {
                        foreach ($fieldConfig as $f) {
                            $params [] = $f;
                        }
                    } else {
                        $params[] = $fieldConfig;
                    }
                } else {
                    if (is_array($fieldConfig)) {
                        foreach ($fieldConfig as $andExpVal) {
                            $exOperator = '=';
                            if (is_array($andExpVal)) {
                                list($exOperator, $nValue) = $andExpVal;
                                $andExpVal = $nValue;
                            }
                            $condition[' AND '][] = "{$field} {$exOperator} ?";
                            $params [] = $andExpVal;
                        }
                    } else {
                        $params [] = $fieldConfig;
                        $condition[' AND '][] = "{$field} {$operator} ?";
                    }
                }
                break;

            case 'IN':
            case 'NOT IN':
                if (!is_array($fieldConfig)) {
                    throw new CoreException('IN or NOT IN need a array parameter');
                }

                $inWhereCondition = [];
                foreach ($fieldConfig as $inFieldVal) {
                    $params[] = $inFieldVal;
                    $inWhereCondition [] = '?';
                }

                $inWhereConditionString = implode(',', $inWhereCondition);
                $condition[" {$conditionConnector} "][] = "{$field} {$connector} ($inWhereConditionString)";
                break;

            case 'BETWEEN':
            case 'NOT BETWEEN':
                if (!is_array($fieldConfig)) {
                    throw new CoreException('BETWEEN need a array parameter');
                }

                if (!isset($fieldConfig[0]) || !isset($fieldConfig[1])) {
                    throw new CoreException('BETWEEN parameter error!');
                }

                $condition[" {$conditionConnector} "][] = "{$field} {$connector} ? AND ?";
                $params[] = $fieldConfig[0];
                $params[] = $fieldConfig[1];
                break;

            case '#SQL#':
                if (is_array($fieldConfig)) {
                    list($operator, $sqlSegment) = $fieldConfig;
                } else {
                    $sqlSegment = $fieldConfig;
                }

                $condition[" {$conditionConnector} "][] = "{$field} {$operator} {$sqlSegment}";
                break;

            case '#RAW#':
                if (is_array($fieldConfig)) {
                    $sqlSegment = array_shift($fieldConfig);
                    if (!empty($fieldConfig)) {
                        foreach ($fieldConfig as $f) {
                            $params[] = $f;
                        }
                    }
                } else {
                    $sqlSegment = $fieldConfig;
                }
                $condition[" {$conditionConnector} "][] = " ({$sqlSegment}) ";
                break;

            default:
                $operator = $connector;
                $condition[" {$conditionConnector} "][] = "{$field} {$operator} ?";
                $params [] = $fieldConfig;
        }

        return $condition;
    }

    /**
     * 插入数据转换为SQL片段
     *
     * @param mixed $data
     * @param bool $parseParams
     * @param array $params
     * @param null $sequenceKey
     * @return string
     */
    protected function insertDataToSQLSegment(array $data, bool $parseParams = true, array &$params = [], &$sequenceKey = null): string
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

            $fields[] = sprintf('%s%s%s', $this->fieldQuoteChar, $key, $this->fieldQuoteChar);
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
        $offset = ($p - 1) * $limit;
        return "LIMIT {$offset}, {$limit}";
    }

    /**
     * 解析数据之前执行
     *
     * @param array $data
     */
    protected function beforeParseData(array &$data)
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
        $allCondition = [];
        foreach ($where as $field => $fieldConfig) {
            $operator = '=';
            $field = trim($field);
            $isMixedField = false;
            $conditionConnector = $connector = 'AND';

            if ($field[0] == '(' && $field[strlen($field) - 1] == ')') {
                $isMixedField = true;
            }

            if ($isMixedField === false && is_array($fieldConfig)) {
                if (count($fieldConfig) == 3) {
                    list($connector, $fieldTrueValue, $conditionConnector) = $fieldConfig;
                } else {
                    list($connector, $fieldTrueValue) = $fieldConfig;
                }

                $conditionConnector = strtoupper(trim($conditionConnector));
                $connector = strtoupper(trim($connector));
                $fieldConfig = $fieldTrueValue;
            }

            $condition = $this->parseCondition($operator, $field, $fieldConfig, $isMixedField, $conditionConnector, $connector, $params);
            $allCondition[] = $condition;
        }

        return $this->combineWhereCondition($allCondition);
    }

    /**
     * 组合where条件
     *
     * @param array $whereCondition
     * @return string
     */
    private function combineWhereCondition(array $whereCondition): string
    {
        $where = '';
        foreach ($whereCondition as $condition) {
            foreach ($condition as $whereConnector => $whereCondition) {
                if (isset($whereCondition[1])) {
                    $whereSnippetString = implode($whereConnector, $whereCondition);
                    $whereSnippet = "($whereSnippetString)";
                    $whereConnector = ' AND ';
                } else {
                    $whereSnippet = $whereCondition[0];
                }

                if ('' === $where) {
                    $where = $whereSnippet;
                } else {
                    $where .= $whereConnector . $whereSnippet;
                }
            }
        }
        return $where;
    }

    /**
     * 将数组格式化成批量添加的格式
     *
     * @param array $data
     * @param string|null $ignoreKey 忽略指定键
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
