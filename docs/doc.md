# CrossPHP 框架功能及特点


# 一. 项目基本结构

在一个 `Cross` 项目中, 一个项目被拆分为多个app, 放在app目录下, 每个app都包含独立的控制器,视图及模板, 分别用于控制项目的具体部分, 一个常见的项目结构如下:

	├─app 应用模块目录    
	│  ├─admin 后台模块  
	│  │  ├─templates 模板文件夹
	│  │  │  └─web   
	│  │  ├─controller 控制器目录  
	│  │  └─view 视图文件目录 
	│  └─home 前台模块  
	│     ├─init.php 模块配置文件
	│     ├─templates 模板文件夹
	│     │  └─web  
	│     ├─controller 控制器目录  
	│     └─view 视图文件目录    
	├─lib 第三方类库 
	├─cache 缓存文件目录   
	├─config 配置文件目录  
	├─modules modules文件夹
	│   ├─admin
	│   └─common
	└─htdocs 可访问目录  
	  ├─admin  
	  └─home

每个app的根目录下面,都有一个名为 `init.php` 的PHP文件, 该配置文件返回一个PHP数组,这个数组默认分为三个部分

	├─app 应用模块目录    
	│  └─web 网站模块  
	│     ├─init.php app配置文件
	│     ├─templates
	│     │  └─web
	│     │      ├─home
	│     │      ├─...
	│     │      └─default.layer.php 
	│     ├─controller
	│     └─view 


### sys
app默认配置,用于指定默认模板目录等

1. `auth` 默认的认证方式, 使用SESSION或COOKIE.
2. `default_tpl_dir` 默认模板文件夹路径,可以在控制器中通过 `$this->config->set('sys', array('default_tpl_dir'=>'name'))` 来指定controller项目使用的模板.
3. `display` 默认的视图处理方法, 默认 `HTML` 使用视图控制器对应的方法来处理, `JSON` / `XML` 直接使用视图控制器中的JSON / XML方法来处理数据.

### url
为每个app指定独立的url风格

1. `*` 指定默认的控制器和方法.

2. `type` 指定解析url的方式, 其中 `1`和`3` 处理`QUERY_STRING`格式的url, `2`和`4`处理`PATH_INFO`的方式生成的url, 假设生成连接的方法为 `$this->link("main:index", array('p1'=>1, 'p2'=>2, 'p3'=>3))` 那么根据type的值会生成以下四种风格的url:	
		
		1  skeleton/htdocs/web/?/main/index/1/2/3
		3  skeleton/htdocs/web/?/main/index/p1/1/p2/2/p3/3

		2  skeleton/htdocs/web/index.php/main/index?p1=1&p2=2&p3=3		
		4  skeleton/htdocs/web/index.php/main/index/p1/1/p2/2/p3/3

	由此可见,当 `type = 1` 的时候生成的url最短. 在控制器中使用 `$this->params` 可以获得通过url传递的参数, 当 `type = 1` 时, 需要在控制器方法的头部使用 `@cp_params p1, p2, p3` 来指定指定参数的key名称.

3. `rewrite` 用来控制生成url的时候是否隐藏url中的 `? `号或索引文件名, 当`type`的值为1或3的时候, apache对应的 `.htaccess` 文件内容如下:
		
		<IfModule mod_rewrite.c>
		RewriteEngine On
		RewriteCond %{REQUEST_FILENAME} -s [OR]
		RewriteCond %{REQUEST_FILENAME} -l [OR]
		RewriteCond %{REQUEST_FILENAME} -d
		RewriteRule ^.*$ - [NC,L]
		
		RewriteCond %{REQUEST_URI}::$1 ^(/.+)(.+)::\2$
		RewriteRule ^(.*) - [E=BASE:%1]
		RewriteRule ^(.*)$ %{ENV:BASE}index.php?$1 [NC,L]
		</IfModule>
	
	nginx 配置的rewrite配置如下:
		    
		location / {
	        if (!-f $request_filename) {
	            rewrite ^/(.*)$ /index.php?$1 last;
	        }
	    }
	当`type`的值为2或4的时候, 把上面两条规则中的 `index.php?` 替换为 `index.php/` 即可.

