<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class UserView
 */
class BlogView extends BaseView
{
    /**
     * 加载管理页面
     */
    function index( )
    {
        $this->set(array("title"=>'管理博客'));
        include $this->tpl('blog/index');
    }

    /**
     * 添加博客
     */
    function add( $data = array() )
    {
        $this->set(array("title"=>'添加博客'));
        include $this->tpl('blog/add');
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
        include $this->tpl('blog/list');
    }

    function page($page, $tpl='page')
    {
        @list($controller, $params) = $page['link'];
        if(empty($params)) $params = array();

        $_dot = isset($page['dot']) ? $page["dot"] : $this->urlconfig['dot'];
        include $this->tpl("page/{$tpl}");
    }
}
