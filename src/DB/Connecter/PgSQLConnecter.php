<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.2.0
 */
namespace Cross\DB\Connecter;

use Cross\Exception\CoreException;
use Exception;
use PDO;

/**
 * @Auth: wonli <wonli@live.com>
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
     * 创建PgSQL的PDO连接
     *
     * @param string $dsn dsn
     * @param string $user 数据库用户名
     * @param string $password 数据库密码
     * @param array $options
     * @throws CoreException
     */
    private function __construct($dsn, $user, $password, $options = array())
    {
        try {
            $this->pdo = new PDO($dsn, $user, $password, parent::getOptions($options));
        } catch (Exception $e) {
            throw new CoreException($e->getMessage() . ' line:' . $e->getLine() . ' ' . $e->getFile());
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
    static function getInstance($dsn, $user, $password, $option = array())
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
     * 获取数据表信息
     *
     * @param string $table_name
     * @return array
     * @throws CoreException
     */
    public function getTableInfo($table_name)
    {
        $sql_tpl = "SELECT a.attname, format_type(a.atttypid, a.atttypmod) AS data_type
                    FROM   pg_index i
                    JOIN   pg_attribute a ON a.attrelid = i.indrelid
                                         AND a.attnum = ANY(i.indkey)
                    WHERE  i.indrelid = '%s'::regclass
                    AND    i.indisprimary";

        try {
            $data = $this->pdo->query(sprintf($sql_tpl, $table_name));
            return $data->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 获取表的主键名
     *
     * @param string $table_name
     * @return bool
     */
    public function getPK($table_name)
    {
        $table_info = $this->getTableInfo($table_name);
        foreach ($table_info as $ti) {
            if (!empty($ti['attname'])) {
                return $ti['attname'];
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
}
