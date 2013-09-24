<?php
/**
 * Usage:
 * Cross::widget("app_name")->load("admin", array('p'=>1))->login();
 * <pre>
 *  widget:app name
 *  load:params 1: controller name
 *       params 2: params
 *  login:actino name
 * </pre>
 *
 * @Auth: wonli <wonli@live.com>
 * Widget.php
 */

class Widget
{
    /**
     * app名称
     *
     * @var string
     */
    protected $app_name;

    /**
     * 运行时配置
     *
     * @var mixed
     */
    protected $runtime_config;

    /**
     * 构造方法
     *
     * @param $app_name
     * @param $runtime_config
     */
    function __construct($app_name, $runtime_config)
    {
        $this->app_name = $app_name;
        $this->runtime_config = $runtime_config;
    }

    /**
     * 初始化widget
     *
     * @param $app_name
     * @param null $runtime_config
     * @return Widget
     */
    static function init($app_name, $runtime_config = null)
    {
        Loader::init( $app_name );
        return new Widget( $app_name, $runtime_config );
    }

    /**
     * 返回配置类对象
     *
     * @return config Object
     */
    function config( )
    {
        return Config::load( $this->app_name )->parse( $this->runtime_config );
    }

    /**
     * 加载控制器
     *
     * @param $controller_name
     * @param $params
     * @return mixed
     */
    function load($controller_name, $params = array())
    {
        return Dispatcher::init( $this->app_name, $this->config() )->widget_run( $controller_name, $params);
    }
}
