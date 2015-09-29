<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Core;

use Cross\Exception\CoreException;
use Cross\Runtime\ClosureContainer;
use Cross\I\RouterInterface;
use Closure;

//检查环境版本
version_compare(PHP_VERSION, '5.3.0', '>=') or die('requires PHP 5.3.0 Please upgrade!');

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
     * app名称
     *
     * @var string
     */
    public $app_name;

    /**
     * 允许的请求列表(mRun)时生效
     *
     * @var array
     */
    private static $map;

    /**
     * Delegate的实例
     *
     * @var Delegate
     */
    private static $instance;

    /**
     * 注入的匿名函数数组
     *
     * @var array
     */
    private $di;

    /**
     * @var Router
     */
    private $router;

    /**
     * 运行时匿名函数容器
     *
     * @var ClosureContainer
     */
    private $action_container;

    /**
     * app配置
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

        $this->config = self::initConfig($app_name, $runtime_config);
        $this->action_container = new ClosureContainer();
        $this->router = new Router($this->config);

        $this->app = new Application($app_name, $this);
    }

    /**
     * 当前框架版本号
     *
     * @return string
     */
    static function getVersion()
    {
        return '1.4.1';
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
     * 直接调用控制器类中的方法 忽略解析和alias配置
     *
     * @param string $controller "控制器:方法"
     * @param null $args 参数
     * @param bool $return_content 是输出还是直接返回结果
     * @return array|mixed|string
     */
    public function get($controller, $args = null, $return_content = false)
    {
        return $this->app->dispatcher($controller, $args, true, $return_content);
    }

    /**
     * 从路由解析url请求,自动运行
     *
     * @param string $params = null 为router指定参数
     * @param string $args
     */
    public function run($params = null, $args = null)
    {
        $this->app->dispatcher($this->router->setRouterParams($params)->getRouter(), $args);
    }

    /**
     * 自定义router运行
     *
     * @param RouterInterface $router RouterInterface的实现
     * @param string $args 参数
     */
    public function rRun(RouterInterface $router, $args)
    {
        $this->app->dispatcher($router, $args);
    }

    /**
     * 处理REST风格的请求
     * <pre>
     * $app = Cross\Core\Delegate::loadApp('web')->rest();
     *
     * $app->get("/", function(){
     *    echo "hello";
     * });
     * </pre>
     *
     * @return Rest
     */
    public function rest()
    {
        return Rest::getInstance($this);
    }

    /**
     * 配置uri
     * @see mRun()
     *
     * @param string $uri 指定uri
     * @param null $controller "控制器:方法"
     */
    public function map($uri, $controller = null)
    {
        self::$map [$uri] = $controller;
    }

    /**
     * 执行self::$map中匹配的url
     *
     * @param null $args 参数
     * @throws CoreException
     */
    public function mRun($args = null)
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

    /**
     * CLI模式下运行方式
     * <pre>
     * 在命令行模式下的调用方法如下:
     * php /path/index.php controller:action params1=value params2=value ... $paramsN=value
     * 第一个参数用来指定要调用的控制器和方法
     * 格式如下:
     *      控制器名称:方法名称
     *
     * 在控制器:方法后加空格来指定参数,格式如下:
     *      参数1=值, 参数2=值, ... 参数N=值
     *
     * 控制器中调用$this->params来获取并处理参数
     * </pre>
     *
     * @param int|bool $run_argc
     * @param array|bool $run_argv
     */
    public function cliRun($run_argc = false, $run_argv = false)
    {
        if (PHP_SAPI !== 'cli') {
            die('This app is only running from CLI');
        }

        if (false === $run_argc) {
            $run_argc = $_SERVER['argc'];
        }

        if (false === $run_argv) {
            $run_argv = $_SERVER['argv'];
        }

        if ($run_argc == 1) {
            die('Please specify params: controller:action params');
        }

        //去掉argv中的第一个参数
        array_shift($run_argv);
        $controller = array_shift($run_argv);

        //使用get调用指定的控制器和方法,并传递参数
        $this->get($controller, $run_argv);
    }

    /**
     * 注册注入匿名函数
     *
     * @param string $name
     * @param Closure $f
     * @return $this
     */
    function di($name, Closure $f)
    {
        $this->di[$name] = $f;
        return $this;
    }

    /**
     * 注册运行时匿名函数
     *
     * @param string $name
     * @param Closure $f
     * @return $this
     */
    function on($name, Closure $f)
    {
        $this->action_container->add($name, $f);
        return $this;
    }

    /**
     * app配置对象
     *
     * @return Config
     */
    function getConfig()
    {
        return $this->config;
    }

    /**
     * 获取运行时指定的配置
     *
     * @return array
     */
    function getRuntimeConfig()
    {
        return $this->runtime_config;
    }

    /**
     * @return Router
     */
    function getRouter()
    {
        return $this->router;
    }

    /**
     * 返回当前app的aspect容器实例
     *
     * @return ClosureContainer
     */
    function getClosureContainer()
    {
        return $this->action_container;
    }

    /**
     * @return Request
     */
    function getRequest()
    {
        return Request::getInstance();
    }

    /**
     * @return Response
     */
    function getResponse()
    {
        return Response::getInstance();
    }

    /**
     * 返回依赖注入对象
     *
     * @return array
     */
    function getDi()
    {
        return $this->di;
    }

    /**
     * 初始化App配置
     * @param string $app_name
     * @param array $runtime_config
     * @return $this
     * @throws \Cross\Exception\FrontException
     */
    private static function initConfig($app_name, $runtime_config)
    {
        $config = Config::load(APP_PATH_DIR . $app_name . DIRECTORY_SEPARATOR . 'init.php')->parse($runtime_config);

        $request = Request::getInstance();
        $host = $request->getHostInfo();
        $index_name = $request->getIndexName();

        $request_url = $request->getBaseUrl();
        $base_script_path = $request->getScriptFilePath();

        //设置app名称和路径
        $config->set('app', array(
            'name' => $app_name,
            'path' => APP_PATH_DIR . $app_name . DIRECTORY_SEPARATOR
        ));

        //静态文件url和绝对路径
        $config->set('static', array(
            'url' => $host . $request_url . '/static/',
            'path' => $base_script_path . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR
        ));

        //url相关设置
        $config->set('url', array(
            'index' => $index_name,
            'host' => $host,
            'request' => $request_url,
            'full_request' => $host . $request_url,
        ));

        return $config;
    }
}
