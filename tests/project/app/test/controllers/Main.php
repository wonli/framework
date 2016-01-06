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
     * 测试注释配置
     *
     * @cp_params a, b, c
     */
    function annotate()
    {
        return $this->params;
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
        return $this->view->link('Main:getUrlSecurityParams', array(
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
        return $this->view->slink('Main:getUrlSecurityParams', array(
            'p1'    =>  $params['p1'],
            'p2'    =>  $params['p2'],
            'p3'    =>  $params['p3']
        ));
    }

    /**
     * url加密 参数解密
     *
     * @cp_params p1, p2, p3
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

        $controller = 'Main';
        $action = 'getUrlSecurityParams';

        $this->view->setLinkBase('');
        $url = $this->view->slink(sprintf('%s:%s', $controller, $action), array(
            'p1'    =>  $params['p1'],
            'p2'    =>  $params['p2'],
            'p3'    =>  $params['p3']
        ));

        $custom_router_params[] = $controller;
        $custom_router_params[] = $action;

        switch($link_type)
        {
            case 2:
                list(, $params_string) = explode('?', $url);
                $custom_router_params[$params_string] = '';
                break;

            default:
                $url_array = explode($dot, $url);
                $params_string = end($url_array);
                $custom_router_params[] = $params_string;
        }

        $router = new Router(parent::getConfig());
        $router->setRouterParams($custom_router_params)->getRouter();
        $result = $this->sParams();

        $this->display($result);
    }
}
