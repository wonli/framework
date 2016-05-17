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
    private $indexName;
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
     * 返回当前脚本文件名
     *
     * @return string 当前请求的脚本名
     */
    public function getScriptName()
    {
        $s = explode('/', $this->_SERVER('SCRIPT_NAME'));
        return end($s);
    }

    /**
     * 当前执行的脚本名
     *
     * @return string
     */
    public function getIndexName()
    {
        if ($this->indexName === null) {
            return $this->getScriptName();
        }
        return $this->indexName;
    }

    /**
     * @param $index_name
     */
    public function setIndexName($index_name)
    {
        $this->indexName = $index_name;
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

        $http = $this->_SERVER('HTTPS') == 'on' ? 'https' : 'http';
        if (($httpHost = $this->_SERVER('HTTP_HOST')) != null) {
            $this->hostInfo = $http . '://' . $httpHost;
        } elseif (($httpHost = $this->_SERVER('SERVER_NAME')) != null) {
            $this->hostInfo = $http . '://' . $httpHost;
            if (($port = $this->getServerPort()) != null) {
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
        return $this->_SERVER('HTTPS') == 'on' ? 443 : 80;
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
     * 按type返回uri请求
     *
     * @param string $type
     * @param bool $fix_query_string rewrite状态下,如果request_uri包含问号要进行特殊处理
     * @return string
     */
    public function getUriRequest($type = 'QUERY_STRING', $fix_query_string = false)
    {
        switch ($type) {
            case 'QUERY_STRING':
                $request_uri = $this->_SERVER('REQUEST_URI');
                $query_string = $this->_SERVER('QUERY_STRING');

                array_shift($_GET);
                if ($fix_query_string && $request_uri && false !== strpos($request_uri, '?')) {
                    list(, $get_string) = explode('?', $request_uri);

                    parse_str($get_string, $addition_get_params);
                    $_GET += $addition_get_params;
                    if ($this->isPostRequest()) {
                        $_POST += $addition_get_params;
                    }

                    if ($get_string == $query_string) {
                        return '';
                    }
                }

                return $query_string;
            case 'PATH_INFO':
                return $this->_SERVER('PATH_INFO');

            default:
                return '';
        }
    }

    /**
     * @return string
     */
    public function getQueryString()
    {
        return $this->_SERVER('QUERY_STRING');
    }

    /**
     * 是否是PUT请求
     *
     * @return bool
     */
    public function isPutRequest()
    {
        return ($this->_SERVER('REQUEST_METHOD') && !strcasecmp(
                $this->_SERVER('REQUEST_METHOD'),
                'PUT'
            )) || $this->isPutViaPostRequest();
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
     * @return string userIP
     */
    public function getUserHostAddress()
    {
        $ip = null;
        $remote_address = $this->_SERVER('REMOTE_ADDR');
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (!empty($remote_address) && strcasecmp($remote_address, 'unknown')) {
            $ip = $remote_address;
        }

        $ip = (false !== ip2long($ip)) ? $ip : '0.0.0.0';
        return $ip;
    }

    /**
     * @return string ACCEPT TYPE
     */
    public function getAcceptTypes()
    {
        return $this->_SERVER('HTTP_ACCEPT');
    }
}

