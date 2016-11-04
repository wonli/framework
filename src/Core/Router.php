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
            $defaultRouter = $this->getDefaultRouter();
            $this->setController($defaultRouter['controller']);
            $this->setAction($defaultRouter['action']);
            $this->setParams($defaultRouter['params']);
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
                $request = Request::getInstance()->getUriRequest('QUERY_STRING');
                break;

            case 2:
            case 4:
            case 5:
                $request = Request::getInstance()->getUriRequest('PATH_INFO');
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
                return $this->parseRequestString($request, $url_config);

            default:
                throw new CoreException('不支持的 url type');
        }
    }

    /**
     * 解析请求字符串
     *
     * @param string $query_string
     * @param array $url_config
     * @param bool $parse_mixed_params
     * @return array
     * @throws FrontException
     */
    private static function parseRequestString($query_string, $url_config, $parse_mixed_params = false)
    {
        if (true === $parse_mixed_params && false !== strpos($query_string, '&')) {
            $tmp = explode('&', $query_string);
            $query_string = array_shift($tmp);
        }

        $router_params = array();
        if ($query_string) {
            $url_ext = &$url_config['ext'];
            if (isset($url_ext[0]) && ($url_ext_len = strlen(trim($url_ext))) > 0) {
                if (0 === strcasecmp($url_ext, substr($query_string, -$url_ext_len))) {
                    $query_string = substr($query_string, 0, -$url_ext_len);
                } else {
                    throw new FrontException('Page not found !');
                }
            }

            if (false !== strpos($query_string, $url_config['dot'])) {
                $router_params = explode($url_config['dot'], $query_string);
            } else {
                $router_params = array($query_string);
            }
        }

        return $router_params;
    }

    /**
     * 默认控制器和方法
     *
     * @return array
     * @throws CoreException
     */
    private function getDefaultRouter()
    {
        $default_router = $this->config->get('url', '*');
        if (empty($default_router)) {
            throw new CoreException('Undefined default router!');
        }

        if (false !== strpos($default_router, ':')) {
            list($controller, $action) = explode(':', $default_router);
        } else {
            $controller = $default_router;
            $action = self::$default_action;
        }

        return array(
            'controller' => $controller,
            'action' => $action,
            'params' => array()
        );
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
        $ori_controller = $controller = array_shift($request);

        $combine_alias_key = '';
        if (isset($request[0])) {
            $combine_alias_key = $controller . ':' . $request[0];
        }

        $controller_alias = '';
        $router_config = $this->config->get('router');
        if (isset($router_config [$combine_alias_key])) {
            array_shift($request);
            $controller_alias = $router_config [$combine_alias_key];
        } elseif (isset($router_config [$controller])) {
            $controller_alias = $router_config [$controller];
        }

        if (!empty($controller_alias)) {
            if (false !== strpos($controller_alias, ':')) {
                list($controller, $action) = explode(':', $controller_alias);
            } else {
                $controller = $controller_alias;
            }
        }

        $ori_action = '';
        if (!isset($action)) {
            if (isset($request[0]) && !empty($request[0])) {
                $ori_action = $action = array_shift($request);
            } else {
                $action = self::$default_action;
            }
        }

        $this->config->set('ori_router', array(
            'controller' => $ori_controller,
            'action' => $ori_action,
            'params' => $request
        ));

        $this->setController($controller);
        $this->setAction($action);
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

