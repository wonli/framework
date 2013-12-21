<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class MainView
 */
class MainView extends BaseView
{
    function index($data)
    {
        $article = $data["article"];

        //用于SEO
        $this->set(array(
            "title"=>'首页'
        ));

        include $this->tpl('main/index');
    }
}
