<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:  wonli <wonli@live.com>
 * @version: $Id: Cross.php 141 2013-09-24 06:43:12Z ideaa $
 */
class Cross
{
    /**
     * app 名称
     *
     * @var
     */
    public $app_name;

    /**
     * 设置允许的请求列表
     *
     * @var array
     */
    public static $map;

    /**
     * 运行时配置 (高于配置文件)
     *
     * @var array
     */
    private $runtime_config;

    /**
     * app配置 init.php
     *
     * @var array
     */
    public $app_config;

    /**
     * cross instance
     *
     * @var object
     */
    public static $instance;

    /**
     * 初始化框架
     */
    private function __construct( $app_name, $runtime_config )
    {
        $this->app_name = $app_name;
        $this->runtime_config = $runtime_config;
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
     * @param $app_name
     * @param $runtime_config 运行时加载的设置
     * @internal param $appname 要加载的App名称
     * @return mixed
     */
    static function loadApp($app_name, $runtime_config = null)
    {
        Loader::init( $app_name );
        if(! isset(self::$instance [$app_name]))
        {
            self::$instance [$app_name] = new Cross( $app_name, $runtime_config );
        }

        return self::$instance [$app_name];
    }

    /**
     * 返回配置类对象
     *
     * @param null $app_name
     * @return config Object
     */
    function config( $app_name = null )
    {
        if(null === $app_name)
        {
            $app_name = $this->app_name;
        }

        return Config::load( $app_name )->parse( $this->runtime_config );
    }

    /**
     * 解析请求
     *
     * @param null $params 参见router->initParams();
     * @return $this
     */
    function router( $params=null )
    {
        return Router::init( $this->config() )->set_router_params( $params )->getRouter();
    }

    /**
     * 初始化配置参数,定义常量
     */
    private function appInit( )
    {
        $sys_config = $this->config()->get("sys");

        $this->definer(array(
            'APP_NAME'      => $sys_config["app_name"],
            'SITE_URL'      => $sys_config["site_url"],
            'STATIC_URL'    => $sys_config["static_url"],
            'STATIC_PATH'   => $sys_config["static_path"],
            'APP_PATH'      => $sys_config["app_path"],
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
        if(is_array($define))
        {
            foreach($define as $def=>$value)
            {
                defined($def)or define($def, $value);
            }
        }
        else
        {
            defined($define)or define($define, $args);
        }
    }

    /**
     * 配置uri 参见mrun()
     *
     * @param $uri 指定uri
     * @param null $controller "控制器:方法"
     */
    public function map($uri, $controller = null)
    {
        self::$map [ $uri ] = $controller;
    }

    /**
     * 在控制器中以插件方式调用其他app中的控制器
     *
     * @param $app_name
     * @param array $runtime_config
     * @return Widget
     */
    public static function widget($app_name, $runtime_config=array())
    {
        return Widget::init($app_name, $runtime_config);
    }

    /**
     * 直接调用控制器类中的方法 忽略解析和alias配置
     *
     * @param $controller "控制器:方法"
     * @param $args 参数
     */
    public function get( $controller, $args = null )
    {
        Dispatcher::init( $this->app_name, $this->config() )->run( $controller, $args );
    }

    /**
     * 根据配置解析请求
     *
     * @param $params = null 用于自定义url请求内容
     * @param $args 参数
     */
    public function run( $params = null, $args = null )
    {
        Dispatcher::init( $this->app_name, $this->config() )->run( $this->router( $params ), $args );
    }

    /**
     * 自定义router运行
     *
     * @param RouterInterface $router RouterInterface的实现
     * @param $args 参数
     */
    public function rrun( RouterInterface $router, $args )
    {
        Dispatcher::init( $this->app_name, $this->config() )->run( $router, $args );
    }

    /**
     * 按map配置运行
     *
     * @param null $args 参数
     * @throws CoreException
     */
    public function mrun( $args = null )
    {
        $url_type = self::config()->get('url', 'type');
        $r = Request::getInstance()->getUrlRequest( $url_type );

        if(isset( self::$map [ $r ] ))
        {
            $controller = self::$map [ $r ];
            Cross::get( $controller, $args );
        }
        else
        {
            throw new CoreException("Not Specified Uri");
        }
    }
}
