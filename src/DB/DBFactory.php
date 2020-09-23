<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\DB;

use Closure;

use Cross\Exception\DBConnectException;
use Cross\Exception\CoreException;

use Cross\Cache\Driver\MemcacheDriver;
use Cross\Cache\Driver\RedisDriver;

use Cross\DB\SQLAssembler\MySQLAssembler;
use Cross\DB\SQLAssembler\SQLiteAssembler;
use Cross\DB\SQLAssembler\OracleAssembler;
use Cross\DB\SQLAssembler\PgSQLAssembler;

use Cross\DB\Connector\MySQLConnector;
use Cross\DB\Connector\OracleConnector;
use Cross\DB\Connector\SQLiteConnector;
use Cross\DB\Connector\PgSQLConnector;

use Cross\DB\Drivers\PDOOracleDriver;
use Cross\DB\Drivers\PDOSqlDriver;
use Cross\DB\Drivers\CouchDriver;
use Cross\DB\Drivers\MongoDriver;

/**
 * @author wonli <wonli@live.com>
 * Class DBFactory
 * @package Cross\DB
 */
class DBFactory
{
    /**
     * 生成model实例
     *
     * @param string $type
     * @param array|Closure $params
     * @param array $config
     * @return RedisDriver|CouchDriver|MongoDriver|PDOSqlDriver|mixed
     * @throws CoreException
     * @throws DBConnectException
     */
    static function make(string $type, &$params, array $config = []): object
    {
        //如果params是一个匿名函数, 则调用匿名函数创建数据库连接
        if ($params instanceof Closure) {
            return call_user_func_array($params, $config);
        }

        //配置的数据表前缀
        $prefix = $params['prefix'] ?? '';
        $options = $params['options'] ?? [];
        switch (strtolower($type)) {
            case 'mysql' :
                $Connector = MySQLConnector::getInstance(self::getDsn($params, 'mysql'), $params['user'], $params['pass'], $options);
                return new PDOSqlDriver($Connector, new MySQLAssembler($prefix), $params);

            case 'sqlite':
                $Connector = SQLiteConnector::getInstance($params['dsn'], null, null, $options);
                return new PDOSqlDriver($Connector, new SQLiteAssembler($prefix), $params);

            case 'pgsql':
                $Connector = PgSQLConnector::getInstance(self::getDsn($params, 'pgsql'), $params['user'], $params['pass'], $options);
                return new PDOSqlDriver($Connector, new PgSQLAssembler($prefix), $params);

            case 'oracle':
                $Connector = OracleConnector::getInstance(self::getOracleTns($params), $params['user'], $params['pass'], $options);
                return new PDOOracleDriver($Connector, new OracleAssembler($prefix), $params);

            case 'mongo':
                return new MongoDriver($params);

            case 'redis':
                return new RedisDriver($params);

            case 'memcache':
                return new MemcacheDriver($params);

            case 'couch':
                return new CouchDriver($params);

            default:
                throw new CoreException('不支持的数据库扩展!');
        }
    }

    /**
     * 生成DSN
     *
     * @param array $params
     * @param string $type
     * @param bool|true $useUnixSocket
     * @return string
     * @throws CoreException
     */
    private static function getDsn(array &$params, string $type = 'mysql', bool $useUnixSocket = true): string
    {
        if (!empty($params['dsn'])) {
            return $params['dsn'];
        }

        if (!isset($params['host']) || !isset($params['name'])) {
            throw new CoreException('连接数据库所需参数不足');
        }

        if ($useUnixSocket && !empty($params['unix_socket'])) {
            $dsn = "{$type}:unix_socket={$params['unix_socket']};dbname={$params['name']};";
        } else {
            $dsn = "{$type}:host={$params['host']};dbname={$params['name']};";
            if (isset($params['port'])) {
                $dsn .= "port={$params['port']};";
            }

            if (isset($params['charset'])) {
                $dsn .= "charset={$params['charset']};";
            }
        }

        return $dsn;
    }

    /**
     * 生成tns
     *
     * @param array $params
     * @return string
     * @throws CoreException
     */
    private static function getOracleTns(array &$params)
    {
        if (!empty($params['tns'])) {
            return $params['tns'];
        }

        $params['port'] = $params['port'] ?? 1521;
        $params['charset'] = $params['charset'] ?? 'utf8';
        $params['protocol'] = $params['protocol'] ?? 'tcp';
        if (!isset($params['host']) || !isset($params['name'])) {
            throw new CoreException('请指定服务器地址和名称');
        }

        $tns = "(DESCRIPTION = 
            (ADDRESS_LIST = (ADDRESS = (PROTOCOL = %s)(HOST = %s)(PORT = %s)))
            (CONNECT_DATA = (SERVICE_NAME = %s)))";

        $db = sprintf($tns, $params['protocol'], $params['host'], $params['port'], $params['name']);
        return "oci:dbname={$db};charset=" . $params['charset'];
    }
}
