<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:       wonli
 * @Version $Id: Dispatcher.php 162 2013-10-23 13:01:37Z ideaa $
 */
class Dispatcher
{
    /**
     * action 名称
     *
     * @var string
     */
    private static $action;

    /**
     * 运行时的参数
     *
     * @var mixed
     */
    private static $params;

    /**
     * app配置
     *
     * @var array
     */
    private static $appConfig;

    /**
     * 控制器名称
     *
     * @var string
     */
    private static $controller;

    /**
     * 以单例模式运行
     *
     * @var object
     */
    public static $instance;

    private function __construct($app_config)
    {

    }

    /**
     * 实例化dispatcher
     *
     * @param $app_name
     * @param $app_config
     * @return Dispatcher
     */
    public static function init( $app_name, $app_config )
    {
        self::init_config( $app_config );
        if(! isset(self::$instance [ $app_name ]) )
        {
            self::$instance [ $app_name ] = new Dispatcher( $app_config );
        }

        return self::$instance [ $app_name ];
    }

    /**
     * 解析router
     *
     * @param $router
     * @param $args 当$router类型为string时,指定参数
     * @return array
     */
    private function getRouter( $router, $args )
    {
        if( is_object($router) ) {
            $controller     = $router->getController();
            $action         = $router->getAction();
            $params         = $router->getParams();
        }

        if(is_array($router)) {
            $controller = $router["controller"];
            $action = $router["action"];
            $params = $router["params"];
        }

        if( is_string($router) ) {
            if(strpos($router,':')) {
                list($controller, $action) = explode(':', $router);
            } else {
                $controller = $router;
                $action     = 'index';
            }

            $params = $args;
        }

        return array(
            'controller' =>  ucfirst($controller),
            'action'     =>  $action,
            'params'     =>  $params,
        );
    }

    /**
     * 初始化请求cache
     *
     * @return bool|FileCache|Memcache|RedisCache
     */
    function init_request_cache( )
    {
        $controller_conf = $this->getConfig()->get('controller', strtolower(self::$controller));
        if(! isset($controller_conf['cache']))
        {
            return false;
        }

        $controller_cache_config = $controller_conf ['cache'];
        list($is_enable, $cache_config) = $controller_cache_config;
        if(! $is_enable)
        {
            return false;
        }

        $app_name = $this->getConfig()->get("sys", "app_name");
        $cache_dot_config = array(
            '1' =>  $this->getConfig()->get('url', 'dot'),
            '2' =>  '.',
            '3' =>  ':',
        );

        if(! isset($cache_config ['cache_path']))
        {
            $cache_config ['cache_path'] = DOCROOT.'cache'.DS.'html';
        }

        if(! isset($cache_config ['file_ext']))
        {
            $cache_config ['file_ext'] = '.html';
        }

        if(! isset($cache_config ['key_dot']))
        {
            $cache_config ['key_dot'] = $cache_dot_config[ $cache_config['type'] ];
        }

        $cache_key_conf = array(
            $app_name,
            strtolower($this->getController()),
            $this->getAction(),
            implode($cache_config ['key_dot'], $this->getParams())
        );
        $cache_key = implode($cache_config ['key_dot'], $cache_key_conf);
        $cache_config['key']   = $cache_key;

        return CoreCache::factory( $cache_config );
    }

