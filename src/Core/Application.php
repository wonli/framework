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

        $annotate_config = $this->getAnnotateConfig();
        if ($init_prams) {
            $action_params = array();
            if (isset($annotate_config['params'])) {
                $action_params = $annotate_config['params'];
            }
            $this->initParams($router['params'], $action_params);
        } else {
            $this->setParams($router['params']);
        }

        $this->delegate->getClosureContainer()->run('dispatcher');
        $cache = false;
        if (isset($annotate_config['cache']) && $this->delegate->getRequest()->isGetRequest()) {
            $cache = $this->initRequestCache($annotate_config['cache']);
        }

        if (isset($annotate_config['before'])) {
            $this->getClassInstanceByName($annotate_config['before']);
        }

        if (!empty($annotate_config['basicAuth'])) {
            $this->delegate->getResponse()->basicAuth($annotate_config['basicAuth']);
        }

        if ($cache && $cache->getExpireTime()) {
            $response_content = $cache->get();
        } else {
            $action = $this->getAction();
            $controller_name = $this->getController();

            try {
                $cr->setStaticPropertyValue('action_annotate', $annotate_config);
                $cr->setStaticPropertyValue('view_controller_namespace', $this->getViewControllerNameSpace($controller_name));
                $cr->setStaticPropertyValue('controller_name', $controller_name);
                $cr->setStaticPropertyValue('call_action', $action);
                $cr->setStaticPropertyValue('url_params', $this->getParams());
                $cr->setStaticPropertyValue('app_delegate', $this->delegate);
                $controller = $cr->newInstance();
            } catch (Exception $e) {
                throw new CoreException($e->getMessage());
            }

            if ($this->delegate->getResponse()->isEndFlush()) {
                return true;
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

        if (isset($annotate_config['after'])) {
            $this->getClassInstanceByName($annotate_config['after']);
        }

        return true;
    }

    /**
     * 合并参数注释配置
     *
     * @param array $params
     * @param array $annotate_params
     * @return array
     */
    public static function combineParamsAnnotateConfig(array $params = array(), array $annotate_params = array())
    {
        if (empty($params)) {
            return $annotate_params;
        }

        if (!empty($annotate_params)) {
            $params_set = array();
            foreach ($annotate_params as $params_key => $default_value) {
                $params_value = array_shift($params);
                if ($params_value != '') {
                    $params_set[$params_key] = $params_value;
                } else {
                    $params_set[$params_key] = $default_value;
                }
            }
            unset($params);
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
        for ($max = count($oneDimensional), $i = 0; $i < $max; $i++) {
            if (isset($oneDimensional[$i]) && isset($oneDimensional[$i + 1])) {
                $result[$oneDimensional[$i]] = $oneDimensional[$i + 1];
            }
            array_shift($oneDimensional);
        }

        return $result;
    }

    /**
     * 解析router
     * <pre>
     * router类型为字符串时, 第二个参数生效, dispatcher中也不再调用initParams()方法
     * </pre>
     *
     * @param RouterInterface|string $router
     * @param array $args
     * @param bool $init_params
     * @return array
     */
    private function parseRouter($router, $args = array(), & $init_params = true)
    {
        $controller = '';
        $action = 'index';
        $params = array();

        if ($router instanceof RouterInterface) {
            $controller = $router->getController();
            $action = $router->getAction();
            $params = $router->getParams();
        } elseif (is_string($router)) {
            $init_params = false;
            if (strpos($router, ':')) {
                list($controller, $action) = explode(':', $router);
            } else {
                $controller = $router;
            }
            $params = $args;
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
            $controller_annotate = Annotate::getInstance($class_annotate_content)->parse();
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
                $this->setAnnotateConfig(Annotate::getInstance($is_callable->getDocComment())->parse(), $controller_annotate);
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
        $url_config = $this->config->get('url');
        //获取附加参数
        $combine_get_params = true;
        $reset_annotate_params = false;
        $router_addition_params = array();
        if (!empty($url_config['router_addition_params']) && is_array($url_config['router_addition_params'])) {
            $router_addition_params = $url_config['router_addition_params'];
            $reset_annotate_params = true;
        }

        switch ($url_config['type']) {
            case 1:
            case 5:
                if ($reset_annotate_params) {
                    $now_annotate_params = array();
                    foreach ($annotate_params as $key) {
                        if (!isset($router_addition_params[$key])) {
                            $now_annotate_params[] = $key;
                        }
                    }
                    $annotate_params = $now_annotate_params;
                }
                $params = self::combineParamsAnnotateConfig($url_params, $annotate_params);
                break;

            case 3:
            case 4:
                $params = self::oneDimensionalToAssociativeArray($url_params);
                if (empty($params)) {
                    $params = $url_params;
                }
                break;
            default:
                $params = $url_params;
                $combine_get_params = false;
                break;
        }

        if (empty($params)) {
            $current_params = $router_addition_params;
        } elseif (is_array($params)) {
            $current_params = array_merge($router_addition_params, $params);
        } else {
            $current_params = $params;
        }

        if ($combine_get_params) {
            $current_params += $_GET;
        }

        $this->setParams($current_params);
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
        $params_checker = $this->delegate->getClosureContainer()->isRegister('setParams', $closure);
        if ($params_checker && !empty($params)) {
            array_walk($params, $closure);
        }

        $this->params = $params;
    }

    /**
     * 初始化RequestCache
     *
     * @param array $request_cache_config
     * @return bool|FileCacheDriver|Memcache|RedisCache|RequestCacheInterface|object
     * @throws CoreException
     */
    private function initRequestCache(array $request_cache_config)
    {
        if (!isset($request_cache_config[1]) || !is_array($request_cache_config[1])) {
            throw new CoreException('Request Cache 配置格式不正确');
        }

        list($cache_enable, $cache_config) = $request_cache_config;
        if (!$cache_enable) {
            return false;
        }

        if (empty($cache_config['type'])) {
            throw new CoreException('请指定Cache类型');
        }

        $display = $this->config->get('sys', 'display');
        $this->delegate->getResponse()->setContentType($display);
        if (!isset($cache_config ['cache_path'])) {
            $cache_config ['cache_path'] = PROJECT_REAL_PATH . 'cache' . DIRECTORY_SEPARATOR . 'request';
        }

        if (!isset($cache_config ['file_ext'])) {
            $cache_config ['file_ext'] = '.' . strtolower($display);
        }

        if (!isset($cache_config['key_dot'])) {
            $cache_config ['key_dot'] = DIRECTORY_SEPARATOR;
        }

        $cache_key_conf = array(
            'app_name' => $this->app_name,
            'tpl_dir_name' => $this->config->get('sys', 'default_tpl_dir'),
            'controller' => strtolower($this->getController()),
            'action' => $this->getAction(),
        );

        $params = $this->getParams();
        if (isset($cache_config ['key'])) {
            if ($cache_config ['key'] instanceof Closure) {
                $cache_key = call_user_func_array($cache_config ['key'], array($cache_key_conf, $params));
            } else {
                $cache_key = $cache_config['key'];
            }

            if (empty($cache_key)) {
                throw new CoreException("缓存key不能为空");
            }
        } else {
            if (!empty($params)) {
                $cache_key_conf['params'] = md5(implode($cache_config ['key_dot'], $params));
            }
            $cache_key = implode($cache_config['key_dot'], $cache_key_conf);
        }

        $cache_config['key'] = $cache_key;
        return RequestCache::factory($cache_config['type'], $cache_config);
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
     * 实例化一个数组中指定的所有类
     *
     * @param string|array $class_name
     * @throws CoreException
     */
    private function getClassInstanceByName($class_name)
    {
        if (!is_array($class_name)) {
            $class_array = array($class_name);
        } else {
            $class_array = $class_name;
        }

        foreach ($class_array as $class) {
            try {
                if (is_string($class)) {
                    $obj = new ReflectionClass($class);
                    $obj->newInstance();
                }
            } catch (Exception $e) {
                throw new CoreException('初始化类失败');
            }
        }
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

