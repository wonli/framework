<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Core;

use Cross\I\RequestCacheInterface;
use Cross\I\RouterInterface;
use Cross\Cache\Driver\FileCacheDriver;
use Cross\Cache\Request\Memcache;
use Cross\Cache\Request\RedisCache;
use Cross\Cache\RequestCache;
use Cross\Exception\CoreException;
use ReflectionClass;
use ReflectionMethod;
use Exception;
use Closure;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Application
 * @package Cross\Core
 */
class Application
{
    /**
     * action 名称
     *
     * @var string
     */
    protected $action;

    /**
     * 运行时的参数
     *
     * @var mixed
     */
    protected $params;

    /**
     * 控制器名称
     *
     * @var string
     */
    protected $controller;

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
     * @var Delegate
     */
    private $delegate;

    /**
     * 实例化Application
     *
     * @param string $app_name
     * @param Delegate $delegate
     */
    function __construct($app_name, Delegate $delegate)
    {
        $this->app_name = $app_name;
        $this->delegate = $delegate;
        $this->config = $delegate->getConfig();
    }

    /**
     * 运行框架
     *
     * @param object|string $router
     * @param null $args 指定参数
     * @param bool $return_response_content 是否输出执行结果
     * @return array|mixed|string
     * @throws CoreException
     */
    public function dispatcher($router, $args = null, $return_response_content = false)
    {
        $init_prams = true;
        $router = $this->parseRouter($router, $args, $init_prams);
        $cr = $this->initController($router['controller'], $router['action']);

        $closureContainer = $this->delegate->getClosureContainer();
        $annotate_config = $this->getAnnotateConfig();

        $action_params = array();
        if (isset($annotate_config['params'])) {
            $action_params = $annotate_config['params'];
        }

        if ($init_prams) {
            $this->initParams($router['params'], $action_params);
        } else {
            $this->setParams($router['params']);
        }

        $closureContainer->run('dispatcher');

        $cache = false;
        if (isset($annotate_config['cache'])) {
            $cache = $this->initRequestCache($annotate_config['cache'], $action_params);
        }

        if (!empty($annotate_config['basicAuth'])) {
            $this->delegate->getResponse()->basicAuth($annotate_config['basicAuth']);
        }

        if ($cache && $cache->isValid()) {
            $response_content = $cache->get();
        } else {
            $action = $this->getAction();
            $controller_name = $this->getController();

            $runtime_config = array(
                'action_annotate' => $annotate_config,
                'view_controller_namespace' => $this->getViewControllerNameSpace($controller_name),
                'controller' => $controller_name,
                'action' => $action,
                'params' => $this->getParams(),
            );

            $closureContainer->add('~controller~runtime~', function () use ($runtime_config) {
                return $runtime_config;
            });

            try {
                $cr->setStaticPropertyValue('app_delegate', $this->delegate);
                $controller = $cr->newInstance();
            } catch (Exception $e) {
                throw new CoreException($e->getMessage());
            }

            if ($this->delegate->getResponse()->isEndFlush()) {
                return true;
            }

            if (isset($annotate_config['before'])) {
                $this->callReliesControllerClosure($annotate_config['before'], $controller);
            }

            ob_start();
            $response_content = $controller->$action();
            if (!$response_content) {
                $response_content = ob_get_contents();
            }
            ob_end_clean();
            if ($cache) {
                $cache->set(null, $response_content);
            }
        }

        if (!empty($annotate_config['response'])) {
            $this->setResponseConfig($annotate_config['response']);
        }

        if ($return_response_content) {
            return $response_content;
        } else {
            $this->delegate->getResponse()->display($response_content);
        }

        if (isset($annotate_config['after']) && isset($controller)) {
            $this->callReliesControllerClosure($annotate_config['after'], $controller);
        }

        return true;
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
     * @return object
     */
    public function instanceClass($class, $args = array())
    {
        $rc = new ReflectionClass($class);
        if ($rc->hasProperty('app_delegate')) {
            $rc->setStaticPropertyValue('app_delegate', $this->delegate);
        }

        if ($rc->hasMethod('__construct')) {
            if (!is_array($args)) {
                $args = array($args);
            }

            return $rc->newInstanceArgs($args);
        }

        return $rc->newInstance();
    }

    /**
     * 合并参数注释配置
     *
     * @param array $params
     * @param array $annotate_params
     * @param int $op_mode 处理参数的方式
     * @return array
     */
    public static function combineParamsAnnotateConfig(array $params = array(), array $annotate_params = array(), $op_mode = 1)
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
    public static function stringParamsToAssociativeArray($stringParams, $separator)
    {
        return self::oneDimensionalToAssociativeArray(explode($separator, $stringParams));
    }

    /**
     * 一维数组按顺序转换为关联数组
     *
     * @param array $oneDimensional
     * @return array
     */
    public static function oneDimensionalToAssociativeArray(array $oneDimensional)
    {
        $result = array();
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
    private function parseRouter($router, $params = array(), &$init_params = true)
    {
        if ($router instanceof RouterInterface) {
            $controller = $router->getController();
            $action = $router->getAction();
            $params = $router->getParams();
        } elseif (is_array($router)) {
            $init_params = false;
            $controller = $router['controller'];
            $action = $router['action'];
            $params = $router['params'];
        } else {
            $init_params = false;
            if (strpos($router, ':')) {
                list($controller, $action) = explode(':', $router);
            } else {
                $controller = $router;
                $action = Router::$default_action;
            }
        }

        return array('controller' => ucfirst($controller), 'action' => $action, 'params' => $params);
    }

    /**
     * 获取控制器的命名空间
     *
     * @param string $controller_name
     * @return string
     */
    protected function getControllerNamespace($controller_name)
    {
        return 'app\\' . $this->app_name . '\\controllers\\' . $controller_name;
    }

    /**
     * 默认的视图控制器命名空间
     *
     * @param string $controller_name
     * @return string
     */
    protected function getViewControllerNameSpace($controller_name)
    {
        return 'app\\' . $this->app_name . '\\views\\' . $controller_name . 'View';
    }

    /**
     * 初始化控制器
     *
     * @param string $controller 控制器
     * @param string $action 动作
     * @return ReflectionClass
     * @throws CoreException
     */
    private function initController($controller, $action = null)
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

        $this->setController($controller);
        //控制器全局注释配置(不检测父类注释配置)
        $controller_annotate = array();
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
                $this->setAction($action);
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
    private function initParams($url_params, array $annotate_params = array())
    {
        $url_type = $this->config->get('url', 'type');
        switch ($url_type) {
            case 1:
            case 5:
                $combined_params = self::combineParamsAnnotateConfig($url_params, $annotate_params);
                break;

            case 3:
            case 4:
                $url_params = self::oneDimensionalToAssociativeArray($url_params);
                break;
        }

        if (isset($combined_params)) {
            $params = $combined_params;
        } else if (!empty($annotate_params)) {
            $params = self::combineParamsAnnotateConfig($url_params, $annotate_params, 2);
        } else {
            $params = $url_params;
        }

        $this->setParams($params);
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
     * 设置action
     *
     * @param $action
     */
    private function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * 设置params
     *
     * @param array $params
     */
    private function setParams($params)
    {
        if (!empty($_GET)) {
            $url_type = $this->config->get('url', 'type');
            $addition_params = array_map('htmlentities', $_GET);
            if ($url_type == 2) {
                $params = array_merge($params, $addition_params);
            } else {
                $params += $addition_params;
            }
        }

        $params_checker = $this->delegate->getClosureContainer()->has('setParams', $closure);
        if ($params_checker && !empty($params)) {
            array_walk($params, $closure);
        }

        $this->params = $params;
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
     * @param array $action_annotate_params
     * @return bool|FileCacheDriver|Memcache|RedisCache|RequestCacheInterface|object
     * @throws CoreException
     */
    private function initRequestCache(array $request_cache_config, array $action_annotate_params)
    {
        if (!isset($request_cache_config[1]) || !is_array($request_cache_config[1])) {
            throw new CoreException('请求缓存配置格式不正确');
        }

        if (empty($request_cache_config[2]) && !$this->delegate->getRequest()->isGetRequest()) {
            return false;
        }

        $display_type = $this->config->get('sys', 'display');
        $this->delegate->getResponse()->setContentType($display_type);

        $default_cache_config = array(
            'type' => 1,
            'expire_time' => 3600,
            'limit_params' => false,
            'cache_path' => $this->config->get('path', 'cache') . 'request' . DIRECTORY_SEPARATOR,
            'key_suffix' => '',
            'key_dot' => DIRECTORY_SEPARATOR
        );

        $cache_config = $request_cache_config[1];
        foreach ($default_cache_config as $default_config_key => $default_value) {
            if (!isset($cache_config[$default_config_key])) {
                $cache_config[$default_config_key] = $default_value;
            }
        }

        $cache_key_config = $default_cache_key_config = array(
            'app_name' => $this->app_name,
            'tpl_dir_name' => $this->config->get('sys', 'default_tpl_dir'),
            'controller' => lcfirst($this->getController()),
            'action' => $this->getAction()
        );

        $params = $this->getParams();
        if ($cache_config['limit_params'] && !empty($action_annotate_params)) {
            $params_member = array();
            foreach ($params as $params_key => $params_value) {
                if (isset($action_annotate_params[$params_key])) {
                    $params_member[$params_key] = $params_value;
                }
            }
            $cache_key_config['params'] = implode($cache_config['key_dot'], $params_member);
        } else {
            $cache_key_config['params'] = md5(implode($cache_config['key_dot'], $params));
        }

        $cache_config['key'] = implode($cache_config['key_dot'], $cache_key_config) . $cache_config['key_suffix'];
        $closureContainer = $this->delegate->getClosureContainer();
        $has_cache_closure = $closureContainer->has('cpCache');
        if ($has_cache_closure) {
            $cache_config['params'] = $params;
            $cache_config['cache_key_config'] = $default_cache_key_config;
            $enable_cache = $closureContainer->run('cpCache', array(&$cache_config));
            unset($cache_config['cache_key_config'], $cache_config['params']);
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
    private function setResponseConfig(array $config)
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
     * @param $controller
     */
    private function callReliesControllerClosure(Closure $closure, $controller)
    {
        $closure($controller);
    }

    /**
     * 设置action注释
     *
     * @param array $annotate
     * @param array $controller_annotate
     */
    private function setAnnotateConfig(array $annotate, array $controller_annotate)
    {
        if (empty($controller_annotate)) {
            $this->action_annotate = $annotate;
        } else {
            $this->action_annotate = array_merge($controller_annotate, $annotate);
        }
    }

    /**
     * 获取action注释配置
     *
     * @return array|bool
     */
    private function getAnnotateConfig()
    {
        return $this->action_annotate;
    }

    /**
     * 获取控制器名称
     *
     * @return mixed
     */
    private function getController()
    {
        return $this->controller;
    }

    /**
     * 获取action名称
     *
     * @return string
     */
    private function getAction()
    {
        return $this->action;
    }

    /**
     * 获取参数
     *
     * @return mixed
     */
    private function getParams()
    {
        return $this->params;
    }
}

