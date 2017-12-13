<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\MVC;

use Cross\Exception\CoreException;
use Cross\Core\FrameBase;

/**
 * @author wonli <wonli@live.com>
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
        return $this->delegate->getRequest()->isPostRequest();
    }

    /**
     * 判断请求类型是否为get
     *
     * @return bool
     */
    protected function is_get()
    {
        return $this->delegate->getRequest()->isGetRequest();
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
        return $this->delegate->getRequest()->isAjaxRequest();
    }

    /**
     * 返回执行的前一页
     */
    protected function return_referer()
    {
        $this->redirect($this->request->getUrlReferrer());
    }

    /**
     * 先生成连接再redirect
     *
     * @param string|null $controller controller:action
     * @param string|array $params
     * @param bool $sec
     * @throws CoreException
     */
    protected function to($controller = null, $params = null, $sec = false)
    {
        $url = $this->view->url($controller, $params, $sec);
        $this->redirect($url);
    }

    /**
     * @see Response::redirect
     *
     * @param string $url
     * @param int $http_response_status
     */
    protected function redirect($url, $http_response_status = 200)
    {
        $this->delegate->getResponse()->redirect($url, $http_response_status);
    }

    /**
     * @see View::display()
     *
     * @param null|mixed $data
     * @param null|string $method
     * @param int $http_response_status
     * @throws CoreException
     */
    protected function display($data = null, $method = null, $http_response_status = 200)
    {
        $this->delegate->getResponse()->setResponseStatus($http_response_status);
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
            $file_name = $this->controller . '_' . $this->action;
        }

        $download_header = array(
            "Pragma: public",
            "Expires: 0",
            "Cache-Control: must-revalidate, post-check=0, pre-check=0",
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

        $this->delegate->getResponse()->setHeader($download_header);
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
