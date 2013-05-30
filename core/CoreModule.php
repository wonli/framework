<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:       wonli
 * @Version: $Id: CoreModule.php 72 2013-04-14 07:22:42Z ideaa $
 */
class CoreModule extends FrameBase
{
	function __construct($controller = null)
    {
        $this->controller = $controller;
    }

    final function loadModel($model_name, $type = "mysql")
    {
        $model_class_name = ucfirst($model_name.'Module');        
        return new $model_class_name($model_name);
    }

    final function load($module_name)
    {
        $name = substr($module_name, 0, -6);        
        return $this->initModule($name);
    }
}
