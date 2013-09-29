<?php defined('DOCROOT')or die("Access Denied");
/**
* Author:       wonli
* Contact:      wonli@live.com
* Date:         2011.08
* Description:  user
*/
class User extends CoreController
{

    protected $USER;

    protected $ARTICLE;

    function __construct()
    {
        parent::__construct();
        $this->USER = $this->loadModule('User');
        $this->ARTICLE = $this->loadModule("Article");

    }

    function index()
    {
        if(! $_SESSION["u"]) return $this->to("user:login");
        $this->view->display();
    }

    function add()
    {
        if(! $_SESSION["u"]) return $this->to("user:login");
        $this->view->display();
    }

    function bloglist()
    {
        if(! $_SESSION["u"]) return $this->to("user:login");

        $page = array(
            'p' => $this->params?intval($this->params):1,
            'limit' => 15,
            'half' => 3,
            'link' => array("user:bloglist")
        );

        $result['data'] = $this->ARTICLE->getArticle($page);
        $result['page'] = $page;

        $this->view->display($result);
    }

    function post()
    {
        if(! $_SESSION["u"]) return $this->to("user:login");
        if($this->is_post()) {       
            $args = $this->getArgs();        
            $title = $args["title"];
            $istop = isset($args["istop"]) ? 1 : 0;
            $cid = isset($args["cid"]) ? 1 : 0;
            $desc = $args["desc"];
            $content = $args["content"];
            $status = $args["status"] ? 1 : 0;
            $id = $args["id"];

            $tags = $args["tag"];

            if(! empty($tags)) {
                $tags_id = $this->parse_tags($tags);
            }

            #更新
            if($id) {
                $notes = $this->USER->upPost($id, $title, $content, $status, $istop, $desc, $cid);
                $aid = $id;
            } else {
                $notes = $this->USER->savePost($title, $content, $status, $istop, $desc, $cid);
                $aid = $notes;
            }

            if($notes) {

                if(! empty($tags_id))
                {
                    $this->USER->update_tags($aid, $tags_id);
                }
                return $this->to("article:detail", $notes);
            } else {
                return $this->to("article:detail");
            }
        }
    }

    function parse_tags($tags)
    {
        $tags_name = explode(",", $tags);
        $tags_id = $this->USER->get_tag_id_by_name($tags_name);
        return $tags_id;
    }

    function get_tag_list()
    {

    }

    function del()
    {
        if(! $_SESSION["u"]) return $this->to("user:login");
        $id = intval($this->params);
        $notes = $this->USER->delBlog($id);

        $this->return_referer();
    }

    function edit()
    {
        if(! $_SESSION["u"]) return $this->to("user:login");
        $id = intval($this->params);
        $data = $this->ARTICLE->getDetail($id);

        if($data) {
            return $this->view->display($data, 'add');
        } else {
            return $this->notes("没有找到内容!");
        }
    }

    function login()
    {
        if($this->is_post())
        {
            $user = $_POST["user"];
            $pass = $_POST["pass"];

            $notes = $this->USER->checkUser($user, $pass);

            if(false != $notes)
            {
                $_SESSION["u"] = $notes["user"];
                $_SESSION["screen_name"] = $notes["screen_name"];

                return $this->to("user");
            } else {
                return $this->view->notes("用户名或密码不正确,<a href=".$this->view->link("user:login").">返回重试</a>");
            }
        }

        $this->view->display();
    }

    function logout()
    {
        $_SESSION = array();
        return $this->to("main");
    }
}
