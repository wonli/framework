<?php defined('DOCROOT')or die("Access Denied");
/**
* @Author:       wonli <wonli@live.com>
*/
class Tag extends CoreController
{
    function index()
    {
        @list($tid, $tname, $p) = $this->params;
        
        if(! $tid) return $this->to("article");;
        
    	$pn = $p?intval($p)-1:0;
    	$pre = 10;
    	$limit = $pre*$pn;
    	$_total = $this->model->load("ArticleModel")->get_total_by_tag($tid);
    	$total = ceil($_total/$pre);
    	
        if($_total > 0) {
            $data["page"] = new Page($pn+1, $total, $this->view->link("tag", array($tid, $tname, "<:pn:>")));        
        }
        
        
        $data["tag"] = $this->model->load("UserModel")->get_all_tag();
        $data["article"] = $this->model->load("ArticleModel")->get_article_by_tag($tid, $limit, $pre);
        $data["tid"] = $tid;
        
        $this->view->display($data);
    }
}
