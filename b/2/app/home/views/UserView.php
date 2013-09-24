<?php defined('DOCROOT')or die('Access Denied');
/**
* Author:       wonli
* Contact:      wonli@live.com
* Date:         2011.08
* Description:  MainView
*/
class UserView extends CoreView
{
    function index($data)
    {
        $this->set(array("title"=>'添加博客'));
        include $this->tpl('userindex');
    }

    function add($data)
    {        
        $this->set(array("title"=>'添加博客'));   
        include $this->tpl('add');
    }
    
    function bloglist($notes)
    {
        // $data = $notes;
        $data = $notes["data"];
        $page = $notes["page"];    
        include $this->tpl('list');
    }
    
    function login()
    {
        $this->set(array("title"=>"用户登录"));   
        include $this->tpl("login");
    }
}