<?php defined('DOCROOT')or die('Access Denied');
/**
* @Author: wonli <wonli@live.com>
*/
class SecurityView extends BaseView
{
    function output($notes)
    {
        if(is_array($notes))
        {
            self::_print($notes);

        } else {
            if($notes == -1)
            {
                echo '密保卡已经过期!';
            } elseif ($notes == -2) {
                echo '你还未绑定密保卡!';
            }
        }
    }

    function create($notes)
    {
        var_dump($notes);
    }

    function bind($notes)
    {
        if($notes == -1) {
            echo '绑定失败';
        }

        if($notes == 1)
        {
            echo '绑定成功!';
        } elseif ($notes == 3) {
            echo '你已经绑定过啦!';
        }
    }

    function download($notes)
    {
        if(isset($notes["ok"]) && $notes["ok"] < 0)
        {
            echo $notes["msg"];
        }
    }

    function refresh($notes)
    {
        if($notes == 1)
        {
            echo '更新成功! <a href="'.$this->link("security:download").'">点此下载</a>';
        } elseif ($notes == -1) {
            echo '操作失败!请联系管理员!';
        } elseif ($notes == -2) {
            echo '请先绑定密保卡!';
        }
    }

    function kill($notes)
    {
        if($notes == 1)
        {
            echo '解除绑定成功!';
        } elseif ($notes == -1) {
            echo '操作失败!请联系管理员!';
        } elseif ($notes == -2) {
            echo '请先绑定密保卡!';
        }
    }

    function _print($notes)
    {
        $scodedata = $notes;

        echo '<table style="width:350px;height:350px;text-align:center;border:1px solid #808080;margin:20px;border-collapse: collapse;"><tr><td>&nbsp;&nbsp;</td>';
        for($i=1;$i<=9;$i++)
        {
            echo '<td style="background:#ffffee;border:1px solid #808080">'.$i.'</td>';
        }
        echo '</tr>';

        foreach($scodedata as $k=>$v)
        {
            echo '<tr><td style="background:#eeffff;border:1px solid #808080">'.$k.'</td>';
            for($i=1;$i<=9;$i++)
            {
                echo '<td style="border:1px solid #808080">'.$v[$i].'</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    }

    function setnotes($notes)
    {
        if($notes == -1) {
            echo '原密码不正确';
        }

        if($notes == 1) {
            echo '修改成功!';
        }

        if($notes == -2) {
            echo '修改失败!请联系管理员';
        }
    }

    function changepassword($data)
    {
        if(! empty( $data ['notes'] ) )
        {
            $this->js_notes($data['notes']);
        }

        include $this->tpl('security/changepassword');
    }
}
