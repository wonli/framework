<?php defined('DOCROOT')or die('Access Denied');
/**
 * @Auth: wonli <wonli@live.com>
 * Class ArticleView
 */
class ArticleView extends BaseView
{
    function index($notes)
    {        
        $article = $notes["data"];
        $page = $notes["page"];
        
        $this->set(array("title"=>'首页'));   
        include $this->tpl('article/index');
    }
    
    function detail($data)
    {
        $content = $data[0];
        // $comment = $data[1];
        
        $this->set(array("title"=>$content["title"]));   
        include $this->tpl("article/detail");
    }
    
    function about($notes)
    {
        $this->set(array("title"=>'关于Cross'));   
        include $this->tpl('about');    
    }
}
?>