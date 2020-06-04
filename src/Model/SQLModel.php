<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Model;

use Cross\Exception\DBConnectException;
use Cross\Exception\CoreException;
use Cross\DB\Drivers\PDOSqlDriver;
use Cross\DB\DBFactory;

use PDOStatement;
use Closure;
use PDO;

/**
 * @author wonli <wonli@live.com>
 * Class SQLModel
 * @package Cross\Model
 */
class SQLModel
{
    /**
     * 主键名
     *
     * @var string
     */
    protected $pk;

    /**
     * 表名
     *
     * @var string
     */
    protected $table;

    /**
     * 连表数组
     *
     * @var array
     */
    protected $joinTables = [];

    /**
     * 自定义索引
     *
     * @var array
     */
    protected $index = [];

    /**
     * 在事务中获取单条数据时是否加锁
     *
     * @var bool
     */
    protected $useLock = false;

    /**
     * 模型信息
     *
     * <pre>
     * n 连接名
     * type 连接类型
     * table 表名
     * connect 数据库连接配置
     * </pre>
     * @var array
     */
    protected $modelInfo = [
        'n' => null,
        'type' => null,
        'table' => null,
        'connect' => [
            'host' => null,
            'port' => null,
            'user' => null,
            'pass' => null,
            'prefix' => null,
            'charset' => null,
            'name' => null,
        ],
    ];

    /**
     * 分表配置
     *
     * <pre>
     * field 按配置字段分表
     * prefix 表前缀
     * method 分表方法(hash||mod)
     * number 分表数量
     * </pre>
     *
     * @var array
     */
    protected $splitConfig = [
        'number' => 32,
        'method' => 'hash',
        'field' => null,
        'prefix' => null
    ];

    /**
     * 表字段属性
     *
     *
     * @var array
     */
    protected $fieldsInfo = [];

