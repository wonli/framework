<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.1
 */
namespace Cross\MVC;

use Cross\Core\FrameBase;
use Cross\Core\Response;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Controller
 * @package Cross\MVC
 */
class Controller extends FrameBase
{
    /**
     * 判断一个链接是否为post请求
     *
     * @return boolean
     */
    protected function is_post()
    {
        return $this->request->isPostRequest();
    }

    /**
     * 判断请求类型是否为get
     *
     * @return bool
     */
    protected function is_get()
    {
        return $this->request->isGetRequest();
    }

    /**
     * 是否是cli方式
     *
     * @return bool
     */
    protected function is_cli()
    {
        define('IS_CLI', PHP_SAPI === 'cli');
        if (IS_CLI) return true;

        return false;
    }

    /**
     * 判断是否为一个ajax请求
     *
     * @return boolean
     */
    protected function is_ajax_request()
    {
        return $this->request->isAjaxRequest();
    }

    /**
     * 返回执行的前一页
     *
     * @return void
     */
    protected function return_referer()
    {
        header('location:' . $_SERVER['HTTP_REFERER']);
        exit(0);
    }

    /**
     * 跳转到指定页面
     *
     * @param null $_controller
     * @param null $params
     * @param bool $sec
     */
    protected function to($_controller = null, $params = null, $sec = false)
    {
        $url = $this->view->link($_controller, $params, $sec);
        header("Location: {$url}");
        exit(0);
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
        Response::getInstance()->setResponseStatus($http_response_status);
        return $this->view->display($data, $method);
    }

    /**
     * 发送一个包含文件名的下载头
     *
     * @param null $file_name
     * @param array $add_header
     * @param bool $only_add_header
     */
    protected function sendDownloadHeader($file_name = null, $add_header = array(), $only_add_header = false)
    {
        if (null === $file_name) {
            $file_name = parent::getController() . '_' . parent::getAction();
        }

        $download_header = array(
            "Pragma: public",
            "Expires: 0",
            "Cache-Control:must-revalidate, post-check=0, pre-check=0",
            "Content-Type: application/force-download",
            "Content-Type: application/octet-stream",
            "Content-Type: application/download",
            "Content-Disposition:attachment;filename={$file_name}",
            "Content-Transfer-Encoding:binary"
        );

        if (!empty($add_header)) {
            if (true === $only_add_header) {
                $download_header = $add_header;
            } else {
                $download_header = array_merge($download_header, $add_header);
            }
        }

        Response::getInstance()->setHeader($download_header);
    }

    /**
     * 重设视图action名称
     *
     * @param $action_name
     * @return $this
     */
    function setAction($action_name)
    {
        $this->view->action = $action_name;
        return $this;
    }
}
