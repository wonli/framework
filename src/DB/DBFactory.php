<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\DB;

use Cross\Cache\RedisCache;
use Cross\DB\Connecter\MySQLConnecter;
use Cross\DB\Connecter\PgSQLConnecter;
use Cross\DB\Connecter\SQLiteConnecter;
use Cross\DB\Drivers\CouchDriver;
use Cross\DB\Drivers\MongoDriver;
use Cross\DB\Drivers\PDOSqlDriver;
use Cross\DB\SQLAssembler\MySQLAssembler;
use Cross\DB\SQLAssembler\PgSQLAssembler;
use Cross\DB\SQLAssembler\SQLiteAssembler;
use Cross\Exception\CoreException;
use Closure;

/**
 * @Auth: wonli <wonli@live.com>
 * Class DBFactory
 * @package Cross\DB
 */
class DBFactory
{
    /**
     * 为module中的link生成对象的实例,在配置文件中支持匿名函数
     *
     * @param $link
     * @param $params
     * @param $config
     * @return RedisCache|CouchDriver|MongoDriver|PDOSqlDriver|mixed
     * @throws CoreException
     */
    static function make($link, $params, $config)
    {
        //如果params是一个匿名函数
        //匿名函数的第一个参数为当前app配置, 执行匿名函数并返回
        if ($params instanceof Closure) {
            return call_user_func_array($params, array($config));
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
                return new PDOSqlDriver(SQLiteConnecter::getInstance($params['dsn']), new SQLiteAssembler($prefix));

            case 'pgsql':
                return new PDOSqlDriver(
                    PgSqlConnecter::getInstance(self::getDsn($params, 'pgsql'), $params['user'], $params['pass'], $options),
                    new PgSQLAssembler($prefix)
                );

            case 'mongo':
                return new MongoDriver($params);

            case 'redis':
                return new RedisCache($params);

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
     * @param bool|false $use_unix_socket
     * @return string
     * @throws CoreException
     */
    private static function getDsn($params, $type = 'mysql', $use_unix_socket = true)
    {
        if (!empty($params['dsn'])) {
            return $params['dsn'];
        }

        if (!isset($params['host']) || !isset($params['name'])) {
            throw new CoreException('连接数据库所需参数不足');
        }

        $port = isset($params['port']) ? $params['port'] : 3306;
        $char_set = isset($params['charset']) ? $params['charset'] : 'utf8';

        if ($use_unix_socket && strcasecmp(PHP_OS, 'linux') == 0 && !empty($params['unix_socket'])) {
            $dsn = "{$type}:dbname={$params['name']};unix_socket={$params['unix_socket']}";
        } else {
            $dsn = "{$type}:host={$params['host']};port={$port};dbname={$params['name']};charset={$char_set}";
        }

        return $dsn;
    }

}
