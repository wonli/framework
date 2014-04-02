<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class CoreController
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
        return isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post';
 	}

    /**
     * 判断请求类型是否为get
     * @return bool
     */
    protected function is_get()
    {
        return isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'get';
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
     * @param bool $p 调试参数
     * @return array
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
     * @return void
     */
    protected function return_referer()
    {
        return header('location:'.$_SERVER['HTTP_REFERER']);
    }

    /**
     * 跳转到指定页面
     *
     * @param null $_controller
     * @param null $params
     * @param bool $sec
     */
    protected function to($_controller=null, $params=null, $sec=false)
    {
        $url = $this->view->link($_controller, $params, $sec);
        return header("location:{$url}");
    }

    /**
     * 加载缓存的文件
     *
     * @param $cache_name
     * @throws CoreException
     * @return mixed
     */
    protected function loadCache($cache_name)
    {
        if( file_exists($data_file = APP_PATH.DS.'cache'.DS.$cache_name.'.php') ){
            $helper = require_once $data_file;
            return $helper;
        } else {
            throw new CoreException($cache_name.' is not found!');
        }
    }

    /**
     * view->display 的连接
     *
     * @param null $data
     * @param null $method
     * @param int $http_response_status
     * @return mixed
     */
    protected function display($data = null, $method = null, $http_response_status = 200)
    {
        Response::getInstance()->set_response_status( $http_response_status );
        return $this->view->display( $data, $method );
    }

    /**
     * 发送下载请求
     *
     * @param null $data
     * @param null $method
     * @param null $file_name
     * @param array $add_header
     * @param bool $only_add_header
     * @return bool|mixed
     */
    protected function fileDisplay($data = null, $method = null, $file_name = null, $add_header = array(), $only_add_header = false)
    {
        if (null == $file_name)
        {
            $file_name = $method.time();
        }

        $down_header = array(
            "Pragma: public",
            "Expires: 0",
            "Cache-Control:must-revalidate, post-check=0, pre-check=0",
            "Content-Type: application/force-download",
            "Content-Type: application/octet-stream",
            "Content-Type: application/download",
            "Content-Disposition:attachment;filename={$file_name}",
            "Content-Transfer-Encoding:binary"
        );

        if (! empty($add_header))
        {
            if (true === $only_add_header)
            {
                $down_header = $add_header;
            } else {
                $down_header = array_merge($down_header, $add_header);
            }
        }

        Response::getInstance()->set_header( $down_header );
        if ($data && $method)
        {
            return $this->view->display( $data, $method );
        }

        return true;
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
     * @return $this
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



