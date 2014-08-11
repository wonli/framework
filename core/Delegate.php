<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.1
 */
namespace cross\core;

use cross\exception\CoreException;
use cross\i\RouterInterface;

//DIRECTORY_SEPARATOR
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

//框架路径
defined('CP_PATH') or define('CP_PATH', realpath(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);

//外部定义的项目路径
defined('PROJECT_PATH') or die("undefined PROJECT_PATH");

//项目路径
defined('PROJECT_REAL_PATH') or define('PROJECT_REAL_PATH', rtrim(PROJECT_PATH, DS) . DS);

//项目APP路径
defined('APP_PATH_DIR') or define('APP_PATH_DIR', PROJECT_REAL_PATH . 'app');

/**
 * @Auth: wonli <wonli@live.com>
 * Class Delegate
 * @package cross\core
 */
class Delegate
{
    /**
     * app 名称
     *
     * @var string
     */
    public $app_name;

    /**
     * 设置允许的请求列表
     *
     * @var array
     */
    public static $map;

    /**
     * app配置文件
     *
     * @var Config
     */
    private $config;

    /**
     * 运行时配置 (高于配置文件)
     *
     * @var array
     */
    private $runtime_config;

    /**
     * cross instance
     *
     * @var Delegate
     */
    private static $instance;

    /**
     * 初始化框架
     */
    private function __construct($app_name, $runtime_config)
    {
        define('APP_NAME', $app_name);
        define('APP_PATH', APP_PATH_DIR . DS . APP_NAME);

        $this->app_name = $app_name;
        $this->runtime_config = $runtime_config;
        $this->config = $this->getConfig();
        $this->appInit();
    }

    /**
     * 当前框架版本号
     *
     * @return string
     */
    static function getVersion()
    {
        return '1.0.1';
    }

    /**
     * 实例化框架
     *
     * @param string $app_name app名称
     * @param array $runtime_config 运行时加载的设置
     * @return mixed
     */
    static function loadApp($app_name, $runtime_config = array())
    {
        if (!isset(self::$instance)) {
            self::$instance = new Delegate($app_name, $runtime_config);
        }

        return self::$instance;
    }

    /**
     * 返回配置类对象
     *
     * @return Config
     */
    function getConfig()
    {
        return Config::load()->parse($this->runtime_config);
    }

    /**
     * 解析请求
     *
     * @param null $params 参见router->initParams();
     * @return $this
     */
    function router($params = null)
    {
        return Router::initialization($this->config)->set_router_params($params)->getRouter();
    }

    /**
     * 初始化配置参数,定义常量
     */
    private function appInit()
    {
        $sys_config = $this->config->get("sys");

        defined('SITE_URL') or define('SITE_URL', $sys_config['site_url']);
        defined('STATIC_URL') or define('STATIC_URL', $sys_config['static_url']);
        defined('STATIC_PATH') or define('STATIC_PATH', $sys_config['static_path']);
    }

    /**
     * 配置uri 参见mrun()
     *
     * @param string $uri 指定uri
     * @param null $controller "控制器:方法"
     */
    public function map($uri, $controller = null)
    {
        self::$map [$uri] = $controller;
    }

    /**
     * 直接调用控制器类中的方法 忽略解析和alias配置
     *
     * @param string $controller "控制器:方法"
     * @param null $args 参数
     */
    public function get($controller, $args = null)
    {
        Application::initialization($this->config)->dispatcher($controller, $args);
    }

    /**
     * REST
     *
     * @return $this
     */
    public function rest()
    {
        return Rest::getInstance($this->config);
    }

    /**
     * 根据配置解析请求
     *
     * @param string $params = null 用于自定义url请求内容
     * @param string $args 参数
     */
    public function run($params = null, $args = null)
    {
        Application::initialization($this->config)->dispatcher($this->router($params), $args);
    }

    /**
     * 自定义router运行
     *
     * @param RouterInterface $router RouterInterface的实现
     * @param string $args 参数
     */
    public function rrun(RouterInterface $router, $args)
    {
        Application::initialization($this->config)->dispatcher($router, $args);
    }

    /**
     * 按map配置运行
     *
     * @param null $args 参数
     * @throws CoreException
     */
    public function mrun($args = null)
    {
        $url_type = $this->config->get('url', 'type');
        $req = Request::getInstance()->getUrlRequest($url_type);

        if (isset(self::$map [$req])) {
            $controller = self::$map [$req];
            $this->get($controller, $args);
        } else {
            throw new CoreException("Not Specified Uri");
        }
    }
}
