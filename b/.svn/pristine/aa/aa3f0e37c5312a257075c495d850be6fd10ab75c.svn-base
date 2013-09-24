<?php defined('DOCROOT')or die('Access Denied');
/**
* Author:       wonli
* Contact:      wonli@live.com
* Date:         2011.08
* Description:  mainModel
*/
class ArticleModel extends CoreModel
{
    function index()
    {
        $sql = "SELECT * FROM `article` ORDER BY `date` DESC LIMIT 10";
        $data = $this->link->fetchAll($sql);
        return $data;
    }
    
    function getArticle($page=0, $pre=10)
    {
        $sql = "SELECT * FROM `article` ORDER BY `date` DESC LIMIT {$page}, {$pre}";
        $data = $this->link->fetchAll($sql);
        
        foreach($data as & $a) {
            $tag_sql = "SELECT at.tid as id, t.name as name FROM `article_tags` as at, `tags` as t WHERE at.aid={$a["id"]} AND at.tid=t.id";
            $a["tag"] = $this->link->fetchAll($tag_sql);
        }
        
        return $data;        
    }        
    
    function get_article_by_tag($tid, $page=0, $pre=10)
    {
        $sql = "SELECT a.*,t.id as tag_id FROM article as a, article_tags as t WHERE t.tid={$tid} and a.id=t.aid LIMIT {$page}, {$pre}";
        $data = $this->link->fetchAll($sql);
        
        foreach($data as & $a) {
            $tag_sql = "SELECT at.tid as id, t.name as name FROM `article_tags` as at, `tags` as t WHERE at.aid={$a["id"]} AND at.tid=t.id";
            $a["tag"] = $this->link->fetchAll($tag_sql);
        }        
        
        return $data;
    }
    
    function get_total_by_tag($tid)
    {
        $sql = "SELECT COUNT(*) as max FROM article as a, article_tags as t WHERE t.tid={$tid} and a.id=t.aid";
        $data = $this->link->fetchOne($sql);  
        return $data["max"];
    }
    
    function getTotal()
    {
    	$sql = "SELECT count(*) as tc FROM `article`";
    	$result = $this->link->fetchOne($sql);
        return $result["tc"];
    }    
    
    function getDetail($id)
    {
        $sql = "SELECT * FROM `article` WHERE `id`={$id}";
        $data = $this->link->fetchOne($sql);
        
        $tag_sql = "SELECT at.tid as id, t.name as name FROM `article_tags` as at, `tags` as t WHERE at.aid={$id} AND at.tid=t.id";
        $tag_info = $this->link->fetchAll($tag_sql);

        $data["tag_str"] = $tag_str = "";
        if(! empty($tag_info)) {
            foreach($tag_info as $t) {
                $tag_str .= $t["name"].",";
            }
        }
        
        $data["tag_str"] = trim($tag_str, ",");
        $data["tag"] = $tag_info;
        return $data;
    }
    
    function getComment($id)
    {
        $sql = "SELECT * FROM `comment` WHERE `post_id` = {$id}";
        $data = $this->link->fetchAll($sql);
        return $data;
    }
    
    function saveComment($id, $content, $user, $email, $link,$ct)
    {
        $sql = "INSERT INTO `comment` (`id`, `post_id`, `author`, `email`, `content`, `url`, `createtime`, `status`) 
                VALUES (NULL, {$id}, '{$user}', '{$email}', '{$content}', '{$link}', '{$ct}', '1')";
        if($this->link->execute($sql))
        {
            return true;
        }
        return false;
    }
}
?>