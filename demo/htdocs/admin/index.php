<?php
/**
* @Author:       wonli
*/
session_start();
require '../../crossboot.php';

try{
    if( !empty($_SESSION["admin"]) )
    {
        Cross::loadApp( 'admin' )->run();
    }
    else
    {
        Cross::loadApp( 'admin' )->get("Admin:login");
    }
} catch (Exception $e) {
    $file = Loader::getFilePath( '::cache/install/install.Lock' );
    if(! file_exists($file)) {
        header("LOCATION:".HTDOCS_URL."install");
    }
}
