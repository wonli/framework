<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\DB\Connecter;

use Cross\Exception\CoreException;
use Exception;
use PDO;

/**
 * @author wonli <wonli@live.com>
 * Class PgSQLConnecter
 * @package Cross\DB\Connecter
 */
class PgSQLConnecter extends BaseConnecter
{
    /**
     * 数据库连接实例
     *
     * @var object
     */
    private static $instance;

    /**
     * 默认连接参数
     *
     * @var array
     */
    private static $options = array();

    /**
     * 创建PgSQL的PDO连接
     *
     * @param string $dsn dsn
     * @param string $user 数据库用户名
     * @param string $password 数据库密码
     * @param array $options
     * @throws CoreException
     */
    private function __construct($dsn, $user, $password, array $options = array())
    {
        try {
            $this->pdo = new PDO($dsn, $user, $password, parent::getOptions(self::$options, $options));
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * @see MysqlModel::__construct
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param array $option
     * @return mixed
     */
    static function getInstance($dsn, $user, $password, array $option = array())
    {
        //同时建立多个连接时候已dsn的md5值为key
        $key = md5($dsn);
        if (!isset(self::$instance[$key])) {
            self::$instance [$key] = new self($dsn, $user, $password, $option);
        }

        return self::$instance [$key];
    }

    /**
     * 返回PDO连接的实例
     *
     * @return PDO
     */
    public function getPDO()
    {
        return $this->pdo;
    }

    /**
     * 获取表的主键名
     *
     * @param string $table_name
     * @return bool
     */
    public function getPK($table_name)
    {
        $table_info = $this->getMetaData($table_name, false);
        foreach ($table_info as $info) {
            if ($info['contype'] == 'p') {
                return $info['column_name'];
            }
        }
        return false;
    }

    /**
     * 获取最后插入时的ID
     *
     * @return mixed
     */
    public function lastInsertId()
    {
        $sql = "SELECT LASTVAL() as insert_id";
        try {
            $data = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);;
            return $data['insert_id'];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 获取表的字段信息
     *
     * @param string $table
     * @param bool $fields_map
     * @return array
     */
    function getMetaData($table, $fields_map = true)
    {
        $sql = "select a.column_name, a.is_nullable, a.column_default, p.contype from (
                    select i.column_name, i.is_nullable, i.column_default, i.ordinal_position, c.oid
                    from information_schema.columns i left join pg_class c on c.relname=i.table_name
                    where i.table_name='{$table}'
                ) a left join pg_constraint p on p.conrelid=a.oid and a.ordinal_position = ANY (p.conkey)";

        try {
            $data = $this->pdo->query($sql);
            if ($fields_map) {
                $result = array();
                $data->fetchAll(PDO::FETCH_FUNC, function ($column_name, $is_null, $column_default, $con_type) use (&$result) {
                    $auto_increment = preg_match("/nextval\((.*)\)/", $column_default);
                    $result[$column_name] = array(
                        'primary' => $con_type == 'p',
                        'auto_increment' => $auto_increment,
                        'default_value' => $auto_increment ? '' : strval($column_default),
                        'not_null' => $is_null == 'NO',
                    );
                });
                return $result;
            } else {
                return $data->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            return array();
        }
    }
}
