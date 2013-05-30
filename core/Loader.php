<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:       wonli
 * @Version: $Id: Loader.php 79 2013-05-24 09:20:46Z ideaa $
 */
class Loader
{
    static private $loaded = array();
    static private $instance;
    static private $load_file = array();
    static private $coreClass;

    private function __construct( )
    {
        self::$coreClass = self::getCoreClass();
        spl_autoload_register(array("Loader","autoLoad"));
    }

    /**
     * 实例化类
     */
    public static function init( )
    {
        if(! self::$instance) {
            self::$instance = new Loader( );
        }
        return self::$instance;
    }

    private static function getCoreClass()
    {
        return array
        (
            'RouterInterface'   => 'interface/RouterInterface.php',
            'CacheInterface'    => 'interface/CacheInterface.php',

            'Loader'          => 'core/Loader.php',
            'Dispatcher'      => 'core/Dispatcher.php',
            'FrameBase'       => 'core/FrameBase.php',
            'Config'          => 'core/Config.php',
            'Cache'           => 'core/Cache.php',

            'CoreController'  => 'core/CoreController.php',
            'CoreModule'      => 'core/CoreModule.php',
            'CoreModel'       => 'core/CoreModel.php',
            'CoreView'        => 'core/CoreView.php',

            'Request'         => 'core/Request.php',
            'Response'        => 'core/Response.php',
            'Router'          => 'core/Router.php',
            'UrlRouter'       => 'core/UrlRouter.php',
            'Helper'          => 'core/Helper.php',
            'HttpAuth'        => 'core/HttpAuth.php',

            'Mcrypt'          => 'core/Mcrypt.php',
            'DEcode'          => 'core/DEcode.php',
            'HexCrypt'        => 'core/HexCrypt.php',

            'ControllerCache' => 'cache/ControllerCache.php',

            'CrossException'    => 'exception/CrossException.php',
            'CoreException'     => 'exception/CoreException.php',
            'FrontException'    => 'exception/FrontException.php',
            'CacheException'    => 'exception/CacheException.php',

            'Page'          => 'lib/Page.php', //分页
            'tsImg'         => 'lib/class.image.php',
            'MySql'         => 'lib/pdo_mysql.php',
            'MongoBase'     => 'lib/MongoBase.php',
            'DataAccess'    => 'lib/DataAccess.php',
            'resizeimage'   => 'lib/resizeimage.php', //图片剪裁
            'PdoDataAccess' => 'lib/PdoDataAccess.php',
            'Captcha'       => 'lib/Captcha.php', //头像
            'AImages'       => 'lib/AImages.php', //图片上传
            'Tree'          => 'lib/Tree.php', //格式化树
            'Crumb'         => 'lib/Crumb.php', //验证字符串
            'CacheRedis'	=> 'lib/CacheRedis.php',
            'Uploader'	    => 'lib/Uploader.php',
        );
    }

    public static function isload($_classname)
    {

    }

    static function import($path, $file)
    {
        if(is_array($file)) {
            foreach($file as $_file_path) {
                $file_path = DOCROOT.DS.$path.DS.$_file_path.'.php';
                
                if(is_file($file_path) && !in_array($file_path, Loader::$load_file)) {
                    require $file_path;
                    Loader::$load_file[] = $file_path;
                }
                else {
                    throw new CoreException($file.' is not found !');
                }
            }

        } else {
            $file = DOCROOT.$path.DS.$file.'.php';
            if(is_file($file) && !in_array($file, Loader::$load_file)) {
                require $file;
                Loader::$load_file[] = $file;
            }
            else {
                throw new CoreException($file.' is not found !');
            }
        }
    }
    
    /**
     * 自动加载函数
     */
    static function autoLoad($classname)
    {
        if( isset(self::$coreClass[$classname]) ) {
            $file_real_path= CROSSPHP_PATH.self::$coreClass[$classname];
        } else {

            if( 'Module' === substr($classname, -6) ) {
                $_filepath = DOCROOT."modules".DS;
                $file_real_path = $_filepath.$classname.".php";
            }
            else if( 'View' === substr($classname, -4) ) {
                $_filetype = 'views';
            }
            else {
                $_filetype = 'controllers';
            }

            if(! isset($file_real_path) && $_filetype) {
                $_filepath = Cross::config()->get("sys", "app_path").DS.$_filetype.DS;
                $file_real_path = $_filepath.$classname.'.php';
            }
            
            if( ! is_file($file_real_path) ) {
                return false;
            }            
        }
        
        require $file_real_path;        
    }
}

