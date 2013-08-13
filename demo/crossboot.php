<?php defined('DOCROOT')or  define('DOCROOT', realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR);
/**
* @Author:       wonli <wonli@live.com>
*/
define('APP_PATH_DIR', DOCROOT.'app');
define('COOKIE_DOMAIN', '');

date_default_timezone_set('Asia/Chongqing');
header("Content-Type:text/html; charset=utf-8");

require DOCROOT.'../crossphp/boot.php';
