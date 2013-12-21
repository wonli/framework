<?php
/**
* @Author: wonli <wonli@live.com>
*/
class SecurityModule extends AdminModule
{
    private $t_sec = "back_securitycard";

    /**
     * 验证老密码
     *
     * @param $inp_pwd
     * @return bool
     */
    function checkPassword( $inp_pwd )
    {
        $admin_info = $this->link->get("back_admin", "*", array('name'=>$_SESSION['admin']));
        return $admin_info ['password'] === sha1( md5($inp_pwd) );
    }

    /**
     * 更新密码
     *
     * @param $inp_pwd
     * @return array|string
     */
    function update_password( $inp_pwd )
    {
        $np = sha1( md5($inp_pwd));
        $status = $this->link->update("back_admin", array('password'=>$np),
            array('name'=>$_SESSION['admin']));

        if($status)
        {
            return $this->result(1, "更改成功");
        }

        return $this->result(-2, "更新失败");
    }
    
    /**
     * 生成密保卡数据
     *
     * @param
     * @return array
     */
    private function makeSecurityCode($is_serialize = true)
    {
        $security = array();
        $str = '3456789abcdefghjkmnpqrstuvwxy';

        for($k = 65; $k<74; $k++)
        {
            for($i = 1; $i<=9; $i++)
            {
                $_x=substr(str_shuffle($str), $i, $i+2);
                $security[chr($k)][$i] = $_x[0].$_x[1];
            }
        }
        if($is_serialize === true) {        
            return serialize($security);
        }
        return $security;
    }
    
    /**
     * 随机生成密保卡坐标
     *
     * @param
     * @return string
     */
    function shuffle_location()
    {
        $strx = '123456789';
        $stry = 'ABCEDEGHI';
        $scode = array();

        for($i=0; $i<9; $i++)
        {
            for($k=0; $k<9; $k++)
            {
                $scode[] = $stry[$i].$strx[$k];
            }
        }

        shuffle($scode);
        $scode =  array_slice($scode, 0, 2);
        return $scode[0].$scode[1];
    }

    /**
     * 绑定密保卡
     *
     * @param $bind_user
     * @return int
     */
    function bindcard($bind_user)
    {
        $card_data = $this->makeSecurityCode();
        $isbind = $this->checkbind($bind_user);

        if($isbind)
        {        
            return 3;
        } else {

            $data = array(
                'card_data' => $card_data,
                'bind_user' => $bind_user,
            );

            $card_id = $this->link->add($this->t_sec, $data);

            if( $card_id ){
                return 1;
            } 
            
            return -1;
        }
    }        

    /**
     * 更新密保卡
     *
     * @param $bind_user
     * @return int
     */
    function updateCard($bind_user)
    {
        $card_data = self::makeSecurityCode();
        $isbind = self::checkbind($bind_user);

        if($isbind)
        {
            $data = array(
                'card_data' => $card_data,
            );

            $up_status = $this->link->update($this->t_sec, $data, array( 'bind_user'=>$bind_user ));

            if( $up_status )
            {
                return 1;
            } else { 
                return -1;
            }
        } else {
            return -2;
        } 
    }
    
    /**
     * 取消绑定
     *
     * @param string $bind_user
     * @return bool;
     */
    function killbind($bind_user)
    {
        $isbind = self::checkbind($bind_user);
    
        if($isbind)
        {
            $del_status = $this->link->del($this->t_sec, array('bind_user' => $bind_user));

            if( $del_status ) return 1;
            else return -1;
            
        } else {
            return -2;
        }
    }
    
    /**
     * 检查是否绑定过密保卡
     *
     * @param string $bind_user
     * @return bool
     */
    public function checkbind($bind_user)
    {
        $id = $this->link->get($this->t_sec, 'id', array('bind_user'=>$bind_user));

        if(! empty($id) ) return true;
        else return false;
    }
    
    /**
     * 取得密保卡数据
     *
     * @param string 
     * @return array
     */
    private function getSecrityData($bind_user)
    {
        $isbind = $this->checkbind($bind_user);
        
        if($isbind)
        {
            $data = $this->link->get($this->t_sec, '*', array('bind_user'=>$bind_user));
            return array($data['ext_time'], unserialize($data['card_data']));
        }

        return false;
    }

