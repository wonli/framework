<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Runtime;

use Cross\Http\Request;

/**
 * Class RequestMapping
 * @package Cross\Core
 */
class RequestMapping
{
    /**
     * @var array
     */
    protected $rules = [];

    /**
     * @var array
     */
    protected $mapping = [];

    /**
     * @var string
     */
    protected $matchString;

    /**
     * @var self
     */
    static protected $instance;

    /**
     * RequestMapping constructor.
     */
    private function __construct()
    {

    }

    /**
     * @return static
     */
    static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $request
     * @param mixed $handle
     * @param array $params
     * @return bool
     */
    function match(string $request, &$handle = null, &$params = []): bool
    {
        $match = false;
        $this->matchString = $request;
        $routers = $this->mapping[Request::getInstance()->getRequestType()];
        if (!empty($routers['high']) && isset($routers['high'][$request])) {
            $match = true;
            $handle = $routers['high'][$request];
        } elseif (!empty($routers['current'])) {
            $match = $this->matchProcess($routers['current'], $handle, $params);
        } elseif (!empty($routers['global'])) {
            $match = $this->matchProcess($routers['global'], $handle, $params);
        }

        return $match;
    }

    /**
     * 设置验证规则
     *
     * @param array $rules
     */
    function setRules(array $rules): void
    {
        $this->rules = $rules;
    }

    /**
     * 添加验证规则
     *
     * @param string $params
     * @param string $pattern
     * @return static
     */
    function addRule($params, $pattern): self
    {
        $this->rules[$params] = $pattern;
        return $this;
    }

    /**
     * 添加路由（按当前请求类型分组，等同于Any）
     *
     * @param string $router
     * @param string $handler
     * @return bool
     */
    static function addRouter($router, $handler = null): bool
    {
        return self::getInstance()->addToMapping(Request::getInstance()->getRequestType(), $router, $handler);
    }

    /**
     * 添加分组路由
     *
     * @param string $requestType
     * @param string $router
     * @param mixed $handler
     * @return bool
     */
    function addGroupRouter(string $requestType, string $router, $handler = null): bool
    {
        return $this->addToMapping($requestType, $router, $handler);
    }

    /**
     * 循环匹配(参数多的优先)
     *
     * @param array $routers
     * @param mixed $handle
     * @param array $params
     * @return bool
     */
    private function matchProcess(array $routers, &$handle, array &$params): bool
    {
        uasort($routers, function ($a, $b) {
            return $a['params_count'] < $b['params_count'];
        });

        foreach ($routers as $router => $router_config) {
            $params = [];
            if (true === $this->matchCustomRouter($router, $router_config['params_key'], $params)) {
                $handle = $router_config['process_closure'];
                return true;
            }
        }

        return false;
    }

    /**
     * 匹配uri和自定义路由
     *
     * @param string $customRouter
     * @param array $paramsKeys
     * @param array $params
     * @return bool
     */
    private function matchCustomRouter(string $customRouter, array $paramsKeys = [], array &$params = []): bool
    {
        $matchString = $this->matchString;
        $customRouterParamsToken = preg_replace("#\{:(.*?)\}#", '{PARAMS}', $customRouter);
        while (strlen($customRouterParamsToken) > 0) {
            $defined_params_pos = strpos($customRouterParamsToken, '{PARAMS}');
            if ($defined_params_pos) {
                $compare_ret = substr_compare($customRouterParamsToken, $matchString, 0, $defined_params_pos);
            } else {
                $compare_ret = strcmp($customRouterParamsToken, $matchString);
            }

            if ($compare_ret !== 0) {
                return false;
            }

            //分段解析
            $customRouterParamsToken = substr($customRouterParamsToken, $defined_params_pos + 8);
            $matchString = substr($matchString, $defined_params_pos);

            if ($customRouterParamsToken) {
                //下一个标识符的位置
                $next_defined_dot_pos = strpos($matchString, $customRouterParamsToken[0]);
                $paramValue = substr($matchString, 0, $next_defined_dot_pos);
                $matchString = substr($matchString, $next_defined_dot_pos);
            } else {
                $paramValue = $matchString;
            }

            $keyName = array_shift($paramsKeys);
            if ($keyName && isset($this->rules[$keyName]) && !preg_match($this->rules[$keyName], $paramValue)) {
                return false;
            }

            if ($keyName) {
                $params[$keyName] = $paramValue;
            }
        }

        return true;
    }

    /**
     * 构造mapping
     *
     * @param string $groupKey 分组名称
     * @param string $customRouter
     * @param null $handler
     * @return bool
     */
    private function addToMapping(string $groupKey, string $customRouter, $handler = null): bool
    {
        $customRouter = trim($customRouter);
        $isLowLevelRouter = preg_match_all("#(.*?)(?:(?:\[(.))|)\{:(.*?)\}(?:\]|)#", $customRouter, $matches);
        if ($isLowLevelRouter) {
            $level = 'current';
            $prefix_string_length = strlen($matches[1][0]);
            if ($prefix_string_length == 1) {
                $level = 'global';
            }

            $optional = $matches[2];
            if (!empty($optional[0])) {
                $ori = $matches[1][0];
                $oriLevel = 'high';
                $oriRouterHandel = $handler;

                //处理可选参数
                $paramsKey = [];
                $optionalRouters = $ori;
                foreach ($matches[3] as $n => $optionalParamsName) {
                    if ($n > 0 && !empty($matches[1][$n])) {
                        $optionalRouters .= $matches[1][$n];
                        $this->mapping[$groupKey][$level][$optionalRouters] = [
                            'process_closure' => $handler,
                            'params_count' => count($paramsKey),
                            'params_key' => $paramsKey,
                        ];
                    }

                    $paramsKey[] = $matches[3][$n];
                    $optionalRouters .= sprintf('%s{:%s}', $optional[$n], $optionalParamsName);
                    $this->mapping[$groupKey][$level][$optionalRouters] = [
                        'process_closure' => $handler,
                        'params_count' => count($paramsKey),
                        'params_key' => $paramsKey,
                    ];
                }
            } else {
                $ori = $customRouter;
                $oriLevel = $level;
                $oriRouterHandel = [
                    'process_closure' => $handler,
                    'params_count' => count($matches[3]),
                    'params_key' => $matches[3],
                ];
            }

            $this->mapping[$groupKey][$oriLevel][$ori] = $oriRouterHandel;
        } else {
            $this->mapping[$groupKey]['high'][$customRouter] = $handler;
        }

        return true;
    }

}