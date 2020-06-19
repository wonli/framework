<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Core;

use Cross\Exception\CoreException;
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
    protected $request;

    /**
     * @var Delegate
     */
    protected $delegate;

    /**
     * @var string
     */
    protected $request_type;

    /**
     * @var string
     */
    protected $request_string;

    /**
     * @var Rest
     */
    private static $instance;

    /**
     * 初始化request
     *
     * @param Delegate $delegate
     */
    private function __construct(Delegate &$delegate)
    {
        $this->delegate = $delegate;
        $this->request = $delegate->getRequest();
        $this->request_type = strtoupper($this->request->getRequestType());
        $this->request_string = $delegate->getRouter()->getUriRequest('/', $useless, false);
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
     */
    function any(string $customRouter, $handle): void
    {
        $this->addCustomRouter($this->request_type, $customRouter, $handle);
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
     * @throws CoreException
     */
    function run(): void
    {
        $params = [];
        $match = $this->delegate->getRequestMapping()->match($this->request_string, $handle, $params);
        if ($match && $handle instanceof Closure) {
            $this->response($handle, $params);
        } elseif ($match && is_string($handle)) {
            $this->delegate->get($handle, $params);
        } else {
            $closure_container = $this->delegate->getClosureContainer();
            if ($closure_container->has('mismatching')) {
                $closure_container->run('mismatching');
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
            $closure_params = [];
            $parameters = $ref->getParameters();
            if (!empty($parameters)) {
                foreach ($parameters as $p) {
                    if (!isset($params[$p->name]) && !$p->isOptional()) {
                        throw new CoreException("未指定的参数: {$p->name}");
                    }

                    $closure_params[$p->name] = $params[$p->name] ?? $p->getDefaultValue();
                }
            }

            $content = call_user_func_array($handle, $closure_params);
            if (null != $content) {
                $this->delegate->getResponse()->send($content);
            }
        } catch (Exception $e) {
            throw new CoreException('Reflection ' . $e->getMessage());
        }
    }

    /**
     * 解析自定义路由并保存参数key
     *
     * @param string $requestType
     * @param string $customRouter
     * @param mixed $handle
     */
    private function addCustomRouter(string $requestType, string $customRouter, $handle): void
    {
        $this->delegate->getRequestMapping()->addGroupRouter($requestType, $customRouter, $handle);
    }
}
