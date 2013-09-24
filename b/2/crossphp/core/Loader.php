<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
* Author:       wonli
* Contact:	    wonli@live.com
* Date:	        2011.08
* Description:  loader
*/
class Loader
{
    static private $isload = array();
    static private $instance;
    static private $coreClass;

    private function __construct( )
    {
        self::$coreClass = self::getCoreClass();
        spl_autoload_register('Loader::autoLoad');
    }

    /**
     * 实例化类
     */
    public static function getInstance(  )
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

            'Loader'        => 'core/Loader.php',
            'Dispatcher'    => 'core/Dispatcher.php',
            'FrameBase'     => 'core/FrameBase.php',
            'Config'        => 'core/Config.php',
            'Cache'         => 'core/Cache.php',

            'CrossException'    => 'exception/CrossException.php',
            'CoreException'     => 'exception/CoreException.php',
            'FrontException'    => 'exception/FrontException.php',
            'CacheException'    => 'exception/CacheException.php',

            'CoreController' => 'core/CoreController.php',
            'CoreModel'      => 'core/CoreModel.php',
            'CoreView'       => 'core/CoreView.php',

            'Request'        => 'core/Request.php',
            'Response'       => 'core/Response.php',
            'Router'         => 'core/Router.php',
            'UrlRouter'      => 'core/UrlRouter.php',
            'Helper'         => 'core/Helper.php',

            'Mcrypt'         => 'core/Mcrypt.php',
            'DEcode'         => 'core/DEcode.php',
            'HexCrypt'       => 'core/HexCrypt.php',            
            
            'Page'          => 'lib/Page.php',
            'tsImg'         => 'lib/class.image.php',
            'MySql'         => 'lib/pdo_mysql.php',
            'MongoBase'     => 'lib/MongoBase.php',
            'DataAccess'    => 'lib/DataAccess.php',
            'resizeimage'   => 'lib/resizeimage.php',
            'PdoDataAccess' => 'lib/PdoDataAccess.php',
        );
    }

    public static function isload($_classname)
    {

    }

    /**
     * 自动加载函数
     */
    static function autoLoad($classname)
    {
        if( isset(self::$coreClass[$classname]) ) {
            $_filename= CROSSPHP_PATH.self::$coreClass[$classname];
        } else {

            if( 'Model' === substr($classname, -5) ) {
                $_filetype = 'models';
            }
            else if( 'View' === substr($classname, -4) ) {
                $_filetype = 'views';
            }
            else {
                $_filetype = 'controllers';
            }

            $_filepath = Cross::config()->get("sys", "app_path").DS.$_filetype.DS;
            $_filename = $_filepath.$classname.'.php';

            if( ! is_file($_filename) ) {
                throw new FrontException($classname.' is not found !');
            }
        }
        require $_filename;
    }
}

