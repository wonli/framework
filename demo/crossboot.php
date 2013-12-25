<?php
/**
* @Author:       wonli <wonli@live.com>
*/

//必须定义项目路径
defined('PROJECT_PATH')or  define('PROJECT_PATH', realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR);

/**
 * 非必须内容
 * 一些自定义常量可以放这里
 * 自定义常用函数也可以在这里载入
 * {{{
 */
//定义COOKIE作用域
define('COOKIE_DOMAIN', '');
//定义默认时区
date_default_timezone_set('Asia/Chongqing');
//定义HTTP请求返回头格式
header("Content-Type:text/html; charset=utf-8");
/**
 * }}}
 */

//载入框架引导文件
require PROJECT_PATH.'../crossphp/boot.php';
