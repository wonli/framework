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
     * 当前app名称
     *
     * @var string
     */
    private $app_name;

    /**
     * action 注释
     *
     * @var string
     */
    private $action_annotate;

    /**
     * 输出缓冲状态
     *
     * @var bool
     */
    private $ob_cache_status = true;

    /**
     * @var Delegate
     */
    private $delegate;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Router
     */
    private $router;

    /**
     * 实例化Application
     *
     * @param string $app_name
     * @param Delegate $delegate
     */
    function __construct(string $app_name, Delegate &$delegate)
    {
        $this->app_name = $app_name;
        $this->delegate = $delegate;
        $this->config = $delegate->getConfig();
        $this->router = $delegate->getRouter();
    }

    /**
     * 运行框架
     *
     * @param object|string $router
     * @param array|string $args 指定参数
     * @param bool $return_response_content 是否输出执行结果
     * @return array|mixed|string
     * @throws CoreException
     */
    public function dispatcher($router, $args = [], bool $return_response_content = false)
    {
        $init_prams = true;
        $router = $this->parseRouter($router, $args, $init_prams);
        $cr = $this->initController($router['controller'], $router['action']);

        $closureContainer = $this->delegate->getClosureContainer();
        $annotate_config = $this->getAnnotateConfig();

        $action_params = [];
        if (isset($annotate_config['params'])) {
            $action_params = &$annotate_config['params'];
        }

        if ($init_prams) {
            $this->initParams($router['params'], $action_params);
        } elseif (is_array($router['params'])) {
            $params = $router['params'] + $action_params;
            $this->updateRouterParams($params);
        } else {
            $this->updateRouterParams($router['params']);
        }

        $closureContainer->run('dispatcher');
        if (!empty($annotate_config['basicAuth'])) {
            $this->delegate->getResponse()->basicAuth($annotate_config['basicAuth']);
        }

        $cache = false;
        if (isset($annotate_config['cache'])) {
            $cache = $this->initRequestCache($annotate_config['cache'], $action_params);
        }

        $hasResponse = false;
        if ($cache && $cache->isValid()) {
            $response_content = $cache->get();
        } else {
            try {
                $cr->setStaticPropertyValue('app_delegate', $this->delegate);
            } catch (Exception $e) {
                throw new CoreException($e->getMessage());
            }

            $controller = $cr->newInstance();
            if ($this->delegate->getResponse()->isEndFlush()) {
                return true;
            }

            if (isset($annotate_config['before'])) {
                $this->callReliesControllerClosure($annotate_config['before'], $controller);
            }

            $response_content = $this->delegate->getResponse()->getContent();
            if (null !== $response_content && PHP_SAPI !== 'cli') {
                $hasResponse = true;
            } else {
                $action = $this->router->getAction();
                if ($this->ob_cache_status) {
                    ob_start();
                    $response_content = $controller->$action();
                    if (!$response_content) {
                        $response_content = ob_get_contents();
                    }
                    ob_end_clean();
                } else {
                    $response_content = $controller->$action();
                }
            }

            if ($cache) {
                $cache->set($response_content);
            }
        }

        if (!empty($annotate_config['response'])) {
            $this->setResponseConfig($annotate_config['response']);
        }

        if ($return_response_content) {
            return $response_content;
        } else if (false === $hasResponse) {
            $this->delegate->getResponse()->display($response_content);
        }

        if (isset($annotate_config['after']) && isset($controller)) {
            $this->callReliesControllerClosure($annotate_config['after'], $controller);
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

        $this->router->setParams($params);
    }

    /**
     * 设置控制器结果是否使用输出缓冲
     *
     * @param bool $status
     */
    public function setObStatus(bool $status): void
    {
        $this->ob_cache_status = $status;
    }

    /**
     * 获取action注释配置
     *
     * @return array|bool
     */
    function getAnnotateConfig()
    {
        return $this->action_annotate;
    }

    /**
     * 获取控制器的命名空间
     *
     * @param string $controller_name
     * @return string
     */
    function getControllerNamespace(string $controller_name): string
    {
        return 'app\\' . str_replace('/', '\\', $this->app_name) . '\\controllers\\' . $controller_name;
    }

    /**
     * 默认的视图控制器命名空间
     *
     * @param string $controller_name
     * @return string
     */
    function getViewControllerNameSpace(string $controller_name): string
    {
        return 'app\\' . str_replace('/', '\\', $this->app_name) . '\\views\\' . $controller_name . 'View';
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
     * @param array $annotate_params
     * @param int $op_mode 处理参数的方式
     * @return array
     */
    public static function combineParamsAnnotateConfig(array $params = [], array $annotate_params = [], int $op_mode = 1): array
    {
        if (empty($params)) {
            return $annotate_params;
        }

        if (!empty($annotate_params)) {
            $params_set = array();
            foreach ($annotate_params as $params_name => $default_value) {
                if ($op_mode == 1) {
                    $params_value = array_shift($params);
                } else {
                    if (isset($params[$params_name])) {
                        $params_value = $params[$params_name];
                    } else {
                        $params_value = $default_value;
                    }
                }

                if ($params_value != '') {
                    $params_set[$params_name] = $params_value;
                } else {
                    $params_set[$params_name] = $default_value;
                }
            }
            return $params_set;
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
     * @param bool $init_params
     * @return array
     */
    private function parseRouter($router, array $params = [], &$init_params = true): array
    {
        if ($router instanceof RouterInterface) {
            $controller = $router->getController();
            $action = $router->getAction();
            $params = $router->getParams();
        } elseif (is_array($router)) {
            $init_params = false;
            $controller = $router['controller'];
            $action = $router['action'];
        } else {
            $init_params = false;
            if (strpos($router, ':')) {
                list($controller, $action) = explode(':', $router);
            } else {
                $controller = $router;
                $action = Router::DEFAULT_ACTION;
            }
        }

        return ['controller' => ucfirst($controller), 'action' => $action, 'params' => $params];
    }

    /**
     * 初始化控制器
     *
     * @param string $controller 控制器
     * @param string $action 动作
     * @return ReflectionClass
     * @throws CoreException
     */
    private function initController(string $controller, $action = null): ReflectionClass
    {
        $controller_name_space = $this->getControllerNamespace($controller);

        try {
            $class_reflection = new ReflectionClass($controller_name_space);
            if ($class_reflection->isAbstract()) {
                throw new CoreException("{$controller_name_space} 不允许访问的控制器");
            }
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }

        $this->router->setController($controller);
        //控制器类注释(不检测父类注释)
        $controller_annotate = [];
        $class_annotate_content = $class_reflection->getDocComment();
        if ($class_annotate_content) {
            $controller_annotate = Annotate::getInstance($this->delegate)->parse($class_annotate_content);
        }

        if ($action) {
            try {
                $is_callable = new ReflectionMethod($controller_name_space, $action);
            } catch (Exception $e) {
                try {
                    $is_callable = new ReflectionMethod($controller_name_space, '__call');
                } catch (Exception $e) {
                    throw new CoreException("{$controller_name_space}->{$action} 不能解析的请求");
                }
            }

            if (isset($is_callable) && $is_callable->isPublic() && true !== $is_callable->isAbstract()) {
                $this->router->setAction($action);
                //获取Action的注释配置
                $this->setAnnotateConfig(Annotate::getInstance($this->delegate)->parse($is_callable->getDocComment()), $controller_annotate);
            } else {
                throw new CoreException("{$controller_name_space}->{$action} 不允许访问的方法");
            }
        }

        return $class_reflection;
    }

    /**
     * 初始化参数
     *
     * @param array|string $url_params
     * @param array $annotate_params
     */
    private function initParams($url_params, array $annotate_params = []): void
    {
        $url_type = $this->config->get('url', 'type');
        switch ($url_type) {
            case 1:
                $params = self::combineParamsAnnotateConfig($url_params, $annotate_params);
                break;

            case 2:
                $url_params = self::oneDimensionalToAssociativeArray($url_params);
                if (!empty($annotate_params)) {
                    $params = self::combineParamsAnnotateConfig($url_params, $annotate_params, 2);
                } else {
                    $params = $url_params;
                }
                break;

            default:
                if (empty($url_params)) {
                    $params = $annotate_params;
                } elseif (is_array($url_params) && !empty($annotate_params)) {
                    $params = array_merge($annotate_params, $url_params);
                } else {
                    $params = $url_params;
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
     * @param array $request_cache_config
     * @param array $annotate_params
     * @return bool|FileCacheDriver|Memcache|RedisCache|RequestCacheInterface|object
     * @throws CoreException
     */
    private function initRequestCache(array $request_cache_config, array $annotate_params)
    {
        if (empty($request_cache_config[0])) {
            return false;
        }

        if (!isset($request_cache_config[1]) || !is_array($request_cache_config[1])) {
            throw new CoreException('请求缓存配置格式不正确');
        }

        if (empty($request_cache_config[2]) && !$this->delegate->getRequest()->isGetRequest()) {
            return false;
        }

        $display_type = $this->config->get('sys', 'display');
        $this->delegate->getResponse()->setContentType($display_type);

        $default_cache_config = [
            'type' => 1,
            'expire_time' => 3600,
            'ignore_params' => false,
            'cache_path' => $this->config->get('path', 'cache') . 'request' . DIRECTORY_SEPARATOR,
            'key_dot' => DIRECTORY_SEPARATOR
        ];

        $cache_config = &$request_cache_config[1];
        foreach ($default_cache_config as $default_config_key => $default_value) {
            if (!isset($cache_config[$default_config_key])) {
                $cache_config[$default_config_key] = $default_value;
            }
        }

        $params_cache_key = '';
        $params = $this->router->getParams();
        if (!$cache_config['ignore_params'] && !empty($params)) {
            $params_member = &$params;
            if (!empty($annotate_params)) {
                foreach ($annotate_params as $k => &$v) {
                    if (isset($params[$k])) {
                        $v = $params[$k];
                    }
                }
                $params_member = $annotate_params;
            }

            $params_cache_key = md5(json_encode($params_member));
        }

        $cache_key = [
            'app_name' => $this->app_name,
            'tpl_dir_name' => $this->config->get('sys', 'default_tpl_dir'),
            'controller' => lcfirst($this->router->getController()),
            'action' => $this->router->getAction()
        ];

        $cache_config['key'] = implode($cache_config['key_dot'], $cache_key);
        if ($params_cache_key) {
            $cache_config['key'] .= '@' . $params_cache_key;
        }

        $closureContainer = $this->delegate->getClosureContainer();
        $has_cache_closure = $closureContainer->has('cpCache');
        if ($has_cache_closure) {
            $cache_config['params'] = $params;
            $cache_config['cache_key'] = $cache_key;
            $cache_config['annotate_params'] = $annotate_params;
            $enable_cache = $closureContainer->run('cpCache', [&$cache_config]);
            unset($cache_config['cache_key_config'], $cache_config['params'], $cache_config['annotate_params']);
        } else {
            $enable_cache = $request_cache_config[0];
        }

        if ($enable_cache) {
            return RequestCache::factory($cache_config['type'], $cache_config);
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
     * @param array $controller_annotate
     */
    private function setAnnotateConfig(array $annotate, array $controller_annotate): void
    {
        if (empty($controller_annotate)) {
            $this->action_annotate = $annotate;
        } else {
            $this->action_annotate = array_merge($controller_annotate, $annotate);
        }
    }
}

