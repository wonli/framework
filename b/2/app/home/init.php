<?php defined('DOCROOT')or die('Access Denied');
/**
* app配置文件
*/
$init = array();

$init['sys'] = array(
    'debug'=>true,
);

$init['url'] = array(
    'rewrite'=>true,//是否启用rewrite true/false;
    'dot'=>'-',
    'ext'=>'',
    'index'=>'index.php'//end(explode( '/', $_SERVER["SCRIPT_NAME"]))
);

#控制器配置
$init["controller"] = array(

    'tag'=>array(
        'alias' => 'Tag:index'
    ),
);

$init['request'] = array
(
    '*'=>'Main:index',
);

return $init;


