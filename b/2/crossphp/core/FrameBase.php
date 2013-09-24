<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
* Author:       wonli
* Contact:      wonli@live.com
* Date:         2011.08
* Description:  FrameBase
*/
class FrameBase
{
    protected $name;
    protected $view;
    protected $model;
    protected $action;
    protected $params;
    protected $config;
    protected static $appInit;

    /**
     * 构造函数 实例化当前类
     *
     * @param $name    controller名称
     * @param $action  当前要执行的方法名
     * @param $params  方法的参数
     * @param $appinit config类的object
     */
    function __construct($name = null, $action = null, $params = null, $appInit = null)
    {
        $this->name = $name;
        $this->action = $action;
        $this->params = $params;
        $this->config = $appInit;

        $this->init($appInit);
    }

    public function init($appInit)
    {
        $this->model = $this->initModel();
        $this->view  = $this->initView($appInit);
    }

    protected function sparams($params)
    {
        return $this->paramsDeCode($params);
    }

    private function paramsDeCode($params)
    {
        $mcrypt = new Mcrypt;
        $_params = $mcrypt->deCode($params);
        return $_params;
    }

    /**
     * 初始化model
     * @return object model类实例
     */
    public function initModel()
    {
        $modelname = $this->name.'Model';
        return new $modelname($this->name);
    }

    /**
     * 初始化view
     * @return object view类实例
     */
    public function initView()
    {
        $viewname = $this->name.'View';
        return new $viewname($this->action, $this->name, $this->params, $this->config);
    }
}
