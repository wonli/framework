<?php defined('DOCROOT')or die('Access Denied');
/**
* Author:       wonli
* Contact:      wonli@live.com
* Date:         2011.08
* Description:  MainView
*/
class UserView extends BaseView
{
    function index($data)
    {
        $this->set(array("title"=>'添加博客'));
        include $this->tpl('user/index');
    }

    function add($data)
    {
        $this->set(array("title"=>'添加博客'));
        include $this->tpl('user/add');
    }

    function bloglist($notes)
    {
        $data = $notes["data"];
        $page = $notes["page"];
        include $this->tpl('user/list');
    }

    function login()
    {
        $this->set(array("title"=>"用户登录"));
        include $this->tpl("user/login");
    }
}
