<?php defined('DOCROOT')or die('Access Denied');
/**
 * app配置文件
 */

return array(

    /**
     * 系统设置
     */
    'sys' => array(

        /**
         * 登录认证保存方式
         * 支持 COOKIE 和 SESSION
         */
        'auth' => 'SESSION',

        /**
         * 默认的template路径
         */
        'default_tpl' => 'web',

        /**
         * response输出方式
         * 支持 JSON HTML XML 默认是HTML
         */
        'display' => 'AUTO'
    ),


    'url' => array(

        /**
         * 默认调用的控制器和方法
         */
        '*'	=> 'Admin:index',

        /**
         * 解析url的方式
         * 1 QUERY_STRING
         * 2 PATH_INFO
         */
        'type'=>2,

        /**
         * 是否使用rewrite
         */
        'rewrite'=>false,

        /**
         * url请求中的连接字符
         */
        'dot'=>'/',

        /**
         * 请求扩展
         */
        'ext'=>'',

        /**
         * 索引文件名称
         */
        'index'=>'index.php'
    ),

    /**
     * 控制器配置
     */
    "controller" => array(

        /**
         * 请求http://youdomain/hi时的配置
         */
        'hi'=>array(
            /**
             * 别名: 实际调用的控制器和方法 支持字符串和数组
             * 如: alias = array("list"=>"index") 时
             *
             * 请求:http://youdomain/hi/list
             * 实际响应的url是 http://youdomain/hi/index
             *
             * 也可以在控制器中用静态属性_act_alias_来指定别名[优先级低于配置]
             */
            'alias'=>'player',
        ),

        'panel' =>  array(
            'cache' =>  array(false, array('type'=>1, 'expire_time'=>300))
        ),
    )
);


