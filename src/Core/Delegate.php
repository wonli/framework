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
use Cross\Http\Response;
use Cross\Http\Request;
use Closure;

//检查环境版本
version_compare(PHP_VERSION, '5.3.6', '>=') or die('requires PHP 5.3.6!');

//外部定义的项目路径
defined('PROJECT_PATH') or die('undefined PROJECT_PATH');

//项目路径
define('PROJECT_REAL_PATH', rtrim(PROJECT_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);

//项目APP路径
define('APP_PATH_DIR', PROJECT_REAL_PATH . 'app' . DIRECTORY_SEPARATOR);

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
     * @var string
     */
    public $app_name;

    /**
     * @var Application
     */
    private $app;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Loader
     */
    private $loader;

    /**
     * 运行时配置 (高于配置文件)
     *
     * @var array
     */
    private $runtime_config;

    /**
     * 运行时匿名函数容器
     *
     * @var ClosureContainer
     */
    private $action_container;

    /**
     * Delegate的实例
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
    private function __construct($app_name, array $runtime_config)
    {
        $this->app_name = $app_name;
        $this->runtime_config = $runtime_config;

        $this->loader = Loader::init();
        $this->config = self::initConfig($app_name, $runtime_config);

        $this->registerNamespace();
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
        return '1.5.7';
    }

    /**
     * 实例化框架
     *
     * @param string $app_name app名称
     * @param array $runtime_config 运行时加载的设置
     * @return self
     */
    static function loadApp($app_name, array $runtime_config = array())
    {
        if (!isset(self::$instance[$app_name])) {
            self::$instance[$app_name] = new Delegate($app_name, $runtime_config);
        }

        return self::$instance[$app_name];
    }

    /**
     * 直接调用控制器类中的方法
     * <pre>
     * 忽略路由别名相关配置和URL参数, @cp_params注释不生效
     * </pre>
     *
     * @param string $controller "控制器:方法"
     * @param string|array $args 参数
     * @param bool $return_content 是输出还是直接返回结果
     * @return array|mixed|string
     * @throws \Cross\Exception\CoreException
     */
    public function get($controller, $args = array(), $return_content = false)
    {
        return $this->app->dispatcher($controller, $args, $return_content);
    }

    /**
     * 解析url并运行
     *
     * @throws \Cross\Exception\CoreException
     */
    public function run()
    {
        $this->app->dispatcher($this->router->getRouter());
    }

    /**
     * 自定义router运行
     *
     * @param RouterInterface $router
     * @throws \Cross\Exception\CoreException
     */
    public function rRun(RouterInterface $router)
    {
        $this->app->dispatcher($router);
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
     * application对象
     *
     * @return Application
     */
    function getApplication()
    {
        return $this->app;
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
     * Loader
     *
     * @return Loader
     */
    function getLoader()
    {
        return $this->loader;
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
     * 初始化App配置
     * @param string $app_name
     * @param array $runtime_config
     * @return $this
     * @throws \Cross\Exception\FrontException
     */
    private static function initConfig($app_name, array $runtime_config)
    {
        $request = Request::getInstance();
        $host = $request->getHostInfo();
        $index_name = $request->getIndexName();

        $request_url = $request->getBaseUrl();
        $script_path = $request->getScriptFilePath();

        //app名称和路径
        $runtime_config['app'] = array(
            'name' => $app_name,
            'path' => APP_PATH_DIR . $app_name . DIRECTORY_SEPARATOR
        );

        $env_config = array(
            //url相关设置
            'url' => array(
                'host' => $host,
                'index' => $index_name,
                'request' => $request_url,
                'full_request' => $host . $request_url
            ),

            //配置和缓存的绝对路径
            'path' => array(
                'cache' => PROJECT_REAL_PATH . 'cache' . DIRECTORY_SEPARATOR,
                'config' => PROJECT_REAL_PATH . 'config' . DIRECTORY_SEPARATOR,
                'script' => $script_path . DIRECTORY_SEPARATOR,
            ),

            //静态文件url和绝对路径
            'static' => array(
                'url' => $host . $request_url . '/static/',
                'path' => $script_path . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR
            )
        );

        foreach ($env_config as $key => $value) {
            if (isset($runtime_config[$key]) && is_array($runtime_config[$key])) {
                $runtime_config[$key] = array_merge($value, $runtime_config[$key]);
            } elseif (!isset($runtime_config[$key])) {
                $runtime_config[$key] = $value;
            }
        }

        return Config::load(APP_PATH_DIR . $app_name . DIRECTORY_SEPARATOR . 'init.php')->combine($runtime_config);
    }

    /**
     * 批量注册命名空间
     *
     * @throws CoreException
     */
    private function registerNamespace()
    {
        $namespaceConfig = $this->config->get('namespace');
        if (!empty($namespaceConfig)) {
            foreach ($namespaceConfig as $namespace => $libDir) {
                $libDir = PROJECT_REAL_PATH . $libDir;
                if (file_exists($libDir)) {
                    $this->loader->registerNamespace($namespace, $libDir);
                } else {
                    throw new CoreException("Register namespace {$namespace} failed");
                }
            }
        }
    }
}
