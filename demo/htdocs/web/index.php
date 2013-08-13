<?php 
/**
* @Author:       wonli
*/

require '../../crossboot.php'; 
Cross::loadApp( 'web' )->run();

/*

调用指定controller
Cross::loadApp( 'web' )->get('main:index', $params);

	应该用场景

	1.path_info,request_string 不能达到要求的时候
	  
	  $controller = $_REQUEST['controller'];
	  $action = $_REQUEST['action'];
	  unset($_REQUEST['controller'], $_REQUEST['action']);
	  $params = $_REQUEST;
	  
	  $_c = "{$controller}:{$action}";
	  Cross::loadApp( 'web' )->get($_c, $params);
	  
	2.需要单个临时页面的时候,比如游戏活动,商品打折等

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
	4.crontab的时候解析args argv
	
自定义路由 $r = new r(); $r要实现router接口
	Cross::loadApp( 'web' )->rrun($r);	
*/





