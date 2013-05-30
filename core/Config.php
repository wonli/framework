<?php defined('CROSSPHP_PATH')or die('Access Denied');
interface ConfigInterface
{
    function put();
    function get($config, $name=null);
    function set($config, $name=null);
}

class ConfigBase implements ConfigInterface
{
    private $sys;
    private $init;
    private $appname;
    private $userInit;
    private static $interface;

    private function __construct($appname)
    {
        $this->appname = $appname;
        if(!$this->sys) {
            $this->sys = $this->getSysSet();
        }

        if(!$this->userInit) {
            $initfile = APP_PATH_DIR.DS.$this->appname.DS.'init.php';
            $this->userInit = $this->getInitFile($initfile);
        }
    }

    public static function getInstance($appname)
    {
        if(! self::$interface) {
            self::$interface = new ConfigBase($appname);
        }
        return self::$interface;
    }

    private function getSysSet()
    {
        $_sys = array();
        $_sys["host"] = Request::getInstance()->getHostInfo();

        $_sys["base_url"] = Request::getInstance()->getBaseUrl();
        $_sys["site_url"] = $_sys["host"].$_sys["base_url"];

        $_sys["app_name"] = $this->appname;
        $_sys["app_path"] = APP_PATH_DIR.DS.$this->appname;

        $_sys["static_url"] = $_sys["site_url"].'/static/';
        $_sys["static_path"] = Request::getInstance()->getScriptFilePath().DS.'static'.DS;

        $_sys["cache_path"] = $_sys["app_path"].DS.'cache'.DS;

        return $_sys;
    }

    /**
     * 获取配置参数
     * $config为字符串的时候 获取配置数组,此时设定$name 则获取数组中指定项的值
     * $config为数组的时候 获取数组中指定的配置项,如果$name为true 则获取指定项之外的配置项
     *
     * @param $confing 字符串或数组
     * @param $name null或boolean
     * @return string或array
     */
    function get($config, $name=null)
    {
        if(is_string($config))
        {
            if(isset($this->init[$config])) {
                if($name) {
                    if(isset($this->init[$config][$name])) {
                        return $this->init[$config][$name];
                    } else {
                        return false;
                    }
                }
                return $this->init[$config];
            }
        }

        if(is_array($config)) {

            if($name === true) {
                foreach($config as $item) {
                    if(isset($this->init[$item])) {
                        unset($this->init[$item]);
                    }
                }
                return $this->init;
            } else {
                $_returnArr = array();
                foreach($config as $item) {
                    if(isset($this->init[$item])) {
                        $_returnArr[$item] = $this->init[$item];
                    }
                }
            }
            return $_returnArr;
        }
    }

    /**
     * 设定配置项的值
     *
     * @param $name 要设定的项
     * @param $values 设定的项的值
     * @return null
     */
    function set($name, $values=null)
    {
        foreach($values as $k=>$v) {
            $this->init[$name][$k] = $v;
        }
        print_r($this->init);
    }

    /**
     * 设置配置文件
     *
     * @param $init 配置文件
     * @return array
     */
    function setInit($init)
    {
        $this->init = $init;
    }

    /**
     * 读取APP目录下的配置文件
     *
     * @return array;
     */
    function getInitFile($initfile)
    {
        if(is_file($initfile)) {
            return require $initfile;
        }
        else
        throw new CoreException("配置文件未找到");
    }

    /**
     * 初始化配置文件
     *
     * @param $configset 框架运行时指定的配置
     * @return Object Config
     */
    function init($configset=null)
    {
        if($this->userInit && is_array($this->userInit)) {
            if(isset($this->userInit["sys"])) {
                $this->userInit["sys"] = array_merge($this->sys, array_filter($this->userInit["sys"]));
            } else {
                $this->userInit["sys"] = $this->sys;
            }
        } else {
            throw new CoreException("读取配置文件异常");
        }

        if($configset)
        {
            if(!is_array($configset) && is_file($configset)) {
                $this->setInit($configset);
                return $this;
            } else if(! empty($configset) && is_array($configset) ) {

                foreach($configset as $key=>$_config) {
                    if(is_array($_config)) {
                        foreach($_config as $_config_key=>$_config_value) {
                            if($_config_value) {
                                $this->userInit[$key][$_config_key] = $_config_value;
                            }
                        }
                    } else {
                        throw new CoreException("不能识别的运行时配置参数");
                    }
                }
            }
        }
        $this->setInit($this->userInit);

        return $this;
    }

    /**
     * 取得配置文件
     *
     * @param   $obj 是否返回对象
     * @return array/object
     */
    function getInit($obj = false)
    {
        if($obj) {
            return $this->arrayToObject($this->init);
        }
        return $this->init;
    }

    function arrayToObject($d) {
        if (is_array($d)) {
            return (object) array_map(array($this, __FUNCTION__), $d);
        }
        else {
            return $d;
        }
    }

    function put()
    {

    }
}

class Config extends ConfigBase
{

}