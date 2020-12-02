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
use Cross\Runtime\RequestMapping;
use Cross\I\RouterInterface;
use Cross\Http\Response;
use Cross\Http\Request;
use Closure;


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
    private $appName;

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
    private $runtimeConfig;

    /**
     * 是否多app模式
     *
     * @var bool
     */
    private $multiAppMode;

    /**
     * 运行时匿名函数容器
     *
     * @var ClosureContainer
     */
    private $actionContainer;

    /**
     * app命名空间
     *
     * @var string
     */
    private $appNamespace;

    /**
     * app名称是否命名空间
     *
     * @var bool
     */
    private $useNamespace;

    /**
     * 注入对象
     *
     * @var []
     */
    private $dii = [];

    /**
     * Delegate的实例
     *
     * @var Delegate
     */
    private static $instance;

    /**
     * 环境变量
     *
     * <pre>
     * 以入口第一个加载的app的配置为准
     * </pre>
     * @var Config
     */
    private static $env;

    /**
     * 初始化框架
     *
     * @param string $appName 要加载的app名称
     * @param bool $useNamespace
     * @param array $runtimeConfig 运行时指定的配置
     * @throws CoreException
     * @throws FrontException
     */
    private function __construct(string $appName, bool $useNamespace, array $runtimeConfig)
    {
        $this->initConstant();
        $this->loader = Loader::init();

        $this->useNamespace = $useNamespace;
        $this->runtimeConfig = $runtimeConfig;
        $this->multiAppMode = ($appName == '*');

        $this->setAppName($appName);
        $this->config = $this->initConfig($appName, $runtimeConfig);
        if (null === self::$env) {
            self::$env = $this->config;
        }

        $this->registerNamespace();
        $this->actionContainer = ClosureContainer::getInstance();
        $this->router = new Router($this);
        $this->app = new Application($this);
    }

    /**
     * 当前框架版本号
     *
     * @return string
     */
    static function getVersion(): string
    {
        return '2.3.2';
    }

    /**
     * 实例化框架
     *
     * @param string $appNamespace app命名空间
     * @param array $runtimeConfig 运行时加载的设置
     * @return static
     * @throws CoreException
     * @throws FrontException
     */
    static function app(string $appNamespace, array $runtimeConfig = []): self
    {
        return self::initApp($appNamespace, true, $runtimeConfig);
    }

    /**
     * 实例化框架
     *
     * @param string $appName app名称
     * @param array $runtimeConfig 运行时加载的设置
     * @return self
     * @throws CoreException
     * @throws FrontException
     */
    static function loadApp(string $appName, array $runtimeConfig = []): self
    {
        return self::initApp($appName, false, $runtimeConfig);
    }

    /**
     * 直接调用控制器类中的方法
     *
     * @param string $controller "控制器:方法"
     * @param mixed $args 参数
     * @param bool $returnContent 是输出还是直接返回结果
     * @return mixed
     * @throws CoreException
     */
    public function get(string $controller, $args = [], bool $returnContent = false)
    {
        return $this->app->dispatcher($controller, $args, $returnContent);
    }

    /**
     * 解析url并运行
     *
     * @throws CoreException
     * @throws FrontException
     */
    public function run()
    {
        $this->app->dispatcher($this->router->parseUrl());
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
            $args = [];
            if (empty($defaultController)) {
                die('Please specify controller(controller[:action [-p|--params]])');
            } else {
                $controller = $defaultController;
            }
        } else {
            //处理参数和控制别名
            $args = $argv;
            array_shift($args);
            if ($args[0][0] == '-' || false !== strpos($args[0], '=')) {
                $controller = $defaultController;
            } else {
                $controller = array_shift($args);
            }
        }

        $controller = $this->router->getRouterAlias($controller);
        $this->get($controller, $args);
    }

    /**
     * DI
     *
     * @param string $name
     * @param mixed $obj
     * @return mixed
     */
    function di(string $name, $obj)
    {
        return $this->dii[$name] = $obj;
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
        $this->actionContainer->add($name, $f);
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
     * DII
     *
     * @param string|null $name
     * @return array
     */
    function dii(string $name = null)
    {
        if (null !== $name) {
            return $this->dii[$name] ?? null;
        }

        return $this->dii;
    }

    /**
     * 运行时环境变量
     *
     * @param string $key
     * @param mixed $newVal
     * @return array|string
     */
    static function env(string $key, $newVal = null)
    {
        if (null !== $newVal) {
            self::$env->update($key, $newVal);
        } else {
            $newVal = self::$env->query($key);
        }

        return $newVal;
    }

    /**
     * 获取app名称
     *
     * @return string
     */
    function getAppName(): string
    {
        return $this->appName;
    }

    /**
     * 设置app命名空间
     *
     * @param string $appName
     */
    function setAppName(string $appName): void
    {
        $this->appName = $appName;
        $namespace = str_replace('/', '\\', $appName);
        if (!$this->useNamespace) {
            $namespace = 'app\\' . $namespace;
        }

        $this->appNamespace = $namespace;
    }

    /**
     * 获取app命名空间
     *
     * @return string
     */
    function getAppNamespace(): string
    {
        return $this->appNamespace;
    }

    /**
     * 是否多app模式
     *
     * @return bool
     */
    function onMultiAppMode(): bool
    {
        return $this->multiAppMode;
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
        return $this->runtimeConfig;
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
        return $this->actionContainer;
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
     * @return RequestMapping
     */
    function getRequestMapping(): RequestMapping
    {
        return RequestMapping::getInstance();
    }

    /**
     * 实例化框架
     *
     * @param string $app
     * @param bool $useNamespace
     * @param array $runtimeConfig
     * @return static
     * @throws CoreException
     * @throws FrontException
     */
    private static function initApp(string $app, bool $useNamespace, array $runtimeConfig = []): self
    {
        if (!isset(self::$instance[$app])) {
            self::$instance[$app] = new Delegate($app, $useNamespace, $runtimeConfig);
        }

        return self::$instance[$app];
    }

    /**
     * 初始化App配置
     *
     * @param string $appName
     * @param array $runtimeConfig
     * @return Config
     * @throws FrontException
     * @throws CoreException
     */
    private function initConfig(string $appName, array $runtimeConfig): Config
    {
        $request = $this->getRequest();

        $host = $request->getHostInfo();
        $indexName = $request->getIndexName();
        $requestUrl = $request->getBaseUrl();
        $scriptPath = $request->getScriptFilePath();

        $appNamespace = $this->getAppNamespace();
        $appPath = PROJECT_REAL_PATH . str_replace('\\', DIRECTORY_SEPARATOR, $appNamespace) . DIRECTORY_SEPARATOR;

        //app名称和路径
        $runtimeConfig['app'] = [
            'name' => $appName,
            'path' => $appPath
        ];

        $envConfig = [
            //url相关设置
            'url' => [
                'host' => $host,
                'index' => $indexName,
                'request' => $requestUrl,
                'full_request' => $host . $requestUrl
            ],

            //配置和缓存的绝对路径
            'path' => [
                'cache' => PROJECT_REAL_PATH . 'cache' . DIRECTORY_SEPARATOR,
                'config' => PROJECT_REAL_PATH . 'config' . DIRECTORY_SEPARATOR,
                'script' => $scriptPath . DIRECTORY_SEPARATOR,
            ],

            //静态文件url和绝对路径
            'static' => [
                'url' => $host . $requestUrl . '/static/',
                'path' => $scriptPath . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR
            ]
        ];

        if ($this->multiAppMode) {
            $Config = Config::load(PROJECT_REAL_PATH . 'config' . DIRECTORY_SEPARATOR . 'app.init.php');
        } else {
            $Config = Config::load($appPath . 'init.php');
        }

        //默认环境
        $Config->combine($envConfig);

        //运行时配置
        $Config->combine($runtimeConfig);

        //app共享配置
        $appConfigFile = PROJECT_REAL_PATH . 'config' . DIRECTORY_SEPARATOR . 'app.config.php';
        if (file_exists($appConfigFile)) {
            $appConfig = Config::load($appConfigFile)->getAll();
            if (!empty($appConfig)) {
                $Config->combine($appConfig, false);
            }
        }

        return $Config;
    }

    /**
     * 设置运行所需常量
     */
    private function initConstant()
    {
        defined('PROJECT_PATH') or die('Requires PROJECT_PATH');

        //项目及框架根目录
        defined('PROJECT_REAL_PATH') or define('PROJECT_REAL_PATH', rtrim(PROJECT_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
        defined('CP_PATH') or define('CP_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
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
