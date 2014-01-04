# 轻量级的php5 MVC开发框架 #

###一、程序结构

CrossPHP是一个轻量级的基于PHP5.2+的MVC开发框架,单入口,灵活易扩展,易分工,适合多人协作的项目开发(比如前台后台和API同时进行),一个CrossPHP项目的结构如下:

	app
	    web 前台
	    admin 管理后台
	    api 接口
	    mobile 移动设备
		spider 针对蜘蛛搜索引擎的优化
	    ...
	htdocs
		web
			static
				css
				js
				images
			index.php
		admin
			static
			index.php
		...
	modules
		...
    config
		...
	lib
		...
	crossboot.php

每个app下都包含以下结构, 如:app下的web目录包含以下结构

    controllers 控制器
    templates 这个app的模板
		web
		admin
		open
		...

    views 控制器的视图（跟控制器一一对应）
	init.php 这个app的配置文件

1. controllers主要负责处理请求，调用Module获取需要的数据，交给view来展示。

2. modules跟app属于平级关系，多个app共用一个Module，在各个app中保持了数据的一致性。一个module可以包含多个model比如mysql,redis,mongodb等, 共同为controller提供数据 (在大型项目中推荐中使用PHP 5.3的命名空间,使结构更清晰,使用时只需在控制器中使用use就可以了)

3. view提供了layer功能,可以通过配置实现自动切换布局（如：web和移动设备之间的视图切换）。如果你习惯使用Smarty等第三方模板系统，也可以在View层中轻松扩展.

4. lib为第三方库文件目录,比如想使用smarty可以放在这里,使用的时候只需要
	>Loader::import("::/lib/smarty.xxx.")

5. config 为一些配置文件,比如db.config.php 存放的是数据库配置文件信息

6. htdocs为对外提供的访问目录,比如将adinm.youdomain.com指向htdocs下的admin文件夹就可以实现二级域名了,为了安全建议只将htdocs放到可访问目录 

###二、创建框架引导文件

在项目根目录下新建crossboot.php文件
在文件中定义项目路径(* PROJECT_PATH必须定义), 然后载入框架的boot.php文件:

	> define('PROJECT_PATH', realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR);
	> require PROJECT_PATH.'../crossphp/boot.php';

如果你有一些常用函数和一些常量也可以在这个文件里设置或载入

###三、运行框架

	1.自动运行
		根据init.php中的配置来自动解析url运行 支持pathinfo和request_string两种方式
		
		require '../../crossboot.php';
		Cross::loadApp( 'web' )->run();
	
	2.调用指定控制器
		
		a. 单页调用,比如活动页面

			require '../../crossboot.php';		
			Cross::loadApp( 'web' )->get("act:index");
		
		b. 自定义url解析规则
			
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
	  
	3.设置允许调用的控制器

		require '../../crossboot.php';
		$admin = Cross::loadApp( 'admin' );

		$admin->map("/", "admin:login");
		$admin->map("/2", "admin:haha");
		$admin->map("/news/:d+/", "news/$1");
		
		$admin->mrun();

	4.组合方式

		需要验证的app,比如后台管理
		如果$_SESSION['admin']为空,只允许访问登陆页面
		
		session_start()
		require '../../crossboot.php';

		if( !empty($_SESSION['admin']) ) {
		   Cross::loadApp( 'admin' )->run();
		} else {
		   Cross::loadApp( 'admin' )->get("Admin:login");
		}		
    
更多使用方法请查看demo中的例子和源代码注释.