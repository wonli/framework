<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Http;

use Cross\Exception\FrontException;

/**
 * @author wonli <wonli@live.com>
 * Class Request
 * @package Cross\Core
 */
class Request
{
    protected $getData = [];
    protected $postData = [];
    protected $fileData = [];
    protected $serverData = [];
    protected $requestData = [];

    /**
     * @var string
     */
    protected $scriptUrl;

    /**
     * @var self
     */
    protected static $instance;

    /**
     * Request constructor.
     */
    private function __construct()
    {
        $this->getData = &$_GET;
        $this->postData = &$_POST;
        $this->fileData = &$_FILES;
        $this->serverData = &$_SERVER;
        $this->requestData = &$_REQUEST;
    }

    /**
     * 单例模式
     *
     * @return Request
     */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return string
     */
    function getUserHost(): string
    {
        return $this->server('REMOTE_HOST');
    }

    /**
     * @return string
     */
    function getRequestURI(): string
    {
        return $this->server('REQUEST_URI');
    }

    /**
     * @return string
     */
    function getRequestType(): string
    {
        return $this->server('REQUEST_METHOD');
    }

    /**
     * @return string
     */
    function getPathInfo(): string
    {
        return $this->server('ORIG_PATH_INFO') ?: $this->server('PATH_INFO');
    }

    /**
     * @return string
     */
    function getQueryString(): string
    {
        return $this->server('QUERY_STRING');
    }

    /**
     * HTTP_REFERER;
     *
     * @return string
     */
    function getUrlReferrer(): string
    {
        return $this->server('HTTP_REFERER');
    }

    /**
     * @return string userAgent
     */
    function getUserAgent(): string
    {
        return $this->server('HTTP_USER_AGENT');
    }

    /**
     * @return string ACCEPT TYPE
     */
    function getAcceptTypes(): string
    {
        return $this->server('HTTP_ACCEPT');
    }


    /**
     * 判断一个链接是否为post请求
     *
     * @return boolean
     */
    function isPostRequest(): bool
    {
        return $this->isRequestMethod('post');
    }

    /**
     * 判断请求类型是否为get
     *
     * @return bool
     */
    function isGetRequest(): bool
    {
        return $this->isRequestMethod('GET');
    }

    /**
     * 是否是PUT请求
     *
     * @return bool
     */
    function isPutRequest(): bool
    {
        return $this->isRequestMethod('PUT');
    }

    /**
     * 判断请求类型是否为delete
     *
     * @return bool
     */
    function isDeleteRequest(): bool
    {
        return $this->isRequestMethod('DELETE');
    }

    /**
     * 是否是ajax请求
     *
     * @return bool
     */
    function isAjaxRequest(): bool
    {
        return 0 === strcasecmp($this->server('HTTP_X_REQUESTED_WITH'), 'XMLHttpRequest');
    }

    /**
     * 是否是flash请求
     *
     * @return bool
     */
    function isFlashRequest(): bool
    {
        return stripos($this->server('HTTP_USER_AGENT'), 'Shockwave') !== false
            || stripos($this->server('HTTP_USER_AGENT'), 'Flash') !== false;
    }

    /**
     * 取得服务器端口
     *
     * @return int 当前服务器端口号
     */
    function getServerPort(): int
    {
        return $this->server('SERVER_PORT');
    }

    /**
     * 当前脚本路径
     *
     * @return string
     * @throws FrontException
     */
    function getScriptFilePath(): string
    {
        if (($scriptName = $this->server('SCRIPT_FILENAME')) == '') {
            throw new FrontException('Determine the entry script URL failed!!!');
        }

        return dirname($scriptName);
    }

    /**
     * 判断是否使用https
     *
     * @return bool
     */
    function isSecure(): bool
    {
        $isSecure = false;
        if ('on' == $this->server('HTTPS') || 443 == $this->server('SERVER_PORT')) {
            $isSecure = true;
        } elseif ('https' == $this->server('HTTP_X_FORWARDED_PROTO') || 'on' == $this->server('HTTP_X_FORWARDED_SSL')) {
            $isSecure = true;
        }

        return $isSecure;
    }

    /**
     * 设置入口URL
     *
     * @param string $scriptUrl
     */
    function setScriptUrl(string $scriptUrl): void
    {
        $this->scriptUrl = $scriptUrl;
    }

    /**
     * 入口URL
     *
     * @return string
     * @throws FrontException
     */
    function getScriptUrl(): string
    {
        if (!$this->scriptUrl) {
            $envKeys = [
                'SCRIPT_NAME', 'PHP_SELF', 'ORIG_SCRIPT_NAME'
            ];

            foreach ($envKeys as $env) {
                $scriptUrl = $this->server($env);
                if (!empty($scriptUrl)) {
                    break;
                }
            }

            $scriptFile = $this->server('SCRIPT_FILENAME');
            if (empty($scriptUrl) || empty($scriptFile) || basename($scriptUrl) !== basename($scriptFile)) {
                throw new FrontException('Determine the entry script URL failed!!!');
            }

            $this->scriptUrl = $scriptUrl;
        }

        return $this->scriptUrl;
    }

    /**
     * 返回当前URL绝对路径
     *
     * @param boolean $absolute 是否返回带HOST的绝对路径
     * @param bool $withoutIndex
     * @return string 当前请求的url
     * @throws FrontException
     */
    function getBaseUrl(bool $absolute = false, $withoutIndex = true): string
    {
        if ($withoutIndex) {
            $baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/.');
        } else {
            $baseUrl = $this->getScriptUrl();
        }

        if ($absolute) {
            $baseUrl = $this->getHostInfo() . $baseUrl;
        }

        return $baseUrl;
    }

