<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\I;

use Cross\Exception\CoreException;

/**
 * Interface IModel
 * @author ideaa <ideaa@qq.com>
 */
interface IModel
{
    /**
     * 获取单条数据
     *
     * @param array $where
     * @param string $fields
     * @return mixed
     * @throws CoreException
     */
    function get($where = array(), $fields = '*');

    /**
     * 添加
     *
     * @throws CoreException
     */
    function add();

    /**
     * 更新
     *
     * @param array $condition
     * @param array $data
     * @return bool
     * @throws CoreException
     */
    function update($condition = array(), $data = array());

    /**
     * 删除
     *
     * @param array $condition
     * @return bool
     * @throws CoreException
     */
    function del($condition = array());

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
    function getAll($where = array(), $fields = '*', $order = 1, $group_by = 1, $limit = 0);

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
    function find(&$page = array('p' => 1, 'limit' => 50), $where = array(), $fields = '*', $order = 1, $group_by = 1);

    /**
     * 查询数据, 并更新本类属性
     *
     * @param array $where
     * @return $this
     * @throws CoreException
     */
    function property($where = array());

    /**
     * 获取数据库链接
     *
     * @return \Cross\Cache\Driver\RedisDriver|\Cross\DB\Drivers\CouchDriver|\Cross\DB\Drivers\MongoDriver|\Cross\DB\Drivers\PDOSqlDriver
     * @throws CoreException
     */
    function db();

    /**
     * 自定义表名(包含前缀的完整名称)
     *
     * @param string $table
     */
    function setTable($table);

    /**
     * 连表查询
     *
     * @param string $table 表名
     * @param string $on 当前类表别名为a, 依次为b,c,d,e...
     * @param string $type 默认左联
     * @return $this
     */
    function join($table, $on, $type = 'left');

    /**
     * 指定索引
     *
     * @param string $indexName
     * @param $indexValue
     * @throws CoreException
     */
    function useIndex($indexName, $indexValue = '');

    /**
     * 仅在事务中调用get方法时生效
     *
     * @return $this
     */
    function useLock();

    /**
     * 获取表名
     *
     * @return array|mixed
     * @throws CoreException
     */
    function getTable();

    /**
     * 获取模型信息
     *
     * @param string $key
     * @return mixed
     */
    function getModelInfo($key = null);

    /**
     * 获取字段属性
     *
     * @param string $property
     * @return bool|mixed
     */
    function getPropertyInfo($property = null);

    /**
     * 更新属性值
     *
     * @param array $data
     */
    function updateProperty(array $data);

    /**
     * 获取数据库表字段
     *
     * @param string $alias 别名
     * @param bool $as 是否把别名加在字段名之前
     * @return string
     */
    function getFields($alias = '', $as = false);

    /**
     * 获取默认值
     *
     * @return array
     */
    function getDefaultData();

    /**
     * 获取属性数据
     *
     * @param bool $hasValue
     * @return array
     */
    function getArrayData($hasValue = false);
}