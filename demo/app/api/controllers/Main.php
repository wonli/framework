<?php
class Main extends CoreController
{
    function index()
    {
        return $this->display(array(
			'name' => 'crossphp',
			'version' => '1.0.1',
		));
    }
}