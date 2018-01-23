<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\DB\Drivers;

use Cross\Exception\CoreException;
use MongoDB\Driver\Manager;
use Exception;

/**
 * @author wonli <wonli@live.com>
 * Class MongoDriver
 * @package Cross\DB\Drivers
 */
class MongoDriver
{
    /**
     * @var Manager
     */
    public $manager;

    /**
     * 创建MongoDB实例
     *
     * @param $params
     * @throws CoreException
     */
    function __construct(array $params)
    {
        if (!class_exists('MongoDB\Driver\Manager')) {
            throw new CoreException('MongoDB\Driver\Manager not found!');
        }

        try {
            $options = empty($params['options']) ? array() : $params['options'];
            $driverOptions = empty($params['driverOptions']) ? array() : $params['driverOptions'];

            $this->manager = new Manager($params['dsn'], $options, $driverOptions);
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
    }
}
