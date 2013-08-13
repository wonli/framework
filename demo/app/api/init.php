<?php defined('DOCROOT')or die('Access Denied');
/**
* app配置文件
*/
$init = array();

$init['sys'] = array(
    'debug'=>true,
    'auth'=>'COOKIE',
    'default_tpl'=>'api',
    'display'=>'JSON' //输出格式
);

$init['url'] = array(
    '*'=>'Main:index',//默认路由
    'type'=>2,//1 query string 2 path info
    'rewrite'=>false,//是否启用rewrite true/false;
    'dot'=>'/',
    'ext'=>'',
    'index'=>'index.php'//end(explode( '/', $_SERVER["SCRIPT_NAME"]))
);

# 控制器配置
# 例如：$init["controller"] = array (
#  'home'=>array(
#     'alias'=>'',
#  ),
#)
# :alias 是否调用其他控制器 默认控制器alias项的配置无效 以home配置项为例:
#       如：alias = "user"; 访问http://host/home时
#       实际调用:user->index()
#
#       如：alias = "user:register"; 访问http://host/home时
#       实际调用:user->register()方法
#
#       如: alias = array("list"=>"index"); 访问 http://host/home/list
#       那么实际调用为home中的index方法
#
#       也可以在控制器中用静态属性_act_alias_来指定别名[优先级低于配置]

$init["controller"] = array(

);

return $init;


