<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.2
 */
namespace Cross\DB\Drivers;

use Cross\Exception\CoreException;
use Cross\Exception\FrontException;
use PDOException;
use PDOStatement;
use Exception;
use PDO;

/**
 * @Auth: wonli <wonli@live.com>
 * Class MysqlDriver
 * @package Cross\DB\Drivers
 */
class MysqlDriver
{
    /**
     * @var PDOStatement
     */
    public $stmt;

    /**
     * @var PDO
     */
    public $pdo;

    /**
     * @var string
     */
    public $sql;

    /**
     * 数据库连接实例
     *
     * @var object
     */
    private static $instance;

    /**
     * 默认连接参数
     * <ul>
     *  <li>PDO::ATTR_PERSISTENT => false //禁用长连接</li>
     *  <li>PDO::ATTR_EMULATE_PREPARES => false //禁用模拟预处理</li>
     *  <li>PDO::ATTR_STRINGIFY_FETCHES => false //禁止数值转换成字符串</li>
     *  <li>PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true //使用缓冲查询</li>
     *  <li>PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION //发生错误时抛出异常 </li>
     *  <li>PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8" </li>
     * </ul>
     *
     * @var array
     */
    private $options = array(
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
    );

    /**
     * 创建数据库连接
     *
     * @param string $dsn dsn
     * @param string $user 数据库用户名
     * @param string $password 数据库密码
     * @param array $options
     * @throws CoreException
     */
    private function __construct($dsn, $user, $password, $options = array())
    {
        try {
            if (empty($options)) {
                $options = $this->options;
            }
            $this->pdo = new PDO($dsn, $user, $password, $options);
        } catch (Exception $e) {
            throw new CoreException($e->getMessage() . ' line:' . $e->getLine() . ' ' . $e->getFile());
        }
    }

    /**
     * @see MysqlModel::__construct
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @return mixed
     */
    static function getInstance($dsn, $user, $password)
    {
        //同时建立多个数据库连接
        $_instance = md5($dsn . $user . $password);
        if (!isset(self::$instance[$_instance])) {
            self::$instance [$_instance] = new self($dsn, $user, $password);
        }

        return self::$instance [$_instance];
    }

    /**
     * @see MysqlModel::prepareGetOne
     * @param string $table
     * @param string $fields
     * @param string|array $where
     * @return mixed
     */
    public function get($table, $fields, $where)
    {
        return $this->prepareGetOne($table, $fields, $where);
    }

    /**
     * @see MysqlModel::prepareGetAll
     * @param string $table
     * @param string $fields
     * @param string|array $where
     * @param int $order
     * @param int $group_by
     * @return array
     */
    public function getAll($table, $fields, $where = null, $order = 1, $group_by = 1)
    {
        return $this->prepareGetAll($table, $fields, $where, $order, $group_by);
    }

    /**
     * @see MysqlModel::prepareInsert
     * @param string $table
     * @param string $data
     * @param bool $multi
     * @param array $insert_data
     * @return array|bool|mixed
     */
    public function add($table, $data, $multi = false, & $insert_data = array())
    {
        return $this->prepareInsert($table, $data, $multi, $insert_data);
    }

    /**
     * @see MysqlModel::prepareFind
     * @param $table
     * @param $fields
     * @param $where
     * @param int $order
     * @param array $page
     * @param int|string $group_by
     * @return array|mixed
     */
    public function find($table, $fields, $where, $order = 1, & $page = array('p' => 1, 'limit' => 50), $group_by = 1)
    {
        return $this->prepareFind($table, $fields, $where, $order, $page, $group_by);
    }

    /**
     * @see MysqlModel::prepareUpdate
     * @param string $table
     * @param string $data
     * @param string|array $where
     * @return $this|array|string
     */
    public function update($table, $data, $where)
    {
        return $this->prepareUpdate($table, $data, $where);
    }

