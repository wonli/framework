<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class Article
 */
class Article extends CoreController
{
    function __construct()
    {
        parent::__construct();

        /**
         * 分页参数
         */
        $this->p = isset($this->params['p']) ? intval($this->params['p']) : 1;

        /**
         * 返回视图的格式 限定为XML或JSON
         */
        $this->t = isset($this->params['t']) && (in_array($this->params['t'], array('json','xml'))) ?
                $this->params['t'] : 'JSON';

        $this->config->set('sys', array('display'=>$this->t));
    }

    function index()
    {
        $page = array(
            'p'     => $this->p,
            'half'  => 3,
            'link'  => array("article:page"),
            'limit' => 5,
        );

        $result ['ret'] = 1;
        $result ['data'] = $this->loadModule('Article')->getArticle( $page );
        $result ['page'] = $page;
        $this->display($result);
    }

    function detail( )
    {
        if( empty($this->params['id']) )
        {
            $this->display( array('ret'=>-1));
            exit(0);
        }

        $id = intval($this->params['id']);
        $data = $this->loadModule('Article')->getDetail($id);

        if($data)
        {
            $this->display(array($data));
        }
    }
}
