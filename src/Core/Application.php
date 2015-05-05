<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.2.0
 */
namespace Cross\Core;

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
     * 注入对象数组
     *
     * @var array
     */
    protected static $di;

    /**
     * 注入对象的实例数组
     *
     * @var array
     */
    protected static $dii;

    /**
     * action 名称
     *
     * @var string
     */
    protected static $class_action;

    /**
     * 运行时的参数
     *
     * @var mixed
     */
    protected static $class_params;

    /**
     * 控制器名称
     *
     * @var string
     */
    protected static $controller_class_name;

    /**
     * app配置
     *
     * @var CrossArray
     */
    protected static $app_config;

    /**
     * action 注释
     *
     * @var string
     */
    private static $action_annotate;

    /**
     * 控制器注释配置
     *
     * @var array
     */
    private static $controller_annotate_config = array();

    /**
     * 实例化Application
     *
     * @param Config $app_config
     * @param array $di
     * @return Application
     */
    final public static function initialization($app_config, $di = array())
    {
        self::setDi($di);
        self::setConfig($app_config);
        return new Application();
    }

    /**
     * 解析router
     *
     * @param Router|string|array $router
     * @param string $args 当$router类型为string时,指定参数
     * @return array
     */
    private function getRouter($router, $args)
    {
        $controller = '';
        $action = '';
        $params = '';

        if (is_object($router)) {

            $controller = $router->getController();
            $action = $router->getAction();
            $params = $router->getParams();

        } elseif (is_array($router)) {

            $controller = $router['controller'];
            $action = $router['action'];
            $params = $router['params'];

        } elseif (is_string($router)) {

            if (strpos($router, ':')) {
                list($controller, $action) = explode(':', $router);
            } else {
                $controller = $router;
                $action = 'index';
            }

            $params = $args;
        }

        return array('controller' => ucfirst($controller), 'action' => $action, 'params' => $params,);
    }

    /**
     * 获取控制器的命名空间
     *
     * @return string
     */
    protected function getControllerNamespace()
    {
        return 'app\\' . $this->getConfig()->get('app', 'name') . '\\controllers\\' . $this->getController();
    }

    /**
     * 初始化控制器
     *
     * @param string $controller 控制器
     * @param string $action 动作
     * @throws CoreException
     */
    private function initController($controller, $action = null)
    {
        $this->setController($controller);

        $controllerSpace = $this->getControllerNamespace();
        $controllerRealFile = PROJECT_REAL_PATH . str_replace('\\', DIRECTORY_SEPARATOR, $controllerSpace) . '.php';

        if (!file_exists($controllerRealFile)) {
            throw new CoreException("{$controllerSpace} 控制器不存在");
        }

        $class_reflection = new ReflectionClass($controllerSpace);
        if ($class_reflection->isAbstract()) {
            throw new CoreException("{$controllerSpace} 不允许访问的控制器");
        }

        //控制器全局注释配置(不检测父类注释配置)
        $controller_annotate = $class_reflection->getDocComment();
        if ($controller_annotate) {
            self::$controller_annotate_config = Annotate::getInstance($controller_annotate)->parse();
        }

        if ($action) {
            try {
                $is_callable = new ReflectionMethod($controllerSpace, $action);
            } catch (Exception $e) {
                try {
                    new ReflectionMethod($controllerSpace, '__call');
                    $this->setAction($action);
                    return;
                } catch (Exception $e) {
                    throw new CoreException("{$controllerSpace}->{$action} 不能解析的请求");
                }
            }

            if (isset($is_callable) && $is_callable->isPublic() && true !== $is_callable->isAbstract()) {
                $this->setAction($action);
                self::setActionAnnotate($is_callable->getDocComment());
            } else {
                throw new CoreException("{$controllerSpace}->{$action} 不允许访问的方法");
            }
        }
    }

    /**
     * 初始化参数
     *
     * @param $params
     * @param array $annotate_params
     */
    private function initParams($params, $annotate_params = array())
    {
        $this->setParams($params, $annotate_params);
    }

    /**
     * 运行框架
     *
     * @param object|string $router 要解析的理由
     * @param null $args 指定参数
     * @param bool $run_controller 是否只返回控制器实例
     * @param bool $return_response_content 是输出还是直接返回结果
     * @return array|mixed|string
     * @throws CoreException
     */
    public function dispatcher($router, $args = null, $run_controller = true, $return_response_content = false)
    {
        $router = $this->getRouter($router, $args);
        $action = $run_controller ? $router ['action'] : null;
        $this->initController($router ['controller'], $action);

        $action_config = self::getActionConfig();
        $action_params = array();
        if (isset($action_config['params'])) {
            $action_params = $action_config['params'];
        }
        $this->initParams($router ['params'], $action_params);

        $cache = false;
        if (isset($action_config['cache']) && Request::getInstance()->isGetRequest()) {
            $cache = $this->initRequestCache($action_config['cache']);
        }

        if (isset($action_config['before'])) {
            $this->getClassInstanceByName($action_config['before']);
        }

        if (!empty($action_config['basicAuth'])) {
            Response::getInstance()->basicAuth($action_config['basicAuth']);
        }

        if ($cache && $cache->getExpireTime()) {
            $response_content = $cache->get();
        } else {
            $action = $this->getAction();
            $full_class_name = $this->getControllerNamespace();
            $controller = new $full_class_name();

            if (Response::getInstance()->isEndFlush()) {
                return true;
            }

            if (true === $run_controller) {
                ob_start();
                $response_content = $controller->$action();
                if (!$response_content) {
                    $response_content = ob_get_contents();
                }
                ob_end_clean();
                if ($cache) {
                    $cache->set(null, $response_content);
                }
            } else {
                return $controller;
            }
        }

        if (!empty($action_config['response'])) {
            $this->setResponseConfig($action_config['response']);
        }

        if ($return_response_content) {
            return $response_content;
        } else {
            Response::getInstance()->display($response_content);
        }

        if (isset($action_config['after'])) {
            $this->getClassInstanceByName($action_config['after']);
        }

        return true;
    }

    /**
     * 设置config
     *
     * @param array|object $config 配置
     */
    private static function setConfig($config)
    {
        self::$app_config = $config;
    }

    /**
     * 设置di
     *
     * @param $di
     */
    private static function setDi($di)
    {
        self::$di = $di;
    }

    /**
     * 设置controller
     *
     * @param $controller
     */
    private function setController($controller)
    {
        self::$controller_class_name = $controller;
    }

    /**
     * 设置action
     *
     * @param $action
     */
    private function setAction($action)
    {
        self::$class_action = $action;
    }

    /**
     * 设置params
     *
     * @param null $url_params
     * @param array $annotate_params
     */
    private function setParams($url_params = null, $annotate_params = array())
    {
        $url_type = $this->getConfig()->get('url', 'type');
        switch ($url_type) {
            case 1:
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
                break;
        }

        self::$class_params = $params;
    }

    /**
     * 合并参数注释配置
     *
     * @param null $params
     * @param array $annotate_params
     * @return array|null
     */
    public static function combineParamsAnnotateConfig($params = null, $annotate_params = array())
    {
        if (empty($annotate_params)) {
            return $params;
        }

        if (!empty($params)) {
            $params_set = array();
            foreach ($params as $k => $p) {
                if (isset($annotate_params[$k])) {
                    $params_set [$annotate_params[$k]] = $p;
                } else {
                    $params_set [] = $p;
                }
            }
            $params = $params_set;
        }

        return $params;
    }

    /**
     * 字符类型的参数转换为一个关联数组
     *
     * @param $stringParams
     * @return array
     */
    public static function stringParamsToAssociativeArray($stringParams)
    {
        $paramsArray = explode(self::$app_config->get('url', 'dot'), $stringParams);
        return self::oneDimensionalToAssociativeArray($paramsArray);
    }

    /**
     * 一维数组按顺序转换为关联数组
     *
     * @param array $oneDimensional
     * @return array
     */
    public static function oneDimensionalToAssociativeArray($oneDimensional)
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
     * 初始化request cache
     *
     * @param $request_cache_config
     * @return bool|\cross\cache\FileCache|\cross\cache\MemcacheBase|\cross\cache\RedisCache|\cross\cache\RequestMemcache|\cross\cache\RequestRedisCache
     * @throws \cross\exception\CoreException
     */
    private function initRequestCache($request_cache_config)
    {
        if (!is_array($request_cache_config) || count($request_cache_config) != 2) {
            throw new CoreException('Request Cache配置为一个二维数组');
        }

        list($cache_enable, $cache_config) = $request_cache_config;
        if (!$cache_enable) {
            return false;
        }

        if (empty($cache_config['type'])) {
            throw new CoreException('请指定Cache类型');
        }

        $display = self::getConfig()->get('sys', 'display');
        Response::getInstance()->setContentType($display);
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
            'app_name' => $this->getConfig()->get('app', 'name'),
            'controller' => strtolower($this->getController()),
            'action' => $this->getAction(),
        );

        $params = self::getParams();
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
        return RequestCache::factory($cache_config);
    }

    /**
     * 设置Response
     *
     * @param $config
     */
    private function setResponseConfig($config)
    {
        if (isset($config['content_type'])) {
            Response::getInstance()->setContentType($config['content_type']);
        }

        if (isset($config['status'])) {
            Response::getInstance()->setResponseStatus($config['status']);
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
     * @param string $annotate
     */
    private static function setActionAnnotate($annotate)
    {
        self::$action_annotate = $annotate;
    }

    /**
     * 获取action注释
     *
     * @return string
     */
    private static function getActionAnnotate()
    {
        return self::$action_annotate;
    }

    /**
     * 获取action注释配置
     *
     * @return array|bool
     */
    public static function getActionConfig()
    {
        $action_annotate_config = Annotate::getInstance(self::getActionAnnotate())->parse();
        if (empty(self::$controller_annotate_config)) {
            return $action_annotate_config;
        }

        if (is_array($action_annotate_config)) {
            return array_merge(self::$controller_annotate_config, $action_annotate_config);
        }

        return self::$controller_annotate_config;
    }

    /**
     * 获取配置
     *
     * @return Config
     */
    public static function getConfig()
    {
        return self::$app_config;
    }

    /**
     * 返回一个注入对象的实例
     *
     * @param string $name
     * @param array $params
     * @return mixed
     * @throws CoreException
     */
    function getDi($name, $params = array())
    {
        if (isset(self::$di[$name])) {
            return call_user_func_array(self::$di[$name], $params);
        }
        throw new CoreException("未定义的依赖 {$name}");
    }

    /**
     * 以单例的方式实例化注入对象
     *
     * @param string $name
     * @param array $params
     * @return mixed
     * @throws CoreException
     */
    function getDii($name, $params = array())
    {
        if (isset(self::$dii[$name])) {
            return self::$dii[$name];
        } elseif (isset(self::$di[$name])) {
            self::$dii[$name] = call_user_func_array(self::$di[$name], $params);
            return self::$dii[$name];
        }
        throw new CoreException("未定义的依赖 {$name}");
    }

    /**
     * 获取控制器名称
     *
     * @return mixed
     */
    public static function getController()
    {
        return self::$controller_class_name;
    }

    /**
     * 获取action名称
     *
     * @return string
     */
    public static function getAction()
    {
        return self::$class_action;
    }

    /**
     * 获取参数
     *
     * @return mixed
     */
    public static function getParams()
    {
        return self::$class_params;
    }

}