    /**
     * 初始化控制器
     *
     * @param $controller 控制器
     * @param $action 动作
     * @throws CoreException
     */
    private function init_controller( $controller, $action=null )
    {
        $app_sys_conf = self::$appConfig->get('sys');
        $app_path = $app_sys_conf ['app_path'];
        $app_name = $app_sys_conf ['app_name'];

        $controller_file = implode(array($app_path,'controllers', "{$controller}.php"), DS);
        if(! file_exists($controller_file))
        {
            throw new CoreException("app:{$app_name} 控制器{$controller_file}不存在");
        }
        $this->setController( $controller );

        if($action)
        {
            //自动识别返回类型
            $_display_type = $app_sys_conf ['display'];
            if('AUTO' == $_display_type)
            {
                $_display = 'HTML';

                if( false !== strpos($action, ".") )
                {
                    list($action, $_display) = explode(".", strtolower($action));
                }

                self::$appConfig->set("sys", array("display"=>$_display));
            }

            try
            {
                /**
                 * 判断Controller是否手动处理action
                 */
                $have_call = new ReflectionMethod($controller, '__call');
                $this->setAction($action);

            } catch (Exception $e) {

                try
                {
                    #会触发autoLoad
                    $is_callable = new ReflectionMethod($controller, $action);

                } catch (Exception $e) {

                    #控制器静态属性_act_alias_指定action的别名
                    try
                    {
                        $_property = new ReflectionProperty($controller, '_act_alias_');
                    } catch (Exception $e) {
                        throw new CoreException("app:{$app_name}不能识别的请求{$controller}->{$action}");
                    }

                    $act_alias = $_property->getValue();
                    if( isset($act_alias [$action]) )
                    {
                        $_action = $act_alias [$action];
                    }

                    if(! empty($_action))
                    {
                        $is_callable = new ReflectionMethod($controller, $_action);
                    } else {
                        throw new CoreException("app::{$app_name}未指定的方法{$controller}->{$action}");
                    }
                }

                if( $is_callable->isPublic() )
                {
                    $this->setAction( $action );
                    if(! empty($_action))
                    {
                        $this->setAction($_action);
                    }
                } else {
                    throw new CoreException("不被允许访问的方法!");
                }
            }
        }
    }

    /**
     * 初始化参数
     *
     * @param $params
     */
    private function init_params( $params )
    {
        if(is_array($params))
        {
            if(self::$appConfig->get("url", "type") == 2)
            {
                $this->setParams( $params );
            }
            else
            {
                if( count($params) > 1 )
                {
                    $this->setParams( $params );
                }
                else
                {
                    $this->setParams( current( $params ) );
                }
            }
        }
        else
        {
            $this->setParams( $params );
        }
    }

    /**
     * 初始化配置文件
     *
     * @param $config
     */
    private static function init_config( $config )
    {
        self::setConfig( $config );
    }

    /**
     * 实例化带参数的控制器
     *
     * @param $controller
     * @param null $args 指定的参数
     * @internal param $router 要解析的路由
     * @return mixed
     */
    public function widget_run($controller, $args = null)
    {
        $this->init_controller( $controller );
        $this->init_params( $args );

        $controller = $this->getController();
        return new $controller;
    }

    /**
     * 运行框架
     *
     * @param $router 要解析的理由
     * @param null $args 指定参数
     * @param bool $run_controller 是否只返回控制器实例
     * @return array|mixed|string
     * @throws CoreException
     */
    public function run($router, $args = null, $run_controller = true)
    {
        $router = $this->getRouter( $router, $args);
        $action = $run_controller ? $router ['action'] : null;

        $this->init_controller( $router ['controller'], $action );
        $this->init_params( $router ['params'] );
        $cache = $this->init_request_cache();

        if($cache)
        {
            $content = $cache->get();
            if(strlen($content) > 0)
            {
                return $content;
            }
        }

        $cp = new self::$controller( );
        if(true == $run_controller)
        {
            $cp->run( self::$action, self::$params );
        }

        return $cp;
    }

    /**
     * 设置config
     *
     * @param $config 配置
     */
    private static function setConfig( $config )
    {
        self::$appConfig = $config;
    }

    /**
     * 设置controller
     *
     * @param $controller
     */
    private function setController( $controller )
    {
        self::$controller = $controller;
    }

    /**
     * 设置action
     *
     * @param $action
     */
    private function setAction( $action )
    {
        self::$action = $action;
    }

    /**
     * 设置params
     *
     * @param null $params
     */
    private function setParams( $params = null )
    {
        self::$params = $params;
    }

    /**
     * 获取配置
     *
     * @return array
     */
    public static function getConfig()
    {
        return self::$appConfig;
    }

    /**
     * 获取控制器名称
     *
     * @return mixed
     */
    public static function getController()
    {
        return self::$controller;
    }

    /**
     * 获取action名称
     *
     * @return string
     */
    public static function getAction()
    {
        return self::$action;
    }

    /**
     * 获取参数
     *
     * @return mixed
     */
    public static function getParams()
    {
        return self::$params;
    }

}

