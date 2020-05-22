<?php
/**
 * @author wonli <wonli@live.com>
 */

namespace app\test\controllers;

use Cross\Exception\CoreException;
use Cross\MVC\Controller;

class Main extends Controller
{
    /**
     * 取app配置文件
     */
    function getAppConfig()
    {
        $config = $this->config->get('router');
        return json_encode($config);
    }

    /**
     * 设置app配置文件项
     */
    function setAppConfig()
    {
        $a = $this->params['a'];
        $this->config->set('a', $a);

        return json_encode(array('a' => $this->config->get('a')));
    }

    /**
     * 测试注释配置
     *
     * @cp_params a=1, b=2, c=3
     */
    function annotate()
    {
        return $this->params;
    }

    /**
     * 生成连接
     *
     * @return string
     * @throws CoreException
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
            'ext' => $ext,
            'dot' => $dot,
            'type' => $type,
            'index' => $index,
        ));

        $this->view->setLinkBase('');
        return $this->view->url('Main:getUrlSecurityParams', array(
            'p1' => $params['p1'],
            'p2' => $params['p2'],
            'p3' => $params['p3']
        ));
    }

    /**
     * 生成加密链接
     *
     * @return string
     * @throws CoreException
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
            'ext' => $ext,
            'dot' => $dot,
            'type' => $type,
            'index' => $index,
        ));

        $this->view->setLinkBase('');
        return $this->view->sUrl('Main:getUrlSecurityParams', [
            'p1' => $params['p1'],
            'p2' => $params['p2'],
            'p3' => $params['p3']
        ]);
    }

    /**
     * url加密 参数解密
     *
     * @cp_params p1, p2, p3
     * @throws CoreException
     */
    function makeEncryptLinkAndDecryptParams()
    {
        $params = $this->params;
        $link_type = $this->params['link_type'];
        $dot = $this->params['dot'];
        $ext = $this->params['ext'];

        $this->view->cleanLinkCache();
        $this->config->set('url', array(
            'rewrite' => false,
            'ext' => $ext,
            'dot' => $dot,
            'type' => $link_type
        ));

        $controller = 'Main';
        $action = 'getUrlSecurityParams';

        $this->view->setLinkBase('');
        $url = $this->view->sUrl(sprintf('%s:%s', $controller, $action), array(
            'p1' => $params['p1'],
            'p2' => $params['p2'],
            'p3' => $params['p3']
        ));

        $custom_router_params[] = $controller;
        $custom_router_params[] = $action;

        if ($link_type > 2) {
            list(, $params_string) = explode('?', $url);
        } else {
            $url_array = explode($dot, $url);
            $params_string = end($url_array);
        }

        return $this->sParams(true, $params_string);
    }
}
