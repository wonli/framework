<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\DB\Drivers;

use Cross\Exception\CoreException;
use CouchbaseCluster;
use Exception;

/**
 * @Auth: wonli <wonli@live.com>
 * Class CouchDriver
 * @package Cross\DB\Drivers
 */
class CouchDriver
{
    /**
     * @param array $link_params
     * @throws CoreException
     */
    function __construct(array $link_params)
    {
        if (!class_exists('CouchbaseCluster')) {
            throw new CoreException('Class CouchbaseCluster not found!');
        }

        $bucket = isset($link_params['bucket']) ? $link_params['bucket'] : 'default';
        $bucket_password = isset($link_params['bucket_password']) ? $link_params['bucket_password'] : '';

        try {
            $myCluster = new CouchbaseCluster($link_params['dsn'], $link_params['username'], $link_params['password']);
            $this->link = $myCluster->openBucket($bucket, $bucket_password);
        } catch (Exception $e) {
            throw new CoreException ($e->getMessage());
        }
    }

    /**
     * 调用Couch提供的方法
     *
     * @param $method
     * @param $argv
     * @throws CoreException
     * @return mixed|null
     */
    public function __call($method, $argv)
    {
        $result = null;
        if (method_exists($this->link, $method)) {
            try {
                $result = ($argv == null)
                    ? $this->link->$method()
                    : call_user_func_array(array($this->link, $method), $argv);
            } catch (Exception $e) {
                throw new CoreException($e->getMessage());
            }
        }

        return $result;
    }
}
