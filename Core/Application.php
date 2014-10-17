<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.3
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
     * @var array
     */
    protected static $app_config;

    /**
     * action 注释
     *
     * @var string
     */
    private static $action_annotate;

    /**
     * 以单例模式运行
     *
     * @var object
     */
    private static $instance;

    private function __construct()
    {

    }

    /**
     * 实例化Application
     *
     * @param $app_config
     * @return Application
     */
    public static function initialization($app_config)
    {
        if (!isset(self::$instance)) {
            self::setConfig($app_config);
            self::$instance = new Application();
        }

        return self::$instance;
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

            $controller = $router["controller"];
            $action = $router["action"];
            $params = $router["params"];

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
     * 初始化request cache
     *
     * @param $request_cache_config
     * @return bool|\cross\cache\FileCache|\cross\cache\MemcacheBase|\cross\cache\RedisCache|\cross\cache\RequestMemcache|\cross\cache\RequestRedisCache
     * @throws \cross\exception\CoreException
     */
    function initRequestCache($request_cache_config)
    {
        if (!$request_cache_config) {
            return false;
        }

        list($open_cache, $cache_config) = $request_cache_config;
        if (!$open_cache) {
            return false;
        }

        if (empty($cache_config['type'])) {
            throw new CoreException('请用使用type项指定cache类型');
        }

        $app_name = $this->getConfig()->get("sys", "app_name");
        $cache_dot_config = array(1 => $this->getConfig()->get('url', 'dot'), 2 => '.', 3 => ':',);

        if (!isset($cache_config ['cache_path'])) {
            $cache_config ['cache_path'] = PROJECT_REAL_PATH . 'cache' . DS . 'html';
        }

        if (!isset($cache_config ['file_ext'])) {
            $cache_config ['file_ext'] = '.html';
        }

        if (!isset($cache_config ['key_dot'])) {
            if (isset($cache_dot_config[$cache_config['type']])) {
                $cache_config ['key_dot'] = $cache_dot_config[$cache_config['type']];
            } else {
                $cache_config ['key_dot'] = $this->getConfig()->get('url', 'dot');
            }
        }

        $cache_key_conf = array(
            $app_name,
            strtolower($this->getController()),
            $this->getAction(),
            md5(implode($cache_config ['key_dot'], $this->getParams()))
        );

        $cache_key = implode($cache_config ['key_dot'], $cache_key_conf);
        $cache_config['key'] = $cache_key;

        return RequestCache::factory($cache_config);
    }

    /**
     * 获取控制器的命名空间
     *
     * @return string
     */
    protected function getControllerNamespace()
    {
        return 'app\\' . APP_NAME . '\\controllers\\' . $this->getController();
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
        $app_sys_conf = $this->getConfig()->get('sys');
        $app_path = $app_sys_conf ['app_path'];
        $app_name = $app_sys_conf ['app_name'];

        $controller_file = implode(array($app_path, 'controllers', "{$controller}.php"), DS);
        if (!file_exists($controller_file)) {
            throw new CoreException("{$app_name}/controller/{$controller} 控制器不存在");
        }

        $this->setController($controller);
        $controllerSpace = $this->getControllerNamespace();

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
                throw new CoreException("不允许访问的方法");
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
     * @return array|mixed|string
     * @throws CoreException
     */
    public function dispatcher($router, $args = null, $run_controller = true)
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

        if ($cache && $cache->getExtime()) {
            $response = $cache->get();
        } else {
            $action = $this->getAction();
            $cfn = $this->getControllerNamespace();
            $cp = new $cfn();

            if (true === $run_controller) {
                ob_start();
                $cp->$action();
                $response = ob_get_clean();
                if ($cache) {
                    $cache->set(null, $response);
                }
            } else {
                return $cp;
            }
        }

        $content_type = Response::getInstance()->getContentType();
        if (!$content_type) {
            Response::getInstance()->setContentType($this->getConfig()->get('sys', 'display'));
        }

        Response::getInstance()->output($response);
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
        switch($url_type)
        {
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
    public static function stringParamsToAssociativeArray($stringParams) {
        $paramsArray = explode(self::$app_config->get('url', 'dot'), $stringParams);
        return self::oneDimensionalToAssociativeArray($paramsArray);
    }

    /**
     * 一维数组按顺序转换为关联数组
     *
     * @param array $oneDimensional
     * @return array
     */
    public static function oneDimensionalToAssociativeArray($oneDimensional) {
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

