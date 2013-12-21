<?php
/**
* @Author:       wonli <wonli@live.com>
*/
class TagView extends BaseView
{
    function index($data)
    {
        $article = $data['article'];
        $page = $data['page'];

        $this->set(array(
            "title"=>'首页'
        ));

        include $this->tpl('tag/index');
    }
}
