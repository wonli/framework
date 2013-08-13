<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:  wonli <wonli@live.com>
 * @Version: $Id: Router.php 99 2013-08-02 11:04:36Z ideaa $
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
     * @var 单例
     */
    private static $instance;

    private function __construct( $_config )
    {
        $this->setInit($_config);
        $this->initParams();
    }

    /**
     * 单例实现
     */
    static function getInstance( $_config )
    {
        if(! self::$instance) {
            self::$instance = new Router( $_config );
        }
        return self::$instance;
    }

    /**
     * 设置url配置
     *
     * @param $init
     * @return $this
     */
    public function setInit($init)
    {
        $this->init = $init ['url'];
        return $this;
    }

    /**
     * 设置url解析参数
     *
     * @param $params
     */
    public function set_router_params($params)
    {
        $this->router_params = $params;
        return $this;
    }

    /**
     * 初始化参数 按类型返回要解析的url字符串
     *
     * @return $this
     */
    function initParams( )
    {
        $url_config = $this->init;
        $params = '';
        $r = $_REQUEST;

        switch ($url_config ['type'])
        {
            case 1 :
            case 2 :
                $request = Request::getInstance()->getUrlRequest( $url_config ['type'] );
                $params = self::paseString($request, $url_config);
                break;

            default :
                $params = array($r["c"], $r["a"]);
                unset($r["c"], $r["a"]);
                $params = array_merge($params, $r);
                break;
        }

        return $this->set_router_params($params);
    }

    /**
     * 解析querystring
     * @param  array $init_url 用户配置参数
     * @return <pre>
     * [0] 解析的结果
     * [1] 抛出异常
     * [2] 返回空字符串
     * </pre>
     */
    static function paseString( $_querystring, $url_config )
    {
        $_querystring = trim(trim( $_querystring, "/" ), $url_config['dot']);

        if(! $_querystring) {
            return ;
        }

        $_urlext = $url_config["ext"];
        if(isset($_urlext[1]) && false !== strpos($_querystring, $_urlext[0]))
        {
            list($_querystring, $ext) = explode($_urlext[0], $_querystring);
            if($ext !== substr($_urlext, 1)) {
                throw new FrontException("找不到该页面");
            }
        }

        if($url_config['dot']) {
            $router_params = array_filter( explode($url_config['dot'], $_querystring) );
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

            $_defaultRouter['params'] = null;
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
        $_router = $this->router_params;
        $_defaultRouter = $this->getDefaultRouter( $this->init["*"] );

        #没有请求时的控制器和路由
        if( empty($_router) )
        {
            $this->setController($_defaultRouter["controller"]);
            $this->setAction($_defaultRouter["action"]);
            $this->setParams($_GET);
        }
        else
        {
            $_controller = urldecode($_router[0]);

            $this->setController($_controller);
            array_shift($_router);

            if(isset($_router[0])) {
                $this->setAction($_router[0]);
                array_shift($_router);
            } else {
                $this->setAction(self::$default_action);
            }

            if($this->init ['type'] == 2)
            {
                $this->setParams($_REQUEST);
            } else {                
                if( empty($_router) )
                {                
                    $_params = '';
                } else {
                    if( isset($_router[1]) ) 
                    {
                        $_params = $_router;
                    } else {
                        $_params = $_router[0];
                    }                
                }
                
                $this->setParams($_params);
            }
        }

        return $this;
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

