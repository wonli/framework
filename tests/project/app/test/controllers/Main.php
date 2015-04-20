<?php
/**
 * @Author:       wonli <wonli@live.com>
 */
namespace app\test\controllers;

use Cross\Core\Router;
use Cross\MVC\Controller;

class Main extends Controller
{
    /**
     * 获取app配置文件
     */
    function getAppConfig()
    {
        $this->display($this->config->get('router'));
    }

    /**
     * 设置app配置文件项
     */
    function setAppConfig()
    {
        $a = $this->params['a'];
        $this->config->set('a', $a);

        $this->display(array('a'=>$this->config->get('a')));
    }

    /**
     * 生成连接
     */
    function makeLink()
    {
        $params = $this->params;
        $type = $this->params['type'];
        $dot = $this->params['dot'];
        $ext = $this->params['ext'];
        $index = $this->params['index'];

        $this->view->cleanLinkCache();
        $this->config->set('url', array(
            'ext'   =>  $ext,
            'dot'   =>  $dot,
            'type'  =>  $type,
            'index' =>  $index,
        ));

        $this->view->setLinkBase('');
        echo $this->view->link("Main:getUrlSecurityParams", array(
            'p1'    =>  $params['p1'],
            'p2'    =>  $params['p2'],
            'p3'    =>  $params['p3']
        ));
    }

    /**
     * 生成加密连接
     */
    function makeEncryptLink()
    {
        $params = $this->params;
        $type = $this->params['type'];
        $dot = $this->params['dot'];
        $ext = $this->params['ext'];
        $index = $this->params['index'];

        $this->view->cleanLinkCache();
        $this->config->set('url', array(
            'ext'   =>  $ext,
            'dot'   =>  $dot,
            'type'  =>  $type,
            'index' =>  $index,
        ));

        $this->view->setLinkBase('');
        echo $this->view->slink("Main:getUrlSecurityParams", array(
            'p1'    =>  $params['p1'],
            'p2'    =>  $params['p2'],
            'p3'    =>  $params['p3']
        ));
    }

    /**
     * url加密 参数解密
     */
    function makeEncryptLinkAndDecryptParams()
    {
        $params = $this->params;
        $link_type = $this->params['link_type'];
        $dot = $this->params['dot'];
        $ext = $this->params['ext'];

        $this->view->cleanLinkCache();
        $this->config->set('url', array(
            'rewrite'   =>  false,
            'ext'   =>  $ext,
            'dot'   =>  $dot,
            'type'  =>  $link_type
        ));

        $this->view->setLinkBase('');
        $link = $this->view->slink("Main:getUrlSecurityParams", array(
            'p1'    =>  $params['p1'],
            'p2'    =>  $params['p2'],
            'p3'    =>  $params['p3']
        ));

        $url_start = 0;
        switch($link_type)
        {
            case 1:
            case 3:
                $url_start = 2;
                $index_file_name = $this->config->get('url', 'index');
                if (strcasecmp($index_file_name, 'index.php') != 0) {
                    $url_start += strlen($index_file_name);
                } else {
                    $url_start += 1;
                }
                break;

            case 2:
            case 4:
                $url_start = strlen($this->config->get('url', 'index')) + 2;
                break;
        }

        $r = Router::initialization($this->config)->set_router_params(explode($dot, substr($link, $url_start)))->getRouter();
        $result = $this->sParams($r->getParams());

        $this->display($result);
    }
}
