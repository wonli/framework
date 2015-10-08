<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Core;

use Cross\Exception\CoreException;
use Cross\Lib\Mcrypt\Mcrypt;
use Cross\MVC\View;

/**
 * @Auth: wonli <wonli@live.com>
 * Class FrameBase
 * @package Cross\Core
 * @property Config $config
 * @property Request $request
 * @property Response $response
 * @property View $view
 */
class FrameBase
{
    /**
     * action名称
     *
     * @var string
     */
    protected $action;

    /**
     * 参数列表
     *
     * @var array
     */
    protected $params;

    /**
     * 控制器名称
     *
     * @var string
     */
    protected $controller;

    /**
     * 视图控制器命名空间
     *
     * @var string
     */
    protected $view_controller;

    /**
     * @var Delegate
     */
    protected $delegate;

    /**
     * url加密时用到的key
     *
     * @var string
     */
    protected $url_crypt_key;

    /**
     * 指定加密http auth加密的key
     *
     * @var string
     */
    protected $http_auth_key;

    /**
     * @var Delegate
     */
    public static $app_delegate;

    /**
     * 参数列表
     *
     * @var array
     */
    public static $url_params;

    /**
     * 当前调用的方法名
     *
     * @var string
     */
    public static $call_action;

    /**
     * 控制器名称
     *
     * @var string
     */
    public static $controller_name;

    /**
     * 默认视图控制器命名空间
     *
     * @var string
     */
    public static $view_controller_namespace;

    /**
     * 当前方法的注释配置
     *
     * @var array
     */
    public static $action_annotate;

    public function __construct()
    {
        $this->delegate = self::$app_delegate;
        $this->view_controller = self::$view_controller_namespace;
        $this->controller = self::$controller_name;
        $this->action = self::$call_action;
        $this->params = self::$url_params;
    }

    /**
     * @return Config
     */
    function getConfig()
    {
        return $this->delegate->getConfig();
    }

    /**
     * @return Delegate
     */
    function getDelegate()
    {
        return $this->delegate;
    }

    /**
     * 调用注入的匿名函数
     *
     * @param string $name
     * @param array $params
     * @return mixed
     * @throws CoreException
     */
    protected function getDi($name, $params = array())
    {
        $di = $this->delegate->getDi();
        if (isset($di[$name])) {
            return call_user_func_array($di[$name], $params);
        }
        throw new CoreException("未定义的注入方法 {$name}");
    }

    /**
     * 调用注入的匿名函数并缓存结果
     *
     * @param string $name
     * @param array $params
     * @return mixed
     * @throws CoreException
     */
    protected function getDii($name, $params = array())
    {
        static $dii = array();
        $di = $this->delegate->getDi();
        if (isset($dii[$name])) {
            return $dii[$name];
        } elseif (isset($di[$name])) {
            $dii[$name] = call_user_func_array($di[$name], $params);
            return $dii[$name];
        }
        throw new CoreException("未定义的注入方法 {$name}");
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
        $auth_type = $this->getConfig()->get('sys', 'auth');
        return HttpAuth::factory($auth_type, $this->http_auth_key)->set($key, $value, $exp);
    }

    /**
     * 解密会话
     *
     * @param string $key
     * @param bool $de
     * @return bool|mixed|string
     */
    protected function getAuth($key, $de = false)
    {
        $auth_type = $this->getConfig()->get('sys', 'auth');
        return HttpAuth::factory($auth_type, $this->http_auth_key)->get($key, $de);
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
            $url_crypt_key = $this->getConfig()->get('url', 'crypto_key');
            if (!$url_crypt_key) {
                $url_crypt_key = 'crossphp';
            }

            $this->setUrlEncryptKey($url_crypt_key);
        }

        return $this->url_crypt_key;
    }

    /**
     * 还原加密后的参数
     *
     * @param null|string $params
     * @return bool|string
     */
    protected function sParams($params = null)
    {
        $url_type = $this->getConfig()->get('url', 'type');
        if (null === $params) {
            switch ($url_type) {
                case 1:
                case 5:
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

        switch ($url_type) {
            case 1:
            case 5:
                $result_array = explode($this->getConfig()->get('url', 'dot'), $decode_params_str);
                $result = Application::combineParamsAnnotateConfig($result_array, self::$action_annotate['params']);
                break;
            case 2:
                parse_str($decode_params_str, $result);
                break;
            case 3:
            case 4:
                $result = Application::stringParamsToAssociativeArray($decode_params_str, $this->getConfig()->get('url', 'dot'));
                break;
        }

        return $result;
    }

    /**
     * mcrypt加密
     *
     * @param string $params
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
     * @param string $params
     * @return string
     */
    protected function mcryptDecode($params)
    {
        $mcrypt = new Mcrypt;
        $_params = $mcrypt->deCode($params);

        return $_params;
    }

    /**
     * 初始化视图控制器
     *
     * @return mixed
     */
    protected function initView()
    {
        $view = new $this->view_controller();
        $view->config = $this->getConfig();
        $view->params = $this->params;
        return $view;
    }

    /**
     * 返回一个数组或JSON字符串
     *
     * @param int $status
     * @param string $message
     * @param string $type
     * @return array|string
     * @throws CoreException
     */
    function result($status = 1, $message = 'ok', $type = '')
    {
        $result = array(
            'status' => $status,
            'message' => $message,
        );

        if (strcasecmp($type, 'json') == 0) {
            if (json_encode($result) === false) {
                throw new CoreException('json encode失败');
            }

            $result = json_encode($result);
        }

        return $result;
    }

    /**
     * request response view
     *
     * @param string $property
     * @return Response|Request|View|Config|null
     */
    function __get($property)
    {
        switch ($property) {
            case 'config':
                return $this->config = $this->delegate->getConfig();

            case 'request' :
                return $this->request = Request::getInstance();

            case 'response' :
                return $this->response = Response::getInstance();

            case 'view' :
                return $this->view = $this->initView();
        }

        return null;
    }
}
