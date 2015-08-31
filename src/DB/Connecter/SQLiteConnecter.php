<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.4.0
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
     * 创建一个SQLite的PDO连接
     *
     * @param string $dsn
     * @param array $options
     * @throws CoreException
     */
    private function __construct($dsn, $options)
    {
        try {
            $this->pdo = new PDO($dsn, null, null, parent::getOptions($options));
        } catch (Exception $e) {
            throw new CoreException($e->getMessage() . ' line:' . $e->getLine() . ' ' . $e->getFile());
        }
    }

    /**
     * @param string $dsn
     * @param string $user
     * @param string $pwd
     * @param array $options
     * @return SqliteConnecter|PDO
     */
    static function getInstance($dsn, $user = '', $pwd = '', $options = array())
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
     * @param string $table_name
     * @return mixed|void
     */
    function getPK($table_name)
    {
        $sql = sprintf('PRAGMA table_info(%s)', $table_name);
        $info = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($info)) {
            foreach ($info as $i) {
                if ($i['pk'] == 1) return $i['name'];
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
}