    /**
     * @see MysqlModel::prepareDelete
     * @param string $table
     * @param string $where
     * @param bool $multi
     * @return bool|mixed
     */
    public function del($table, $where, $multi = false)
    {
        return $this->prepareDelete($table, $where, $multi);
    }

    /**
     * 执行一条SQL 语句 并返回结果
     *
     * @param string $sql
     * @param int $model
     * @throws CoreException
     * @return mixed
     */
    public function fetchOne($sql, $model = PDO::FETCH_ASSOC)
    {
        try {
            $data = $this->pdo->query($sql);

            return $data->fetch($model);
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * 执行sql 并返回所有结果
     *
     * @param string $sql
     * @param int $model
     * @throws CoreException
     * @return array
     */
    public function fetchAll($sql, $model = PDO::FETCH_ASSOC)
    {
        try {
            $data = $this->pdo->query($sql);
            return $data->fetchAll($model);
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * 执行sql 用于无返回值的操作
     *
     * @param string $sql
     * @return int
     * @throws CoreException
     */
    public function execute($sql)
    {
        try {
            return $this->pdo->exec($sql);
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * 参数绑定
     *
     * @param string $statement
     * @param array $params
     * @return MysqlModel
     * @throws CoreException
     */
    public function prepare($statement, $params = array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY))
    {
        try {
            $this->stmt = $this->pdo->prepare($statement, $params);

            return $this;
        } catch (PDOException $e) {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * 执行参数绑定
     *
     * @param array $args
     * @return $this
     * @throws CoreException
     */
    public function exec($args = array())
    {
        try {
            $this->stmt->execute($args);
            return $this;
        } catch (PDOException $e) {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * 返回参数绑定结果
     *
     * @param bool $_fetchAll
     * @param int $result_type
     * @return mixed
     * @throws CoreException
     */
    public function stmtFetch($_fetchAll = false, $result_type = PDO::FETCH_ASSOC)
    {
        if (!$this->stmt) throw new CoreException('stmt init failed!');
        if (true === $_fetchAll) {
            return $this->stmt->fetchAll($result_type);
        }

        return $this->stmt->fetch($result_type);
    }

    /**
     * prepare方式单条查询
     *
     * @param string $table
     * @param string $fields
     * @param string|array $where
     * @return mixed
     */
    protected function prepareGetOne($table, $fields, $where)
    {
        $one_sql = "SELECT %s FROM {$table} WHERE %s LIMIT 1";
        $params = array();

        $field_str = $this->parseFields($fields);
        $where_str = $this->parseWhere($where, $params);

        $this->sql = sprintf($one_sql, $field_str, $where_str);
        $result = $this->prepare($this->sql)->exec($params)->stmtFetch();

        return $result;
    }

    /**
     * prepare插入
     *
     * @param string $table 表名称
     * @param array $data 要处理的数据,跟表中的字段对应
     * @param bool $multi <pre>
     * @param array $insert_data
     *  是否批量插入数据,如果是
     *      $data = array(
     *          'fields' = array(字段1,字段2,...),
     *          'values'= array(
     *                      array(字段1的值, 字段2的值),
     *                      array(字段1的值, 字段2的值))
     *      );
     *  </pre>
     * @return array|bool
     * @throws FrontException
     */
    protected function prepareInsert($table, $data, $multi = false, & $insert_data)
    {
        $insert_sql = "INSERT {$table} (%s) VALUE (%s)";
        $field = $value = '';

        if (true === $multi) {
            $inc_name = $this->getAutoIncrementName($table);

            if (empty($data ['fields']) || empty($data ['values'])) {
                throw new FrontException('data format error!');
            }

            foreach ($data ['fields'] as $d) {
                $field .= "{$d},";
                $value .= '?,';
            }

            $this->sql = sprintf($insert_sql, rtrim($field, ','), rtrim($value, ','));
            $stmt = $this->prepare($this->sql);

            foreach ($data ['values'] as $data_array) {
                if ($stmt->exec($data_array)) {
                    $add_data_info = array_combine($data['fields'], $data_array);
                    if ($inc_name) {
                        $add_data_info[$inc_name] = $this->insertId();
                    }

                    $insert_data[] = $add_data_info;
                }
            }

            return true;
        } else {
            $params = array();
            foreach ($data as $_field => $_value) {
                $field .= "{$_field},";
                $value .= '?,';
                $params[] = $_value;
            }

            $this->sql = sprintf($insert_sql, rtrim($field, ','), rtrim($value, ','));
            $stmt = $this->prepare($this->sql);
            $id = $stmt->exec($params)->insertId();

            if ($id) {
                return $id;
            }

            return true;
        }
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
     * @return array
     */
    protected function prepareFind($table, $fields, $where = null, $order = 1, & $page = array('p' => 1, 'limit' => 50), $group_by = 1)
    {
        $params = array();

        $field_str = $this->parseFields($fields);
        $where_str = $this->parseWhere($where, $params);
        $order_str = $this->parseOrder($order);
        $group_str = $this->parseGroup($group_by);

        $total = $this->prepareGetOne($table, 'COUNT(*) as total', $where);

        $page['result_count'] = (int)$total ['total'];
        $page['limit'] = max(1, (int)$page['limit']);
        $page['total_page'] = ceil($page['result_count'] / $page['limit']);
        $page['p'] = max(1, min($page['p'], $page['total_page']));
        $p = ($page['p'] - 1) * $page['limit'];

        if (1 !== $group_by) {
            $all_sql = "SELECT %s FROM {$table} WHERE %s GROUP BY %s ORDER BY %s LIMIT %s";
            $this->sql = sprintf($all_sql, $field_str, $where_str, $group_str, $order_str, "{$p}, {$page['limit']}");
        }
        else {
            $all_sql = "SELECT %s FROM {$table} WHERE %s ORDER BY %s LIMIT %s";
            $this->sql = sprintf($all_sql, $field_str, $where_str, $order_str, "{$p}, {$page['limit']}");
        }

        $result = $this->prepare($this->sql)->exec($params)->stmtFetch(true);

        return $result;
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
    protected function prepareGetAll($table, $fields, $where = null, $order = 1, $group_by = 1)
    {
        $params = array();
        $field_str = $this->parseFields($fields);
        $where_str = $this->parseWhere($where, $params);
        $order_str = $this->parseOrder($order);
        $group_str = $this->parseGroup($group_by);

        if (1 !== $group_by) {
            $all_sql = "SELECT %s FROM {$table} WHERE %s GROUP BY %s ORDER BY %s";
            $this->sql = sprintf($all_sql, $field_str, $where_str, $group_str, $order_str);
        }
        else {
            $all_sql = "SELECT %s FROM {$table} WHERE %s ORDER BY %s";
            $this->sql = sprintf($all_sql, $field_str, $where_str, $order_str);
        }

        $result = $this->prepare($this->sql)->exec($params)->stmtFetch(true);

        return $result;
    }

    /**
     * prepare 方式更新
     *
     * @param string $table
     * @param array $data
     * @param array|string $where
     * @return $this|array|string
     */
    protected function prepareUpdate($table, $data, $where)
    {
        $up_sql = "UPDATE {$table} SET %s WHERE %s";
        $params = array();

        $field = '';
        if (!empty($data)) {
            if (is_array($data)) {
                foreach ($data as $d_key => $d_value) {
                    $field .= "{$d_key} = ? ,";
                    $params [] = strval($d_value);
                }
            }
            else {
                $field = $data;
            }
        }

        $where_params = array();
        $where_str = $this->parseWhere($where, $where_params);

        foreach ($where_params as $wp) {
            $params [] = $wp;
        }

        $this->sql = sprintf($up_sql, trim($field, ','), $where_str);
        $this->prepare($this->sql)->exec($params);

        return true;
    }

    /**
     * 删除数据
     *
     * @param string $table
     * @param string|array $where
     * @param bool $multi 是否批量删除数据
     *      $where = array(
     *          'fields' = array(字段1,字段2,...),
     *          'values'= array(
     *                      array(字段1的值, 字段2的值),
     *                      array(字段1的值, 字段2的值))
     *      );
     * @return bool
     * @throws FrontException
     */
    protected function prepareDelete($table, $where, $multi = false)
    {
        $del_sql = "DELETE FROM %s WHERE %s";

        if (true === $multi) {
            if (empty($where ['fields']) || empty($where ['values'])) {
                throw new FrontException('data format error!');
            }

            $where_condition = array();
            foreach ($where ['fields'] as $d) {
                $where_condition[] = "{$d} = ?";
            }

            $where_str = implode(' AND ', $where_condition);
            $this->sql = sprintf($del_sql, $table, $where_str);

            $stmt = $this->prepare($this->sql);
            foreach ($where ['values'] as $data_array) {
                $stmt->exec($data_array);
            }

            return true;
        }
        else {
            $params = array();
            $where_str = $this->parseWhere($where, $params);

            $this->sql = sprintf($del_sql, $table, $where_str);
            $this->prepare($this->sql)->exec($params);

            return true;
        }
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
            }
            else {
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
     * @throws \Cross\Exception\CoreException
     * @return string
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

                    switch($operator)
                    {
                        case 'OR':

                            if (! is_array($w_value) ) {
                                throw new CoreException('OR need a array parameter');
                            }
                            foreach($w_value as $or_exp_val) {
                                $ex_operator = '=';
                                if (is_array($or_exp_val)) {
                                    list($ex_operator, $n_value) = $or_exp_val;
                                    $or_exp_val = $n_value;
                                }

                                $condition[' OR '][] = "{$w_key} {$ex_operator} ?";
                                $params [] = $or_exp_val;
                            }
                            break;

                        case 'IN':
                        case 'NOT IN':
                            if (! is_array($w_value) ) {
                                throw new CoreException('IN or NOT IN need a array parameter');
                            }

                            $in_where_condition = array();
                            foreach($w_value as $in_exp_val) {
                                $params[] = $in_exp_val;
                                $in_where_condition [] = '?';
                            }

                            $condition[' AND '][] = sprintf('%s %s (%s)', $w_key, $operator, implode(',', $in_where_condition));
                            break;

                        case 'BETWEEN':
                        case 'NOT BETWEEN':
                            if (! is_array($w_value) ) {
                                throw new CoreException('BETWEEN need a array parameter');
                            }

                            if (! isset($w_value[0]) || ! isset($w_value[1])) {
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
                foreach($condition as $sql_opt => $where_condition) {
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
            }
            else {
                $order_str = $order;
            }
        }
        else {
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
     * 获取数据表信息
     *
     * @param string $table_name
     * @return array
     * @throws CoreException
     */
    public function getTableInfo($table_name)
    {
        $sql = "SHOW COLUMNS FROM {$table_name}";
        $info = $this->fetchAll($sql);
        if ($info) {
            return $info;
        }

        throw new CoreException("SHOW {$table_name} COLUMNS error !");
    }

    /**
     * 获取自增字段名
     *
     * @param string $table_name
     * @return bool
     */
    public function getAutoIncrementName($table_name)
    {
        $table_info = $this->getTableInfo($table_name);
        foreach ($table_info as $ti) {
            if ($ti['Extra'] == 'auto_increment') {
                return $ti['Field'];
            }
        }

        return false;
    }

    /**
     * 开启事务
     *
     * @return bool
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * @return bool
     */
    public function beginTA()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * 回滚
     *
     * @return bool
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    /**
     * 返回最后操作的id
     *
     * @return string
     */
    public function insertId()
    {
        return $this->pdo->lastInsertId();
    }
}
