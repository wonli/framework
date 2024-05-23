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
    private string $action;

    /**
     * 控制器名称
     *
     * @var string
     */
    private string $controller;

    /**
     * url参数
     *
     * @var array
     */
    private array $params = [];

    /**
     * @var string
     */
    private string $uriRequest;

    /**
     * @var string
     */
    private string $originUriRequest;

    /**
     * @var Delegate
     */
    private Delegate $delegate;

    /**
     * @var array
     */
    private array $defaultRouter = [];

    /**
     * 虚拟路径
     *
     * @var string
     */
    private string $virtual = '';

    /**
     * 默认Action名称
     */
    const DEFAULT_ACTION = 'index';

    /**
     * Router constructor.
     * @param Delegate $delegate
     */
    function __construct(Delegate $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * Router
     *
     * @return $this
     * @throws CoreException
     * @throws FrontException
     */
    public function parseUrl(): self
    {
        $request = [];
        $urlConfig = [];
        $rs = $this->getUriRequest('', $urlConfig);
        if (!empty($rs)) {
            $request = $this->parseRequestString($rs, $urlConfig);
        }

        if ($this->delegate->onMultiAppMode()) {
            $multipleApp = $this->delegate->getConfig()->get('multipleApp');
            if (empty($multipleApp)) {
                throw new CoreException('Not find multipleApp config!');
            }

            if (!empty($request)) {
                $pathAppName = [];
                if (!empty($multipleApp['namespacePrefix'])) {
                    $pathAppName[] = $multipleApp['namespacePrefix'];
                }

                $level = $multipleApp['pathLevel'] ?? null;
                while ($level && $level-- > 0) {
                    $pathAppName[] = array_shift($request);
                }

                $appName = implode('\\', $pathAppName);
            } else {
                $appName = $multipleApp['default'] ?? '';
                if (empty($appName)) {
                    throw new CoreException('Not find defaultApp config');
                }
            }

            $this->delegate->setAppName($appName);
        }

        if (!empty($request)) {
            $closure = $this->delegate->getClosureContainer();
            if ($closure->has('router')) {
                $closure->run('router', [$request, $this]);
                if (empty($this->controller) || empty($this->action)) {
                    $this->parseRouter($request, $urlConfig);
                }
            } else {
                $this->parseRouter($request, $urlConfig);
            }
        } else {
            $router = $this->parseDefaultRouter($urlConfig['*']);
            $this->setController($router[0]);
            $this->setAction($router[1]);

            $params = $this->parseParams([], $urlConfig);
            $this->setParams($params);
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
            $urlConfig = $this->delegate->getConfig()->get('url');
            $this->parseDefaultRouter($urlConfig['*']);
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
     * @param array $urlConfig
     * @param bool $convertHtmlEntities
     * @return string
     */
    public function getUriRequest(string $prefix = '/', array &$urlConfig = [], bool $convertHtmlEntities = true): string
    {
        $urlConfig = $this->delegate->getConfig()->get('url');
        if (!empty($this->uriRequest)) {
            return $this->uriRequest;
        }

        $uriRequest = $this->delegate->getRequest()->getPathInfo();
        $this->originUriRequest = $uriRequest;
        if ($uriRequest) {
            $uriRequest = urldecode(ltrim($uriRequest, '/'));
            if ($convertHtmlEntities) {
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
     * @return $this
     */
    public function setUriRequest(string $uriRequest): self
    {
        $this->uriRequest = urldecode(ltrim($uriRequest, '/'));
        $this->originUriRequest = $uriRequest;
        return $this;
    }

    /**
     * 解析router别名配置
     *
     * @param array $request
     * @param array $urlConfig
     * @throws CoreException
     */
    function parseRouter(array $request, array $urlConfig): void
    {
        $virtual = '';
        $setVirtual = &$urlConfig['virtual'];
        if ($setVirtual == '*') {
            $virtual = array_shift($request);
        } elseif (!empty($setVirtual)) {
            $virtualConfigs = array_map('trim', explode(',', $setVirtual));
            if (in_array($request[0], $virtualConfigs)) {
                $virtual = array_shift($request);
            }
        }

        $group = '';
        $ctlGroups = &$urlConfig['group'];
        if (!empty($ctlGroups)) {
            $ctlGroups = array_map('trim', explode(',', $ctlGroups));
            if (in_array($request[0], $ctlGroups)) {
                $group = array_shift($request);
            }
        }

        $combineAliasKey = '';
        $oriController = $controller = array_shift($request);
        if (isset($request[0])) {
            $combineAliasKey = $controller . ':' . $request[0];
        }

        $controllerAlias = '';
        $routerConfig = $this->delegate->getConfig()->get('router');
        if (isset($routerConfig [$combineAliasKey])) {
            array_shift($request);
            $controllerAlias = $routerConfig [$combineAliasKey];
        } elseif (isset($routerConfig [$controller])) {
            $controllerAlias = $routerConfig [$controller];
        }

        if (!empty($controllerAlias)) {
            if (false !== strpos($controllerAlias, ':')) {
                list($controller, $action) = explode(':', $controllerAlias);
            } else {
                $controller = $controllerAlias;
            }
        }

        if ($group) {
            $controller = "{$group}\\{$controller}";
        }

        if (empty($controller)) {
            $defaultRouter = $this->getDefaultRouter();
            $controller = $defaultRouter[0];
        }

        $oriAction = '';
        if (!isset($action)) {
            if (isset($request[0]) && !empty($request[0])) {
                $oriAction = $action = array_shift($request);
            } else {
                $action = self::DEFAULT_ACTION;
            }
        }

        $additionParams = [];
        $params = $this->parseParams($request, $urlConfig, $additionParams);
        $this->delegate->getConfig()->set('ori_router', [
            'group' => $group,
            'request' => $this->originUriRequest,
            'virtual' => $virtual,
            'addition_params' => $additionParams,
            'controller' => $oriController,
            'action' => $oriAction,
            'params' => $request,
        ]);

        $this->setVirtual($virtual);
        $this->setController($controller);
        $this->setAction($action);
        $this->setParams($params);
    }

    /**
     * 处理路由别名
     *
     * @param string $name
     * @return string
     */
    function getRouterAlias(string $name): string
    {
        $routerConfig = $this->delegate->getConfig()->get('router');
        if (isset($routerConfig[$name])) {
            return $routerConfig[$name];
        }

        return $name;
    }

    /**
     * @return string
     */
    public function getVirtual(): string
    {
        return $this->virtual;
    }

    /**
     * @param string $virtual
     */
    public function setVirtual(string $virtual): void
    {
        $this->virtual = $virtual;
    }

    /**
     * 设置controller
     *
     * @param string $controller
     * @return void
     */
    function setController(string $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * 设置Action
     *
     * @param string $action
     * @return void
     */
    function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * 设置参数
     *
     * @param array $params
     */
    function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * 将字符串参数解析成数组
     *
     * @param string $queryString
     * @param array $urlConfig
     * @return array
     * @throws FrontException
     */
    private static function parseRequestString(string $queryString, array $urlConfig): array
    {
        $urlSuffix = &$urlConfig['ext'];
        if (isset($urlSuffix[0]) && $urlSuffix !== '/' && ($urlSuffixLength = strlen(trim($urlSuffix))) > 0) {
            if (0 === strcasecmp($urlSuffix, substr($queryString, -$urlSuffixLength))) {
                $queryString = substr($queryString, 0, -$urlSuffixLength);
            } else {
                throw new FrontException('Page not found !');
            }
        }

        $urlDot = &$urlConfig['dot'];
        if ($urlDot && false !== strpos($queryString, $urlDot)) {
            $routerParams = explode($urlDot, $queryString);
            $endParams = array_pop($routerParams);
        } else {
            $routerParams = [];
            $endParams = $queryString;
        }

        $paramsDot = &$urlConfig['params_dot'];
        if ($paramsDot && $paramsDot != $urlDot && false !== strpos($endParams, $paramsDot)) {
            $paramsData = explode($paramsDot, $endParams);
            foreach ($paramsData as $p) {
                $routerParams[] = $p;
            }
        } else {
            $routerParams[] = $endParams;
        }

        return $routerParams;
    }

    /**
     * 解析默认控制器和方法
     *
     * @param string $defaultRouter
     * @return array
     * @throws CoreException
     */
    private function parseDefaultRouter(string $defaultRouter): array
    {
        if (empty($defaultRouter)) {
            throw new CoreException('Undefined default router!');
        }

        if (empty($this->defaultRouter)) {
            if (str_contains($defaultRouter, ':')) {
                list($controller, $action) = explode(':', $defaultRouter);
            } else {
                $controller = $defaultRouter;
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
     * @param array $urlConfig
     * @param array $additionParams
     * @return array
     */
    private function parseParams(array $params, array $urlConfig, array &$additionParams = []): array
    {
        $additionParams = $this->delegate->getRequest()->getGetData();
        if (empty($params)) {
            $params = $additionParams;
        } elseif (!empty($additionParams)) {
            if ($urlConfig['type'] > 2) {
                $params = array_merge($params, $additionParams);
            } else {
                $params += $additionParams;
            }
        }

        return $params;
    }
}

