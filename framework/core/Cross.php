<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:       wonli
 * @version: $Id: Cross.php 96 2013-08-01 05:34:03Z ideaa $
 */
class Cross
{
    public static $app_name;

    /**
     * @var App配置 init.php
     */
    private static $app_config;

    /**
     * @var 运行时配置 (高于配置文件)
     */
    private static $runtime_config;

    /**
     * 初始化框架 参见self::loadApp
     */
    private function __construct( $app_name, $runtime_config )
    {
        self::$app_name = $app_name;
        self::$runtime_config = $runtime_config;
        $this->appInit( );
    }

    /**
     * 当前框架版本号
     *
     * @return string
     */
    static function getVersion() {
        return '1.0.1';
    }

    /**
     * 实例化框架
     *
     * @param $appname 要加载的App名称
     * @param $runtime_config 运行时加载的设置
     * @return mixed
     */
    static function loadApp($app_name, $runtime_config = null)
    {
        return new Cross( $app_name, $runtime_config );
    }

    /**
     * 返回配置类对象
     *
     * @return config Object
     */
    static function config( )
    {
        return Config::load( self::$app_name )->parse( self::$runtime_config );
    }

    /**
     * 取得所有自定义配置
     *
     * @return array 配置数组
     */
    static function get_app_config()
    {
        return self::config()->getAll();
    }

    /**
     * 路由规则初始化
     *
     * @return mixed
     */
    static function router()
    {
        return Router::getInstance(self::$app_config)->getRouter();
    }

    /**
     * 初始化配置参数,定义常量
     */
    private function appInit( )
    {
        self::$app_config = self::get_app_config();

        $this->definer(array(
            'APP_NAME'      => self::$app_config["sys"]["app_name"],
            'SITE_URL'      => self::$app_config["sys"]["site_url"],
            'STATIC_URL'    => self::$app_config["sys"]["static_url"],
            'STATIC_PATH'   => self::$app_config["sys"]["static_path"],
            'APP_PATH'      => self::$app_config["sys"]["app_path"],
        ));
    }

    /**
     * 常量定义
     *
     * @param   $define 要定义的常量名
     * @param   $args   常量的值
     */
    private function definer($define, $args=null)
    {
        if(is_array($define)) {
            foreach($define as $def=>$value) {
                defined($def)or define($def, $value);
            }
        } else {
            defined($define)or define($define, $args);
        }
    }

    /**
     * Dispatcher解析执行Cross路由,加载缓存
     *
     * @param   $args 指定运行时参数
     */
    public function run( $args = null )
    {
        Dispatcher::init( self::config() )->run( self::router(), $args );
    }

    /**
     * 自定义router
     *
     * @param RouterInterface $router
     * @param $args
     */
    public function rrun( RouterInterface $router, $args )
    {
        Dispatcher::init( self::config() )->run( $router, $args );
    }

    /**
     * 直接调用App的控制器
     *
     * @param $controller "控制器:方法"
     * @param $args 参数
     */
    public function get( $controller, $args = null )
    {
        Dispatcher::init( self::config() )->run( $controller, $args );
    }

    /**
     * ob缓存结果
     *
     * @param $controller
     * @param null $args
     * @return string
     */
    public function cget( $controller, $args = null )
    {
        ob_start();
            Cross::get($controller, $args);
        return ob_get_clean();
    }
}