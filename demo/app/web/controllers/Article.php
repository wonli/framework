<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Article.php
 */
class Article extends CoreController
{
    /**
     * @cp_params p
     */
    function index( )
    {
        $page = array(
            'p' =>  isset($this->params['p'])?intval($this->params['p']):0,
            'limit' => 20,
            'half' => 3,
            'link' => array("article:page"),
        );

        $result['data'] = $this->loadModule('Article')->getArticle( $page );
        $result["tag"] = $this->loadModule("Blog")->get_all_tag();
        $result['page'] = $page;

        $this->display($result);
    }

    /**
     * @cp_params id
     */
    function detail( )
    {
        $id = is_array($this->params['id']) ? intval($this->params['id']) : intval($this->params['id']);

        if($id)
        {
            $data = $this->loadModule('Article')->getDetail($id);

            if($data)
            {
                $this->display(array($data));
            } else {
                $this->notes("你所请求的页面不存在!");
            }
        } else {
            $this->to();
        }
    }
}
