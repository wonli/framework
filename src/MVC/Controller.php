<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\MVC;

use Cross\Interactive\ResponseData;
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
     * 默认数据
     *
     * @var array
     */
    protected $data = ['status' => 1, 'message' => ''];

    /**
     * 状态配置文件
     *
     * @var string
     */
    protected $statusConfigFile = 'config::status.config.php';

    /**
     * 判断是否POST请求
     *
     * @return bool
     */
    protected function isPost(): bool
    {
        return $this->delegate->getRequest()->isPostRequest();
    }

    /**
     * 判断是否GET请求
     *
     * @return bool
     */
    protected function isGet(): bool
    {
        return $this->delegate->getRequest()->isGetRequest();
    }

    /**
     * 是否在命令行下执行
     *
     * @return bool
     */
    protected function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * 判断是否AJAX请求
     *
     * @return boolean
     */
    protected function isAjax(): bool
    {
        return $this->delegate->getRequest()->isAjaxRequest();
    }

    /**
     * 返回执行的前一页
     */
    protected function returnReferer(): void
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
    protected function to(string $controller = null, $params = null, bool $sec = false): void
    {
        $url = $this->view->url($controller, $params, $sec);
        $this->redirect($url);
    }

    /**
     * @param string $url
     * @param int $http_response_status
     * @see Response::redirect
     *
     */
    protected function redirect(string $url, int $http_response_status = 302): void
    {
        $has = $this->delegate->getClosureContainer()->has('redirect', $closure);
        if ($has) {
            $closure($url, $http_response_status);
        } else {
            $this->delegate->getResponse()->redirect($url, $http_response_status);
        }
    }

    /**
     * 视图渲染
     *
     * @param mixed $data
     * @param string $method
     * @param int $http_response_status
     * @throws CoreException
     * @see View::display()
     */
    protected function display($data = null, string $method = null, int $http_response_status = 200): void
    {
        $this->delegate->getResponse()->setResponseStatus($http_response_status);
        $this->view->display($data, $method);
    }

    /**
     * 交互数据对齐
     *
     * @param mixed $data
     * @param bool $combine 是否合并
     * @return array
     * @throws CoreException
     */
    protected function getResponseData($data, bool $combine = false): array
    {
        $responseData = (new ResponseData())->getData();
        if (is_numeric($data)) {
            $responseData['status'] = $data;
        } elseif (is_array($data) && $combine) {
            $manualMergeData = true;
            if (isset($data['data'])) {
                $manualMergeData = false;
                $responseData['data'] = &$data['data'];
            }

            foreach ($data as $k => $v) {
                if (isset($responseData[$k])) {
                    $responseData[$k] = $v;
                } elseif ($manualMergeData) {
                    $responseData['data'][$k] = $v;
                }
            }
        } elseif (is_array($data)) {
            $responseData = array_merge($responseData, $data);
        } else {
            $responseData['message'] = $data;
        }

        if ($responseData['status'] != 1 && empty($responseData['message'])) {
            $responseData['message'] = $this->getStatusMessage($responseData['status']);
        }

        return $responseData;
    }

    /**
     * 获取消息状态内容
     *
     * @param int $status
     * @return string
     * @throws CoreException
     */
    protected function getStatusMessage(int $status): string
    {
        static $statusConfig = null;
        if ($statusConfig === null) {
            $statusConfig = $this->parseGetFile($this->statusConfigFile);
        }

        if (!isset($statusConfig[$status])) {
            throw new CoreException('未定义的错误码: ' . $status);
        }

        return $statusConfig[$status];
    }

    /**
     * 发送一个包含文件名的下载头
     *
     * @param string $file_name
     * @param array $add_header
     * @param bool $only_add_header
     */
    protected function sendDownloadHeader(string $file_name = null, array $add_header = [], bool $only_add_header = false): void
    {
        if (null === $file_name) {
            $file_name = $this->controller . '_' . $this->action;
        }

        $download_header = [
            "Pragma: public",
            "Expires: 0",
            "Cache-Control: must-revalidate, post-check=0, pre-check=0",
            "Content-Type: application/force-download",
            "Content-Type: application/octet-stream",
            "Content-Type: application/download",
            "Content-Disposition:attachment;filename={$file_name}",
            "Content-Transfer-Encoding:binary"
        ];

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
     * @param string $action_name
     * @return $this
     */
    function setAction(string $action_name): self
    {
        $this->view->action = $action_name;
        return $this;
    }
}
