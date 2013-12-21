<?php
/**
* @Author: wonli <wonli@live.com>
*/
class AclModule extends AdminModule
{
    /**
     * @var string 表名
     */
    protected $t_acl_menu = "back_acl_menu";

    /**
     * @var string 角色表名
     */
    protected $t_role = "back_acl_role";

    /**
     * @var string 行为表
     */
    protected $t_behavior = "back_acl_behavior";

    /**
     * 增加导航菜单
     *
     * @param $name
     * @param $link
     * @param int $pid
     * @return array|string
     */
    function add_nav($name, $link, $pid=0)
    {
        $menu_id = $this->link->add($this->t_acl_menu, array(
            'pid' => $pid,
            'name'  => $name,
            'link'  => $link,
        ));

        if($menu_id) return $this->result(1, "保存成功");
        else return $this->result(-1, "保存失败,请联系管理员");
    }

    /**
     * 删除导航
     *
     * @param $nav_id
     * @return mixed
     */
    function del_nav($nav_id)
    {
        return $this->link->del($this->t_acl_menu, array(
                'id' => $nav_id
        ));
    }

    /**
     * 初始化菜单
     *
     * @return mixed
     */
    function init_menu_list()
    {
        /**
         * 要过滤的方法
         */
        $_filter = array("__construct", "__descruct", "__tostring", "__call", "__set", "__get");

        /**
         * 所有导航菜单
         */
        $menu_list = $this->get_menu_list(0);

        foreach($menu_list as & $m)
        {
            $cname = ucfirst($m["link"]);

            /**
             * 控制器文件物理路径
             */
            $controller_file = APP_PATH.DS.'controllers'.DS.$cname.'.php';

            /**
             * 获取子菜单数据及整理菜单格式
             */
            $c_menu_data = $this->get_menu_list($m["id"]);
            $c_menu_list = array();

            foreach($c_menu_data as $cm)
            {
                $c_menu_list[ $cm["link"] ] ["id"] = $cm ["id"];
                $c_menu_list[ $cm["link"] ] ["name"] = $cm ["name"];
                $c_menu_list[ $cm["link"] ] ["display"] = $cm ["display"];
                $c_menu_list[ $cm["link"] ] ["order"] = $cm["order"];
            }

            /**
             * 判断物理文件是否存在
             */
            if(file_exists($controller_file))
            {
                /**
                 * 使用反射API 取得类中的名称
                 */
                $rc = new ReflectionClass($cname);
                $method = $rc->getMethods( ReflectionMethod::IS_PUBLIC );

                /**
                 * 清除类中不存在但存在数据库中的方法
                 */
                foreach($c_menu_list as $cm_key => $cm_value)
                {
                    if(! $rc->hasMethod($cm_key))
                    {
                        unset($c_menu_list[$cm_key]);
                        $this->del_nav( $cm_value['id'] );
                    }
                }

                foreach($method as $mm)
                {
                    if( $mm->class == $cname)
                    {
                        /**
                         * 类名称是否在过滤列表
                         */
                        if(! in_array($mm->name, $_filter))
                        {
                            if( isset( $c_menu_list [ $mm->name ] ) )
                            {
                                $m ["method"][$mm->name] = $c_menu_list [ $mm->name ];
                            } else {
                                $m ["method"][$mm->name] = '' ;
                            }
                        }
                    }
                }

            } else {
                $m["error"] = "-1";
                $m["method"] = array();
            }
        }

        return $menu_list;
    }

    /**
     * 保存导航菜单
     *
     * @param $params
     * @return bool
     */
    function save_nav($params)
    {
        foreach($params as $p)
        {
            $data = array(
                'name' => $p ['name'],
                'link' => $p ['link'],
                '`order`' => !empty($p['order']) ? $p ['order'] : 0,
                'status' => $p ['status']
            );

            if(isset($p['id']))
            {
                $this->link->update($this->t_acl_menu, $data, array('id' => $p['id']));
            }
            else
            {
                $this->link->add( $this->t_acl_menu, $data );
            }
        }
        return true;
    }

    /**
     * 返回菜单列表
     *
     * @return array
     */
    function get_menu()
    {
        $menu_list = array();
        $count = $this->link->get($this->t_acl_menu, 'count(1) cnt', array('pid'=>0, 'status'=>1), '`order` ASC');

        if(! $count['cnt'])
        {
            $this->init_menu4controllers();
        }

        $menu = $this->link->getAll($this->t_acl_menu, '*', array('pid'=>0, 'status'=>1), '`order` ASC');
        foreach($menu as $m)
        {
            $menu_list[$m["link"]] = $m;
        }

        return $menu_list;
    }

    /**
     * 从控制器中初始化菜单数据
     */
    function init_menu4controllers()
    {
        $controller_file = APP_PATH.DS.'controllers'.DS;
        $nav_data = array();
        foreach( glob($controller_file.'*.php') as $f)
        {
            $fi = pathinfo($f);
            $class_name = $fi['filename'];
            $nav_data [] = array(
                'name'      =>  strtolower( $class_name ),
                'link'      =>  strtolower( $class_name ),
                'status'    =>  1,
            );
        }

        $this->save_nav( $nav_data );
    }

