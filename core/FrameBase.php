<?php defined('CROSSPHP_PATH')or die('Access Denied');
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
            $this->setConfig( Dispatcher::$appConfig );
        }

        if(! $this->controller) {
            $this->setControllerName( Dispatcher::$controller );
        }

        if(! $this->action) {
            $this->setActionName( Dispatcher::$action );
        }

        if(! $this->params) {
            $this->setParams( Dispatcher::$params );
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
    function setAuth($key, $value, $exp=86400)
    {
        $auth_type = Cross::Config()->get("sys", "auth");
        return HttpAuth::factory( $auth_type )->set($key, $value, $exp);
    }

    /**
     * 解密会话
     *
     * @param $key
     * @param bool $de
     * @return bool|mixed|string
     */
    function getAuth($key, $de = false)
    {
        $auth_type = Cross::Config()->get("sys", "auth");
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
    function encode_params($tex, $key, $type="encode")
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
     * 加载module
     *
     * @param $module_name
     * @return mixed
     */
    protected function loadModule( $module_name, $db_type = '' )
    {
        if( false !== strpos( $module_name, "/") )
        {
            list($_, $module_name) = explode("/", $module_name);
            Loader::import("::modules/{$module_name}Module");
        }

        $_module = ucfirst($module_name.'Module');
        return new $_module( );
    }

    /**
     * 加载视图
     *
     * @param null $action
     * @param null $controller_name
     * @return mixed
     */
    public function loadView($controller_name = null, $action = null)
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
     * 运行app TODO 检查是否有页面缓存
     *
     * @param $act
     * @param $params
     */
    function run($act , $params)
    {
        /**
            $_key_dot = self::$appConfig->get("url", "dot");
            $_app_name = self::$appConfig->get("sys", "app_name");

            //简单文件缓存
            if(empty(self::$params)) {
            $_key_params = 'index';
            } else {
            if(is_array(self::$params)) {
            $_key_params = implode($_key_dot, self::$params);
            } else {
            $_key_params = self::$params;
            }
            }
            $_cache_key = self::$controller.$_key_dot.self::$action.$_key_dot.$_key_params;
            $this_controller_config = self::$appConfig->get("controller", strtolower(self::$controller));

            $_cacheConfig = null;
            if(isset($this_controller_config["cache"]) && $this_controller_config["cache"][0] === true) {
            list($_isCache, $_cacheConfig) = $this_controller_config["cache"];
            $_ex = false;

            if(true === $_isCache)
            {
            $cache=Cache::create( $_cacheConfig, $_cache_key );
            $_ex = $cache->getExtime();
            }

            if(true === $_isCache && $_ex) {
            return $cache->get();
            }
            }
            self::$cache_config = $_cacheConfig;
         */

        $mr = new ReflectionClass($this);

        $_before = "{$act}_before";
        $_after = "{$act}_after";

        if( $mr->hasMethod($_before) )
        {
            $this->$_before();
        }

        $this->$act( $params );

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
     * request,response,view 重载
     *
     * @param $property
     * @return action|mixed
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
