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
    protected $custom_router_config = array();

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
        $this->addCustomRouter('get', $custom_router, $process_closure);
    }

    /**
     * POST
     *
     * @param string $custom_router
     * @param callable|Closure $process_closure
     */
    function post($custom_router, Closure $process_closure)
    {
        $this->addCustomRouter('post', $custom_router, $process_closure);
    }

    /**
     * PUT
     *
     * @param string $custom_router
     * @param callable|Closure $process_closure
     */
    function put($custom_router, Closure $process_closure)
    {
        $this->addCustomRouter('put', $custom_router, $process_closure);
    }

    /**
     * DELETE
     *
     * @param string $custom_router
     * @param callable|Closure $process_closure
     */
    function delete($custom_router, Closure $process_closure)
    {
        $this->addCustomRouter('delete', $custom_router, $process_closure);
    }

    /**
     * @see Delegate::on()
     *
     * @param string $name
     * @param Closure $f
     * @return $this
     */
    function on($name, Closure $f)
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
        if (!empty($this->custom_router_config[$this->request_type])) {
            $match_routers = array();
            $custom_router_config = $this->custom_router_config[$this->request_type];

            if (!empty($custom_router_config['high_level'])) {
                $match_routers = $custom_router_config['high_level'];
            }

            if (!empty($custom_router_config['low_level'])) {
                $match_routers += $custom_router_config['low_level'];
            }

            foreach ($match_routers as $custom_router => $router_config) {
                $params = array();
                if (true === $this->matchCustomRouter($custom_router, $router_config['params_key'], $params)) {
                    $this->response($router_config['process_closure'], $params);
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
                throw new CoreException('Not match uri');
            }
        }
    }

    /**
     * 匹配uri和自定义路由
     *
     * @param string $custom_router
     * @param array $params_keys
     * @param array $params
     * @return bool
     */
    private function matchCustomRouter($custom_router, array $params_keys = array(), array & $params = array())
    {
        $request_uri_string = $this->request_string;
        if (!$custom_router) {
            return false;
        }

        if (strcasecmp($custom_router, $request_uri_string) == 0) {
            return true;
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

            //分段解析
            $custom_router_params_token = substr($custom_router_params_token, $defined_params_pos + 8);
            $request_uri_string = substr($request_uri_string, $defined_params_pos);

            if ($custom_router_params_token) {
                //下一个标识符的位置
                $next_defined_dot_pos = strpos($request_uri_string, $custom_router_params_token[0]);
                $params_value = substr($request_uri_string, 0, $next_defined_dot_pos);
                $request_uri_string = substr($request_uri_string, $next_defined_dot_pos);
            } else {
                $params_value = $request_uri_string;
            }

            $key_name = array_shift($params_keys);
            if ($key_name && isset($this->rules[$key_name]) && !preg_match($this->rules[$key_name], $params_value)) {
                return false;
            }

            if ($key_name) {
                $params[$key_name] = $params_value;
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
        $need_params = '';
        $closure_params = array();
        $ref = new ReflectionFunction($process_closure);

        $parameters = $ref->getParameters();
        if (count($parameters) > count($params)) {
            foreach ($parameters as $r) {
                if (!isset($params[$r->name])) {
                    $need_params .= sprintf('%s ', $r->name);
                }
            }
            throw new CoreException("所需参数: {$need_params}未指定");
        }

        foreach($parameters as $p) {
            if (!isset($params[$p->name])) {
                throw new CoreException("不匹配的参数: {$p->name}");
            }

            $closure_params[$p->name] = $params[$p->name];
        }

        $rep = call_user_func_array($process_closure, $closure_params);
        if (null != $rep) {
            $this->delegate->getResponse()->display($rep);
        }
    }

    /**
     * 解析自定义路由并保存参数key
     *
     * @param string $request_type
     * @param string $custom_router
     * @param Closure $process_closure
     */
    private function addCustomRouter($request_type, $custom_router, Closure $process_closure)
    {
        if ($this->request_type === $request_type) {
            $preg_match_result = preg_match_all("/\{:(.*?)\}/", $custom_router, $params_keys);
            if ($preg_match_result) {
                $this->custom_router_config[$request_type]['low_level'][$custom_router] = array(
                    'process_closure' => $process_closure,
                    'params_key' => $params_keys[1]
                );
            } else {
                $this->custom_router_config[$request_type]['high_level'][$custom_router] = array(
                    'process_closure' => $process_closure,
                    'params_key' => array()
                );
            }
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
