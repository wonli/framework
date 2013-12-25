<?php
/**
 * @Author: wonli <wonli@live.com>
 */
class CoreModel
{
    static function factory($link_type, $link_params)
    {
        switch( strtolower($link_type) )
        {
            case 'mysql' :

                $host = $link_params['host'];
                $name = $link_params['name'];
                $port = isset($link_params['port'])?$link_params['port']:3306;
                $char_set = isset($link_params['charset'])?$link_params['charset']:'utf8';

                $dsn = "mysql:host={$host};dbname={$name};port={$port};charset={$char_set}";
                return MysqlModel::getInstance($dsn, $link_params["user"], $link_params["pass"]);

            case 'mongo':
                return new MongoModel( $link_params );

            case 'redis':
                return new RedisCache( $link_params );

            case 'couch':
                return new CouchModel( $link_params );

            default:
                throw new CoreException("不支持的数据库扩展!");
        }
    }
}
