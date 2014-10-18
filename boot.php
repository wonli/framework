<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.3
 */

//DIRECTORY_SEPARATOR
define('DS', DIRECTORY_SEPARATOR);

//框架路径
define('CP_PATH', realpath(dirname(__FILE__)) . DS . 'src' . DS);

require CP_PATH . 'Core/Loader.php';
Cross\Core\Loader::init();
class_alias('Cross\Core\Delegate', 'Cross');
