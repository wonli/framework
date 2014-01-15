<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class Request
 */
class Request
{
    /**
     * @var
     */
    private $_scriptUrl;

    /**
     * @var
     */
    private $_baseUrl;

    /**
     * @var
     */
    private $_hostInfo;

    /**
     * @var
     */
    private $_indexName;

    /**
     * @var
     */
    static $instance;

    private function __construct()
    {

    }

    /**
     * 实例化类
     *
     * @return object SimpleHttpRequest() 类的实例
     */
    public static function getInstance()
    {
        if(! self::$instance ) {
            self::$instance = new Request();
        }

        return self::$instance;
    }

    /**
     * 初始化URL
     *
     * @throws FrontException
     * @return null
     */
    private function _initScriptUrl()
    {
        if (($scriptName = $this->_SERVER('SCRIPT_FILENAME')) == null) {
            throw new FrontException('determine the entry script URL failed!!!');
        }
        $scriptName = basename($scriptName);
        if (($_scriptName = $this->_SERVER('SCRIPT_NAME')) != null && basename($_scriptName) === $scriptName)
        {
            $this->_scriptUrl = $_scriptName;
        }
        elseif (($_scriptName = $this->_SERVER('PHP_SELF')) != null && basename($_scriptName) === $scriptName)
        {
            $this->_scriptUrl = $_scriptName;
        }
        elseif (($_scriptName = $this->_SERVER('ORIG_SCRIPT_NAME')) != null && basename($_scriptName) === $scriptName)
        {
            $this->_scriptUrl = $_scriptName;
        }
        elseif (($pos = strpos($this->_SERVER('PHP_SELF'), '/' . $scriptName)) !== false)
        {
            $this->_scriptUrl = substr($this->_SERVER('SCRIPT_NAME'), 0, $pos) . '/' . $scriptName;
        } elseif (($_documentRoot = $this->_SERVER('DOCUMENT_ROOT')) != null && ($_scriptName = $this->_SERVER(
            'SCRIPT_FILENAME')) != null && strpos($_scriptName, $_documentRoot) === 0)
        {
            $this->_scriptUrl = str_replace('\\', '/', str_replace($_documentRoot, '', $_scriptName));
        }
        else
        {
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
        if (!$this->_scriptUrl) $this->_initScriptUrl();
        return $this->_scriptUrl;
    }

	/**
	 * 设置基础路径
     *
	 * @param  string $url 设置基础路径
	 */
    public function setBaseUrl($url)
    {
        $this->_baseUrl = $url;
    }

	/**
	 * 返回baseurl
     *
	 * @param  boolean $absolute 是否返回带HOST的绝对路径
	 * @return string 当前请求的url
	 */
	public function getBaseUrl($absolute = false)
    {
		if ($this->_baseUrl === null) $this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/.');
		return $absolute ? $this->getHostInfo() . $this->_baseUrl : $this->_baseUrl;
	}

	/**
	 * 返回当前脚本文件名
     *
	 * @return string 当前请求的脚本名
	 */
    public function getScriptName()
    {
        $s = explode("/", $this->_SERVER('SCRIPT_NAME'));
        return end( $s );
    }

    /**
     * 当前执行的脚本名
     *
     * @return string
     */
    public function getIndexName()
    {
        if($this->_indexName === null) return $this->getScriptName();
        return $this->_indexName;
    }

    /**
     * @param $index_name
     */
    public function setIndexName($index_name)
    {
        $this->_indexName = $index_name;
    }

	/**
	 * 取得Host信息
     *
	 * @return null
	 */
	public function getHostInfo()
    {
        if(!$this->_hostInfo) $this->_initHostInfo();
		return $this->_hostInfo;
	}

    /**
     * 设置Host信息
     *
     * @throws FrontException
     * @return null
     */
	private function _initHostInfo()
    {
		if(PHP_SAPI === 'cli')
        {
            return "cli";
        }

		$http = $this->_SERVER("HTTPS") == 'on'?'https':'http';
		if (($httpHost = $this->_SERVER('HTTP_HOST')) != null)
        {
            $this->_hostInfo = $http . '://' . $httpHost;
        }
		elseif (($httpHost = $this->_SERVER('SERVER_NAME')) != null)
        {
			$this->_hostInfo = $http . '://' . $httpHost;
			if (($port = $this->getServerPort()) != null) $this->_hostInfo .= ':' . $port;
		}
        else
        {
            throw new FrontException('determine the entry script URL failed!!');
        }
	}

    /**
     * 取得服务器端口
     *
     * @return int 当前服务器端口号
     */
	public function getServerPort()
    {
        $_default = $this->_SERVER("HTTPS") == 'on'?443:80;
		return $_default;
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
     * 按type返回url请求
     *
     * @param int $type
     * @return string
     */
    public function getUrlRequest($type = 1)
    {
        if ($type == 2) {
            return $this->_SERVER('PATH_INFO');
        } else {
            return $this->_SERVER('QUERY_STRING');
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
		return ( $this->_SERVER('REQUEST_METHOD') && !strcasecmp($this->_SERVER('REQUEST_METHOD'),'PUT') ) || $this->getIsPutViaPostRequest();
	}

    /**
     * 是否是通过POST的PUT请求
     *
     * @return bool
     */
    protected function isPutViaPostRequest()
	{
		return isset($_POST['_method']) && !strcasecmp($_POST['_method'],'PUT');
	}

    /**
     * 是否是ajax请求
     *
     * @return bool
     */
    public function isAjaxRequest()
	{
		return $this->_SERVER('HTTP_X_REQUESTED_WITH')==='XMLHttpRequest';
	}

    /**
     * 是否是flash请求
     *
     * @return bool
     */
    public function isFlashRequest()
	{
		return  stripos($this->_SERVER('HTTP_USER_AGENT'),'Shockwave')!==false || stripos($this->_SERVER('HTTP_USER_AGENT'),'Flash')!==false;
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
		return $this->_SERVER('REMOTE_ADDR') !== ''?$this->_SERVER('REMOTE_ADDR'):'127.0.0.1';
	}

    /**
     * @return string ACCEPT TYPE
     */
    public function getAcceptTypes()
	{
		return $this->_SERVER('HTTP_ACCEPT');
	}
}

