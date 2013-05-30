<?php
set_time_limit(0);
/**
 +-------------------------------------
 * CacheRedis缓存驱动类
 * 要求安装phpredis扩展：https://github.com/owlient/phpredis
 +-------------------------------------
 */
class CacheRedis extends Cache {

    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __construct($options='') {
        if ( !extension_loaded('redis') ) {
            throw new Exception('NOT_SUPPERT : redis');
        }
        $redis = new Redis();
        if(empty($options)) {
            global $glbConfig;
            $options = $glbConfig['cache']['redis'][0];
        }
        $this->options =  $options;
        $this->handler  = new Redis;
        $this->connected = $this->handler->connect($options['host'], $options['port']);
        if($options['auth'])
        {
        	$this->handler->auth($options['auth']);
        }
        //是否保存数组
        //TODO
        //$this->setOpt(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        $this->handler->select($options['db']);
    }

    public function __call($method, $argv)
    {
    	
    	$result = null;
    	if(method_exists($this->handler, $method))
    	{
    		$result = ($argv == null)
    			? $this->handler->$method()
    			: call_user_func_array(array($this->handler, $method), $argv);
    	}
    	return $result;
    }
    
    /**
     +----------------------------------------------------------
     * 是否连接
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    private function isConnected() {
        return $this->connected;
    }

    /**
     +----------------------------------------------------------
     * 读取缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function get($name) {
        $str = $this->handler->get($name);
        if($str){
        	$str = unserialize($str);
        }
        return $str;
    }

    /**
     +----------------------------------------------------------
     * 写入缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function set($name, $value, $expire = null) {
        if(is_null($expire)) {
            $expire  =  $this->options['expire'];
        }
        $value = serialize($value);
        if(is_int($expire)) {
            $result = $this->handler->setex($name, $expire, $value);
        }else{
            $result = $this->handler->set($name, $value);
        }
        return $result;
    }

    /**
     +----------------------------------------------------------
     * 删除缓存
     *
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function rm($name) {
        return $this->handler->delete($name);
    }
    
	public function delete($name) {
        return $this->handler->delete($name);
    }
    
    /**
     +----------------------------------------------------------
     * 清除缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function clear() {
        return $this->handler->flushDB();
    }
    
    private function setOpt($name, $value) {
    	
    	$this->handler->setOption($name, $value);
    }
}