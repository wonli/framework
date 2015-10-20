# CrossPHP Framework #
----------

MMVC，Layer，PSR，composer，注释配置，智能路由别名

程序需求
---------
PHP5.3以上版本。

功能简介
---------
支持PSR标准，composer包管理工具。MMVC，支持注释配置。
Layer布局支持，更换模板更简单。路由别名（先写代码，后定义，比如rul：/Controoooooller/Actiooooon/5，可以通过别名指定为：/d/5）。
全局的异常处理系统及错误展示，在开发中可以快速定位到具体的代码行数。默认使用PDO, 更安全, 更简单易用的Mysql查询。

使用场景
---------
可以用于快速的网站, API, REST, 及命令行工具开发。

获取框架
---------
从[http://www.crossphp.com/download](http://www.crossphp.com/download "crossphp.com")下载框架完整版并解压到本地目录。
使用`admin`前需要修改数据库配置`config/db.config.php`，并导入`sql/admin/back.sql`文件到指定的数据库。

>你也可以单独下载[skeleton](http://git.oschina.net/ideaa/skeleton "crossphp skeleton")， 从命令行进入到skeleton的根目录， 使用`composer install`来安装框架

文档地址
---------
[http://document.crossphp.com](http://document.crossphp.com "crossphp document"), 欢迎加入我们的QQ群:120801063

