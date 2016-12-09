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
 * @Auth: wonli <wonli@live.com>
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
     * 初始化URL
     *
     * @throws FrontException
     * @return null
     */
    private function initScriptUrl()
    {
        if (($scriptName = $this->_SERVER('SCRIPT_FILENAME')) == null) {
            throw new FrontException('determine the entry script URL failed!!!');
        }
        $scriptName = basename($scriptName);
        if (($_scriptName = $this->_SERVER('SCRIPT_NAME')) != null && basename($_scriptName) === $scriptName) {
            $this->scriptUrl = $_scriptName;
        } elseif (($_scriptName = $this->_SERVER('PHP_SELF')) != null && basename($_scriptName) === $scriptName) {
            $this->scriptUrl = $_scriptName;
        } elseif (($_scriptName = $this->_SERVER('ORIG_SCRIPT_NAME')) != null && basename(
                $_scriptName
            ) === $scriptName
        ) {
            $this->scriptUrl = $_scriptName;
        } elseif (($pos = strpos($this->_SERVER('PHP_SELF'), '/' . $scriptName)) !== false) {
            $this->scriptUrl = substr($this->_SERVER('SCRIPT_NAME'), 0, $pos) . '/' . $scriptName;
        } elseif (($_documentRoot = $this->_SERVER('DOCUMENT_ROOT')) != null && ($_scriptName = $this->_SERVER(
                'SCRIPT_FILENAME'
            )) != null && strpos($_scriptName, $_documentRoot) === 0
        ) {
            $this->scriptUrl = str_replace('\\', '/', str_replace($_documentRoot, '', $_scriptName));
        } else {
            throw new FrontException('determine the entry script URL failed!!');
        }
    }

    /**
     * 取得$_SERVER全局变量的值
     *
     * @param string $name $_SERVER的名称
     * @return string
     */
    private function _SERVER($name)
    {
        return isset($_SERVER[$name]) ? $_SERVER[$name] : '';
    }

    /**
     * 取得script的URL
     *
     * @return null
     */
    public function getScriptUrl()
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
    public function setBaseUrl($url)
    {
        $this->baseUrl = $url;
    }

    /**
     * 返回当前URL绝对路径
     *
     * @param  boolean $absolute 是否返回带HOST的绝对路径
     * @return string 当前请求的url
     */
    public function getBaseUrl($absolute = false)
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
    public function getIndexName()
    {
        return basename($this->getScriptName());
    }

    /**
     * 取得Host信息
     *
     * @return null
     */
    public function getHostInfo()
    {
        if (!$this->hostInfo) {
            $this->initHostInfo();
        }
        return $this->hostInfo;
    }

    /**
     * 获取当前页面URL路径
     *
     * @param bool $absolute
     * @return string
     */
    public function getCurrentUrl($absolute = true)
    {
        if ($absolute) {
            return $this->getHostInfo() . $this->_SERVER('REQUEST_URI');
        }

        return $this->_SERVER('REQUEST_URI');
    }

    /**
     * 设置Host信息
     *
     * @throws FrontException
     * @return null|string
     */
    private function initHostInfo()
    {
        if (PHP_SAPI === 'cli') {
            return '';
        }

        $protocol = 'http';
        if (strcasecmp($this->_SERVER('HTTPS'), 'on') === 0) {
            $protocol = 'https';
        }

        if (($host = $this->_SERVER('HTTP_HOST')) != null) {
            $this->hostInfo = $protocol . '://' . $host;
        } elseif (($host = $this->_SERVER('SERVER_NAME')) != null) {
            $this->hostInfo = $protocol . '://' . $host;
            $port = $this->getServerPort();
            if (($protocol == 'http' && $port != 80) || ($protocol == 'https' && $port != 443)) {
                $this->hostInfo .= ':' . $port;
            }
        } else {
            throw new FrontException('determine the entry script URL failed!!');
        }

        return '';
    }

    /**
     * 取得服务器端口
     *
     * @return int 当前服务器端口号
     */
    public function getServerPort()
    {
        return $this->_SERVER('SERVER_PORT');
    }

    /**
     * 当前scriptFile的路径
     *
     * @throws FrontException
     * @return string
     */
    public function getScriptFilePath()
    {
        if (($scriptName = $this->_SERVER('SCRIPT_FILENAME')) == null) {
            throw new FrontException('determine the entry script URL failed!!!');
        }

        return realpath(dirname($scriptName));
    }

    /**
     * @return string
     */
    public function getUserHost()
    {
        return $this->_SERVER('REMOTE_HOST');
    }

    /**
     * @return string
     */
    function getRequestURI()
    {
        return $this->_SERVER('REQUEST_URI');
    }

    /**
     * @return string
     */
    function getPathInfo()
    {
        return $this->_SERVER('PATH_INFO');
    }

    /**
     * @return string
     */
    public function getQueryString()
    {
        return $this->_SERVER('QUERY_STRING');
    }

    /**
     * @return string
     */
    public function getScriptName()
    {
        return $this->_SERVER('SCRIPT_NAME');
    }

    /**
     * HTTP_REFERER;
     *
     * @return string
     */
    public function getUrlReferrer()
    {
        return $this->_SERVER('HTTP_REFERER');
    }

    /**
     * @return string userAgent
     */
    public function getUserAgent()
    {
        return $this->_SERVER('HTTP_USER_AGENT');
    }

    /**
     * @return string ACCEPT TYPE
     */
    public function getAcceptTypes()
    {
        return $this->_SERVER('HTTP_ACCEPT');
    }

    /**
     * 是否是PUT请求
     *
     * @return bool
     */
    public function isPutRequest()
    {
        return ($this->_SERVER('REQUEST_METHOD')
            && !strcasecmp($this->_SERVER('REQUEST_METHOD'), 'PUT')) || $this->isPutViaPostRequest();
    }

    /**
     * 判断一个链接是否为post请求
     *
     * @return boolean
     */
    public function isPostRequest()
    {
        return $this->_SERVER('REQUEST_METHOD') && !strcasecmp($this->_SERVER('REQUEST_METHOD'), 'POST');
    }

    /**
     * 判断请求类型是否为get
     *
     * @return bool
     */
    public function isGetRequest()
    {
        return $this->_SERVER('REQUEST_METHOD') && !strcasecmp($this->_SERVER('REQUEST_METHOD'), 'GET');
    }

    /**
     * 判断请求类型是否为delete
     *
     * @return bool
     */
    public function isDeleteRequest()
    {
        return $this->_SERVER('REQUEST_METHOD') && !strcasecmp($this->_SERVER('REQUEST_METHOD'), 'DELETE');
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
     * 是否是ajax请求
     *
     * @return bool
     */
    public function isAjaxRequest()
    {
        return 0 === strcasecmp($this->_SERVER('HTTP_X_REQUESTED_WITH'), 'XMLHttpRequest');
    }

    /**
     * 是否是flash请求
     *
     * @return bool
     */
    public function isFlashRequest()
    {
        return stripos($this->_SERVER('HTTP_USER_AGENT'), 'Shockwave') !== false
        || stripos($this->_SERVER('HTTP_USER_AGENT'), 'Flash') !== false;
    }

    /**
     * 获取客户端IP地址
     *
     * @param array $env_keys
     * @return string userIP
     */
    public function getClientIPAddress(array $env_keys = array())
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
                $env_info = $this->_SERVER($env);
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
}