    /**
     * 菜单修改
     *
     * @param array $menu
     */
    function save_menu(array $menu)
    {
    	//已经保存在数据库中的菜单
    	$menu_data = $this->get_menu_list();
		foreach($menu_data as $m) {
            if($m ['pid'] == 0)
            {
                $menu_list2 [ $m['id'] ] = array();
            }
		}

        foreach($menu_data as $ml)
        {
            $menu_list2 [ $ml['pid'] ] [$ml ['link']] = $ml;
        }

        $menu_list = $menu_list2;

        foreach($menu as $pid => $change_data)
        {
            if(isset($menu_list [$pid]))
            {
                $be_change = $menu_list [ $pid ];

                foreach($change_data as $change_key => $change_value)
                {
                    if( isset( $be_change [ $change_key ] ) )
                    {
                        //更新
                        $_change ['name']    = $change_value['name'];
                        $_change ['display'] = isset($change_value ['display']) && $change_value ['display'] == 'on' ? 1 : 0;
                        $_change ['`order`'] = empty($change_value ['order']) ? 0 : intval($change_value ['order']);

                        $this->link->update($this->t_acl_menu, $_change, array(
                                'id' => $be_change [ $change_key ] ['id']
                        ));
                    }
                    else
                    {
                        //新增方法
                        if( empty( $change_value ['name'] ) )
                        {
                            continue;
                        }

                        unset($change_value['order']);

                        $change_value ['pid'] = $pid;
                        $change_value ['link'] = $change_key;
                        $change_value ['`order`'] = empty($change_value ['order']) ? 0 : intval($change_value ['order']);
                        $change_value ['display'] = isset($change_value ['display']) && $change_value ['display'] == 'on' ? 1 : 0;
                        $change_value ['type'] = 1;
                        $change_value ['status'] = 1;

                        $this->link->add($this->t_acl_menu, $change_value);
                    }
                }
            }
        }
    }

    /**
     * @return mixed 角色列表
     */
    function get_role_list()
    {
        return $this->link->getAll($this->t_role, '*');
    }

    /**
     * 查询role详细信息
     *
     * @param $condition
     * @return mixed
     */
    function get_role_info($condition)
    {
        return $this->link->get($this->t_role, '*', $condition);
    }

    /**
     * 保存菜单设置
     *
     * @param $menu_name
     * @param $data
     * @return array|string
     */
    function save_role_menu($menu_name, $data )
    {
        if(! $menu_name)
        {
            return $this->result(-1, "角色名不能为空");
        }

        if(empty( $data) )
        {
            return $this->result(-2, "菜单不能为空");
        }

        $save_data ['name'] = $menu_name;
        $save_data ['behavior'] = implode($data, ',');

        $_role_info = $this->link->get($this->t_role, "*", array('name'=>$menu_name));

        if($_role_info)
        {
            return $this->result(-3, "角色已存在");
        }

        $rid = $this->link->add($this->t_role, $save_data);
        if($rid)
        {
            return $this->result(1, $rid);
        }

        return $this->result(-5, '添加失败');
    }

    /**
     * 编辑角色菜单权限
     *
     * @param $rid
     * @param $menu_name
     * @param $data
     * @return array|string
     */
    function edit_role_menu($rid, $menu_name, $data)
    {
        if(! $menu_name)
        {
            return $this->result(-1, "角色名不能为空");
        }

        if(empty( $data) )
        {
            return $this->result(-2, "菜单不能为空");
        }

        $save_data ['name'] = $menu_name;
        $save_data ['behavior'] = implode($data, ',');

        $_role_info = $this->link->get($this->t_role, "*", array('id'=>$rid));

        if(! $_role_info)
        {
            return $this->result(-4, '角色不存在');
        }

        $rid = $_role_info['id'];
        $status = $this->link->update($this->t_role, $save_data, array('id'=>$rid));

        if($status)
        {
            return $this->result(1, $rid);
        }

        return $this->result(-5, '更新失败');
    }

    /**
     * 导航菜单列表
     *
     * @param null $pid
     * @return mixed
     */
    function get_menu_list($pid=null)
    {
        $params = array();

    	if(null !== $pid) {
            $params = array('pid' => $pid);
        }

        $menu_list = $this->link->getAll($this->t_acl_menu, '*', $params, '`order` ASC');

        return $menu_list;
    }

    /**
     * 一级菜单列表
     *
     * @return mixed
     */
    function get_nav_list()
    {
        return $this->link->getAll($this->t_acl_menu, '*', array(
            'pid' => '0',
        ), '`order` ASC');
    }

    /**
     * 查询子菜单
     *
     * @param $pid
     * @return mixed
     */
    function get_c_menu($pid)
    {
        $result = $this->link->getAll($this->t_acl_menu, '*', array('pid'=>$pid, 'display'=>1), '`order` ASC');

        if(empty($result)) {
            $result = array();
        }

        return $result;
    }
}