    /**
     * 获取单条数据
     *
     * @param array|string $where
     * @param string $fields
     * @return mixed
     * @throws CoreException|DBConnectException
     */
    function get($where = [], string $fields = '*')
    {
        if (empty($where)) {
            $where = $this->getDefaultCondition();
        }

        $query = $data = $this->db()->select($fields)->from($this->getTable());
        if ($this->useLock) {
            $params = [];
            $where = $this->db()->getSQLAssembler()->parseWhere($where, $params);
            $query->where([$where . ' for UPDATE', $params]);
        } else {
            $query->where($where);
        }

        return $query->stmt()->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 最新
     *
     * @param array|string $where
     * @param string $fields
     * @return mixed
     * @throws CoreException|DBConnectException
     */
    function latest($where = [], string $fields = '*')
    {
        if (empty($where)) {
            $where = $this->getDefaultCondition();
        }

        $query = $this->db()->select($fields)->from($this->getTable());
        if ($this->useLock) {
            $params = [];
            $where = $this->db()->getSQLAssembler()->parseWhere($where, $params);
            $query->where([$where . ' for UPDATE', $params]);
        } else {
            $query->where($where);
        }

        $query->orderBy("{$this->pk} DESC")->limit(1);

        return $query->stmt()->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 添加
     *
     * @return bool|int|mixed|string
     * @throws CoreException|DBConnectException
     */
    function add()
    {
        $insertId = $this->db()->add($this->getTable(false), $this->makeInsertData());
        if (false !== $insertId) {
            $this->{$this->pk} = $insertId;
        }

        return $insertId;
    }

    /**
     * 更新
     *
     * @param array|string $condition
     * @param array|string $data
     * @return bool
     * @throws CoreException|DBConnectException
     */
    function update($condition = [], $data = [])
    {
        if (empty($data)) {
            $data = $this->getModifiedData();
        }

        if (empty($condition)) {
            $condition = $this->getDefaultCondition(true);
        }

        return $this->db()->update($this->getTable(false), $data, $condition);
    }

    /**
     * 更新或添加（必须要有唯一索引）
     *
     * @return bool
     * @throws CoreException|DBConnectException
     */
    function updateOrAdd()
    {
        $data = $this->getModifiedData();
        if (empty($data)) {
            $dup = "{$this->pk}={$this->pk}";
        } else {
            $dup = [];
            foreach ($data as $k => $v) {
                $dup[] = sprintf("{$k}='%s'", $v);
            }

            $dup = implode(',', $dup);
        }

        return $this->db()->insert($this->getTable(false), $data)
            ->on("DUPLICATE KEY UPDATE {$dup}")->stmtExecute();
    }

    /**
     * 删除
     *
     * @param array|string $condition
     * @return bool
     * @throws CoreException|DBConnectException
     */
    function del($condition = [])
    {
        if (empty($condition)) {
            $condition = $this->getDefaultCondition(true);
        }

        return $this->db()->del($this->getTable(false), $condition);
    }

    /**
     * 获取数据
     *
     * @param array|string $where
     * @param string $fields
     * @param string|int $order
     * @param string|int $group_by
     * @param int $limit
     * @return mixed
     * @throws CoreException|DBConnectException
     */
    function getAll($where = [], string $fields = '*', $order = null, $group_by = null, $limit = null)
    {
        if (empty($where)) {
            $where = $this->getDefaultCondition();
        }

        return $this->db()->getAll($this->getTable(), $fields, $where, $order, $group_by, $limit);
    }

    /**
     * 按分页获取数据
     *
     * @param array $page
     * @param array|string $where
     * @param string $fields
     * @param string|int $order
     * @param string|int $group_by
     * @return mixed
     * @throws CoreException|DBConnectException
     */
    function find(array &$page = ['p' => 1, 'limit' => 50], $where = [], string $fields = '*', $order = null, $group_by = null)
    {
        if (empty($where)) {
            $where = $this->getDefaultCondition();
        }

        return $this->db()->find($this->getTable(), $fields, $where, $page, $order, $group_by);
    }

    /**
     * 原生SQL
     *
     * @param string $sql
     * @param mixed ...$params
     * @return PDOStatement
     * @throws CoreException
     * @throws DBConnectException
     */
    function rawSql(string $sql, ...$params)
    {
        return $this->db()->rawSql($sql, ...$params)->stmt();
    }

    /**
     * 查询数据, 并更新本类属性
     *
     * @param array $where
     * @return $this
     * @throws CoreException|DBConnectException
     */
    function property($where = []): self
    {
        $data = $this->get($where);
        if (!empty($data)) {
            $this->updateProperty($data);
        }

        return $this;
    }

    /**
     * 获取数据库链接
     *
     * @return PDOSqlDriver
     * @throws CoreException|DBConnectException
     */
    function db(): PDOSqlDriver
    {
        return $this->getPDOInstance();
    }

    /**
     * 自定义表名(包含前缀的完整名称)
     *
     * @param string $table
     */
    function setTable(string $table): void
    {
        $this->table = $table;
    }

    /**
     * 连表查询
     *
     * @param string $table 表名
     * @param string $on 当前类表别名为a, 依次为b,c,d,e...
     * @param string $type 默认左联
     * @return $this
     */
    function join(string $table, string $on, string $type = 'left'): self
    {
        $this->joinTables[] = [
            'name' => $table,
            'on' => $on,
            't' => strtoupper($type),
        ];

        return $this;
    }

    /**
     * 将已赋值字段设为索引
     *
     * @return $this
     */
    function asIndex(): self
    {
        foreach ($this->fieldsInfo as $property => $value) {
            if (null !== $this->{$property}) {
                $this->index[$property] = $this->{$property};
            }
        }

        return $this;
    }

    /**
     * 指定索引
     *
     * @param string $indexName
     * @param $indexValue
     * @throws CoreException
     */
    function useIndex(string $indexName, $indexValue): void
    {
        if (!property_exists($this, $indexName)) {
            throw new CoreException('不支持的索引名称');
        }

        $this->{$indexName} = $indexValue;
        $this->index[$indexName] = $indexValue;
    }

    /**
     * 仅在事务中调用get方法时生效
     *
     * @return $this
     */
    function useLock(): self
    {
        $this->useLock = true;
        return $this;
    }

    /**
     * 获取表名
     *
     * @param bool $userJoinTable 更新，修改，删除时使用默认表名
     * @return string
     * @throws CoreException
     */
    function getTable(bool $userJoinTable = true): string
    {
        $table = $this->getOriTableName();
        if (!$userJoinTable) {
            return $table;
        }

        if ($userJoinTable && !empty($this->joinTables)) {
            $i = 98;
            $joinTables[] = "{$table} a";
            array_map(function ($d) use (&$joinTables, &$i) {
                $joinTables[] = sprintf("%s JOIN %s %s ON %s", $d['t'], $d['name'], chr($i), $d['on']);
                $i++;
            }, $this->joinTables);

            $table = implode(' ', $joinTables);
        }

        $this->table = $table;
        return $this->table;
    }

    /**
     * 获取数据中的表名
     *
     * @return string
     * @throws CoreException
     */
    function getOriTableName(): string
    {
        $table = $this->splitMethod();
        if (empty($table)) {
            $table = &$this->modelInfo['table'];
        }

        return $this->modelInfo['connect']['prefix'] . $table;
    }

    /**
     * 获取模型信息
     *
     * @param string $key
     * @return mixed
     */
    function getModelInfo($key = null)
    {
        if (null === $key) {
            return $this->modelInfo;
        } elseif (isset($this->modelInfo[$key])) {
            return $this->modelInfo[$key];
        } else {
            return false;
        }
    }

    /**
     * 验证数据是否能映射到类实例
     *
     * @param array $data
     * @param bool $updateProperty 为true时更新类属性
     * @return bool
     */
    function verifyModelData(array $data, bool $updateProperty = false): bool
    {
        $isModel = true;
        foreach ($this->fieldsInfo as $key => $value) {
            if (!isset($data[$key])) {
                $isModel = false;
                break;
            }
        }

        if ($isModel && $updateProperty) {
            $this->updateProperty($data);
        }

        return $isModel;
    }

    /**
     * 成员属性名称数组
     *
     * @return array
     */
    function getProperties(): array
    {
        $data = [];
        foreach ($this->fieldsInfo as $p => $config) {
            $data[] = $p;
        }

        return $data;
    }

    /**
     * 获取字段属性
     *
     * @param string $property
     * @return bool|mixed
     */
    function getPropertyInfo($property = null)
    {
        if (null === $property) {
            return $this->fieldsInfo;
        } elseif (isset($this->fieldsInfo[$property])) {
            return $this->fieldsInfo[$property];
        } else {
            return false;
        }
    }

    /**
     * 更新属性值
     *
     * @param array $data
     * @param Closure $callback
     */
    function updateProperty(array $data, Closure $callback = null): void
    {
        if (!empty($data)) {
            foreach ($data as $property => $value) {
                if (property_exists($this, $property)) {
                    if (null !== $callback) {
                        $this->{$property} = $callback($property, $value);
                    } else {
                        $this->{$property} = $value;
                    }
                }
            }
        }
    }

    /**
     * 重置属性
     */
    function resetProperty(): void
    {
        $this->index = [];
        array_walk($this->fieldsInfo, function ($v, $p) {
            $this->{$p} = null;
        });
    }

    /**
     * 获取数据库表字段
     *
     * @param string $alias 别名
     * @param bool $asPrefix 是否把别名加在字段名之前
     * @return string
     */
    function getFields(string $alias = '', bool $asPrefix = false): string
    {
        $fieldsList = array_keys($this->fieldsInfo);
        if (!empty($alias)) {
            array_walk($fieldsList, function (&$d) use ($alias, $asPrefix) {
                if ($asPrefix) {
                    $d = "{$alias}.{$d} {$alias}_{$d}";
                } else {
                    $d = "{$alias}.{$d}";
                }
            });
        }

        return implode(', ', $fieldsList);
    }

    /**
     * 获取查询条件
     *
     * @param string $tableAlias 表别名
     * @return array
     * @throws CoreException
     */
    function getCondition(string $tableAlias = ''): array
    {
        $defaultCondition = $this->getDefaultCondition();
        if (empty($defaultCondition)) {
            return [];
        }

        if (empty($tableAlias)) {
            return $defaultCondition;
        }

        $condition = [];
        foreach ($defaultCondition as $k => $v) {
            $condition["{$tableAlias}.{$k}"] = $v;
        }

        return $condition;
    }

    /**
     * 获取默认值
     *
     * @return array
     */
    function getDefaultData(): array
    {
        $data = [];
        foreach ($this->fieldsInfo as $p => $c) {
            if (0 === strcasecmp($c['default_value'], 'CURRENT_TIMESTAMP')) {
                continue;
            }

            $data[$p] = $c['default_value'];
        }

        return $data;
    }

    /**
     * 获取属性数据
     *
     * @param bool $hasValue
     * @return array
     */
    function getArrayData(bool $hasValue = false): array
    {
        $data = [];
        foreach ($this->fieldsInfo as $p => $c) {
            if (0 === strcasecmp($c['default_value'], 'CURRENT_TIMESTAMP')) {
                continue;
            }

            if ($hasValue && null === $this->{$p}) {
                continue;
            }

            $data[$p] = $this->{$p};
        }

        return $data;
    }

    /**
     * 获取修改过的数据
     *
     * @return array
     */
    protected function getModifiedData(): array
    {
        $data = [];
        foreach ($this->fieldsInfo as $p => $c) {
            if (0 === strcasecmp($c['default_value'], 'CURRENT_TIMESTAMP')) {
                continue;
            }

            $value = $this->{$p};
            if (null !== $value) {
                $data["{$p}"] = $value;
            }
        }

        return $data;
    }

    /**
     * 获取待插入数据
     *
     * @return array
     */
    protected function makeInsertData(): array
    {
        $data = [];
        foreach ($this->fieldsInfo as $p => $c) {
            $value = $this->{$p};
            if ($this->modelInfo['type'] == 'oracle') {
                //oracle处理自增主键
                $sequence = &$this->modelInfo['connect']['sequence'];
                if (null === $value && $c['primary'] && null !== $sequence) {
                    $value = ['#SEQ#' => $sequence];
                }
            } else {
                if ($c['auto_increment'] || (0 === strcasecmp($c['default_value'], 'CURRENT_TIMESTAMP'))) {
                    continue;
                }
            }

            if (null === $value) {
                $value = $c['default_value'];
            }

            $data["{$p}"] = $value;
        }

        return $data;
    }

    /**
     * 获取默认的sequence（oracle）
     *
     * @param bool $onlyName 默认返回表达式
     * @return string|array
     */
    function getDefaultSequence($onlyName = false)
    {
        $sequence = &$this->modelInfo['connect']['sequence'];
        if (!empty($sequence) && $onlyName) {
            return $sequence;
        } elseif (!empty($sequence)) {
            return ['#SEQ#' => $sequence];
        }

        return '';
    }

    /**
     * 获取默认条件
     *
     * @param bool $strictModel 严格模式下索引不能为空
     * @return mixed
     * @throws CoreException
     */
    function getDefaultCondition(bool $strictModel = false): array
    {
        if (null !== $this->{$this->pk}) {
            $this->index = ["{$this->pk}" => $this->{$this->pk}];
        } else if (empty($this->index)) {
            $this->asIndex();
        }

        if ($strictModel && empty($this->index)) {
            throw new CoreException('请指定索引');
        }

        return $this->index;
    }

    /**
     * 分表方法
     *
     * @return string
     * @throws CoreException
     */
    protected function splitMethod(): string
    {
        $field = &$this->splitConfig['field'];
        if (null === $field) {
            return '';
        }

        $v = $this->{$field};
        if (null === $v) {
            throw new CoreException('分表字段的值不能为空');
        }

        $number = &$this->splitConfig['number'];
        if ($this->splitConfig['method'] == 'mod') {
            if (!is_int($v)) {
                throw new CoreException("分表字段{$field}的值仅支持数字");
            }

            $suffix = $v % $number;
        } else {
            $v = sprintf('%u', crc32($v));
            $suffix = fmod($v, $number);
        }

        return $this->splitConfig['prefix'] . $suffix;
    }

    /**
     * 连接数据库
     *
     * @return PDOSqlDriver
     * @throws CoreException
     * @throws DBConnectException
     */
    protected function getPDOInstance(): PDOSqlDriver
    {
        static $model = null;
        if (null === $model) {
            $model = DBFactory::make($this->modelInfo['type'], $this->modelInfo['connect']);
        }

        return $model;
    }
}