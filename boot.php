<?php 
/**
 * @Author:  wonli <wonli@live.com>
 */
defined('DS')or define('DS', DIRECTORY_SEPARATOR);
define('CROSSPHP_PATH', realpath(dirname(__FILE__)).DS);
define('CORE_PATH', CROSSPHP_PATH.'core');

require CORE_PATH.DS.'Loader.php';
require CORE_PATH.DS.'Cross.php';

Loader::init();