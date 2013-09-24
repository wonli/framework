<?php defined('DOCROOT')or die('Access Denied');
/**
* Author:       wonli
* Contact:      wonli@live.com
* Date:         2011.08
* Description:  MainView
*/
class MainView extends CoreView
{
    function index($data)
    {        
        $article = $data["article"];
        $this->set(array("title"=>'首页'));   
        include $this->tpl('main');
    }
}