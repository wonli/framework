<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Model;

use Cross\Exception\CoreException;
use Cross\DB\Drivers\PDOSqlDriver;
use Cross\MVC\Module;

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
     * mode 链接配置名称,如: mysql:db
     * table 表名
     * primary_key 主键
     * link_type 类型
     * link_name 名称
     * </pre>
     * @var array
     */
    protected $modelInfo = [
        'mode' => null,
        'table' => null,
        'primary_key' => null,
        'link_type' => null,
        'link_name' => null
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
    protected static $fieldsInfo = [];

    /**
     * 获取单条数据
     *
     * @param array $where
     * @param string $fields
     * @return mixed
     * @throws CoreException
     */
    function get($where = array(), $fields = '*')
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
     * @param array $where
     * @param string $fields
     * @return mixed
     * @throws CoreException
     */
    function latest($where = array(), $fields = '*')
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

        $pk = empty($this->modelInfo['primary_key']) ? '1' : $this->modelInfo['primary_key'];
        $query->orderBy("{$pk} DESC")->limit(1);

        return $query->stmt()->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 添加
     *
     * @throws CoreException
     */
    function add()
    {
        $insertId = $this->db()->add($this->getTable(false), $this->makeInsertData());
        if (false !== $insertId) {
            $primaryKey = &$this->modelInfo['primary_key'];
            if ($primaryKey) {
                $this->{$primaryKey} = $insertId;
            }
        }

        return $insertId;
    }

    /**
     * 更新
     *
     * @param array $condition
     * @param array $data
     * @return bool
     * @throws CoreException
     */
    function update($condition = array(), $data = array())
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
     * @throws CoreException
     */
    function updateOrAdd()
    {
        $data = $this->getModifiedData();
        if (empty($data)) {
            $pk = $this->modelInfo['primary_key'];
            $dup = "`{$pk}`={$pk}";
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
     * @param array $condition
     * @return bool
     * @throws CoreException
     */
    function del($condition = array())
    {
        if (empty($condition)) {
            $condition = $this->getDefaultCondition(true);
        }

        return $this->db()->del($this->getTable(false), $condition);
    }

    /**
     * 获取数据
     *
     * @param array $where
     * @param string $fields
     * @param string|int $order
     * @param string|int $group_by
     * @param int $limit
     * @return mixed
     * @throws CoreException
     */
    function getAll($where = array(), $fields = '*', $order = null, $group_by = null, $limit = null)
    {
        return $this->db()->getAll($this->getTable(), $fields, $where, $order, $group_by, $limit);
    }

    /**
     * 按分页获取数据
     *
     * @param array $page
     * @param array $where
     * @param string $fields
     * @param string|int $order
     * @param string|int $group_by
     * @return mixed
     * @throws CoreException
     */
    function find(&$page = array('p' => 1, 'limit' => 50), $where = array(), $fields = '*', $order = null, $group_by = null)
    {
        return $this->db()->find($this->getTable(), $fields, $where, $order, $page, $group_by);
    }

    /**
     * 查询数据, 并更新本类属性
     *
     * @param array $where
     * @return $this
     * @throws CoreException
     */
    function property($where = array())
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
     * @throws CoreException
     */
    function db()
    {
        return $this->getModuleInstance()->link;
    }

    /**
     * 自定义表名(包含前缀的完整名称)
     *
     * @param string $table
     */
    function setTable($table)
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
    function join($table, $on, $type = 'left')
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
    function asIndex()
    {
        foreach (self::$fieldsInfo as $property => $value) {
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
    function useIndex($indexName, $indexValue)
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
    function useLock()
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
    function getTable($userJoinTable = true): string
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
     * @throws CoreException
     */
    function getOriTableName(): string
    {
        $table = $this->splitMethod();
        if (empty($table)) {
            $table = &$this->modelInfo['table'];
        }

        return $this->getModuleInstance()->getPrefix($table);
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
     * 获取字段属性
     *
     * @param string $property
     * @return bool|mixed
     */
    function getPropertyInfo($property = null)
    {
        if (null === $property) {
            return self::$fieldsInfo;
        } elseif (isset(self::$fieldsInfo[$property])) {
            return self::$fieldsInfo[$property];
        } else {
            return false;
        }
    }

    /**
     * 更新属性值
     *
     * @param array $data
     * @param Closure|null $callback
     */
    function updateProperty(array $data, Closure $callback = null)
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
    function resetProperty()
    {
        $this->index = [];
        array_walk(self::$fieldsInfo, function ($v, $p) {
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
    function getFields($alias = '', $asPrefix = false)
    {
        $fieldsList = array_keys(self::$fieldsInfo);
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
     * @return array|mixed
     * @throws CoreException
     */
    function getCondition($tableAlias = '')
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
    function getDefaultData()
    {
        $data = array();
        foreach (self::$fieldsInfo as $p => $c) {
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
    function getArrayData($hasValue = false)
    {
        $data = array();
        foreach (self::$fieldsInfo as $p => $c) {
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
    protected function getModifiedData()
    {
        $data = array();
        foreach (self::$fieldsInfo as $p => $c) {
            if (0 === strcasecmp($c['default_value'], 'CURRENT_TIMESTAMP')) {
                continue;
            }

            $value = $this->{$p};
            if (null !== $value) {
                $data["`{$p}`"] = $value;
            }
        }

        return $data;
    }

    /**
     * 获取待插入数据
     *
     * @return array
     */
    protected function makeInsertData()
    {
        $data = array();
        foreach (self::$fieldsInfo as $p => $c) {
            $value = $this->{$p};
            if ($c['auto_increment'] || (0 === strcasecmp($c['default_value'], 'CURRENT_TIMESTAMP'))) {
                continue;
            }

            if (null === $value) {
                $value = $c['default_value'];
            }

            $data["`{$p}`"] = $value;
        }

        return $data;
    }

    /**
     * 获取默认条件
     *
     * @param bool $strictModel 严格模式下索引不能为空
     * @return mixed
     * @throws CoreException
     */
    protected function getDefaultCondition($strictModel = false)
    {
        $pkName = &$this->modelInfo['primary_key'];
        if (null !== $this->{$pkName}) {
            $this->index = [$pkName => $this->{$pkName}];
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
     * 链接数据库
     *
     * @return Module
     * @throws CoreException
     */
    protected function getModuleInstance()
    {
        static $model = null;
        if (null === $model) {
            $model = new Module($this->modelInfo['mode']);
        }

        return $model;
    }
}