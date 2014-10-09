<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.3
 */
//定义项目根目录路径
defined('PROJECT_PATH') or die("undefined PROJECT_PATH");

//DIRECTORY_SEPARATOR
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

//项目路径
define('PROJECT_REAL_PATH', rtrim(PROJECT_PATH, DS) . DS);

//框架路径
define('CP_PATH', realpath(dirname(__FILE__)) . DS);

//项目APP路径
define('APP_PATH_DIR', PROJECT_REAL_PATH . 'app');

require CP_PATH . 'Core/Loader.php';
Cross\Core\Loader::init();
class_alias('Cross\Core\Delegate', 'Cross');
