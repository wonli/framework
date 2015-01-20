<?php
/**
 * @Auth: wonli <wonli@live.com>
 * MysqlModule.php
 */
namespace Cross\Module;

use Cross\Exception\CoreException;
use Cross\MVC\Module;

/**
 * mysql模块,提供简单的CRUD功能
 *
 * @Auth: wonli <wonli@live.com>
 * Class MysqlModule
 * @package Cross\Module
 */
class MysqlModule extends Module
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
     * 实例化当前类的子类
     *
     * @param array $args
     * @return static::get_called_class()
     * @throws CoreException
     */
    static function init($args = array())
    {
        $called_class_name = get_called_class();
        if (empty(self::$instance[$called_class_name])) {
            $obj = new \ReflectionClass($called_class_name);
            if($obj->hasProperty('t')) {
                self::$instance[$called_class_name] = $obj->newInstance($args);
            } else {
                throw new CoreException('请定义一个成员属性t指定默认表名');
            }
        }

        return self::$instance[$called_class_name];
    }

    /**
     * 设置表名
     *
     * @param $table_name
     * @return static::get_called_class()
     */
    function setTable($table_name)
    {
        $this->t = $table_name;
        return $this;
    }

    /**
     * @see Cross\DB\Drivers\MysqlDriver::get()
     *
     * @param $condition
     * @param string $fields
     * @return mixed
     */
    function get($condition, $fields='*')
    {
        return $this->link->get($this->t, $fields, $condition);
    }

    /**
     * @see Cross\DB\Drivers\MysqlDriver::getAll()
     *
     * @param null $where
     * @param int $order
     * @param int $group_by
     * @param string $fields
     * @return array
     */
    function getAll($where = null, $order = 1, $group_by = 1, $fields = '*')
    {
        return $this->link->getAll($this->t, $fields, $where, $order, $group_by);
    }

    /**
     * @see Cross\DB\Drivers\MysqlDriver::find()
     *
     * @param $condition
     * @param array $page
     * @param int $order
     * @param int $group_by
     * @param string $fields
     * @return array|mixed
     */
    function find($condition, & $page = array(), $order=1, $group_by = 1, $fields='*')
    {
        return $this->link->find($this->t, $fields, $condition, $order, $page, $group_by);
    }

    /**
     * @see Cross\DB\Drivers\MysqlDriver::add()
     *
     * @param $data
     * @param bool $multi
     * @param array $insert_data
     * @return array|bool|mixed
     */
    public function add($data, $multi = false, & $insert_data = array())
    {
        return $this->link->add($this->t, $data, $multi, $insert_data);
    }

    /**
     * @see Cross\DB\Drivers\MysqlDriver::update()
     *
     * @param $data
     * @param $where
     * @return $this|array|string
     */
    public function update($data, $where)
    {
        return $this->link->update($this->t, $data, $where);
    }

    /**
     * @see Cross\DB\Drivers\MysqlDriver::del()
     *
     * @param $where
     * @param bool $multi
     * @return bool|mixed
     */
    function del($where, $multi = false)
    {
        return $this->link->del($this->t, $where, $multi);
    }
}
