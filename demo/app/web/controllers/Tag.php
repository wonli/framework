<?php
/**
* @Author:       wonli <wonli@live.com>
*/
class Tag extends CoreController
{
    function index()
    {
        @list($tid, $tag_name, $p) = $this->params;
        if(! $tid)
        {
            $this->to("article");
        }

        $page = array(
            'p' => $p ? $p : 0,
            'limit' => 10,
            'half' => 3,
            'link' => array("tag", array($tid, $tag_name))
        );

        $data['tag'] = $this->loadModule("Blog")->get_all_tag();
        $data['article'] = $this->loadModule("Article")->get_article_by_tag($tid, $page);
        $data['page'] = $page;

        $data['tid'] = $tid;
        $this->view->display($data);
    }
}
