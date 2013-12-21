<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class UserView
 */
class UserView extends BaseView
{
    /**
     * 加载管理页面
     */
    function index( )
    {
        $this->set(array("title"=>'管理博客'));
        include $this->tpl('user/index');
    }

    /**
     * 添加博客
     */
    function add( )
    {
        $this->set(array("title"=>'添加博客'));
        include $this->tpl('user/add');
    }

    /**
     * 文章列表
     *
     * @param $notes
     */
    function blogList($notes = array())
    {
        $data = $notes["data"];
        $page = $notes["page"];
        include $this->tpl('user/list');
    }

    /**
     * 登陆页面
     */
    function login()
    {
        $this->set(array("title"=>"用户登录"));
        include $this->tpl("user/login");
    }
}
