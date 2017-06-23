<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Core;

use Cross\I\RouterInterface;
use Cross\Exception\FrontException;
use Cross\Exception\CoreException;
use Cross\Http\Request;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Router
 * @package Cross\Core
 */
class Router implements RouterInterface
{
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
     * url参数
     *
     * @var array
     */
    private $params = array();

    /**
     * @var string
     */
    private $uriRequest;

    /**
     * @var string
     */
    private $originUriRequest;

    /**
     * @var Config
     */
    private $config;

    /**
     * 默认Action名称
     */
    const DEFAULT_ACTION = 'index';

    /**
     * Router constructor.
     * @param Config $config
     */
    function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * 设置URI字符串
     *
     * @param string $request_string
     * @return $this
     */
    public function setUriRequest($request_string)
    {
        $this->originUriRequest = $request_string;

        $uri = parse_url($request_string);
        $this->uriRequest = $uri['path'];
        if (!empty($uri['query'])) {
            parse_str($uri['query'], $addition_params);
            $_GET += $addition_params;
        }

        return $this;
    }

    /**
     * Router
     *
     * @return $this
     * @throws CoreException
     */
    public function getRouter()
    {
        $request = $this->getUriRequest('', $url_config);
        if (!empty($request)) {
            $request = $this->parseRequestString($request, $url_config);
            $this->initRouter($request);
        } else {
            $this->initByDefaultRouter($url_config['*']);
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
     * @param bool $clear_ampersand
     * @param bool $convert_html_entities
     * @return string
     * @throws CoreException
     */
    public function getUriRequest($prefix = '/', &$url_config = array(), $clear_ampersand = true, $convert_html_entities = true)
    {
        $url_config = $this->config->get('url');
        if (!empty($this->uriRequest)) {
            return $this->uriRequest;
        }

        switch ($url_config['type']) {
            case 1:
            case 3:
                $request = Request::getInstance()->getQueryString();
                $this->originUriRequest = $request;

                if ($clear_ampersand) {
                    //rewrite下带问号的请求参数追加到$_GET数组
                    $request_uri = Request::getInstance()->getRequestURI();
                    if ($url_config['rewrite'] && $request_uri && false !== strpos($request_uri, '?')) {
                        $query_string = parse_url($request_uri, PHP_URL_QUERY);
                        parse_str($query_string, $addition_params);
                        $_GET += $addition_params;

                        if ($query_string == $request) {
                            $request = '';
                        }
                    }

                    if (false !== ($l = strpos($request, '&'))) {
                        $request = substr($request, 0, $l);
                    }

                    if (false !== strpos($request, '=')) {
                        $request = '';
                    }

                    if (isset($request[0]) && $request[0] != '&') {
                        array_shift($_GET);
                    }
                }
                break;

            case 2:
            case 4:
            case 5:
                $request = Request::getInstance()->getPathInfo();
                $this->originUriRequest = $request;
                break;

            default:
                throw new CoreException('Not support URL type!');
        }

        if ($request) {
            $request = urldecode(ltrim($request, '/'));
            if ($convert_html_entities) {
                $request = htmlspecialchars($request, ENT_QUOTES);
            }
        }

        return $prefix . $request;
    }

    /**
     * 从默认配置初始化Router
     *
     * @param string $default_router
     * @return array
     * @throws CoreException
     */
    private function initByDefaultRouter($default_router)
    {
        if (empty($default_router)) {
            throw new CoreException('Undefined default router!');
        }

        if (false !== strpos($default_router, ':')) {
            list($controller, $action) = explode(':', $default_router);
        } else {
            $controller = $default_router;
            $action = self::DEFAULT_ACTION;
        }

        $this->setController($controller);
        $this->setAction($action);
    }

    /**
     * 解析router别名配置
     *
     * @param array $request
     * @throws CoreException
     * @internal param $router
     */
    private function initRouter(array $request)
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
                $action = self::DEFAULT_ACTION;
            }
        }

        $this->config->set('ori_router', array(
            'request' => $this->originUriRequest,
            'controller' => $ori_controller,
            'action' => $ori_action,
            'params' => $request
        ));

        $this->setController($controller);
        $this->setAction($action);
        $this->setParams($request);
    }

    /**
     * 将字符串参数解析成数组
     *
     * @param string $query_string
     * @param array $url_config
     * @return array
     * @throws FrontException
     */
    private static function parseRequestString($query_string, $url_config)
    {
        $url_suffix = &$url_config['ext'];
        if (isset($url_suffix[0]) && ($url_suffix_length = strlen(trim($url_suffix))) > 0) {
            if (0 === strcasecmp($url_suffix, substr($query_string, -$url_suffix_length))) {
                $query_string = substr($query_string, 0, -$url_suffix_length);
            } else {
                throw new FrontException('Page not found !');
            }
        }

        $url_dot = &$url_config['dot'];
        if ($url_dot && false !== strpos($query_string, $url_dot)) {
            $router_params = explode($url_dot, $query_string);
            $end_params = array_pop($router_params);
        } else {
            $router_params = array();
            $end_params = $query_string;
        }

        $params_dot = &$url_config['params_dot'];
        if ($params_dot && $params_dot != $url_dot && false !== strpos($end_params, $params_dot)) {
            $params_data = explode($params_dot, $end_params);
            foreach ($params_data as $p) {
                $router_params[] = $p;
            }
        } else {
            $router_params[] = $end_params;
        }

        return $router_params;
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

