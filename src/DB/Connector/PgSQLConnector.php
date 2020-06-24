<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\DB\Connector;

use Cross\Exception\DBConnectException;
use Exception;
use PDO;

/**
 * @author wonli <wonli@live.com>
 * Class PgSQLConnector
 * @package Cross\DB\Connector
 */
class PgSQLConnector extends BaseConnector
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
    private static $options = [];

    /**
     * 创建PgSQL的PDO连接
     *
     * @param string $dsn dsn
     * @param string $user 数据库用户名
     * @param string $password 数据库密码
     * @param array $options
     * @throws DBConnectException
     */
    private function __construct(string $dsn, string $user, $password, array $options = [])
    {
        try {
            $this->pdo = new PDO($dsn, $user, $password, parent::getOptions(self::$options, $options));
        } catch (Exception $e) {
            throw new DBConnectException($e->getMessage());
        }
    }

    /**
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param array $option
     * @return mixed
     * @throws DBConnectException
     * @see MysqlModel::__construct
     */
    static function getInstance(string $dsn, string $user, $password, array $option = []): self
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
    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    /**
     * 获取表的主键名
     *
     * @param string $table_name
     * @return string
     */
    public function getPK(string $table_name): string
    {
        $table_info = $this->getMetaData($table_name, false);
        foreach ($table_info as $info) {
            if ($info['contype'] == 'p') {
                return $info['column_name'];
            }
        }
        return '';
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
            $data = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
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
    function getMetaData(string $table, bool $fields_map = true): array
    {
        $sql = "select a.column_name, a.is_nullable, a.column_default, p.contype from (
                    select i.column_name, i.is_nullable, i.column_default, i.ordinal_position, c.oid
                    from information_schema.columns i left join pg_class c on c.relname=i.table_name
                    where i.table_name='{$table}'
                ) a left join pg_constraint p on p.conrelid=a.oid and a.ordinal_position = ANY (p.conkey)";

        try {
            $data = $this->pdo->query($sql);
            if ($fields_map) {
                $result = [];
                $data->fetchAll(PDO::FETCH_FUNC, function ($column_name, $is_null, $column_default, $con_type) use (&$result) {
                    $auto_increment = preg_match("/nextval\((.*)\)/", $column_default);
                    $result[$column_name] = [
                        'primary' => $con_type == 'p',
                        'auto_increment' => $auto_increment,
                        'default_value' => $auto_increment ? '' : strval($column_default),
                        'not_null' => $is_null == 'NO',
                    ];
                });
                return $result;
            } else {
                return $data->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            return [];
        }
    }
}
