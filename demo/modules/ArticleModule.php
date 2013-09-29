<?php defined('DOCROOT')or die('Access Denied');
/**
 * @Auth: wonli <wonli@live.com>
 * Class ArticleModel
 */
class ArticleModule extends CoreModule
{
    /**
     * 拼接SQL 适合复杂查询
     *
     * @return mixed
     */
    function index()
    {
        $sql = "SELECT * FROM `article` ORDER BY `date` DESC LIMIT 10";
        $data = $this->link->fetchAll($sql);
        return $data;
    }

    /**
     * 取article列表
     *
     * @param array $p
     * @internal param int $page
     * @internal param int $pre
     * @return mixed
     */
    function getArticle( & $p = array("p"=>1, "limit"=>20) )
    {
        $data = $this->link->find("article", "*", "id > 0", "date DESC", $p);
        
        foreach($data as & $a) {
            $a ['tag'] = $this->link->getAll(
                "article_tags a LEFT JOIN tags t ON a.tid=t.id", 
                "a.tid as id, t.name as name", 
                array('aid'=>$a['id'])
            );
        }

        return $data;
    }

    /**
     * 按tag_id查找博文
     *
     * @param $tid
     * @param array $page
     * @return mixed
     */
    function get_article_by_tag($tid, & $page = array('p'=>1, 'limit'=>5))
    {
        $data = $this->link->find( "(select aid from article_tags where tid={$tid}) t LEFT JOIN article a ON a.id=t.aid",
            "a.*" , '1=1', '', $page
        );

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

    /**
     * 文章详情
     *
     * @param $id
     * @return mixed
     */
    function getDetail($id)
    {
        $data = $this->link->get('article', '*', array('id'=>$id));
        $tag_info = $this->link->getAll("`article_tags` as at, `tags` as t", "*", "at.aid = {$id} AND at.tid = t.id");

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
