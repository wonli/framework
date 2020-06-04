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
 * Class OracleConnecter
 * @package Cross\DB\Connecter
 */
class OracleConnecter extends BaseConnecter
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
    private static $options = [
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'
    ];

    /**
     * 创建Mysql的PDO连接
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
     * 单例模式连接数据库
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param array $option
     * @return mixed
     * @throws DBConnectException
     */
    static function getInstance($dsn, $user, $password, array $option = []): self
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
     * @param string $table
     * @return string
     */
    public function getPK(string $table): string
    {
        $table = strtoupper($table);
        $q = $this->pdo->query("select cu.* from all_cons_columns cu, all_constraints au 
                where cu.constraint_name = au.constraint_name 
                and au.constraint_type = 'P' 
                and au.table_name = '{$table}'");

        $pk = '';
        $tablePKList = $q->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($tablePKList)) {
            array_walk($tablePKList, function ($d) use (&$pk) {
                //当存在复合主键时，取第一个
                if ($d['POSITION'] == 1) {
                    $pk = $d['COLUMN_NAME'];
                    return;
                }
            });
        }

        return $pk;
    }

    /**
     * 最后插入时的id
     *
     * @return string
     */
    function lastInsertId()
    {
        if (!empty($this->sequence)) {
            $sh = $this->pdo->query("SELECT {$this->sequence}.CURRVAL AS LID FROM DUAL");
            $saveInfo = $sh->fetch(PDO::FETCH_ASSOC);
            if (!empty($saveInfo)) {
                return $saveInfo['LID'];
            }
        }

        return '';
    }

    /**
     * 获取表的字段信息
     *
     * @param string $table
     * @param bool $fields_map
     * @return mixed
     */
    function getMetaData(string $table, bool $fields_map = true): array
    {
        //获取所有字段
        $table = strtoupper($table);
        $q = $this->pdo->query("select 
            t.COLUMN_NAME, t.DATA_TYPE, t.NULLABLE, t.DATA_DEFAULT, c.COMMENTS 
            from all_tab_columns t,all_col_comments c 
            where t.table_name = c.table_name 
            and t.column_name = c.column_name 
            and t.table_name = '{$table}'");

        $table_fields = $q->fetchAll(PDO::FETCH_ASSOC);
        if (empty($table_fields)) {
            return [];
        }

        $pk = $this->getPK($table);
        $qIndex = $this->pdo->query("select 
            t.column_name,t.index_name,i.index_type 
            from all_ind_columns t,all_indexes i 
            where t.index_name = i.index_name 
            and t.table_name = i.table_name and t.table_name = '{$table}'");

        $indexInfo = [];
        $qIndex->fetchAll(PDO::FETCH_FUNC, function ($name, $indexName, $indexType) use (&$indexInfo, $pk) {
            $indexInfo[$name] = [
                'pk' => $pk == $name,
                'index' => $indexName,
                'type' => $indexType
            ];
        });

        $result = [];
        array_map(function ($d) use ($indexInfo, &$result, $fields_map) {
            $data = [
                'primary' => $indexInfo[$d['COLUMN_NAME']]['pk'] ?? false,
                'is_index' => isset($indexInfo[$d['COLUMN_NAME']]),
                'auto_increment' => false,
                'default_value' => $d['DATA_DEFAULT'],
                'not_null' => $d['NULLABLE'] == 'N',
                'comment' => $d['COMMENTS']
            ];

            if ($fields_map) {
                $result[$d['COLUMN_NAME']] = $data;
            } else {
                $data['field'] = $d['COLUMN_NAME'];
                $result[] = $data;
            }
        }, $table_fields);

        return $result;
    }
}
