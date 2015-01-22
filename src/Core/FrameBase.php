<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.1
 */
namespace Cross\Core;

use Cross\Exception\CoreException;
use Cross\Lib\Mcrypt\Mcrypt;
use Exception;
use ReflectionClass;

/**
 * @Auth: wonli <wonli@live.com>
 * Class FrameBase
 * @package Cross\Core
 * @property Request request
 * @property Response response
 * @property mixed view
 */
class FrameBase extends Application
{
    /**
     * 参数列表
     *
     * @var array
     */
    protected $params;

    /**
     * 方法名称
     *
     * @var string
     */
    protected $action;

    /**
     * 控制器名称
     *
     * @var string
     */
    protected $controller;

    /**
     * app配置
     *
     * @var Config
     */
    protected $config;

    /**
     * 缓存配置
     *
     * @var string
     */
    protected $cache_config;

    /**
     * url加密时用到的key
     *
     * @var string
     */
    protected $url_crypt_key;

    /**
     * module的实例
     *
     * @var array
     */
    static protected $moduleInstance = array();

    /**
     * object的缓存hash
     *
     * @var array
     */
    static protected $objectCache = array();

    public function __construct()
    {
        $this->app_init();
    }

    /**
     * 为初始化准备参数
     */
    function app_init()
    {
        if (!$this->config) {
            $this->config = parent::getConfig();
        }

        if (!$this->controller) {
            $this->controller = parent::getController();
        }

        if (!$this->action) {
            $this->action = parent::getAction();
        }

        if (!$this->params) {
            $this->params = parent::getParams();
        }
    }

    /**
     * 设置缓存配置
     *
     * @param $cache_config
     */
    public function setCacheConfig($cache_config)
    {
        $this->cache_config = $cache_config;
    }

    /**
     * 返回缓存配置
     *
     * @return mixed
     */
    public function getCacheConfig()
    {
        return $this->cache_config;
    }

    /**
     * 加密会话 sys=>auth中指定是cookie/session
     *
     * @param string $key key
     * @param string $value 值
     * @param int $exp 过期时间
     * @return bool
     */
    protected function setAuth($key, $value, $exp = 86400)
    {
        $auth_type = parent::getConfig()->get('sys', 'auth');
        return HttpAuth::factory($auth_type)->set($key, $value, $exp);
    }

    /**
     * 解密会话
     *
     * @param $key
     * @param bool $de
     * @return bool|mixed|string
     */
    protected function getAuth($key, $de = false)
    {
        $auth_type = parent::getConfig()->get('sys', 'auth');
        return HttpAuth::factory($auth_type)->get($key, $de);
    }

    /**
     * 参数加密
     *
     * @param $tex
     * @param string $type
     * @return bool|string
     */
    protected function urlEncrypt($tex, $type = 'encode')
    {
        $key = $this->getUrlEncryptKey();
        return Helper::encodeParams($tex, $key, $type);
    }

    /**
     * 设置url加密时候用到的key
     *
     * @param $key
     */
    protected function setUrlEncryptKey($key)
    {
        $this->url_crypt_key = $key;
    }

    /**
     * 获取url加密/解密时用到的key
     */
    protected function getUrlEncryptKey()
    {
        if (!$this->url_crypt_key) {
            $url_crypt_key = parent::getConfig()->get('url', 'crypto_key');
            if (! $url_crypt_key) {
                $url_crypt_key = 'crossphp';
            }

            $this->setUrlEncryptKey($url_crypt_key);
        }

        return $this->url_crypt_key;
    }

    /**
     * 还原加密后的参数
     *
     * @param null $params
     * @return bool|string
     */
    protected function sParams($params = null)
    {
        $url_type = parent::getConfig()->get('url', 'type');
        if (null === $params) {
            switch($url_type)
            {
                case 1:
                    $params = $this->params;
                    if (is_array($this->params)) {
                        $params = current(array_values($this->params));
                    }
                    break;

                case 2:
                    $params = current(array_keys($this->params));
                    break;

                case 3:
                case 4:
                    $params = current($this->params);
                    break;
            }
        }

        $result = array();
        $decode_params_str = false;
        if (is_string($params)) {
            $decode_params_str = $this->urlEncrypt($params, 'decode');
        }

        if (false == $decode_params_str) {
            return $this->params;
        }

        switch($url_type)
        {
            case 1:
                $result_array = explode(parent::getConfig()->get('url', 'dot'), $decode_params_str);
                $annotate = parent::getActionConfig();
                $result = parent::combineParamsAnnotateConfig($result_array, $annotate['params']);
                break;
            case 2:
                parse_str($decode_params_str, $result);
                break;
            case 3:
            case 4:
                $result = parent::stringParamsToAssociativeArray($decode_params_str);
                break;
        }

        return $result;
    }

    /**
     * mcrypt加密
     *
     * @param $params
     * @return mixed
     */
    protected function mcryptEncode($params)
    {
        $mcrypt = new Mcrypt;
        $_params = $mcrypt->enCode($params);

        return $_params[1];
    }

    /**
     * mcrypt 解密
     *
     * @param $params
     * @return string
     */
    protected function mcryptDecode($params)
    {
        $mcrypt = new Mcrypt;
        $_params = $mcrypt->deCode($params);

        return $_params;
    }

    /**
     * 缓存并返回object的一个实例(module为一个对象)
     *
     * @param $objectInstance
     * @return mixed
     * @throws CoreException
     */
    protected function loadObject($objectInstance)
    {
        try {
            $obj = new ReflectionClass($objectInstance);
            if (!isset(self::$objectCache[$obj->name])) {
                self::$objectCache[$obj->name] = $objectInstance;
            }

            return self::$objectCache[$obj->name];
        } catch (Exception $e) {
            throw new CoreException('cache module failed!');
        }
    }

    /**
     * 加载视图控制器
     *
     * @return mixed
     */
    protected function initView()
    {
        list(,,$type) = explode("\\", get_called_class());
        if (strcasecmp($type, 'views') !== 0) {
            $view_class_name = str_replace($type, 'views', get_called_class()) . 'View';
        } else {
            $view_class_name = get_called_class();
        }

        $view = new $view_class_name;
        $view->config = $this->config;
        return $view;
    }

    /**
     * 返回一个数组或JSON字符串
     *
     * @param int $status
     * @param string $message
     * @param string $type
     * @return array|string
     */
    function result($status = 1, $message = 'ok', $type = '')
    {
        $result = array(
            'status'  =>  $status,
            'message'   =>  $message,
        );

        if (strcasecmp($type, 'json') == 0) {
            $result = json_encode($result);
        }

        return $result;
    }

    /**
     * request response view
     *
     * @param $property
     * @return Response|Request|mixed|object
     */
    function __get($property)
    {
        switch ($property) {
            case 'request' :
                return $this->request = Request::getInstance();

            case 'response' :
                return $this->response = Response::getInstance();

            case 'view' :
                return $this->view = $this->initView();

            default :
                break;
        }
    }
}
