<?php defined('DOCROOT')or die('Access Denied');
/**
* @Author: wonli <wonli@live.com>
*/
class MainView extends CoreView
{   
    function index( $data )
    {
        include $this->tpl("main/index");
    }
 
	function test2()
	{
		echo 'hi, 我是view输出的内容';
	}
 
}