    /**
     * 返回密保卡数据
     *
     * @param $bind_user
     * @return int
     */
    function secrityData($bind_user)
    {
        $isbind = $this->checkbind($bind_user);
        
        if($isbind)
        {
            $data = $this->getSecrityData($bind_user);
            
            if($data[0] != -1)
            {
                return $data[1];
            } else {
                return -1;
            }
            
        } else {
        
            return -2;
        }
    }

    /**
     * 输出密保卡图片
     *
     * @param $binduser
     * @return array|string
     */
    function output_img( $binduser )
    {
    
        $isbind = $this->checkbind($binduser);
        
        if(! $isbind) {
            //没有数据
            return $this->result("-1", "还未绑定密保卡");
        }
    
        $data = $this->secrityData($binduser);        
        
        if(! $data) {
            return $this->result("-2", "密保卡已过期或不存在");
        }
        
        $im = imagecreatetruecolor(520, 520);
        // 设置背景为白色
        imagefilledrectangle($im, 31, 31, 520, 520, 0xFFFFFF);
        
        $_front = 5;
        $_space = 50;
        $_margin = 20;
        
        $_y = $_x = $_i = 0;           
        
        //在图像上写字
        if(is_array($data)) {  

            $color = imagecolorallocate($im, 45, 45, 45);
            $color2 = imagecolorallocate($im, 205, 205, 205);
            
            imageline($im, $_x+30, 0, $_x+30, 480, $color);
            imageline($im, 0, 0, 0, 480, $color);
            
            
            imageline($im, 0, $_y+30, 480, $_x+30, $color);
            imageline($im, 0, 0, 480, 0, $color);
        
            foreach($data as $y => $c) 
            {                  
                ++$_i;                        
                
                imagestring($im, $_front, $_margin-10, $_y+$_space, $y, 0xFFBB00); 
                imagestring($im, $_front, $_x+$_space, $_margin-10, $_i, 0xFFBB00);             
                
                $_x = $_y += $_space;

                $_code_location = 0;
                foreach($c as $code) {
                    $_code_location += $_space;
                    imagestring($im, $_front, $_code_location, $_y, $code, 0x336699); 
                }

                imageline($im, $_x+30, 0, $_x+30, 480, $color);
                imageline($im, 0, $_y+30, 480, $_x+30, $color);            
                
            }
            
            imagestring($im, $_front, 350, $_y+46, "power by crossphp", 0xCCCCCC);
            
            imageline($im, 519, 519, 500, 520, $color2);
            imageline($im, 519, 519, 520, 500, $color2);
        }
        
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename='.$binduser.'_seccard.png'); 
        imagepng($im);
    }    

    /**
     * 验证密保卡
     *
     * @param $user
     * @param $location
     * @param $inputscode
     * @return bool|int
     */
    function verifyscode($user, $location, $inputscode)
    {
        $data = $this->getSecrityData($user);
        
        if($data[0] != -1)
        {
            $scodedata  = $data[1];
        } else {
            return -1;
        }

        $right_scode = $scodedata[$location[0]][$location[1]].$scodedata[$location[2]][$location[3]];
       
        #判断是否相等
        if($inputscode == $right_scode) return true;
        else return false;
    }

    /**
     * 创建代码
     * @return mixed
     */
    function create_table()
    {
        $create_sql = "CREATE TABLE `back_securitycard` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `card_data` TEXT NOT NULL COLLATE 'utf8_unicode_ci',
            `bind_user` VARCHAR(255) NOT NULL COLLATE 'utf8_unicode_ci',
            `ext_time` INT(11) NOT NULL COMMENT '已过期,-1',
            PRIMARY KEY (`id`),
            INDEX `binduser` (`binduser`)
        )
        COLLATE='utf8_unicode_ci'
        ENGINE=InnoDB
        AUTO_INCREMENT=1";
    
        return $this->link->execute($create_sql);
    }
}
