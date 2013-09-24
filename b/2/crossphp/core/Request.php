<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * 简单Request类
 */
class Request
{
    private $_scriptUrl;
    private $_baseUrl;
    private $_hostInfo;

    static $instance;

    private function __construct()
    {

    }

    /**
     * 实例化类
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
     * 设置scripturl
     * @return null
     */
    private function _initScriptUrl() {
        if (($scriptName = $this->_SERVER('SCRIPT_FILENAME')) == null) {
            throw new FrontException('determine the entry script URL failed!!!');
        }
        $scriptName = basename($scriptName);
        if (($_scriptName = $this->_SERVER('SCRIPT_NAME')) != null && basename($_scriptName) === $scriptName) {
            $this->_scriptUrl = $_scriptName;
        } elseif (($_scriptName = $this->_SERVER('PHP_SELF')) != null && basename($_scriptName) === $scriptName) {
            $this->_scriptUrl = $_scriptName;
        } elseif (($_scriptName = $this->_SERVER('ORIG_SCRIPT_NAME')) != null && basename($_scriptName) === $scriptName) {
            $this->_scriptUrl = $_scriptName;
        } elseif (($pos = strpos($this->_SERVER('PHP_SELF'), '/' . $scriptName)) !== false) {
            $this->_scriptUrl = substr($this->_SERVER('SCRIPT_NAME'), 0, $pos) . '/' . $scriptName;
        } elseif (($_documentRoot = $this->_SERVER('DOCUMENT_ROOT')) != null && ($_scriptName = $this->_SERVER(
            'SCRIPT_FILENAME')) != null && strpos($_scriptName, $_documentRoot) === 0) {
            $this->_scriptUrl = str_replace('\\', '/', str_replace($_documentRoot, '', $_scriptName));
        } else
            throw new FrontException('determine the entry script URL failed!!');
    }

    /**
     * 取得$_SERVER全局变量的值
     * @param string $name $_SERVER的名称
     */
    private function _SERVER($name)
    {
        return isset($_SERVER[$name]) ? $_SERVER[$name] : '';
    }

    /**
     * 取得script的URL
     * @return null
     */
    public function getScriptUrl() {
        if (!$this->_scriptUrl) $this->_initScriptUrl();
        return $this->_scriptUrl;
    }

	/**
	 * 返回baseurl
	 * @param  boolean $absolute 是否返回带HOST的绝对路径
	 * @return string 当前请求的url
	 */
	public function getBaseUrl($absolute = false) {
		if ($this->_baseUrl === null) $this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/.');
		return $absolute ? $this->getHostInfo() . $this->_baseUrl : $this->_baseUrl;
	}

	/**
	 * 取得Host信息
	 * @return null
	 */
	public function getHostInfo() {
        if(!$this->_hostInfo) $this->_initHostInfo();
		return $this->_hostInfo;
	}

    /**
     * 设置Host信息
     * @return null
     */
	private function _initHostInfo() {
		$http = $this->_SERVER("HTTPS") == 'on'?'https':'http';

		if (($httpHost = $this->_SERVER('HTTP_HOST')) != null)
			$this->_hostInfo = $http . '://' . $httpHost;
		elseif (($httpHost = $this->_SERVER('SERVER_NAME')) != null) {
			$this->_hostInfo = $http . '://' . $httpHost;
			if (($port = $this->getServerPort()) != null) $this->_hostInfo .= ':' . $port;
		} else
			throw new FrontException('determine the entry script URL failed!!');
	}

    /**
     * 取得服务器端口
     * @return int 当前服务器端口号
     */
	public function getServerPort() {
        $_default = $this->_SERVER("HTTPS") == 'on'?443:80;
		return $_default;
	}

    /**
     * 当前scriptfile的路径
     * @return string
     */
    public function getScriptFilePath()
    {
        if (($scriptName = $this->_SERVER('SCRIPT_FILENAME')) == null) {
            throw new FrontException('determine the entry script URL failed!!!');
        }
        return realpath(dirname($scriptName));
    }

	public function getUserHost()
	{
		return $this->_SERVER('REMOTE_HOST');
	}

	public function getQueryString()
	{
		return $this->_SERVER('QUERY_STRING');
	}

	public function getIsPutRequest()
	{
		return ( $this->_SERVER('REQUEST_METHOD') && !strcasecmp($this->_SERVER('REQUEST_METHOD'),'PUT') ) || $this->getIsPutViaPostRequest();
	}

	protected function getIsPutViaPostRequest()
	{
		return isset($_POST['_method']) && !strcasecmp($_POST['_method'],'PUT');
	}

	public function getIsAjaxRequest()
	{
		return $this->_SERVER('HTTP_X_REQUESTED_WITH')==='XMLHttpRequest';
	}

	public function getIsFlashRequest()
	{
		return  stripos($this->_SERVER('HTTP_USER_AGENT'),'Shockwave')!==false || stripos($this->_SERVER('HTTP_USER_AGENT'),'Flash')!==false;
	}

	public function getUrlReferrer()
	{
		return $this->_SERVER('HTTP_REFERER');
	}

	public function getUserAgent()
	{
		return $this->_SERVER('HTTP_USER_AGENT');
	}

	public function getUserHostAddress()
	{
		return $this->_SERVER('REMOTE_ADDR') !== ''?$this->_SERVER('REMOTE_ADDR'):'127.0.0.1';
	}

	public function getAcceptTypes()
	{
		return $this->_SERVER('HTTP_ACCEPT');
	}
}

