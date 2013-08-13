<?php defined('DOCROOT')or die('Access Denied');
/**
* Author:       wonli
* Contact:      wonli@live.com
* Date:         2011.08
* Description:  Helper
*/
class App_Home_Helper_MakeLink extends Helper
{

    static function mkurl($data)
    {
        $url = array();

        foreach($data as $v)
        {
            if(ROUTER)
            {
                $url[] = '<a href="'.APPHOME.'news/detail/'.$v['aid'].'">'.$v['atitle'].'</a>';
            } else {
                $url[] = '<a href="'.APPHOME.'?c=news&a=detail&p='.$v['aid'].'">'.$v['atitle'].'</a>';
            }
        }
        return $url;
    }
    
    static function app_link()
    {
        $url = array();
        if(ROUTER)
        {
            $url['register'] = APPHOME.'/Register';
            $url['login']    = APPHOME.'/Login';
            $url['member']   = APPHOME.'/Member';
            $url['logout']   = APPHOME.'/Logout';
            
        } else {
            
            $url['register'] = APPHOME.'?Register';
            $url['login']    = APPHOME.'?Login';
            $url['member']   = APPHOME.'?Member';
            $url['logout']   = APPHOME.'?Logout';
        }
        return $url;
    }

}