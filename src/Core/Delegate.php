<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.1
 */
namespace Cross\Core;

use Cross\Exception\CoreException;
use Cross\I\RouterInterface;

//检查环境版本
!version_compare(PHP_VERSION, '5.3.0', '<') or die('requires PHP 5.3.0 Please upgrade!');

//外部定义的项目路径
defined('PROJECT_PATH') or die('undefined PROJECT_PATH');

//项目路径
define('PROJECT_REAL_PATH', rtrim(PROJECT_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);

//项目APP路径
defined('APP_PATH_DIR') or define('APP_PATH_DIR', PROJECT_REAL_PATH . 'app' . DIRECTORY_SEPARATOR);

//框架路径
define('CP_PATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

/**
 * @Auth: wonli <wonli@live.com>
 * Class Delegate
 * @package Cross\Core
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
     *
     * @param string $app_name 要加载的app名称
     * @param array $runtime_config 运行时指定的配置
     */
    private function __construct($app_name, $runtime_config)
    {
        Loader::init($app_name);
        $this->app_name = $app_name;
        $this->runtime_config = $runtime_config;
        $this->config = $this->initConfig();
        $this->appInit();
    }

    /**
     * 当前框架版本号
     *
     * @return string
     */
    static function getVersion()
    {
        return '1.1.1';
    }

    /**
     * 实例化框架
     *
     * @param string $app_name app名称
     * @param array $runtime_config 运行时加载的设置
     * @return self
     */
    static function loadApp($app_name, $runtime_config = array())
    {
        if (!isset(self::$instance[$app_name])) {
            self::$instance[$app_name] = new Delegate($app_name, $runtime_config);
        }

        return self::$instance[$app_name];
    }

    /**
     * 初始化App配置
     *
     * @return Config
     */
    function initConfig()
    {
        $config = Config::load(APP_PATH_DIR . $this->app_name . DIRECTORY_SEPARATOR . 'init.php')->parse(
            $this->runtime_config
        );

        $request = Request::getInstance();
        $host = $request->getHostInfo();
        $index_name = $request->getIndexName();

        $request_url = $request->getBaseUrl();
        $base_script_path = $request->getScriptFilePath();

        //设置app名称和路径
        $config->set('app', array(
            'name'  =>  $this->app_name,
            'path'  =>  APP_PATH_DIR.$this->app_name.DIRECTORY_SEPARATOR
        ));

        //静态文件url和绝对路径
        $config->set('static', array(
            'url'   =>  $host.$request_url.'/static/',
            'path'  =>  $base_script_path . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR
        ));

        //url相关设置
        $config->set('url', array(
            'index' => $index_name,
            'host'  =>  $host,
            'request'   =>  $request_url ? $request_url : '/',
            'full_request'  =>  $host.$request_url,
        ));

        return $config;
    }

    /**
     * 获取app配置
     *
     * @return Config
     */
    function getConfig()
    {
        return $this->config;
    }

    /**
     * 初始化配置参数,定义常量
     */
    private function appInit()
    {

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
     * @param bool $return_content 是输出还是直接返回结果
     * @return array|mixed|string
     */
    public function get($controller, $args = null, $return_content = false)
    {
        return Application::initialization($this->config)->dispatcher($controller, $args, true, $return_content);
    }

    /**
     * REST
     *
     * @return Rest
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
            throw new CoreException('Not Specified Uri');
        }
    }
}
