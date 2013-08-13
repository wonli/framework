<?php 
/**
* @Author:   wonli <wonli@live.com>
*/
define("__DEBUG__", true);
require '../../crossboot.php';
//解析请求
$controller = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
if( $controller )
{
    if(false !== strpos($controller, '.')) {
        $controller = str_replace('.', ':', $controller);
    } else {
        $controller = "{$controller}:index";
    }
    unset($_REQUEST['mode']);
} else {
    $controller = 'main:index';
}

if(__DEBUG__)
{

    Cross::loadApp( 'api' )->get( $controller, $_REQUEST );

} else {

    try {

        Cross::loadApp( 'api' )->get( $controller, $_REQUEST );

    } catch(Exception $e) {

        Response::getInstance( 'json' )->output("200", json_encode(
            array('ret'=>999, 'error'=>888)
        ));

    }

}

