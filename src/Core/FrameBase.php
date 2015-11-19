<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Core;

use Cross\Exception\CoreException;
use Cross\Http\Request;
use Cross\Http\Response;
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
     * 返回一个数组或JSON字符串
     *
     * @param int $status
     * @param string|array $message
     * @param bool $json_encode
     * @return array|string
     * @throws CoreException
     */
    function result($status = 1, $message = 'ok', $json_encode = false)
    {
        $result = array(
            'status' => $status,
            'message' => $message,
        );

        if ($json_encode) {
            if (($result = json_encode($result)) === false) {
                throw new CoreException('json encode fail');
            }
        }

        return $result;
    }

    /**
     * 调用注入的匿名函数
     *
     * @param string $name
     * @param array $params
     * @return mixed
     * @throws CoreException
     */
    protected function getDi($name, array $params = array())
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
    protected function getDii($name, array $params = array())
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
        return HttpAuth::factory($this->getConfig()->get('sys', 'auth'), $this->getUrlEncryptKey('auth'))->set($key, $value, $exp);
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
        return HttpAuth::factory($this->getConfig()->get('sys', 'auth'), $this->getUrlEncryptKey('auth'))->get($key, $de);
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
        return Helper::encodeParams($tex, $this->getUrlEncryptKey('uri'), $type);
    }

    /**
     * 获取uri加密/解密时用到的key
     *
     * @param string $type
     * @return string
     */
    protected function getUrlEncryptKey($type = 'auth')
    {
        $encrypt_key = $this->getConfig()->get('encrypt', $type);
        if (empty($encrypt_key)) {
            $encrypt_key = 'cross';
        }

        return $encrypt_key;
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
                if (!empty(self::$action_annotate['params'])) {
                    $result = Application::combineParamsAnnotateConfig($result_array, self::$action_annotate['params']);
                } else {
                    $result = $result_array;
                }
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
                return $this->request = $this->delegate->getRequest();

            case 'response' :
                return $this->response = $this->delegate->getResponse();

            case 'view' :
                return $this->view = $this->initView();
        }

        return null;
    }
}
