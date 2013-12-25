<?php
/**
 * @Author:       wonli wonli@live.com
 */
class FrameBase
{
    /**
     * @var 参数
     */
    protected $params;

    /**
     * @var action 名称
     */
    protected $action;

    /**
     * @var module
     */
    protected $module;

    /**
     * @var 用户配置
     */
    protected $config;

    /**
     * @var 控制器名称
     */
    protected $controller;

    /**
     * @var array
     */
    static protected $moduleInstance = array();

    public function __construct()
    {
        $this->app_init();
    }

    /**
     * 为初始化准备参数
     */
    function app_init()
    {
        if(! $this->config) {
            $this->setConfig( Dispatcher::getConfig() );
        }

        if(! $this->controller) {
            $this->setControllerName( Dispatcher::getController() );
        }

        if(! $this->action) {
            $this->setActionName( Dispatcher::getAction() );
        }

        if(! $this->params) {
            $this->setParams( Dispatcher::getParams() );
        }
    }

    /**
     * 设置控制器名称
     *
     * @param $controller_name
     */
    public function setControllerName($controller_name)
    {
        $this->controller = $controller_name;
    }

    /**
     * 取得控制器名称
     *
     * @return mixed
     */
    public function getControllerName() {
        return $this->controller;
    }

    /**
     * 设置Action
     *
     * @param $action_name
     */
    public function setActionName($action_name)
    {
        $this->action = $action_name;
    }

    /**
     * 取得action
     *
     * @return action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * 设置params
     *
     * @param $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * 取得url参数列表
     *
     * @param bool $strip 是否过滤html标签
     * @return action|mixed
     */
    public function getParams( $strip = false )
    {
        if(true === $strip)
        {
            return Helper::strip_selected_tags($this->params);
        }

        return $this->params;
    }

    /**
     * 设置全局配置
     *
     * @param $config
     */
    public function setConfig($config)
    {
         $this->config = $config;
    }

    /**
     * 返回配置
     *
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * 设置缓存配置
     *
     * @param $cache_config
     */
    public function setCacheConfig($cache_config)
    {
        $this->cache_config = $cache_config;
    }

    /**
     * 返回缓存配置
     *
     * @return mixed
     */
    public function getCacheConfig()
    {
       return $this->cache_config;
    }

    /**
     * 加密会话 sys=>auth中指定是cookie/session
     *
     * @param $key key
     * @param $value 值
     * @param int $exp 过期时间
     * @return bool
     */
    protected function setAuth($key, $value, $exp=86400)
    {
        $auth_type = $this->config->get("sys", "auth");
        return HttpAuth::factory( $auth_type )->set($key, $value, $exp);
    }

    /**
     * 解密会话
     *
     * @param $key
     * @param bool $de
     * @return bool|mixed|string
     */
    protected function getAuth($key, $de = false)
    {
        $auth_type = $this->config->get("sys", "auth");
        return HttpAuth::factory( $auth_type )->get($key, $de);
    }

    /**
     * 参数加密
     *
     * @param $tex
     * @param $key
     * @param string $type
     * @return bool|string
     */
    protected function encode_params($tex, $key, $type="encode")
    {
        return Helper::encode_params($tex, $key, $type);
    }

    /**
     * 参数解密
     *
     * @param null $params
     * @return bool|string
     */
    protected function sparams( $params=null )
    {
        if(! $params) {
            $params = $this->params;
        }
        return $this->encode_params($params, "crossphp", "decode");
    }

    /**
     * mcrypt加密
     *
     * @param $params
     * @return mixed
     */
    protected function mcryptEncode($params)
    {
        $mcrypt = new Mcrypt;
        $_params = $mcrypt->enCode($params);
        return $_params[1];
    }

    /**
     * mcrypt 解密
     *
     * @param $params
     * @return string
     */
    protected function mcryptDecode($params)
    {
        $mcrypt = new Mcrypt;
        $_params = $mcrypt->deCode($params);
        return $_params;
    }

    /**
     * 加载其他控制器
     *
     * @param $controller_name
     * @param $params
     * @param $run_action 是否只返回controller的实例
     * <pre>
     *  如果$run_action=true
     *      $controller_name 支持 控制器:方法 的形式调用
     * </pre>
     * @return array|mixed|string
     */
    protected function loadController($controller_name, $params=array(), $run_action=false)
    {
        return Dispatcher::init( APP_NAME, $this->config )->run( $controller_name, $params, $run_action );
    }

    /**
     * 加载指定Module
     *
     * @param $module_name
     * @param string $params
     * @return mixed
     */
    protected function loadModule( $module_name, $params = '' )
    {
        if(! isset(self::$moduleInstance[ $module_name ]))
        {
            $args = func_get_args();
            if( $params != '' && count($args) > 2 )
            {
                array_shift($args);
                $params = $args;
            }

            if( false !== strpos( $module_name, "/") )
            {
                Loader::import("::modules/{$module_name}Module");
                $module_name = end( explode("/", $module_name) );
            }

            $_module = ucfirst("{$module_name}Module");
            self::$moduleInstance[ $module_name ] = new $_module( $params );
        }

        return self::$moduleInstance[ $module_name ];
    }

    /**
     * 加载视图
     *
     * @param null $action
     * @param null $controller_name
     * @return mixed
     */
    protected function loadView($controller_name = null, $action = null)
    {
        if(null == $controller_name)
        {
            $controller_name = $this->controller;
        }

        $view_class_name = "{$controller_name}View";
        $view = new $view_class_name;

        if(null !== $action)
        {
            return $view->$action();
        }

        return $view;
    }

    /**
     * 运行app
     *
     * @param $act
     */
    function run( $act )
    {
        $mr = new ReflectionClass($this);

        $_before = "{$act}_before";
        $_after = "{$act}_after";

        if( $mr->hasMethod($_before) )
        {
            $this->$_before();
        }

        $this->$act( );

        if( $mr->hasMethod($_after) )
        {
            $this->$_after();
        }
    }

    /**
     * 输出结果
     *
     * @param string $ok
     * @param string $msg
     * @param string $type
     * @return array|string
     */
    function result($ok = "1", $msg = "ok", $type="")
    {
        $result = array();

        $result["status"] = $ok;
        $result["message"] = $msg;

        if($type == "JSON") {
            $result = json_encode($result);
        }
        return $result;
    }

    /**
     * 重载 request,response,view
     *
     * @param $property
     * @return mixed|object|Response
     */
    function __get( $property )
    {
        switch( $property )
        {
            case 'request' :
                return $this->request = Request::getInstance();

            case 'response' :
                return $this->response = Response::getInstance();

            case 'view' :
                return $this->view = $this->loadView();

            default :
                break;
        }
    }
}
