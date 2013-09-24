<?php defined('DOCROOT')or die('Access Denied');
#默认数据库配置
$db = array();

if( defined("SAE_APPNAME") )
{
    $db["host"]  = SAE_MYSQL_HOST_M;
    $db["port"]  = SAE_MYSQL_PORT;
    $db["user"]  = SAE_MYSQL_USER;
    $db["pass"]  = SAE_MYSQL_PASS;
    $db["name"]  = SAE_MYSQL_DB;
} else {
    $db["host"]  = '127.0.0.1';
    $db["port"]  = '3306';
    $db["user"]  = 'root';
    $db["pass"]  = '000000';
    $db["name"]  = 'ideaa';
}

$db["charset"]  = 'utf8';

$db["dsn"] = "mysql:host=".$db["host"].";dbname=".$db["name"].";port=".$db["port"].";charset=".$db["charset"];

$init["mysql"]["db"]   = $db;

return $init;