<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
* Author:       wonli
* Contact:      wonli@live.com
* Date:         2011.08
* Description:  dispather
*/
class Dispatcher
{
    private static $controller;
    private static $action;
    private static $params;

    private static $appConfig;
    private static $router;

    private static $instance;

    private function __construct($appinit)
    {
        self::$appConfig = $appinit;
    }

    public static function getInstance($appinit)
    {
        if(! self::$instance) {
            self::$instance = new Dispatcher( $appinit );
        }
        return self::$instance;
    }

    private static function parseRouter( $router, $args )
    {
        if(is_object($router)) {
            self::$controller     = $router->getController();
            self::$action         = $router->getAction();
            self::$params         = $router->getParams();
        }

        if(is_array($router)) {
            self::$controller = $router["controller"];
            self::$action = $router["action"];
            self::$params = $router["params"];
        }

        if( is_string($router) ) {

            if(strpos($router,':')) {
                list(self::$controller, self::$action) = explode(':', $router);
            } else {
                self::$controller = $router;
                self::$action     = 'index';
            }

            self::$params = $args;
        }
    }

    public static function run($router, $args = null)
    {
        self::parseRouter($router, $args);

        if(null === self::$controller) {
            throw new CoreException(self::$controller.' Controller not found!');
        }

        $_key_dot = self::$appConfig->get("url", "dot");
        $_app_name = self::$appConfig->get("sys", "app_name");

        if(empty($params)) {
            $_key_params = 'index';
        } else {
            if(is_array($_key_params)) {
                $_key_params = implode($_key_dot, $params);
            }
        }
        $_cache_key = self::$controller.$_key_dot.self::$action.$_key_dot.$_key_params;

        list($_isCache, $_cacheConfig) = self::$appConfig->get( strtolower(self::$controller), 'cache' );
        $_ex = false;

        if($_isCache)
        {
            $cache=Cache::create( $_cacheConfig, $_cache_key );
            $_ex = $cache->getExtime();
        }

        if($_isCache && $_ex) {
            return $cache->get();
        }

        $controllerfile = self::$appConfig->get("sys", "app_path").DS.'controllers'.DS.self::$controller.'.php';

        $_action = self::$action;
        $controller = new self::$controller( self::$controller, $_action, self::$params, self::$appConfig );

        return $controller->$_action( self::$params );
    }
}

