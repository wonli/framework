<?php defined('DOCROOT')or die("Access Denied");
/**
* Author:       wonli
* Contact:      wonli@live.com
* Date:         2011.08
* Description:  main
*/
class Main extends CoreController
{
    function index()
    {
        $data["article"] = $data["tag"] = array();
        $data["article"] = $this->model->load("ArticleModel")->getArticle(0, 20);
        $data["tag"] = $this->model->load("UserModel")->get_all_tag();

        $this->view->display($data);
    }
    
    function json()
    {
        $article = $this->model->load("ArticleModel")->getArticle(0, 20);        
        print "result(".json_encode($article).")";
    }
}
