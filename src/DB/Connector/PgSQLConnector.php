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
    private function __construct(string $dsn, string $user, string $password, array $options = [])
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
     * @param array $options
     * @return mixed
     * @throws DBConnectException
     * @see MysqlModel::__construct
     */
    static function getInstance(string $dsn, string $user, string $password, array $options): self
    {
        //同时建立多个连接时候已dsn的md5值为key
        $key = md5($dsn);
        if (!isset(self::$instance[$key])) {
            self::$instance [$key] = new self($dsn, $user, $password, $options);
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
     * @param string $tableName
     * @return string
     */
    public function getPK(string $tableName): string
    {
        $tableInfo = $this->getMetaData($tableName, false);
        foreach ($tableInfo as $info) {
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
     * @param bool $fieldsMap
     * @return array
     */
    function getMetaData(string $table, bool $fieldsMap = true): array
    {
        $sql = "select a.column_name, a.is_nullable, a.column_default, p.contype from (
                    select i.column_name, i.is_nullable, i.column_default, i.ordinal_position, c.oid
                    from information_schema.columns i left join pg_class c on c.relname=i.table_name
                    where i.table_name='{$table}'
                ) a left join pg_constraint p on p.conrelid=a.oid and a.ordinal_position = ANY (p.conkey)";

        try {
            $data = $this->pdo->query($sql);
            if ($fieldsMap) {
                $result = [];
                $data->fetchAll(PDO::FETCH_FUNC, function ($columnName, $isNull, $columnDefault, $conType) use (&$result) {
                    $autoIncrement = preg_match("/nextval\((.*)\)/", $columnDefault);
                    $result[$columnName] = [
                        'primary' => $conType == 'p',
                        'auto_increment' => $autoIncrement,
                        'default_value' => $autoIncrement ? '' : strval($columnDefault),
                        'not_null' => $isNull == 'NO',
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
