<?php defined('DOCROOT')or die('Access Denied');
/**
* @Author: wonli <wonli@live.com>
*/
class AclModule extends BaseModule
{
    /**
     * @var string 表名
     */
    protected $t_acl_menu = "back_acl_menu";

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
     * 删除
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

            $this->link->update($this->t_acl_menu, $data, array('id' => $p['id']));
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
        $menu = $this->link->getAll($this->t_acl_menu, '*', array('pid'=>0, 'status'=>1), '`order` ASC');

        foreach($menu as $m) {
            $menu_list[$m["link"]] = $m;
        }

        return $menu_list;
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
        return $this->link->getAll($this->t_acl_menu, '*', array('pid'=>$pid, 'display'=>1), '`order` ASC');
    }
}