    /**
     * 当前执行的脚本名
     *
     * @return string
     * @throws FrontException
     */
    function getIndexName(): string
    {
        return basename($this->getScriptUrl());
    }

    /**
     * 获取HOST信息
     *
     * @param bool $withoutProtocol 是否返回协议类型
     * @return mixed
     */
    function getHostInfo(bool $withoutProtocol = false): string
    {
        $hostInfo = '';
        if (PHP_SAPI === 'cli') {
            return $hostInfo;
        }

        $protocol = 'http';
        if ($this->isSecure()) {
            $protocol = 'https';
        }

        if (($host = $this->server('HTTP_HOST')) != '') {
            $httpHost = &$host;
        } elseif (($host = $this->server('SERVER_NAME')) != '') {
            $httpHost = &$host;
            $port = $this->getServerPort();
            if (($protocol == 'http' && $port != 80) || ($protocol == 'https' && $port != 443)) {
                $httpHost .= ':' . $port;
            }
        }

        if (isset($httpHost)) {
            if ($withoutProtocol) {
                $hostInfo = '//' . $httpHost;
            } else {
                $hostInfo = $protocol . '://' . $httpHost;
            }
        }

        return $hostInfo;
    }

    /**
     * 获取当前页面URL
     *
     * @param bool $absolute
     * @return string
     */
    function getCurrentUrl(bool $absolute = true): string
    {
        $uri = $this->server('REQUEST_URI');
        if ($absolute) {
            return $this->getHostInfo() . $uri;
        }

        return $uri;
    }

    /**
     * 获取客户端IP地址
     *
     * @param array $envKeys
     * @return string
     */
    function getClientIPAddress(array $envKeys = []): string
    {
        static $ip = null;
        if (null === $ip) {
            if (empty($envKeys)) {
                $envKeys = [
                    'HTTP_CLIENT_IP',
                    'HTTP_CF_CONNECTING_IP',
                    'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
                    'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED',
                    'REMOTE_ADDR'
                ];
            }

            $ip = '0.0.0.0';
            foreach ($envKeys as $env) {
                $envInfo = $this->server($env);
                if (!empty($envInfo) && 0 !== strcasecmp($envInfo, 'unknown')) {
                    $ips = explode(',', $envInfo);
                    foreach ($ips as $ip) {
                        $ip = trim($ip);
                        if (false !== ip2long($ip)) {
                            break 2;
                        }
                    }
                }
            }
        }

        return $ip;
    }

    /**
     * HTTP环境变量
     *
     * @param string $name
     * @return string
     */
    function server(string $name): string
    {
        return $this->serverData[$name] ?? '';
    }

    /**
     * 设置环境变量
     *
     * @param array $server
     */
    function setServeData(array $server)
    {
        if (!empty($server)) {
            array_walk($server, function ($v, $k) {
                $this->serverData[strtoupper($k)] = $v;
            });
        }
    }

    /**
     * 获取环境变量
     *
     * @return array
     */
    function getServeData(): array
    {
        return $this->serverData;
    }

    /**
     * 设置HTTP请求类型
     *
     * @param string $method
     */
    function setRequestMethod(string $method): void
    {
        $this->serverData['REQUEST_METHOD'] = strtoupper($method);
    }

    /**
     * 获取HTTP请求类型
     *
     * @return string
     */
    function getRequestMethod(): string
    {
        return $this->server('REQUEST_METHOD');
    }

    /**
     * 验证请求类型
     *
     * @param string $method
     * @return bool
     */
    function isRequestMethod(string $method): bool
    {
        $requestMethod = $this->getRequestMethod();
        return $requestMethod && 0 === strcasecmp($requestMethod, $method);
    }

    /**
     * 设置URI参数
     *
     * @param array $data
     * @param bool $merge
     */
    function setGetData(array $data, bool $merge = false): void
    {
        if ($merge && is_array($this->getData)) {
            $this->getData = array_merge($this->getData, $data);
        } else {
            $this->getData = $data;
        }
    }

    /**
     * 获取URI参数
     *
     * @return array
     */
    function getGetData(): array
    {
        return $this->getData;
    }

    /**
     * 设置POST参数
     *
     * @param array $data
     * @param bool $merge
     */
    function setPostData(array $data, bool $merge = false): void
    {
        if ($merge && is_array($this->postData)) {
            $this->postData = array_merge($this->postData, $data);
        } else {
            $this->postData = $data;
        }
    }

    /**
     * 获取POST参数
     *
     * @return array
     */
    function getPostData(): array
    {
        return $this->postData;
    }

    /**
     * 设置File参数
     *
     * @param array $data
     * @param bool $merge
     */
    function setFileData(array $data, bool $merge = false): void
    {
        if ($merge && is_array($this->fileData)) {
            $this->fileData = array_merge($this->fileData, $data);
        } else {
            $this->fileData = $data;
        }
    }

    /**
     * 获取File参数
     *
     * @return array
     */
    function getFileData(): array
    {
        return $this->fileData;
    }

    /**
     * 设置Request参数
     *
     * @param array $data
     * @param bool $merge
     */
    function setRequestData(array $data, bool $merge = false): void
    {
        if ($merge && is_array($this->requestData)) {
            $this->requestData = array_merge($this->requestData, $data);
        } else {
            $this->requestData = $data;
        }
    }

    /**
     * 获取Request参数
     *
     * @return array
     */
    function getRequestData(): array
    {
        return $this->requestData;
    }
}

