<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Core;

use Cross\Exception\CoreException;
use Cross\Http\Response;
use Cross\Http\Request;
use Cross\MVC\View;

/**
 * @author wonli <wonli@live.com>
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
     * @var Delegate
     */
    protected $delegate;

    /**
     * 视图控制器命名空间
     *
     * @var string
     */
    protected $view_controller;

    /**
     * 当前方法的注释配置
     *
     * @var array
     */
    protected $action_annotate;

    /**
     * @var Delegate
     */
    public static $app_delegate;

    public function __construct()
    {
        $this->delegate = self::$app_delegate;
        $runtime_config = $this->delegate->getClosureContainer()->run('~controller~runtime~');

        $this->view_controller = &$runtime_config['view_controller_namespace'];
        $this->action_annotate = &$runtime_config['action_annotate'];
        $this->controller = &$runtime_config['controller'];
        $this->action = &$runtime_config['action'];
        $this->params = &$runtime_config['params'];
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
    function result($status = 1, $message = '', $json_encode = false)
    {
        $result = array('status' => $status, 'message' => $message);
        if ($json_encode) {
            if (($result = json_encode($result)) === false) {
                throw new CoreException('json encode fail');
            }
        }

        return $result;
    }

    /**
     * 读取配置文件
     *
     * @param string $config_file
     * @return Config
     * @throws CoreException
     */
    function loadConfig($config_file)
    {
        return Config::load($this->config->get('path', 'config') . $config_file);
    }

    /**
     * @see Loader::read()
     *
     * @param string $name
     * @param bool $get_file_content
     * @return mixed
     * @throws CoreException
     */
    function parseGetFile($name, $get_file_content = false)
    {
        return Loader::read($this->getFilePath($name), $get_file_content);
    }

    /**
     * 解析文件路径
     * <pre>
     *  格式如下:
     *  1 ::[path/file_name] 从当前项目根目录查找
     *  2 app::[path/file_name] 当前app路径
     *  3 static::[path/file_name] 静态资源目录
     *  4 cache::[path/file_name] 缓存路径
     *  5 config::[path/file_name] 配置路径
     * </pre>
     *
     * @param string $name
     * @return string
     */
    function getFilePath($name)
    {
        $prefix_name = 'project';
        if (false !== strpos($name, '::')) {
            list($prefix_name, $file_name) = explode('::', $name);
            if (!empty($prefix_name)) {
                $prefix_name = strtolower(trim($prefix_name));
            }
        } else {
            $file_name = $name;
        }

        static $cache = null;
        if (!isset($cache[$prefix_name])) {
            switch ($prefix_name) {
                case 'app':
                    $prefix_path = $this->config->get('app', 'path');
                    break;

                case 'cache':
                case 'config':
                    $prefix_path = $this->config->get('path', $prefix_name);
                    break;

                case 'static':
                    $prefix_path = $this->config->get('static', 'path');
                    break;

                default:
                    $prefix_path = PROJECT_REAL_PATH;
            }
            $cache[$prefix_name] = rtrim($prefix_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        return $cache[$prefix_name] . str_replace('/', DIRECTORY_SEPARATOR, $file_name);
    }

    /**
     * 加密会话 sys=>auth中指定是cookie/session
     *
     * @param string $key key
     * @param string|array $value 值
     * @param int $expire 过期时间(默认一天过期)
     * @return bool
     * @throws CoreException
     */
    protected function setAuth($key, $value, $expire = 86400)
    {
        return HttpAuth::factory($this->getConfig()->get('sys', 'auth'), $this->getUrlEncryptKey('auth'))->set($key, $value, $expire);
    }

    /**
     * 解密会话
     *
     * @param string $key
     * @param bool $deCode
     * @return bool|mixed|string
     * @throws CoreException
     */
    protected function getAuth($key, $deCode = false)
    {
        return HttpAuth::factory($this->getConfig()->get('sys', 'auth'), $this->getUrlEncryptKey('auth'))->get($key, $deCode);
    }

    /**
     * uri参数加密
     *
     * @param string $params
     * @param string $type
     * @return bool|string
     */
    protected function urlEncrypt($params, $type = 'encode')
    {
        return Helper::encodeParams($params, $this->getUrlEncryptKey('uri'), $type);
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
     * @param bool $use_annotate
     * @param string $params
     * @return bool|string
     */
    protected function sParams($use_annotate = true, $params = null)
    {
        $config = $this->getConfig();
        $addition_params = $config->get('ori_router', 'addition_params');
        if (empty($addition_params)) {
            $addition_params = array();
        }

        $url_config = $config->get('url');
        if (null === $params) {
            $ori_params = $config->get('ori_router', 'params');
            switch ($url_config['type']) {
                case 2:
                    $params = current(array_keys($ori_params));
                    array_shift($addition_params);
                    break;

                default:
                    if (is_array($ori_params)) {
                        $params = array_shift($ori_params);
                    } else {
                        $params = $ori_params;
                    }
            }
        }

        $decode_params_str = false;
        if (is_string($params)) {
            $decode_params_str = $this->urlEncrypt($params, 'decode');
        }

        if (false == $decode_params_str) {
            if ($params !== null) return $params;
            return $this->params;
        }

        $op_type = 2;
        $ori_result = array();
        if (!empty($url_config['params_dot'])) {
            $url_dot = &$url_config['params_dot'];
        } else {
            $url_dot = &$url_config['dot'];
        }

        switch ($url_config['type']) {
            case 1:
            case 5:
                $op_type = 1;
                $ori_result = explode($url_dot, $decode_params_str);
                break;
            case 2:
                parse_str($decode_params_str, $ori_result);
                break;
            case 3:
            case 4:
                $ori_result = Application::stringParamsToAssociativeArray($decode_params_str, $url_dot);
                break;
        }

        if (!empty($this->action_annotate['params']) && $use_annotate) {
            $result = Application::combineParamsAnnotateConfig($ori_result, $this->action_annotate['params'], $op_type);
        } else {
            $result = $ori_result;
        }

        if (!empty($addition_params) && is_array($addition_params)) {
            $result += $addition_params;
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
