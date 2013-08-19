<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:       wonli
 * @Version: $Id: Loader.php 117 2013-08-18 13:08:34Z ideaa $
 */
class Loader
{
    /**
     * @var array 已加载类
     */
    static private $loaded = array();

    /**
     * @var loader的实体
     */
    static private $instance;

    /**
     * @var array 加载的文件
     */
    static private $load_file = array();

    /**
     * @var array 核心类列表
     */
    static private $coreClass;

    private function __construct( )
    {
        self::$coreClass = self::getCoreClass();
        spl_autoload_register(array("Loader","autoLoad"));
    }

    /**
     * 单例模式运行Loader
     *
     * @return Loader
     */
    public static function init( )
    {
        if(! self::$instance) {
            self::$instance = new Loader( );
        }
        return self::$instance;
    }

    /**
     * 核心类路径配置
     *
     * @return array
     */
    private static function getCoreClass()
    {
        return array
        (
            'RouterInterface'   => 'interface/RouterInterface.php',
            'CacheInterface'    => 'interface/CacheInterface.php',
            'SqlInterface'      => 'interface/SqlInterface.php',

            'Loader'          => 'core/Loader.php',
            'Dispatcher'      => 'core/Dispatcher.php',
            'FrameBase'       => 'core/FrameBase.php',
            'Config'          => 'core/Config.php',

            'CoreController'  => 'core/CoreController.php',
            'CoreModule'      => 'core/CoreModule.php',
            'CoreView'        => 'core/CoreView.php',

            'CoreModel'       => 'model/CoreModel.php',
            'MysqlModel'      => 'model/MysqlModel.php',
            'MongoModel'      => 'model/MongoModel.php',

            'Request'         => 'core/Request.php',
            'Response'        => 'core/Response.php',
            'Router'          => 'core/Router.php',
            'UrlRouter'       => 'core/UrlRouter.php',
            'Helper'          => 'core/Helper.php',
            'HttpAuth'        => 'core/HttpAuth.php',
            'CrossArray'      => 'core/CrossArray.php',

            'Mcrypt'          => 'core/Mcrypt.php',
            'DEcode'          => 'core/DEcode.php',
            'HexCrypt'        => 'core/HexCrypt.php',

            'Cache'           => 'cache/Cache.php',
            'ControllerCache' => 'cache/ControllerCache.php',
            'FileCache'       => 'cache/FileCache.php',
            'RedisCache'      => 'cache/RedisCache.php',

            'CrossException'    => 'exception/CrossException.php',
            'CoreException'     => 'exception/CoreException.php',
            'FrontException'    => 'exception/FrontException.php',
            'CacheException'    => 'exception/CacheException.php',

            'Page'          => 'lib/Page.php', //分页
            'MySql'         => 'lib/pdo_mysql.php',
            'MongoBase'     => 'lib/MongoBase.php',
            'DataAccess'    => 'lib/DataAccess.php',
            'resizeimage'   => 'lib/resizeimage.php', //图片剪裁
            'PdoAccess'     => 'lib/PdoAccess.php',
            'Captcha'       => 'lib/Captcha.php', //头像
            'AImages'       => 'lib/AImages.php', //图片上传
            'ImagesThumb'   => 'lib/ImagesThumb.php', //图片剪裁
            'Tree'          => 'lib/Tree.php', //格式化树
            'Crumb'         => 'lib/Crumb.php', //验证字符串
            'CacheRedis'	=> 'lib/CacheRedis.php',
            'Uploader'	    => 'lib/Uploader.php',
        );
    }

    /**
     * 载入其他类
     *
     * @param $class 类名称 <pre>
     *  用法如下:
     *
     *  1 file_name 直接指定文件路径
     *  2 ::[path/]file_name 从当前项目根目录查找
     *  3 app::[path/]file_name 当前app路径
     *  4 core::[path/]file_name 核心目录
     * </pre>
     * @param bool $return
     * @return mixed
     * @throws CoreException
     */
    static public function import( $class, $return = false )
    {
        $list = Loader::getFileRealPath($class);

        foreach($list as $file)
        {
            $cache_key = crc32($file);
            if(isset( self::$loaded [$cache_key] ) ) {
                return ;
            }

            if(file_exists($file))
            {
                if(true === $return) {
                    return require $file;
                } else {
                    require $file;
                }
                self::$loaded [$cache_key] = 1; //标识已载入
            } else throw new CoreException("未找到要载入的文件:{$file}");
        }
    }

    /**
     * 根据给定的参数解析文件的绝对路径
     *
     * @param $class 类名称 <pre>
     *  格式如下:
     *
     *  1 file_name 直接指定文件路径
     *  2 ::[path/]file_name 从当前项目根目录查找
     *  3 app::[path/]file_name 当前app路径
     *  4 core::[path/]file_name 核心目录
     * </pre>
     * @param $class
     * @return array
     */
    static function getFileRealPath( $class )
    {
        $files = $list = array();
        $_defines = array (
            'app' => APP_PATH,
            'core' => CORE_PATH,
            'project' => DOCROOT
        );

        if(is_array($class))
        {
            $files = $class;
        }
        else
        {
            if(false !== strpos($class, ","))
            {
                $files = explode("," , $class);
            }
            else
            {
                $files[] = $class;
            }
        }

        foreach($files as $f)
        {
            if(false !== strpos($f, '::'))
            {
                list($path, $file_info) = explode('::', $f);

                if(! $path)
                {
                    $path = "project";
                }

                $list [] = rtrim( $_defines[strtolower($path)].DS.str_replace("/", DS, $file_info), '.php').'.php';
            }
            else
            {
                $list [] = $f;
            }
        }

        return $list;
    }

    /**
     * autoload函数
     *
     * @param $classname
     * @return bool
     */
    static function autoLoad($classname)
    {
        if( isset(self::$coreClass [$classname]) )
        {
            $file_real_path= CROSSPHP_PATH.self::$coreClass[$classname];
        }
        else
        {
            if( 'Module' === substr($classname, -6) )
            {
                return spl_autoload_register(array("Loader","module_load"));
            }
            else if( 'View' === substr($classname, -4) )
            {
                $_filetype = 'views';
            }
            else
            {
                $_filetype = 'controllers';
            }

            if(! isset($file_real_path) && $_filetype)
            {
                $_filepath = Cross::config()->get("sys", "app_path").DS.$_filetype.DS;
                $file_real_path = $_filepath.$classname.'.php';
            }
            
            if( ! is_file($file_real_path) )
            {
                return false;
            }
        }

        require $file_real_path;        
    }

    /**
     * Module自动加载
     *
     * @param $module_name
     * @throws CoreException
     */
    static function module_load( $module_name )
    {
        $_filepath = DOCROOT."modules".DS;
        $file_real_path = $_filepath.$module_name.".php";

        if( file_exists( $file_real_path ) )
        {
            require($file_real_path);
        }
    }
}

