<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:  wonli <wonli@live.com>
 * @Version: $Id: Loader.php 146 2013-09-29 03:21:24Z ideaa $
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

    /**
     * 初始化Loader
     */
    private function __construct( $app_name )
    {
        $this->app_name = $app_name;
        self::$coreClass = self::getCoreClass();
        spl_autoload_register(array("Loader","autoLoad"));
    }

    /**
     * 单例模式运行Loader
     *
     * @return Loader
     */
    public static function init( $app_name )
    {
        if(! isset(self::$instance [$app_name])) {
            self::$instance [$app_name] = new Loader( $app_name );
        }
        return self::$instance [$app_name];
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

            'Widget'          => 'core/Widget.php',
            'Loader'          => 'core/Loader.php',
            'Dispatcher'      => 'core/Dispatcher.php',
            'CoreWidget'      => 'core/CoreWidget.php',
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

            'CoreCache'       => 'cache/CoreCache.php',
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
            'ReSizeImage'   => 'lib/ReSizeImage.php', //图片剪裁
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
     * 载入文件(支持多文件载入)
     *
     * @param $files 参见Loader::parseFileRealPath()
     * @return mixed
     * @throws CoreException
     */
    static public function import( $files )
    {
        $list = Loader::parseFileRealPath($files);

        foreach($list as $file)
        {
            $cache_key = crc32($file);
            if(isset( self::$loaded [$cache_key] ) ) {
                return ;
            }

            if(file_exists($file))
            {
                self::$loaded [$cache_key] = 1; //标识已载入
                require $file;
            } else throw new CoreException("未找到要载入的文件:{$file}");
        }
    }

    /**
     * 读取指定的单一文件
     *
     * @param $file 参见Loader::getFileRealPath()
     * @param bool $parse_file 是否解析文件路径
     * @return mixed
     * @throws CoreException
     */
    static public function read( $file, $parse_file = true )
    {
        if(true === $parse_file)
        {
            $parse_path = Loader::parseFileRealPath( $file, '' );
            $file_path = current( $parse_path );
        }
        else
        {
            $file_path = $file;
        }

        $key = crc32($file_path);
        if( isset(self::$loaded [ $key ]) )
        {
            return self::$loaded [ $key ];
        }

        if( file_exists($file_path) )
        {
            $ext = Helper::getExt($file_path);
            switch($ext)
            {
                case 'php' :
                    $data = require $file_path;
                    self::$loaded [$key] = $data;
                    return $data;

                case 'json' :
                    $data = json_decode( file_get_contents($file_path), true);
                    self::$loaded [$key] = $data;
                    return $data;

                default :
                    throw new CoreException("不支持的解析格式");
            }
        }
        else throw new CoreException("未找到要载入的文件:{$file}");
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
    static function parseFileRealPath( $class, $append_file_ext=".php" )
    {
        $files = $list = array();
        $_defines = array (
            'app' => APP_PATH,
            'core' => CP_CORE_PATH,
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

                $list [] = rtrim( rtrim($_defines[strtolower($path)], DS).DS.str_replace("/", DS, $file_info), $append_file_ext).$append_file_ext;
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
    function autoLoad($classname)
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
                $_filepath = APP_PATH_DIR.DS.$this->app_name.DS.$_filetype.DS;
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

