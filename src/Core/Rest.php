<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Core;

use Cross\Exception\CoreException;
use Cross\Exception\FrontException;
use Cross\Interactive\DataFilter;
use Cross\Http\Request;

use ReflectionFunction;
use Exception;
use Closure;

/**
 * @author wonli <wonli@live.com>
 * Class Rest
 * @package Cross\Core
 */
class Rest
{
    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @var Delegate
     */
    protected Delegate $delegate;

    /**
     * @var string
     */
    protected string $requestType;

    /**
     * @var string
     */
    protected string $requestString;

    /**
     * 匹配失败时是否兼容MVC模式
     *
     * @var bool
     */
    protected bool $compatibleModel = false;

    /**
     * @var Rest|null
     */
    private static ?Rest $instance = null;

    /**
     * 初始化request
     *
     * @param Delegate $delegate
     */
    private function __construct(Delegate &$delegate)
    {
        $this->delegate = $delegate;
        $this->request = $delegate->getRequest();
        $this->requestType = strtoupper($this->request->getRequestType());
        $this->requestString = $delegate->getRouter()->getUriRequest('/', $useless, false);
    }

    /**
     * 创建rest实例
     *
     * @param Delegate $delegate
     * @return Rest
     */
    static function getInstance(Delegate &$delegate): self
    {
        if (!self::$instance) {
            self::$instance = new Rest($delegate);
        }

        return self::$instance;
    }

    /**
     * GET
     *
     * @param string $customRouter
     * @param mixed $handle
     * @throws CoreException
     */
    function get(string $customRouter, $handle): void
    {
        $this->addCustomRouter('GET', $customRouter, $handle);
    }

    /**
     * POST
     *
     * @param string $customRouter
     * @param mixed $handle
     * @throws CoreException
     */
    function post(string $customRouter, $handle): void
    {
        $this->addCustomRouter('POST', $customRouter, $handle);
    }

    /**
     * PUT
     *
     * @param string $customRouter
     * @param mixed $handle
     * @throws CoreException
     */
    function put(string $customRouter, $handle): void
    {
        $this->addCustomRouter('PUT', $customRouter, $handle);
    }

    /**
     * PATCH
     *
     * @param string $customRouter
     * @param mixed $handle
     * @throws CoreException
     */
    function patch(string $customRouter, $handle): void
    {
        $this->addCustomRouter('PATCH', $customRouter, $handle);
    }

    /**
     * OPTIONS
     *
     * @param string $customRouter
     * @param mixed $handle
     * @throws CoreException
     */
    function options(string $customRouter, $handle): void
    {
        $this->addCustomRouter('OPTIONS', $customRouter, $handle);
    }

    /**
     * DELETE
     *
     * @param string $customRouter
     * @param mixed $handle
     * @throws CoreException
     */
    function delete(string $customRouter, $handle): void
    {
        $this->addCustomRouter('DELETE', $customRouter, $handle);
    }

    /**
     * HEAD
     *
     * @param string $customRouter
     * @param mixed $handle
     * @throws CoreException
     */
    function head(string $customRouter, $handle): void
    {
        $this->addCustomRouter('HEAD', $customRouter, $handle);
    }

    /**
     * Any
     *
     * @param string $customRouter
     * @param mixed $handle
     * @throws CoreException
     */
    function any(string $customRouter, $handle): void
    {
        $this->addCustomRouter($this->requestType, $customRouter, $handle);
    }

    /**
     * on
     *
     * @param string $name
     * @param Closure $f
     * @return Delegate
     * @see Delegate::on()
     */
    function on(string $name, Closure $f): Delegate
    {
        return $this->delegate->on($name, $f);
    }

    /**
     * 匹配失败后是否兼容MVC模式
     *
     * @param bool $compatible
     */
    function compatibleModel(bool $compatible = true): void
    {
        $this->compatibleModel = $compatible;
    }

    /**
     * 参数正则验证规则
     *
     * @param array $rules
     */
    function rules(array $rules): void
    {
        $this->delegate->getRequestMapping()->setRules($rules);
    }

    /**
     * 处理请求
     *
     * @throws CoreException|FrontException
     */
    function run(): void
    {
        $params = [];
        $match = $this->delegate->getRequestMapping()->match($this->requestString, $handle, $params);
        if ($match && $handle instanceof Closure) {
            $this->response($handle, $params);
        } elseif ($match && is_string($handle)) {
            $this->delegate->get($handle, $params);
        } elseif ($this->compatibleModel) {
            $this->delegate->run();
        } else {
            $closureContainer = $this->delegate->getClosureContainer();
            if ($closureContainer->has('mismatching')) {
                $closureContainer->run('mismatching');
            } else {
                throw new CoreException('Not match uri');
            }
        }
    }

    /**
     * 输出结果
     *
     * @param Closure $handle
     * @param array $params
     * @throws CoreException
     */
    private function response(Closure $handle, array $params = []): void
    {
        try {
            $ref = new ReflectionFunction($handle);
            $parameters = $ref->getParameters();

            $closureParams = [];
            if (!empty($parameters)) {
                foreach ($parameters as $p) {
                    if (!isset($params[$p->name]) && !$p->isOptional()) {
                        throw new CoreException("Callback closure need param: {$p->name}");
                    }

                    $closureParams[$p->name] = new DataFilter($params[$p->name] ?? $p->getDefaultValue());
                }
            }

            $content = call_user_func_array($handle, $closureParams);
            if (null != $content) {
                $this->delegate->getResponse()->setRawContent($content)->send();
            }
        } catch (Exception $e) {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * 解析自定义路由并保存参数key
     *
     * @param string $requestType
     * @param string $customRouter
     * @param mixed $handle
     * @throws CoreException
     */
    private function addCustomRouter(string $requestType, string $customRouter, $handle): void
    {
        $this->delegate->getRequestMapping()->addRequestRouter($requestType, $customRouter, $handle);
    }
}
