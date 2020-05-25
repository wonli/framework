<?php
/**
 * Cross - a micro PHP framework
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
 * @author wonli <wonli@live.com>
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
    private $params = [];

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
     * @var Delegate
     */
    private $delegate;

    /**
     * @var array
     */
    private $defaultRouter = [];

    /**
     * @var bool
     */
    private $hasParseUrl = false;

    /**
     * 默认Action名称
     */
    const DEFAULT_ACTION = 'index';

    /**
     * Router constructor.
     * @param Delegate $delegate
     */
    function __construct(Delegate &$delegate)
    {
        $this->delegate = $delegate;
        $this->config = $delegate->getConfig();
    }

    /**
     * Router
     *
     * @return $this
     * @throws CoreException
     * @throws FrontException
     */
    public function getRouter(): self
    {
        if (!$this->hasParseUrl) {
            $this->hasParseUrl = true;
            $rs = $this->getUriRequest('', $url_config);
            if (!empty($rs)) {
                $request = $this->parseRequestString($rs, $url_config);
                $closure = $this->delegate->getClosureContainer();
                if ($closure->has('router')) {
                    $closure->run('router', [$request, $this]);
                }

                if (empty($this->controller) || empty($this->action)) {
                    $this->parseRouter($request, $url_config);
                }
            } else {
                $router = $this->parseDefaultRouter($url_config['*']);
                $this->setController($router[0]);
                $this->setAction($router[1]);

                $params = $this->parseParams([], $url_config);
                $this->setParams($params);
            }
        }

        return $this;
    }

    /**
     * 使用默认路由
     *
     * @throws CoreException
     */
    function useDefaulterRouter(): void
    {
        $router = $this->getDefaultRouter();
        $this->setController($router[0]);
        $this->setAction($router[1]);
    }

    /**
     * 获取默认控制器
     *
     * @return array
     * @throws CoreException
     */
    function getDefaultRouter(): array
    {
        if (empty($this->defaultRouter)) {
            $url_config = $this->config->get('url');
            $this->parseDefaultRouter($url_config['*']);
        }

        return $this->defaultRouter;
    }

    /**
     * 返回控制器名称
     *
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * 返回action名称
     *
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * 返回参数
     *
     * @return mixed
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * 按类型解析请求字符串
     *
     * @param string $prefix
     * @param array $url_config
     * @param bool $convert_html_entities
     * @return string
     */
    public function getUriRequest(string $prefix = '/', &$url_config = [], bool $convert_html_entities = true): string
    {
        $url_config = $this->config->get('url');
        if (!empty($this->uriRequest)) {
            return $this->uriRequest;
        }

        $uriRequest = Request::getInstance()->getPathInfo();
        $this->originUriRequest = $uriRequest;
        if ($uriRequest) {
            $uriRequest = urldecode(ltrim($uriRequest, '/'));
            if ($convert_html_entities) {
                $uriRequest = htmlspecialchars($uriRequest, ENT_QUOTES);
            }
        }

        $this->uriRequest = $prefix . $uriRequest;
        return $this->uriRequest;
    }

    /**
     * 设置请求字符串
     *
     * @param string $uriRequest
     */
    public function setUrlRequest(string $uriRequest): void
    {
        $this->uriRequest = urldecode(ltrim($uriRequest, '/'));
        $this->originUriRequest = $uriRequest;
    }

    /**
     * 解析router别名配置
     *
     * @param array $request
     * @param array $url_config
     * @internal param $router
     */
    function parseRouter(array $request, array $url_config): void
    {
        $virtual_path = '';
        $set_virtual_path = &$url_config['virtual_path'];
        if (!empty($set_virtual_path) && $set_virtual_path == $request[0]) {
            $virtual_path = array_shift($request);
        }

        $combine_alias_key = '';
        $ori_controller = $controller = array_shift($request);
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

        $addition_params = [];
        $params = $this->parseParams($request, $url_config, $addition_params);
        $this->config->set('ori_router', [
            'request' => $this->originUriRequest,
            'addition_params' => $addition_params,
            'virtual_path' => $virtual_path,
            'controller' => $ori_controller,
            'action' => $ori_action,
            'params' => $request,
        ]);

        $this->setController($controller);
        $this->setAction($action);
        $this->setParams($params);
    }

    /**
     * 设置controller
     *
     * @param $controller
     */
    function setController(string $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * 设置Action
     *
     * @param $action
     */
    function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * 设置参数
     *
     * @param $params
     */
    function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * 将字符串参数解析成数组
     *
     * @param string $query_string
     * @param array $url_config
     * @return array
     * @throws FrontException
     */
    private static function parseRequestString(string $query_string, array $url_config): array
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
            $router_params = [];
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
     * 解析默认控制器和方法
     *
     * @param string $default_router
     * @return array
     * @throws CoreException
     */
    private function parseDefaultRouter(string $default_router): array
    {
        if (empty($default_router)) {
            throw new CoreException('Undefined default router!');
        }

        if (empty($this->defaultRouter)) {
            if (false !== strpos($default_router, ':')) {
                list($controller, $action) = explode(':', $default_router);
            } else {
                $controller = $default_router;
                $action = self::DEFAULT_ACTION;
            }

            $this->defaultRouter = [$controller, $action];
        }

        return $this->defaultRouter;
    }

    /**
     * 解析参数并处理附加参数
     *
     * @param array $params
     * @param array $url_config
     * @param array $addition_params
     * @return array
     */
    private function parseParams(array $params, array $url_config, array &$addition_params = array()): array
    {
        $addition_params = $_GET;
        if (empty($params)) {
            $params = $addition_params;
        } elseif (is_array($params) && !empty($addition_params)) {
            if ($url_config['type'] == 2) {
                $params = array_merge($params, $addition_params);
            } else {
                $params += $addition_params;
            }
        }

        return $params;
    }
}

