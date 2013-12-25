<?php
/**
* @Author: wonli <wonli@live.com>
*/
class AclView extends BaseView
{
    function nav_manager($data=array())
    {
        include $this->tpl("acl/nav_manager");
    }

    function index($data)
    {
        $menu_list = $data['menu_list'];
        include $this->tpl("acl/index");
    }

    function add_role($data)
    {
        if(! empty( $data ['notes'] ) )
        {
            $this->js_notes($data['notes']);
        }

        $menu_list = $data ['menu_list'];
        include $this->tpl("acl/add_role");
    }

    function edit_role($data)
    {
        $role_info = $data ['role_info'];
        $menu_list = $data ['menu_list'];
        $menu_select = explode(',', $data['role_info']['behavior']);

        include $this->tpl("acl/role_edit");
    }

    function role_list($data)
    {
        if(! empty( $data ['notes'] ) )
        {
            $this->js_notes($data['notes']);
        }
        include $this->tpl("acl/role_list");
    }

    function user( $data )
    {
        include $this->tpl("acl/user");
    }
}
