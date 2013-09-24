<?php
/**
* @Author:       wonli
*/
session_start();
require '../../crossboot.php';



$web = Cross::loadApp( 'web' )->run();












/*
class a
{

	function __construct( $name )
	{
		$this->name = $name;
	}

	static function init( $name )
	{
		return new a($name);
	}

	function get_name()
	{
		return $this->name;
	}
}



echo '<br>---------------<br>';
$e = a::init('d');
$d = a::init('e');

echo $d->get_name();
echo $e->get_name();
*/







