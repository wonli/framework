<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.3
 */
namespace Cross\Lib\Other;

use Cross\Exception\CoreException;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Crumb
 * @package Cross\Lib\Other
 */
class Crumb
{
    /**
     * 过期时间
     *
     * @var int
     */
    private $ttl = 1800;

    /**
     * @var string
     */
    private $key = '!@c#r$!o>s<s&*';

    /**
     * @var string
     */
    private $algorithm = 'crc32';

    /**
     * 设置过期时间
     *
     * @param $ttl
     * @return $this
     */
    function setTTL($ttl)
    {
        $this->ttl = $ttl;
        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * 设置加密算法
     *
     * @param string $algorithm
     * @return $this
     * @throws \Cross\Exception\CoreException
     */
    function setAlgorithm($algorithm)
    {
        if(! in_array($algorithm, hash_algos())) {
            throw new CoreException("不支持的加密算法");
        }

        $this->algorithm = $algorithm;
        return $this;
    }

    /**
     * 生成加密数据
     *
     * @param $data
     * @return string
     */
    public function encrypt($data)
    {
        return hash_hmac($this->algorithm, $data, $this->key);
    }

    /**
     * 生成一个字符串用于校验encrypt的值
     *
     * @param $key
     * @param int $action
     * @return string
     */
    public function make($key, $action = -1)
    {
        $i = ceil(time() / $this->ttl);
        return substr($this->encrypt($i . $action . $key), -12, 10);
    }

    /**
     * 用make生成的校验字符串校验encrypt是否有效
     *
     * @param $key
     * @param $crumb
     * @param int $action
     * @return bool
     */
    public function verify($key, $crumb, $action = -1)
    {
        $i = ceil(time() / $this->ttl);
        if (substr($this->encrypt($i . $action . $key), -12, 10) === $crumb ||
            substr($this->encrypt(($i - 1) . $action . $key), -12, 10) === $crumb
        ) {
            return true;
        }

        return false;
    }
}
