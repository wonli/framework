<?php
/**
* @Author:       wonli <wonli@live.com>
*/
class Tag extends CoreController
{
    /**
     * @cp_params id, p
     */
    function index()
    {
        $tid = isset($this->params['id']) ? intval($this->params['id']) : 0;
        $p = isset($this->params['p'])?intval($this->params['p']):0;

        if(! $tid)
        {
            $this->to("article");
        }

        $page = array(
            'p' => $p,
            'limit' => 15,
            'half' => 3,
            'link' => array("tag", array('id'=>$tid))
        );

        $data['tag'] = $this->loadModule("Blog")->get_all_tag();
        $data['article'] = $this->loadModule("Article")->get_article_by_tag($tid, $page);
        $data['page'] = $page;

        $data['tid'] = $tid;
        $this->view->display($data);
    }
}
