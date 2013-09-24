<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:       wonli
 * @Version $Id: Dispatcher.php 141 2013-09-24 06:43:12Z ideaa $
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
        $this->init_config( $app_config );
    }

    /**
     * 实例化dispatcher
     *
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
            'controller' =>  $controller,
            'action'     =>   $action,
            'params'     =>  $params,
        );
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
                    throw new CoreException('未指定的方法'.$controller.'->'.$action);
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

    /**
     * 初始化参数
     *
     * @param $params
     */
    private function init_params( $params )
    {
        if(! empty($params))
        {
            if(! isset($params[1]))
            {
                if( isset($params[0]))
                {
                    $this->setParams( $params[0] );
                }
                else
                {
                    $this->setParams( $params );
                }
            }
        }
        else
        {
            $this->setParams();
        }
    }

    /**
     * 初始化配置文件
     *
     * @param $config
     */
    private function init_config( $config )
    {
        self::setConfig( $config );
    }

    /**
     * 实例化带参数的控制器
     *
     * @param $router 要解析的路由
     * @param null $args 指定的参数
     * @return mixed
     */
    public function widget_run($controller, $args = null)
    {
        $this->init_controller( $controller );
        $this->init_params( $args );
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
        self::$controller = ucfirst( $controller );
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
     * @return app
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
     * @return action
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

