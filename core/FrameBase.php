<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class FrameBase
 */

class FrameBase
{
    /**
     * app配置
     *
     * @var array
     */
    protected $config;

    /**
     * 请求的参数列表
     *
     * @var array
     */
    protected $params;

    /**
     * action
     *
     * @var string
     */
    protected $action;

    /**
     * 控制器名称
     *
     * @var string
     */
    protected $controller;

    /**
     * url加密时用到的key
     *
     * @var string
     */
    protected $url_crypt_key;

    /**
     * module的实例
     *
     * @var array
     */
    static protected $moduleInstance = array();

    /**
     * object的缓存hash
     *
     * @var array
     */
    static protected $objectCache = array();

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
     * 获取action
     *
     * @return string
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
     * @param string $key key
     * @param string $value 值
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
     * @param string $type
     * @return bool|string
     */
    protected function urlEncrypt($tex, $type="encode")
    {
        $key = $this->getUrlEncryptKey();
        return Helper::encode_params($tex, $key, $type);
    }

    /**
     * 设置url加密时候用到的key
     *
     * @param $key
     */
    private function setUrlEncryptKey($key)
    {
        $this->url_crypt_key = $key;
    }

    /**
     * 获取url加密/解密时用到的key
     */
    protected function getUrlEncryptKey()
    {
        if (! $this->url_crypt_key)
        {
            $url_crypt_key = $this->config->get('url', 'crypto_key');
            if (! $url_crypt_key) {
                $url_crypt_key = 'crossphp';
            }

            $this->setUrlEncryptKey($url_crypt_key);
        }

        return $this->url_crypt_key;
    }

    /**
     * 还原加密后的参数
     *
     * @param null $params
     * @return bool|string
     */
    protected function sParams($params=null)
    {
        $url_type = $this->config->get('url', 'type');

        if (null === $params) {
            if ($url_type == 2) {
                $params = current(array_keys( $this->params ));
            } else {
                $params = $this->params;
                if (is_array($this->params)) {
                    $params = current( array_values( $this->params ) );
                }
            }
        }

        $result = array();
        $decode_params_str = false;
        if (is_string($params)) {
            $decode_params_str = $this->urlEncrypt($params, "decode");
        }

        if (false == $decode_params_str) {
            return $this->params;
        }

        if ($url_type == 2) {
            parse_str($decode_params_str, $result);
        } else {
            $result_array = explode($this->config->get('url', 'dot'), $decode_params_str);
            $annotate = Dispatcher::getActionConfig();
            $result = Dispatcher::combineParamsAnnotateConfig($result_array, $annotate['params']);
        }

        return $result;
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
     * @param array $params
     * @param bool $run_action
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
        if (! isset(self::$moduleInstance[ $module_name ]))
        {
            $args = func_get_args();
            if ($params != '' && count($args) > 2)
            {
                array_shift($args);
                $params = $args;
            }

            if (false !== strpos( $module_name, "/"))
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
     * 缓存并返回object的一个实例(module为一个对象)
     *
     * @param $objectInstance
     * @return mixed
     * @throws CoreException
     */
    protected function loadObject( $objectInstance )
    {
        try
        {
            $obj = new ReflectionClass( $objectInstance );
            if(! isset(self::$objectCache[ $obj->name ]))
            {
                self::$objectCache[ $obj->name ] = $objectInstance;
            }

            return self::$objectCache[ $obj->name ];
        } catch (Exception $e) {
            throw new CoreException( 'cache module failed!' );
        }
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
     * @return string
     */
    function run( $act )
    {
        $mr = new ReflectionClass($this);

        $_before = "{$act}_before";
        $_after = "{$act}_after";

        ob_start();
        if ( $mr->hasMethod($_before) ) {
            echo $this->$_before();
        }
        echo $this->$act( );
        if ( $mr->hasMethod($_after) ) {
            echo $this->$_after();
        }
        return ob_get_clean();
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
        switch($property)
        {
            case 'request' :
                return $this->request = Request::getInstance();

            case 'response' :
                return $this->response = Response::getInstance();

            case 'view' :
                return $this->view = $this->loadView();

            default :
                return null;
                break;
        }
    }
}
