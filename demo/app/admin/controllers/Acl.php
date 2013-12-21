<?php defined('DOCROOT')or die('Access Denied');
/**
* @Author: wonli <wonli@live.com>
*/
class Acl extends Base
{
    /**
     * admin/controller下所有类文件的方法列表
     */
    function index()
    {
        $menu_list = $this->ACL->init_menu_list();

        if($this->is_post())
        {
        	$args = $this->getArgs();
        	$this->ACL->save_menu($args["menu"]);
			$this->to("acl");
        }

        $data["menu_list"] = $menu_list;
        $this->view->display($data);
    }

    /**
     * @return javascript
     */
    function nav_manager()
    {
        if($this->is_post())
        {
            $args = $this->getArgs();
            if(isset($args['add']))
            {
                $pid = isset($args["pid"]) ? $args["pid"] : 0;
                $this->ACL->add_nav($args["name"], $args["link"], $pid);
            }

            if(isset($args['save']))
            {
                $this->ACL->save_nav($args['nav']);
            }

            return $this->to("acl:nav_manager");
        }

        $data["menu"] = $this->ACL->get_nav_list();
        $this->view->display($data);
    }

    /**
     * 删除
     */
    function del()
    {
        if(! empty($this->params['id']))
        {
            $this->ACL->del_nav( intval($this->params['id']) );
        }

        $this->to("acl:nav_manager");
    }

    /**
     * 添加管理角色
     */
    function add_role()
    {
        $menu_list = $this->ACL->init_menu_list();

        if($this->is_post())
        {
            if(! empty($_POST['name']) && ! empty($_POST ['menu_id']))
            {
                $menu_set = $_POST ['menu_id'];
                $status = $this->ACL->save_role_menu($_POST['name'], $menu_set);

                if($status['status'] == 1)
                {
                    $this->to("acl:add_role");
                }
                else
                {
                    $data ['notes'] = $status;
                }
            }
            else
            {
                $data ['notes'] = $this->result(-1, "不要乱整");
            }
        }

        $data ['menu_list'] = $menu_list;
        $this->view->display( $data );
    }

    /**
     * 编辑管理角色
     */
    function role_list()
    {
        $data ['role_list'] = $this->ACL->get_role_list();

        if($this->is_post())
        {
            $data['notes'] = $this->ACL->edit_role_menu($_POST['rid'], $_POST['name'], $_POST['menu_id']);
            if($data['notes'] ['status'] == 1)
            {
                $this->to("acl:role_list");
            }
        }

        $this->view->display( $data );
    }

    /**
     * 编辑角色
     *
     * @return array|string
     */
    function edit_role()
    {
        if($this->is_ajax_request())
        {
            if(empty($this->params ['rid']))
            {
                return $this->result(-1, '角色id错误');
            }

            $rid = intval($this->params['rid']);
            $role_info = $this->ACL->get_role_info(array('id'=>$rid));

            $data ['role_info'] = $role_info;
            $data ['menu_list'] = $this->ACL->init_menu_list();

            $this->view->edit_role( $data );
        }
    }

    /**
     * 管理员列表
     */
    function user()
    {
        $u = $this->ADMIN->get_user_list();
        foreach($u as $k => $ui)
        {
            if($ui['rid'] == 0)
            {
                unset($u[$k]);
            }
        }

        $data ['u'] = $u;
        $data['roles'] = $this->ACL->get_role_list();

        if($this->is_post())
        {
            $a = $_POST['a'];

            foreach($a as $k=>$v)
            {
                if($k == '+')
                {
                    if(! empty($v ['name']) && ! empty($v ['password']))
                    {
                        $this->ADMIN->add_admin($v);
                    }
                }
                else
                {
                    if(! empty($v['name']) )
                    {
                        $this->ADMIN->update( $v, array('id'=>$k) );
                    }
                    else
                    {
                        $this->ADMIN->del( array('id'=>$k) );
                    }
                }
            }

            $this->to("acl:user");
        }

        $this->view->display( $data );
    }
}



