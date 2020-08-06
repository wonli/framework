<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\DB\Drivers;

use Cross\DB\SQLAssembler\SQLAssembler;
use Cross\Exception\CoreException;
use Cross\I\PDOConnecter;
use Cross\I\SqlInterface;
use PDOException;
use PDOStatement;
use Exception;
use PDO;

/**
 * @author wonli <wonli@live.com>
 * Class PDOSqlDriver
 * @package Cross\DB\Drivers
 */
class PDOSqlDriver implements SqlInterface
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
     * 链式查询每条语句的标示符
     *
     * @var int
     */
    protected $qid;

    /**
     * 以qid为key,存储链式查询生成的sql语句
     *
     * @var array
     */
    protected $querySQL = [];

    /**
     * 联系查询生成的参数缓存
     *
     * @var array
     */
    protected $queryParams;

    /**
     * @var string|array
     */
    protected $params;

    /**
     * @var PDOConnecter
     */
    protected $connecter;

    /**
     * @var SQLAssembler
     */
    protected $SQLAssembler;

    /**
     * @var array
     */
    protected $connectOptions;

    /**
     * 创建数据库连接
     *
     * @param PDOConnecter $connecter
     * @param SQLAssembler $SQLAssembler
     * @param array $connectOptions 连接配置
     * @throws CoreException
     */
    public function __construct(PDOConnecter $connecter, SQLAssembler $SQLAssembler, array $connectOptions)
    {
        $this->connectOptions = $connectOptions;
        if (!empty($connectOptions['sequence'])) {
            $connecter->setSequence($connectOptions['sequence']);
        }

        $this->setConnecter($connecter);
        $this->setSQLAssembler($SQLAssembler);

        $this->pdo = $this->connecter->getPDO();
        if (!$this->pdo instanceof PDO) {
            throw new CoreException("init pdo failed!");
        }
    }

    /**
     * 获取单条数据
     *
     * @param string $table
     * @param string $fields
     * @param string|array $where
     * @return mixed
     * @throws CoreException
     */
    public function get(string $table, string $fields, $where)
    {
        return $this->select($fields)->from($table)->where($where)->stmt()->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取表中所有数据
     *
     * @param string $table
     * @param string $fields
     * @param string|array $where
     * @param int|string $order
     * @param int|string $group_by
     * @param int|string $limit 0 表示无限制
     * @return mixed
     * @throws CoreException
     */
    public function getAll(string $table, string $fields, $where = [], $order = null, $group_by = null, $limit = null)
    {
        $data = $this->select($fields)->from($table);
        if ($where) {
            $data->where($where);
        }

        if (null !== $group_by) {
            $data->groupBy($group_by);
        }

        if (null !== $order) {
            $data->orderBy($order);
        }

        if (null !== $limit) {
            $data->limit($limit);
        }

        return $data->stmt()->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 插入数据
     *
     * @param string $table
     * @param string|array $data
     * @param bool $multi 是否批量添加
     * @param array $insert_data 批量添加的数据
     * @param bool $openTA 批量添加时是否开启事务
     * @return bool|mixed
     * @throws CoreException
     * @see SQLAssembler::add()
     */
    public function add(string $table, $data, bool $multi = false, &$insert_data = [], bool $openTA = false)
    {
        $this->SQLAssembler->add($table, $data, $multi);
        $this->sql = $this->SQLAssembler->getSQL();
        $this->params = $this->SQLAssembler->getParams();

        if ($multi) {
            $add_count = 0;
            if (!empty($this->params)) {
                $inc_name = $this->getAutoIncrementName($table);
                $stmt = $this->prepare($this->sql);
                if ($openTA) {
                    $this->beginTA();
                }

                try {
                    if (!empty($this->params)) {
                        foreach ($this->params as $p) {
                            if ($stmt->exec($p, true)) {
                                $add_data_info = array_combine($data['fields'], $p);
                                if ($inc_name) {
                                    $add_data_info[$inc_name] = $this->insertId();
                                }

                                $add_count++;
                                $insert_data[] = $add_data_info;
                            }
                        }
                    }
                } catch (Exception $e) {
                    $insert_data = [];
                    if ($openTA) {
                        $this->rollBack();
                    }
                    throw new CoreException($e->getMessage());
                }

                if ($openTA) {
                    $this->commit();
                }
            }
            return $add_count;
        } else {
            $add_count = $this->prepare($this->sql)->exec($this->params, true);
            $last_insert_id = $this->insertId();
            if ($last_insert_id > 0) {
                return $last_insert_id;
            }

            return $add_count;
        }
    }

    /**
     * 带分页的数据查询
     *
     * @param string $table
     * @param string $fields
     * @param string|array $where
     * @param string|int $order
     * @param array $page
     * @param int $group_by
     * @return mixed
     * @throws CoreException
     */
    public function find(string $table, string $fields, $where, array &$page = ['p' => 1, 'limit' => 10], $order = null, $group_by = null)
    {
        if (!isset($page['result_count'])) {
            $total = $this->get($table, 'COUNT(*) as TOTAL', $where);
            $page['result_count'] = (int)$total['TOTAL'];
        }

        $page['p'] = $page['p'] ?? 1;
        $page['limit'] = max(1, (int)$page['limit']);
        $page['total_page'] = ceil($page['result_count'] / $page['limit']);

        if ($page['p'] <= $page['total_page']) {
            $page['p'] = max(1, $page['p']);
            $this->SQLAssembler->find($table, $fields, $where, $page, $order, $group_by);
            return $this->getPrepareResult(true);
        }

        return [];
    }

    /**
     * 数据更新
     *
     * @param string $table
     * @param string|array $data
     * @param string|array $where
     * @return bool
     * @throws CoreException
     */
    public function update(string $table, $data, $where)
    {
        $this->SQLAssembler->update($table, $data, $where);
        $this->sql = $this->SQLAssembler->getSQL();
        $this->params = $this->SQLAssembler->getParams();

        return $this->prepare($this->sql)->exec($this->params, true);
    }

    /**
     * 删除
     *
     * @param string $table
     * @param string|array $where
     * @return bool
     * @throws CoreException
     * @see SQLAssembler::del()
     */
    public function del(string $table, $where)
    {
        $this->SQLAssembler->del($table, $where);
        $this->sql = $this->SQLAssembler->getSQL();
        $this->params = $this->SQLAssembler->getParams();

        return $this->prepare($this->sql)->exec($this->params, true);
    }

    /**
     * 支持参数绑定的原生SQL查询
     *
     * @param string $sql
     * @param array $params
     * @return $this
     */
    public function rawSql(string $sql, ...$params): self
    {
        $this->generateQueryID();
        $this->querySQL[$this->qid] = trim(trim($sql, ';'));
        $this->queryParams[$this->qid] = $params;

        return $this;
    }

    /**
     * 执行sql 用于无返回值的操作
     *
     * @param string $sql
     * @return int
     * @throws CoreException
     */
    public function execute(string $sql)
    {
        try {
            return $this->pdo->exec($sql);
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * 链式查询以select开始,跟原生的sql语句保持一致
     *
     * @param string $fields
     * @param string $modifier
     * @return PDOSqlDriver
     */
    function select(string $fields = '*', string $modifier = ''): self
    {
        $this->generateQueryID();
        $this->querySQL[$this->qid] = $this->SQLAssembler->select($fields, $modifier);
        $this->queryParams[$this->qid] = [];
        return $this;
    }

    /**
     * insert
     *
     * @param string $table
     * @param array $data
     * @param string $modifier
     * @return PDOSqlDriver
     */
    function insert(string $table, array $data = [], string $modifier = ''): self
    {
        $params = [];
        $this->generateQueryID();
        $this->querySQL[$this->qid] = $this->SQLAssembler->insert($table, $data, $modifier, $params);
        $this->queryParams[$this->qid] = $params;
        return $this;
    }

    /**
     * replace
     *
     * @param string $table
     * @param string $modifier
     * @return PDOSqlDriver
     */
    function replace(string $table, string $modifier = ''): self
    {
        $this->generateQueryID();
        $this->querySQL[$this->qid] = $this->SQLAssembler->replace($table, $modifier);
        $this->queryParams[$this->qid] = [];
        return $this;
    }

    /**
     * @param string $table
     * @return PDOSqlDriver
     */
    function from(string $table): self
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->from($table);
        return $this;
    }

    /**
     * @param $where
     * @return PDOSqlDriver
     * @throws CoreException
     */
    function where($where): self
    {
        $params = &$this->queryParams[$this->qid];
        $this->querySQL[$this->qid] .= $this->SQLAssembler->where($where, $params);

        return $this;
    }

    /**
     * @param int $start
     * @param int $end
     * @return PDOSqlDriver
     */
    function limit(int $start, int $end = null): self
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->limit($start, $end);
        return $this;
    }

    /**
     * @param int $offset
     * @return PDOSqlDriver
     */
    function offset(int $offset): self
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->offset($offset);
        return $this;
    }

    /**
     * @param $order
     * @return PDOSqlDriver
     */
    function orderBy($order): self
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->orderBy($order);
        return $this;
    }

    /**
     * @param $group
     * @return PDOSqlDriver
     */
    function groupBy($group): self
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->groupBy($group);
        return $this;
    }

    /**
     * @param string $having
     * @return PDOSqlDriver
     */
    function having(string $having): self
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->having($having);
        return $this;
    }

    /**
     * @param string $procedure
     * @return PDOSqlDriver
     */
    function procedure(string $procedure): self
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->procedure($procedure);
        return $this;
    }

    /**
     * @param string $var_name
     * @return PDOSqlDriver
     */
    public function into(string $var_name): self
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->into($var_name);
        return $this;
    }

    /**
     * @param mixed $set
     * @return PDOSqlDriver
     */
    public function set($set): self
    {
        $params = &$this->queryParams[$this->qid];
        $this->querySQL[$this->qid] .= $this->SQLAssembler->set($set, $params);

        return $this;
    }

    /**
     * @param string $on
     * @return PDOSqlDriver
     */
    function on(string $on): self
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->on($on);
        return $this;
    }

    /**
     * 当前SQL语句及参数
     *
     * @return mixed
     */
    function getSQL(): array
    {
        $this->sql = &$this->querySQL[$this->qid];
        return [
            'sql' => $this->sql,
            'params' => $this->queryParams[$this->qid]
        ];
    }

    /**
     * 获取表前缀
     *
     * @return string
     */
    function getPrefix(): string
    {
        return $this->SQLAssembler->getPrefix();
    }

    /**
     * 绑定链式查询生成的sql语句并返回stmt对象
     *
     * @param bool $execute 是否调用stmt的execute
     * @param array $prepare_params prepare时的参数
     * @return PDOStatement
     * @throws CoreException
     */
    public function stmt(bool $execute = true, array $prepare_params = [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]): PDOStatement
    {
        if (!$this->qid) {
            throw new CoreException("链式风格的查询必须以->select()开始");
        }

        $this->sql = &$this->querySQL[$this->qid];
        try {
            $stmt = $this->pdo->prepare($this->sql, $prepare_params);
            if ($execute) {
                $execute_params = $this->queryParams[$this->qid];
                $stmt->execute($execute_params);
            }

            unset($this->querySQL[$this->qid], $this->queryParams[$this->qid]);
            return $stmt;
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * 链式执行操作
     *
     * @param array $prepare_params
     * @return bool
     * @throws CoreException
     */
    public function stmtExecute($prepare_params = [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY])
    {
        if ($this->qid == 0) {
            throw new CoreException("无效的执行语句");
        }

        $this->sql = &$this->querySQL[$this->qid];
        try {
            $stmt = $this->pdo->prepare($this->sql, $prepare_params);
            $execute_params = $this->queryParams[$this->qid];

            unset($this->querySQL[$this->qid], $this->queryParams[$this->qid]);
            return $stmt->execute($execute_params);
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * 参数绑定
     *
     * @param string $statement
     * @param array $params
     * @return $this
     * @throws CoreException
     */
    public function prepare($statement, $params = [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]): self
    {
        try {
            $this->stmt = $this->pdo->prepare($statement, $params);
            if (!$this->stmt) {
                throw new CoreException("PDO prepare failed!");
            }

            return $this;
        } catch (PDOException $e) {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * 执行参数绑定
     *
     * @param array $args
     * @param bool $row_count
     * @return int|$this
     * @throws CoreException
     */
    public function exec(array $args = [], bool $row_count = false)
    {
        try {
            $this->stmt->execute($args);
            if ($row_count) {
                return $this->stmt->rowCount();
            }

            return $this;
        } catch (PDOException $e) {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * 返回参数绑定结果
     *
     * @param bool $fetch_all
     * @param int $fetch_style
     * @return mixed
     * @throws CoreException
     */
    public function stmtFetch(bool $fetch_all = false, int $fetch_style = PDO::FETCH_ASSOC)
    {
        if (!$this->stmt) {
            throw new CoreException('stmt init failed!');
        }

        if ($fetch_all) {
            return $this->stmt->fetchAll($fetch_style);
        }

        return $this->stmt->fetch($fetch_style);
    }

    /**
     * 获取表的字段信息
     *
     * @param string $table
     * @param bool $fields_map
     * @return mixed
     */
    public function getMetaData(string $table, bool $fields_map = true)
    {
        return $this->connecter->getMetaData($table, $fields_map);
    }

    /**
     * 获取自增字段名
     *
     * @param string $table_name
     * @return string
     */
    public function getAutoIncrementName(string $table_name): string
    {
        return $this->connecter->getPK($table_name);
    }

    /**
     * @return PDOConnecter
     */
    public function getConnecter(): PDOConnecter
    {
        return $this->connecter;
    }

    /**
     * 设置PDOConnecter对象
     *
     * @param PDOConnecter $connecter
     */
    public function setConnecter(PDOConnecter $connecter): void
    {
        $this->connecter = $connecter;
    }

    /**
     * @return SQLAssembler
     */
    public function getSQLAssembler(): SQLAssembler
    {
        return $this->SQLAssembler;
    }

    /**
     * 设置SQLAssembler对象
     *
     * @param SQLAssembler $SQLAssembler
     */
    public function setSQLAssembler(SQLAssembler $SQLAssembler): void
    {
        $this->SQLAssembler = $SQLAssembler;
    }

    /**
     * 解析fields
     *
     * @param string|array $fields
     * @return string
     */
    public function parseFields($fields): string
    {
        return $this->SQLAssembler->parseFields($fields);
    }

    /**
     * 解析where
     *
     * @param string|array $where
     * @param string|array $params
     * @return string
     * @throws CoreException
     */
    public function parseWhere($where, &$params): string
    {
        return $this->SQLAssembler->parseWhere($where, $params);
    }

    /**
     * 解析order
     *
     * @param string|array $order
     * @return int|string
     */
    public function parseOrder($order)
    {
        return $this->SQLAssembler->parseOrder($order);
    }

    /**
     * 解析group
     *
     * @param string $group_by
     * @return int|string
     */
    public function parseGroup($group_by)
    {
        return $this->SQLAssembler->parseGroup($group_by);
    }

    /**
     * 开启事务
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * @return bool
     */
    public function beginTA(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * 回滚
     *
     * @return bool
     */
    public function rollBack(): bool
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
        $sequence = $this->getSQLAssembler()->getSequence();
        if (!empty($sequence)) {
            $this->getConnecter()->setSequence($sequence);
        }

        return $this->connecter->lastInsertID();
    }

    /**
     * 绑定sql语句,执行后给出返回结果
     *
     * @param bool $fetch_all
     * @param int $fetch_style
     * @return mixed
     * @throws CoreException
     */
    protected function getPrepareResult(bool $fetch_all = false, int $fetch_style = PDO::FETCH_ASSOC)
    {
        $this->sql = $this->SQLAssembler->getSQL();
        $this->params = $this->SQLAssembler->getParams();

        return $this->prepare($this->sql)->exec($this->params)->stmtFetch($fetch_all, $fetch_style);
    }

    /**
     * 生成qid
     */
    private function generateQueryID(): void
    {
        do {
            $qid = mt_rand(1, 99999);
            if (!isset($this->querySQL[$qid])) {
                $this->qid = $qid;
                break;
            }
        } while (true);
    }
}