4. `dot` 用来指定生成url路径中的分隔符例如: `skeleton/htdocs/web/?/main/index/1/2/3`. 这里的dot的值为 `/`, 如果把dot的值改为 `-`, 那么重新生成后的url为: `skeleton/htdocs/web/?/main-index-1-2-3` dot可以指定为浏览器能识别的任何字符.

5. `ext` 用来指定生成url后缀, 假设指定 `ext` 的值为`.html` url `skeleton/htdocs/web/?/main/index/1/2/3` 重新生成的url为 `skeleton/htdocs/web/?/main/index/1/2/3.html`, 此时会强制检查url后最是否以 `.html` 结束, 否则会抛出一个找不到该页面的异常.

6. `index` 索引文件名称即htdocs目录对应app文件夹中的默认文件, 默认是 `index.php` 如果要使用其他的索引文件请修改此处. 

### router 
router用于指定控制器的别名, 以以下配置为例:

	'router'    => array(
        'hi'    =>  'main:index',

        'article'   =>  array(
            'page'  =>  'index',
        ),
    ),

当请求的url是 `skeleton/htdocs/web/?/hi` 的时, 实际响应的是 `main` 控制器中的 `index` 方法
 
当请求的url是 `skeleton/htdocs/web/?/article/page` 的时, 实际响应的控制是 `article` 的 `index` 方法  

在Cross中，路由是双向解析的，生成连接后，如果给连接指定了别名，连接会自动刷新为指定的别名。

# 二. 启动框架

Cross项目 框架和项目 独立， 所以启动框架前需要做两件事

