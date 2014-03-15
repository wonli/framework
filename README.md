#CrossPHP 轻量级PHP开发框架 #

###一、程序结构

CrossPHP是一个轻量级的基于PHP5.2+的MVC开发框架,单入口,易扩展,分工,适合多人协作,一个CrossPHP项目的结构如下:

	app
	    web 
	    admin 
	    api 
	    ...
	htdocs
		web
			static
				css
				js
				images
			index.php
		admin
			...
		api
			...
	modules
	cache
    config
	lib
	...
	crossboot.php

###二、运行方式

	1.自动运行
		根据init.php中的配置自动解析url 支持pathinfo和request_string两种方式自由切换
		
		require '../../crossboot.php';
		Cross::loadApp( 'web' )->run();
	
	2.调用指定控制器

		require '../../crossboot.php';		
		Cross::loadApp( 'web' )->get("act:index");
	  
	3.组合方式

		需要验证的app,比如后台管理
		如果$_SESSION['admin']为空,只允许访问登陆页面
		
		session_start()
		require '../../crossboot.php';

		if (!empty($_SESSION['admin'])) {
		   Cross::loadApp( 'admin' )->run();
		} else {
		   Cross::loadApp( 'admin' )->get("Admin:login");
		}
    
更多使用方法请查看demo中的例子和源代码注释.
[http://git.oschina.net/ideaa/blog](http://git.oschina.net/ideaa/blog "http://git.oschina.net/ideaa/blog")