<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
* Author:       wonli
* Contact:	    wonli@live.com
* Date:	        2011.08
* Description:  controller.php
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
        return header("location:".$this->view->link($_controller, $params, $sec));
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

    protected function load()
    {

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



