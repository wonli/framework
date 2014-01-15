<?php
/**
 * @Author:  wonli <wonli@live.com>
 * @Version: $Id: Router.php 138 2013-09-13 09:59:03Z ideaa $
 */

class Router implements RouterInterface
{
    /**
     * @var 用户配置
     */
    private $init;

    /**
     * @var 控制器
     */
    private $controller;

    /**
     * @var action
     */
    private $action;

    /**
     * @var 参数
     */
    private $params;

    /**
     * @var string 默认action
     */
    public static $default_action = "index";

    /**
     * @var $router_params;
     */
    private $router_params = array();

    /**
     * @var 配置参数
     */
    private $config;


    private static $instance;

    private function __construct( $_config )
    {
        $this->config = $_config;
    }

    /**
     * 实例化类
     */
    static function init( config $_config )
    {
        if(! self::$instance)
        {
            self::$instance = new Router( $_config );
        }
        return self::$instance;
    }

    /**
     * 设置url解析参数
     *
     * @param $params
     * @return $this
     */
    public function set_router_params( $params = null )
    {
        if(null === $params)
        {
            $this->router_params = $this->initParams();
        }
        else
        {
            $this->router_params = $params;
        }
        return $this;
    }

    /**
     * 要解析的请求string
     *
     * @return array
     */
    public function get_router_params()
    {
        return $this->router_params;
    }

    /**
     * 初始化参数 按类型返回要解析的url字符串
     *
     * @return $this
     */
    function initParams( )
    {
        $url_config = $this->config->get("url");
        $request = array();

        $r = $_REQUEST;

        switch ($url_config ['type'])
        {
            case 1 :
                $request = Request::getInstance()->getUrlRequest( 1 );
                return self::parseString($request, $url_config);

            case 2 :
                $path_info = Request::getInstance()->getUrlRequest( 2 );
                $request = self::parseString($path_info, $url_config);

                if(! empty($request))
                {
                    return array_merge($request, $r);
                }

                return $request;

            default :
                $request = array($r["c"], $r["a"]);
                unset($r["c"], $r["a"]);
                return array_merge($request, $r);
        }
    }

    /**
     * 解析请求字符串
     * <pre>
     * [0] 解析的结果
     * [1] 抛出异常
     * [2] 返回空字符串
     * </pre>
     *
     * @param $_query_string
     * @param $url_config
     * @throws FrontException
     * @return array
     */
    static function parseString( $_query_string, $url_config )
    {
        $_query_string = trim(trim( $_query_string, "/" ), $url_config['dot']);
        $router_params = array();

        if (! $_query_string) {
            return $router_params;
        }

        $_url_ext = $url_config["ext"];
        if(isset($_url_ext[1]) && false !== strpos($_query_string, $_url_ext[0]))
        {
            list($_query_string, $ext) = explode($_url_ext[0], $_query_string);
            if($ext !== substr($_url_ext, 1)) {
                throw new FrontException("找不到该页面");
            }
        }


        if ( false !== strpos($_query_string, $url_config['dot']) ) {
            $router_params = explode($url_config['dot'], $_query_string);
        } else {
            $router_params = array($_query_string);
        }
        return $router_params;
    }

    /**
     * 默认控制器和方法 init['url']['*']
     *
     * @param $init_default
     * @return array
     * @throws CoreException
     */
    private function getDefaultRouter( $init_default )
    {
        if( $init_default )
        {
            list($_defController, $_defAction) = explode(":", $init_default);
            $_defaultRouter = array();

            if( isset($_defController) ) {
                $_defaultRouter['controller'] = $_defController;
            } else {
                throw new CoreException("please define the default controller in the APP_PATH/APP_NAME/init file!");
            }

            if(isset($_defAction)) {
                $_defaultRouter['action'] = $_defAction;
            } else {
                throw new CoreException("please define the default action in the APP_PATH/APP_NAME/init file!");
            }

            $_defaultRouter['params'] = $_REQUEST;
            return $_defaultRouter;
        }
        else throw new CoreException("undefined default router!");
    }

    /**
     * 设置router
     *
     * @return $this
     * @throws FrontException
     */
    public function getRouter( )
    {
        $_router = $this->get_router_params();

        #没有请求时的控制器和路由
        if( empty($_router) )
        {
            $_defaultRouter = $this->getDefaultRouter( $this->config->get("url", "*") );

            $this->setController($_defaultRouter['controller']);
            $this->setAction($_defaultRouter['action']);
            $this->setParams($_defaultRouter['params']);
        }
        else
        {
            $this->setRouter( $_router );
        }
        return $this;
    }

    /**
     * 解析alias配置
     *
     * @param $request
     * @internal param $router
     */
    function setRouter($request)
    {
        /**
         * 控制器配置
         */
        $controller_config = $this->config->get("controller");
        $_controller = $request [0];
        $this->config->set('url', array('ori_controller' => $_controller));
        array_shift($request);

        if(isset($controller_config [ $_controller ]))
        {
            $_config = $controller_config [ $_controller ];
        }

        if( isset($_config['alias']) && !empty($_config['alias']) )
        {
            $_calias = $_config['alias'];
            if(is_array($_calias))
            {
                if(isset($request [0]))
                {
                    $_action = $request [0];
                    $this->config->set('url', array('ori_action' => $_action));
                    array_shift( $request );

                    if(isset($_calias [$_action]))
                    {
                        if(false !== strpos($_calias [$_action], ":"))
                        {
                            $_calias_ = explode(":", $_calias [$_action]);
                            $_action = $_calias_[0];
                            array_shift($_calias_);
                            $alias_params = $_calias_;
                        }
                        else
                        {
                            $_action = $_calias [$_action];
                        }
                    }
                }
                else
                {
                    $_action = self::$default_action;
                }
            }
            else
            {
                if( false !== strpos($_calias, ":") )
                {
                    $_user_alias = explode(":", $_calias);
                    $_controller = $_user_alias [0];
                    array_shift($_user_alias);

                    $_action = $_user_alias [0];
                    array_shift($_user_alias);

                    $alias_params = $_user_alias;
                }
                else
                {
                    $_controller = $_calias;

                    if(isset($request [0]))
                    {
                        $_action = $request[0];
                        array_shift($request);
                    }
                    else
                    {
                        $_action = self::$default_action;
                    }
                }
            }
        }
        else
        {
            if( isset($request[0]) )
            {
                $_action = $request [0];
                $this->config->set('url', array('ori_action' => $_action));
                array_shift( $request );
            }
            else
            {
                $_action = self::$default_action;
            }
        }

        if(isset($alias_params) && ! empty($alias_params))
        {
            $_params = array_merge($request, $alias_params);
        }
        else
        {
            $_params = $request;
        }

        $this->setController( $_controller );
        $this->setAction( $_action );
        $this->setParams( $_params );
    }


    /**
     * 设置controller
     *
     * @param $controller
     */
    private function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * 设置Action
     *
     * @param $action
     */
    private function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * 设置参数
     *
     * @param $params
     */
    private function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * 返回控制器名称
     * @return mixed
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * 返回action名称
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * 返回参数
     *
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }
}

