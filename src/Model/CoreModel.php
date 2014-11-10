<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.5
 */
namespace Cross\Model;

use Cross\Cache\RedisCache;
use Cross\Exception\CoreException;

/**
 * @Auth: wonli <wonli@live.com>
 * Class CoreModel
 * @package Cross\Model
 */
class CoreModel
{
    static function factory($link_type, $link_params)
    {
        switch (strtolower($link_type)) {
            case 'mysql' :

                $port = isset($link_params['port']) ? $link_params['port'] : 3306;
                $char_set = isset($link_params['charset']) ? $link_params['charset'] : 'utf8';
                $options = isset($link_params['options']) ? $link_params['options'] : array();

                if (strcasecmp(PHP_OS, 'linux') == 0 && !empty($link_params['unix_socket'])) {
                    $dsn = "mysql:dbname={$link_params['name']};unix_socket={$link_params['unix_socket']}";
                }
                else {
                    $dsn = "mysql:host={$link_params['host']};dbname={$link_params['name']};port={$port};charset={$char_set}";
                }

                return MysqlModel::getInstance($dsn, $link_params["user"], $link_params["pass"], $options);

            case 'mongo':
                return new MongoModel($link_params);

            case 'redis':
                return new RedisCache($link_params);

            case 'couch':
                return new CouchModel($link_params);

            default:
                throw new CoreException("不支持的数据库扩展!");
        }
    }
}
