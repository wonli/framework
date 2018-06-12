<?php
/**
 * Cross - a micro PHP 5 framework
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
    private $baseUrl;
    private $hostInfo;
    private $scriptUrl;
    private static $instance;

    /**
     * 实例化类
     *
     * @return Request
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * script url
     *
     * @return mixed
     * @throws FrontException
     */
    function getScriptUrl()
    {
        if (!$this->scriptUrl) {
            $this->initScriptUrl();
        }

        return $this->scriptUrl;
    }

    /**
     * 设置基础路径
     *
     * @param  string $url 设置基础路径
     */
    function setBaseUrl($url)
    {
        $this->baseUrl = $url;
    }

    /**
     * 返回当前URL绝对路径
     *
     * @param  boolean $absolute 是否返回带HOST的绝对路径
     * @return string 当前请求的url
     * @throws FrontException
     */
    function getBaseUrl($absolute = false)
    {
        if ($this->baseUrl === null) {
            $this->baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/.');
        }
        return $absolute ? $this->getHostInfo() . $this->baseUrl : $this->baseUrl;
    }

    /**
     * 当前执行的脚本名
     *
     * @return string
     */
    function getIndexName()
    {
        return basename($this->getScriptName());
    }

    /**
     * get host
     *
     * @param bool $without_protocol
     * @return mixed
     */
    function getHostInfo($without_protocol = false)
    {
        if (!$this->hostInfo) {
            $this->initHostInfo($without_protocol);
        }

        return $this->hostInfo;
    }

    /**
     * 获取当前页面URL
     *
     * @param bool $absolute
     * @return string
     */
    function getCurrentUrl($absolute = true)
    {
        if ($absolute) {
            return $this->getHostInfo() . $this->SERVER('REQUEST_URI');
        }

        return $this->SERVER('REQUEST_URI');
    }

    /**
     * 取得服务器端口
     *
     * @return int 当前服务器端口号
     */
    function getServerPort()
    {
        return $this->SERVER('SERVER_PORT');
    }

    /**
     * 当前scriptFile的路径
     *
     * @throws FrontException
     * @return string
     */
    function getScriptFilePath()
    {
        if (($scriptName = $this->SERVER('SCRIPT_FILENAME')) == null) {
            throw new FrontException('determine the entry script URL failed!!!');
        }

        return dirname($scriptName);
    }

    /**
     * @return string
     */
    function getUserHost()
    {
        return $this->SERVER('REMOTE_HOST');
    }

    /**
     * @return string
     */
    function getRequestURI()
    {
        return $this->SERVER('REQUEST_URI');
    }

    /**
     * @return string
     */
    function getRequestType()
    {
        return $this->SERVER('REQUEST_METHOD');
    }

    /**
     * @return string
     */
    function getPathInfo()
    {
        return $this->SERVER('PATH_INFO');
    }

    /**
     * @return string
     */
    function getQueryString()
    {
        return $this->SERVER('QUERY_STRING');
    }

    /**
     * @return string
     */
    function getScriptName()
    {
        return $this->SERVER('SCRIPT_NAME');
    }

    /**
     * HTTP_REFERER;
     *
     * @return string
     */
    function getUrlReferrer()
    {
        return $this->SERVER('HTTP_REFERER');
    }

    /**
     * @return string userAgent
     */
    function getUserAgent()
    {
        return $this->SERVER('HTTP_USER_AGENT');
    }

    /**
     * @return string ACCEPT TYPE
     */
    function getAcceptTypes()
    {
        return $this->SERVER('HTTP_ACCEPT');
    }

    /**
     * 是否是PUT请求
     *
     * @return bool
     */
    function isPutRequest()
    {
        return ($this->SERVER('REQUEST_METHOD')
                && !strcasecmp($this->SERVER('REQUEST_METHOD'), 'PUT')) || $this->isPutViaPostRequest();
    }

    /**
     * 判断一个链接是否为post请求
     *
     * @return boolean
     */
    function isPostRequest()
    {
        return $this->SERVER('REQUEST_METHOD') && !strcasecmp($this->SERVER('REQUEST_METHOD'), 'POST');
    }

    /**
     * 判断请求类型是否为get
     *
     * @return bool
     */
    function isGetRequest()
    {
        return $this->SERVER('REQUEST_METHOD') && !strcasecmp($this->SERVER('REQUEST_METHOD'), 'GET');
    }

    /**
     * 判断请求类型是否为delete
     *
     * @return bool
     */
    function isDeleteRequest()
    {
        return $this->SERVER('REQUEST_METHOD') && !strcasecmp($this->SERVER('REQUEST_METHOD'), 'DELETE');
    }

    /**
     * 是否是ajax请求
     *
     * @return bool
     */
    function isAjaxRequest()
    {
        return 0 === strcasecmp($this->SERVER('HTTP_X_REQUESTED_WITH'), 'XMLHttpRequest');
    }

    /**
     * 是否是flash请求
     *
     * @return bool
     */
    function isFlashRequest()
    {
        return stripos($this->SERVER('HTTP_USER_AGENT'), 'Shockwave') !== false
            || stripos($this->SERVER('HTTP_USER_AGENT'), 'Flash') !== false;
    }

    /**
     * 获取客户端IP地址
     *
     * @param array $env_keys
     * @return string userIP
     */
    function getClientIPAddress(array $env_keys = array())
    {
        static $ip = null;
        if (null === $ip) {
            if (empty($env_keys)) {
                $env_keys = array(
                    'HTTP_CLIENT_IP',
                    'HTTP_CF_CONNECTING_IP',
                    'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
                    'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED',
                    'REMOTE_ADDR'
                );
            }

            $ip = '0.0.0.0';
            foreach ($env_keys as $env) {
                $env_info = $this->SERVER($env);
                if (!empty($env_info) && 0 !== strcasecmp($env_info, 'unknown')) {
                    $ips = explode(',', $env_info);
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
     * 取得$_SERVER全局变量的值
     *
     * @param string $name $_SERVER的名称
     * @return string
     */
    function SERVER($name)
    {
        return isset($_SERVER[$name]) ? $_SERVER[$name] : '';
    }

    /**
     * 是否是通过POST的PUT请求
     *
     * @return bool
     */
    protected function isPutViaPostRequest()
    {
        return isset($_POST['_method']) && !strcasecmp($_POST['_method'], 'PUT');
    }

    /**
     * 初始化URL
     *
     * @throws FrontException
     */
    private function initScriptUrl()
    {
        if (($scriptName = $this->SERVER('SCRIPT_FILENAME')) == null) {
            throw new FrontException('determine the entry script URL failed!!!');
        }
        $scriptName = basename($scriptName);
        if (($_scriptName = $this->SERVER('SCRIPT_NAME')) != null && basename($_scriptName) === $scriptName) {
            $this->scriptUrl = $_scriptName;
        } elseif (($_scriptName = $this->SERVER('PHP_SELF')) != null && basename($_scriptName) === $scriptName) {
            $this->scriptUrl = $_scriptName;
        } elseif (($_scriptName = $this->SERVER('ORIG_SCRIPT_NAME')) != null && basename(
                $_scriptName
            ) === $scriptName
        ) {
            $this->scriptUrl = $_scriptName;
        } elseif (($pos = strpos($this->SERVER('PHP_SELF'), '/' . $scriptName)) !== false) {
            $this->scriptUrl = substr($this->SERVER('SCRIPT_NAME'), 0, $pos) . '/' . $scriptName;
        } elseif (($_documentRoot = $this->SERVER('DOCUMENT_ROOT')) != null && ($_scriptName = $this->SERVER(
                'SCRIPT_FILENAME'
            )) != null && strpos($_scriptName, $_documentRoot) === 0
        ) {
            $this->scriptUrl = str_replace('\\', '/', str_replace($_documentRoot, '', $_scriptName));
        } else {
            throw new FrontException('determine the entry script URL failed!!');
        }
    }

    /**
     * 设置Host信息
     *
     * @param bool $without_protocol 是否返回协议类型
     */
    private function initHostInfo($without_protocol = false)
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $protocol = 'http';
        if (strcasecmp($this->SERVER('HTTPS'), 'on') === 0) {
            $protocol = 'https';
        }

        if (($host = $this->SERVER('HTTP_HOST')) != null) {
            $httpHost = &$host;
        } elseif (($host = $this->SERVER('SERVER_NAME')) != null) {
            $httpHost = &$host;
            $port = $this->getServerPort();
            if (($protocol == 'http' && $port != 80) || ($protocol == 'https' && $port != 443)) {
                $httpHost .= ':' . $port;
            }
        }

        if (isset($httpHost)) {
            if ($without_protocol) {
                $this->hostInfo = '//' . $httpHost;
            } else {
                $this->hostInfo = $protocol . '://' . $httpHost;
            }
        }
    }
}

