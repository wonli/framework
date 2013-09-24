<?php 
/**
* Author:       wonli
* Contact:      wonli@live.com
* Date:         2011.08
* Description:  config
*/
defined('DS')or define('DS', DIRECTORY_SEPARATOR);
defined('CROSSPHP_PATH')or  define('CROSSPHP_PATH', realpath(dirname(__FILE__)).DS);
define('CORE_PATH', CROSSPHP_PATH.'core');

require CORE_PATH.DS.'Cross.php';