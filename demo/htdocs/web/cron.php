<?php
/**
 * @Auth: wonli <wonli@live.com>
 * cron.php
 *
 * usage:
 * php E:\cp\crossphp\demo\htdocs\web\cron.php tag:index 2 3 4
 */
define('IS_CLI', PHP_SAPI === 'cli');

if(! IS_CLI)
{
    die('this app is only run in cli mode');
}

$this_file_path = $argv[0];
array_shift($argv);

$THIS_DIR = (realpath(dirname($this_file_path)).DIRECTORY_SEPARATOR).'';
require "{$THIS_DIR}/../../crossboot.php";

/**
 * 默认控制器
 */
$controller = 'main:index';
$params = array();

if(! empty($argv))
{
    $controller = $argv[0];
    array_shift($argv);
    $params = $argv;
}

try
{
    Cross::loadApp( 'cron' )->get($controller, $params);

} catch (Exception $e) {

    /**
     * Cron失败时写日志
     */
    $cron_log = $THIS_DIR."/log/log_".date("Y_m");
    if(! file_exists($cron_log))
    {
        Helper::mkfile($cron_log, 0777);
    }

    $fp = fopen($cron_log, "a");
    fwrite($fp, date("Y-m-d H:i:s", TIME).'  '.$e->getMessage()."\r\n");
    fclose($fp);

    die("error: ".$e->getMessage());
}

