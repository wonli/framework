<?php
/**
 * Cross - a micro PHP framework
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
    public Manager $manager;

    /**
     * 创建MongoDB实例
     *
     * @param array $params
     * @throws CoreException
     */
    function __construct(array $params)
    {
        if (!class_exists('MongoDB\Driver\Manager')) {
            throw new CoreException('MongoDB\Driver\Manager not found!');
        }

        try {
            $options = empty($params['options']) ? [] : $params['options'];
            $driverOptions = empty($params['driverOptions']) ? [] : $params['driverOptions'];

            $this->manager = new Manager($params['dsn'], $options, $driverOptions);
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
    }
}
