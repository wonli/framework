# CrossPHP 基于MVC的轻量级php5开发框架 #

一个CrossPHP项目的结构如下

	app
	    web 主项目
	    admin 管理后台
	    open 开放接口
	    mobile 移动设备
		spider 爬虫
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
	crossboot.php

每个app下都包含以下结构, 如 app->web

    controllers 控制器
    templates 这个app的模板
		web
		admin
		open
		...

    views 控制器的视图（跟控制器一一对应）
	init.php app的配置文件

controllers主要负责处理请求，调用Module获取需要的数据，交给view来展示。
Modules跟app属于平级关系，多个app共用一个Module，这样就在各个app中保持了数据的一致性。

view提供了layer功能,可以通过配置实现自动切换布局（如：web和移动设备之间的视图切换）。
如果你习惯使用Smarty等第三方模板系统，也可以在View层中轻松扩展。

怎么运行CrossPHP呢？首先载入crossboot.php
> require '../../crossboot.php'; 

如果想根据init.php中的配置来自动解析url,可以这样:
> Cross::loadApp( 'web' )->run();

只想调用一个页面(比如打折活动),可以这样:
> Cross::loadApp( 'web' )->get("act:index");

载入并且运行就可以啦（具体使用方法请查看demo中的例子和源代码注释）.