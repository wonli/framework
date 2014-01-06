<?php
/**
* @Author: wonli <wonli@live.com>
*/
class AdminModule extends CoreModule
{
    /**
     * @var string
     */
    protected $t_a = 'back_admin';

    /**
     * 登录验证
     *
     * @param $username
     * @param $password
     * @param string $scode_location
     * @param string $scode_value
     * @return array|string
     */
    function check_admin($username, $password, $scode_location='', $scode_value='')
    {
        $SEC = $this->load("SecurityModule");
        $userinfo = $this->link->get('back_admin', '*', array('name'=>$username));

        if($userinfo ['t'] != 1)
        {
            return $this->result(-5, "帐号已禁用");
        }

        if($userinfo && !empty($userinfo ["password"]))
        {
            $user_password = sha1( md5($password) );
            $is_bind = $SEC->checkbind($username);

            if($is_bind) {
                if( empty($scode_location) || empty($scode_value) ) {
                    return $this->result("-3", "请输入正确的安全码");
                }

                $verify_right = $SEC->verifyscode($username, $scode_location, $scode_value);
                if(! $verify_right) {
                    return $this->result("-4", "安全认证失败");
                }
            }

            if($user_password === $userinfo ["password"]) {
                return $this->result("1", "登录成功");
            }
            return $this->result("-1", "密码验证失败");
        }

        return $this->result("-2", "用户名或密码不能为空");
    }

    /**
     * 管理员列表
     */
    function get_user_list()
    {
        return $this->link->getAll($this->t_a, "*");
    }

    /**
     * 新增加管理员
     *
     * @param $data
     * @return bool
     */
    function add_admin($data)
    {
        if(empty($data['name']) || empty($data['password']))
        {
            return false;
        }

        $accept_field = array('name', 'password', 'rid', 't');
        foreach($data as $k => & $d)
        {
            if(! in_array($k, $accept_field))
            {
                unset($data[$k]);
            }

            if($k === 'password')
            {
                $d = sha1( md5($data ['password']) );
            }
        }

        $this->link->add($this->t_a, $data);
        return true;
    }

    /**
     * 根据condition查询管理员信息
     *
     * @param $condition
     * @return mixed
     */
    function get_admin_info($condition)
    {
        return $this->link->get($this->t_a, '*', $condition);
    }

    /**
     * 删除用户
     *
     * @param $condition
     * @return mixed
     */
    function del( $condition )
    {
        return $this->link->del( $this->t_a, $condition );
    }

    /**
     * 更新管理员列表
     *
     * @param $data
     * @param $condition
     * @return array|string
     */
    function update($data, $condition)
    {
        $admin_info = $this->link->get($this->t_a, '*', $condition);

        if(! $admin_info )
        {
            return $this->result(-1, "没找到用户信息");
        }

        //更新密码
        if($admin_info ['password'] !== $data['password'])
        {
            $data ['password'] = sha1( md5($data ['password']) );
        }

        $this->link->update($this->t_a, $data, $condition);

        return $this->result(1, 'ok');
    }
}

