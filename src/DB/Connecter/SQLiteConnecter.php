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
 * @Auth: wonli <wonli@live.com>
 * Class SQLiteConnecter
 * @package Cross\DB\Connecter
 */
class SQLiteConnecter extends BaseConnecter
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
    private static $options = array();

    /**
     * 创建一个SQLite的PDO连接
     *
     * @param string $dsn
     * @param array $options
     * @throws CoreException
     */
    private function __construct($dsn, array $options)
    {
        try {
            $this->pdo = new PDO($dsn, null, null, parent::getOptions(self::$options, $options));
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * @param string $dsn
     * @param null $user
     * @param null $pwd
     * @param array $options
     * @return SQLiteConnecter|PDO
     */
    static function getInstance($dsn, $user = null, $pwd = null, array $options = array())
    {
        $key = md5($dsn);
        if (empty(self::$instance[$key])) {
            self::$instance[$key] = new SqliteConnecter($dsn, $options);
        }

        return self::$instance[$key];
    }

    /**
     * 返回一个PDO连接对象的实例
     *
     * @return mixed
     */
    function getPDO()
    {
        return $this->pdo;
    }

    /**
     * 获取表的主键名
     *
     * @param string $table
     * @return mixed|void
     */
    function getPK($table)
    {
        $info = $this->getMetaData($table, false);
        if (!empty($info)) {
            foreach ($info as $i) {
                if ($i['pk'] == 1) {
                    return $i['name'];
                }
            }
        }

        return false;
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
     * @param bool $fields_map
     * @return mixed
     */
    function getMetaData($table, $fields_map = true)
    {
        $sql = "PRAGMA table_info('{$table}')";
        try {
            $data = $this->pdo->query($sql);
            if ($fields_map) {
                $result = array();
                $data->fetchAll(PDO::FETCH_FUNC, function ($cid, $name, $type, $notnull, $dflt_value, $pk) use (&$result) {
                    $result[$name] = array(
                        'primary' => $pk == 1,
                        'auto_increment' => (bool)(($pk == 1) && ($type == 'INTEGER')), //INTEGER && PRIMARY KEY.
                        'default_value' => strval($dflt_value),
                        'not_null' => $notnull == 1
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
