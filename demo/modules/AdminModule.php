<?php defined('DOCROOT')or die('Access Denied');
/**
* @Author: wonli <wonli@live.com>
*/
class AdminModule extends BaseModule
{
    function check_admin($username, $password, $scode_location='', $scode_value='')
    {
        $SEC = $this->load("SecurityModule");
        $userinfo = $this->link->get('back_admin', '*', array('name'=>$username));

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
}

