<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Auth;

use Cross\Exception\CoreException;
use Cross\I\HttpAuthInterface;
use Cross\Model\RedisModel;
use RedisException;

/**
 * @author wonli <wonli@live.com>
 * Class RedisAuth
 * @package Cross\Auth
 */
class RedisAuth implements HttpAuthInterface
{
    /**
     * auth key前缀
     *
     * @var string
     */
    protected string $authKeyPrefix;

    /**
     * RedisAuth constructor.
     */
    function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->authKeyPrefix = '_@CPA@_' . session_id();
    }

    /**
     * 设置session的值
     *
     * @param string $key
     * @param array|string $value
     * @param int $expire
     * @return bool
     * @throws CoreException
     * @throws RedisException
     */
    function set(string $key, array|string $value, int $expire = 0): bool
    {
        $key = $this->authKeyPrefix . '@' . $key;
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        RedisModel::use('auth')->set($key, $value, $expire);
        return true;
    }

    /**
     * 获取session的值
     *
     * @param string $key
     * @param bool $deCode
     * @return bool|mixed
     * @throws CoreException
     * @throws RedisException
     */
    function get(string $key, bool $deCode = false): mixed
    {
        $key = $this->authKeyPrefix . '@' . $key;
        if (str_contains($key, ':') && $deCode) {
            list($key, $arrKey) = explode(':', $key);
        }

        $result = RedisModel::use('auth')->get($key);
        if (!$result) {
            return false;
        }

        if ($deCode) {
            $result = json_decode($result, true);
            if (isset($arrKey) && isset($result[$arrKey])) {
                return $result[$arrKey];
            }
        }

        return $result;
    }
}
