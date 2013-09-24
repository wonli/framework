<?php defined('DOCROOT')or die('Access Denied');
/**
* Author:       wonli
* Contact:      wonli@live.com
* Date:         2011.08
* Description:  MainView
*/
class ArticleView extends CoreView
{
    function index($notes)
    {        
        $article = $notes["data"];
        $page = $notes["page"];
        
        $this->set(array("title"=>'首页'));   
        include $this->tpl('article');
    }
    
    function detail($data)
    {
        $content = $data[0];
        // $comment = $data[1];
        
        $this->set(array("title"=>$content["title"]));   
        include $this->tpl("detail");
    }
    
    function about($notes)
    {
        $this->set(array("title"=>'关于Cross'));   
        include $this->tpl('about');    
    }
}
?>