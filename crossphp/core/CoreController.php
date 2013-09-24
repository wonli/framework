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

    /**
     * 判断请求类型是否为get
     * @return bool
     */
    protected function is_get()
    {
        return strtolower($_SERVER['REQUEST_METHOD']) == 'get';
    }

    /**
     * 是否是cli方式
     * @return bool
     */
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
     * @param $p 调试参数
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
                if(! empty($v) && is_string($v)) {
                    $args[$k] = addslashes(trim($v));
                } else {
                    $args[$k] = $v;
                }
            }
        }
        unset($_POST);
        return $args;
    }

    /**
     * 返回执行的前一页
     *
     * @return
     */
    protected function return_referer()
    {
        return header('location:'.$_SERVER['HTTP_REFERER']);
    }

    /**
     * 跳转到指定页面
     *
     * @param $url 要跳转的路径
     * @return javascript
     */
    protected function to($_controller=null, $params=null, $sec=false)
    {
        $url = $this->view->link($_controller, $params, $sec);
        return header("location:{$url}");
    }

    /**
     * 加载缓存的文件
     *
     * @param $cachename
     * @return mixed
     * @throws CoreException
     */
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
     * view->display 的连接
     *
     * @param null $date
     * @param null $method
     * @return mixed
     */
    protected function display($date=null, $method = null)
    {
        return $this->view->display( $date, $method );
    }

    /**
     * 设置参数
     *
     * @param $debug
     */
    protected function setArgs($debug)
    {
        $this->args = $this->getArgs($debug);
    }

    /**
     * 重设视图action名称
     *
     * @param $action_name
     */
    function setAction( $action_name )
    {
        $this->view->action = $action_name;
        return $this;
    }

    /**
     * 来自app的参数
     *
     * @param bool $obj
     * @return stdClass
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

    /**
     * $_GET
     *
     * @return mixed
     */
    protected function _GET()
    {
        return $_GET;
    }

    /**
     * $_POST
     *
     * @param bool $debug
     * @return bool
     */
    protected function _POST($debug=false)
    {
        if($this->is_post()) {
            $this->setArgs($debug);
            return true;
        }
        return false;
    }

    /**
     * $_FILE
     * @return mixed
     */
    protected function _FILE()
    {
        return $_FILES;
    }
}



