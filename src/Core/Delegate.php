<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Core;

use Cross\Exception\CoreException;
use Cross\Exception\FrontException;
use Cross\Runtime\ClosureContainer;
use Cross\I\RouterInterface;
use Cross\Http\Response;
use Cross\Http\Request;
use Closure;

//检查环境版本
version_compare(PHP_VERSION, '7.2.0', '>=') or die('Requires PHP 7.2.0!');

//外部定义的项目路径
defined('PROJECT_PATH') or die('Requires PROJECT_PATH');

//项目路径
define('PROJECT_REAL_PATH', rtrim(PROJECT_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);

//项目APP路径
define('APP_PATH_DIR', PROJECT_REAL_PATH . 'app' . DIRECTORY_SEPARATOR);

//框架路径
define('CP_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

/**
 * @author wonli <wonli@live.com>
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
     * @throws CoreException
     * @throws FrontException
     */
    private function __construct(string $app_name, array $runtime_config)
    {
        $this->app_name = $app_name;
        $this->runtime_config = $runtime_config;

        $this->loader = Loader::init();
        $this->config = $this->initConfig($app_name, $runtime_config);

        $this->registerNamespace();
        $this->action_container = new ClosureContainer();
        $this->router = new Router($this);
        $this->app = new Application($app_name, $this);
    }

    /**
     * 当前框架版本号
     *
     * @return string
     */
    static function getVersion(): string
    {
        return '2.0.1';
    }

    /**
     * 实例化框架
     *
     * @param string $app_name app名称
     * @param array $runtime_config 运行时加载的设置
     * @return self
     * @throws CoreException
     * @throws FrontException
     */
    static function loadApp(string $app_name, array $runtime_config = []): self
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
     * @throws CoreException
     */
    public function get(string $controller, $args = [], bool $return_content = false)
    {
        return $this->app->dispatcher($controller, $args, $return_content);
    }

    /**
     * 解析url并运行
     *
     * @throws CoreException
     * @throws FrontException
     */
    public function run()
    {
        $this->app->dispatcher($this->router->getRouter());
    }

    /**
     * 自定义router运行
     *
     * @param RouterInterface $router
     * @throws CoreException
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
    public function rest(): Rest
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
     * @throws CoreException
     */
    public function cliRun()
    {
        if (PHP_SAPI !== 'cli') {
            die('This is a CLI app');
        }

        global $argc, $argv;
        $defaultController = $this->config->get('*');
        if ($argc == 1) {
            if (empty($defaultController)) {
                die('Please specify controller(controller[:action [-p|--params]])');
            } else {
                $controller = $defaultController;
            }
        } else {
            //处理参数和控制别名
            $iArgv = $argv;
            array_shift($iArgv);
            $iArgv = array_filter($iArgv, function ($a) {
                return ($a[0] == '-' || false !== strpos($a, '=')) ? false : $a;
            });

            $controller = array_shift($iArgv) ?? $defaultController;
        }

        $controller = $this->router->getRouterAlias($controller);
        $this->get($controller, $argv);
    }

    /**
     * 注册运行时匿名函数
     *
     * @param string $name
     * @param Closure $f
     * @return $this
     */
    function on(string $name, Closure $f): self
    {
        $this->action_container->add($name, $f);
        return $this;
    }

    /**
     * application对象
     *
     * @return Application
     */
    function getApplication(): Application
    {
        return $this->app;
    }

    /**
     * app配置对象
     *
     * @return Config
     */
    function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Loader
     *
     * @return Loader
     */
    function getLoader(): Loader
    {
        return $this->loader;
    }

    /**
     * 获取运行时指定的配置
     *
     * @return array
     */
    function getRuntimeConfig(): array
    {
        return $this->runtime_config;
    }

    /**
     * @return Router
     */
    function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * 返回当前app的aspect容器实例
     *
     * @return ClosureContainer
     */
    function getClosureContainer(): ClosureContainer
    {
        return $this->action_container;
    }

    /**
     * @return Request
     */
    function getRequest(): Request
    {
        return Request::getInstance();
    }

    /**
     * @return Response
     */
    function getResponse(): Response
    {
        return Response::getInstance();
    }

    /**
     * 初始化App配置
     *
     * @param string $app_name
     * @param array $runtime_config
     * @return Config
     * @throws FrontException
     * @throws CoreException
     */
    private function initConfig(string $app_name, array $runtime_config): Config
    {
        $request = $this->getRequest();
        $host = $request->getHostInfo();
        $index_name = $request->getIndexName();

        $request_url = $request->getBaseUrl();
        $script_path = $request->getScriptFilePath();

        //app名称和路径
        $runtime_config['app'] = [
            'name' => $app_name,
            'path' => APP_PATH_DIR . $app_name . DIRECTORY_SEPARATOR
        ];

        $env_config = [
            //url相关设置
            'url' => [
                'host' => $host,
                'index' => $index_name,
                'request' => $request_url,
                'full_request' => $host . $request_url
            ],

            //配置和缓存的绝对路径
            'path' => [
                'cache' => PROJECT_REAL_PATH . 'cache' . DIRECTORY_SEPARATOR,
                'config' => PROJECT_REAL_PATH . 'config' . DIRECTORY_SEPARATOR,
                'script' => $script_path . DIRECTORY_SEPARATOR,
            ],

            //静态文件url和绝对路径
            'static' => [
                'url' => $host . $request_url . '/static/',
                'path' => $script_path . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR
            ]
        ];

        $Config = Config::load(APP_PATH_DIR . $app_name . DIRECTORY_SEPARATOR . 'init.php');

        //默认环境
        $Config->combine($env_config);

        //运行时配置
        $Config->combine($runtime_config);

        //app共享配置
        $app_config_file = PROJECT_REAL_PATH . 'config' . DIRECTORY_SEPARATOR . 'app.config.php';
        if (file_exists($app_config_file)) {
            $app_config = Loader::read($app_config_file);
            if (!empty($app_config)) {
                $Config->combine($app_config, false);
            }
        }

        return $Config;
    }

    /**
     * 批量注册命名空间
     *
     * @throws CoreException
     */
    private function registerNamespace(): void
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
