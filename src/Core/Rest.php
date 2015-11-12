<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Core;

use Cross\Exception\CoreException;
use ReflectionFunction;
use Closure;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Rest
 * @package Cross\Core
 */
class Rest
{
    /**
     * @var Rest
     */
    private static $instance;

    /**
     * @var array
     */
    protected $rules;

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
     * @var array
     */
    protected $uri_closure_map = array();

    /**
     * 初始化request
     *
     * @param Delegate $delegate
     */
    private function __construct(Delegate $delegate)
    {
        $this->delegate = $delegate;
        $this->request = $delegate->getRequest();
        $this->request_type = $this->getRequestType();
        $this->request_string = $delegate->getRouter()->getUriRequest('/');
    }

    /**
     * 创建rest实例
     *
     * @param Delegate $delegate
     * @return Rest
     */
    static function getInstance(Delegate $delegate)
    {
        if (!self::$instance) {
            self::$instance = new Rest($delegate);
        }

        return self::$instance;
    }

    /**
     * GET
     *
     * @param string $custom_router
     * @param callable|Closure $process_closure
     */
    function get($custom_router, Closure $process_closure)
    {
        if ($this->request_type === 'get') {
            $this->uri_closure_map['get'][$custom_router] = $process_closure;
        }
    }

    /**
     * POST
     *
     * @param string $custom_router
     * @param callable|Closure $process_closure
     */
    function post($custom_router, Closure $process_closure)
    {
        if ($this->request_type === 'post') {
            $this->uri_closure_map['post'][$custom_router] = $process_closure;
        }
    }

    /**
     * PUT
     *
     * @param string $custom_router
     * @param callable|Closure $process_closure
     */
    function put($custom_router, Closure $process_closure)
    {
        if ($this->request_type === 'put') {
            $this->uri_closure_map['put'][$custom_router] = $process_closure;
        }
    }

    /**
     * DELETE
     *
     * @param string $custom_router
     * @param callable|Closure $process_closure
     */
    function delete($custom_router, Closure $process_closure)
    {
        if ($this->request_type === 'delete') {
            $this->uri_closure_map['delete'][$custom_router] = $process_closure;
        }
    }

    /**
     * @see Delegate::on()
     *
     * @param string $name
     * @param Closure $f
     * @return $this
     */
    function on($name, $f)
    {
        $this->delegate->on($name, $f);
    }

    /**
     * 参数正则验证规则
     *
     * @param array $rules
     */
    function rules(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * 处理请求
     *
     * @throws CoreException
     */
    function run()
    {
        $match = false;
        if (!empty($this->uri_closure_map[$this->request_type])) {
            foreach ($this->uri_closure_map[$this->request_type] as $custom_router => $process_closure) {
                $params = array();
                if (true === $this->matchCustomRouter($custom_router, $params)) {
                    $this->response($process_closure, $params);
                    $match = true;
                    break;
                }
            }
        }

        if (false === $match) {
            $closure_container = $this->delegate->getClosureContainer();
            if ($closure_container->isRegister('mismatching')) {
                $closure_container->run('mismatching');
            } else {
                throw new CoreException('Not Match Uri');
            }
        }
    }

    /**
     * 匹配uri和自定义路由
     *
     * @param string $custom_router
     * @param array $params
     * @return bool
     */
    private function matchCustomRouter($custom_router, & $params = array())
    {
        $params_key = $params_value = array();
        $request_uri_string = $this->request_string;

        if (!$custom_router) {
            return false;
        }

        if (strcasecmp($custom_router, $request_uri_string) == 0) {
            return true;
        }

        preg_match_all("/\{:(.*?)\}/", $custom_router, $p);
        if (!empty($p)) {
            $params_key = $p[1];
        }

        $custom_router_params_token = preg_replace("/\{:(.*?)\}/", '{PARAMS}', $custom_router);
        while (strlen($custom_router_params_token) > 0) {
            $defined_params_pos = strpos($custom_router_params_token, '{PARAMS}');
            if ($defined_params_pos) {
                $compare_ret = substr_compare($custom_router_params_token, $request_uri_string, 0, $defined_params_pos, true);
            } else {
                $compare_ret = strcasecmp($custom_router_params_token, $request_uri_string);
            }

            if ($compare_ret !== 0) {
                return false;
            }

            //去掉已经解析的部分
            $custom_router_params_token = substr($custom_router_params_token, $defined_params_pos + 8);
            $request_uri_string = substr($request_uri_string, $defined_params_pos);

            if ($custom_router_params_token) {
                //下一个标识符的位置
                $next_defined_dot_pos = strpos($request_uri_string, $custom_router_params_token[0]);

                $params_value[] = substr($request_uri_string, 0, $next_defined_dot_pos);
                $request_uri_string = substr($request_uri_string, $next_defined_dot_pos);
            } else {
                $params_value[] = $request_uri_string;
            }
        }

        foreach ($params_key as $position => $key_name) {
            if (isset($params_value[$position])) {
                $val = $params_value[$position];
                if (isset($this->rules[$key_name]) && !preg_match($this->rules[$key_name], $val)) {
                    return false;
                }

                $params[$key_name] = $val;
            } else {
                $params[$key_name] = null;
            }
        }

        return true;
    }

    /**
     * 输出结果
     *
     * @param Closure $process_closure
     * @param array $params
     * @throws CoreException
     */
    private function response(Closure $process_closure, $params)
    {
        $ref = new ReflectionFunction($process_closure);
        if (count($ref->getParameters()) > count($params)) {
            $need_params = '';
            foreach ($ref->getParameters() as $r) {
                if (!isset($params[$r->name])) {
                    $need_params .= sprintf('$%s ', $r->name);
                }
            }
            throw new CoreException(sprintf('所需参数: %s 未指定', $need_params));
        }

        if (!is_array($params)) {
            $params = array($params);
        }

        $rep = call_user_func_array($process_closure, $params);
        if (null != $rep) {
            Response::getInstance()->display($rep);
        }
    }

    /**
     * 获取当前请求类型
     *
     * @return string
     */
    private function getRequestType()
    {
        $request_type = 'get';
        if ($this->request->isPostRequest()) {
            $request_type = 'post';
        } elseif ($this->request->isPutRequest()) {
            $request_type = 'put';
        } elseif ($this->request->isDeleteRequest()) {
            $request_type = 'delete';
        }

        return $request_type;
    }
}
