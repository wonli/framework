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
 * Class SQLiteConnector
 * @package Cross\DB\Connector
 */
class SQLiteConnector extends BaseConnector
{
    /**
     * @var PDO
     */
    private static $instance;

    /**
     * 默认连接参数
     *
     * @var array
     */
    private static $options = [];

    /**
     * 创建一个SQLite的PDO连接
     *
     * @param string $dsn
     * @param array $options
     * @throws DBConnectException
     */
    private function __construct(string $dsn, array $options)
    {
        try {
            $this->pdo = new PDO($dsn, null, null, parent::getOptions(self::$options, $options));
        } catch (Exception $e) {
            throw new DBConnectException($e->getMessage());
        }
    }

    /**
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param array $options
     * @return SQLiteConnector|PDO
     * @throws DBConnectException
     */
    static function getInstance(string $dsn, string $user, string $password, array $options): self
    {
        $key = md5($dsn);
        if (empty(self::$instance[$key])) {
            self::$instance[$key] = new SQLiteConnector($dsn, $options);
        }

        return self::$instance[$key];
    }

    /**
     * 返回一个PDO连接对象的实例
     *
     * @return PDO
     */
    function getPDO(): PDO
    {
        return $this->pdo;
    }

    /**
     * 获取表的主键名
     *
     * @param string $table
     * @return string
     */
    function getPK(string $table): string
    {
        $info = $this->getMetaData($table, false);
        if (!empty($info)) {
            foreach ($info as $i) {
                if ($i['pk'] == 1) {
                    return $i['name'];
                }
            }
        }

        return '';
    }

    /**
     * 最后插入的id
     */
    function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * 获取表的字段信息
     *
     * @param string $table
     * @param bool $fieldsMap
     * @return mixed
     */
    function getMetaData(string $table, bool $fieldsMap = true): array
    {
        $sql = "PRAGMA table_info('{$table}')";
        try {
            $data = $this->pdo->query($sql);
            if ($fieldsMap) {
                $result = [];
                $data->fetchAll(PDO::FETCH_FUNC, function ($cid, $name, $type, $notnull, $dfltValue, $pk) use (&$result) {
                    $result[$name] = [
                        'primary' => $pk == 1,
                        'auto_increment' => (bool)(($pk == 1) && ($type == 'INTEGER')), //INTEGER && PRIMARY KEY.
                        'default_value' => strval($dfltValue),
                        'not_null' => $notnull == 1
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
