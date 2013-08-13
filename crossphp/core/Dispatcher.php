<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:       wonli
 * @Version $Id: Dispatcher.php 103 2013-08-05 16:20:53Z ideaa $
 */
class Dispatcher
{
    /**
     * @var 控制器
     */
    public static $controller;

    /**
     * @var action
     */
    public static $action;

    /**
     * @var 参数
     */
    public static $params;

    /**
     * @var 缓存配置
     */
    public static $cache_config;

    /**
     * @var app配置
     */
    public static $appConfig;

    /**
     * @var 路由
     */
    private static $router;

    private function __construct($app_config)
    {
        self::$appConfig = $app_config;
    }

    /**
     * 实例化dispatcher
     *
     * @param $app_config
     * @return Dispatcher
     */
    public static function init( $app_config )
    {
        return new Dispatcher( $app_config );
    }

    /**
     * 解析router
     *
     * @param $router
     * @param $args 当$router类型为string时,指定参数
     */
    private static function getRouter( $router, $args )
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

        $router = array(
            'controller' => $controller,
            'action' => $action,
            'params' => $params
        );

        self::parseAlias( $router );
    }

    /**
     * 运行框架
     *
     * @param $router
     * @param null $args
     * @return array|mixed|string
     * @throws CoreException
     */
    public static function run($router, $args = null)
    {
        self::getRouter($router, $args);

        if(null === self::$controller) {
            throw new CoreException(self::$controller.' Controller not found!');
        }

        $cp = new self::$controller( );
        $cp->run( self::$action, self::$params );
    }

    /**
     * 解析alias配置
     *
     * @param $router
     * @throws CoreException
     */
    static function parseAlias($router)
    {
        $controller_config = self::$appConfig->get("controller");
        $_config = array();

        $_display_type = self::$appConfig->get("sys", "display");
        
        $_controller = $router ['controller'];
        $_action = $router ['action'];
        
        if(isset($controller_config [ $_controller ]))
        {
            $_config = $controller_config [ $_controller ];
        }

        if( isset($_config['alias']) && !empty($_config['alias']) )
        {
            $_calias = $_config['alias'];

            if(is_array($_calias))
            {
                $_controller = ucfirst($_controller);

                if(isset($_calias [$_action])) {
                    $_action = $_calias [$_action];
                }

            } else {
                if( false !== strpos($_calias, ":") )
                {
                    $_user_alias = explode(":", $_calias);
                    $_controller = ucfirst($_user_alias[0]);
                    array_shift($_user_alias);
                    $_action = $_user_alias[0];
                    array_shift($_user_alias);
                    $alias_params = $_user_alias;
                } else {
                    $_controller = ucfirst($_calias);
                }
            }
        }
        else
        {
            $_controller = ucfirst( $_controller );
        }

        //自动识别返回类型
        if('AUTO' == $_display_type)
        {      
            $_display = 'HTML';
        
            if( false !== strpos($_action, ".") )
            {
                list($_action, $_display) = explode(".", strtolower($_action));            
            }

            self::$appConfig->set("sys", array("display"=>$_display));
        }
        
        try
        {
            #会触发autoLoad
            $is_callable = new ReflectionMethod($_controller, $_action);
        } catch (Exception $e) {
            #控制器静态属性_act_alias_指定action的别名
            try
            {
                $_property = new ReflectionProperty($_controller, '_act_alias_');
                $act_alias = $_property->getValue();

                if( isset($act_alias [$_action]) )
                {
                    $_action = $act_alias [$_action];
                }

                $is_callable = new ReflectionMethod($_controller, $_action);

            } catch (Exception $e) {
                throw new CoreException("未定义的方法{$_controller}->{$_action}");
            }
        }

        if( $is_callable->isPublic() )
        {
            if(isset($alias_params) && ! empty($alias_params)) {
                $_params = array_merge($alias_params, $router["params"]);
            } else {
                $_params = $router["params"];
            }

            self::$controller = $_controller;
            self::$action     = $_action;
            self::$params     = $_params;

        } else {
            throw new CoreException("不被允许访问的方法!");
        }
    }
}

