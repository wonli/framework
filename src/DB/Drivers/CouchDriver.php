<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\DB\Drivers;

use Cross\Exception\CoreException;

use Couchbase\PasswordAuthenticator;
use Couchbase\Bucket;
use CouchbaseCluster;

use Exception;

/**
 * @author wonli <wonli@live.com>
 * Class CouchDriver
 * @package Cross\DB\Drivers
 */
class CouchDriver
{
    /**
     * @var Bucket
     */
    protected Bucket $link;

    /**
     * @param array $params
     * @throws CoreException
     */
    function __construct(array $params)
    {
        if (!class_exists('CouchbaseCluster')) {
            throw new CoreException('Class CouchbaseCluster not found!');
        }

        try {
            $authenticator = new PasswordAuthenticator();
            $authenticator->username($params['username'])->password($params['password']);

            $cluster = new CouchbaseCluster($params['dsn']);
            $cluster->authenticate($authenticator);

            $bucket = isset($params['bucket']) ? $params['bucket'] : 'default';
            if (!empty($params['bucket_password'])) {
                $this->link = $cluster->openBucket($bucket, $params['bucket_password']);
            } else {
                $this->link = $cluster->openBucket($bucket);
            }
        } catch (Exception $e) {
            throw new CoreException ($e->getMessage());
        }
    }

    /**
     * 调用Couch提供的方法
     *
     * @param $method
     * @param $argv
     * @return mixed|null
     * @throws CoreException
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
