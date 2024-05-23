<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Runtime;

use Cross\Exception\CoreException;
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
    protected array $rules = [];

    /**
     * @var array
     */
    protected array $mapping = [];

    /**
     * @var string
     */
    protected string $matchString;

    /**
     * @var self|null
     */
    static protected ?RequestMapping $instance = null;

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
     * @param mixed|null $handle
     * @param array $params
     * @return bool
     */
    function match(string $request, mixed &$handle = null, array &$params = []): bool
    {
        $this->matchString = $request;
        $routers = $this->mapping[Request::getInstance()->getRequestType()] ?? [];
        if (empty($routers)) {
            return false;
        }

        $match = false;
        if (!empty($routers['high']) && isset($routers['high'][$request])) {
            $handle = $routers['high'][$request];
            if (is_array($handle)) {
                $handle = $handle['handler'];
            }

            $match = true;
        }

        if (!$match && !empty($routers['current'])) {
            $match = $this->matchProcess($routers['current'], $handle, $params);
        }

        if (!$match && !empty($routers['global'])) {
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
    function addRule(string $params, string $pattern): self
    {
        $this->rules[$params] = $pattern;
        return $this;
    }

    /**
     * 添加路由（按当前请求类型分组，等同于Any）
     *
     * @param mixed $router
     * @param mixed|null $handler
     * @return bool
     * @throws CoreException
     */
    static function addRouter(mixed $router, mixed $handler = null): bool
    {
        return self::getInstance()->addToMapping(Request::getInstance()->getRequestType(), $router, $handler);
    }

    /**
     * 添加HTTP路由
     *
     * @param string $requestType
     * @param string $router
     * @param mixed|null $handler
     * @return bool
     * @throws CoreException
     */
    function addRequestRouter(string $requestType, string $router, mixed $handler = null): bool
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
    private function matchProcess(array $routers, mixed &$handle, array &$params): bool
    {
        foreach ($routers as $r => &$rr) {
            if (isset($r[1]) && isset($this->matchString[1]) && 0 === strcasecmp($r[1], $this->matchString[1])) {
                $rr['score'] += 10000;
            }
        }

        uasort($routers, function ($a, $b) {
            return $a['score'] < $b['score'];
        });

        foreach ($routers as $router => $setting) {
            $params = [];
            if (true === $this->matchCustomRouter($router, $setting['params'], $params)) {
                $handle = $setting['handler'];
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
            $definedParamsPos = strpos($customRouterParamsToken, '{PARAMS}');
            if ($definedParamsPos) {
                $compareRet = substr_compare($customRouterParamsToken, $matchString, 0, $definedParamsPos);
            } else {
                $compareRet = strcasecmp($customRouterParamsToken, $matchString);
            }

            if ($compareRet !== 0) {
                return false;
            }

            //分段解析
            $customRouterParamsToken = substr($customRouterParamsToken, $definedParamsPos + 8);
            $matchString = substr($matchString, $definedParamsPos);

            if ($customRouterParamsToken) {
                //下一个标识符的位置
                $nextDefinedDotPos = strpos($matchString, $customRouterParamsToken[0]);
                $paramValue = substr($matchString, 0, $nextDefinedDotPos);
                $matchString = substr($matchString, $nextDefinedDotPos);
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
     * @throws CoreException
     */
    private function addToMapping(string $groupKey, string $customRouter, $handler = null): bool
    {
        $customRouter = trim($customRouter);
        $isLowLevelRouter = preg_match_all("#(.*?)(?:(?:\[(.))|)\{:(.*?)\}(?:\]|)|(?:.+)#", $customRouter, $matches);
        if ($isLowLevelRouter) {
            $level = 'current';
            $prefixStringLength = strlen($matches[1][0]);
            if ($prefixStringLength == 0) {
                $level = 'high';
            } elseif ($prefixStringLength == 1) {
                $level = 'global';
            }

            $hasOptional = false;
            $optional = $matches[2];
            foreach ($optional as $n => $op) {
                if (!empty($op)) {
                    $hasOptional = true;
                }

                if ($hasOptional && empty($op)) {
                    throw new CoreException('Request mapping syntax error!');
                }
            }

            if ($hasOptional) {
                $ori = $matches[1][0];
                $oriLevel = 'high';
                $oriRouterHandel = $handler;

                //处理可选参数
                $j = 0;
                $paramsKey = [];
                $optionalRouters = $ori;
                foreach ($matches[3] as $n => $optionalParamsName) {
                    if ($n > 0 && !empty($matches[1][$n])) {
                        $j++;
                        $optionalRouters .= $matches[1][$n];
                        $this->mapping[$groupKey][$level][$optionalRouters] = [
                            'handler' => $handler,
                            'score' => count($paramsKey) * 100 + $j,
                            'params' => $paramsKey,
                        ];
                    }

                    $j++;
                    $paramsKey[] = $matches[3][$n];
                    $optionalRouters .= sprintf('%s{:%s}', $optional[$n], $optionalParamsName);
                    $this->mapping[$groupKey][$level][$optionalRouters] = [
                        'handler' => $handler,
                        'score' => count($paramsKey) * 100 + $j,
                        'params' => $paramsKey,
                    ];
                }
            } else {
                $ori = $customRouter;
                $oriLevel = $level;
                $oriRouterHandel = [
                    'handler' => $handler,
                    'score' => count($matches[3]) * 100,
                    'params' => array_filter($matches[3]),
                ];
            }

            $this->mapping[$groupKey][$oriLevel][$ori] = $oriRouterHandel;
        } else {
            $this->mapping[$groupKey]['high'][$customRouter] = $handler;
        }

        return true;
    }

}