<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\DB\Drivers;

use Closure;
use Throwable;
use Cross\DB\SQLAssembler\SQLAssembler;
use Cross\Exception\CoreException;
use Cross\I\PDOConnector;
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
    public PDOStatement $stmt;

    /**
     * @var PDO
     */
    public PDO $pdo;

    /**
     * @var string
     */
    public string $sql;

    /**
     * 链式查询每条语句的标示符
     *
     * @var int
     */
    protected int $qid;

    /**
     * 以qid为key,存储链式查询生成的sql语句
     *
     * @var array
     */
    protected array $querySQL = [];

    /**
     * 联系查询生成的参数缓存
     *
     * @var array
     */
    protected array $queryParams;

    /**
     * @var mixed
     */
    protected mixed $params;

    /**
     * @var PDOConnector
     */
    protected PDOConnector $connector;

    /**
     * @var SQLAssembler
     */
    protected SQLAssembler $SQLAssembler;

    /**
     * @var array
     */
    protected array $connectOptions;

    /**
     * @var int
     */
    protected int $maxQueryHistoryCount = 255;

    /**
     * 创建数据库连接
     *
     * @param PDOConnector $connector
     * @param SQLAssembler $SQLAssembler
     * @param array $connectOptions 连接配置
     * @throws CoreException
     */
    public function __construct(PDOConnector $connector, SQLAssembler $SQLAssembler, array $connectOptions)
    {
        $this->connectOptions = $connectOptions;
        if (!empty($connectOptions['sequence'])) {
            $connector->setSequence($connectOptions['sequence']);
        }

        $this->setConnector($connector);
        $this->setSQLAssembler($SQLAssembler);

        $this->pdo = $this->connector->getPDO();
    }

    /**
     * 获取单条数据
     *
     * @param string $table
     * @param string $fields
     * @param array|string $where
     * @return mixed
     * @throws CoreException
     */
    public function get(string $table, string $fields, array|string $where): mixed
    {
        return $this->select($fields)->from($table)->where($where)->stmt()->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取表中所有数据
     *
     * @param string $table
     * @param string $fields
     * @param array|string $where
     * @param mixed|null $order
     * @param mixed|null $groupBy
     * @param mixed $limit 0 表示无限制
     * @return mixed
     * @throws CoreException
     */
    public function getAll(string $table, string $fields, array|string $where = [], mixed $order = null, mixed $groupBy = null, $limit = null): mixed
    {
        $data = $this->select($fields)->from($table);
        if ($where) {
            $data->where($where);
        }

        if (null !== $groupBy) {
            $data->groupBy($groupBy);
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
     * @param mixed $data
     * @param bool $multi 是否批量添加
     * @param array $insertData
     * @param bool $openTA
     * @return bool|mixed
     * @throws CoreException
     * @see SQLAssembler::add()
     */
    public function add(string $table, mixed $data, bool $multi = false, array &$insertData = [], bool $openTA = false): mixed
    {
        $this->SQLAssembler->add($table, $data, $multi);
        $this->sql = $this->SQLAssembler->getSQL();
        $this->params = $this->SQLAssembler->getParams();

        if ($multi) {
            $addCount = 0;
            if (!empty($this->params)) {
                $incName = $this->getAutoIncrementName($table);
                $stmt = $this->prepare($this->sql);
                if ($openTA) {
                    $this->beginTA();
                }

                try {
                    if (!empty($this->params)) {
                        foreach ($this->params as $p) {
                            if ($stmt->exec($p, true)) {
                                $addDataInfo = array_combine($data['fields'], $p);
                                if ($incName) {
                                    $addDataInfo[$incName] = $this->insertId();
                                }

                                $addCount++;
                                $insertData[] = $addDataInfo;
                            }
                        }
                    }
                } catch (Exception $e) {
                    $insertData = [];
                    if ($openTA) {
                        $this->rollBack();
                    }
                    throw new CoreException($e->getMessage());
                }

                if ($openTA) {
                    $this->commit();
                }
            }
            return $addCount;
        } else {
            $addCount = $this->prepare($this->sql)->exec($this->params, true);
            $lastInsertId = $this->insertId();
            if ($lastInsertId > 0) {
                return $lastInsertId;
            }

            return $addCount;
        }
    }

    /**
     * 带分页的数据查询
     *
     * @param string $table
     * @param string $fields
     * @param array|string $where
     * @param array $page
     * @param mixed|null $order
     * @param mixed|null $groupBy
     * @return mixed
     * @throws CoreException
     */
    public function find(string $table, string $fields, array|string $where,
                         array  &$page = ['p' => 1, 'limit' => 10], mixed $order = null, mixed $groupBy = null): mixed
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
            $this->SQLAssembler->find($table, $fields, $where, $page, $order, $groupBy);
            return $this->getPrepareResult(true);
        }

        return [];
    }

    /**
     * 数据更新
     *
     * @param string $table
     * @param mixed $data
     * @param mixed $where
     * @return PDOSqlDriver|int
     * @throws CoreException
     */
    public function update(string $table, mixed $data, mixed $where): PDOSqlDriver|int
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
     * @param mixed $where
     * @return PDOSqlDriver|int
     * @throws CoreException
     * @see SQLAssembler::del()
     */
    public function del(string $table, mixed $where): PDOSqlDriver|int
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
     * @return false|int
     * @throws CoreException
     */
    public function execute(string $sql): false|int
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
     * @param int|null $end
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
     * 事务
     *
     * @param Closure $handle
     * @param null $result
     * @return bool
     */
    function transaction(Closure $handle, &$result = null): bool
    {
        try {
            $this->beginTA();
            $result = $handle($this);
            if (false === $result) {
                throw new CoreException('User transaction return false!');
            }

            $this->commit();
            return true;
        } catch (Throwable $e) {
            echo $e->getMessage();
            $this->rollBack();
            return false;
        }
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
     * @param array $prepareParams prepare时的参数
     * @return PDOStatement
     * @throws CoreException
     */
    public function stmt(bool $execute = true, array $prepareParams = [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]): PDOStatement
    {
        if (!$this->qid) {
            throw new CoreException("链式风格的查询必须以->select()开始");
        }

        $this->sql = &$this->querySQL[$this->qid];
        try {
            $stmt = $this->pdo->prepare($this->sql, $prepareParams);
            if ($execute) {
                $executeParams = $this->queryParams[$this->qid];
                $stmt->execute($executeParams);
            }

            return $stmt;
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * 链式执行操作
     *
     * @param array $prepareParams
     * @return bool
     * @throws CoreException
     */
    public function stmtExecute(array $prepareParams = [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]): bool
    {
        if ($this->qid == 0) {
            throw new CoreException("无效的执行语句");
        }

        $this->sql = &$this->querySQL[$this->qid];
        try {
            $stmt = $this->pdo->prepare($this->sql, $prepareParams);
            $executeParams = $this->queryParams[$this->qid];

            return $stmt->execute($executeParams);
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
    public function prepare(string $statement, array $params = [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]): self
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
     * @param bool $rowCount
     * @return int|PDOSqlDriver
     * @throws CoreException
     */
    public function exec(array $args = [], bool $rowCount = false): int|static
    {
        try {
            $this->stmt->execute($args);
            if ($rowCount) {
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
     * @param bool $fetchAll
     * @param int $fetchStyle
     * @return mixed
     * @throws CoreException
     */
    public function stmtFetch(bool $fetchAll = false, int $fetchStyle = PDO::FETCH_ASSOC): mixed
    {
        if (!$this->stmt) {
            throw new CoreException('stmt init failed!');
        }

        if ($fetchAll) {
            return $this->stmt->fetchAll($fetchStyle);
        }

        return $this->stmt->fetch($fetchStyle);
    }

    /**
     * 获取表的字段信息
     *
     * @param string $table
     * @param bool $fieldsMap
     * @return mixed
     */
    public function getMetaData(string $table, bool $fieldsMap = true): mixed
    {
        return $this->connector->getMetaData($table, $fieldsMap);
    }

    /**
     * 获取自增字段名
     *
     * @param string $tableName
     * @return string
     */
    public function getAutoIncrementName(string $tableName): string
    {
        return $this->connector->getPK($tableName);
    }

    /**
     * @return PDOConnector
     */
    public function getConnector(): PDOConnector
    {
        return $this->connector;
    }

    /**
     * 设置PDOConnector对象
     *
     * @param PDOConnector $connector
     */
    public function setConnector(PDOConnector $connector): void
    {
        $this->connector = $connector;
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
     * @param array|string $fields
     * @return string
     */
    public function parseFields(array|string $fields): string
    {
        return $this->SQLAssembler->parseFields($fields);
    }

    /**
     * 解析where
     *
     * @param array|string $where
     * @param array|string $params
     * @return string
     * @throws CoreException
     */
    public function parseWhere(array|string $where, array|string &$params): string
    {
        return $this->SQLAssembler->parseWhere($where, $params);
    }

    /**
     * 解析order
     *
     * @param array|string $order
     * @return int|string
     */
    public function parseOrder(array|string $order): int|string
    {
        return $this->SQLAssembler->parseOrder($order);
    }

    /**
     * 解析group
     *
     * @param string $groupBy
     * @return int|string
     */
    public function parseGroup(string $groupBy): int|string
    {
        return $this->SQLAssembler->parseGroup($groupBy);
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
     * @return mixed
     */
    public function insertId(): mixed
    {
        $sequence = $this->getSQLAssembler()->getSequence();
        if (!empty($sequence)) {
            $this->getConnector()->setSequence($sequence);
        }

        return $this->connector->lastInsertID();
    }

    /**
     * 绑定sql语句,执行后给出返回结果
     *
     * @param bool $fetchAll
     * @param int $fetchStyle
     * @return mixed
     * @throws CoreException
     */
    protected function getPrepareResult(bool $fetchAll = false, int $fetchStyle = PDO::FETCH_ASSOC): mixed
    {
        $this->sql = $this->SQLAssembler->getSQL();
        $this->params = $this->SQLAssembler->getParams();

        return $this->prepare($this->sql)->exec($this->params)->stmtFetch($fetchAll, $fetchStyle);
    }

    /**
     * 生成qid
     */
    private function generateQueryID(): void
    {
        static $i = 1;
        $this->qid = $i++;

        if ($this->qid > $this->maxQueryHistoryCount) {
            array_shift($this->querySQL);
            array_shift($this->queryParams);
        }

        if ($this->qid > PHP_INT_MAX) {
            $i = 1;
        }
    }
}
