<?php defined('CROSSPHP_PATH')or die('Access Denied');
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
                return MysqlModel::getInstance($link_params["dsn"], $link_params["user"], $link_params["pass"]);

            case 'mongodb':
                return true;

            case 'redis':
                return new RedisCache($link_params);

            default:
                throw new CoreException("不支持的数据库类型!");
        }
    }

}
