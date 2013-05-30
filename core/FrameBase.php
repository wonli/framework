<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:       wonli wonli@live.com
 */
class FrameBase
{
    protected $name;
    
    protected $model;
    
    protected $module;
    
    protected $config;
    
    protected $cache_config;
    
    public function setControllerName($controller_name)
    {
        $this->name = $controller_name;
    }

    public function setActionName($action_name)
    {
        $this->action = $action_name;
    }

    public function setParams($params)
    {
        // $this->params = Helper::strip_selected_tags($params);
        // $this->params = $params;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function setCacheConfig($cache_config)
    {
        $this->cache_config = $cache_config;
    }

    public function init()
    {       
        //$this->module = $this->initModule();
        //$this->model = $this->initModel();
        //$this->view  = $this->initView();
    }

    function setAuth($key, $value, $exp=86400)
    {
        $auth_type = Cross::Config()->get("sys", "auth");
        return HttpAuth::factory( $auth_type )->set($key, $value, $exp);
    }

    function getAuth($key, $de = false)
    {
        $auth_type = Cross::Config()->get("sys", "auth");
        return HttpAuth::factory( $auth_type )->get($key, $de);
    }

    function encode_params($tex,$key,$type="encode")
    {
        if($type=="decode"){
            if(strlen($tex)<5)return false;
            $verity_str=substr($tex, 0,3);
            $tex=substr($tex, 3);
            if($verity_str!=substr(md5($tex),0,3)){
                //完整性验证失败
                return false;
            }
        }
        $rand_key=md5($key);

        if($type == "decode") {
            $tex = base64_decode($tex);
        } else {
            $tex = strval($tex);
        }

        $texlen=strlen($tex);
        $reslutstr="";
        for($i=0;$i<$texlen;$i++){
            $reslutstr.=$tex{$i}^$rand_key{$i%32};
        }

        if($type!="decode"){
            $reslutstr=trim(base64_encode($reslutstr),"==");
            $reslutstr=substr(md5($reslutstr), 0,3).$reslutstr;
        }
        return $reslutstr;
    }

    protected function sparams( $params=null )
    {
        if(! $params) {
            $params = $this->params;
        }
        return $this->encode_params($params, "crossphp", "decode");
    }

    protected function mcryptEncode($params)
    {
        $mcrypt = new Mcrypt;
        $_params = $mcrypt->enCode($params);
        return $_params[1];
    }

    protected function mcryptDecode($params)
    {
        $mcrypt = new Mcrypt;
        $_params = $mcrypt->deCode($params);
        return $_params;
    }

    /**
     * 初始化model
     * @return object model类实例
     */
    public function initModel($controller_name = null)
    {
        if(! $controller_name) {
            $modelname = $this->name.'Model';
        } else {
            $modelname = $controller_name.'Model';
        }
        return new $modelname($this->name);
    }

    /**
     * 初始化module
     * @return object model类实例
     */
    public function initModule($controller_name = null)
    {
        if(! $controller_name) {
            $modulename = $this->name.'Module';
        } else {
            $modulename = $controller_name.'Module';
        }

        $modulename = ucfirst($modulename);

        return new $modulename($this->name);
    }

    /**
     * 初始化view
     * @return object view类实例
     */
    public function initView($action = null, $controller_name = null)
    {
        if(! $controller_name) {
            $controller_name = Dispatcher::$controller;
            $viewname = Dispatcher::$controller.'View';
        } else {
            $viewname = $controller_name.'View';
        }

        if(! $action) {
            $action = Dispatcher::$action;
        }

        return new $viewname($action, $controller_name, Dispatcher::$params, Dispatcher::$cache_config);
    }
    
    function __get($name)
    {    
        if($name == "view") {
            return $this->view = $this->initView();
        }
        
        if($name == "action") {
            return $this->action = Dispatcher::$action;
        }
        
        if($name == "params") {
            return $this->params = Dispatcher::$params;
        }
        
        if($name == "controller") {
            return $this->controller = Dispatcher::$controller;
        }
    }
}
