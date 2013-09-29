<?php
class Main extends CoreController
{
    function index()
    {
        $data["article"] = $this->loadModule("Article")->getArticle( );
        $this->display($data);
    }
}
