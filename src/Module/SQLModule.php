<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Module;

use Cross\Exception\CoreException;
use Cross\MVC\Module;

/**
 * 提供简单的CRUD功能
 *
 * @author wonli <wonli@live.com>
 * Class MysqlModule
 * @package Cross\Module
 */
class SQLModule extends Module
{
    /**
     * 要操作的数据表名
     *
     * @var string
     */
    protected $t;

    /**
     * @var array()
     */
    protected static $instance;

    /**
     * 是否自动添加表前缀
     *
     * @var bool
     */
    protected $auto_prefix = true;

    /**
     * SQLModule constructor.
     *
     * @param string $params
     * @throws CoreException
     */
    function __construct($params = '')
    {
        parent::__construct($params);
        if ($this->auto_prefix) {
            $this->t = $this->getPrefix($this->t);
        }
    }

    /**
     * 实例化当前类的子类
     *
     * @param string $args
     * @return static::get_called_class()
     */
    static public function init($args = '')
    {
        $called_class_name = get_called_class();
        if (empty(self::$instance[$called_class_name])) {
            self::$instance[$called_class_name] = new $called_class_name($args);
        }

        return self::$instance[$called_class_name];
    }

    /**
     * 设置表名
     *
     * @param string $table_name
     * @param bool $add_prefix
     * @return static ::get_called_class()
     */
    public function setTable($table_name, $add_prefix = true)
    {
        $this->t = $table_name;
        if ($add_prefix) {
            $this->t = $this->getPrefix($table_name);
        }

        return $this;
    }

    /**
     * @see PDOSqlDriver::get()
     *
     * @param $condition
     * @param string $fields
     * @return mixed
     * @throws CoreException
     */
    public function get($condition, $fields = '*')
    {
        return $this->link->get($this->t, $fields, $condition);
    }

    /**
     * @see PDOSqlDriver::getAll()
     *
     * @param null $where
     * @param int $order
     * @param int $group_by
     * @param int $limit
     * @param string $fields
     * @return array
     * @throws CoreException
     */
    public function getAll($where = null, $order = 1, $group_by = 1, $limit = 0, $fields = '*')
    {
        return $this->link->getAll($this->t, $fields, $where, $order, $group_by, $limit);
    }

    /**
     * @see PDOSqlDriver::find()
     *
     * @param $condition
     * @param array $page
     * @param int $order
     * @param int $group_by
     * @param string $fields
     * @return array|mixed
     * @throws CoreException
     */
    public function find($condition, array & $page = array(), $order = 1, $group_by = 1, $fields = '*')
    {
        return $this->link->find($this->t, $fields, $condition, $order, $page, $group_by);
    }

    /**
     * @see PDOSqlDriver::add()
     *
     * @param $data
     * @param bool $multi
     * @param array $insert_data
     * @param bool $openTA
     * @return array|bool|mixed
     * @throws CoreException
     */
    public function add($data, $multi = false, & $insert_data = array(), $openTA = false)
    {
        return $this->link->add($this->t, $data, $multi, $insert_data, $openTA);
    }

    /**
     * @see PDOSqlDriver::update()
     *
     * @param $data
     * @param $where
     * @return $this|array|string
     * @throws CoreException
     */
    public function update($data, $where)
    {
        return $this->link->update($this->t, $data, $where);
    }

    /**
     * @see PDOSqlDriver::del()
     *
     * @param $where
     * @param bool $multi
     * @param bool $openTA
     * @return bool|mixed
     * @throws CoreException
     */
    public function del($where, $multi = false, $openTA = false)
    {
        return $this->link->del($this->t, $where, $multi, $openTA);
    }
}
