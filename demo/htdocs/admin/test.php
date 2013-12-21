<?php
/**
* @Author:       wonli
*/
session_start();
require '../../crossboot.php';

$admin = Cross::loadApp( 'admin' );

$admin->map("/", "admin:login");
$admin->map("/2", "admin:haha");
$admin->map("/news/:d+/", "news/$1");

$admin->mrun();




