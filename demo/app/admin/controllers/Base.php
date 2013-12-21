<?php defined('DOCROOT')or die('Access Denied');
/**
* @Author: wonli <wonli@live.com>
*/
class Base extends CoreController
{
    /**
     * @var string
     */
    protected $u;

    /**
     * @var AclModule
     */
    protected $ACL;

    function __construct()
    {
        parent::__construct();

        if( ! empty($_SESSION["admin"]) )
        {
            $this->u = $_SESSION["admin"];
        }
        else
        {
            $this->to();
        }

        $this->ACL = new AclModule();
        $this->ADMIN = new AdminModule();

        /**
         * 查询登录用户信息
         */
        $user_info = $this->ADMIN->get_admin_info(array('name'=>$this->u));
        $role_id = $user_info['rid'];

        /**
         * 导航菜单
         */
        $nav_menu_data = $this->ACL->get_menu( );

        /**
         * 判断是否是超级管理员
         */
        if($role_id == 0)
        {
            /**
             * 设置view导航数据
             */
            $this->view->set_nav_menu($nav_menu_data);

            if( isset( $nav_menu_data [ strtolower($this->controller) ] ) )
            {
                $_menu =  $nav_menu_data [ strtolower($this->controller) ];
                $c_menu = $this->ACL->get_c_menu( $_menu ['id'] );
            }
            else
            {
                $c_menu = array();
            }

            $this->view->set_menu($c_menu);
        }
        else
        {
            /**
             * 查询所属管理角色
             */
            $role_info = $this->ACL->get_role_info(array('id'=>$role_id));

            /**
             * 角色允许的方法
             */
            $accept_behavior = explode(',', $role_info ['behavior']);

            /**
             * 只保留允许访问的菜单
             */
            foreach($nav_menu_data as $k => $nav)
            {
                if(! in_array($nav['id'], $accept_behavior))
                {
                    unset( $nav_menu_data[ $k ] );
                }
            }

            /**
             * 设置view导航数据
             */
            $this->view->set_nav_menu($nav_menu_data);

            if( isset( $nav_menu_data [ strtolower($this->controller) ] ) )
            {
                $_menu =  $nav_menu_data [ strtolower($this->controller) ];
                $c_menu = $this->ACL->get_c_menu( $_menu ['id'] );
                $controller = strtolower($this->controller);
            }
            else
            {
                /**
                 * 如果没有访问权限 使用有权限的第一个设置
                 */
                $accept_menus = array_keys( $nav_menu_data );
                $default_menu = $nav_menu_data [ $accept_menus [0] ];
                $c_menu = $this->ACL->get_c_menu( $default_menu ['id'] );

                $this->to($accept_menus[0]);
                $controller = strtolower($accept_menus[0]);
            }

            $accept_action = array();
            foreach($c_menu as $c_key => $c_value)
            {
                /**
                 * 过滤无权限的菜单
                 */
                if(! in_array($c_value ['id'], $accept_behavior) )
                {
                    unset( $c_menu [$c_key] );
                }
                else
                {
                    $accept_action [] = $c_value ['link'];
                }
            }

            $this->view->set_menu($c_menu);

            /**
             * 如果访问没权限的action 则跳转到默认第一个有权限的菜单
             */
            if(! in_array($this->action, $accept_action))
            {
                $_accept_action = $accept_action [0];

                $this->to("{$controller}:{$_accept_action}");
            }
        }
    }
}
