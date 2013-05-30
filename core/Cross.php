<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:       wonli
 * @version: $Id: Cross.php 77 2013-05-23 13:54:54Z ideaa $
 */
class Cross {

    public  static $appname;

    private static $runtime_config;

    private static $appset;

    private static $instance;

    private static $loaded;
    
    public static $config;

    private function __construct( $appname, $runtime_config )
    {
        self::$appname = $appname;
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
    static function loadApp($appname, $runtime_config = null)
    {
        if(! self::$instance)
        {
            self::$instance = new Cross( $appname, $runtime_config );
        }
        return self::$instance;
    }

    /**
     * 返回配置类对象
     * @return config Object
     */
    static function config( )
    {
        // if(! self::$config) {
            // self::$config = Config::getInstance( self::$appname )->init( self::$runtime_config );
        // }
        // return self::$config;
        
        return Config::getInstance( self::$appname )->init( self::$runtime_config );
    }

    /**
     * 取得所有自定义配置
     * @return array 配置数组
     */
    static function getAppset()
    {
        return self::config()->getInit();
    }

    /*
     *url路由
     */
    static function router()
    {
        return Router::getInstance()->set( self::$appset )->getRouter();
    }

    /**
     * 初始化框架
     */
    private function appInit( )
    {
        self::$appset = self::getAppset();

        $this->definer(array(
            'BASEURL'       => self::$appset["sys"]["site_url"],
            'APP_PATH'      => self::$appset["sys"]["app_path"],
            'SITE_URL'      => self::$appset["sys"]["site_url"],
            'STATIC_URL'    => self::$appset["sys"]["static_url"],
            'STATIC_PATH'   => self::$appset["sys"]["static_path"]
        ));
    }

    /**
     * 定义常用常量
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
     * @param   $args 指定运行时参数
     */
    public function run( $args = null )
    {
        Dispatcher::getInstance( self::config() )->run( self::router(), $args );
    }

    /**
     * 直接调用App的控制器
     *
     * @param $controller "控制器:方法"
     * @param $args 参数
     */
    public function get( $controller, $args = null )
    {
        Dispatcher::getInstance( self::config() )->run( $controller, $args );
    }

    /**
     * 载入核心类
     *
     * @param $class 类名称 如果含有.则载入APP中类
     */
    static public function import($class)
    {
        if(isset(self::$loaded[$class])) {
            return ;
        }

        if( false !== strpos($class, ".") ) {
            $_path = str_replace(".", DS, $class);
            $_class_real_path = APP_PATH.DS.$_path.".php";
            if( file_exists($_class_real_path) ) {

                self::$loaded[$class] = $_class_real_path;
                require $_class_real_path;

            } else throw new CoreException("没有找到该文件");
        } else {
            if(is_array($class)) {
                foreach($class as $class_key) {
                    self::$loaded[$class_key] = $class_key;
                    require CORE_PATH.DS.$class_key.'.php';
                }
            } else {
                self::$loaded[$class] = $class;
                require CORE_PATH.DS.$class.'.php';
            }
        }
    }
}