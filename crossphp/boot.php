<?php
/**
 * @Author:  wonli <wonli@live.com>
 */
defined('DS')or define('DS', DIRECTORY_SEPARATOR);
define('CROSSPHP_PATH', realpath(dirname(__FILE__)).DS);
define('CP_CORE_PATH', CROSSPHP_PATH.'core');

require CP_CORE_PATH . DS .'Cross.php';
require CP_CORE_PATH . DS .'Loader.php';
