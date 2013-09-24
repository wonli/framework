<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
* Author:       wonli
* Contact:      wonli@live.com
* Date:         2012.01
* Description:  router
*/
class Router implements RouterInterface
{
    private $init;

    private $controller;
    private $action;
    private $params;

    private static $instance;

    /**
     * @param $config_path 默认参数的路径
     */
    private function __construct( )
    {

    }

    /**
     * 单例实现
     */
    static function getInstance( )
    {
        if(! self::$instance) {
            self::$instance = new Router( );
        }
        return self::$instance;
    }

    /**
     * @param $init 解析参数
     */
    public function set($init)
    {
        $this->init = $init;
        return $this;
    }

    /**
     * 读取默认配置
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
                throw new CoreException("please define the default controller in the App/Config/init file!");
            }

            if(isset($_defAction)) {
                $_defaultRouter['action'] = $_defAction;
            } else {
                throw new CoreException("please define the default action in the App/Config/init file!");
            }

            $_defaultRouter['params'] = null;
            return $_defaultRouter;
        }
        else throw new CoreException("default router is not an array!");
    }

    /**
     * 读取用户自定义路由别名
     * @param  array $init_alias init.php中自定义的数组
     * @return [type]             [description]
     */
    private function getAlias( $init_alias )
    {
        if( isset($init_alias) && is_array($init_alias) ) {
            return $init_alias;
        }
    }

    /**
     * 解析querystring
     * @param  array $init_url 用户配置参数
     * @return (null array)  解析结果
     */
    private function paseRequest($init_url)
    {
        $_querystring = trim($this->getQueryString(), '/');

        if(! $_querystring) {
            return ;
        }

        $_urlext = $init_url["ext"];
        if(isset($_urlext[1]) && false !== strpos($_querystring, $_urlext[0])) {

            list($_querystring, $ext) = explode($_urlext[0], $_querystring);
            if($ext !== substr($_urlext,1)) {
                throw new FrontException("找不到该页面");
            }
        }

        if($init_url["dot"]) {
            return array_filter( explode($init_url["dot"], $_querystring) );
        }
    }

    /**
     * 解析当前请求
     */
    public function getRouter( )
    {
        $_router = $this->paseRequest($this->init['url']);
        $_defaultRouter = $this->getDefaultRouter( $this->init["request"]["*"] );
        $_config = $this->init["controller"];

        #没有请求直接返回默认控制器和路由
        if(! $_router) {
            $this->setController($_defaultRouter["controller"]);
            $this->setAction($_defaultRouter["action"]);
            return $this;
        }

        $_thisRouter = array();
        $_controller = urldecode($_router[0]);
        array_shift($_router);

        if(isset($this->init["controller"][$_controller]["alias"]) && $_calias = $this->init["controller"][$_controller]["alias"] ) {
            if( false !== strpos($_calias, ":") ) {
                list($_c, $_action) = explode(":", $_calias);
                $_thisRouter["controller"] = $_c;
            } else {
                $_thisRouter["controller"] = $_calias;
                if(isset($_router[0])) {
                    $_action = $_router[0];
                    array_shift($_router);
                } else {
                    $_action = $_defaultRouter['action'];
                }
            }
        } else {
            $_thisRouter['controller'] = ucfirst($_controller);
            $_action = isset($_router[0]) ? $_router[0] : $_defaultRouter['action'];
            array_shift($_router);
        }

        try
        {
            #会触发autoLoad
            $is_callable = new ReflectionMethod($_thisRouter['controller'], $_action);
            if( $is_callable->isPublic() )
            {
                $_thisRouter['action'] = $_action;
                $_params = $_router;
            } else {
                throw new FrontException("不被允许访问的方法!");
            }

        } catch (ReflectionException $e) {
            throw new FrontException("无法找到您请求的页面! ".$e->getMessage());
        }

        if(isset($_params) && !empty($_params))
        {
            if(count($_params) == 1) {
                $_thisRouter['params'] = $_params[0];
            } else {
                $_thisRouter['params'] = $_params;
            }
        } else {
            $_thisRouter['params'] = null;
        }

        $this->setController($_thisRouter["controller"]);
        $this->setAction($_thisRouter["action"]);
        $this->setParams($_thisRouter["params"]);
        return $this;
    }

    /**
     * 取得当前的querystring
     * @return string
     */
    private function getQueryString()
    {
        return Request::getInstance()->getQueryString();
    }

    /**
     * 设置请求的controller
     * @param string $controller controller
     */
    private function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * 设置请求的Action
     * @param string $action
     */
    private function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * 设置当前请求的参数
     * @param (string array) $params 参数
     */
    private function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * 返回当前请求的控制器
     * @return string 控制器名称
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * 返回当前请求的控制器
     * @return string 当前请求的控制器
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * 返回当前请求的参数
     * @return string array 当前请求的参数或参数数组
     */
    public function getParams()
    {
        return $this->params;
    }
}

