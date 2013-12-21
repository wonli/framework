<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class Main
 */
class Main extends CoreController
{
    function __construct()
    {
        parent::__construct();
        $this->ARTICLE = new ArticleModule();
        $this->BLOG = new BlogModule();
    }

    function index()
    {
        $data ["article"] = $data["tag"] = array();
        $data ['article'] = $this->ARTICLE->getArticle();
        $data ['tag'] = $this->BLOG->get_all_tag();

        $this->display($data);
    }

    function setSkin()
    {
        $skin = empty($this->params) ? 'default' : $this->params;
        if(is_array($skin))
        {
            $skin = current($skin);
        }

        setcookie('skin', $skin, time()+315360000);
        $this->to();
    }
}
