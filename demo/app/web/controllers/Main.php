<?php defined('DOCROOT')or die('Access Denied');
/**
* @Author: wonli <wonli@live.com>
*/
class Main extends CoreController
{
	function index()
	{	
		$data = '我是内容';
		$this->view->display($data);
	}

	function hello()
	{
		echo 'hello, 我是用控制器直接输出的文字';
	}

	function test2()
	{
		$this->view->test2();
	}
	
	function j()
	{
		$data = array('name'=>'crossphp', 'version'=>'1.0.1');
		//调用JSON方法1: $this->display() == $this->view->display() 
		$this->display($data, 'JSON');
		//方法2:
		//$this->view->JSON($data);
		//$this->view->XML($data);		
	}
	
	function d()
	{
		//调用module
		$TEST = $this->loadModule('Test');
		
	}
}



