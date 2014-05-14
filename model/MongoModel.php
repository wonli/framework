<?php
/**
 * @Auth: wonli <wonli@live.com>
 * MongoModel.php
 */

class MongoModel
{
    /**
     * @var MongoDB
     */
    public $db;

    /**
     * 创建MongoDB实例
     *
     * @param $link_params
     * @throws CoreException
     */
    function __construct( $link_params )
    {
        if ( ! extension_loaded('mongo') ) {
            throw new CoreException('NOT_SUPPORT : mongo');
        }

        if(class_exists('MongoClient'))
        {
            $m = new MongoClient($link_params['dsn'], $link_params['options']);
            $this->db = $m->$link_params['db'];
        }
        else
        {
            throw new CoreException("please use PCEL MongoDB extends");
        }
    }

}
