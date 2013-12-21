<?php
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
        $result["tag"] = $this->loadModule("Blog")->get_all_tag();
        $result['page'] = $page;

        $this->display($result);
    }

    function detail( )
    {
        $p = is_array($this->params) ? intval($this->params[0]) : intval($this->params);

        if($p)
        {
            $data = $this->loadModule('Article')->getDetail($p);

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
