<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.1
 */
namespace Cross\Core;

use Cross\Cache\RequestCache;
use Cross\Exception\CoreException;
use Exception;
use ReflectionMethod;
use ReflectionProperty;

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
     * 实例化Application
     *
     * @param Config $app_config
     * @return Application
     */
    final public static function initialization($app_config)
    {
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

        if ($action) {
            try {

                $is_callable = new ReflectionMethod($controllerSpace, $action);

            } catch (Exception $e) {

                try {
                    //控制中是否使用__call处理action
                    new ReflectionMethod($controllerSpace, '__call');
                    $this->setAction($action);

                    return;

                } catch (Exception $e) {

                    try {
                        //是否使用public static $_act_alias_ 指定action别名
                        $_property = new ReflectionProperty($controllerSpace, '_act_alias_');

                    } catch (Exception $e) {
                        throw new CoreException("不能识别的请求: {$controllerSpace}->{$action}");
                    }

                    $act_alias = $_property->getValue();
                    if (isset($act_alias [$action])) {
                        $action = $act_alias [$action];
                        $is_callable = new ReflectionMethod($controllerSpace, $action);
                    } else {
                        throw new CoreException("未指定的方法: {$controllerSpace}->{$action}");
                    }
                }
            }

            if ($is_callable->isPublic() && true !== $is_callable->isAbstract()) {
                $this->setAction($action);
                self::setActionAnnotate($is_callable->getDocComment());
            } else {
                throw new CoreException('不允许访问的方法');
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
        if (isset($action_config['cache'])) {
            $cache = $this->initRequestCache($action_config['cache']);
        }

        if (isset($action_config['before'])) {
            $this->getClassInstanceByName($action_config['before']);
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
                if(! $response_content) {
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

        if (! empty($action_config['response'])) {
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

        if (!isset($cache_config ['cache_path'])) {
            $cache_config ['cache_path'] = PROJECT_REAL_PATH . 'cache' . DIRECTORY_SEPARATOR . 'request';
        }

        if (!isset($cache_config ['file_ext'])) {
            $cache_config ['file_ext'] = '.html';
        }

        if (!isset($cache_config ['key'])) {
            $cache_config ['key_dot'] = DIRECTORY_SEPARATOR;
            $cache_key_conf = array(
                $this->getConfig()->get('app', 'name'),
                strtolower($this->getController()),
                $this->getAction()
            );

            $params = self::getParams();
            if (!empty($params)) {
                $cache_key_conf[] = md5(implode($cache_config ['key_dot'], $params));
            }
            $cache_key = implode($cache_config ['key_dot'], $cache_key_conf);
            $cache_config['key'] = $cache_key;
        }

        return RequestCache::factory($cache_config);
    }

    /**
     * 设置Response
     *
     * @param $config
     */
    private function setResponseConfig($config)
    {
        if (! empty($config['basic_auth'])) {
            Response::getInstance()->basicAuth($config['basic_auth']);
        }

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
        if (! is_array($class_name)) {
            $class_array = array($class_name);
        } else {
            $class_array = $class_name;
        }

        foreach($class_array as $class) {
            try {
                if (is_string($class)) {
                    $obj = new \ReflectionClass($class);
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
        return Annotate::getInstance(self::getActionAnnotate())->parse();
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

