<?php
/**
* @Author:   wonli <wonli@live.com>
*/
define("__DEBUG__", false);
require '../../crossboot.php';

/**
 * 从url中解析控制器示例(自定义路由)
 * {{{
 */
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
    $controller = 'article:index';
}
/**
 * }}}
 */

if(__DEBUG__)
{

    Cross::loadApp( 'api' )->get( $controller, $_REQUEST );

} else {

    try {

        Cross::loadApp( 'api' )->get( $controller, $_REQUEST );

    } catch(Exception $e) {

        Response::getInstance( 'json' )->output("200", json_encode(
            array( 'ret'=>999, 'error'=>$e->getMessage() )
        ));
    }

}

