<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Core;

use Cross\I\RequestCacheInterface;
use Cross\I\RouterInterface;

use Cross\Exception\CoreException;

use Cross\Cache\Driver\FileCacheDriver;
use Cross\Cache\Request\Memcache;
use Cross\Cache\Request\RedisCache;
use Cross\Cache\RequestCache;

use ReflectionException;
use ReflectionProperty;
use ReflectionMethod;
use ReflectionClass;
use Exception;
use Closure;

/**
 * @author wonli <wonli@live.com>
 * Class Application
 * @package Cross\Core
 */
class Application
{
    /**
     * action 注释
     *
     * @var string
     */
    private $actionAnnotate;

    /**
     * @var Delegate
     */
    private $delegate;

    /**
     * 实例化Application
     *
     * @param Delegate $delegate
     */
    function __construct(Delegate $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * 运行框架
     *
     * @param object|string $router
     * @param array|string $args 指定参数
     * @param bool $returnResponseContent 是否输出执行结果
     * @return array|mixed|string
     * @throws CoreException
     */
    public function dispatcher($router, $args = [], bool $returnResponseContent = false)
    {
        $initPrams = true;
        $router = $this->parseRouter($router, $args, $initPrams);
        $cr = $this->initController($router['controller'], $router['action']);

        $closureContainer = $this->delegate->getClosureContainer();
        $annotateConfig = $this->getAnnotateConfig();

        $actionParams = [];
        if (isset($annotateConfig['params'])) {
            $actionParams = &$annotateConfig['params'];
        }

        if ($initPrams) {
            $this->initParams($router['params'], $actionParams);
        } elseif (is_array($router['params'])) {
            $params = $router['params'] + $actionParams;
            $this->updateRouterParams($params);
        } else {
            $this->updateRouterParams($router['params']);
        }

        $closureContainer->run('dispatcher');

        $Request = $this->delegate->getRequest();
        $Response = $this->delegate->getResponse();
        if (!empty($annotateConfig['basicAuth'])) {
            $Response->basicAuth($annotateConfig['basicAuth'], $Request->server('PHP_AUTH_USER'), $Request->server('PHP_AUTH_PW'));
        }

        $cache = false;
        if (isset($annotateConfig['cache'])) {
            $cache = $this->initRequestCache($annotateConfig['cache'], $actionParams);
        }

        $hasResponse = false;
        if ($cache && $cache->isValid()) {
            $responseContent = $cache->get();
            $Response->setContent($responseContent);
        } else {
            try {
                $cr->setStaticPropertyValue('appDelegate', $this->delegate);
            } catch (Exception $e) {
                throw new CoreException($e->getMessage());
            }

            $controller = $cr->newInstance();
            $dii = $this->delegate->dii();
            if (!empty($dii)) {
                try {
                    $properties = $cr->getProperties(ReflectionProperty::IS_PUBLIC);
                    foreach ($properties as $p) {
                        if (isset($dii[$p->name])) {
                            $cr->getProperty($p->name)
                                ->setValue($controller, $dii[$p->name]);
                        }
                    }
                } catch (ReflectionException $e) {
                    throw new CoreException($e->getMessage());
                }
            }

            if (isset($annotateConfig['before'])) {
                $this->callReliesControllerClosure($annotateConfig['before'], $controller);
            }

            $hasResponse = $Response->isEndFlush();
            if (!$hasResponse) {
                $action = $this->delegate->getRouter()->getAction();
                ob_start();
                $ctx = call_user_func([$controller, $action]);
                $obContent = ob_get_clean();
                if (empty($Response->getContent())) {
                    if (null !== $ctx) {
                        $Response->setRawContent($ctx);
                    } elseif (!empty($obContent)) {
                        $Response->setRawContent($obContent);
                    }
                }
            }

            $responseContent = $Response->getContent();
            if ($cache) {
                $cache->set($responseContent);
            }
        }

        if (!empty($annotateConfig['response'])) {
            $this->setResponseConfig($annotateConfig['response']);
        }

        if ($returnResponseContent) {
            return $responseContent;
        } elseif (false === $hasResponse) {
            $Response->setEndFlush(false)->end();
        }

        if (isset($annotateConfig['after']) && isset($controller)) {
            $this->callReliesControllerClosure($annotateConfig['after'], $controller);
        }

        return true;
    }

    /**
     * 设置params
     *
     * @param array|string $params
     */
    function updateRouterParams($params): void
    {
        $paramsChecker = $this->delegate->getClosureContainer()->has('setParams', $closure);
        if ($paramsChecker && is_array($params)) {
            array_walk($params, $closure);
        } elseif ($paramsChecker) {
            call_user_func($closure, $params);
        }

        $this->delegate->getRouter()->setParams($params);
    }

    /**
     * 获取action注释配置
     *
     * @return array|bool
     */
    function getAnnotateConfig()
    {
        return $this->actionAnnotate;
    }

    /**
     * 获取控制器的命名空间
     *
     * @param string $controllerName
     * @return string
     */
    function getControllerNamespace(string $controllerName): string
    {
        return $this->delegate->getAppNamespace() . '\\controllers\\' . $controllerName;
    }

    /**
     * 默认的视图控制器命名空间
     *
     * @param string $controllerName
     * @return string
     */
    function getViewControllerNameSpace(string $controllerName): string
    {
        return $this->delegate->getAppNamespace() . '\\views\\' . $controllerName . 'View';
    }

    /**
     * 实例化内部类
     * <pre>
     * 判断类中是否包含静态成员变量app_delegate并赋值
     * 主要用于实例化Cross\MVC\Module, Cross\MVC\View命名空间下的派生类
     * 不能实例化控制器, 实例化控制器请调用本类中的get()方法
     * </pre>
     *
     * @param string $class 类名或命名空间
     * @param array $args
     * @return object|bool
     */
    public function instanceClass(string $class, $args = [])
    {
        try {
            $rc = new ReflectionClass($class);

            if ($rc->hasProperty('app_delegate')) {
                $rc->setStaticPropertyValue('app_delegate', $this->delegate);
            }

            if ($rc->hasMethod('__construct')) {
                if (!is_array($args)) {
                    $args = [$args];
                }

                return $rc->newInstanceArgs($args);
            }

            return $rc->newInstance();
        } catch (ReflectionException $e) {
            return false;
        }
    }

    /**
     * 合并参数注释配置
     *
     * @param array $params
     * @param array $annotateParams
     * @param int $opMode 处理参数的方式
     * @return array
     */
    public static function combineParamsAnnotateConfig(array $params = [], array $annotateParams = [], int $opMode = 1): array
    {
        if (empty($params)) {
            return $annotateParams;
        }

        if (!empty($annotateParams)) {
            $paramsSet = [];
            foreach ($annotateParams as $paramsName => $defaultValue) {
                if ($opMode == 1) {
                    $paramsValue = array_shift($params);
                } else {
                    if (isset($params[$paramsName])) {
                        $paramsValue = $params[$paramsName];
                    } else {
                        $paramsValue = $defaultValue;
                    }
                }

                if ($paramsValue != '') {
                    $paramsSet[$paramsName] = $paramsValue;
                } else {
                    $paramsSet[$paramsName] = $defaultValue;
                }
            }
            return $paramsSet;
        }

        return $params;
    }

    /**
     * 字符类型的参数转换为一个关联数组
     *
     * @param string $stringParams
     * @param string $separator
     * @return array
     */
    public static function stringParamsToAssociativeArray(string $stringParams, string $separator): array
    {
        return self::oneDimensionalToAssociativeArray(explode($separator, $stringParams));
    }

    /**
     * 一维数组按顺序转换为关联数组
     *
     * @param array $oneDimensional
     * @return array
     */
    public static function oneDimensionalToAssociativeArray(array $oneDimensional): array
    {
        $result = [];
        while ($p = array_shift($oneDimensional)) {
            $result[$p] = array_shift($oneDimensional);
        }

        return $result;
    }

    /**
     * 解析router
     * <pre>
     * router类型为字符串时, 第二个参数生效
     * 当router类型为数组或字符串时,dispatcher中不再调用initParams()
     * </pre>
     *
     * @param RouterInterface|string $router
     * @param array $params
     * @param bool $initParams
     * @return array
     */
    private function parseRouter($router, array $params = [], &$initParams = true): array
    {
        $afu = true;
        if ($router instanceof RouterInterface) {
            $controller = $router->getController();
            $action = $router->getAction();
            $params = $router->getParams();
        } elseif (is_array($router)) {
            $initParams = false;
            $controller = $router['controller'];
            $action = $router['action'];
        } else {
            $initParams = false;
            if (strpos($router, ':')) {
                list($controller, $action) = explode(':', $router);
            } else {
                $controller = $router;
                $action = Router::DEFAULT_ACTION;
            }

            if (false !== strpos($controller, '\\')) {
                $afu = false;
            }
        }

        return [
            'controller' => $afu ? ucfirst($controller) : $controller,
            'action' => $action,
            'params' => $params
        ];
    }

    /**
     * 初始化控制器
     *
     * @param string $controller 控制器
     * @param mixed $action 动作
     * @return ReflectionClass
     * @throws CoreException
     */
    private function initController(string $controller, $action = null): ReflectionClass
    {
        $controllerNamespace = $this->getControllerNamespace($controller);

        try {
            $classReflection = new ReflectionClass($controllerNamespace);
            if ($classReflection->isAbstract()) {
                throw new CoreException("{$controllerNamespace} 不允许访问的控制器");
            }
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }

        $this->delegate->getRouter()->setController($controller);
        //控制器类注释(不检测父类注释)
        $controllerAnnotate = [];
        $classAnnotateContent = $classReflection->getDocComment();
        if ($classAnnotateContent) {
            $controllerAnnotate = Annotate::getInstance($this->delegate)->parse($classAnnotateContent);
        }

        if ($action) {
            try {
                $isCallable = new ReflectionMethod($controllerNamespace, $action);
            } catch (Exception $e) {
                try {
                    $isCallable = new ReflectionMethod($controllerNamespace, '__call');
                } catch (Exception $e) {
                    throw new CoreException("{$controllerNamespace}->{$action} 不能解析的请求");
                }
            }

            if (isset($isCallable) && $isCallable->isPublic() && true !== $isCallable->isAbstract()) {
                $this->delegate->getRouter()->setAction($action);
                //获取Action的注释配置
                $this->setAnnotateConfig(Annotate::getInstance($this->delegate)->parse($isCallable->getDocComment()), $controllerAnnotate);
            } else {
                throw new CoreException("{$controllerNamespace}->{$action} 不允许访问的方法");
            }
        }

        return $classReflection;
    }

    /**
     * 初始化参数
     *
     * @param array|string $urlParams
     * @param array $annotateParams
     */
    private function initParams($urlParams, array $annotateParams = []): void
    {
        $urlType = $this->delegate->getConfig()->get('url', 'type');
        switch ($urlType) {
            case 1:
                $params = self::combineParamsAnnotateConfig($urlParams, $annotateParams);
                break;

            case 2:
                $urlParams = self::oneDimensionalToAssociativeArray($urlParams);
                if (!empty($annotateParams)) {
                    $params = self::combineParamsAnnotateConfig($urlParams, $annotateParams, 2);
                } else {
                    $params = $urlParams;
                }
                break;

            default:
                if (empty($urlParams)) {
                    $params = $annotateParams;
                } elseif (is_array($urlParams) && !empty($annotateParams)) {
                    $params = array_merge($annotateParams, $urlParams);
                } else {
                    $params = $urlParams;
                }
        }

        $this->updateRouterParams($params);
    }

    /**
     * 初始化请求缓存
     * <pre>
     * request_cache_config 共接受3个参数
     * 1 缓存开关
     * 2 缓存配置数组
     * 3 是否强制开启请求缓存(忽略HTTP请求类型检查)
     *
     * 请求类型验证优先级大于缓存开关
     * 注册匿名函数cpCache可以更灵活的控制请求缓存
     * </pre>
     *
     * @param array $requestCacheConfig
     * @param array $annotateParams
     * @return bool|FileCacheDriver|Memcache|RedisCache|RequestCacheInterface|object
     * @throws CoreException
     */
    private function initRequestCache(array $requestCacheConfig, array $annotateParams)
    {
        if (empty($requestCacheConfig[0])) {
            return false;
        }

        if (!isset($requestCacheConfig[1]) || !is_array($requestCacheConfig[1])) {
            throw new CoreException('请求缓存配置格式不正确');
        }

        if (empty($requestCacheConfig[2]) && !$this->delegate->getRequest()->isGetRequest()) {
            return false;
        }

        $displayType = $this->delegate->getConfig()->get('sys', 'display');
        $this->delegate->getResponse()->setContentType($displayType);

        $defaultCacheConfig = [
            'type' => 1,
            'expire_time' => 3600,
            'ignore_params' => false,
            'cache_path' => $this->delegate->getConfig()->get('path', 'cache') . 'request' . DIRECTORY_SEPARATOR,
            'key_dot' => DIRECTORY_SEPARATOR
        ];

        $cacheConfig = &$requestCacheConfig[1];
        foreach ($defaultCacheConfig as $defaultConfigKey => $defaultValue) {
            if (!isset($cacheConfig[$defaultConfigKey])) {
                $cacheConfig[$defaultConfigKey] = $defaultValue;
            }
        }

        $paramsCacheKey = '';
        $params = $this->delegate->getRouter()->getParams();
        if (!$cacheConfig['ignore_params'] && !empty($params)) {
            $paramsMember = &$params;
            if (!empty($annotateParams)) {
                foreach ($annotateParams as $k => &$v) {
                    if (isset($params[$k])) {
                        $v = $params[$k];
                    }
                }
                $paramsMember = $annotateParams;
            }

            $paramsCacheKey = md5(json_encode($paramsMember));
        }

        $cacheKey = [
            'app_name' => $this->delegate->getAppName(),
            'tpl_dir_name' => $this->delegate->getConfig()->get('sys', 'default_tpl_dir'),
            'controller' => lcfirst($this->delegate->getRouter()->getController()),
            'action' => $this->delegate->getRouter()->getAction()
        ];

        $cacheConfig['key'] = implode($cacheConfig['key_dot'], $cacheKey);
        if ($paramsCacheKey) {
            $cacheConfig['key'] .= '@' . $paramsCacheKey;
        }

        $closureContainer = $this->delegate->getClosureContainer();
        $hasCacheClosure = $closureContainer->has('cpCache');
        if ($hasCacheClosure) {
            $cacheConfig['params'] = $params;
            $cacheConfig['cache_key'] = $cacheKey;
            $cacheConfig['annotate_params'] = $annotateParams;
            $enableCache = $closureContainer->run('cpCache', [&$cacheConfig]);
            unset($cacheConfig['cache_key_config'], $cacheConfig['params'], $cacheConfig['annotate_params']);
        } else {
            $enableCache = $requestCacheConfig[0];
        }

        if ($enableCache) {
            return RequestCache::factory($cacheConfig['type'], $cacheConfig);
        }

        return false;
    }

    /**
     * 设置Response
     *
     * @param array $config
     */
    private function setResponseConfig(array $config): void
    {
        if (isset($config['content_type'])) {
            $this->delegate->getResponse()->setContentType($config['content_type']);
        }

        if (isset($config['status'])) {
            $this->delegate->getResponse()->setResponseStatus($config['status']);
        }
    }

    /**
     * 调用依赖控制器实例的匿名函数
     *
     * @param Closure $closure
     * @param object $controller 当前控制器实例
     */
    private function callReliesControllerClosure(Closure $closure, object $controller): void
    {
        $closure($controller);
    }

    /**
     * 设置action注释
     *
     * @param array $annotate
     * @param array $controllerAnnotate
     */
    private function setAnnotateConfig(array $annotate, array $controllerAnnotate): void
    {
        if (empty($controllerAnnotate)) {
            $this->actionAnnotate = $annotate;
        } else {
            $this->actionAnnotate = array_merge($controllerAnnotate, $annotate);
        }
    }
}

