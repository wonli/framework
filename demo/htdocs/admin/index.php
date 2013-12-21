<?php
/**
* @Author:       wonli
*/
session_start();
require '../../crossboot.php';

if( !empty($_SESSION["admin"]) )
{
    Cross::loadApp( 'admin' )->run();
}
else
{
    Cross::loadApp( 'admin' )->get("Admin:login");
}

