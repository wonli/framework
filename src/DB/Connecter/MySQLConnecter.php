<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\DB\Connecter;

use Cross\Exception\DBConnectException;
use Exception;
use PDO;

/**
 * @author wonli <wonli@live.com>
 * Class MySQLConnecter
 * @package Cross\DB\Connecter
 */
class MySQLConnecter extends BaseConnecter
{
    /**
     * 数据库连接实例
     *
     * @var object
     */
    private static $instance;

    /**
     * 默认连接参数
     * <ul>
     *  <li>PDO::ATTR_PERSISTENT => false //禁用长连接</li>
     *  <li>PDO::ATTR_EMULATE_PREPARES => false //禁用模拟预处理</li>
     *  <li>PDO::ATTR_STRINGIFY_FETCHES => false //禁止数值转换成字符串</li>
     *  <li>PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true //使用缓冲查询</li>
     *  <li>PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION //发生错误时抛出异常 </li>
     *  <li>PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8" </li>
     * </ul>
     *
     * @var array
     */
    private static $options = array(
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
    );

    /**
     * 创建Mysql的PDO连接
     *
     * @param string $dsn dsn
     * @param string $user 数据库用户名
     * @param string $password 数据库密码
     * @param array $options
     * @throws DBConnectException
     */
    private function __construct($dsn, $user, $password, array $options = array())
    {
        try {
            $this->pdo = new PDO($dsn, $user, $password, parent::getOptions(self::$options, $options));
        } catch (Exception $e) {
            throw new DBConnectException($e->getMessage());
        }
    }

    /**
     * @see MysqlModel::__construct
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param array $option
     * @return mixed
     * @throws DBConnectException
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
     * @param string $table
     * @return bool
     */
    public function getPK($table)
    {
        $table_info = $this->getMetaData($table, false);
        foreach ($table_info as $ti) {
            if ($ti['Extra'] == 'auto_increment') {
                return $ti['Field'];
            }
        }

        return false;
    }

    /**
     * 最后插入时的id
     *
     * @return string
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
        $data = $this->pdo->query("DESCRIBE {$table}");
        try {
            if ($fields_map) {
                $result = array();
                $data->fetchAll(PDO::FETCH_FUNC, function ($field, $type, $null, $key, $default, $extra) use (&$result) {
                    $result[$field] = array(
                        'primary' => $key == 'PRI',
                        'auto_increment' => $extra == 'auto_increment',
                        'default_value' => strval($default),
                        'not_null' => $null == 'NO',
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
