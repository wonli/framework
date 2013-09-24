<?php defined('DOCROOT')or die('Access Denied');
/**
 * @Auth: wonli <wonli@live.com>
 * Class UserModel
 */
class UserModule extends CoreModule
{
    /**
     * 用户表
     *
     * @var string
     */
    protected $t_user = 'user';

    /**
     * 登录验证
     *
     * @param $user
     * @param $pass
     * @return bool
     */
    function checkUser($user, $pass)
    {
        $user_info = $this->link->get($this->t_user, "*", array('user'=>$user));

        if( md5(sha1($user_info["salt"].$pass).$pass) === $user_info["password"] )
        {
            return $user_info;
        }
        return false;
    }

    function listBlog()
    {
        $sql = "SELECT * FROM `article` ORDER BY `date` DESC";
        $data = $this->link->fetchAll($sql);
        return $data;
    }

    function delBlog($id)
    {
        $sql = "DELETE FROM `article` WHERE `id` = {$id}";
        if($this->link->execute($sql)) return true;
        else return false;
    }

    function upPost($id, $title, $content, $status, $istop, $desc, $cid)
    {
        $time = time();
        $sql = "UPDATE `article` SET
                    `title` = '{$title}',
                    `content` = '{$content}',
                    `status`={$status},
                    `author`='{$_SESSION["screen_name"]}',
                    `desc` = '{$desc}',
                    `istop` = {$istop},
                    `cid` = {$cid},
                    `date` = {$time}
                WHERE `id` ={$id}";

        if($this->link->execute($sql)) return $id;
        else return false;
    }

    function savePost($title, $content, $status, $istop, $desc, $cid)
    {
        $time = time();
        $sql = "INSERT INTO `article` (
                `id` , `cid` , `istop` ,`title` ,`status` ,`author` ,`desc` ,`content` ,`date`
            ) VALUES (
                NULL , {$cid}, {$istop}, '{$title}', '{$status}', '{$_SESSION["screen_name"]}', '{$desc}', '{$content}', {$time}
            )";

        if($this->link->execute($sql)) return $this->link->insertid();
        else return false;
    }

    function getDetail($id)
    {
        $sql = "SELECT * FROM `article` WHERE `id`={$id}";
        $data = $this->link->fetchOne($sql);
        return $data;
    }

    function getComment($id)
    {
        $sql = "SELECT * FROM `comment` WHERE `post_id` = {$id}";
        $data = $this->link->fetchAll($sql);
        return $data;
    }

    function get_tag_id_by_name(array $tags_name)
    {
        $result = array();
        if(is_array($tags_name)) {
            foreach($tags_name as & $n) {
                $n = trim($n);
                $sql = "SELECT * FROM `tags` WHERE `name` = '{$n}'";
                $r = $this->link->fetchOne($sql);
                if(! empty($r)) {
                    $result [] = $r["id"];
                } else {
                    $sql = "INSERT INTO `tags` (`name`) VALUES ('{$n}')";
                    $this->link->execute($sql);
                    $result [] = $this->link->insertid();
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
        return $this->link->getAll('tags', '*');
    }

    function update_tags($aid, $tags_id)
    {
        $sql = "DELETE FROM `article_tags` WHERE `aid`={$aid}";

        $this->link->execute($sql);

        $values = '';
        foreach($tags_id as $tid) {
            $values .= "({$aid}, {$tid}),";
        }

        $values = trim($values, ",");
        $sql = "INSERT INTO `article_tags` (`aid`, `tid`) VALUES {$values}";

        return $this->link->execute($sql);
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
