<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.1
 */
namespace Cross\DB;

use Cross\Cache\RedisCache;
use Cross\DB\Drivers\CouchDriver;
use Cross\DB\Drivers\MongoDriver;
use Cross\DB\Drivers\MysqlDriver;
use Cross\Exception\CoreException;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Factory
 * @package Cross\Model
 */
class DBFactory
{
    static function make($link_type, $link_params)
    {
        switch (strtolower($link_type)) {
            case 'mysql' :
                $port = isset($link_params['port']) ? $link_params['port'] : 3306;
                $char_set = isset($link_params['charset']) ? $link_params['charset'] : 'utf8';
                $options = isset($link_params['options']) ? $link_params['options'] : array();

                if (strcasecmp(PHP_OS, 'linux') == 0 && !empty($link_params['unix_socket'])) {
                    $dsn = "mysql:dbname={$link_params['name']};unix_socket={$link_params['unix_socket']}";
                } else {
                    $dsn = "mysql:host={$link_params['host']};dbname={$link_params['name']};port={$port};charset={$char_set}";
                }
                return MysqlDriver::getInstance($dsn, $link_params['user'], $link_params['pass'], $options);

            case 'mongo':
                return new MongoDriver($link_params);

            case 'redis':
                return new RedisCache($link_params);

            case 'couch':
                return new CouchDriver($link_params);

            default:
                throw new CoreException('不支持的数据库扩展!');
        }
    }
}
