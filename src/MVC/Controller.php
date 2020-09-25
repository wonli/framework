<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\MVC;


use Cross\Exception\LogicStatusException;
use Cross\Interactive\ResponseData;
use Cross\Exception\CoreException;
use Cross\Interactive\DataFilter;
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
    protected $data = [];

    /**
     * 状态配置文件
     *
     * @var string
     */
    protected $statusConfigFile = 'config::status.config.php';

    /**
     * Controller constructor.
     */
    function __construct()
    {
        parent::__construct();
        $this->data = ResponseData::builder()->getData();
    }

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
        $this->redirect($this->delegate->getRequest()->getUrlReferrer());
    }

    /**
     * 获取输入数据
     *
     * @param string $key
     * @param mixed $default
     * @return DataFilter
     */
    function input(string $key, $default = null): DataFilter
    {
        $val = '';
        $dataContainer = array_merge($this->params, $this->request->getRequestData(), $this->request->getPostData());
        if (is_array($dataContainer)) {
            $val = $dataContainer[$key] ?? null;
        }

        if (empty($val) && null !== $default) {
            $val = $default;
        }

        return new DataFilter($val);
    }

    /**
     * 重定向到指定的控制器
     *
     * @param string|null $controller controller:action
     * @param string|array|null $params
     * @param bool $sec
     * @throws CoreException
     */
    protected function to(string $controller = null, $params = null, bool $sec = false): void
    {
        $url = $this->view->url($controller, $params, $sec);
        $this->redirect($url);
    }

    /**
     * 重定向
     *
     * @param string $url
     * @param int $httpResponseStatus
     * @see Response::redirect
     *
     */
    protected function redirect(string $url, int $httpResponseStatus = 302): void
    {
        $this->delegate->getResponse()->redirect($url, $httpResponseStatus);
    }

    /**
     * 发送一个错误状态
     *
     * @param int $status
     * @param mixed $message
     * @throws CoreException|LogicStatusException
     */
    protected function end(int $status, string $message = null)
    {
        if ($status == 1) {
            throw new CoreException('Incorrect status value!');
        }

        throw new LogicStatusException($status, $message);
    }

    /**
     * 视图渲染
     *
     * @param mixed $data
     * @param string|null $method
     * @param int $httpResponseStatus
     * @throws CoreException
     * @see View::display()
     */
    protected function display($data = null, string $method = null, int $httpResponseStatus = 200): void
    {
        $this->delegate->getResponse()->setResponseStatus($httpResponseStatus);
        $this->view->display($data, $method);
    }

    /**
     * 交互数据对齐
     *
     * @param mixed $data
     * @return ResponseData
     * @throws CoreException
     */
    protected function getResponseData($data): ResponseData
    {
        if ($data instanceof ResponseData) {
            $responseData = $data;
        } else {
            $responseData = ResponseData::builder();
            if (is_numeric($data)) {
                $responseData->setStatus($data);
            } elseif (is_array($data)) {
                $responseData->updateInfoProperty($data);
                if (!empty($data)) {
                    $responseData->setData($data);
                }
            } elseif (is_object($data)) {
                if (false === ($jsonData = json_encode($data))) {
                    throw new CoreException('Unsupported data types!');
                }

                $data = json_decode($jsonData, true);
                if (!is_array($data)) {
                    throw new CoreException('Unsupported data types!');
                }

                $responseData->updateInfoProperty($data);
                if (!empty($data)) {
                    $responseData->setData($data);
                }
            } elseif (null !== $data && is_scalar($data)) {
                $responseData->setMessage((string)$data);
            }
        }

        $status = $responseData->getStatus();
        if ($status != 1 && empty($responseData->getMessage())) {
            $responseData->setMessage($this->getStatusMessage($status));
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
            throw new CoreException("未知错误（{$status}）");
        }

        return $statusConfig[$status];
    }

    /**
     * 重设视图action名称
     *
     * @param string $actionName
     * @return self
     */
    function setAction(string $actionName): self
    {
        $this->view->action = $actionName;
        return $this;
    }
}
