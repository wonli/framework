<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Auth;

use cross\exception\CoreException;
use Cross\I\HttpAuthInterface;
use Cross\Model\RedisModel;

/**
 * @author wonli <wonli@live.com>
 * Class RedisAuth
 * @package Cross\Auth
 */
class RedisAuth implements HttpAuthInterface
{
    /**
     * 加解密默认key
     *
     * @var string
     */
    protected $authKey;

    /**
     * auth key前缀
     *
     * @var string
     */
    protected $authKeyPrefix = '';

    function __construct(string $authKey = '')
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        $this->authKeyPrefix = '_@CPA@_' . session_id();
    }

    /**
     * 设置session的值
     *
     * @param string $key
     * @param string|array $value
     * @param int $expire
     * @return bool|mixed
     * @throws CoreException
     */
    function set(string $key, $value, int $expire = 0): bool
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
     */
    function get(string $key, bool $deCode = false)
    {
        $key = $this->authKeyPrefix . '@' . $key;
        if (false !== strpos($key, ':') && $deCode) {
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
