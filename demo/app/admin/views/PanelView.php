<?php
/**
* Author: wonli <wonli@live.com>
*/
class PanelView extends BaseView
{
	function index()
    {
        include $this->tpl('panel/index');
    }
}