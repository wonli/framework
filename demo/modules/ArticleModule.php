<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class ArticleModel
 */
class ArticleModule extends CoreModule
{
    /**
     * @var string
     */
    protected $t_tag = 'front_tags';

    /**
     * @var string
     */
    protected $t_article = 'front_article';

    /**
     * @var string
     */
    protected $t_article_tag = 'front_article_tags';

    /**
     * 拼接SQL 适合复杂查询
     *
     * @return mixed
     */
    function index()
    {
        $sql = "SELECT * FROM {$this->t_article} ORDER BY `ct` DESC LIMIT 10";
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
        $data = $this->link->find($this->t_article, "*", "id > 0", "ct DESC", $p);

        foreach($data as & $a) {
            $a ['tag'] = $this->link->getAll(
                "{$this->t_article_tag} a LEFT JOIN {$this->t_tag} t ON a.tid=t.id",
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
        $data = $this->link->find( "(select aid from {$this->t_article_tag} where tid={$tid}) t LEFT JOIN {$this->t_article} a ON a.id=t.aid",
            "a.*" , '1=1', '', $page
        );

        return $data;
    }

    /**
     * @param $tid
     * @return mixed
     */
    function get_total_by_tag($tid)
    {
        $sql = "SELECT COUNT(*) as max FROM {$this->t_article} as a, {$this->t_article_tag} as t WHERE t.tid={$tid} and a.id=t.aid";
        $data = $this->link->fetchOne($sql);
        return $data["max"];
    }

    /**
     * @return mixed
     */
    function getTotal()
    {
    	$sql = "SELECT count(*) as tc FROM {$this->t_article}";
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
        $data = $this->link->get($this->t_article, '*', array('id'=>$id));
        $tag_info = $this->link->getAll("{$this->t_article_tag} as at, {$this->t_tag} as t", "*", "at.aid = {$id} AND at.tid = t.id");

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
}
