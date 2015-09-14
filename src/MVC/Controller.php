<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.4.1
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
     * 是否cli方式发起请求
     *
     * @return bool
     */
    protected function is_cli()
    {
        return PHP_SAPI === 'cli';
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
     * @return string
     */
    protected function return_referer()
    {
        return $this->redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * 先生成连接再redirect
     *
     * @param string|null $_controller
     * @param string|array $params
     * @param bool $sec
     * @return string
     */
    protected function to($_controller = null, $params = null, $sec = false)
    {
        $url = $this->view->url($_controller, $params, $sec);
        return $this->redirect($url);
    }

    /**
     * @see Response::redirect
     *
     * @param string $url
     * @param int $http_response_status
     * @return string
     */
    protected function redirect($url, $http_response_status = 200)
    {
        return $this->response->redirect($url, $http_response_status);
    }

    /**
     * @see View::display()
     *
     * @param null|mixed $data
     * @param null|string $method
     * @param int $http_response_status
     */
    protected function display($data = null, $method = null, $http_response_status = 200)
    {
        Response::getInstance()->setResponseStatus($http_response_status);
        $this->view->display($data, $method);
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
