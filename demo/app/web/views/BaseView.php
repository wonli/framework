<?php
/**
 * @Auth: wonli <wonli@live.com>
 * BaseView.php
 */

class BaseView extends CoreView
{
    function page($page, $tpl='page')
    {
        @list($controller, $params) = $page['link'];
        if(empty($params)) $params = array();

        $_dot = isset($page['dot']) ? $page["dot"] : $this->urlconfig['dot'];
        include $this->tpl("page/{$tpl}");
    }
}
