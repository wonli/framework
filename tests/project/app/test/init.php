<?php
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
        'auth' => 'COOKIE',
        /**
         * 默认的template路径
         */
        'default_tpl_dir' => 'default',
        /**
         * response输出方式
         * 支持 JSON HTML XML 默认是HTML
         */
        'display' => 'JSON'
    ),
    'url' => array(

        /**
         * 默认调用的控制器和方法
         */
        '*' => 'Main:index',
        /**
         * 解析url的方式
         * 1 QUERY_STRING
         * 2 PATH_INFO
         */
        'type' => 1,
        /**
         * 是否使用rewrite
         */
        'rewrite' => false,
        /**
         * url请求中的连接字符
         */
        'dot' => '/',
        /**
         * 请求扩展
         */
        'ext' => '',
        /**
         * 索引文件名称
         */
        'index' => 'index.php'
    ),
    /**
     * 控制器配置
     */
    "router" => array(

        /**
         * 请求http://youdomain/hi时的配置
         */
        'hi' => 'main:index',
    )
);


