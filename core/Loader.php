<?php
/**
 * @Author:  wonli <wonli@live.com>
 * @Version: $Id: Loader.php 166 2013-11-06 07:52:21Z ideaa $
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
        spl_autoload_register(array($this, "autoLoad"));
    }

    /**
     * 单例模式运行Loader
     *
     * @param $app_name
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
            'Rest'            => 'core/Rest.php',
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
            'CouchModel'      => 'model/CouchModel.php',

            'Request'         => 'core/Request.php',
            'Response'        => 'core/Response.php',
            'Router'          => 'core/Router.php',
            'UrlRouter'       => 'core/UrlRouter.php',
            'Helper'          => 'core/Helper.php',
            'CrossArray'      => 'core/CrossArray.php',
            'ArrayMap'        => 'core/ArrayMap.php',
            'Annotate'        => 'core/Annotate.php',
            'HttpAuth'        => 'core/HttpAuth.php',

            'CookieAuth'      => 'auth/CookieAuth.php',
            'SessionAuth'     => 'auth/SessionAuth.php',

            'CoreCache'             =>  'cache/CoreCache.php',
            'FileCache'             =>  'cache/FileCache.php',
            'RedisCache'            =>  'cache/RedisCache.php',
            'RequestCache'          =>  'cache/RequestCache.php',
            'MemcacheBase'          =>  'cache/MemcacheBase.php',
            'ControllerCache'       =>  'cache/ControllerCache.php',
            'RequestMemcache'       =>  'cache/RequestMemcache.php',
            'RequestRedisCache'     =>  'cache/RequestRedisCache.php',

            'CrossException'    => 'exception/CrossException.php',
            'CoreException'     => 'exception/CoreException.php',
            'FrontException'    => 'exception/FrontException.php',
            'CacheException'    => 'exception/CacheException.php',

            'HttpAuthInterface' =>  'interface/HttpAuthInterface.php',
            'RouterInterface'   =>  'interface/RouterInterface.php',
            'CacheInterface'    =>  'interface/CacheInterface.php',
            'SqlInterface'      =>  'interface/SqlInterface.php',

            'Page'          =>  'lib/Page.php', //分页
            'MysqlSimple'   =>  'lib/MysqlSimple.php',
            'MongoBase'     =>  'lib/MongoBase.php',
            'ImageCut'      =>  'lib/ImageCut.php', //图片剪裁
            'ImageThumb'    =>  'lib/ImageThumb.php', //生成缩略图
            'PdoAccess'     =>  'lib/PdoAccess.php',
            'Captcha'       =>  'lib/Captcha.php', //头像
            'AImages'       =>  'lib/AImages.php', //图片上传
            'UploadImages'  =>  'lib/UploadImages.php', //图片上传
            'Tree'          =>  'lib/Tree.php', //格式化树
            'Crumb'         =>  'lib/Crumb.php', //验证字符串
            'Uploader'	    =>  'lib/Uploader.php',
            'Mcrypt'        =>  'lib/Mcrypt.php',
            'DEcode'        =>  'lib/DEcode.php',
            'HexCrypt'      =>  'lib/HexCrypt.php',
            'DESMcrypt'     =>  'lib/DESMcrypt.php',
            'Mcrypt'        =>  'lib/Mcrypt.php',
            'PYInitials'    =>  'lib/PYInitials.php',
            'Array2XML'     =>  'lib/Array2XML.php',
        );
    }

    /**
     * 载入文件(支持多文件载入)
     *
     * @see Loader::parseFileRealPath()
     * @param $files
     * @return mixed
     * @throws CoreException
     */
    static public function import( $files )
    {
        $list = Loader::parseFileRealPath($files);
        foreach($list as $file)
        {
            $cache_key = crc32($file);
            if (isset( self::$loaded [$cache_key] ) ) {
                return ;
            }

            if (file_exists($file))
            {
                self::$loaded [$cache_key] = 1; //标识已载入
                require $file;
            } else throw new CoreException("未找到要载入的文件:{$file}");
        }
    }

    /**
     * 读取指定的单一文件
     *
     * @param $file Loader::parseFileRealPath()
     * @param bool $read_file 是否读取文件内容
     * @return mixed
     * @throws CoreException
     */
    static public function read( $file, $read_file = true )
    {
        if (file_exists($file)) {
            $file_path = $file;
        } else {
            $file_path = Loader::getFilePath( $file );
        }

        $key = crc32($file_path);
        $read_file_flag = $read_file ? 1 : 0;
        if (isset(self::$loaded [$read_file_flag][ $key ]) ) {
            return self::$loaded [$read_file_flag][ $key ];
        }

        if (is_readable($file_path) )
        {
            if (false === $read_file)
            {
                $file_content = file_get_contents( $file_path );
                self::$loaded [$read_file_flag][ $key ] = $file_content;
                return $file_content;
            }

            $ext = Helper::getExt($file_path);
            switch($ext)
            {
                case 'php' :
                    $data = require $file_path;
                    self::$loaded [$read_file_flag][$key] = $data;
                    return $data;

                case 'json' :
                    $data = json_decode( file_get_contents($file_path), true);
                    self::$loaded [$read_file_flag][$key] = $data;
                    return $data;

                default :
                    throw new CoreException("不支持的解析格式");
            }
        }
        else throw new CoreException("读取文件失败:{$file}");
    }

    /**
     * 根据给定的参数解析文件的绝对路径
     * <pre>
     *  格式如下:
     *
     *  1 file_name 直接指定文件路径
     *  2 ::[path/]file_name 从当前项目根目录查找
     *  3 app::[path/]file_name 当前app路径
     *  4 core::[path/]file_name 核心目录
     * </pre>
     *
     * @param $class
     * @param string $append_file_ext
     * @return array
     */
    static function parseFileRealPath( $class, $append_file_ext=".php" )
    {
        $files = $list = array();
        $_defines = array (
            'app'       =>  defined("APP_PATH")?APP_PATH:'',
            'core'      =>  CP_PATH.'core',
            'static'    =>  STATIC_PATH,
            'project'   =>  PROJECT_PATH,
        );

        if (is_array($class))
        {
            $files = $class;
        }
        else
        {
            if (false !== strpos($class, ","))
            {
                $files = explode("," , $class);
            }
            else
            {
                $files[] = $class;
            }
        }

        foreach ($files as $f)
        {
            if(false !== strpos($f, '::'))
            {
                list($path, $file_info) = explode('::', $f);
                if (! $path)
                {
                    $path = "project";
                }

                $file_real_path = rtrim($_defines[strtolower($path)], DS).DS.str_replace("/", DS, $file_info);
                $file_path_info = pathinfo( $file_real_path );
                if (! isset($file_path_info['extension']) )
                {
                    $file_real_path .= $append_file_ext;
                }

                $list [] = $file_real_path;
            }
            else
            {
                $list [] = $f;
            }
        }

        return $list;
    }

    /**
     * @see Loader::parseFileRealPath
     *
     * @param $file
     * @return mixed
     */
    static function getFilePath( $file )
    {
        return current( Loader::parseFileRealPath($file, '') );
    }

    /**
     * 自动加载函数
     *
     * @param $class_name
     * @return bool
     */
    function autoLoad($class_name)
    {
        if (isset(self::$coreClass [$class_name])) {
            $file_real_path= CP_PATH.self::$coreClass[$class_name];
        }
        else
        {
            if ( 'Module' === substr($class_name, -6) ) {
                return spl_autoload_register(array("Loader","module_load"));
            } elseif( 'View' === substr($class_name, -4) ) {
                $_file_type = 'views';
            } else {
                $_file_type = 'controllers';
            }

            if (! isset($file_real_path) && $_file_type)
            {
                $_file_path = APP_PATH_DIR.DS.$this->app_name.DS.$_file_type.DS;
                if (false !== strpos($class_name, '\\')) {
                    $_file_path = PROJECT_PATH.DS;
                }
                $file_real_path = $_file_path.$class_name.'.php';
            }

            if (! is_file($file_real_path)) {
                return false;
            }
        }

        self::$loaded[ $class_name ] = $file_real_path;
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
        $_file_path = PROJECT_PATH."modules".DS;
        if (false !== strpos($module_name, '\\'))
        {
            $module_name = str_replace('\\', DS, ltrim($module_name, DS));
            $_file_path = PROJECT_PATH;
        }

        $file_real_path = $_file_path.$module_name.".php";
        if (file_exists( $file_real_path )) {
            require($file_real_path);
        }
    }
}

