<?php
/**
 * @Auth: wonli <wonli@live.com>
 * cron.php
 *
 * usage:
 * php E:\cp\crossphp\demo\htdocs\web\cron.php tag:index 2 3 4
 */

require 'E:\cp\crossphp\demo\crossboot.php';

array_shift($argv);
array_shift($argv);

Cross::loadApp( 'web' )->get( call_user_func(function() use ($p) {
    list($_, $controller) = $p;
    return $controller;
}), $argv);

