<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\DB\Drivers;

use Cross\Exception\CoreException;
use MongoClient;
use Exception;

/**
 * @Auth: wonli <wonli@live.com>
 * Class MongoDriver
 * @package Cross\DB\Drivers
 */
class MongoDriver
{
    /**
     * @var MongoClient
     */
    public $db;

    /**
     * 创建MongoDB实例
     *
     * @param $link_params
     * @throws CoreException
     */
    function __construct(array $link_params)
    {
        if (!class_exists('MongoClient')) {
            throw new CoreException('Class MongoClient not found!');
        }

        try {
            $mongoClient = new MongoClient($link_params['dsn'], $link_params['options']);
            $this->db = $mongoClient->$link_params['db'];
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
    }

}
