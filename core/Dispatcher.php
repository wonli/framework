<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:       wonli
 * @Version $Id: Dispatcher.php 79 2013-05-24 09:20:46Z ideaa $
 */
class Dispatcher
{
    public static $controller;
    public static $action;
    public static $params;
    public static $cache_config;

    private static $appConfig;
    private static $router;

    private static $instance;

    private function __construct($app_config)
    {
        self::$appConfig = $app_config;
    }

    public static function getInstance( $app_config )
    {
        if(! self::$instance) {
            self::$instance = new Dispatcher( $app_config );
        }
        return self::$instance;
    }

    private static function parseRouter( $router, $args )
    {
        if(is_object($router)) {
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
        self::$controller = ucfirst($controller);
        self::$action = $action;
        self::$params = $params;
    }

    public static function run($router, $args = null)
    {
        self::parseRouter($router, $args);

        if(null === self::$controller) {
            throw new CoreException(self::$controller.' Controller not found!');
        }

        $_key_dot = self::$appConfig->get("url", "dot");
        $_app_name = self::$appConfig->get("sys", "app_name");

        if(empty(self::$params)) {
            $_key_params = 'index';
        } else {
            if(is_array(self::$params)) {
                $_key_params = implode($_key_dot, self::$params);
            } else {
                $_key_params = self::$params;
            }
        }
        $_cache_key = self::$controller.$_key_dot.self::$action.$_key_dot.$_key_params;
        $this_controller_config = self::$appConfig->get("controller", strtolower(self::$controller));

        $_cacheConfig = null;
        if(isset($this_controller_config["cache"]) && $this_controller_config["cache"][0] === true) {
            list($_isCache, $_cacheConfig) = $this_controller_config["cache"];
            $_ex = false;

            if(true === $_isCache)
            {
                $cache=Cache::create( $_cacheConfig, $_cache_key );
                $_ex = $cache->getExtime();
            }

            if(true === $_isCache && $_ex) {
                return $cache->get();
            }
        }
        self::$cache_config = $_cacheConfig;        
        $_action = self::$action;
        
        $controller = new self::$controller( );
        $controller->setControllerName(self::$controller);
        $controller->setCacheConfig($_cacheConfig);
        $controller->setConfig(self::$appConfig);
        $controller->setActionName($_action);
        $controller->setParams(self::$params);
        $controller->init( );

        $controller->$_action( self::$params );
    }
}

