<?php
/**
* @Author:       wonli
*/

session_start();
require '../../crossboot.php';

Cross::loadApp( 'web' )->run();
/*
	其他调用方式及应该用场景

	1.path_info,request_string 不能达到要求时,比如要解析这样的url请求:

        http://htdocs/web/?mode=tag.list&b=1&t=2&m=4
        其中mode指定controller和action用.分隔
        b=1&t=2&m=4 分别为3个参数

        PHP 5.3以上版本可以这样解析

        $r = $_REQUEST;
        unset($_REQUEST['mode']);

        Cross::loadApp( 'web' )->get( call_user_func(function( ) use ($r) {
            return str_replace('.', ':', $r['mode']);
        }), $_REQUEST );

        PHP 5.3 以下可以这样

        $r = str_replace('.', ':', $_REQUEST['mode']);
        unset(_REQUEST['mode']);
        Cross::loadApp( 'web' )->get($r, $_REQUEST);

      如要继续使用init.php中的alias配置项 请参考run方法

	2.需要单个临时页面的时候,比如游戏活动,商品打折等

        Cross::loadApp( 'act' )->get('act:sale', 2013);

	3.整个应用需要验证的时候,比如后台

	    session_start()
	    if(! empty($_SESSION['admin']))
	    {
			Cross::loadApp( 'web' )->run();
	    }
	    else
	    {
			Cross::loadApp( 'web' )->get("admin:login");
	    }

	4.计划任务的时候解析args argv
        参考 htdocs 下 cron.php
    
自定义路由 $r = new r(); $r要实现router接口
	Cross::loadApp( 'web' )->rrun($r);
    
*/





