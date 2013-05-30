<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:       wonli
 */
class CoreController extends FrameBase
{
    protected $args;

 	/**
 	 * 判断一个链接是否为post请求
 	 * @return boolean
 	 */
 	protected function is_post()
 	{
        return strtolower($_SERVER['REQUEST_METHOD']) == 'post';
 	}

    protected function is_get()
    {
        return strtolower($_SERVER['REQUEST_METHOD']) == 'get';
    }
    
    protected function is_cli()
    {
        define('IS_CLI', PHP_SAPI === 'cli');
        if(IS_CLI) return true;
        return false;    	
    }
    /**
	 * 判断是否为一个ajax请求
	 * @return boolean
	 */
	protected function is_ajax_request()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	}

    /**
     * 取得通过POST传递的参数
     *
     * @param $p 是否打印
     * @return
     */
    protected function getArgs($p=false)
    {
        if($p) {
            var_dump($_POST);
            return;
        }

        $args = array();
        if( count($_POST) > 0 ) {
            foreach($_POST as $k=>$v) {
                $args[$k] = addslashes(trim($v));
            }
        }
        unset($_POST);
        return $args;
    }

    /**
     * 返回执行的前一页
     * @return
     */
    protected function reload()
    {
        return header('location:'.$_SERVER['HTTP_REFERER']);
    }

    /**
     * 跳转到指定页面
     *
     * @param $url 要跳转的路径
     * @return javascript
     */
    protected function to($_controller, $params=null, $sec=false)
    {
        $view = new CoreView();
        $url = $view->link($_controller, $params, $sec);
        return header("location:{$url}");
    }

    protected function loadCache($cachename)
    {
        if( file_exists($datafile = APP_PATH.DS.'cache'.DS.$cachename.'.php') ){
            $helper = require_once $datafile;
            return $helper;
        } else {
            throw new CoreException($cachename.' is not found!');
        }
    }

    /**
     * 加载其他控制器
     *
     * @param $params 格式 "controller:action"
     * @param $args controller:action 的参数
     * @return mixed
     */
    protected function load($params, $args = null)
    {
        if(false !== strpos($params, ":"))
        {
            list($controller_name, $action) = explode(":", $params);
        } else {
            $controller_name = $params;
            $action = 'index';
        }

        $load_controller = new $controller_name;
        $load_controller->module = $this->initModule($controller_name);
        $load_controller->view = $this->initView($action, $controller_name);

        ob_start();
        $load_controller->$action($args);
        return ob_get_clean();
    }

    protected function loadModule( $module_name )
    {
        $model_class_name = ucfirst($module_name.'Module');
        return new $model_class_name($module_name);
    }

    protected function setArgs($debug)
    {
        $this->args = $this->getArgs($debug);
    }

    /**
     * 取得from提交的参数
     * @param  boolean $obj 是否返回数组
     * @return mixed       from提交的参数
     */
    protected function args($obj=false)
    {
        if($obj) {
            $obj = new stdClass();
            foreach($this->args as $k=>$value) {
                $obj->{$k} = $value;
            }
            return $obj;
        }
        return $this->args;
    }

    protected function _GET()
    {
        return $_GET;
    }

    protected function _POST($debug=false)
    {
        if($this->is_post()) {
            $this->setArgs($debug);
            return true;
        }
        return false;
    }

    protected function _FILE()
    {
        return $_FILE;
    }
}



