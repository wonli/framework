<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Core;

use Cross\Interactive\ResponseData;
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
    protected $viewController;

    /**
     * 当前方法的注释配置
     *
     * @var array
     */
    protected $actionAnnotate;

    /**
     * @var Delegate
     */
    public static $appDelegate;

    /**
     * FrameBase constructor.
     */
    public function __construct()
    {
        $this->delegate = self::$appDelegate;

        $router = $this->delegate->getRouter();
        $this->controller = $router->getController();
        $this->action = $router->getAction();
        $this->params = $router->getParams();

        $app = $this->delegate->getApplication();
        $this->actionAnnotate = $app->getAnnotateConfig();
        $this->viewController = $app->getViewControllerNameSpace($this->controller);
    }

    /**
     * @return Config
     */
    final function getConfig(): Config
    {
        return $this->delegate->getConfig();
    }

    /**
     * @return Delegate
     */
    final function getDelegate(): Delegate
    {
        return $this->delegate;
    }

    /**
     * 返回一个ResponseData对象
     *
     * @param int $status
     * @param array $data
     * @return ResponseData
     */
    function responseData(int $status = 1, array $data = []): ResponseData
    {
        $rd = ResponseData::builder();
        $rd->setStatus($status);
        $rd->setData($data);
        return $rd;
    }

    /**
     * 读取配置文件
     *
     * @param string $configFile
     * @return Config
     * @throws CoreException
     */
    function loadConfig(string $configFile): Config
    {
        return Config::load($this->delegate->getConfig()->get('path', 'config') . $configFile);
    }

    /**
     * 获取文件路径
     *
     * @param string $name
     * @param bool $getFileContent
     * @return mixed
     * @throws CoreException
     * @see Loader::read()
     */
    function parseGetFile(string $name, bool $getFileContent = false)
    {
        return Loader::read($this->getFilePath($name), $getFileContent);
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
    function getFilePath(string $name): string
    {
        $prefixName = 'project';
        if (false !== strpos($name, '::')) {
            list($prefixName, $fileName) = explode('::', $name);
            if (!empty($prefixName)) {
                $prefixName = strtolower(trim($prefixName));
            }
        } else {
            $fileName = $name;
        }

        static $cache = null;
        if (!isset($cache[$prefixName])) {
            switch ($prefixName) {
                case 'app':
                    $prefixPath = $this->delegate->getConfig()->get('app', 'path');
                    break;

                case 'cache':
                case 'config':
                    $prefixPath = $this->delegate->getConfig()->get('path', $prefixName);
                    break;

                case 'static':
                    $prefixPath = $this->delegate->getConfig()->get('static', 'path');
                    break;

                default:
                    $prefixPath = PROJECT_REAL_PATH;
            }
            $cache[$prefixName] = rtrim($prefixPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        return $cache[$prefixName] . str_replace('/', DIRECTORY_SEPARATOR, $fileName);
    }

    /**
     * 加密会话
     * <pre>
     * sys.auth 中指定cookie/session
     * </pre>
     *
     * @param string $key key
     * @param string|array $value 值
     * @param int $expire 过期时间(默认一天过期)
     * @return bool
     * @throws CoreException
     */
    protected function setAuth(string $key, $value, int $expire = 86400)
    {
        $authKey = $this->getUrlEncryptKey('auth');
        $authMethod = $this->getConfig()->get('sys', 'auth');
        return HttpAuth::factory($authMethod, $authKey)->set($key, $value, $expire);
    }

    /**
     * 解密会话
     *
     * @param string $key
     * @param bool $deCode
     * @return bool|mixed|string
     * @throws CoreException
     */
    protected function getAuth(string $key, bool $deCode = false)
    {
        $authKey = $this->getUrlEncryptKey('auth');
        $authMethod = $this->getConfig()->get('sys', 'auth');
        return HttpAuth::factory($authMethod, $authKey)->get($key, $deCode);
    }

    /**
     * uri参数加密
     *
     * @param string $params
     * @param string $type
     * @return bool|string
     */
    protected function urlEncrypt(string $params, string $type = 'encode'): string
    {
        return Helper::encodeParams($params, $this->getUrlEncryptKey('uri'), $type);
    }

    /**
     * 获取uri加密/解密时用到的key
     *
     * @param string $type
     * @return string
     */
    protected function getUrlEncryptKey(string $type = 'auth'): string
    {
        $encryptKey = $this->getConfig()->get('encrypt', $type);
        if (empty($encryptKey)) {
            $encryptKey = 'cross.' . $type;
        }

        return $encryptKey;
    }

    /**
     * 还原加密后的参数
     *
     * @param bool $useAnnotate
     * @param null|string $params
     * @return array|bool|string
     */
    protected function sParams(bool $useAnnotate = true, $params = null)
    {
        $config = $this->getConfig();
        $additionParams = $config->get('ori_router', 'addition_params');
        if (empty($additionParams)) {
            $additionParams = [];
        }

        $urlConfig = $config->get('url');
        if (null === $params) {
            $oriParams = $config->get('ori_router', 'params');
            if ($urlConfig['type'] > 2) {
                $params = current(array_keys($additionParams));
                array_shift($additionParams);
            } else {
                if (is_array($oriParams)) {
                    $params = array_shift($oriParams);
                } else {
                    $params = $oriParams;
                }
            }
        }

        $decodeParamsStr = false;
        if (is_string($params)) {
            $decodeParamsStr = $this->urlEncrypt($params, 'decode');
        }

        if (false == $decodeParamsStr) {
            if ($params !== null) return $params;
            return $this->params;
        }

        $opType = 2;
        $oriResult = [];
        if (!empty($urlConfig['params_dot'])) {
            $urlDot = &$urlConfig['params_dot'];
        } else {
            $urlDot = &$urlConfig['dot'];
        }

        switch ($urlConfig['type']) {
            case 1:
                $opType = 1;
                $oriResult = explode($urlDot, $decodeParamsStr);
                break;
            case 2:
                $oriResult = Application::stringParamsToAssociativeArray($decodeParamsStr, $urlDot);
                break;

            default:
                parse_str($decodeParamsStr, $oriResult);
        }

        if (!empty($this->actionAnnotate['params']) && $useAnnotate) {
            $result = Application::combineParamsAnnotateConfig($oriResult, $this->actionAnnotate['params'], $opType);
        } else {
            $result = $oriResult;
        }

        if (!empty($additionParams) && is_array($additionParams)) {
            $result += $additionParams;
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
        $view = new $this->viewController();
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
