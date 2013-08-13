<?php defined('DOCROOT')or die('Access Denied');


$mysql_link = array(
    'host' => '127.0.0.1',
    'port' => '3306',
    'user' => 'root',
    'pass' => '123456',
    'charset' => 'utf8',
);


$redis_link = array(
    'host' => '127.0.0.1',
    'port' => 6379,
    'auth' => '',
    'timeout' => 2.5
);

#默认数据库配置
$db = array();
$db = $mysql_link;
$db['name'] = 'test';
$db['dsn'] = "mysql:host=".$db["host"].";dbname=".$db["name"].";port=".$db["port"].";charset=".$db["charset"];

/*数据库1*/
$db1 = array();
$db1 = $mysql_link;
$db1['name'] = 'test';
$db1['dsn'] = "mysql:host=".$db1["host"].";dbname=".$db1["name"].";port=".$db1["port"].";charset=".$db1["charset"];

/*mongodb*/
$db5["dsn"] = "mongodb://192.168.1.100:27017";

/*redis front*/
$db6 = $redis_link;
$db6["db"]   = 12;

/*redis backend*/
$db7 = $redis_link;
$db7["db"]   = 13;

$init["mysql"]["db"]  = $db;
$init["mysql"]["log"] = $db1;

$init["mongo"]["db1"] = $db5;

$init["redis"]["front"] = $db6;
$init["redis"]["backend"] = $db7;
return $init;
