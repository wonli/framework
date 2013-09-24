<?php defined('DOCROOT')or die("Access Denied");
/**
* Author:       wonli
* Contact:      wonli@live.com
* Date:         2011.08
* Description:  user
*/
class User extends CoreController
{
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
        
    	$pn = $this->params?intval($this->params)-1:0;
    	$pre = 10;
    	$limit = $pre*$pn;
    	$_total = $this->model->load("ArticleModel")->getTotal();
    	$total = ceil($_total/$pre);
    	
        $result["page"] = new Page($pn+1, $total, $this->view->link("user:bloglist", "<:pn:>"));
        $result["data"] = $this->model->load("ArticleModel")->getArticle($limit, $pre);
        
        $this->view->display($result);        
    }
    
    function post()
    {
        if(! $_SESSION["u"]) return $this->to("user:login");    
        if($this->_POST()) {
            $title = $this->args["title"];
            $istop = isset($this->args["istop"]) ? 1 : 0;
            $cid = isset($this->args["cid"]) ? 1 : 0;            
            $desc = $this->args["desc"];
            $content = $this->args["content"];
            $status = $this->args["status"] ? 1 : 0;
            $id = $this->args["id"];

            
            $tags = $this->args["tag"];
            
            if(! empty($tags)) {
                $tags_id = $this->parse_tags($tags);
            }
            
            #更新
            if($id) {
                $notes = $this->model->upPost($id, $title, $content, $status, $istop, $desc, $cid);                
                $aid = $id;
            } else {
                $notes = $this->model->savePost($title, $content, $status, $istop, $desc, $cid); 
                $aid = $notes;
            }

            if($notes) {
                $s = $this->model->update_tags($aid, $tags_id);
                return $this->to("article:detail", $notes);
            } else {
                return $this->to("article:detail");
            }
        }
    }
    
    function parse_tags($tags)
    {
        $tags_name = explode(",", $tags);       
        $tags_id = $this->model->get_tag_id_by_name($tags_name);
        return $tags_id;
    }
    
    function get_tag_list()
    {
    
    }
    
    function del()
    {
        if(! $_SESSION["u"]) return $this->to("user:login");    
        $id = intval($this->params);
        $notes = $this->model->delBlog($id);
        if($notes) {
            return $this->reload();
        } else {
            return $this->notes("删除失败!");
        }
    }
    
    function edit()
    {
        if(! $_SESSION["u"]) return $this->to("user:login");    
        $id = intval($this->params);
        $data = $this->model->load("ArticleModel")->getDetail($id);        
        
        if($data) {
            return $this->view->display($data, 'add');
        } else {
            return $this->notes("没有找到内容!");
        }
    }
    
    function login()
    {
        if($this->_POST()) 
        {           
            $user = $this->args["user"];
            $pass = $this->args["pass"];
            $notes = $this->model->checkUser($user, $pass);

            if(false != $notes) 
            {
                $_SESSION["u"] = $notes["user"];
                $_SESSION["screen_name"] = $notes["screen_name"];
                
                return $this->to("user");
            } else {
                return $this->view->notes("用户名或密码不正确,<a href=".$this->view->link("user:login").">返回重试</a>");
            }
        } else {
            $this->view->display();
        }
    }

    function logout()
    {
        $_SESSION = array();
        return $this->to("main");
    }
}
