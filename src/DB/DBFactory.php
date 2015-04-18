<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.3
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
     * @param $link
     * @param $params
     * @param $config
     * @return RedisCache|CouchDriver|MongoDriver|PDOSqlDriver|mixed
     * @throws CoreException
     */
    static function make($link, $params, $config)
    {
        if ($params instanceof Closure) {
            return call_user_func_array($params, array($config));
        } else {
            switch (strtolower($link)) {
                case 'mysql' :
                    $port = isset($params['port']) ? $params['port'] : 3306;
                    $char_set = isset($params['charset']) ? $params['charset'] : 'utf8';
                    $options = isset($params['options']) ? $params['options'] : array();

                    if (strcasecmp(PHP_OS, 'linux') == 0 && !empty($params['unix_socket'])) {
                        $dsn = "mysql:dbname={$params['name']};unix_socket={$params['unix_socket']}";
                    } else {
                        $dsn = "mysql:host={$params['host']};dbname={$params['name']};port={$port};charset={$char_set}";
                    }

                    $connecter = MySQLConnecter::getInstance($dsn, $params['user'], $params['pass'], $options);
                    return new PDOSqlDriver($connecter, new MySQLAssembler());

                case 'sqlite':
                    $connecter = SQLiteConnecter::getInstance($params['dsn']);
                    return new PDOSqlDriver($connecter, new SQLiteAssembler());

                case 'pgsql':
                    $port = isset($params['port']) ? $params['port'] : 5432;
                    $char_set = isset($params['charset']) ? $params['charset'] : 'utf8';
                    $options = isset($params['options']) ? $params['options'] : array();
                    if (! isset($params['dsn'])) {
                        $dsn = "mysql:host={$params['host']};dbname={$params['name']};port={$port};charset={$char_set}";
                    } else {
                        $dsn = $params['dsn'];
                    }

                    $connecter = PgSqlConnecter::getInstance($dsn, $params['user'], $params['pass'], $options);
                    return new PDOSqlDriver($connecter, new PgSQLAssembler());

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
    }
}
