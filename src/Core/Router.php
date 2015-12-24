<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Core;

use Cross\Exception\CoreException;
use Cross\Exception\FrontException;
use Cross\Http\Request;
use Cross\I\RouterInterface;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Router
 * @package Cross\Core
 */
class Router implements RouterInterface
{
    /**
     * url参数
     *
     * @var array
     */
    private $params;

    /**
     * Action名称
     *
     * @var string
     */
    private $action;

    /**
     * 控制器名称
     *
     * @var string
     */
    private $controller;

    /**
     * @var Config
     */
    private $config;

    /**
     * 默认action
     *
     * @var string
     */
    public static $default_action = 'index';

    /**
     * @var array;
     */
    private $router_params = array();

    /**
     * 初始化router
     *
     * @param Config $config
     */
    function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * 设置url解析参数
     *
     * @param array $params
     * @return $this
     */
    public function setRouterParams(array $params)
    {
        $this->router_params = $params;
        return $this;
    }

    /**
     * 设置router
     *
     * @return $this
     * @throws FrontException
     */
    public function getRouter()
    {
        $router_params = $this->getRouterParams();
        if (empty($router_params)) {
            $_defaultRouter = $this->getDefaultRouter($this->config->get('url', '*'));

            $this->setController($_defaultRouter['controller']);
            $this->setAction($_defaultRouter['action']);
            $this->setParams($_defaultRouter['params']);
        } else {
            $this->initRouter($router_params);
        }

        return $this;
    }

    /**
     * 返回控制器名称
     *
     * @return mixed
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * 返回action名称
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * 返回参数
     *
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * 按类型解析请求字符串
     *
     * @param string $prefix
     * @param array $url_config
     * @return string
     */
    public function getUriRequest($prefix = '/', & $url_config = array())
    {
        $url_config = $this->config->get('url');
        switch ($url_config ['type']) {
            case 1:
            case 3:
                $request = Request::getInstance()->getUriRequest('QUERY_STRING', $url_config['rewrite']);
                break;

            case 2:
            case 4:
            case 5:
                $request = Request::getInstance()->getUriRequest('PATH_INFO', $url_config['rewrite']);
                break;

            default:
                $request = '';
        }

        return $prefix . htmlspecialchars(urldecode(ltrim($request, '/')), ENT_QUOTES);
    }

    /**
     * 要解析的请求string
     *
     * @return array
     */
    private function getRouterParams()
    {
        if (empty($this->router_params)) {
            $this->router_params = $this->initRequestParams();
        }

        return $this->router_params;
    }

    /**
     * 初始化参数 按类型返回要解析的url字符串
     *
     * @return array|string
     * @throws \cross\exception\CoreException
     */
    private function initRequestParams()
    {
        $request = $this->getUriRequest('', $url_config);
        switch ($url_config ['type']) {
            case 1:
            case 3:
                return $this->parseRequestString($request, $url_config, true);

            case 2:
            case 4:
            case 5:
                $request = $this->parseRequestString($request, $url_config);
                if (!empty($request)) {
                    return array_merge($request, $_REQUEST);
                }
                return $request;

            default:
                throw new CoreException('不支持的 url type');
        }
    }

    /**
     * 解析请求字符串
     * <pre>
     * [0] 解析的结果
     * [1] 抛出异常
     * [2] 返回空字符串
     * </pre>
     *
     * @param string $_query_string
     * @param array $url_config
     * @param bool $parse_mixed_params
     * @return array
     * @throws FrontException
     */
    private static function parseRequestString($_query_string, $url_config, $parse_mixed_params = false)
    {
        if (true === $parse_mixed_params && false !== strpos($_query_string, '&')) {
            $_query_string_array = explode('&', $_query_string);
            $_query_string = array_shift($_query_string_array);
        }

        $router_params = array();
        if (!$_query_string) {
            return $router_params;
        }

        $_url_ext = $url_config['ext'];
        if ($_query_string != '' && isset($_url_ext[0]) && ($_url_ext_len = strlen(trim($_url_ext))) > 0) {
            if (0 === strcasecmp($_url_ext, substr($_query_string, -$_url_ext_len))) {
                $_query_string = substr($_query_string, 0, -$_url_ext_len);
            } else {
                throw new FrontException('Page not found !');
            }
        }

        if (false !== strpos($_query_string, $url_config['dot'])) {
            $router_params = explode($url_config['dot'], $_query_string);
        } else {
            $router_params = array($_query_string);
        }

        return $router_params;
    }

    /**
     * 默认控制器和方法 init['url']['*']
     *
     * @param $init_default
     * @return array
     * @throws CoreException
     */
    private function getDefaultRouter($init_default)
    {
        if ($init_default) {
            list($_defController, $_defAction) = explode(':', $init_default);
            $_defaultRouter = array();

            if (isset($_defController)) {
                $_defaultRouter['controller'] = $_defController;
            } else {
                throw new CoreException('please define the default controller in the APP_PATH/APP_NAME/init.php file!');
            }

            if (isset($_defAction)) {
                $_defaultRouter['action'] = $_defAction;
            } else {
                throw new CoreException('please define the default action in the APP_PATH/APP_NAME/init.php file!');
            }

            $_defaultRouter['params'] = $_REQUEST;

            return $_defaultRouter;
        } else {
            throw new CoreException('undefined default router!');
        }
    }

    /**
     * 解析router别名配置
     *
     * @param array $request
     * @throws CoreException
     * @internal param $router
     */
    private function initRouter($request)
    {
        $_controller = array_shift($request);
        $this->config->set('url', array('ori_controller' => $_controller));

        $combine_alias_key = '';
        if (isset($request[0])) {
            $combine_alias_key = $_controller . ':' . $request[0];
        }

        $controller_alias = '';
        $router_config = $this->config->get('router');
        if (isset($router_config [$combine_alias_key])) {
            array_shift($request);
            $controller_alias = $router_config [$combine_alias_key];
        } elseif (isset($router_config [$_controller])) {
            $controller_alias = $router_config [$_controller];
        }

        if (!empty($controller_alias)) {
            if (false !== strpos($controller_alias, ':')) {
                list($_controller, $_action) = explode(':', $controller_alias);
            } else {
                $_controller = $controller_alias;
            }
        }

        if (!isset($_action)) {
            if (isset($request[0])) {
                $_action = array_shift($request);
                $this->config->set('url', array('ori_action' => $_action));
            } else {
                $_action = self::$default_action;
            }
        }

        $this->setController($_controller);
        $this->setAction($_action);
        $this->setParams($request);
    }

    /**
     * 设置controller
     *
     * @param $controller
     */
    private function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * 设置Action
     *
     * @param $action
     */
    private function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * 设置参数
     *
     * @param $params
     */
    private function setParams($params)
    {
        $this->params = $params;
    }
}

