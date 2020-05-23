<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\DB;

use Cross\DB\SQLAssembler\MySQLAssembler;
use Cross\DB\SQLAssembler\PgSQLAssembler;
use Cross\DB\SQLAssembler\SQLiteAssembler;
use Cross\DB\Connecter\MySQLConnecter;
use Cross\DB\Connecter\PgSQLConnecter;
use Cross\DB\Connecter\SQLiteConnecter;
use Cross\Cache\Driver\MemcacheDriver;
use Cross\Cache\Driver\RedisDriver;
use Cross\DB\Drivers\PDOSqlDriver;
use Cross\DB\Drivers\CouchDriver;
use Cross\DB\Drivers\MongoDriver;
use Cross\Exception\CoreException;
use Closure;
use Cross\Exception\DBConnectException;

/**
 * @author wonli <wonli@live.com>
 * Class DBFactory
 * @package Cross\DB
 */
class DBFactory
{
    /**
     * 为module中的link生成对象的实例,在配置文件中支持匿名函数
     *
     * @param string $link
     * @param array|Closure $params
     * @param array $config
     * @return RedisDriver|CouchDriver|MongoDriver|PDOSqlDriver|mixed
     * @throws CoreException
     * @throws DBConnectException
     */
    static function make(string $link, $params, array $config = array()): object
    {
        //如果params是一个匿名函数, 则调用匿名函数创建数据库连接
        if ($params instanceof Closure) {
            return call_user_func_array($params, $config);
        }

        //配置的数据表前缀
        $prefix = !empty($params['prefix']) ? $params['prefix'] : '';
        $options = isset($params['options']) ? $params['options'] : array();
        switch (strtolower($link)) {
            case 'mysql' :
                return new PDOSqlDriver(
                    MySQLConnecter::getInstance(self::getDsn($params, 'mysql'), $params['user'], $params['pass'], $options),
                    new MySQLAssembler($prefix)
                );

            case 'sqlite':
                return new PDOSqlDriver(SQLiteConnecter::getInstance($params['dsn'], null, null, $options), new SQLiteAssembler($prefix));

            case 'pgsql':
                return new PDOSqlDriver(
                    PgSqlConnecter::getInstance(self::getDsn($params, 'pgsql'), $params['user'], $params['pass'], $options),
                    new PgSQLAssembler($prefix)
                );

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
     * @param bool|true $use_unix_socket
     * @return string
     * @throws CoreException
     */
    private static function getDsn(array $params, string $type = 'mysql', bool $use_unix_socket = true): string
    {
        if (!empty($params['dsn'])) {
            return $params['dsn'];
        }

        if (!isset($params['host']) || !isset($params['name'])) {
            throw new CoreException('连接数据库所需参数不足');
        }

        if ($use_unix_socket && !empty($params['unix_socket'])) {
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

}
