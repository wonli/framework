<?php

if( defined("SAE_APPNAME") )
{
	$mysql_link = array(
		'host' => SAE_MYSQL_HOST_M,
		'port' => SAE_MYSQL_PORT,
		'user' => SAE_MYSQL_USER,
		'pass' => SAE_MYSQL_PASS,
		'name' => SAE_MYSQL_DB,
		'charset' => 'utf8',
	);

} else {

	$mysql_link = array(
		'host' => '127.0.0.1',
		'port' => '3306',
		'user' => 'root',
		'pass' => '123456',
		'name' => 'blog',
		'charset' => 'utf8',
	);

}

$redis_link = array(
    'host' => '127.0.0.1',
    'port' => 6379,
    'auth' => '',
    'timeout' => 2.5
);

#默认数据库配置
$db = array();
$db = $mysql_link;

/*mongodb*/
$db5["dsn"] = "mongodb://192.168.1.100:27017";

/*redis front*/
$db6 = $redis_link;
$db6["db"]   = 12;

/*redis backend*/
$db7 = $redis_link;
$db7["db"]   = 13;

$init["mysql"]["db"]  = $db;

$init["mongo"]["db1"] = $db5;

$init["redis"]["front"] = $db6;
$init["redis"]["backend"] = $db7;
return $init;
