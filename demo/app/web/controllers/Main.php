<?php defined('DOCROOT')or die("Access Denied");
/**
 * @Auth: wonli <wonli@live.com>
 * Class Main
 */
class Main extends CoreController
{
    function index()
    {
        $data["article"] = $data["tag"] = array();
        $data["article"] = $this->loadModule("Article")->getArticle( );
        $data["tag"] = $this->loadModule("User")->get_all_tag();

        $this->view->display($data);
    }
}
