<?php defined('DOCROOT')or die('Access Denied');
/**
* @Author:       wonli <wonli@live.com>
*/
class TagView extends CoreView
{
    function index($data)
    {        
        $article = $data["article"];
        $page = isset($data["page"]) ? $data["page"] : null;
        $this->set(array("title"=>'首页'));   
        include $this->tpl('tag');
    }
}