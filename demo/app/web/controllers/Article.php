<?php defined('DOCROOT')or die("Access Denied");
/**
 * @Auth: wonli <wonli@live.com>
 * Class Article
 */
class Article extends CoreController
{
    function index()
    {
        $page = array(
            'p' =>  $this->params?intval($this->params):0,
            'limit' => 20,
            'half' => 3,
            'link' => array("article:page"),
        );

        $result['data'] = $this->loadModule('Article')->getArticle( $page );
        $result['page'] = $page;

        $this->view->display($result);
    }

    function detail($q)
    {
        $p = is_array($this->params) ? intval($this->params[0]) : intval($this->params);

        if($p)
        {
            $data = $this->loadModule('Article')->getDetail($p);

            if($data)
            {
                $this->view->display(array($data));
            } else {
                $this->view->notes("你所请求的页面不存在!");
            }
        } else {
            $this->to();
        }
    }

    function comment()
    {
        if($_POST)
        {
            $args = $this->getArgs();
            $id = intval($args["id"]);
            $content = $args["comment"];
            $user = $args["user"];
            $email = $args["email"];
            $link = $args["link"];
            $ct = time();

            if(empty($user) || empty($content) ) {
                return $this->to("article:detail", $id);
            }

            $notes = $this->model->saveComment($id, $content, $user, $email, $link,$ct);
            if($notes) {
                return $this->to("article:detail", $id);
            } else {
                return $this->to("article:detail", $id);
            }
        }
    }
}
