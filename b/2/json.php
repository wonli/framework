<?php define('DOCROOT', realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR);
/**
* @Author: wonli <wonli@live.com>
*/

define("DS", DIRECTORY_SEPARATOR);
define("APP_PATH_DIR", DOCROOT.'app'.DS);

$config["url"] = array("rewrite"=>false);

require DOCROOT.'./crossphp/boot.php';
Cross::loadApp( 'home', $config )->get("Main:json");
