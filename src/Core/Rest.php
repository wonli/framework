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
     * @var object
     */
    static $instance;

    /**
     * @var object
     */
    protected $request;

    /**
     * 配置的uri分隔符
     *
     * @var string
     */
    protected $uri_dot;

    /**
     * @var string
     */
    protected $request_string;

    /**
     * 初始化request
     * @param Delegate $delegate
     */
    private function __construct(Delegate $delegate)
    {
        $url_config = array();
        $this->request = $delegate->getRequest();

        $this->request_string = $delegate->getRouter()->getUriRequest('/', $url_config);
        $this->uri_dot = $url_config['dot'];
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
     * @param $request_url
     * @param callable|Closure $process_func
     */
    function get($request_url, Closure $process_func)
    {
        if (true !== $this->request->isGetRequest()) {
            return;
        }

        $params = array();
        if (true === $this->checkRequest($request_url, $params)) {
            $this->response($process_func, $params);
        }
    }

    /**
     * POST
     *
     * @param $request_url
     * @param callable|Closure $process_func
     */
    function post($request_url, Closure $process_func)
    {
        if (true !== $this->request->isPostRequest()) {
            return;
        }

        if (strcasecmp($request_url, $this->request_string) !== 0) {
            return;
        }

        $php_data = file_get_contents("php://input");
        parse_str($php_data, $data);
        $this->response($process_func, $data);
    }

    /**
     * PUT
     *
     * @param $request_url
     * @param callable|Closure $process_func
     */
    function put($request_url, Closure $process_func)
    {
        if (true !== $this->request->isPutRequest()) {
            return;
        }

        if (strcasecmp($request_url, $this->request_string) !== 0) {
            return;
        }

        $php_data = file_get_contents("php://input");
        parse_str($php_data, $data);
        $this->response($process_func, $data);
    }

    /**
     * PUT
     *
     * @param $request_url
     * @param callable|Closure $process_func
     */
    function delete($request_url, Closure $process_func)
    {
        if (true !== $this->request->isDeleteRequest()) {
            return;
        }

        $params = array();
        if (true === $this->checkRequest($request_url, $params)) {
            $this->response($process_func, $params);
        }
    }

    /**
     * 检查参数是否与请求字符串对应
     *
     * @param $request_url
     * @param array $params
     * @return bool
     */
    function checkRequest($request_url, & $params = array())
    {
        $params_key = $params_value = array();
        if (($request_url == '' && $this->request_string != '') || ($request_url == '/' && $this->request_string != '/')) {
            return false;
        }

        if (strcasecmp($request_url, $this->request_string) == 0) {
            return true;
        }

        $request_url_string_flag = '';
        if (false !== ($params_start = strpos($request_url, $this->uri_dot))) {
            preg_match_all("/\{:(.*?)\}/", $request_url, $p);
            if (!empty($p)) {
                $params_key = $p[1];
            }

            $request_url_string_flag = preg_replace("/\{:(.*?)\}/", '{PARAMS}', $request_url);
        }

        $url_request_selection = explode($this->uri_dot, $this->request_string);
        $set_selection = explode($this->uri_dot, $request_url_string_flag);
        if (count($url_request_selection) !== count($set_selection)) {
            return false;
        }

        if ($request_url_string_flag) {
            foreach (explode($this->uri_dot, $request_url_string_flag) as $p => $s) {
                if ($s == '{PARAMS}') {
                    $params_value[] = $url_request_selection[$p];
                    continue;
                }

                if (strcasecmp($s, $url_request_selection[$p]) !== 0) {
                    return false;
                }
            }
        } else {
            return false;
        }

        foreach ($params_key as $position => $key_name) {
            if (isset($params_value[$position])) {
                $params[$key_name] = $params_value[$position];
            } else {
                $params[$key_name] = null;
            }
        }

        return true;
    }

    /**
     * 输出结果
     *
     * @param Closure $process_func
     * @param array $params
     * @throws CoreException
     */
    function response(Closure $process_func, $params)
    {
        $ref = new ReflectionFunction($process_func);
        if (count($ref->getParameters()) > count($params)) {
            $need_params = '';
            foreach ($ref->getParameters() as $r) {
                if (!isset($params[$r->name])) {
                    $need_params .= sprintf('$%s,', $r->name);
                }
            }
            throw new CoreException(sprintf('该方法所需参数: %s 未指定', rtrim($need_params, ',')));
        }

        if (!is_array($params)) {
            $params = array($params);
        }

        $rep = call_user_func_array($process_func, $params);
        if (null != $rep) {
            Response::getInstance()->display($rep);
        }
    }
}
