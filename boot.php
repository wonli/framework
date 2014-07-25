<?php
/**
 * @Author:  wonli <wonli@live.com>
 * 定义一些常量和载入框架入口和自动加载类 其中PROJECT_PATH是必须定义的
 */
/**
 * DIRECTORY_SEPARATOR
 */
defined('DS')or define('DS', DIRECTORY_SEPARATOR);

/**
 * 框架路径
 */
define('CP_PATH', realpath(dirname(__FILE__)).DS);

/**
 * 项目路径
 */
defined('PROJECT_PATH') or die("undefined PROJECT_PATH");

/**
 * 项目APP路径
 */
define('APP_PATH_DIR', rtrim(PROJECT_PATH, DS).DS.'app');

require CP_PATH .'core/Loader.php';
cross\core\Loader::init();