1. 告诉框架项目路径

		defined('PROJECT_PATH') or define('PROJECT_PATH', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
	在引用框架启动文件前需要先告诉框架项目路径， 所以需要先定义一个`PROJECT_PATH`常量，指向项目目录。

2. 告诉项目框架路径

		require PROJECT_PATH . '../crossphp/boot.php';

	载入框架引导文件，该文件位于框架的根目录，名为 `boot.php`, 框架可以放在计算机的任意路径，只要正确的包含进该文件即可。

### 指定要启动的APP

1. 解析url运行

		Cross::loadApp('web')->run();
	
	此时框架会先载入`app\web\init.php`文件中的配置，根据配置来解析url， 并调用相应的控制器来处理访问请求


2. 调用指定的控制器和方法, 以调用 `main` 控制器中的 `index` 方法为例：

		Cross::loadApp('web')->get("main:index");

	如果有附加参数，通过`get`方法的第二个参数传递给控制器
	
		Cross::loadApp('web')->get("main:index", $args);

# 三. 控制器

在app\web\controllers目录下创建一个User.php文件,文件内容如下:

	namespace app\web\controllers;
	
	use Cross\MVC\Controller;
	/**
	 * @Auth: wonli <wonli@live.com>
	 * Class Main
	 * @package app\web\controllers
	 *
	 * @cp_cache array(true, array('type'=>1))
	 */
	class User extends Controller
	{
		function index()
		{
			echo 'hello';
		}
	}

通过浏览器 `http://domain/skeleton/htdocs/web/user` 来访问,这时页面会输出 `hello`。  
控制器的注释生效（对所有方法的请求都会优先使用缓存）。

### 父类提供的方法

1. 判断请求类型

		$this->is_post() 
		$this->is_get()
		$this->is_cli() 
		$this->is_ajax_request()

	以上方法用于判断当前请求的类型，满足条件返回TRUE


2. 跳转到其他控制器

		$this->to([controller:action, params, sec])

	跳转到指定页面,该方法实际是一个 `$this->view->link()` 的连接, 生成url后用header函数跳转.

3. 调用视图

		$this->display([data, method, http_response_status])
	调用视图控制，一个`$this->view->display()`的连接。

>建议: 每个app对应该有一个基类控制器,基类控制器继承至`Cross\MVC\Controller`, 其余控制器从基类继承, 这样更灵活.

### 读取app配置
	
	namespace app\web\controllers;
	
	use Cross\MVC\Controller;
	
	class User extends Controller
	{
		function index()
		{
			$this->config->get("key"[, val])
		}
	}

在控制器中可以通过`$this->config->get()`方法来读取`app\web\init.php`中的配置项的值，`key`为配置数组中的键， 如果不指定`val`则返回该键对应的所有配置。

### 设置或更改配置项

	namespace app\web\controllers;
	
	use Cross\MVC\Controller;
	
	class User extends Controller
	{
		function index()
		{
			$this->config->set("set", array('key', 'val'))
		}
	}	
	
如果app配置文件中有对应的键、值存在，那么修改为指定的值， 没有则添加一项

### 获取URL参数

假设当前的url为 `http://domain/skeleton/htdocs/web/controller/action[/p1/1/p2/2]`, 在方法内部使用控制器的 `$this->params` 属性可以获得参数的值:

	namespace app\web\controllers;
	
	use Cross\MVC\Controller;
	
	class User extends Controller
	{
		function index()
		{
			print_r($this->params);
		}
	}

打印结果为一个关联索引数组 此时 `skeleton/app/web/init.php` 中的值为`url['type'] = 3`

	Array ( [p1] => 1 [p2] => 2 )

> 要还原参数的key,请参见使用注释配置一节

### 使用注释配置

###### 1. 还原参数的key

在app配置文件url字段字段部分，当type=1时, 在方法体内部使用 `$this->params` 属性默认获得的参数是一个数字索引数组,这时可以在方法体的注释中使用 `@cp_params` 指定了参数的key,格式为 `@cp_params 参数1, 参数2...` 如下例:

 	namespace app\web\controllers;

	use Cross\MVC\Controller;
	
	class User extends Controller
	{
	    /**
	     * 默认控制器
	     * @cp_params p1, p2, p3
	     */
		function index()
		{
			print_r($this->params);
		}
	}
	
此时打印的结果为：
	
	Array ( [p1] => 1 [p2] => 2 [p3] => 3 ) 
	
使用其他模式时打印结果均为

	Array ( [p1] => 1 [p2] => 2 [p3] => 3 ) 

>`@cp_params` 只有`type=1`时生效， 为保持程序的一致性使用`@cp_params`时，指定的参数应与生成的连接的参数保存一致 

###### 2. 为Action配置缓存

在注释中使用 `@cp_cache` 字段指定该Action的缓存,直接使用php数组进行配置

 	namespace app\web\controllers;

	use Cross\MVC\Controller;
	
	class User extends Controller
	{
	    /**
	     * 默认控制器
	     * 
	     * @cp_cache array(true, array('type'=>1, 'expire_time'=>864000))
	     */
		function index()
		{
			
		}
	}

配置的格式为一个二维数组 `array(true/false, array(key=>val...))`

第一个参数`true` 或 `false` 分别代表配置的开关.

第二个参数中公用的有
	
	expire_time 表示缓存过期时间
	key 缓存文件key的生成规则,支持匿名函数, 该匿名函数接收两个参数,第一个是app_name,controller,action, 第二个是params.

a. 使用文件缓存 `array(true, array('type'=>1, 'expire_time'=>864000))`

	type = 1
	cache_path 表示缓存文件放在web索引文件的跟目录,默认放在项目的cache/request文件夹下
	file_ext 缓存文件扩展名 默认为sys display中配置的处理方法名
	
>项目根目录下的cache目录需设置为可以读写

b. 使用memcache缓存 `array(true, array('type'=>2, 'host'=>'127.0.0.1', 'port'=>11211, 'expire_time'=>600))`
				
	type=2
	host=127.0.0.1
	port=11211

c. 使用redis缓存 `array(true, array('type'=>1, 'host'=>'127.0.0.1', 'db'=>3, 'port'=>6379, expire_time=86400))`

	type=3, 
	host=127.0.0.1, 
	port=6379, 
	db=3 使用的db id


使用缓存后Dispatcher流程会跳过app模型中该Action的逻辑,直接从缓存读取数据返回给客户端, 合理的使用缓存能大幅度提高应用性能.

>使用op_cache的时候注意与注释相关参数的设置

###### 3. 配置前后置

使用 `cp_before` 配置运行前要执行的方法，使用 `cp_after` 配置运行结束后要执行的方法。参数是一个可访问的类方法。

###### 4. 配置response

使用 `cp_response` 配置response，主要用于修正response时，返回的content-type和status。

##### 5. 配置Basic Auth

使用 `cp_basicAuth` 在运行前先发送一个Basic Auth请求到客户端，使用`array('user'=>'用户名', 'pw'=>'密码')`来指定用户名和密码。

##### 6. 自定义配置参数
自定义配置参数的格式为`cp_配置名`， 参数可以为任意可访问的类/或文件，自定义好以后在自己的类中处理。

### 在控制器中使用modules

在控制器中使用modules,以使用UserModules为例:

	namespace app\web\controllers;
	
	use Cross\MVC\Controller;
	
	class User extends Controller
	{
		function index()
		{
			$USER = new UserModules();
		}
	}

如果类中每个action都依赖`UserModules`, 可以把初始化UserModules的工作放到构造函数中:

	namespace app\web\controllers;
	
	use Cross\MVC\Controller;
	
	class User extends Controller
	{
	    /**
	     * @var UserModule
	     */
	    protected $USER;
	
	    function __construct()
	    {
	        parent::__construct();
	        $this->USER = new UserModule();
	    }

		function index()
		{
		
		}
	}

然后就可以在控制器中调用modules提供的方法了. 

### 在控制器中使用view

###### 1. 基本使用方式
	namespace app\web\controllers;
	
	use Cross\MVC\Controller;
	
	class User extends Controller
	{
		function index()
		{
	        $page = array(
	            'p' =>  isset($this->params['p'])?intval($this->params['p']):1,
	            'limit' => 20,
	            'half' => 3,
	            'link' => array("main:index"),
	        );
	
			$USER = new UserModule();
	        $result['data'] = $USER->getUserList( $page );
	        $result['page'] = $page;
	
	        $this->display($result);
		}
	}

从Modules取出数据和分页信息, 放进变量 `$result` 中, 传给视图控制器的同名方法处理, 在视图控制器中整理数据, 调用模版, 赋值给布局文件中的 `$content` 变量, 生成最终的结果页面一起返回到 `Dispatcher` 中,如果有请求缓存则把结果存进缓存中, 然后通过 `Response->output()` 方法发送给用户, 下次请求的时候先检查是否有缓存, 如果有缓存并且还在缓存的有效期, 直接从缓存把结果返回给用户.

###### 2. 使用视图控制器中指定的Action

	namespace app\web\controllers;
	
	use Cross\MVC\Controller;
	
	class User extends Controller
	{
		function index()
		{
	        $page = array(
	            'p' =>  isset($this->params['p'])?intval($this->params['p']):1,
	            'limit' => 20,
	            'half' => 3,
	            'link' => array("main:index"),
	        );
	
			$USER = new UserModule();
	        $result['data'] = $USER->getUserList( $page );
	        $result['page'] = $page;
	
	        $this->display($result, 'JSON');
		}
	}

使用视图控制器提供的 `JSON` 方法,返回一个JSON结果.


###### 3. 不使用layer布局.

	class Main extends CoreController
	{
		function index()
		{
	        $page = array(
	            'p' =>  isset($this->params['p'])?intval($this->params['p']):1,
	            'limit' => 20,
	            'half' => 3,
	            'link' => array("main:index"),
	        );
	
			$USER = new UserModule();
	        $result['data'] = $USER->getUserList( $page );
	        $result['page'] = $page;
	
			if ($this->is_ajax_request())
			{
				$this->view->index($result);
			} else {
				$this->display($result);
			}
		}
	}

如果是ajax请求不包含layer中的内容.

# 四. Module系统

	├─modules modules文件夹
	│   ├─admin
	│   ├─space
	│   ├─user
	│   ├─article
	│   └─common

模型系统是所有app功能模块共同的数据来源,一个modules支持多个数据来源.目前默认支持以下的数据库系统.

- PDO驱动的MySQL，SQLite，PgSQL
- Redis,
- Memcache,
- CouchBase,
- MongoDB

除了这些默认支持的数据库外， 在数据库配置文件中，还可以将参数配置为一个匿名函数，来自定义你自己的数据处理类。

> 不支持不同类型数据库之间切换

### 连接默认数据库

在项目的根目录下的modules文件夹中创建web文件夹, 在web文件夹下创建 `ApiModule.php` 内容如下:
	
	namespace modules\web;
		
	use Cross\MVC\Module;

	class ApiModule extends Module
	{
	    function __construct()
		{
        	parent::__construct();
		}
	}

### 连接指定数据库


`parent::__construct()` 中的参数为要连接的数据库的配置的名称,默认连接的数据库为`mysql:db`,参数格式为:
 
	数据库类型:配置名称  
通过config目录下的db.config.php 指定, 比如要连接log数据库 

	namespace modules\web;
		
	use Cross\MVC\Module;

	class ApiModule extends Module
	{
	    function __construct()
		{
        	parent::__construct("mysql:log");
		}
	}

连接数据库后,在modules类中用 `$this->link` 来调用数据库驱动提供的接口.
  
>modules默认不建立与任何数据库系统的连接,只需覆盖默认的构造函数 或不从CoreModule继承.

### MySQL查询

假设 `modules\web\UserModule.php` 中的代码如下：

	namespace modules\web;
		
	use Cross\MVC\Module;

	class UserModule extends Module
	{
		protected $t_user = 'front_user';

		function getUser() 
		{

		}
	}

连接数据库后, 在module的方法中就可以使用 `$this->link` 属性使用连接类提供的方法查询了,以MySql类默认提供的查询为例.

###### 查询单条记录

	function getUser() 
	{
		return $this->link->get($this->t_user, '*', 'score=1')
	}
	
也可以用数组表示
	
	function getUser() 
	{
		return $this->link->get($this->t_user, '*', array(
			'score' => 1
		));
	}

###### 查询多条记录

	function getUser() 
	{
		return $this->link->getAll($this->t_user, '*');
	}

###### 条件查询

	function getUser() 
	{
		return $this->link->getAll($this->t_user, '*', array(
			'score' => array('>', 1)
		));
	｝

查询score 大于 1的用户 类似的操作还可以使用`>`, `<>`等。

	function getUser() 
	{
		return $this->link->getAll($this->t_user, '*', array(
			'name' => array('like', 'john')
		));
	｝
使用like查询

###### 使用IN、OR查询

	function getUser()
	{
		return $this->link->getAll($this->t_user, '*', array(
			'id' => array('in', array(1,2,3))
		));
	}

对应的sql语句为 `SELECT * FROM back_acl_menu WHERE id IN (?,?,?)`

	function getUser()
	{
		return $this->link->getAll($this->t_user, '*', array(
				'id' => array('or', array(1, 2, 3))
			));
	}
对应的sql语句为： `SELECT * FROM front_user WHERE id = ? OR id = ? OR id = ?`

###### 为OR中的某一项指定条件

	function getUser()
	{
		return $this->link->getAll($this->t_user, '*', array(
				'id' => array('or', array(1, 2, 3, array('>', 5) ))
			));
	}
此时生成的sql语句为 `SELECT * FROM front_user WHERE id = ? OR id = ? OR id = ? OR id > ?`


###### 使用BETWEEN

	function getUser()
	{
		return $this->link->getAll($this->t_user, '*', array(
			'id'    =>  array('between', array(1, 2))
		));
	}
生成的SQL语句模板为 `SELECT * FROM front_user WHERE id BETWEEN 1 AND 2`

###### 带分页数据的查询

	function getUser(& $page = array('p'=>1, 'limit'=>30))
	{
		$this->link->find($this->t_user, '*', array(
			'score'	=> array('>', 1),
		), 'id DESC', $page);
	｝

###### 混合使用

	function getUser()
	{
		return $this->link->getAll($this->t_user, '*', array(
            'id'    =>  array('in', array(7, 2, 3)),
            'pid'   =>  array('or', array(1, 2 => array('>', 2)))
		));
	}
生成的sql语句为 `SELECT * FROM front_user WHERE id IN (?,?,?) AND pid = ? OR pid > ?`

###### 左右连接

	function getUser()
	{
		return $this->link->getAll("{$this->t_user} tu LEFT JOIN front_user_base fub ON tu.id=fub.id", '*', array(
            'id'    =>  1
		));
	}

### 添加数据

添加单条记录
		
	function getUser()
	{
		$this->link->add($this->t_user, array(
			'score'	=> 5,
			'group'	=>	1,
		));
	｝

>如果主键是id返回id, 否则总是返回 `true`

批量添加 

	function getUser()
	{		
		$insert_data = array();
	
		$this->link->add($this->t_user, array(
			'fields' = array('score', 'group'),
			'values' = array(
				array(5, 1),	
				array(5, 2),
				array(5, 3),
			)
		), true, $insert_data);
	｝

>`insert_data` 返回的值为添加以后结果.

### 删除记录

删除单条记录

	function getUser()
	{
		$this->link->del($this->t_user, array(
			'id'=>1
		));
	｝

批量删除id等于1和id等于2的用户

	function getUser()
	{
		$this->link->del($this->t_user, array(
	        'fields' => array('id'),
	        'values' => array(
	            array(1),
	            array(2),
	        ),
		), true);
	｝

### 直接执行sql语句
返回单条记录

	function getUser($sql)
	{
    	$this->link->fetchOne($sql, $model = PDO::FETCH_ASSOC);
	｝

返回多条记录

	function getUser($sql)
	{
		$this->link->fetchAll($sql, $model = PDO::FETCH_ASSOC);
	｝

> 适用于不带参数的sql查询

### 手动绑定参数

>$sql =  "select fri.*, fue.* from ( select * from front_user where uid = ?) fri  
>left join front_user_extend fue on fri.id=fue.uid"

	$this->link->prepare($sql)->exec(array(1))->stmt_fetch(true);

# 五.视图概述

	├─crossboot.php  
    │
	├─app     
	│  ├─admin   
	│  └─home   
	│     ├─init.php 
	│     ├─templates 
	│     │  └─web
	│     │      ├─home
	│     │      ├─main
	│     │      ├─user
	│     │      ├─...
	│     │      └─default.layer.php     
	│     ├─controller   
	│     └─view 视图控制器文件目录  

CrossPHP框架中的视图由两个部分组成,即视图控制器和模板系统. 

1. app下的view目录即视图控制器目录,每一个控制器的视图都由一个视图控制器单独处理.视图控制器中的action负责格式化数据,包含模板等操作.

2. 模板系统包含模板和布局系统,模板系统中输出的内容先包含进布局中,再输出到浏览器

### 模板结构

CrossPHP默认的模板语言为PHP本身,一个app模板结构如下:

	├─templates 模板文件夹
	│  ├─default
	│  │   ├─acl
	│  │   │   ├─list.tpl.php
	│  │   │   ├─index.tpl.php  
	│  │   │   └─add.tpl.php
	│  │   │    
	│  │   ├─main
	│  │   ├─security
	│  │   ├─...
	│  │   └─default.layer.php 默认布局文件
	│  └─web
	│      ├─acl
	│      ├─main
	│      ├─security
	│      ├─...
	│      └─default.layer.php 默认布局文件


1. 模板命名方式为目录和控制器中的类一一对应, 模板文件和控制器中的方法对应,即模板名称/控制器名称/方法名称.
2. 布局文件放在模板文件的根目录下.

### 视图控制器

视图控制器文件夹位于 `app\web\views` 目录下， 访问控制器中的类的时候，会自动去视图控制器目录下查找视图控制器文件比如`Main`控制器默认的视图控制器类名为`MainView`，文件内容如下:

	namespace app\web\views;
	
	use Cross\MVC\View;
	
	class MainView extends View
	{
	    function index($data = array())
	    {	
	        include $this->tpl("main/index");
	    }
	}

一般视图控制器只是用来载入对应的模板

> ajax返回的时候,不用返回公共的layer文件内容

### 使用布局

###### 1. 基本用法

在控制器中使用`$this->display()`方法,必须包含默认的布局文件default.layer.php, 一个空白的布局文件内容如下:

	<?php echo isset($content)?$content:'' ?>

`$content`的内容即控制器中调用`$this->view->main()`输出的内容.一个HTML布局文件内容如下:
	
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="zh-CN">
	<head>
	    <title><?php echo isset($title)?$title:'' ?></title>
	    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
	    <meta name="Keywords" content="<?php echo isset($keywords)?$keywords:''; ?>" />
	    <meta name="Description" content="<?php echo isset($description)?$description:''; ?>" />
	    <link rel="stylesheet" rev="stylesheet" href="<?php echo $this->res("css/style.css") ?>" media="all" />
	</head>
	<body>
	    <?php echo isset($content)?$content:'' ?>
	</body>
	</html>


请求`index`的时候会自动载入视图控制器目录下面的`MainView.php`文件,文件内容如下:

	namespace app\web\views;
	
	use Cross\MVC\View;
	
	class MainView extends View
	{
	    function index($data = array())
	    {	
	        include $this->tpl("main/index");
	    }
	}
index方法输出的内容至布局文件中的变量`$content`,然后合并输出到浏览器.


###### 2. 在视图控制器中控制布局的内容

a. 更改网站标题,keywords,和description

		namespace app\web\views;
		
		use Cross\MVC\View;
		
		class MainView extends View
		{
		    function index($data = array())
		    {	
		        $this->set(array(
		            'title' =>  'hi',
		            'keywords'   =>  'crossphp',
		            'description'   =>  '轻量高效php开发框架',
		        ));
	
		        include $this->tpl("main/index");
		    }
		}

	布局文件中的所有变量均可以在action中,调用基类中的`$this->set()`替换.参数为一个数组,数组的key即为变量名.


b. 添加静态资源文件,如css,js等,需在layer中指定位置添加`$this->loadRes()`方法
	
		namespace app\web\views;
		
		use Cross\MVC\View;
		
		class MainView extends View
		{
		    function index($data = array())
		    {	
				$this->addRes("css/style.css");	
		        include $this->tpl("main/index");
		    }
		}
	
>自定义app中的视图控制器基类的基类而不是直接继承`CoreView`,这样更灵活易扩展.

### 模板的使用

根据使用的模板引擎选择支持的语法,这里以CrossPHP框架的原生视图为例.

###### 1. 使用默认视图控制器中的方法.

a. 生成连接

	$this->link("controller:action", array('key'=>'value'));	
> 生成后的url中的连接由模块配置文件init.php中的url中type和dot控制  

b. 生成加密连接
	
	$this->slink("controller:action", array('key'=>'value'));

唯一不同的是`array('key'=>'value')` 部分是加密的	
>在控制器中调用 `$this->sparams()` 来还原加密前的参数
	
c. 包含其他模板文件

	$this->tpl("page/p1");
>引入page目录下的p1.tpl.php文件
	

##### 2. 在视图控制器中扩展

    private function threadList( $data = array() )
    {
        foreach($data as $d)
        {
            ?>
			<li>
            	<a href="<?php echo $this->link("read", array('id'=>1)) ?>">标题</a>
			</li>
            <?php
        }
    }

在模板文件中用 `$this->threadList($data)` 调用.
>重复使用的,公共的的tpl可以放在app视图控制器自定义的基类中,保持模板代码的整洁

#六. 使用第三方Model

#####1. 在module中扩展
以使用[http://medoo.in/](http://medoo.in/ "medoo")为例，先下载medoo.min.php文件到lib目录， 使用Loader类中的import方法载入该类， 在控制器中重新指定`TestModule`的link属性为Medoo类的实例，然后就可以在action中调用`$this->link`来使用medoo提供的接口了。

	namespace modules\web;
	
	use Cross\Core\Loader;
	use Cross\MVC\Module;
	
	Loader::import("::lib/medoo.min.php");
	
	class TestModule extends Module
	{
	    function __construct()
	    {
	        parent::__construct();
	        $config = $this->linkConfig()->get('mysql', 'db');
	
	        $this->link = new \Medoo(array(
	            'database_type' => 'mysql',
	            'database_name' => $config['name'],
	            'server' => $config['host'],
	            'username' => $config['user'],
	            'password' => $config['pass'],
	        ));
	    }
	
	    function getUser()
	    {
	        return $this->link->get('back_acl_menu', '*', array(
	                'id'    => 1,
	            ));
	    }
	}

#####2. 通过配置文件扩展

	Loader::import("::lib/medoo.min.php");

	.
	.
	.

	return array(
	    'mysql' => array(
	        'db' => $db,
	    ),	
	
	    'medoo' => array(
	        'db' =>  function() use ($mysql_link) {
	            return new \Medoo(array(
	                'database_type' => 'mysql',
	                'database_name' => $mysql_link['name'],
	                'server' => $mysql_link['host'],
	                'username' => $mysql_link['user'],
	                'password' => $mysql_link['pass'],
	            ));
	        }
	    ),
	
	    'default'   =>  array('mysql' => 'db')
	);

然后在Module中这样使用

	namespace modules\web;
	
	use Cross\Core\Loader;
	use Cross\MVC\Module;		
	
	class TestModule extends Module
	{
	    function __construct()
	    {
	        parent::__construct('medoo:db');
	    }
	
	    function getUser()
	    {
			//通过$this->link访问mdeoo提供的api来使用数据库
	        return $this->link;
	    }
	}

使用其他第三方model类似

#七.扩展模板系统

### 使用第三方PHP模版系统

以添加Smarty为例,从[http://www.smarty.net/download](http://www.smarty.net/download "http://www.smarty.net/download")下载你熟悉的smarty版本到项目根目录的lib中,本例以Smarty-3.1.17为例,新建一个SmartyView控制器继在你的视图控制器目录,使之承至CoreView类

1. 扩展系统
		namespace app\web\views;
		
		use Cross\MVC\View;
		
		class SmartyView extends View
		{
		    function __construct()
		    {
		        parent::__construct();
		        Loader::import("::lib/Smarty-3.1.17/libs/Smarty.class.php");
		        $this->smarty = new Smarty;
		
		        $this->smarty->debugging = true;
		        $this->smarty->caching = true;
		        $this->smarty->cache_lifetime = 120;
		    }
		}

2. 使用
		namespace app\web\views;

		class MainView extends SmartyView
		{
		    function index($data = array())
		    {	
		        $this->smarty->assign("name", $data['name']);
		        $this->smarty->assign("FirstName",array("John","Mary","James","Henry"));
		        $this->smarty->assign("LastName",array("Doe","Smith","Johnson","Case"));	
		        $this->smarty->assign("contacts", array(
						array("phone" => "1", "fax" => "2", "cell" => "3"),
		                array("phone" => "555-4444", "fax" => "555-3333", "cell" => "760-1234")
					));

		        $this->smarty->display( $this->tpl("main/index") );
		    }
		}


### 使用JS模板引擎

以添加artTemplate模板引擎为例, 从[https://github.com/aui/artTemplate](https://github.com/aui/artTemplate "https://github.com/aui/artTemplate")下载最新版本,放在htdocs/static/lib目录下.

1. 修改模板根目录下的default.layer.php文件,在head部分中加入引擎的连接

		<script src="<?php echo $this->res("lib/artTemplate/dist/template-simple.js") ?>"></script>

	为content所在的父级div添加一个id属性
	
	    <div id="layer-content">
	        <?php echo isset($content)?$content:'暂无内容' ?>
	    </div>
	
	在`</body>`前添加如下JS代码
	
	    <script type="text/javascript">
	        var html = template.render('layer-content', <?php echo isset($jsonData)?$jsonData:'{}' ?>);
	        document.getElementById('layer-content').innerHTML = html;
	    </script>

2. 在视图视图控制器类中使用. 通过`$this->set()`把模板引擎需要的数据传送到layer.

		namespace app\web\views;
		
		use Cross\MVC\View;
		
		class MainView extends View
		{
		    function index($data = array())
		    {	
		        $this->set(array(
		           'jsonData' =>  json_encode($data)
		        ));
		
		        include $this->tpl("main/index");
		    }
		}

>以上两种方式都保留了CrossPHP视图的layer功能.

#八.与第三方程序交互

### Yar

	require '../../crossboot.php';
	
	$conf = array(
	    'server'    =>  array(
	        'id'    =>  2,
	        'name'  =>  'test',
	        'ver'   =>  '1.1',
	    ),
	);	
	
	$service = new Yar_Server( Cross::loadApp( 'api', $conf ) );
	$service->handle();
	

### ZMQ

	require '../../crossboot.php';
	
	$conf = array(
	    'server'    =>  array(
	        'id'    =>  2,
	        'name'  =>  'test',
	        'ver'   =>  '1.1',
	    ),
	);
	
	$context = new ZMQContext(1);
	
	//  Socket to talk to clients
	$responder = new ZMQSocket($context, ZMQ::SOCKET_REP);
	$responder->bind("tcp://127.0.0.1:5678");
	
	while (true) {
	    //  Wait for next request from client
	    $request = $responder->recv();
	
	    $request_array = array();
	    parse_str( $request, $request_array );
	
	    $controller = isset($request_array['mode']) ? $request_array['mode'] : '';
	    if( $controller )
	    {
	        if(false !== strpos($controller, '.')) {
	            $controller = str_replace('.', ':', $controller);
	        } else {
	            $controller = "{$controller}:index";
	        }
	        unset($request_array['mode']);
	    } else {
	        $controller = 'main:index';
	    }
	
	    ob_start();	
	    try {
	
	        Cross::loadApp( 'api', $conf )->get( $controller, $request_array );
	
	    } catch(Exception $e) {	
	        $e->getMessage();	
	    }
	
	    $req = ob_get_clean();
	    $responder->send( $req );
	}

更多使用方法请在使用中去发现,如有疑问请加QQ群:120801063