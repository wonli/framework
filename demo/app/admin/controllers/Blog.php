<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class Blog
 */
class Blog extends Base
{
    /**
     * @var BlogModule
     */
    protected $BLOG;

    /**
     * @var ArticleModule
     */
    protected $ARTICLE;

    function __construct()
    {
        parent::__construct();
        $this->BLOG = new BlogModule();
        $this->ARTICLE = new ArticleModule();
    }

    /**
     * 登陆后的默认页面
     */
    function index()
    {
        $this->display();
    }

    /**
     * 默认文章发表页面
     */
    function add()
    {
        $this->display();
    }

    /**
     * 博客文章列表
     */
    function blogList()
    {
        $page = array(
            'p' => isset($this->params['p'])?intval($this->params['p']):1,
            'limit' => 15,
            'half' => 3,
            'link' => array("blog:blogList")
        );

        $result['data'] = $this->ARTICLE->getArticle($page);
        $result['page'] = $page;

        $this->display($result);
    }

    /**
     * 发表博文
     */
    function post()
    {
        if($this->is_post())
        {
            $args = $this->getArgs();
            $args['ct'] = strtotime( $args['ct'] );

            $tags = $args['tag'];
            if(! empty($tags))
            {
                $tags_id = $this->parse_tags($tags);
            }
            unset($args['tag']);
            $args['author'] = $_SESSION['admin'];

            $aid = $this->BLOG->saveArticle( $args );
            if($aid)
            {
                if(! empty($tags_id))
                {
                    $this->BLOG->update_tags($aid, $tags_id);
                }
            }
            return $this->to("blog:blogList");
        }
    }

    /**
     * 解析tags
     *
     * @param $tags
     * @return mixed
     */
    function parse_tags($tags)
    {
        $tags_name = explode(",", $tags);
        $tags_id = $this->BLOG->get_tag_id_by_name($tags_name);
        return $tags_id;
    }

    /**
     * 删除
     */
    function del()
    {
        $id = intval($this->params['id']);
        $notes = $this->BLOG->delBlog($id);
        $this->to("blog:blogList");
    }

    /**
     * 编辑
     *
     * @return mixed
     */
    function edit()
    {
        $id = intval($this->params['id']);
        $data = $this->ARTICLE->getDetail($id);

        if($data)
        {
            return $this->display($data, 'add');
        }
    }
}
