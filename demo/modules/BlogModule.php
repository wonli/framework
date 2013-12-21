<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class UserModel
 */
class BlogModule extends CoreModule
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
     * 列表
     *
     * @return mixed
     */
    function listBlog()
    {
        $sql = "SELECT * FROM {$this->t_article} ORDER BY `date` DESC";
        $data = $this->link->fetchAll($sql);
        return $data;
    }

    /**
     * 删除
     *
     * @param $id
     * @return bool
     */
    function delBlog($id)
    {
        $sql = "DELETE FROM {$this->t_article} WHERE `id` = {$id}";
        if($this->link->execute($sql)) return true;
        else return false;
    }

    /**
     * 保存文章
     *
     * @param $data
     * @return int
     */
    function saveArticle( $data )
    {
        if(isset($data['id']) && !empty($data['id']))
        {
            $aid = intval($data['id']);
            unset($data['id']);
            $this->link->update( $this->t_article, $data, array('id' => $aid) );
        }
        else
        {
            unset($data['id']);
            $aid = $this->link->add( $this->t_article, $data );
        }

        return $aid;
    }

    /**
     * 获取单条
     *
     * @param $id
     * @return mixed
     */
    function getDetail($id)
    {
        $sql = "SELECT * FROM {$this->t_article} WHERE `id`={$id}";
        $data = $this->link->get($sql);
        return $data;
    }

    /**
     * 获取评论
     *
     * @param $id
     * @return mixed
     */
    function getComment($id)
    {
        $sql = "SELECT * FROM `comment` WHERE `post_id` = {$id}";
        $data = $this->link->fetchAll($sql);
        return $data;
    }

    /**
     * 按tag查询
     *
     * @param array $tags_name
     * @return array
     */
    function get_tag_id_by_name(array $tags_name)
    {
        $result = array();
        if(is_array($tags_name)) {
            foreach($tags_name as & $n) {
                $n = trim($n);
                $sql = "SELECT * FROM {$this->t_tag} WHERE `name` = '{$n}'";
                $r = $this->link->fetchOne($sql);
                if(! empty($r)) {
                    $result [] = $r["id"];
                } else {
                    $sql = "INSERT INTO {$this->t_tag} (`name`) VALUES ('{$n}')";
                    $this->link->execute($sql);
                    $result [] = $this->link->insert_id();
                }
            }
        }
        return $result;
    }

    /**
     * 取得所有tag列表
     *
     * @return mixed
     */
    function get_all_tag()
    {
        return $this->link->getAll($this->t_tag, '*');
    }

    /**
     * 更新tag
     *
     * @param $aid
     * @param $tags_id
     * @return mixed
     */
    function update_tags($aid, $tags_id)
    {
        $sql = "DELETE FROM {$this->t_article_tag} WHERE `aid`={$aid}";

        $this->link->execute($sql);

        $values = '';
        foreach($tags_id as $tid) {
            $values .= "({$aid}, {$tid}),";
        }

        $values = trim($values, ",");
        $sql = "INSERT INTO {$this->t_article_tag} (`aid`, `tid`) VALUES {$values}";

        return $this->link->execute($sql);
    }

}
