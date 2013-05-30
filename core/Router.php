<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:  wonli <wonli@live.com>
 * @Version: $Id: Router.php 77 2013-05-23 13:54:54Z ideaa $
 */

class Router implements RouterInterface
{
    private $init;

    private $controller;
    private $action;
    private $params;

    private $default_action = "index";
    
    
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
        $_querystring = trim(trim($this->get_parse_str( $init_url["type"] ), "/"), $init_url["dot"]);

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
        $_defaultRouter = $this->getDefaultRouter( $this->init["url"]["*"] );

        #没有请求时的控制器和路由
        if(! $_router) {
            $this->setController($_defaultRouter["controller"]);
            $this->setAction($_defaultRouter["action"]);
            return $this;
        }
        
        $_thisRouter = array();
        $_controller = urldecode($_router[0]);
        array_shift($_router);
        
        if( isset($this->init["controller"][$_controller]["alias"]) )
        {
            $_calias = $this->init["controller"][$_controller]["alias"];

            if(is_array($_calias)) 
            {
                $_thisRouter["controller"] = ucfirst($_controller);
                
                if( isset($_router[0]) ) {
                    $_action = $_router[0];
                    
                    if(isset( $_calias[$_action] )) {
                    	$_action = $_calias[$_action];
                    }

                    array_shift($_router);
                } else {
                    $_action = $this->default_action;
                }

            } else {            	
                if( false !== strpos($_calias, ":") ) {
    
                    $_user_alias = explode(":", $_calias);
                    $_thisRouter["controller"] = ucfirst($_user_alias[0]);
                    $_action = $_user_alias[1];
                    if(isset($_user_alias[2])) {
                        $_params = array_slice($_user_alias, 2);
                    }
    
                } else {
                    $_thisRouter["controller"] = ucfirst($_calias);
                    if(isset($_router[0])) {
                        $_action = $_router[0];
                        array_shift($_router);
                    } else {
                        $_action = $this->default_action;
                    }
                }
            }
        } else {
            $_thisRouter['controller'] = ucfirst($_controller);
            if( isset($_router[0]) ) {
                $_action = $_router[0];
                array_shift($_router);
            } else {
                $_action = $this->default_action;
            }
        }

        try{
            #会触发autoLoad
            $is_callable = new ReflectionMethod($_thisRouter['controller'], $_action);                    
        } catch (Exception $e) {
            
            #控制器静态属性_act_alias_指定action的别名
            try{
                $_property = new ReflectionProperty($_thisRouter['controller'], '_act_alias_');            
                $act_alias = $_property->getValue();
            
                if( isset($act_alias [$_action]) ) {
                    $_action = $act_alias [$_action];
                }
                
                $is_callable = new ReflectionMethod($_thisRouter['controller'], $_action);
                
            } catch (Exception $e) { 
                throw new FrontException("无法找到您请求的页面! ".$e->getMessage());
            }

        }
        
        if( $is_callable->isPublic() )
        {
            $_thisRouter['action'] = $_action;

            if(isset($_params)) {
                if(! empty($_router)) {
                    $_params = array_merge($_params, $_router);
                } else {
                    $_params = $_params;
                }
            } else {
                $_params = $_router;
            }

        } else {
            throw new FrontException("不被允许访问的方法!");
        }

        if($this->init['url'] ['type'] == 1)
        {        
            if(isset($_params) && ! empty($_params))
            {
                if(count($_params) == 1) {                    
                    if(strpos($_params[0], "&") !== false) {
                        parse_str($_params[0], $_thisRouter['params']);
                    } else {
                        $_thisRouter['params'] = $_params[0];
                    }
                } else {
                    $_thisRouter['params'] = $_params;
                }
            } else {
                $_thisRouter['params'] = null;
            }
        } else {
            $_thisRouter ["params"] = $_GET;
        }
        
        $this->setController($_thisRouter["controller"]);
        $this->setAction($_thisRouter["action"]);
        $this->setParams($_thisRouter["params"]);
        return $this;
    }

    /**
     * 要解析的字符串
     * @return string
     */
    private function get_parse_str($type = 1)
    {
        return Request::getInstance()->getUrlRequest($type);
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

