<?php defined('DOCROOT')or die("Access Denied");
/**
* @Author:       wonli <wonli@live.com>
*/
class Tag extends CoreController
{
    static $_act_alias_ = array(

    );

    function index()
    {


        //var_dump($this->view);

        @list($tid, $tname, $p) = $this->params;


        if(! $tid) return $this->to("article");;

        $page = array(
            'p' => $p ? $p : 0,
            'limit' => 10,
            'half' => 3,
            'link' => array("tag", array($tid, $tname))
        );

        $data['tag'] = $this->loadModule("User")->get_all_tag();
        $data['article'] = $this->loadModule("Article")->get_article_by_tag($tid, $page);
        $data['page'] = $page;

        $data['tid'] = $tid;
        $this->view->display($data);
    }
}
